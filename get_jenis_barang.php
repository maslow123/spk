<?php

declare(strict_types=1);
include 'config/conn.php';

// Set header untuk JSON response
header('Content-Type: application/json');

// Ambil parameter kategori dari GET
$kategori = $_GET['kategori'] ?? '';

$response = [];

if (!empty($kategori)) {
    $stmt = $conn->prepare("SELECT DISTINCT jenis_barang FROM alternatif WHERE kategori = ? ORDER BY jenis_barang");
    $stmt->bind_param("s", $kategori);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $response[] = [
            'jenis_barang' => $row['jenis_barang']
        ];
    }
    
    $stmt->close();
}

echo json_encode($response);
$conn->close();

?>