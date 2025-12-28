
-- File: spk_smart.sql
CREATE DATABASE IF NOT EXISTS spk_smart;
USE spk_smart;

CREATE TABLE alternatif (
    id_alternatif INT AUTO_INCREMENT PRIMARY KEY,
    nama_alternatif VARCHAR(255) NOT NULL,
    kategori ENUM('Food', 'Minuman', 'Nonfood', 'Fresh') NOT NULL,
    jenis_barang VARCHAR(255) NOT NULL
);

CREATE TABLE kriteria (
    id_kriteria INT AUTO_INCREMENT PRIMARY KEY,
    nama_kriteria VARCHAR(255) NOT NULL,
    bobot DECIMAL(5,2) NOT NULL,
    sifat ENUM('benefit', 'cost') NOT NULL
);

CREATE TABLE nilai (
    id_nilai INT AUTO_INCREMENT PRIMARY KEY,
    id_alternatif INT NOT NULL,
    id_kriteria INT NOT NULL,
    nilai DECIMAL(5,2) NOT NULL,
    FOREIGN KEY (id_alternatif) REFERENCES alternatif(id_alternatif) ON DELETE CASCADE,
    FOREIGN KEY (id_kriteria) REFERENCES kriteria(id_kriteria) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS users (
    id_user INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nama_lengkap VARCHAR(255) DEFAULT NULL,
    role ENUM('admin', 'user') DEFAULT 'user'
);

ALTER TABLE alternatif ADD COLUMN kategori ENUM('Food', 'Minuman', 'Nonfood', 'Fresh') NOT NULL DEFAULT 'Food';
ALTER TABLE alternatif ADD COLUMN jenis_barang VARCHAR(255) NOT NULL DEFAULT '';
