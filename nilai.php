<?php

declare(strict_types=1);
include 'config/cek_login.php';
include 'config/conn.php';

if (isset($_POST['simpan'])) {
    foreach ($_POST['nilai'] as $id_alternatif => $kriteria_nilai) {
        foreach ($kriteria_nilai as $id_kriteria => $nilai) {            
            $nilai = floatval($nilai);

            $cek = $conn->prepare("SELECT id_nilai FROM nilai WHERE id_alternatif=? AND id_kriteria=?");
            $cek->bind_param("ii", $id_alternatif, $id_kriteria);
            $cek->execute();
            $cek->store_result();

            if ($cek->num_rows > 0) {
                $stmt = $conn->prepare("UPDATE nilai SET nilai=? WHERE id_alternatif=? AND id_kriteria=?");
                $stmt->bind_param("dii", $nilai, $id_alternatif, $id_kriteria);
            } else {
                $stmt = $conn->prepare("INSERT INTO nilai (id_alternatif, id_kriteria, nilai) VALUES (?, ?, ?)");
                $stmt->bind_param("iid", $id_alternatif, $id_kriteria, $nilai);
            }

            if ($stmt->execute()) {
                // Berhasil
            } else {
                // Gagal, bisa tambahkan penanganan error di sini jika diperlukan
            }
            $stmt->close();
            $cek->close();
        }
    }

    header("Location: nilai.php");
    exit;
}

include 'view/header.php';

$alternatif = $conn->query("SELECT * FROM alternatif");
if (!$alternatif) {
    die("Query Alternatif gagal: " . $conn->error);
}

$kriteria = $conn->query("SELECT * FROM kriteria");
if (!$kriteria) {
    die("Query Kriteria gagal: " . $conn->error);
}


$nilaiData = [];
$resultNilai = $conn->query("SELECT * FROM nilai");
if ($resultNilai) {
    while ($row = $resultNilai->fetch_assoc()) {
        $nilaiData[$row['id_alternatif']][$row['id_kriteria']] = formatSkorAkhir($row['nilai']);
    }
} else {
    die("Query Nilai gagal: " . $conn->error);
}

function formatSkorAkhir($skor) {
    return rtrim(rtrim($skor, '0'), '.');
}

?>


<div class="content mt-5">
    <h2>Input Nilai Alternatif</h2>

    <form method="POST">
        <table class="table table-bordered">
            <thead class="table-light">
                <tr>
                    <th>Alternatif</th>
                    <?php
                    $kriteria_array = []; 
                    if ($kriteria->num_rows > 0) {
                        $kriteria->data_seek(0); 
                        while ($k = $kriteria->fetch_assoc()) {
                            $kriteria_array[] = $k; 
                    ?>
                            <th><?= htmlspecialchars($k['nama_kriteria']); ?> <br><small>(<?= htmlspecialchars($k['sifat']); ?>)</small></th>
                    <?php
                        }
                    }
                    ?>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($alternatif->num_rows > 0) {
                    $alternatif->data_seek(0);
                    while ($alt = $alternatif->fetch_assoc()) {
                ?>
                        <tr>
                            <td><?= htmlspecialchars($alt['nama_alternatif']); ?></td>
                            <?php foreach ($kriteria_array as $k) :
                            ?>
                                <td>
                                    <input type="text"  name="nilai[<?= $alt['id_alternatif']; ?>][<?= $k['id_kriteria']; ?>]"
                                        value="<?= $nilaiData[$alt['id_alternatif']][$k['id_kriteria']] ?? 0; ?>"
                                        class="form-control" required>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                <?php
                    }
                } else {
                ?>
                    <tr>
                        <td colspan="<?= ($kriteria->num_rows > 0) ? ($kriteria->num_rows + 1) : 1; ?>" class="text-center">Belum ada data alternatif atau kriteria.</td>
                    </tr>
                <?php
                }
                ?>
            </tbody>
        </table>

        <div class="text-end">
            <button type="submit" name="simpan" class="btn btn-success">Simpan Semua Nilai</button>
        </div>
    </form>
</div>

<?php
include 'view/footer.php';
?>