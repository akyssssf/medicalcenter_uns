<?php
require_once __DIR__ . "/../server/bootstrap.php";
if (!isset($_SESSION['nik']) || $_SESSION['role'] !== 'admin') {
    header("Location: /login.php"); exit();
}

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

    /* ── Nav ── */
    .clay-nav{background:rgba(255,255,255,.97);backdrop-filter:blur(16px);
      border-bottom:1px solid #e2e8f0;box-shadow:0 2px 12px rgba(0,0,0,.05);position:sticky;top:0;z-index:50;}
    .nav-inner{max-width:900px;margin:0 auto;padding:0 20px;height:60px;
      display:flex;align-items:center;justify-content:space-between;gap:12px;}

    /* ── Stat cards ── */
    .stat-box{background:#fff;border-radius:20px;border:1px solid #e2e8f0;padding:1.1rem 1.25rem;
      box-shadow:0 4px 12px rgba(0,0,0,.04);display:flex;align-items:center;gap:1rem;
      transition:transform .2s;}
    .stat-box:hover{transform:translateY(-2px);}
    .stat-icon{width:48px;height:48px;border-radius:16px;display:flex;align-items:center;
      justify-content:center;font-size:1.5rem;flex-shrink:0;}

    /* ── Menu cards ── */
    .menu-card{background:#fff;border-radius:20px;box-shadow:0 8px 24px rgba(0,0,0,.06);
      border:1px solid #f1f5f9;padding:1.5rem 1.25rem;transition:transform .22s ease,box-shadow .22s ease;
      text-decoration:none;display:flex;flex-direction:column;align-items:center;text-align:center;gap:2px;}
    .menu-card:hover{transform:translateY(-4px);box-shadow:0 16px 36px rgba(0,0,0,.1);}
    .menu-icon{width:56px;height:56px;border-radius:18px;display:flex;align-items:center;
      justify-content:center;font-size:1.6rem;margin-bottom:.6rem;}

    /* ── Grid layout ── */
    .stat-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:24px;}
    .menu-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;}

    /* ── TABLET (≤768px) ── */
    @media(max-width:768px){
      .stat-grid{grid-template-columns:repeat(3,1fr);gap:8px;}
      .menu-grid{grid-template-columns:repeat(3,1fr);gap:10px;}
      .stat-icon{width:40px;height:40px;font-size:1.2rem;border-radius:12px;}
      .stat-box{padding:.85rem 1rem;gap:.75rem;border-radius:16px;}
      .stat-box h3{font-size:1.4rem;}
      .menu-card{padding:1.25rem .9rem;border-radius:16px;}
      .menu-icon{width:46px;height:46px;font-size:1.3rem;border-radius:14px;margin-bottom:.4rem;}
      .menu-card h2{font-size:.82rem;}
      .menu-card p{font-size:.7rem;}
      .menu-card span{font-size:.68rem;padding:3px 10px;}
      main{padding:16px 14px 40px;}
    }

    /* ── MOBILE (≤480px) ── */
    @media(max-width:480px){
      .stat-grid{grid-template-columns:1fr;gap:8px;}
      .stat-box{flex-direction:row;padding:.9rem 1rem;}
      .menu-grid{grid-template-columns:1fr;gap:10px;}
      .menu-card{flex-direction:row;text-align:left;align-items:center;padding:1rem 1.1rem;gap:14px;}
      .menu-icon{margin-bottom:0;flex-shrink:0;width:44px;height:44px;}
      .menu-card-body{display:flex;flex-direction:column;gap:2px;flex:1;}
      .menu-card h2{font-size:.88rem;}
      .menu-card p{font-size:.72rem;}
      .menu-card span{align-self:flex-start;margin-top:4px;}
      .nav-name{display:none;}
      main{padding:14px 12px 40px;}
    }

    /* ── VERY SMALL (≤360px) ── */
    @media(max-width:360px){
      .nav-inner{padding:0 12px;}
      .menu-card{padding:.85rem .9rem;gap:10px;}
    }
  </style>
</head>
<body>
  <nav class="clay-nav">
    <div class="nav-inner">
      <div style="display:flex;align-items:center;gap:10px;">
        <div style="width:36px;height:36px;background:linear-gradient(135deg,#2563eb,#1d4ed8);border-radius:12px;
          display:flex;align-items:center;justify-content:center;font-size:1rem;flex-shrink:0;">⚙️</div>
        <div>
          <p style="font-weight:800;font-size:.92rem;color:#1e293b;line-height:1.2;">Admin Panel</p>
          <p style="font-size:.7rem;color:#94a3b8;">UNS Medical Center</p>
        </div>
      </div>
      <div style="display:flex;align-items:center;gap:10px;">
        <div class="nav-name" style="text-align:right;">
          <p style="font-size:.82rem;font-weight:700;color:#374151;"><?php echo htmlspecialchars($_SESSION['nama']); ?></p>
          <p style="font-size:.68rem;color:#3b82f6;font-weight:600;">Administrator</p>
        </div>
        <button onclick="confirmLogout()"
          style="font-size:.78rem;font-weight:700;color:#fff;background:#ef4444;border:none;
          padding:8px 14px;border-radius:10px;cursor:pointer;transition:background .2s;white-space:nowrap;"
          onmouseover="this.style.background='#dc2626'" onmouseout="this.style.background='#ef4444'">
          Logout
        </button>
      </div>
    </div>
  </nav>

  <main style="max-width:900px;margin:0 auto;padding:24px 20px 60px;">

    <!-- Header -->
    <div style="margin-bottom:20px;">
      <h1 style="font-size:1.4rem;font-weight:800;color:#1e293b;">
        Selamat datang, <?php echo htmlspecialchars(explode(' ', $_SESSION['nama'])[0]); ?>! 👋
      </h1>
      <p style="font-size:.83rem;color:#94a3b8;margin-top:3px;">Berikut ringkasan data sistem hari ini.</p>
    </div>

    <!-- STAT BOXES -->
    <div class="stat-grid">
      <div class="stat-box">
        <div class="stat-icon" style="background:#eff6ff;">👥</div>
        <div>
          <p style="font-size:.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.05em;">Total Pasien</p>
          <h3 style="font-size:1.6rem;font-weight:800;color:#1e293b;line-height:1;"><?php echo $total_users; ?></h3>
        </div>
      </div>
      <div class="stat-box">
        <div class="stat-icon" style="background:#f0fdf4;">📊</div>
        <div>
          <p style="font-size:.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.05em;">Total Survei</p>
          <h3 style="font-size:1.6rem;font-weight:800;color:#1e293b;line-height:1;"><?php echo $total_survei; ?></h3>
        </div>
      </div>
      <div class="stat-box">
        <div class="stat-icon" style="background:#fefce8;">⭐</div>
        <div>
          <p style="font-size:.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.05em;">Indeks Kepuasan</p>
          <h3 style="font-size:1.6rem;font-weight:800;color:#1e293b;line-height:1;">
            <?php echo $avg_global; ?><span style="font-size:.8rem;color:#94a3b8;font-weight:600;">/5</span>
          </h3>
        </div>
      </div>
    </div>

    <!-- MENU CARDS -->
    <p style="font-size:.78rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.08em;margin-bottom:14px;">
      Menu Pengelolaan
    </p>
    <div class="menu-grid">

      <a href="/admin/kelola_user.php" class="menu-card group">
        <div class="menu-icon" style="background:#eff6ff;">👥</div>
        <div class="menu-card-body">
          <h2 style="font-weight:800;color:#1e293b;">Kelola Pasien</h2>
          <p style="font-size:.75rem;color:#94a3b8;">Lihat dan hapus data pasien terdaftar.</p>
          <span style="margin-top:8px;font-size:.72rem;font-weight:700;color:#2563eb;
            background:#eff6ff;padding:4px 12px;border-radius:999px;display:inline-block;">
            <?php echo $total_users; ?> Pasien →
          </span>
        </div>
      </a>

      <a href="/admin/kelola_admin.php" class="menu-card group">
        <div class="menu-icon" style="background:#faf5ff;">🛡️</div>
        <div class="menu-card-body">
          <h2 style="font-weight:800;color:#1e293b;">Kelola Admin</h2>
          <p style="font-size:.75rem;color:#94a3b8;">Tambah atau cabut akses administrator.</p>
          <span style="margin-top:8px;font-size:.72rem;font-weight:700;color:#7c3aed;
            background:#faf5ff;padding:4px 12px;border-radius:999px;display:inline-block;">
            Manajemen Akses →
          </span>
        </div>
      </a>

      <a href="/admin/kelola_survei.php" class="menu-card group">
        <div class="menu-icon" style="background:#f0fdf4;">📊</div>
        <div class="menu-card-body">
          <h2 style="font-weight:800;color:#1e293b;">Laporan Survei</h2>
          <p style="font-size:.75rem;color:#94a3b8;">Pantau dan analisis hasil penilaian pasien.</p>
          <span style="margin-top:8px;font-size:.72rem;font-weight:700;color:#16a34a;
            background:#f0fdf4;padding:4px 12px;border-radius:999px;display:inline-block;">
            <?php echo $total_survei; ?> Respons →
          </span>
        </div>
      </a>

    </div>
  </main>

  <form id="form-logout" action="/proses/logout.php" method="POST" style="display:none;"></form>

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
