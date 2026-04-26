<?php
session_start();
include '../server/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: ../login.php"); exit(); }

// ── CSRF ──
if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
    $_SESSION['flash'] = ['type'=>'error','title'=>'Akses Ditolak','message'=>'Token keamanan tidak valid.'];
    header("Location: ../login.php"); exit();
}
unset($_SESSION['csrf_token']);

$nik      = preg_replace('/[^0-9]/', '', trim($_POST['nik']      ?? ''));
$nama     = trim($_POST['nama']     ?? '');
$no_hp    = preg_replace('/[^0-9]/', '', trim($_POST['no_hp']    ?? ''));
$password = $_POST['password']      ?? '';
$kategori = trim($_POST['kategori'] ?? '');

// ── Validasi ──
$kat_valid = ['Mahasiswa','Dosen','Karyawan','Umum'];

if (!preg_match('/^\d{16}$/', $nik)) {
    $_SESSION['flash'] = ['type'=>'error','title'=>'NIK Tidak Valid','message'=>'NIK harus tepat 16 digit angka.'];
    header("Location: ../login.php"); exit();
}
if (strlen($nama) < 3 || strlen($nama) > 100) {
    $_SESSION['flash'] = ['type'=>'error','title'=>'Nama Tidak Valid','message'=>'Nama lengkap harus 3–100 karakter.'];
    header("Location: ../login.php"); exit();
}
if (!preg_match('/^[0-9]{9,15}$/', $no_hp)) {
    $_SESSION['flash'] = ['type'=>'error','title'=>'No. HP Tidak Valid','message'=>'Nomor WhatsApp harus 9–15 digit angka.'];
    header("Location: ../login.php"); exit();
}
if (strlen($password) < 6 || !preg_match('/[0-9]/', $password) || !preg_match('/[A-Z]/', $password)) {
    $_SESSION['flash'] = ['type'=>'error','title'=>'Password Lemah','message'=>'Password min 6 karakter, harus ada angka dan huruf kapital.'];
    header("Location: ../login.php"); exit();
}
if (!in_array($kategori, $kat_valid, true)) {
    $_SESSION['flash'] = ['type'=>'error','title'=>'Kategori Tidak Valid','message'=>'Pilih kategori pengunjung Anda.'];
    header("Location: ../login.php"); exit();
}

// ── Cek duplikasi ──
$cek = mysqli_prepare($koneksi, "SELECT id FROM users WHERE nik = ? OR no_hp = ?");
mysqli_stmt_bind_param($cek, "ss", $nik, $no_hp);
mysqli_stmt_execute($cek);
mysqli_stmt_store_result($cek);
if (mysqli_stmt_num_rows($cek) > 0) {
    mysqli_stmt_close($cek);
    $_SESSION['flash'] = ['type'=>'warning','title'=>'Sudah Terdaftar','message'=>'NIK atau Nomor HP sudah digunakan akun lain.'];
    header("Location: ../login.php"); exit();
}
mysqli_stmt_close($cek);

// ── Simpan dengan kategori ──
$hash = password_hash($password, PASSWORD_BCRYPT, ['cost'=>12]);
$ins  = mysqli_prepare($koneksi,
    "INSERT INTO users (nik, nama, kategori, no_hp, password, role) VALUES (?, ?, ?, ?, ?, 'user')"
);
mysqli_stmt_bind_param($ins, "sssss", $nik, $nama, $kategori, $no_hp, $hash);

if (mysqli_stmt_execute($ins)) {
    mysqli_stmt_close($ins);
    $_SESSION['flash'] = [
        'type'=>'success','title'=>'Pendaftaran Berhasil!',
        'message'=>"Selamat datang, $nama! Silakan login untuk mengisi survei."
    ];
    header("Location: ../login.php"); exit();
} else {
    mysqli_stmt_close($ins);
    $_SESSION['flash'] = ['type'=>'error','title'=>'Gagal Daftar','message'=>'Terjadi kesalahan sistem. Coba lagi.'];
    header("Location: ../login.php"); exit();
}
?>