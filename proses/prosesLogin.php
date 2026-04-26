<?php
session_start();
include '../server/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../login.php"); exit();
}

// ── CSRF ──
if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
    $_SESSION['flash'] = ['type'=>'error','title'=>'Akses Ditolak','message'=>'Token keamanan tidak valid.'];
    header("Location: ../login.php"); exit();
}
unset($_SESSION['csrf_token']);

// ── Rate limiting ──
$now = time();
if (isset($_SESSION['login_fail_time']) && ($now - $_SESSION['login_fail_time']) > 900) {
    $_SESSION['login_attempts'] = 0;
}
if (($_SESSION['login_attempts'] ?? 0) >= 5) {
    $_SESSION['flash'] = ['type'=>'error','title'=>'Terlalu Banyak Percobaan','message'=>'Akun dikunci 15 menit karena terlalu banyak percobaan login.'];
    header("Location: ../login.php"); exit();
}

$identitas = trim($_POST['identitas'] ?? '');
$password  = $_POST['password'] ?? '';

if (empty($identitas) || empty($password)) {
    $_SESSION['flash'] = ['type'=>'warning','title'=>'Input Tidak Lengkap','message'=>'NIK/No. HP dan password wajib diisi.'];
    header("Location: ../login.php"); exit();
}

// ── Query — ambil juga kolom kategori ──
$stmt = mysqli_prepare($koneksi,
    "SELECT id, nik, nama, password, role,
            COALESCE(kategori, 'Umum') AS kategori
     FROM users WHERE nik = ? OR no_hp = ? LIMIT 1"
);
mysqli_stmt_bind_param($stmt, "ss", $identitas, $identitas);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 1) {
    $user = mysqli_fetch_assoc($result);

    if (password_verify($password, $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['login_attempts'] = 0;
        unset($_SESSION['login_fail_time']);

        // Simpan semua info user ke session — termasuk kategori
        $_SESSION['nik']      = $user['nik'];
        $_SESSION['nama']     = $user['nama'];
        $_SESSION['role']     = $user['role'] ?? 'user';
        $_SESSION['kategori'] = $user['kategori']; // ← BARU

        mysqli_stmt_close($stmt);

        if ($_SESSION['role'] === 'admin') {
            header("Location: ../admin/dashboard.php");
        } else {
            header("Location: ../dashboard.php");
        }
        exit();
    }
}

mysqli_stmt_close($stmt);
$_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
$_SESSION['login_fail_time'] = $now;
$sisa = 5 - $_SESSION['login_attempts'];
$_SESSION['flash'] = [
    'type'=>'error','title'=>'Login Gagal',
    'message'=>"NIK/No. HP atau password salah. Sisa percobaan: $sisa kali."
];
header("Location: ../login.php"); exit();
?>