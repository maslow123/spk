<?php

declare(strict_types=1);
require_once 'vendor/autoload.php';
include 'config/cek_login.php';
include 'config/conn.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$selected_supplier = $_GET['supplier'] ?? '';
$selected_kategori = $_GET['kategori'] ?? '';
$selected_jenis_barang = $_GET['jenis_barang'] ?? '';

function getSupplierRankingForExport($conn, $supplier = '', $kategori = '', $jenis_barang = '') {
    $where_conditions = [];
    $params = [];
    $types = '';
    
    if (!empty($supplier)) {
        $where_conditions[] = "a.nama_alternatif = ?";
        $params[] = $supplier;
        $types .= 's';
    }
    
    if (!empty($kategori)) {
        $where_conditions[] = "a.kategori = ?";
        $params[] = $kategori;
        $types .= 's';
    }
    
    if (!empty($jenis_barang)) {
        $where_conditions[] = "a.jenis_barang = ?";
        $params[] = $jenis_barang;
        $types .= 's';
    }
    
    $where_clause = '';
    if (!empty($where_conditions)) {
        $where_clause = " AND " . implode(" AND ", $where_conditions);
    }
    
    $sql = "
        WITH filtered_alternatif AS (
            SELECT * FROM alternatif a 
            WHERE 1=1 $where_clause
        ),
        minmax_kriteria AS (
            SELECT
                id_kriteria,
                MAX(nilai) AS max_nilai,
                MIN(nilai) AS min_nilai
            FROM nilai n
            JOIN filtered_alternatif a ON n.id_alternatif = a.id_alternatif
            GROUP BY id_kriteria
        ),
        nilai_normalisasi AS (
            SELECT
                n.id_alternatif,
                k.id_kriteria,
                k.bobot AS wj,
                CASE
                    WHEN k.sifat = 'benefit' THEN
                        100 * ((n.nilai - m.min_nilai) / NULLIF((m.max_nilai - m.min_nilai), 0))
                    WHEN k.sifat = 'cost' THEN
                        100 * ((m.max_nilai - n.nilai) / NULLIF((m.max_nilai - m.min_nilai), 0))
                END AS uj
            FROM nilai AS n
            JOIN kriteria AS k ON n.id_kriteria = k.id_kriteria
            JOIN minmax_kriteria AS m ON n.id_kriteria = m.id_kriteria
            JOIN filtered_alternatif AS a ON n.id_alternatif = a.id_alternatif
        ),
        sum_utilitas AS (
            SELECT
                id_alternatif,
                SUM(wj * uj) AS total_utilitas
            FROM nilai_normalisasi
            GROUP BY id_alternatif
        ),
        skor_akhir AS (
            SELECT
                a.nama_alternatif,
                a.kategori,
                a.jenis_barang,
                1 * s.total_utilitas AS skor_total
            FROM sum_utilitas s
            JOIN filtered_alternatif a ON s.id_alternatif = a.id_alternatif
            WHERE s.total_utilitas > 30000
        )
        SELECT
            DENSE_RANK() OVER (ORDER BY skor_total DESC, nama_alternatif ASC) AS peringkat,
            nama_alternatif AS alternatif,
            ROUND(skor_total, 3) AS skor_akhir,
            kategori,
            jenis_barang
        FROM skor_akhir
        ORDER BY skor_total DESC, nama_alternatif ASC
        LIMIT 6;
    ";

    $result = null;
    if (!empty($params)) {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
    } else {
        $result = $conn->query($sql);
    }
    
    $data = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }
    
    return $data;
}

$hasil_perhitungan = getSupplierRankingForExport($conn, $selected_supplier, $selected_kategori, $selected_jenis_barang);

$filter_info = [];
if (!empty($selected_supplier)) $filter_info[] = "Supplier: " . $selected_supplier;
if (!empty($selected_kategori)) $filter_info[] = "Kategori: " . $selected_kategori;
if (!empty($selected_jenis_barang)) $filter_info[] = "Jenis Barang: " . $selected_jenis_barang;

$filter_text = !empty($filter_info) ? implode(" | ", $filter_info) : "Semua Data";

$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Laporan Pemilihan Supplier Terbaik</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            font-size: 12px;
            margin: 20px;
        }
        .header { 
            text-align: center; 
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .header h1 { 
            margin: 0; 
            color: #333;
            font-size: 18px;
        }
        .header h2 { 
            margin: 5px 0; 
            color: #666;
            font-size: 14px;
        }
        .filter-info {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #007bff;
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 20px;
        }
        th, td { 
            border: 1px solid #ddd; 
            padding: 8px; 
            text-align: left;
        }
        th { 
            background-color: #f2f2f2; 
            font-weight: bold;
            text-align: center;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .badge-primary { 
            background-color: #007bff; 
            color: white; 
            padding: 3px 8px; 
            border-radius: 4px;
            font-size: 10px;
        }
        .badge-success { 
            background-color: #28a745; 
            color: white; 
            padding: 3px 8px; 
            border-radius: 4px;
            font-size: 10px;
        }
        .badge-info { 
            background-color: #17a2b8; 
            color: white; 
            padding: 3px 8px; 
            border-radius: 4px;
            font-size: 10px;
        }
        .footer {
            position: fixed;
            bottom: 20px;
            right: 20px;
            font-size: 10px;
            color: #666;
        }
        .no-data {
            text-align: center;
            color: #666;
            font-style: italic;
            padding: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>LAPORAN PEMILIHAN SUPPLIER TERBAIK</h1>
        <h2>Sistem Penunjang Keputusan Metode SMART</h2>
        <p>Tanggal: ' . date('d F Y H:i:s') . '</p>
    </div>
    
    <div class="filter-info">
        <strong>Filter yang Diterapkan:</strong> ' . htmlspecialchars($filter_text) . '
    </div>';

if (empty($hasil_perhitungan)) {
    $html .= '<div class="no-data">Tidak ada data supplier yang memenuhi kriteria filter yang dipilih atau skor akhir di atas 30,000.</div>';
} else {
    $html .= '
    <p><strong>Menampilkan maksimal 6 supplier terbaik dengan skor akhir > 30,000</strong></p>
    
    <table>
        <thead>
            <tr>
                <th>Peringkat</th>
                <th>Nama Supplier</th>
                <th>Skor Akhir</th>
                <th>Status Keputusan</th>
                <th>Kategori</th>
                <th>Jenis Barang</th>
            </tr>
        </thead>
        <tbody>';
    
    foreach ($hasil_perhitungan as $data) {
        $html .= '
            <tr>
                <td class="text-center">
                    <span class="badge-primary">' . htmlspecialchars($data['peringkat']) . '</span>
                </td>
                <td>' . htmlspecialchars($data['alternatif']) . '</td>
                <td class="text-right"><strong>' . number_format((float)$data['skor_akhir'], 3) . '</strong></td>
                <td class="text-center">
                    <span class="badge-success">✓ Supplier Terbaik</span>
                </td>
                <td class="text-center">
                    <span class="badge-info">' . htmlspecialchars($data['kategori']) . '</span>
                </td>
                <td>' . htmlspecialchars($data['jenis_barang']) . '</td>
            </tr>';
    }
    
    $html .= '
        </tbody>
    </table>
    
    <div style="margin-top: 20px; font-size: 11px; color: #666;">
        <p><strong>Keterangan:</strong></p>
        <ul>
            <li>Hasil perhitungan menggunakan metode SMART (Simple Multi-Attribute Rating Technique)</li>
            <li>Hanya menampilkan supplier dengan skor akhir di atas 30,000</li>
            <li>Peringkat diurutkan berdasarkan skor tertinggi ke terendah</li>
            <li>Total ' . count($hasil_perhitungan) . ' supplier memenuhi kriteria</li>
        </ul>
    </div>';
}

$html .= '
    <div class="footer">
        Generated by SPK Supplier System - ' . date('Y') . '
    </div>
</body>
</html>';

$options = new Options();
$options->set('defaultFont', 'Arial');
$options->set('isPhpEnabled', true);
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape'); // Landscape untuk table yang lebar
$dompdf->render();

$filename = 'laporan_supplier_terbaik_' . date('Y-m-d_H-i-s') . '.pdf';
$dompdf->stream($filename, ["Attachment" => 1]);

?>