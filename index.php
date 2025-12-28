<?php
    declare(strict_types=1);
    include 'config/cek_login.php';
    include 'config/conn.php';
    include 'view/header.php';
?>

<div class="container-fluid vh-100 bg-img d-flex align-items-center justify-content-center">
    <div class="card w-75 p-4 bg-transparent text-white center ml-3">
        <h2 class="text-white" style="margin-left:200px;">Dashboard SPK pemilihan supplier terbaik dengan metode SMART PT. Mitra Belanja Anda</h2>
        <p class="text-white" style="margin-left:200px;">Selamat datang, <?= htmlspecialchars($_SESSION['user']['nama_lengkap']); ?>!</p>
    </div>
</div>

<?php include 'view/footer.php'; ?>
