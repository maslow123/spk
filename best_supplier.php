<?php

    declare(strict_types=1);
    include 'config/cek_login.php';
    include 'config/conn.php';
    include 'view/header.php';

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
?>

<div class="content mt-5">
    <h2>Hasil Perhitungan SMART</h2>
    <?php if (empty($hasil_perhitungan)) : ?>
        <div class="alert alert-warning" role="alert">
            Tidak ada data Alternatif, Kriteria, atau Nilai yang cukup untuk melakukan perhitungan.
            Pastikan Anda sudah mengisi data di menu Alternatif, Kriteria, dan Input Nilai.
        </div>
    <?php else : ?>
        <a href="export_pdf.php" class="btn btn-danger mb-3">Export PDF</a>
        <a href="export_excel.php" class="btn btn-success mb-3">Export ke Excel</a>

        <table class="table table-bordered">
            <thead class="table-light">
                <tr>
                    <th>Peringkat</th>
                    <th>Alternatif</th>
                    <th>Skor Akhir</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($hasil_perhitungan as $data) : ?>
                    <tr>
                        <td><?= htmlspecialchars($data['peringkat']); ?></td>
                        <td><?= htmlspecialchars($data['alternatif']); ?></td>
                        <td><?= $data['skor_akhir']; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php
    include 'view/footer.php';
?>