<?php

    declare(strict_types=1);

    $host = '127.0.0.1'; 
    $user = 'root';
    $pass = ''; 
    $dbname = 'spk_smart';
    $port = 3306;

    $conn = new mysqli($host, $user, $pass, $dbname, $port);

    if ($conn->connect_errno) {
        http_response_code(500);
        die("Koneksi database gagal: " . $conn->connect_error);
    }

    $conn->set_charset("utf8mb4");
?>
