<?php

declare(strict_types=1);
require_once 'vendor/autoload.php';
include 'config/cek_login.php';
include 'config/conn.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

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

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

$sheet->setTitle('Supplier Terbaik');

$sheet->setCellValue('A1', 'LAPORAN PEMILIHAN SUPPLIER TERBAIK');
$sheet->mergeCells('A1:F1');
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
$sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

$sheet->setCellValue('A2', 'Sistem Penunjang Keputusan Metode SMART');
$sheet->mergeCells('A2:F2');
$sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);
$sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

$sheet->setCellValue('A3', 'Tanggal: ' . date('d F Y H:i:s'));
$sheet->mergeCells('A3:F3');
$sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

$sheet->setCellValue('A5', 'Filter yang Diterapkan: ' . $filter_text);
$sheet->mergeCells('A5:F5');
$sheet->getStyle('A5')->getFont()->setBold(true);

$row = 7;
$headers = ['Peringkat', 'Nama Supplier', 'Skor Akhir', 'Status Keputusan', 'Kategori', 'Jenis Barang'];
$columns = ['A', 'B', 'C', 'D', 'E', 'F'];

foreach ($headers as $index => $header) {
    $sheet->setCellValue($columns[$index] . $row, $header);
    $sheet->getStyle($columns[$index] . $row)->getFont()->setBold(true);
    $sheet->getStyle($columns[$index] . $row)->getFill()->setFillType(Fill::FILL_SOLID);
    $sheet->getStyle($columns[$index] . $row)->getFill()->getStartColor()->setRGB('E3F2FD');
    $sheet->getStyle($columns[$index] . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
}

// Data
$row = 8;
if (empty($hasil_perhitungan)) {
    $sheet->setCellValue('A' . $row, 'Tidak ada data supplier yang memenuhi kriteria');
    $sheet->mergeCells('A' . $row . ':F' . $row);
    $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('A' . $row)->getFont()->setItalic(true);
    $row++;
} else {
    foreach ($hasil_perhitungan as $data) {
        $sheet->setCellValue('A' . $row, $data['peringkat']);
        $sheet->setCellValue('B' . $row, $data['alternatif']);
        $sheet->setCellValue('C' . $row, number_format((float)$data['skor_akhir'], 3));
        $sheet->setCellValue('D' . $row, 'Supplier Terbaik');
        $sheet->setCellValue('E' . $row, $data['kategori']);
        $sheet->setCellValue('F' . $row, $data['jenis_barang']);
        
        $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
        $sheet->getStyle('C' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('C' . $row)->getFont()->setBold(true);
        
        $sheet->getStyle('D' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('D' . $row)->getFill()->setFillType(Fill::FILL_SOLID);
        $sheet->getStyle('D' . $row)->getFill()->getStartColor()->setRGB('C8E6C9');
        
        $sheet->getStyle('E' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('E' . $row)->getFill()->setFillType(Fill::FILL_SOLID);
        $sheet->getStyle('E' . $row)->getFill()->getStartColor()->setRGB('E1F5FE');
        
        $row++;
    }
}

$row += 2;
$sheet->setCellValue('A' . $row, 'KETERANGAN:');
$sheet->getStyle('A' . $row)->getFont()->setBold(true);
$row++;

$keterangan = [
    '• Hasil perhitungan menggunakan metode SMART (Simple Multi-Attribute Rating Technique)',
    '• Hanya menampilkan supplier dengan skor akhir di atas 30,000',
    '• Peringkat diurutkan berdasarkan skor tertinggi ke terendah',
    '• Total ' . count($hasil_perhitungan) . ' supplier memenuhi kriteria',
    '• Generated on: ' . date('d F Y H:i:s')
];

foreach ($keterangan as $text) {
    $sheet->setCellValue('A' . $row, $text);
    $sheet->mergeCells('A' . $row . ':F' . $row);
    $row++;
}

$tableRange = 'A7:F' . (7 + count($hasil_perhitungan));
$sheet->getStyle($tableRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

foreach (range('A', 'F') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

$sheet->getColumnDimension('B')->setWidth(25); // Nama Supplier
$sheet->getColumnDimension('D')->setWidth(18); // Status Keputusan
$sheet->getColumnDimension('F')->setWidth(20); // Jenis Barang

// Output Excel
$filename = 'laporan_supplier_terbaik_' . date('Y-m-d_H-i-s') . '.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');

?>