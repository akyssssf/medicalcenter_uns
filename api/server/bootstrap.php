<?php
/**
 * bootstrap.php
 * Include ini PERTAMA di semua file — SEBELUM apapun.
 * Menangani: koneksi DB, session handler berbasis DB, dan session_start().
 */

// Cegah double-include
if (defined('_BOOTSTRAP_LOADED')) return;
define('_BOOTSTRAP_LOADED', true);

// ── Koneksi Database ──
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

// ── DB Session Handler ──
class DBSessionHandler implements SessionHandlerInterface {
    private $db;

    public function __construct($db) { $this->db = $db; }
    public function open($path, $name): bool { return true; }
    public function close(): bool { return true; }

    public function read($id): string {
        $stmt = mysqli_prepare($this->db,
            "SELECT data FROM sessions WHERE id = ? AND expires > NOW() LIMIT 1");
        if (!$stmt) return '';
        mysqli_stmt_bind_param($stmt, "s", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        return $row ? $row['data'] : '';
    }

    public function write($id, $data): bool {
        $stmt = mysqli_prepare($this->db,
            "REPLACE INTO sessions (id, data, expires) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 2 HOUR))");
        if (!$stmt) return false;
        mysqli_stmt_bind_param($stmt, "ss", $id, $data);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $ok;
    }

    public function destroy($id): bool {
        $stmt = mysqli_prepare($this->db, "DELETE FROM sessions WHERE id = ?");
        if (!$stmt) return false;
        mysqli_stmt_bind_param($stmt, "s", $id);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $ok;
    }

    public function gc($max_lifetime): int|false {
        $stmt = mysqli_prepare($this->db, "DELETE FROM sessions WHERE expires < NOW()");
        if (!$stmt) return false;
        mysqli_stmt_execute($stmt);
        $affected = mysqli_stmt_affected_rows($stmt);
        mysqli_stmt_close($stmt);
        return $affected;
    }
}

// Daftarkan handler SEBELUM session_start()
$handler = new DBSessionHandler($koneksi);
session_set_save_handler($handler, true);

// Konfigurasi cookie session
session_set_cookie_params([
    'lifetime' => 7200,
    'path'     => '/',
    'secure'   => true,
    'httponly' => true,
    'samesite' => 'Lax',
]);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
