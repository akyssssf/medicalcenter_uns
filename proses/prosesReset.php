<?php
session_start();
include '../server/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../login.php"); exit();
}

// ── CSRF Validation ──
if (
    empty($_POST['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])
) {
    $_SESSION['flash'] = ['type'=>'error','title'=>'Akses Ditolak','message'=>'Token keamanan tidak valid.'];
    header("Location: ../login.php"); exit();
}
// Jangan hapus CSRF token di sini — modal masih mungkin di-reopen

$nik           = trim($_POST['nik']           ?? '');
$no_hp         = trim($_POST['no_hp']         ?? '');
$password_baru = $_POST['password_baru']      ?? '';

// ── Validasi input ──
if (!preg_match('/^\d{16}$/', $nik)) {
    $_SESSION['flash'] = ['type'=>'error','title'=>'NIK Tidak Valid','message'=>'NIK harus tepat 16 digit.'];
    header("Location: ../login.php"); exit();
}

if (
    strlen($password_baru) < 6 ||
    !preg_match('/[0-9]/', $password_baru) ||
    !preg_match('/[A-Z]/', $password_baru)
) {
    $_SESSION['flash'] = ['type'=>'error','title'=>'Password Lemah','message'=>'Password baru minimal 6 karakter, mengandung angka dan huruf kapital.'];
    header("Location: ../login.php"); exit();
}

// ── Cek kecocokan NIK + No HP ──
$stmt_cek = mysqli_prepare($koneksi, "SELECT id FROM users WHERE nik = ? AND no_hp = ?");
mysqli_stmt_bind_param($stmt_cek, "ss", $nik, $no_hp);
mysqli_stmt_execute($stmt_cek);
mysqli_stmt_store_result($stmt_cek);

if (mysqli_stmt_num_rows($stmt_cek) !== 1) {
    mysqli_stmt_close($stmt_cek);
    $_SESSION['flash'] = ['type'=>'error','title'=>'Verifikasi Gagal','message'=>'Kombinasi NIK dan Nomor WhatsApp tidak ditemukan di sistem kami.'];
    header("Location: ../login.php"); exit();
}
mysqli_stmt_close($stmt_cek);

// ── Update Password ──
$pass_hash   = password_hash($password_baru, PASSWORD_BCRYPT, ['cost' => 12]);
$stmt_update = mysqli_prepare($koneksi, "UPDATE users SET password = ? WHERE nik = ?");
mysqli_stmt_bind_param($stmt_update, "ss", $pass_hash, $nik);

if (mysqli_stmt_execute($stmt_update)) {
    mysqli_stmt_close($stmt_update);
    $_SESSION['flash'] = ['type'=>'success','title'=>'Password Berhasil Direset!','message'=>'Silakan login menggunakan password baru Anda.'];
    header("Location: ../login.php"); exit();
} else {
    $_SESSION['flash'] = ['type'=>'error','title'=>'Gagal','message'=>'Terjadi kesalahan sistem. Silakan coba lagi.'];
    header("Location: ../login.php"); exit();
}
?>
