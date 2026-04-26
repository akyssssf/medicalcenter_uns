<?php
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);

$host = getenv('DB_HOST') ?: "localhost";
$user = getenv('DB_USER') ?: "root";
$pass = getenv('DB_PASS') ?: "";
$db   = getenv('DB_NAME') ?: "uns_medicalcenterDB";
$port = (int)(getenv('DB_PORT') ?: 3306);

$koneksi = mysqli_init();
mysqli_ssl_set($koneksi, NULL, NULL, NULL, NULL, NULL);
mysqli_real_connect($koneksi, $host, $user, $pass, $db, $port, NULL, MYSQLI_CLIENT_SSL);

if (!$koneksi || mysqli_connect_errno()) {
    error_log("DB Connection Failed: " . mysqli_connect_error());
    http_response_code(503);
    die("Layanan sementara tidak tersedia. Silakan coba beberapa saat lagi.");
}

mysqli_set_charset($koneksi, 'utf8mb4');
?>