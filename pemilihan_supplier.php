<?php

declare(strict_types=1);
include 'config/cek_login.php';
include 'config/conn.php';
include 'view/header.php';

// Inisialisasi variabel untuk form
$selected_supplier = '';
$selected_kategori = '';
$selected_jenis_barang = '';
$hasil_perhitungan = [];
$show_table = true; // Default menampilkan table

// Function untuk menjalankan query
function getSupplierRanking($conn, $supplier = '', $kategori = '', $jenis_barang = '') {
    // Build WHERE conditions
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
    
    // Query untuk mendapatkan hasil perhitungan dengan filter
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

// Load data default (tanpa filter)
$hasil_perhitungan = getSupplierRanking($conn);

// Proses form jika ada submit
if (isset($_POST['submit'])) {
    $selected_supplier = $_POST['supplier'] ?? '';
    $selected_kategori = $_POST['kategori'] ?? '';
    $selected_jenis_barang = $_POST['jenis_barang'] ?? '';
    
    $hasil_perhitungan = getSupplierRanking($conn, $selected_supplier, $selected_kategori, $selected_jenis_barang);
}

// Reset form
if (isset($_POST['reset'])) {
    $selected_supplier = '';
    $selected_kategori = '';
    $selected_jenis_barang = '';
    // Load data default tanpa filter
    $hasil_perhitungan = getSupplierRanking($conn);
}

// Ambil data untuk dropdown
$suppliers = $conn->query("SELECT DISTINCT nama_alternatif FROM alternatif ORDER BY nama_alternatif");
$kategoris = $conn->query("SELECT DISTINCT kategori FROM alternatif ORDER BY kategori");

?>

<div class="content mt-5" id="content">
    <h2>Pemilihan Supplier Terbaik</h2>
    
    <div class="card mb-4">
        <div class="card-header">
            <h5>Filter Data Supplier</h5>
        </div>
        <div class="card-body">
            <form method="POST">
                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="supplier" class="form-label">Pilih Supplier</label>
                            <select class="form-select" name="supplier" id="supplier">
                                <option value="">-- Semua Supplier --</option>
                                <?php
                                if ($suppliers && $suppliers->num_rows > 0) {
                                    while ($supplier = $suppliers->fetch_assoc()) {
                                        $selected = ($selected_supplier == $supplier['nama_alternatif']) ? 'selected' : '';
                                        echo '<option value="' . htmlspecialchars($supplier['nama_alternatif']) . '" ' . $selected . '>' 
                                             . htmlspecialchars($supplier['nama_alternatif']) . '</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="kategori" class="form-label">Pilih Kategori Barang</label>
                            <select class="form-select" name="kategori" id="kategori">
                                <option value="">-- Semua Kategori --</option>
                                <?php
                                if ($kategoris && $kategoris->num_rows > 0) {
                                    while ($kategori = $kategoris->fetch_assoc()) {
                                        $selected = ($selected_kategori == $kategori['kategori']) ? 'selected' : '';
                                        echo '<option value="' . htmlspecialchars($kategori['kategori']) . '" ' . $selected . '>' 
                                             . htmlspecialchars($kategori['kategori']) . '</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="jenis_barang" class="form-label">Pilih Jenis Barang</label>
                            <select class="form-select" name="jenis_barang" id="jenis_barang">
                                <option value="">-- Semua Jenis Barang --</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex gap-2">
                    <button type="submit" name="submit" class="btn btn-primary">Submit</button>
                    <button type="submit" name="reset" class="btn btn-secondary">Reset Form</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5>Hasil Ranking Supplier Terbaik</h5>
            <small class="text-muted">Menampilkan maksimal 6 supplier terbaik dengan skor akhir > 30,000 diurutkan dari yang terbaik</small>
        </div>
            <div class="card-body">
                <?php if (empty($hasil_perhitungan)) : ?>
                    <div class="alert alert-warning" role="alert">
                        Tidak ada data supplier yang memenuhi kriteria filter yang dipilih atau skor akhir di atas 30,000.
                    </div>
                <?php else : ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Peringkat</th>
                                    <th>Alternatif / Nama Supplier</th>
                                    <th>Skor Akhir</th>
                                    <th>Status Keputusan</th>
                                    <th>Kategori</th>
                                    <th>Jenis Barang</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($hasil_perhitungan as $data) : ?>
                                    <tr>
                                        <td class="text-center">
                                            <span class="badge bg-primary"><?= htmlspecialchars($data['peringkat']); ?></span>
                                        </td>
                                        <td><?= htmlspecialchars($data['alternatif']); ?></td>
                                        <td class="text-end">
                                            <strong><?= number_format((float)$data['skor_akhir'], 3); ?></strong>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-success">
                                                <i class="bi bi-check-circle"></i> Supplier Terbaik
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?= htmlspecialchars($data['kategori']); ?></span>
                                        </td>
                                        <td><?= htmlspecialchars($data['jenis_barang']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-3">
                        <small class="text-muted">
                            Total <?= count($hasil_perhitungan); ?> supplier ditemukan sesuai kriteria filter.
                        </small>
                    </div>
                <?php endif; ?>
            </div>
        </div>
</div>

<script>
// Script untuk mengisi dropdown jenis barang berdasarkan kategori yang dipilih
document.getElementById('kategori').addEventListener('change', function() {
    const kategori = this.value;
    const jenisBarangSelect = document.getElementById('jenis_barang');
    
    // Clear existing options
    jenisBarangSelect.innerHTML = '<option value="">-- Semua Jenis Barang --</option>';
    
    if (kategori) {
        // Fetch jenis barang berdasarkan kategori
        fetch('get_jenis_barang.php?kategori=' + encodeURIComponent(kategori))
            .then(response => response.json())
            .then(data => {
                data.forEach(item => {
                    const option = document.createElement('option');
                    option.value = item.jenis_barang;
                    option.textContent = item.jenis_barang;
                    <?php if (!empty($selected_jenis_barang)) : ?>
                    if (item.jenis_barang === '<?= htmlspecialchars($selected_jenis_barang); ?>') {
                        option.selected = true;
                    }
                    <?php endif; ?>
                    jenisBarangSelect.appendChild(option);
                });
            })
            .catch(error => {
                console.error('Error fetching jenis barang:', error);
            });
    }
});

// Load jenis barang on page load jika ada kategori yang dipilih
document.addEventListener('DOMContentLoaded', function() {
    const kategoriSelect = document.getElementById('kategori');
    if (kategoriSelect.value) {
        kategoriSelect.dispatchEvent(new Event('change'));
    }
});
</script>

<?php
include 'view/footer.php';
?>