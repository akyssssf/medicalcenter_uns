<?php
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);

// ── Konfigurasi Database (pakai env vars untuk production) ──
$host = getenv('DB_HOST') ?: "localhost";
$user = getenv('DB_USER') ?: "root";
$pass = getenv('DB_PASS') ?: "";
$db   = getenv('DB_NAME') ?: "uns_medicalcenterDB";

$koneksi = mysqli_connect($host, $user, $pass, $db);

if (!$koneksi) {
    error_log("DB Connection Failed: " . mysqli_connect_error());
    http_response_code(503);
    die("Layanan sementara tidak tersedia. Silakan coba beberapa saat lagi.");
}

mysqli_set_charset($koneksi, 'utf8mb4');
?>