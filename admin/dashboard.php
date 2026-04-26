<?php
session_start();
if (!isset($_SESSION['nik']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php"); exit();
}
include '../server/koneksi.php';

// Ambil statistik ringkas
$total_users = 0; $total_survei = 0; $avg_global = '0.00';

$r = mysqli_query($koneksi, "SELECT COUNT(*) AS cnt FROM users WHERE role='user'");
if ($r) $total_users = mysqli_fetch_assoc($r)['cnt'];

$r2 = mysqli_query($koneksi, "SELECT COUNT(*) AS cnt, AVG((q1+q2+q3+q4)/4) AS avg_all FROM surveys");
if ($r2) { $row2 = mysqli_fetch_assoc($r2); $total_survei = $row2['cnt']; $avg_global = number_format($row2['avg_all'] ?? 0, 2); }
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Admin Panel — UNS Medical Center</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    *{font-family:'Plus Jakarta Sans',sans-serif;box-sizing:border-box;}
    body{background:linear-gradient(135deg,#f8fafc 0%,#eff6ff 100%);min-height:100vh;}
    .clay-nav{background:rgba(255,255,255,.95);backdrop-filter:blur(16px);border-bottom:1px solid #e2e8f0;
      box-shadow:0 2px 12px rgba(0,0,0,.05);}
    .menu-card{background:#fff;border-radius:24px;box-shadow:0 10px 30px rgba(0,0,0,.06);
      border:1px solid #f1f5f9;padding:2rem;transition:transform .22s ease,box-shadow .22s ease;
      text-decoration:none;display:flex;flex-direction:column;align-items:center;text-align:center;gap:4px;}
    .menu-card:hover{transform:translateY(-5px);box-shadow:0 20px 40px rgba(0,0,0,.1);}
    .menu-icon{width:64px;height:64px;border-radius:20px;display:flex;align-items:center;justify-content:center;
      font-size:1.8rem;margin-bottom:.75rem;box-shadow:inset 2px 2px 6px rgba(255,255,255,.8);}
    .stat-box{background:#fff;border-radius:20px;border:1px solid #e2e8f0;padding:1.25rem 1.5rem;
      box-shadow:0 5px 15px rgba(0,0,0,.04);display:flex;align-items:center;gap:1rem;
      transition:transform .2s;}
    .stat-box:hover{transform:translateY(-2px);}
  </style>
</head>
<body>
  <nav class="clay-nav sticky top-0 z-50">
    <div class="max-w-5xl mx-auto px-4 py-4 flex items-center justify-between">
      <div class="flex items-center gap-3">
        <div class="w-9 h-9 bg-blue-600 rounded-xl flex items-center justify-center text-white font-extrabold text-sm">⚙️</div>
        <div>
          <p class="font-extrabold text-gray-800 leading-tight">Admin Panel</p>
          <p class="text-xs text-gray-400">UNS Medical Center</p>
        </div>
      </div>
      <div class="flex items-center gap-3">
        <div class="hidden sm:block text-right">
          <p class="text-sm font-bold text-gray-700"><?php echo htmlspecialchars($_SESSION['nama']); ?></p>
          <p class="text-xs text-blue-500 font-semibold">Administrator</p>
        </div>
        <button onclick="confirmLogout()"
          class="text-xs font-bold text-white bg-red-500 hover:bg-red-600 px-4 py-2 rounded-xl transition">
          Logout
        </button>
      </div>
    </div>
  </nav>

  <main class="max-w-5xl mx-auto px-4 py-8">

    <!-- Header -->
    <div class="mb-6">
      <h1 class="text-2xl font-extrabold text-gray-800">Selamat datang, <?php echo htmlspecialchars(explode(' ', $_SESSION['nama'])[0]); ?>! 👋</h1>
      <p class="text-gray-500 text-sm mt-1">Berikut ringkasan data sistem hari ini.</p>
    </div>

    <!-- STAT BOXES -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
      <div class="stat-box">
        <div class="w-12 h-12 rounded-2xl bg-blue-100 flex items-center justify-center text-2xl flex-shrink-0">👥</div>
        <div>
          <p class="text-xs text-gray-400 font-bold">Total Pasien</p>
          <h3 class="text-2xl font-extrabold text-gray-800"><?php echo $total_users; ?></h3>
        </div>
      </div>
      <div class="stat-box">
        <div class="w-12 h-12 rounded-2xl bg-green-100 flex items-center justify-center text-2xl flex-shrink-0">📊</div>
        <div>
          <p class="text-xs text-gray-400 font-bold">Total Survei</p>
          <h3 class="text-2xl font-extrabold text-gray-800"><?php echo $total_survei; ?></h3>
        </div>
      </div>
      <div class="stat-box">
        <div class="w-12 h-12 rounded-2xl bg-yellow-100 flex items-center justify-center text-2xl flex-shrink-0">⭐</div>
        <div>
          <p class="text-xs text-gray-400 font-bold">Indeks Kepuasan</p>
          <h3 class="text-2xl font-extrabold text-gray-800"><?php echo $avg_global; ?><span class="text-sm text-gray-400">/5</span></h3>
        </div>
      </div>
    </div>

    <!-- MENU CARDS -->
    <h2 class="text-base font-bold text-gray-700 mb-4">Menu Pengelolaan</h2>
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
      <a href="kelola_user.php" class="menu-card group">
        <div class="menu-icon bg-blue-50 group-hover:bg-blue-100 transition">👥</div>
        <h2 class="font-extrabold text-gray-800">Kelola Pasien</h2>
        <p class="text-xs text-gray-400">Lihat dan hapus data pasien terdaftar.</p>
        <span class="mt-3 text-xs font-bold text-blue-600 bg-blue-50 px-3 py-1 rounded-full">
          <?php echo $total_users; ?> Pasien →
        </span>
      </a>

      <a href="kelola_admin.php" class="menu-card group">
        <div class="menu-icon bg-purple-50 group-hover:bg-purple-100 transition">🛡️</div>
        <h2 class="font-extrabold text-gray-800">Kelola Admin</h2>
        <p class="text-xs text-gray-400">Tambah atau cabut akses administrator.</p>
        <span class="mt-3 text-xs font-bold text-purple-600 bg-purple-50 px-3 py-1 rounded-full">
          Manajemen Akses →
        </span>
      </a>

      <a href="kelola_survei.php" class="menu-card group">
        <div class="menu-icon bg-green-50 group-hover:bg-green-100 transition">📊</div>
        <h2 class="font-extrabold text-gray-800">Laporan Survei</h2>
        <p class="text-xs text-gray-400">Pantau dan analisis hasil penilaian pasien.</p>
        <span class="mt-3 text-xs font-bold text-green-600 bg-green-50 px-3 py-1 rounded-full">
          <?php echo $total_survei; ?> Respons →
        </span>
      </a>
    </div>

  </main>

  <form id="form-logout" action="../proses/logout.php" method="POST" style="display:none;"></form>

  <script>
    function confirmLogout() {
      Swal.fire({
        title: 'Keluar dari Admin Panel?',
        text: 'Sesi admin Anda akan diakhiri.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya, Logout',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
      }).then(result => {
        if (result.isConfirmed) document.getElementById('form-logout').submit();
      });
    }
  </script>
</body>
</html>
