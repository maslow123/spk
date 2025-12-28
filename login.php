<?php

declare(strict_types=1);
session_start();
include 'config/conn.php';

if (isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}

if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if ($username !== '' && $password !== '') {
        $stmt = $conn->prepare("SELECT id_user, username, password, nama_lengkap, role FROM users WHERE username=?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            if (password_verify($password, $user['password'])) {
                // Login sukses
                $_SESSION['user'] = [
                    'id' => $user['id_user'],
                    'username' => $user['username'],
                    'nama_lengkap' => $user['nama_lengkap'],
                    'role' => $user['role']
                ];
                header("Location: index.php");
                exit;
            } else {
                $error = "Password salah!";
            }
        } else {
            $error = "Username tidak ditemukan!";
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
    <title>Login - Admin SPK SMART PT Mitra Belanja Anda</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    
</head>
<body class="bg-light">

<div class="container-fluid">
    <div class="row vh-100">
        <div class="col-md-6 d-none d-md-flex bg-img align-items-center justify-content-center">
            <h1>Sistem Penunjang Keputusan Pemilihan Supplier Terbaik Metode SMART untuk Grandlucky MOI</h1>
        </div>
        <div class="col-md-6 d-flex align-items-center justify-content-center">
            <div class="card w-75 p-4">
                <h4 class="mb-4 text-center">Login Admin</h4>

                <?php if (isset($_SESSION['sukses'])) : ?>
                    <div class="alert alert-success"><?= $_SESSION['sukses']; unset($_SESSION['sukses']); ?></div>
                <?php endif; ?>

                <?php if (isset($error)) : ?>
                    <div class="alert alert-danger"><?= $error; ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-control" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>

                    <div class="d-grid">
                        <button type="submit" name="login" class="btn btn-primary">Login</button>
                    </div>
                </form>

                <div class="text-center mt-3">
                    Belum punya akun? <a href="register.php">Register di sini</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

