<?php
/**
 * koneksi.php — Database Connection
 * Pastikan file ini tidak bisa diakses langsung dari browser.
 * Tambahkan .htaccess deny jika di-host di Apache.
 */

// ── Amankan Cookie Session ──
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
// Aktifkan secure cookie jika di HTTPS (uncomment di production):
// ini_set('session.cookie_secure', 1);

// ── Konfigurasi Database ──
$host = "localhost";
$user = "root";
$pass = "";       // Ganti dengan password kuat di production
$db   = "uns_medicalcenterDB";

// ── Koneksi ──
$koneksi = mysqli_connect($host, $user, $pass, $db);

if (!$koneksi) {
    // JANGAN tampilkan detail error di production — hanya log!
    error_log("DB Connection Failed: " . mysqli_connect_error());
    // Tampilkan pesan generik ke user
    http_response_code(503);
    die("Layanan sementara tidak tersedia. Silakan coba beberapa saat lagi.");
}

// ── Set charset UTF-8 (mencegah charset injection) ──
mysqli_set_charset($koneksi, 'utf8mb4');
?>
