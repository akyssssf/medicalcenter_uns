<?php
session_start();
if (!isset($_SESSION['nik'])) { header("Location: login.php"); exit(); }

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
$nama = $_SESSION['nama'];
$nik  = $_SESSION['nik'];
$inisial = strtoupper(substr(trim($nama), 0, 1));
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Dashboard — UNS Medical Center</title>
  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet"/>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    :root {
      --blue: #2563eb;
      --blue-light: #eff6ff;
      --green: #16a34a;
      --amber: #d97706;
      --gray-50: #f8fafc;
      --gray-100: #f1f5f9;
      --gray-200: #e2e8f0;
      --gray-400: #94a3b8;
      --gray-600: #475569;
      --gray-800: #1e293b;
      --radius: 22px;
      --shadow: 0 4px 24px rgba(15,23,42,.07);
      --shadow-md: 0 8px 32px rgba(15,23,42,.10);
    }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: 'DM Sans', sans-serif;
      background: #f0f4ff;
      min-height: 100vh;
      color: var(--gray-800);
      overflow-x: hidden;
    }

    /* Background */
    .bg-mesh {
      position: fixed; inset: 0; z-index: 0; pointer-events: none;
      background:
        radial-gradient(ellipse 60% 50% at 8% 15%, rgba(99,143,247,.16) 0%, transparent 60%),
        radial-gradient(ellipse 50% 60% at 92% 85%, rgba(52,211,153,.11) 0%, transparent 60%),
        radial-gradient(ellipse 80% 80% at 50% 50%, rgba(240,244,255,1) 0%, transparent 100%);
    }

    /* ── Top Nav ── */
    .top-nav {
      background: linear-gradient(135deg, #0f2057, #1e3a8a, #0369a1);
      position: sticky; top: 0; z-index: 100;
      box-shadow: 0 4px 24px rgba(15,32,87,.35);
    }
    .nav-inner { max-width: 780px; margin: 0 auto; padding: 0 20px; height: 62px; display: flex; align-items: center; justify-content: space-between; gap: 12px; }
    .nav-brand { display: flex; align-items: center; gap: 10px; }
    .nav-brand img { height: 28px; object-fit: contain; opacity: .92; }
    .nav-home {
      font-size: .7rem; font-weight: 700; color: rgba(147,197,253,.8);
      text-decoration: none; display: flex; align-items: center; gap: 4px;
      padding: 5px 10px; border-radius: 8px; border: 1px solid rgba(255,255,255,.15);
      background: rgba(255,255,255,.08); transition: .2s;
    }
    .nav-home:hover { background: rgba(255,255,255,.18); color: white; }
    .nav-right { display: flex; align-items: center; gap: 10px; }
    .nav-user-pill {
      display: flex; align-items: center; gap: 8px;
      background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.2);
      border-radius: 12px; padding: 6px 12px;
    }
    .nav-avatar {
      width: 28px; height: 28px; border-radius: 9px;
      background: rgba(255,255,255,.25); color: white;
      font-family: 'Sora', sans-serif; font-weight: 800; font-size: .75rem;
      display: flex; align-items: center; justify-content: center; flex-shrink: 0;
    }
    .nav-user-info {}
    .nav-user-name { font-size: .75rem; font-weight: 700; color: white; }
    .nav-user-nik { font-size: .6rem; color: rgba(147,197,253,.75); }
    .btn-logout {
      font-size: .7rem; font-weight: 700; color: #fca5a5;
      border: 1px solid rgba(252,165,165,.3); background: rgba(239,68,68,.12);
      padding: 6px 12px; border-radius: 10px; cursor: pointer; transition: .2s;
    }
    .btn-logout:hover { background: rgba(239,68,68,.25); color: white; }

    /* ── Main ── */
    main { max-width: 780px; margin: 0 auto; padding: 24px 16px 60px; position: relative; z-index: 1; display: flex; flex-direction: column; gap: 16px; }

    /* ── Greeting Banner ── */
    .greeting {
      background: linear-gradient(135deg, #0f2057, #1e3a8a, #0369a1);
      border-radius: var(--radius); padding: 28px;
      position: relative; overflow: hidden;
      box-shadow: 0 10px 40px rgba(15,32,87,.35);
      animation: fadeUp .4s ease both;
    }
    .greeting::before {
      content: ''; position: absolute; right: -40px; top: -40px;
      width: 200px; height: 200px; border-radius: 50%;
      background: rgba(255,255,255,.05); pointer-events: none;
    }
    .greeting::after {
      content: ''; position: absolute; right: 20px; bottom: -20px;
      width: 120px; height: 120px; border-radius: 50%;
      background: rgba(255,255,255,.04); pointer-events: none;
    }
    .g-eyebrow { font-size: .72rem; font-weight: 700; color: rgba(147,197,253,.85); margin-bottom: 6px; }
    .g-name { font-family: 'Sora', sans-serif; font-weight: 800; font-size: 1.7rem; color: white; line-height: 1.2; position: relative; z-index: 1; }
    .g-sub { font-size: .82rem; color: rgba(147,197,253,.85); margin-top: 8px; line-height: 1.6; position: relative; z-index: 1; max-width: 360px; }
    .g-date-row { display: flex; gap: 10px; margin-top: 18px; position: relative; z-index: 1; }
    .g-date-chip {
      background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.2);
      border-radius: 12px; padding: 8px 16px; text-align: center;
    }
    .g-date-val { font-family: 'Sora', sans-serif; font-weight: 800; font-size: 1.1rem; color: white; }
    .g-date-lbl { font-size: .6rem; color: rgba(147,197,253,.75); font-weight: 600; }

    /* ── Section Title ── */
    .section-title { font-family: 'Sora', sans-serif; font-weight: 700; font-size: .88rem; color: var(--gray-600); margin-bottom: 4px; display: flex; align-items: center; gap: 6px; }

    /* ── Guide Card ── */
    .guide-card {
      background: white; border-radius: var(--radius);
      padding: 18px 20px; box-shadow: var(--shadow);
      border: 1px solid rgba(226,232,240,.6);
      animation: fadeUp .4s .08s ease both;
    }
    .guide-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-top: 12px; }
    .guide-item {
      border-radius: 14px; padding: 12px;
      display: flex; align-items: flex-start; gap: 10px;
    }
    .guide-num {
      width: 24px; height: 24px; border-radius: 8px;
      display: flex; align-items: center; justify-content: center;
      font-size: .7rem; font-weight: 800; color: white; flex-shrink: 0;
    }
    .guide-t { font-size: .78rem; font-weight: 700; color: var(--gray-800); }
    .guide-s { font-size: .68rem; color: var(--gray-400); margin-top: 3px; line-height: 1.4; }

    /* ── Jalur Cards ── */
    .jalur-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; animation: fadeUp .4s .14s ease both; }

    @media (max-width: 640px) {
      .jalur-grid { grid-template-columns: 1fr; }
      .guide-grid { grid-template-columns: 1fr; }
      .g-name { font-size: 1.3rem; }
    }

    .jalur-card {
      background: white; border-radius: var(--radius);
      border: 2px solid rgba(226,232,240,.6);
      box-shadow: var(--shadow); padding: 22px 18px;
      display: flex; flex-direction: column;
      transition: all .25s ease; cursor: default;
    }
    .jalur-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-md); }
    .jalur-card.token:hover { border-color: var(--blue); }
    .jalur-card.manual:hover { border-color: var(--green); }
    .jalur-card.umum:hover { border-color: var(--amber); }

    .jc-icon {
      width: 50px; height: 50px; border-radius: 16px;
      display: flex; align-items: center; justify-content: center;
      font-size: 1.5rem; margin-bottom: 14px;
    }
    .jc-title { font-family: 'Sora', sans-serif; font-weight: 800; font-size: .95rem; color: var(--gray-800); margin-bottom: 3px; }
    .jc-badge { font-size: .65rem; font-weight: 700; margin-bottom: 10px; }
    .jc-desc { font-size: .75rem; color: var(--gray-400); line-height: 1.6; flex: 1; margin-bottom: 14px; }
    .jc-hint {
      border-radius: 10px; padding: 8px 11px;
      font-size: .68rem; font-weight: 600; margin-bottom: 14px;
    }

    /* Inputs */
    .jc-input {
      width: 100%; background: var(--gray-50);
      border: 1.5px solid var(--gray-200); border-radius: 12px;
      padding: 10px 14px; font-size: .85rem; outline: none;
      font-family: 'DM Sans', sans-serif;
      transition: border-color .2s; margin-bottom: 8px;
    }
    .jc-input:focus { border-color: var(--blue); box-shadow: 0 0 0 3px rgba(37,99,235,.08); }
    .jc-input::placeholder { font-size: .82rem; }

    /* Submit buttons */
    .jc-btn {
      width: 100%; padding: 11px; border-radius: 12px; border: none;
      font-size: .8rem; font-weight: 800; color: white; cursor: pointer;
      transition: all .2s; font-family: 'DM Sans', sans-serif;
      display: flex; align-items: center; justify-content: center; gap: 5px;
    }
    .jc-btn:hover { transform: translateY(-2px); filter: brightness(1.06); }
    .jc-btn:active { transform: scale(.98); }
    .btn-blue { background: linear-gradient(135deg, #3b82f6, #1d4ed8); box-shadow: 0 4px 16px rgba(37,99,235,.3); }
    .btn-green { background: linear-gradient(135deg, #22c55e, #15803d); box-shadow: 0 4px 16px rgba(34,197,94,.3); }
    .btn-amber { background: linear-gradient(135deg, #f59e0b, #d97706); box-shadow: 0 4px 16px rgba(245,158,11,.3); }

    /* ── Riwayat Card ── */
    .riwayat-card {
      background: white; border-radius: var(--radius);
      border: 1.5px solid rgba(226,232,240,.6);
      box-shadow: var(--shadow);
      padding: 18px 20px;
      display: flex; align-items: center; justify-content: space-between; gap: 14px;
      animation: fadeUp .4s .2s ease both;
      transition: all .2s;
    }
    .riwayat-card:hover { transform: translateY(-2px); box-shadow: var(--shadow-md); }
    .riwayat-left { display: flex; align-items: center; gap: 14px; }
    .riwayat-icon {
      width: 46px; height: 46px; border-radius: 14px;
      background: linear-gradient(135deg, #e0f2fe, #dbeafe);
      display: flex; align-items: center; justify-content: center;
      font-size: 1.4rem; flex-shrink: 0;
    }
    .riwayat-title { font-family: 'Sora', sans-serif; font-weight: 700; font-size: .92rem; color: var(--gray-800); }
    .riwayat-sub { font-size: .72rem; color: var(--gray-400); margin-top: 3px; line-height: 1.5; }
    .btn-riwayat {
      display: flex; align-items: center; gap: 6px;
      padding: 10px 18px; border-radius: 12px; border: none;
      background: linear-gradient(135deg, #2563eb, #1d4ed8);
      color: white; font-size: .78rem; font-weight: 700;
      cursor: pointer; transition: all .2s; font-family: 'DM Sans', sans-serif;
      text-decoration: none; white-space: nowrap;
      box-shadow: 0 4px 14px rgba(37,99,235,.3);
    }
    .btn-riwayat:hover { transform: translateY(-2px); filter: brightness(1.08); }

    @keyframes fadeUp { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: translateY(0); } }
  </style>
</head>
<body>
<div class="bg-mesh"></div>

<!-- ── NAV ── -->
<nav class="top-nav">
  <div class="nav-inner">
    <div class="nav-brand">
      <img src="https://senirupa.fkip.uns.ac.id/wp-content/uploads/2021/07/logo_putih.png" alt="UNS"/>
      <a href="index.php" class="nav-home">← Beranda</a>
    </div>
    <div class="nav-right">
      <div class="nav-user-pill">
        <div class="nav-avatar"><?php echo $inisial; ?></div>
        <div class="nav-user-info">
          <div class="nav-user-name"><?php echo htmlspecialchars(explode(' ', $nama)[0]); ?></div>
          <div class="nav-user-nik">NIK: <?php echo substr($nik, 0, 6).'**********'; ?></div>
        </div>
      </div>
      <button onclick="confirmLogout()" class="btn-logout">Logout</button>
    </div>
  </div>
</nav>

<main>

  <!-- ── GREETING ── -->
  <div class="greeting">
    <div class="g-eyebrow">👋 Selamat datang kembali</div>
    <div class="g-name"><?php echo htmlspecialchars($nama); ?></div>
    <div class="g-sub">Pilih jalur pengisian survei yang sesuai, atau lihat riwayat kunjungan Anda.</div>
    <div class="g-date-row">
      <div class="g-date-chip">
        <div class="g-date-val"><?php echo date('d'); ?></div>
        <div class="g-date-lbl"><?php echo date('M Y'); ?></div>
      </div>
      <div class="g-date-chip">
        <div class="g-date-val"><?php echo date('H:i'); ?></div>
        <div class="g-date-lbl">WIB</div>
      </div>
    </div>
  </div>

  <!-- ── RIWAYAT KUNJUNGAN shortcut ── -->
  <div class="riwayat-card">
    <div class="riwayat-left">
      <div class="riwayat-icon">📋</div>
      <div>
        <div class="riwayat-title">Riwayat Kunjungan Saya</div>
        <div class="riwayat-sub">Lihat semua kunjungan, token survei, & status pengisian.<br>Lupa token? Cek di sini!</div>
      </div>
    </div>
    <a href="riwayat_kunjungan.php" class="btn-riwayat">📋 Lihat Riwayat →</a>
  </div>

  <!-- ── PANDUAN ── -->
  <div class="guide-card">
    <div class="section-title">📋 Pilih Jalur Pengisian Survei</div>
    <div class="guide-grid">
      <div class="guide-item" style="background:#eff6ff;">
        <div class="guide-num" style="background:#2563eb;">1</div>
        <div>
          <div class="guide-t">Punya Struk Berobat?</div>
          <div class="guide-s">Gunakan token di struk — poli & data otomatis terisi</div>
        </div>
      </div>
      <div class="guide-item" style="background:#f0fdf4;">
        <div class="guide-num" style="background:#16a34a;">2</div>
        <div>
          <div class="guide-t">Ingat Tanggal Berobat?</div>
          <div class="guide-s">Pilih tanggal, sistem cocokkan riwayat kunjungan Anda</div>
        </div>
      </div>
      <div class="guide-item" style="background:#fef3c7;">
        <div class="guide-num" style="background:#d97706;">3</div>
        <div>
          <div class="guide-t">Feedback Umum?</div>
          <div class="guide-s">Isi survei layanan umum tanpa perlu verifikasi kunjungan</div>
        </div>
      </div>
    </div>
  </div>

  <!-- ── 3 JALUR CARDS ── -->
  <div class="jalur-grid">

    <!-- TOKEN -->
    <div class="jalur-card token">
      <div class="jc-icon" style="background:#eff6ff;">🎫</div>
      <div class="jc-title">Jalur Token</div>
      <div class="jc-badge" style="color:#2563eb;">⭐ Paling Direkomendasikan</div>
      <div class="jc-desc">Masukkan kode Token dari struk berobat Anda. Poli & data kunjungan otomatis terdeteksi.</div>
      <div class="jc-hint" style="background:#eff6ff;color:#2563eb;">💡 Format: <strong>TKN-001</strong></div>
      <form action="proses/cekKunjungan.php" method="POST">
        <input type="hidden" name="jalur" value="token">
        <input type="text" name="token" class="jc-input"
          style="text-transform:uppercase;text-align:center;letter-spacing:.1em;font-weight:700;"
          placeholder="TKN-XXX" maxlength="20" required/>
        <button type="submit" class="jc-btn btn-blue">⚡ Verifikasi Token</button>
      </form>
    </div>

    <!-- MANUAL -->
    <div class="jalur-card manual">
      <div class="jc-icon" style="background:#f0fdf4;">📅</div>
      <div class="jc-title">Cek Tanggal</div>
      <div class="jc-badge" style="color:#16a34a;">🟢 Struk Hilang? Tidak Masalah</div>
      <div class="jc-desc">Pilih tanggal kunjungan Anda. Sistem akan mencocokkan riwayat kunjungan secara otomatis.</div>
      <div class="jc-hint" style="background:#f0fdf4;color:#16a34a;">💡 Pilih tanggal saat Anda berobat ke klinik</div>
      <form action="proses/cekKunjungan.php" method="POST">
        <input type="hidden" name="jalur" value="manual">
        <input type="date" name="tanggal" class="jc-input" max="<?php echo date('Y-m-d'); ?>" required/>
        <button type="submit" class="jc-btn btn-green">🔍 Cari Kunjungan</button>
      </form>
    </div>

    <!-- UMUM -->
    <div class="jalur-card umum">
      <div class="jc-icon" style="background:#fef3c7;">💬</div>
      <div class="jc-title">Survei Umum</div>
      <div class="jc-badge" style="color:#d97706;">🟡 Tanpa Verifikasi Kunjungan</div>
      <div class="jc-desc">Berikan feedback umum tentang layanan klinik kami. Bisa diisi kapan saja meski tidak sedang berobat.</div>
      <div class="jc-hint" style="background:#fef3c7;color:#d97706;">💡 Tersedia untuk semua pengguna terdaftar</div>
      <div style="margin-top:auto;">
        <button onclick="mulaiSurveiUmum()" class="jc-btn btn-amber">✍️ Isi Survei Umum</button>
      </div>
    </div>
  </div>

</main>

<form id="form-logout" action="proses/logout.php" method="POST" style="display:none;"></form>
<form id="form-survei-umum" action="proses/cekKunjungan.php" method="POST" style="display:none;">
  <input type="hidden" name="jalur" value="umum">
</form>

<script>
  <?php if ($flash): ?>
  Swal.fire({
    icon: '<?php echo htmlspecialchars($flash["type"]); ?>',
    title: '<?php echo htmlspecialchars($flash["title"]); ?>',
    text: '<?php echo htmlspecialchars($flash["message"]); ?>',
    confirmButtonColor: '#2563eb',
    borderRadius: '20px',
  });
  <?php endif; ?>

  function mulaiSurveiUmum() {
    Swal.fire({
      title: '📝 Survei Layanan Umum',
      html: `<p style="font-size:.88rem;color:#475569;line-height:1.7;">
        Anda akan mengisi survei <strong>feedback umum</strong> tentang layanan UNS Medical Center.<br><br>
        Survei ini tidak terhubung dengan kunjungan spesifik dan dapat diisi kapan saja.
      </p>`,
      icon: 'info',
      showCancelButton: true,
      confirmButtonText: 'Lanjut Isi Survei ✍️',
      cancelButtonText: 'Batal',
      confirmButtonColor: '#f59e0b',
      cancelButtonColor: '#6b7280',
    }).then(r => { if (r.isConfirmed) document.getElementById('form-survei-umum').submit(); });
  }

  function confirmLogout() {
    Swal.fire({
      title: 'Keluar dari sistem?',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Ya, Logout',
      cancelButtonText: 'Batal',
      confirmButtonColor: '#ef4444',
      cancelButtonColor: '#6b7280',
    }).then(r => { if (r.isConfirmed) document.getElementById('form-logout').submit(); });
  }
</script>
</body>
</html>