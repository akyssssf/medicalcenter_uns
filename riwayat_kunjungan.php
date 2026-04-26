<?php
session_start();
include 'server/koneksi.php';

if (!isset($_SESSION['nik'])) { header("Location: login.php"); exit(); }

$nik  = $_SESSION['nik'];
$nama = $_SESSION['nama'];

// Ambil riwayat kunjungan milik user ini
$stmt = mysqli_prepare($koneksi,
    "SELECT id, poli, tgl_kunjungan, token, status_survei
     FROM kunjungan
     WHERE nik_pasien = ?
     ORDER BY tgl_kunjungan DESC, id DESC"
);
mysqli_stmt_bind_param($stmt, "s", $nik);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$kunjungan_list = [];
while ($row = mysqli_fetch_assoc($result)) {
    $kunjungan_list[] = $row;
}
mysqli_stmt_close($stmt);

$total = count($kunjungan_list);
$inisial = strtoupper(substr(trim($nama), 0, 1));

// Hitung stats
$sudah = 0; $belum = 0;
$poli_count = [];
foreach ($kunjungan_list as $k) {
    if ($k['status_survei'] === 'Sudah') $sudah++;
    else $belum++;
    $poli_count[$k['poli']] = ($poli_count[$k['poli']] ?? 0) + 1;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Riwayat Kunjungan — UNS Medical Center</title>
  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet"/>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    :root {
      --blue: #2563eb;
      --blue-light: #eff6ff;
      --green: #16a34a;
      --amber: #d97706;
      --red: #dc2626;
      --gray-50: #f8fafc;
      --gray-100: #f1f5f9;
      --gray-200: #e2e8f0;
      --gray-400: #94a3b8;
      --gray-600: #475569;
      --gray-800: #1e293b;
      --radius: 20px;
      --shadow-card: 0 4px 24px rgba(15,23,42,.07);
    }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: 'DM Sans', sans-serif;
      background: #f0f4ff;
      min-height: 100vh;
      color: var(--gray-800);
    }

    .bg-mesh {
      position: fixed; inset: 0; z-index: 0; pointer-events: none;
      background:
        radial-gradient(ellipse 60% 50% at 5% 10%, rgba(99,143,247,.15) 0%, transparent 60%),
        radial-gradient(ellipse 40% 50% at 95% 90%, rgba(52,211,153,.10) 0%, transparent 60%);
    }

    /* ── Nav ── */
    .top-nav {
      background: linear-gradient(135deg, #0f2057, #1e3a8a, #0369a1);
      position: sticky; top: 0; z-index: 100;
      box-shadow: 0 4px 24px rgba(15,32,87,.35);
    }
    .nav-inner { max-width: 800px; margin: 0 auto; padding: 0 20px; height: 60px; display: flex; align-items: center; justify-content: space-between; }
    .nav-left { display: flex; align-items: center; gap: 12px; }
    .btn-back-nav {
      width: 34px; height: 34px; border-radius: 10px;
      background: rgba(255,255,255,.15); border: 1px solid rgba(255,255,255,.25);
      color: white; font-size: 1rem; cursor: pointer; transition: .2s;
      display: flex; align-items: center; justify-content: center;
      text-decoration: none;
    }
    .btn-back-nav:hover { background: rgba(255,255,255,.25); }
    .nav-title-main { font-family: 'Sora', sans-serif; font-weight: 700; font-size: .9rem; color: white; }
    .nav-title-sub { font-size: .65rem; color: rgba(147,197,253,.85); }
    .nav-user {
      display: flex; align-items: center; gap: 8px;
      background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.2);
      border-radius: 12px; padding: 6px 12px;
    }
    .nav-avatar {
      width: 26px; height: 26px; border-radius: 8px;
      background: rgba(255,255,255,.25); color: white;
      font-family: 'Sora', sans-serif; font-weight: 800; font-size: .7rem;
      display: flex; align-items: center; justify-content: center;
    }
    .nav-user-name { font-size: .72rem; font-weight: 700; color: white; }

    /* ── Main ── */
    main { max-width: 800px; margin: 0 auto; padding: 24px 16px 60px; position: relative; z-index: 1; }

    /* ── Header Section ── */
    .page-header {
      background: linear-gradient(135deg, #1e3a8a, #1d4ed8, #0369a1);
      border-radius: var(--radius);
      padding: 24px 28px;
      margin-bottom: 20px;
      position: relative; overflow: hidden;
      box-shadow: 0 8px 32px rgba(30,58,138,.3);
    }
    .page-header::before {
      content: ''; position: absolute; right: -30px; top: -30px;
      width: 160px; height: 160px; border-radius: 50%;
      background: rgba(255,255,255,.06);
    }
    .page-header::after {
      content: '🏥'; position: absolute; right: 28px; bottom: 16px;
      font-size: 4rem; opacity: .12;
    }
    .ph-eyebrow { font-size: .7rem; font-weight: 700; color: rgba(147,197,253,.85); letter-spacing: .08em; text-transform: uppercase; margin-bottom: 6px; }
    .ph-title { font-family: 'Sora', sans-serif; font-weight: 800; font-size: 1.5rem; color: white; line-height: 1.2; margin-bottom: 6px; }
    .ph-sub { font-size: .8rem; color: rgba(147,197,253,.8); }

    /* ── Stats Row ── */
    .stats-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 20px; }
    .stat-card {
      background: white; border-radius: 16px;
      padding: 16px; text-align: center;
      box-shadow: var(--shadow-card);
      border: 1px solid rgba(226,232,240,.6);
    }
    .stat-icon { font-size: 1.5rem; margin-bottom: 6px; }
    .stat-val { font-family: 'Sora', sans-serif; font-weight: 800; font-size: 1.6rem; color: var(--gray-800); line-height: 1; }
    .stat-lbl { font-size: .68rem; font-weight: 600; color: var(--gray-400); margin-top: 4px; }

    /* ── Filter / Search ── */
    .toolbar {
      display: flex; gap: 10px; margin-bottom: 16px; flex-wrap: wrap; align-items: center;
    }
    .search-box {
      flex: 1; min-width: 200px;
      display: flex; align-items: center; gap: 8px;
      background: white; border: 1.5px solid var(--gray-200);
      border-radius: 14px; padding: 10px 14px;
      box-shadow: var(--shadow-card);
    }
    .search-box input {
      flex: 1; border: none; outline: none; font-size: .85rem;
      font-family: 'DM Sans', sans-serif; color: var(--gray-800); background: transparent;
    }
    .filter-btn {
      display: flex; align-items: center; gap: 6px;
      padding: 10px 14px; border-radius: 12px; border: 1.5px solid var(--gray-200);
      background: white; font-size: .78rem; font-weight: 700; color: var(--gray-600);
      cursor: pointer; transition: .18s; font-family: 'DM Sans', sans-serif;
    }
    .filter-btn.active { border-color: var(--blue); background: var(--blue-light); color: var(--blue); }
    .filter-btn:hover { border-color: #93c5fd; background: var(--blue-light); }

    /* ── Kunjungan Card ── */
    .kunjungan-list { display: flex; flex-direction: column; gap: 10px; }
    .kunjungan-card {
      background: white; border-radius: 18px;
      border: 1.5px solid rgba(226,232,240,.8);
      box-shadow: var(--shadow-card);
      overflow: hidden; transition: all .2s ease;
      cursor: default;
    }
    .kunjungan-card:hover { transform: translateY(-2px); box-shadow: 0 8px 32px rgba(15,23,42,.10); }

    .kc-main { display: flex; align-items: center; gap: 14px; padding: 16px 20px; }
    .kc-poli-icon {
      width: 46px; height: 46px; border-radius: 14px;
      display: flex; align-items: center; justify-content: center;
      font-size: 1.4rem; flex-shrink: 0;
    }
    .kc-info { flex: 1; min-width: 0; }
    .kc-poli-name { font-family: 'Sora', sans-serif; font-weight: 700; font-size: .92rem; color: var(--gray-800); }
    .kc-tanggal { font-size: .75rem; color: var(--gray-400); margin-top: 2px; }
    .kc-right { display: flex; flex-direction: column; align-items: flex-end; gap: 6px; }

    .badge {
      display: inline-flex; align-items: center; gap: 4px;
      padding: 4px 10px; border-radius: 8px;
      font-size: .65rem; font-weight: 800; letter-spacing: .02em;
    }
    .badge-done { background: #dcfce7; color: #15803d; }
    .badge-pending { background: #fef3c7; color: #b45309; }

    /* Token section */
    .kc-token-section {
      border-top: 1px dashed var(--gray-200);
      padding: 12px 20px;
      display: flex; align-items: center; justify-content: space-between; gap: 10px;
      background: var(--gray-50);
    }
    .token-label { font-size: .65rem; font-weight: 700; color: var(--gray-400); text-transform: uppercase; letter-spacing: .05em; }
    .token-code {
      font-family: 'Sora', sans-serif; font-weight: 800; font-size: 1rem;
      color: var(--blue); letter-spacing: .12em; margin-top: 2px;
    }
    .btn-copy {
      display: flex; align-items: center; gap: 5px;
      padding: 7px 12px; border-radius: 10px; border: 1.5px solid #bfdbfe;
      background: white; color: var(--blue);
      font-size: .72rem; font-weight: 700; cursor: pointer;
      transition: all .18s; font-family: 'DM Sans', sans-serif;
    }
    .btn-copy:hover { background: var(--blue-light); border-color: var(--blue); }
    .btn-copy.copied { background: #dcfce7; border-color: #22c55e; color: #15803d; }

    .btn-isi-survei {
      display: flex; align-items: center; gap: 5px;
      padding: 7px 14px; border-radius: 10px; border: none;
      background: linear-gradient(135deg, #f59e0b, #d97706);
      color: white; font-size: .72rem; font-weight: 700; cursor: pointer;
      transition: all .18s; font-family: 'DM Sans', sans-serif;
      text-decoration: none;
      box-shadow: 0 3px 10px rgba(245,158,11,.3);
    }
    .btn-isi-survei:hover { filter: brightness(1.08); transform: translateY(-1px); }

    /* ── Empty State ── */
    .empty-state {
      text-align: center; padding: 60px 20px;
      background: white; border-radius: var(--radius);
      box-shadow: var(--shadow-card);
      border: 1px solid rgba(226,232,240,.6);
    }
    .empty-icon { font-size: 3.5rem; margin-bottom: 14px; }
    .empty-title { font-family: 'Sora', sans-serif; font-weight: 700; font-size: 1.1rem; color: var(--gray-800); margin-bottom: 6px; }
    .empty-sub { font-size: .82rem; color: var(--gray-400); line-height: 1.6; }

    /* ── Hidden for filter ── */
    .kunjungan-card.hidden-filter { display: none; }

    /* ── Poli color map ── */
    /* applied via JS */

    /* ── Tooltip-like copy feedback ── */
    @keyframes fadeUp { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: translateY(0); } }
    .copy-toast {
      position: fixed; bottom: 24px; left: 50%; transform: translateX(-50%);
      background: #1e293b; color: white; padding: 10px 20px;
      border-radius: 12px; font-size: .82rem; font-weight: 600;
      z-index: 9999; animation: fadeUp .2s ease;
      box-shadow: 0 8px 24px rgba(15,23,42,.3);
    }
  </style>
</head>
<body>
<div class="bg-mesh"></div>

<!-- ── NAV ── -->
<nav class="top-nav">
  <div class="nav-inner">
    <div class="nav-left">
      <a href="dashboard.php" class="btn-back-nav">←</a>
      <div>
        <div class="nav-title-main">Riwayat Kunjungan</div>
        <div class="nav-title-sub">UNS Medical Center</div>
      </div>
    </div>
    <div class="nav-user">
      <div class="nav-avatar"><?php echo $inisial; ?></div>
      <div class="nav-user-name"><?php echo htmlspecialchars(explode(' ', $nama)[0]); ?></div>
    </div>
  </div>
</nav>

<main>
  <!-- ── PAGE HEADER ── -->
  <div class="page-header">
    <div class="ph-eyebrow">📋 Rekam Medis Digital</div>
    <div class="ph-title">Riwayat Kunjungan<br>Anda</div>
    <div class="ph-sub">Lihat semua kunjungan & token survei Anda di sini</div>
  </div>

  <!-- ── STATS ── -->
  <div class="stats-row">
    <div class="stat-card">
      <div class="stat-icon">🏥</div>
      <div class="stat-val"><?php echo $total; ?></div>
      <div class="stat-lbl">Total Kunjungan</div>
    </div>
    <div class="stat-card">
      <div class="stat-icon">✅</div>
      <div class="stat-val" style="color:#15803d;"><?php echo $sudah; ?></div>
      <div class="stat-lbl">Survei Diisi</div>
    </div>
    <div class="stat-card">
      <div class="stat-icon">⏳</div>
      <div class="stat-val" style="color:#b45309;"><?php echo $belum; ?></div>
      <div class="stat-lbl">Belum Diisi</div>
    </div>
  </div>

  <!-- ── TOOLBAR ── -->
  <div class="toolbar">
    <div class="search-box">
      <span>🔍</span>
      <input type="text" id="search-input" placeholder="Cari poli, tanggal, atau token..." oninput="applyFilter()"/>
    </div>
    <button class="filter-btn active" data-filter="all" onclick="setFilter('all', this)">Semua</button>
    <button class="filter-btn" data-filter="Belum" onclick="setFilter('Belum', this)">⏳ Belum diisi</button>
    <button class="filter-btn" data-filter="Sudah" onclick="setFilter('Sudah', this)">✅ Sudah diisi</button>
  </div>

  <!-- ── LIST ── -->
  <?php if (empty($kunjungan_list)): ?>
  <div class="empty-state">
    <div class="empty-icon">🏥</div>
    <div class="empty-title">Belum Ada Riwayat Kunjungan</div>
    <div class="empty-sub">Data kunjungan Anda akan muncul di sini<br>setelah Anda berobat ke UNS Medical Center.</div>
  </div>
  <?php else: ?>
  <div class="kunjungan-list" id="kunjungan-list">
    <?php
    $poli_config = [
      'Umum' => ['icon'=>'🏥','color'=>'#2563eb','bg'=>'#eff6ff'],
      'Gigi' => ['icon'=>'🦷','color'=>'#15803d','bg'=>'#f0fdf4'],
      'KIA'  => ['icon'=>'🤱','color'=>'#9d174d','bg'=>'#fdf2f8'],
    ];
    foreach ($kunjungan_list as $idx => $k):
      $pc = $poli_config[$k['poli']] ?? $poli_config['Umum'];
      $tgl_fmt = date('d F Y', strtotime($k['tgl_kunjungan']));
      $hari    = ['Sunday'=>'Minggu','Monday'=>'Senin','Tuesday'=>'Selasa','Wednesday'=>'Rabu',
                  'Thursday'=>'Kamis','Friday'=>'Jumat','Saturday'=>'Sabtu'];
      $tgl_hari = $hari[date('l', strtotime($k['tgl_kunjungan']))] ?? date('l', strtotime($k['tgl_kunjungan']));
      $is_done = $k['status_survei'] === 'Sudah';
      $bulan_lalu = (strtotime('today') - strtotime($k['tgl_kunjungan'])) > (30*86400);
    ?>
    <div class="kunjungan-card"
      data-status="<?php echo $k['status_survei']; ?>"
      data-search="<?php echo strtolower($k['poli'].' '.$k['tgl_kunjungan'].' '.$k['token']); ?>">

      <div class="kc-main">
        <div class="kc-poli-icon" style="background:<?php echo $pc['bg']; ?>;">
          <?php echo $pc['icon']; ?>
        </div>
        <div class="kc-info">
          <div class="kc-poli-name">Poli <?php echo htmlspecialchars($k['poli']); ?></div>
          <div class="kc-tanggal">📅 <?php echo $tgl_hari.', '.$tgl_fmt; ?></div>
        </div>
        <div class="kc-right">
          <?php if ($is_done): ?>
          <span class="badge badge-done">✓ Survei Diisi</span>
          <?php else: ?>
          <span class="badge badge-pending">⏳ Belum Diisi</span>
          <?php endif; ?>
          <span style="font-size:.65rem;color:var(--gray-400);font-weight:600;">
            #<?php echo str_pad($k['id'], 4, '0', STR_PAD_LEFT); ?>
          </span>
        </div>
      </div>

      <div class="kc-token-section">
        <div>
          <div class="token-label">🎫 Token Survei</div>
          <div class="token-code" id="token-<?php echo $idx; ?>"><?php echo htmlspecialchars($k['token'] ?? '—'); ?></div>
        </div>
        <div style="display:flex;gap:8px;align-items:center;">
          <?php if ($k['token']): ?>
          <button class="btn-copy" onclick="copyToken('<?php echo htmlspecialchars($k['token']); ?>', <?php echo $idx; ?>)" id="copybtn-<?php echo $idx; ?>">
            📋 Salin Token
          </button>
          <?php endif; ?>
          <?php if (!$is_done && $k['token']): ?>
          <form action="proses/cekKunjungan.php" method="POST" style="display:inline;">
            <input type="hidden" name="jalur" value="token">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($k['token']); ?>">
            <button type="submit" class="btn-isi-survei">✍️ Isi Survei</button>
          </form>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <?php if ($total > 0 && $belum > 0): ?>
  <div style="margin-top:20px;background:linear-gradient(135deg,#fef3c7,#fff7ed);border:1.5px solid #fde68a;border-radius:16px;padding:14px 18px;display:flex;align-items:center;gap:12px;">
    <span style="font-size:1.4rem;">💡</span>
    <div>
      <p style="font-size:.8rem;font-weight:700;color:#92400e;">Anda punya <?php echo $belum; ?> survei yang belum diisi</p>
      <p style="font-size:.72rem;color:#b45309;margin-top:2px;">Salin token di atas dan gunakan di halaman Dashboard untuk mengisi survei, atau klik tombol "Isi Survei" langsung.</p>
    </div>
  </div>
  <?php endif; ?>
</main>

<script>
let currentFilter = 'all';

function setFilter(filter, el) {
  currentFilter = filter;
  document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
  el.classList.add('active');
  applyFilter();
}

function applyFilter() {
  const q = document.getElementById('search-input').value.toLowerCase();
  document.querySelectorAll('.kunjungan-card').forEach(card => {
    const matchStatus = currentFilter === 'all' || card.dataset.status === currentFilter;
    const matchSearch = !q || card.dataset.search.includes(q);
    card.style.display = (matchStatus && matchSearch) ? '' : 'none';
  });
}

function copyToken(token, idx) {
  navigator.clipboard.writeText(token).then(() => {
    const btn = document.getElementById('copybtn-'+idx);
    btn.classList.add('copied');
    btn.textContent = '✓ Tersalin!';
    showToast('Token ' + token + ' berhasil disalin!');
    setTimeout(() => {
      btn.classList.remove('copied');
      btn.textContent = '📋 Salin Token';
    }, 2500);
  }).catch(() => {
    // Fallback
    const el = document.createElement('textarea');
    el.value = token;
    el.style.position = 'fixed'; el.style.opacity = '0';
    document.body.appendChild(el); el.select();
    document.execCommand('copy');
    document.body.removeChild(el);
    showToast('Token ' + token + ' berhasil disalin!');
  });
}

function showToast(msg) {
  const old = document.querySelector('.copy-toast');
  if (old) old.remove();
  const toast = document.createElement('div');
  toast.className = 'copy-toast';
  toast.textContent = '✅ ' + msg;
  document.body.appendChild(toast);
  setTimeout(() => toast.remove(), 2500);
}
</script>
</body>
</html>