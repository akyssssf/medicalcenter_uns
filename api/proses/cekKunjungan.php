<?php
session_start();
include '../server/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: ../dashboard.php"); exit(); }
if (!isset($_SESSION['nik'])) { header("Location: ../login.php"); exit(); }

$nik   = $_SESSION['nik'];
$jalur = trim($_POST['jalur'] ?? '');

if (!in_array($jalur, ['token','manual','umum'], true)) {
    header("Location: ../dashboard.php"); exit();
}

// ── JALUR UMUM: langsung ke survei tanpa verifikasi kunjungan ──
if ($jalur === 'umum') {
    $_SESSION['jalur_survei'] = 'umum';
    $_SESSION['poli_aktif']   = null;
    unset($_SESSION['id_kunjungan_aktif']);
    header("Location: ../survei.php"); exit();
}

// ── JALUR TOKEN ──
if ($jalur === 'token') {
    $token = strtoupper(preg_replace('/[^A-Za-z0-9\-]/', '', trim($_POST['token'] ?? '')));
    if (empty($token)) {
        $_SESSION['flash'] = ['type'=>'error','title'=>'Token Kosong','message'=>'Masukkan kode token terlebih dahulu.'];
        header("Location: ../dashboard.php"); exit();
    }
    $stmt = mysqli_prepare($koneksi,
        "SELECT id, poli, status_survei FROM kunjungan WHERE token = ? AND nik_pasien = ? LIMIT 1"
    );
    mysqli_stmt_bind_param($stmt, "ss", $token, $nik);
}

// ── JALUR MANUAL ──
if ($jalur === 'manual') {
    $tanggal = trim($_POST['tanggal'] ?? '');
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal) || $tanggal > date('Y-m-d')) {
        $_SESSION['flash'] = ['type'=>'error','title'=>'Tanggal Tidak Valid','message'=>'Pilih tanggal yang valid dan tidak di masa depan.'];
        header("Location: ../dashboard.php"); exit();
    }
    $stmt = mysqli_prepare($koneksi,
        "SELECT id, poli, status_survei FROM kunjungan WHERE tgl_kunjungan = ? AND nik_pasien = ? LIMIT 1"
    );
    mysqli_stmt_bind_param($stmt, "ss", $tanggal, $nik);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if ($row['status_survei'] === 'Sudah') {
        $_SESSION['flash'] = [
            'type'=>'info','title'=>'Sudah Diisi!',
            'message'=>'Survei untuk kunjungan ini sudah pernah Anda isi. Terima kasih! 🙏'
        ];
        header("Location: ../dashboard.php"); exit();
    }

    $_SESSION['id_kunjungan_aktif'] = (int)$row['id'];
    $_SESSION['poli_aktif']         = $row['poli'];
    $_SESSION['jalur_survei']       = 'kunjungan';
    header("Location: ../survei.php"); exit();

} else {
    mysqli_stmt_close($stmt);
    $_SESSION['flash'] = [
        'type'=>'error','title'=>'Kunjungan Tidak Ditemukan',
        'message'=>'Data tidak ditemukan atau NIK tidak cocok. Periksa kembali token/tanggal Anda.'
    ];
    header("Location: ../dashboard.php"); exit();
}
?>