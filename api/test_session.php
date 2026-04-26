<?php
require_once __DIR__ . '/server/bootstrap.php';

// Halaman test sederhana (bisa dihapus di production)
echo json_encode([
    'session_id' => session_id(),
    'session_status' => session_status(),
    'session_data' => $_SESSION,
    'db_connected' => isset($koneksi) ? true : false,
]);
