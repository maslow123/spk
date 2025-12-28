<?php

declare(strict_types=1);
include 'config/cek_login.php';
include 'config/conn.php';

if (isset($_POST['tambah'])) {
    $nama = trim($_POST['nama_kriteria']);
    $bobot = floatval($_POST['bobot']);
    $sifat = $_POST['sifat'];

    if ($nama !== '' && $bobot > 0 && in_array($sifat, ['benefit', 'cost'])) {
        $stmt = $conn->prepare("INSERT INTO kriteria (nama_kriteria, bobot, sifat) VALUES (?, ?, ?)");
        $stmt->bind_param("sds", $nama, $bobot, $sifat);
        if ($stmt->execute()) {
            $stmt->close();
            header("Location: kriteria.php");
            exit;
        } else {
        }
        $stmt->close();
    }
}

if (isset($_POST['update'])) {
    $id = intval($_POST['id_kriteria']);
    $nama = trim($_POST['nama_kriteria']);
    $bobot = floatval($_POST['bobot']);
    $sifat = $_POST['sifat'];

    if ($nama !== '' && $bobot > 0 && in_array($sifat, ['benefit', 'cost'])) {
        $stmt = $conn->prepare("UPDATE kriteria SET nama_kriteria=?, bobot=?, sifat=? WHERE id_kriteria=?");
        $stmt->bind_param("sdsi", $nama, $bobot, $sifat, $id);
        if ($stmt->execute()) {
            $stmt->close();
            header("Location: kriteria.php");
            exit;
        } else {
        }
        $stmt->close();
    }
}

if (isset($_GET['hapus'])) {
    $id = intval($_GET['hapus']);
    $stmt = $conn->prepare("DELETE FROM kriteria WHERE id_kriteria=?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $stmt->close();
        header("Location: kriteria.php");
        exit;
    } else {
    }
    $stmt->close();
}

include 'view/header.php';

$result = $conn->query("SELECT * FROM kriteria");
if (!$result) {
    die("Query Kriteria gagal: " . $conn->error);
}

?>

<div class="content mt-5">
    <h2>Data Kriteria</h2>

    <form method="POST" class="mb-4">
        <div class="row g-2">
            <div class="col-md-5">
                <input type="text" name="nama_kriteria" class="form-control" placeholder="Nama Kriteria" required>
            </div>
            <div class="col-md-2">
                <input type="text" step="0.01" name="bobot" class="form-control" placeholder="Bobot" required>
            </div>
            <div class="col-md-3">
                <select name="sifat" class="form-select" required>
                    <option value="">- Pilih Sifat -</option>
                    <option value="benefit">Benefit</option>
                    <option value="cost">Cost</option>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100" type="submit" name="tambah">Tambah</button>
            </div>
        </div>
    </form>

    <table class="table table-bordered">
        <thead class="table-light">
            <tr>
                <th>No</th>
                <th>Nama Kriteria</th>
                <th>Bobot</th>
                <th>Sifat</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result->num_rows > 0) : $no = 1; ?>
                <?php while ($row = $result->fetch_assoc()) : ?>
                    <tr>
                        <td><?= $no++; ?></td>
                        <td><?= htmlspecialchars($row['nama_kriteria']); ?></td>
                        <td>
                            <?php  
                                echo sprintf('%g', $row['bobot'])
                            ?>
                        </td>
                        <td><?= ucfirst(htmlspecialchars($row['sifat'])); ?></td>
                        <td>
                            <button class="btn btn-warning btn-sm" onclick="editKriteria(<?= $row['id_kriteria']; ?>, '<?= addslashes($row['nama_kriteria']); ?>', <?= $row['bobot']; ?>, '<?= $row['sifat']; ?>')">Edit</button>
                            <a href="kriteria.php?hapus=<?= $row['id_kriteria']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin hapus?')">Hapus</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else : ?>
                <tr>
                    <td colspan="5" class="text-center">Belum ada data kriteria.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="modal fade" id="modalEdit" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Kriteria</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="id_kriteria" id="edit_id">
                <div class="mb-3">
                    <label for="edit_nama" class="form-label">Nama Kriteria</label>
                    <input type="text" name="nama_kriteria" id="edit_nama" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="edit_bobot" class="form-label">Bobot</label>
                    <input type="text" step="0.01" name="bobot" id="edit_bobot" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="edit_sifat" class="form-label">Sifat</label>
                    <select name="sifat" id="edit_sifat" class="form-select" required>
                        <option value="benefit">Benefit</option>
                        <option value="cost">Cost</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" name="update" class="btn btn-success">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script>
function editKriteria(id, nama, bobot, sifat) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_nama').value = nama;
    document.getElementById('edit_bobot').value = bobot;
    document.getElementById('edit_sifat').value = sifat;
    var modal = new bootstrap.Modal(document.getElementById('modalEdit'));
    modal.show();
}
</script>

<?php
    include 'view/footer.php';
?>