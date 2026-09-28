<?php
// koneksi.php
// Koneksi database Universitas Semantik - InfinityFree

$host = "sql206.infinityfree.com";
$username = "if0_43034143";
$password = "nSfNd9rYeNb6";
$database = "if0_43034143_universitassemantik";

// Membuat koneksi
$conn = new mysqli($host, $username, $password, $database);

// Memeriksa koneksi
if ($conn->connect_error) {
    die("Koneksi database gagal: " . $conn->connect_error);
}

// Mengatur charset agar mendukung UTF-8
$conn->set_charset("utf8mb4");
?>
