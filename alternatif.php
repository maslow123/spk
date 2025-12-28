<?php

declare(strict_types=1);
include 'config/cek_login.php';
include 'config/conn.php';

if (isset($_POST['tambah'])) {
    $nama = trim($_POST['nama_alternatif']);
    $kategori = $_POST['kategori'];
    $jenis_barang = $_POST['jenis_barang'];
    
    if ($nama !== '' && $kategori !== '' && $jenis_barang !== '') {
        $stmt = $conn->prepare("INSERT INTO alternatif (nama_alternatif, kategori, jenis_barang) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $nama, $kategori, $jenis_barang);
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
    $kategori = $_POST['kategori'];
    $jenis_barang = $_POST['jenis_barang'];
    
    if ($nama !== '' && $kategori !== '' && $jenis_barang !== '') {
        $stmt = $conn->prepare("UPDATE alternatif SET nama_alternatif=?, kategori=?, jenis_barang=? WHERE id_alternatif=?");
        $stmt->bind_param("sssi", $nama, $kategori, $jenis_barang, $id);
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
        <div class="row mb-3">
            <div class="col-md-4">
                <input type="text" name="nama_alternatif" class="form-control" placeholder="Nama Alternatif" required>
            </div>
            <div class="col-md-3">
                <select name="kategori" id="kategori" class="form-control" required onchange="updateJenisBarang()">
                    <option value="">Pilih Kategori</option>
                    <option value="Food">Food</option>
                    <option value="Minuman">Minuman</option>
                    <option value="Nonfood">Nonfood</option>
                    <option value="Fresh">Fresh</option>
                </select>
            </div>
            <div class="col-md-3">
                <select name="jenis_barang" id="jenis_barang" class="form-control" required>
                    <option value="">Pilih Jenis Barang</option>
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
                <th>Nama Alternatif</th>
                <th>Kategori</th>
                <th>Jenis Barang</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result->num_rows > 0): $no = 1; ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $no++; ?></td>
                        <td><?= htmlspecialchars($row['nama_alternatif']); ?></td>
                        <td><?= htmlspecialchars($row['kategori']); ?></td>
                        <td><?= htmlspecialchars($row['jenis_barang']); ?></td>
                        <td>
                            <button class="btn btn-warning btn-sm" onclick="editAlternatif(<?= $row['id_alternatif']; ?>, '<?= addslashes($row['nama_alternatif']); ?>', '<?= addslashes($row['kategori']); ?>', '<?= addslashes($row['jenis_barang']); ?>')">Edit</button>
                            <a href="alternatif.php?hapus=<?= $row['id_alternatif']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin hapus?')">Hapus</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" class="text-center">Belum ada data alternatif.</td>
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
                <div class="mb-3">
                    <label for="edit_kategori" class="form-label">Kategori</label>
                    <select name="kategori" id="edit_kategori" class="form-control" required onchange="updateJenisBarangEdit()">
                        <option value="">Pilih Kategori</option>
                        <option value="Food">Food</option>
                        <option value="Minuman">Minuman</option>
                        <option value="Nonfood">Nonfood</option>
                        <option value="Fresh">Fresh</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="edit_jenis_barang" class="form-label">Jenis Barang</label>
                    <select name="jenis_barang" id="edit_jenis_barang" class="form-control" required>
                        <option value="">Pilih Jenis Barang</option>
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
    // Data jenis barang berdasarkan kategori
    const jenisBarangData = {
        'Food': [
            'bahan pangan',
            'bahan olahan', 
            'bahan organik',
            'kopi-kopian',
            'saus, dan kecap',
            'sabun'
        ],
        'Minuman': [
            'minuman isotonik',
            'minuman herbal',
            'minuman air mineral',
            'minuman alkohol'
        ],
        'Nonfood': [
            'sabun',
            'kosmetik',
            'perlengkapan rumah tangga',
            'alat kebersihan',
            'obat-obatan'
        ],
        'Fresh': [
            'buah-buahan',
            'sayur-sayuran'
        ]
    };

    function updateJenisBarang() {
        const kategori = document.getElementById('kategori').value;
        const jenisBarangSelect = document.getElementById('jenis_barang');
        
        // Clear options
        jenisBarangSelect.innerHTML = '<option value="">Pilih Jenis Barang</option>';
        
        if (kategori && jenisBarangData[kategori]) {
            jenisBarangData[kategori].forEach(function(jenis) {
                const option = document.createElement('option');
                option.value = jenis;
                option.textContent = jenis;
                jenisBarangSelect.appendChild(option);
            });
        }
    }

    function updateJenisBarangEdit() {
        const kategori = document.getElementById('edit_kategori').value;
        const jenisBarangSelect = document.getElementById('edit_jenis_barang');
        
        // Clear options
        jenisBarangSelect.innerHTML = '<option value="">Pilih Jenis Barang</option>';
        
        if (kategori && jenisBarangData[kategori]) {
            jenisBarangData[kategori].forEach(function(jenis) {
                const option = document.createElement('option');
                option.value = jenis;
                option.textContent = jenis;
                jenisBarangSelect.appendChild(option);
            });
        }
    }

    function editAlternatif(id, nama, kategori, jenisBarang) {
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_nama').value = nama;
        document.getElementById('edit_kategori').value = kategori;
        
        // Update jenis barang options berdasarkan kategori
        updateJenisBarangEdit();
        
        // Set jenis barang value setelah options di-update
        setTimeout(function() {
            document.getElementById('edit_jenis_barang').value = jenisBarang;
        }, 50);
        
        var modal = new bootstrap.Modal(document.getElementById('modalEdit'));
        modal.show();
    }
</script>

<?php
include 'view/footer.php';
?>