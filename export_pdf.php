<?php

declare(strict_types=1);
include 'config/cek_login.php';
include 'config/conn.php';

require 'vendor/autoload.php';

use Dompdf\Dompdf;

$sql = "
        WITH minmax_kriteria AS (
    SELECT
        id_kriteria,
        MAX(nilai) AS max_nilai,
        MIN(nilai) AS min_nilai
    FROM nilai
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
        1 * s.total_utilitas AS skor_total  -- kalau nanti ada a.bobot, ganti 1 jadi a.bobot
    FROM sum_utilitas s
    JOIN alternatif a ON s.id_alternatif = a.id_alternatif
)
SELECT
    DENSE_RANK() OVER (ORDER BY skor_total DESC, nama_alternatif ASC) AS peringkat,
    nama_alternatif AS alternatif,
    ROUND(skor_total, 3) AS skor_akhir
FROM skor_akhir
ORDER BY skor_total DESC, nama_alternatif ASC;
    ";

$result = $conn->query($sql);

if (!$result) {
    die("Query Perhitungan gagal: " . $conn->error);
}

$hasil_perhitungan = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $hasil_perhitungan[] = $row;
    }
}

$html = '<h2>Hasil SPK pemilihan supplier terbaik dengan metode SMART PT. Mitra Belanja Anda</h2>';
$html .= '<table border="1" cellpadding="5" cellspacing="0" width="100%">';
$html .= '<thead><tr><th>Peringkat</th><th>Alternatif</th><th>Skor Akhir</th></tr></thead><tbody>';

if (!empty($hasil_perhitungan)) {
    foreach ($hasil_perhitungan as $data) {
        $html .= '<tr>';
        $html .= '<td>' . htmlspecialchars($data['peringkat']) . '</td>';
        $html .= '<td>' . htmlspecialchars($data['alternatif']) . '</td>';
        $html .= '<td>' . $data['skor_akhir'] . '</td>';
        $html .= '</tr>';
    }
} else {
    $html .= '<tr><td colspan="3" align="center">Tidak ada data untuk ditampilkan.</td></tr>';
}

$html .= '</tbody></table>';

$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("hasil_spk_smart.pdf", ["Attachment" => true]);
exit;

?>