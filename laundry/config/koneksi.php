<?php
/* Sembunyikan pesan Warning/Notice/Deprecated dari tampilan pengunjung; tetap dicatat ke file log server. */
error_reporting(E_ALL);
ini_set('display_errors','0');
ini_set('display_startup_errors','0');
ini_set('log_errors','1');

mysqli_report(MYSQLI_REPORT_OFF);
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'db_laundry';
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    http_response_code(500);
    exit('Koneksi database gagal. Pastikan MySQL aktif dan database db_laundry tersedia di phpMyAdmin. Detail: ' . htmlspecialchars($conn->connect_error, ENT_QUOTES, 'UTF-8'));
}
$conn->set_charset('utf8mb4');
?>
