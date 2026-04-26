<?php
session_start();
include '../server/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: ../survei.php"); exit(); }
if (!isset($_SESSION['nik'])) { header("Location: ../login.php"); exit(); }

// ── CSRF ──
if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
    $_SESSION['flash'] = ['type'=>'error','title'=>'Akses Ditolak','message'=>'Token keamanan tidak valid.'];
    header("Location: ../survei.php"); exit();
}
unset($_SESSION['csrf_token']);

$jalur = $_SESSION['jalur_survei'] ?? '';
if (!in_array($jalur, ['kunjungan','umum'], true)) {
    header("Location: ../dashboard.php"); exit();
}

// ── Ambil & Sanitasi Input ──
$nik       = $_SESSION['nik'];
$kategori  = trim($_POST['kategori'] ?? '');
$frekuensi = trim($_POST['frekuensi'] ?? '');
$nps_score = (int)($_POST['nps_score'] ?? -1);
$saran     = mb_substr(htmlspecialchars(strip_tags(trim($_POST['saran'] ?? '')), ENT_QUOTES, 'UTF-8'), 0, 500);
$q1 = (int)($_POST['q1'] ?? 0);
$q2 = (int)($_POST['q2'] ?? 0);
$q3 = (int)($_POST['q3'] ?? 0);
$q4 = (int)($_POST['q4'] ?? 0);
$q5 = isset($_POST['q5']) ? (int)$_POST['q5'] : null;

// Tentukan poli
if ($jalur === 'kunjungan') {
    $poli = $_SESSION['poli_aktif'] ?? 'Umum';
} else {
    $poli = trim($_POST['poli'] ?? 'Umum');
}

// Field tambahan (hanya jalur kunjungan)
$akan_kembali = ($jalur === 'kunjungan') ? trim($_POST['akan_kembali'] ?? '') : null;
$waktu_tunggu = ($jalur === 'kunjungan') ? trim($_POST['waktu_tunggu'] ?? '') : null;

// ── Validasi Whitelist ──
$kat_valid  = ['Mahasiswa','Dosen','Karyawan','Umum'];
$poli_valid = ['Umum','Gigi','KIA'];
$frq_valid  = ['Pertama kali','2-3 kali','4-6 kali','Lebih dari 6 kali'];

if (!in_array($kategori, $kat_valid, true)) {
    $_SESSION['flash'] = ['type'=>'error','title'=>'Data Tidak Valid','message'=>'Kategori pengunjung tidak valid.'];
    header("Location: ../survei.php"); exit();
}
if (!in_array($poli, $poli_valid, true)) {
    $_SESSION['flash'] = ['type'=>'error','title'=>'Data Tidak Valid','message'=>'Poli tidak valid.'];
    header("Location: ../survei.php"); exit();
}
if (!in_array($frekuensi, $frq_valid, true)) {
    $_SESSION['flash'] = ['type'=>'error','title'=>'Data Tidak Valid','message'=>'Frekuensi tidak valid.'];
    header("Location: ../survei.php"); exit();
}
foreach ([$q1,$q2,$q3,$q4] as $q) {
    if ($q < 1 || $q > 5) {
        $_SESSION['flash'] = ['type'=>'error','title'=>'Data Tidak Valid','message'=>'Nilai rating harus antara 1–5.'];
        header("Location: ../survei.php"); exit();
    }
}
if ($jalur === 'kunjungan' && ($q5 === null || $q5 < 1 || $q5 > 5)) {
    $_SESSION['flash'] = ['type'=>'error','title'=>'Data Tidak Valid','message'=>'Jawab pertanyaan spesifik poli.'];
    header("Location: ../survei.php"); exit();
}
if ($nps_score < 0 || $nps_score > 10) {
    $_SESSION['flash'] = ['type'=>'error','title'=>'Data Tidak Valid','message'=>'Skor NPS harus antara 0–10.'];
    header("Location: ../survei.php"); exit();
}

// ── Cek kolom exists (graceful fallback jika migrasi belum dijalankan) ──
// Insert ke surveys
$id_kunjungan = ($jalur === 'kunjungan') ? (int)($_SESSION['id_kunjungan_aktif'] ?? 0) : null;

$sql = "INSERT INTO surveys 
  (email, poli, jalur, kategori, q1, q2, q3, q4, q5, nps_score, saran, id_kunjungan) 
  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = mysqli_prepare($koneksi, $sql);

if (!$stmt) {
    // Fallback: tabel mungkin belum di-migrate, coba insert minimal
    $sql_fallback = "INSERT INTO surveys (email, kategori, q1, q2, q3, q4, saran) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($koneksi, $sql_fallback);
    mysqli_stmt_bind_param($stmt, "ssiiiis", $nik, $kategori, $q1, $q2, $q3, $q4, $saran);
} else {
    mysqli_stmt_bind_param($stmt, "ssssiiiiiisi",
        $nik, $poli, $jalur, $kategori,
        $q1, $q2, $q3, $q4, $q5, $nps_score,
        $saran, $id_kunjungan
    );
}

if (mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);

    // Update status kunjungan jika jalur kunjungan
    if ($jalur === 'kunjungan' && $id_kunjungan > 0) {
        $upd = mysqli_prepare($koneksi, "UPDATE kunjungan SET status_survei = 'Sudah' WHERE id = ?");
        mysqli_stmt_bind_param($upd, "i", $id_kunjungan);
        mysqli_stmt_execute($upd);
        mysqli_stmt_close($upd);
    }

    // Bersihkan session
    unset($_SESSION['id_kunjungan_aktif'], $_SESSION['poli_aktif'], $_SESSION['jalur_survei'], $_SESSION['csrf_token']);

    $_SESSION['flash'] = [
        'type'    => 'success',
        'title'   => '🎉 Survei Terkirim!',
        'message' => 'Terima kasih atas penilaian Anda. Masukan Anda sangat berarti bagi peningkatan layanan kami.'
    ];
    header("Location: ../index.php"); exit();

} else {
    mysqli_stmt_close($stmt);
    error_log("prosesSurvei error: " . mysqli_error($koneksi));
    $_SESSION['flash'] = ['type'=>'error','title'=>'Gagal Menyimpan','message'=>'Terjadi kesalahan. Silakan coba lagi.'];
    header("Location: ../survei.php"); exit();
}
?>