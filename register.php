<?php
// File: register.php

declare(strict_types=1);
session_start();
include 'config/conn.php';

if (isset($_POST['register'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $nama_lengkap = trim($_POST['nama_lengkap']);

    if ($username !== '' && $password !== '') {
        $stmt = $conn->prepare("SELECT id_user FROM users WHERE username=?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = "Username sudah terdaftar!";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("INSERT INTO users (username, password, nama_lengkap) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $username, $hash, $nama_lengkap);
            $stmt->execute();

            $_SESSION['sukses'] = "Registrasi berhasil! Silakan login.";
            header("Location: login.php");
            exit;
        }

        $stmt->close();
    } else {
        $error = "Username dan Password wajib diisi.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - SPK pemilihan supplier terbaik dengan metode SMART PT. Mitra Belanja Anda</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    
</head>
<body class="bg-light">

<div class="container-fluid">
    <div class="row vh-100">
        <div class="col-md-6 d-none d-md-flex bg-img align-items-center justify-content-center">
            <h1>Daftar Akun Sistem Penunjang Keputusan Pemilihan Supplier Terbaik Metode SMART untuk Grandlucky MOI</h1>
        </div>

        <div class="col-md-6 d-flex align-items-center justify-content-center">
            <div class="card w-75 p-4">
                <h4 class="mb-4 text-center">Register Akun</h4>

                <?php if (isset($error)) : ?>
                    <div class="alert alert-danger"><?= $error; ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Nama Lengkap</label>
                        <input type="text" name="nama_lengkap" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>

                    <div class="d-grid">
                        <button type="submit" name="register" class="btn btn-primary">Register</button>
                    </div>
                </form>

                <div class="text-center mt-3">
                    Sudah punya akun? <a href="login.php">Login di sini</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
