<?php

declare(strict_types=1);
include 'config/cek_login.php';
include 'config/conn.php';

if (isset($_POST['tambah'])) {
    $nama = trim($_POST['nama_alternatif']);
    if ($nama !== '') {
        $stmt = $conn->prepare("INSERT INTO alternatif (nama_alternatif) VALUES (?)");
        $stmt->bind_param("s", $nama);
        if ($stmt->execute()) {
            $stmt->close();
            header("Location: alternatif.php");
            exit;
        }
        $stmt->close();
    }
}

if (isset($_POST['update'])) {
    $id = intval($_POST['id_alternatif']);
    $nama = trim($_POST['nama_alternatif']);
    if ($nama !== '') {
        $stmt = $conn->prepare("UPDATE alternatif SET nama_alternatif=? WHERE id_alternatif=?");
        $stmt->bind_param("si", $nama, $id);
        if ($stmt->execute()) {
            $stmt->close();
            header("Location: alternatif.php");
            exit;
        }
        $stmt->close();
    }
}

if (isset($_GET['hapus'])) {
    $id = intval($_GET['hapus']);
    $stmt = $conn->prepare("DELETE FROM alternatif WHERE id_alternatif=?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $stmt->close();
        header("Location: alternatif.php");
        exit;
    }
    $stmt->close();
}

include 'view/header.php';

$result = $conn->query("SELECT * FROM alternatif");

?>

<div class="content mt-5">
    <h2>Data Alternatif</h2>

    <form method="POST" class="mb-4">
        <div class="input-group">
            <input type="text" name="nama_alternatif" class="form-control" placeholder="Nama Alternatif" required>
            <button class="btn btn-primary" type="submit" name="tambah">Tambah</button>
        </div>
    </form>

    <table class="table table-bordered">
        <thead class="table-light">
            <tr>
                <th>No</th>
                <th>Nama Alternatif</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result->num_rows > 0): $no = 1; ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $no++; ?></td>
                        <td><?= htmlspecialchars($row['nama_alternatif']); ?></td>
                        <td>
                            <button class="btn btn-warning btn-sm" onclick="editAlternatif(<?= $row['id_alternatif']; ?>, '<?= addslashes($row['nama_alternatif']); ?>')">Edit</button>
                            <a href="alternatif.php?hapus=<?= $row['id_alternatif']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin hapus?')">Hapus</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="3" class="text-center">Belum ada data alternatif.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="modal fade" id="modalEdit" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Alternatif</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="id_alternatif" id="edit_id">
                <div class="mb-3">
                    <label for="edit_nama" class="form-label">Nama Alternatif</label>
                    <input type="text" name="nama_alternatif" id="edit_nama" class="form-control" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" name="update" class="btn btn-success">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script>
    function editAlternatif(id, nama) {
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_nama').value = nama;
        var modal = new bootstrap.Modal(document.getElementById('modalEdit'));
        modal.show();
    }
</script>

<?php
include 'view/footer.php';
?>