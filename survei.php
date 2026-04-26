<?php
session_start();

if (!isset($_SESSION['nik'])) { header("Location: login.php"); exit(); }

$jalur = $_SESSION['jalur_survei'] ?? null;
if (!$jalur) { header("Location: dashboard.php"); exit(); }
if ($jalur === 'kunjungan' && !isset($_SESSION['id_kunjungan_aktif'])) {
    header("Location: dashboard.php"); exit();
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$poli         = $_SESSION['poli_aktif'] ?? null;
$nama_user    = $_SESSION['nama'];
$nik_user     = $_SESSION['nik'];
$is_kunjungan = ($jalur === 'kunjungan');

// Auto-detect kategori dari NIK (opsional, atau bisa dari session/db)
// Untuk display di header
$inisial = strtoupper(substr(trim($nama_user), 0, 1));

// Info per poli
$poli_info = [
    'Umum' => ['icon'=>'🏥','color'=>'#2563eb','grad'=>'linear-gradient(135deg,#2563eb,#0ea5e9)','bg'=>'#eff6ff','label'=>'Poli Umum',
        'q_spesifik'=>'Apakah dokter menjelaskan diagnosis dan pengobatan Anda dengan jelas dan mudah dipahami?'],
    'Gigi' => ['icon'=>'🦷','color'=>'#15803d','grad'=>'linear-gradient(135deg,#15803d,#10b981)','bg'=>'#f0fdf4','label'=>'Poli Gigi',
        'q_spesifik'=>'Apakah prosedur perawatan gigi dilakukan dengan hati-hati dan minim rasa sakit?'],
    'KIA'  => ['icon'=>'🤱','color'=>'#9d174d','grad'=>'linear-gradient(135deg,#9d174d,#ec4899)','bg'=>'#fdf2f8','label'=>'KIA / KB',
        'q_spesifik'=>'Apakah tenaga medis memberikan informasi kesehatan ibu & anak secara lengkap dan jelas?'],
];
$poli_data  = $is_kunjungan ? ($poli_info[$poli] ?? $poli_info['Umum']) : null;
$total_steps = $is_kunjungan ? 3 : 3;
// Note: kunjungan = 3 steps (identitas sudah otomatis → step 1 data diri, step 2 penilaian, step 3 poli+NPS+saran)
// umum = 3 steps (data diri, penilaian, NPS+saran)
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Survei Kepuasan — UNS Medical Center</title>
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
      --radius-sm: 12px;
      --shadow: 0 4px 24px rgba(37,99,235,.10);
      --shadow-card: 0 8px 32px rgba(15,23,42,.08);
    }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    html { scroll-behavior: smooth; }
    body {
      font-family: 'DM Sans', sans-serif;
      background: #f0f4ff;
      min-height: 100vh;
      overflow-x: hidden;
      color: var(--gray-800);
    }

    /* ── Background Mesh ── */
    .bg-mesh {
      position: fixed; inset: 0; z-index: 0; pointer-events: none;
      background:
        radial-gradient(ellipse 60% 50% at 10% 20%, rgba(99,143,247,.18) 0%, transparent 60%),
        radial-gradient(ellipse 40% 60% at 90% 80%, rgba(52,211,153,.12) 0%, transparent 60%),
        radial-gradient(ellipse 50% 40% at 50% 50%, rgba(241,245,255,1) 0%, transparent 100%);
    }

    /* ── Top Nav ── */
    .survey-nav {
      background: linear-gradient(135deg, #0f2057 0%, #1e3a8a 60%, #0369a1 100%);
      position: sticky; top: 0; z-index: 100;
      box-shadow: 0 4px 32px rgba(15,32,87,.4);
    }
    .nav-inner { max-width: 680px; margin: 0 auto; padding: 0 20px; height: 60px; display: flex; align-items: center; justify-content: space-between; gap: 16px; }
    .nav-brand { display: flex; align-items: center; gap: 10px; }
    .nav-brand img { height: 28px; object-fit: contain; opacity: .92; }
    .nav-title { font-family: 'Sora', sans-serif; font-weight: 700; font-size: .88rem; color: white; line-height: 1.3; }
    .nav-sub { font-size: .65rem; color: rgba(147,197,253,.85); font-weight: 500; }
    .nav-right { display: flex; align-items: center; gap: 12px; }

    /* Progress pill */
    .prog-pill {
      display: flex; align-items: center; gap: 8px;
      background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.2);
      border-radius: 999px; padding: 5px 12px;
    }
    .prog-pill-text { font-size: .7rem; font-weight: 700; color: rgba(255,255,255,.9); white-space: nowrap; }
    .prog-track { width: 48px; height: 4px; background: rgba(255,255,255,.2); border-radius: 99px; overflow: hidden; }
    .prog-fill { height: 100%; border-radius: 99px; background: linear-gradient(90deg, #7dd3fc, #34d399); transition: width .5s cubic-bezier(.4,0,.2,1); }

    .btn-exit {
      font-size: .7rem; font-weight: 700; color: rgba(255,255,255,.7);
      border: 1px solid rgba(255,255,255,.2); background: transparent;
      padding: 5px 12px; border-radius: 10px; cursor: pointer; transition: .2s;
    }
    .btn-exit:hover { background: rgba(255,255,255,.15); color: white; }

    /* ── Main Container ── */
    main { max-width: 680px; margin: 0 auto; padding: 24px 16px 80px; position: relative; z-index: 1; }

    /* ── User Identity Card (auto-filled) ── */
    .identity-card {
      background: linear-gradient(135deg, #1e3a8a, #1d4ed8);
      border-radius: var(--radius);
      padding: 16px 20px;
      display: flex; align-items: center; gap: 14px;
      box-shadow: 0 8px 24px rgba(30,58,138,.3);
      margin-bottom: 16px;
      position: relative; overflow: hidden;
    }
    .identity-card::before {
      content: ''; position: absolute; right: -20px; top: -20px;
      width: 120px; height: 120px; border-radius: 50%;
      background: rgba(255,255,255,.06);
    }
    .identity-card::after {
      content: ''; position: absolute; right: 30px; bottom: -30px;
      width: 80px; height: 80px; border-radius: 50%;
      background: rgba(255,255,255,.04);
    }
    .id-avatar {
      width: 46px; height: 46px; border-radius: 14px;
      background: rgba(255,255,255,.2); border: 2px solid rgba(255,255,255,.3);
      display: flex; align-items: center; justify-content: center;
      font-family: 'Sora', sans-serif; font-weight: 800; font-size: 1.2rem; color: white;
      flex-shrink: 0; position: relative; z-index: 1;
    }
    .id-info { flex: 1; position: relative; z-index: 1; }
    .id-name { font-family: 'Sora', sans-serif; font-weight: 700; font-size: 1rem; color: white; }
    .id-nik { font-size: .7rem; color: rgba(147,197,253,.85); font-weight: 500; margin-top: 2px; }
    .id-badge {
      background: rgba(52,211,153,.2); border: 1px solid rgba(52,211,153,.4);
      color: #6ee7b7; font-size: .62rem; font-weight: 700;
      padding: 3px 8px; border-radius: 6px; position: relative; z-index: 1;
    }

    /* ── Poli/Mode Banner ── */
    .mode-banner {
      border-radius: var(--radius);
      padding: 14px 18px;
      display: flex; align-items: center; gap: 14px;
      margin-bottom: 20px;
      border: 2px solid transparent;
    }
    .mode-icon { font-size: 2rem; flex-shrink: 0; }
    .mode-label { font-size: .7rem; font-weight: 700; opacity: .75; }
    .mode-title { font-family: 'Sora', sans-serif; font-weight: 800; font-size: 1rem; color: var(--gray-800); margin-top: 2px; }
    .mode-sub { font-size: .68rem; color: var(--gray-400); margin-top: 2px; }
    .mode-chip {
      margin-left: auto; font-size: .65rem; font-weight: 800; color: white;
      padding: 5px 10px; border-radius: 8px; white-space: nowrap;
    }

    /* ── Step Dots ── */
    .dots-row { display: flex; justify-content: center; align-items: center; gap: 8px; margin: 0 0 20px; }
    .dot {
      height: 8px; border-radius: 99px;
      background: var(--gray-200); transition: all .4s cubic-bezier(.4,0,.2,1);
    }
    .dot.current { width: 28px; background: var(--blue); }
    .dot.done { width: 8px; background: #22c55e; }
    .dot.idle { width: 8px; }

    /* ── Card ── */
    .card {
      background: white;
      border-radius: var(--radius);
      box-shadow: var(--shadow-card);
      border: 1px solid rgba(226,232,240,.6);
      margin-bottom: 12px;
      overflow: hidden;
    }
    .card-header {
      padding: 18px 22px 14px;
      border-bottom: 1px dashed var(--gray-200);
      display: flex; align-items: center; gap: 12px;
    }
    .card-step-num {
      width: 30px; height: 30px; border-radius: 10px;
      display: flex; align-items: center; justify-content: center;
      font-family: 'Sora', sans-serif; font-weight: 800; font-size: .8rem; color: white;
      flex-shrink: 0;
    }
    .card-title { font-family: 'Sora', sans-serif; font-weight: 700; font-size: .92rem; color: var(--gray-800); }
    .card-sub { font-size: .68rem; color: var(--gray-400); margin-top: 1px; }
    .card-body { padding: 20px 22px; }

    /* ── Step Wizard ── */
    .step-panel { display: none; }
    .step-panel.active { display: block; animation: slideIn .3s ease; }
    @keyframes slideIn { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }

    /* ── Form Elements ── */
    .field-label {
      font-size: .75rem; font-weight: 700; color: var(--gray-600);
      margin-bottom: 10px; display: flex; align-items: center; gap: 6px;
    }
    .req { color: #f43f5e; }

    /* Info field (auto-filled, read-only appearance) */
    .info-row {
      display: flex; gap: 10px; margin-bottom: 14px; flex-wrap: wrap;
    }
    .info-field {
      flex: 1; min-width: 140px;
      background: var(--gray-50);
      border: 1.5px solid var(--gray-200);
      border-radius: var(--radius-sm);
      padding: 10px 14px;
      position: relative;
    }
    .info-field-label { font-size: .6rem; font-weight: 700; color: var(--gray-400); text-transform: uppercase; letter-spacing: .05em; }
    .info-field-value { font-size: .9rem; font-weight: 700; color: var(--gray-800); margin-top: 2px; }
    .info-field-lock { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); font-size: .75rem; }

    /* Kategori Grid */
    .kat-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
    .kat-opt input { display: none; }
    .kat-box {
      display: flex; align-items: center; gap: 10px;
      padding: 12px 14px; border-radius: 14px;
      border: 2px solid var(--gray-200);
      background: var(--gray-50); cursor: pointer;
      transition: all .2s ease;
    }
    .kat-box:hover { border-color: #93c5fd; background: #eff6ff; }
    .kat-opt input:checked ~ .kat-box {
      border-color: var(--blue); background: #eff6ff;
      box-shadow: 0 4px 14px rgba(37,99,235,.15);
    }
    .kat-icon { font-size: 1.2rem; }
    .kat-text { font-size: .8rem; font-weight: 700; color: var(--gray-800); }
    .kat-sub { font-size: .65rem; color: var(--gray-400); margin-top: 1px; }

    /* Select */
    .field-select {
      width: 100%;
      background: var(--gray-50);
      border: 1.5px solid var(--gray-200);
      border-radius: var(--radius-sm);
      padding: 11px 14px;
      font-size: .88rem; font-family: 'DM Sans', sans-serif;
      color: var(--gray-800); outline: none; cursor: pointer;
      transition: border-color .2s;
      appearance: none;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath d='M2 4l4 4 4-4' stroke='%2394a3b8' stroke-width='1.5' fill='none' stroke-linecap='round'/%3E%3C/svg%3E");
      background-repeat: no-repeat; background-position: right 12px center;
      padding-right: 36px;
    }
    .field-select:focus { border-color: var(--blue); box-shadow: 0 0 0 3px rgba(37,99,235,.08); }

    /* Chk pill (tujuan) */
    .chk-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 7px; }
    .chk-pill { display: flex; align-items: center; gap: 8px; padding: 9px 12px; border-radius: 12px; border: 2px solid var(--gray-200); background: var(--gray-50); cursor: pointer; transition: all .18s; font-size: .78rem; font-weight: 600; color: var(--gray-800); }
    .chk-pill:hover { border-color: #93c5fd; background: #eff6ff; }
    .chk-pill input { display: none; }
    .chk-pill:has(input:checked) { border-color: var(--blue); background: #eff6ff; color: var(--blue); }

    /* ── Emoji Rating ── */
    .q-block { margin-bottom: 24px; padding-bottom: 24px; border-bottom: 1px dashed var(--gray-200); }
    .q-block:last-child { margin-bottom: 0; padding-bottom: 0; border-bottom: none; }
    .q-row { display: flex; justify-content: space-between; align-items: flex-start; gap: 8px; margin-bottom: 4px; }
    .q-title { font-size: .88rem; font-weight: 700; color: var(--gray-800); line-height: 1.4; }
    .q-desc { font-size: .7rem; color: var(--gray-400); margin-bottom: 12px; line-height: 1.5; }
    .q-check { width: 22px; height: 22px; border-radius: 7px; border: 2px solid var(--gray-200); display: flex; align-items: center; justify-content: center; font-size: .6rem; font-weight: 800; transition: all .25s; flex-shrink: 0; margin-top: 2px; }
    .q-check.done { background: #22c55e; border-color: #16a34a; color: white; }

    .emoji-row { display: flex; gap: 6px; }
    .emoji-opt { flex: 1; }
    .emoji-opt input { display: none; }
    .emoji-box {
      border-radius: 14px; border: 2px solid var(--gray-200);
      background: var(--gray-50); padding: 8px 4px;
      cursor: pointer; text-align: center;
      display: flex; flex-direction: column; align-items: center; gap: 4px;
      transition: all .2s ease;
    }
    .emoji-box:hover { border-color: #93c5fd; background: #eff6ff; transform: translateY(-2px); }
    .emoji-opt input:checked ~ .emoji-box {
      border-color: var(--blue); background: #eff6ff;
      transform: translateY(-4px) scale(1.07);
      box-shadow: 0 8px 20px rgba(37,99,235,.2);
    }
    .e-icon { font-size: 1.45rem; line-height: 1; }
    .e-lbl { font-size: .58rem; font-weight: 700; color: var(--gray-400); line-height: 1.3; text-align: center; }
    .emoji-opt input:checked ~ .emoji-box .e-lbl { color: var(--blue); }

    /* ── NPS ── */
    .nps-grid { display: flex; gap: 5px; flex-wrap: wrap; }
    .nps-opt input { display: none; }
    .nps-box {
      width: 40px; height: 40px; border-radius: 10px;
      border: 2px solid var(--gray-200); background: var(--gray-50);
      display: flex; align-items: center; justify-content: center;
      font-size: .82rem; font-weight: 800; color: var(--gray-400);
      cursor: pointer; transition: all .18s;
    }
    .nps-box:hover { transform: translateY(-3px); border-color: #93c5fd; }
    .nps-opt input:checked ~ .nps-box { color: white; transform: translateY(-4px) scale(1.1); }
    <?php for ($i=0;$i<=5;$i++) echo ".nps-$i input:checked ~ .nps-box{background:#ef4444;border-color:#dc2626;box-shadow:0 6px 18px rgba(239,68,68,.3);}"; ?>
    .nps-6 input:checked ~ .nps-box,.nps-7 input:checked ~ .nps-box { background:#f59e0b;border-color:#d97706;box-shadow:0 6px 18px rgba(245,158,11,.3); }
    .nps-8 input:checked ~ .nps-box,.nps-9 input:checked ~ .nps-box,.nps-10 input:checked ~ .nps-box { background:#22c55e;border-color:#16a34a;box-shadow:0 6px 18px rgba(34,197,94,.3); }

    .nps-labels { display: flex; justify-content: space-between; margin-top: 8px; }
    .nps-label-txt { font-size: .65rem; font-weight: 600; color: var(--gray-400); }
    .nps-desc {
      text-align: center; font-size: .82rem; font-weight: 700;
      color: var(--gray-400); margin-top: 10px; min-height: 22px;
      transition: color .2s;
    }

    /* ── Yn buttons ── */
    .yn-row { display: flex; gap: 8px; }
    .yn-opt input { display: none; }
    .yn-box {
      flex: 1; padding: 12px 8px; border-radius: 14px;
      border: 2px solid var(--gray-200); background: var(--gray-50);
      text-align: center; cursor: pointer;
      font-weight: 700; font-size: .82rem;
      display: flex; flex-direction: column; align-items: center; gap: 4px;
      transition: all .2s;
    }
    .yn-box:hover { transform: translateY(-2px); }
    .yn-yes input:checked ~ .yn-box { background: #22c55e; color: white; border-color: #16a34a; box-shadow: 0 6px 18px rgba(34,197,94,.3); }
    .yn-maybe input:checked ~ .yn-box { background: #f59e0b; color: white; border-color: #d97706; box-shadow: 0 6px 18px rgba(245,158,11,.3); }
    .yn-no input:checked ~ .yn-box { background: #ef4444; color: white; border-color: #dc2626; box-shadow: 0 6px 18px rgba(239,68,68,.3); }

    /* ── Textarea ── */
    .field-textarea {
      width: 100%; background: var(--gray-50);
      border: 1.5px solid var(--gray-200); border-radius: var(--radius-sm);
      padding: 12px 14px; font-size: .88rem; font-family: 'DM Sans', sans-serif;
      color: var(--gray-800); outline: none; resize: vertical;
      transition: border-color .2s; line-height: 1.6;
    }
    .field-textarea:focus { border-color: var(--blue); box-shadow: 0 0 0 3px rgba(37,99,235,.08); }
    .textarea-footer { display: flex; justify-content: space-between; margin-top: 6px; }
    .textarea-hint { font-size: .68rem; color: var(--gray-400); }
    .char-count { font-size: .68rem; color: var(--gray-400); font-weight: 600; }

    /* ── Navigation Buttons ── */
    .step-nav { display: flex; justify-content: space-between; align-items: center; margin-top: 24px; gap: 10px; }
    .btn-back {
      display: flex; align-items: center; gap: 6px;
      padding: 10px 18px; border-radius: 14px;
      border: 1.5px solid var(--gray-200); background: white;
      font-size: .82rem; font-weight: 700; color: var(--gray-600);
      cursor: pointer; transition: all .2s;
    }
    .btn-back:hover { background: var(--gray-50); border-color: var(--gray-400); }
    .btn-next {
      display: flex; align-items: center; gap: 6px;
      padding: 11px 24px; border-radius: 14px; border: none;
      font-size: .85rem; font-weight: 800; color: white;
      cursor: pointer; transition: all .2s; font-family: 'DM Sans', sans-serif;
    }
    .btn-next:hover:not(:disabled) { transform: translateY(-2px); filter: brightness(1.05); }
    .btn-next:disabled { opacity: .38; cursor: not-allowed; }
    .btn-next:active:not(:disabled) { transform: scale(.98); }

    .btn-blue { background: linear-gradient(135deg, #3b82f6, #1d4ed8); box-shadow: 0 4px 16px rgba(37,99,235,.35); }
    .btn-cyan { background: linear-gradient(135deg, #06b6d4, #2563eb); box-shadow: 0 4px 16px rgba(6,182,212,.3); }
    .btn-green { background: linear-gradient(135deg, #22c55e, #15803d); box-shadow: 0 4px 16px rgba(34,197,94,.3); }

    /* ── Success state for q-check ── */
    @keyframes popIn { 0% { transform: scale(0); } 60% { transform: scale(1.2); } 100% { transform: scale(1); } }
    .q-check.done { animation: popIn .25s ease; }

    /* ── Divider ── */
    .section-divider { display: flex; align-items: center; gap: 10px; margin: 20px 0; }
    .section-divider::before,.section-divider::after { content:''; flex:1; height:1px; background: var(--gray-200); }
    .section-divider span { font-size: .68rem; font-weight: 700; color: var(--gray-400); white-space: nowrap; }

    /* ── Scrollbar ── */
    ::-webkit-scrollbar { width: 4px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: var(--gray-200); border-radius: 99px; }
  </style>
</head>
<body>
  <div class="bg-mesh"></div>

  <!-- ── TOP NAV ── -->
  <nav class="survey-nav">
    <div class="nav-inner">
      <div class="nav-brand">
        <img src="https://senirupa.fkip.uns.ac.id/wp-content/uploads/2021/07/logo_putih.png" alt="UNS"/>
        <div>
          <div class="nav-title">UNS Medical Center</div>
          <div class="nav-sub">
            <?php echo $is_kunjungan ? "Survei Pasien · {$poli_data['label']}" : "Survei Pengunjung Umum"; ?>
          </div>
        </div>
      </div>
      <div class="nav-right">
        <div class="prog-pill" id="prog-pill">
          <span class="prog-pill-text" id="nav-label">Langkah 1/<?php echo $total_steps; ?></span>
          <div class="prog-track"><div class="prog-fill" id="nav-prog" style="width:<?php echo round(1/$total_steps*100); ?>%"></div></div>
        </div>
        <button class="btn-exit" onclick="confirmExit()">✕ Keluar</button>
      </div>
    </div>
  </nav>

  <main>

    <!-- ── USER IDENTITY CARD (auto-filled) ── -->
    <div class="identity-card">
      <div class="id-avatar"><?php echo $inisial; ?></div>
      <div class="id-info">
        <div class="id-name"><?php echo htmlspecialchars($nama_user); ?></div>
        <div class="id-nik">NIK: <?php echo substr($nik_user,0,6).'**********'; ?></div>
      </div>
      <div class="id-badge">✓ Terautentikasi</div>
    </div>

    <!-- ── MODE BANNER ── -->
    <?php if ($is_kunjungan): ?>
    <div class="mode-banner" style="background:<?php echo $poli_data['bg']; ?>;border-color:<?php echo $poli_data['color']; ?>33;">
      <div class="mode-icon"><?php echo $poli_data['icon']; ?></div>
      <div>
        <div class="mode-label" style="color:<?php echo $poli_data['color']; ?>">Survei Pasien Berobat</div>
        <div class="mode-title"><?php echo $poli_data['label']; ?></div>
        <div class="mode-sub">Poli terdeteksi otomatis dari data kunjungan Anda</div>
      </div>
      <div class="mode-chip" style="background:<?php echo $poli_data['color']; ?>">✓ Terverifikasi</div>
    </div>
    <?php else: ?>
    <div class="mode-banner" style="background:linear-gradient(135deg,#fffbeb,#fff7ed);border-color:#fed7aa;">
      <div class="mode-icon">🚶</div>
      <div>
        <div class="mode-label" style="color:#d97706;">Survei Pengunjung Umum</div>
        <div class="mode-title">Pengalaman Berkunjung</div>
        <div class="mode-sub">Untuk pengunjung yang datang tanpa berobat</div>
      </div>
      <div class="mode-chip" style="background:#f59e0b;">📝 Umum</div>
    </div>
    <?php endif; ?>

    <!-- ── STEP DOTS ── -->
    <div class="dots-row">
      <?php for ($i=1; $i<=$total_steps; $i++): ?>
      <div class="dot <?php echo $i===1?'current':'idle'; ?>" id="dot-<?php echo $i; ?>"></div>
      <?php endfor; ?>
    </div>

    <form id="survey-form" action="proses/prosesSurvei.php" method="POST">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
      <input type="hidden" name="jalur" value="<?php echo $jalur; ?>">
      <input type="hidden" name="poli" value="<?php echo htmlspecialchars($poli ?? 'Umum'); ?>">

      <!-- ═══════════════════════════════════════
           STEP 1 — Profil Pengunjung (data diri otomatis + kategori & frekuensi)
      ═══════════════════════════════════════ -->
      <div class="step-panel active card" id="step-1">
        <div class="card-header">
          <div class="card-step-num" style="background:linear-gradient(135deg,#3b82f6,#1d4ed8)">1</div>
          <div>
            <div class="card-title">Profil Pengunjung</div>
            <div class="card-sub">Data Anda sudah terisi otomatis — lengkapi sisa informasi di bawah</div>
          </div>
        </div>
        <div class="card-body">

          <?php
          // Ambil kategori dari session (sudah diset saat login)
          $kategori_user = $_SESSION['kategori'] ?? 'Umum';
          $kat_icons = ['Mahasiswa'=>'🎓','Dosen'=>'👨‍🏫','Karyawan'=>'💼','Umum'=>'👤'];
          $kat_icon  = $kat_icons[$kategori_user] ?? '👤';
          // Kirim sebagai hidden field — tidak perlu dipilih lagi
          ?>
          <input type="hidden" name="kategori" value="<?php echo htmlspecialchars($kategori_user); ?>">

          <!-- Data diri otomatis — display only, tidak perlu diisi ulang -->
          <div class="field-label">👤 Data Diri <span style="color:#22c55e;font-size:.65rem;font-weight:700;">✓ Terisi Otomatis dari Akun</span></div>
          <div class="info-row">
            <div class="info-field">
              <div class="info-field-label">Nama Lengkap</div>
              <div class="info-field-value"><?php echo htmlspecialchars($nama_user); ?></div>
              <div class="info-field-lock">🔒</div>
            </div>
            <div class="info-field">
              <div class="info-field-label">NIK</div>
              <div class="info-field-value" style="font-family:monospace;letter-spacing:.05em;"><?php echo substr($nik_user,0,6).'**********'; ?></div>
              <div class="info-field-lock">🔒</div>
            </div>
          </div>

          <!-- Kategori otomatis -->
          <div style="margin:14px 0;padding:12px 14px;border-radius:14px;
            background:#f0fdf4;border:1.5px solid #bbf7d0;display:flex;align-items:center;gap:10px;">
            <span style="font-size:1.4rem;"><?php echo $kat_icon; ?></span>
            <div>
              <div style="font-size:.68rem;font-weight:700;color:#15803d;">Kategori Terdeteksi Otomatis</div>
              <div style="font-size:.92rem;font-weight:800;color:#14532d;"><?php echo htmlspecialchars($kategori_user); ?></div>
            </div>
            <div style="margin-left:auto;font-size:.68rem;font-weight:700;
              background:#22c55e;color:white;padding:4px 10px;border-radius:99px;">✓ Dari Profil</div>
          </div>

          <div class="section-divider"><span>Lengkapi Data Berikut</span></div>

          <!-- Tujuan kunjungan (umum only) -->
          <?php if (!$is_kunjungan): ?>
          <div style="margin-bottom:18px;">
            <div class="field-label">Tujuan kunjungan Anda ke klinik <span class="req">*</span></div>
            <div class="chk-grid">
              <?php foreach ([
                ['Ambil obat / resep','💊'],
                ['Mengantar keluarga / teman','👨‍👩‍👧'],
                ['Mencari informasi layanan','ℹ️'],
                ['Urusan administrasi','📋'],
                ['Lainnya','✨'],
              ] as [$v,$ic]): ?>
              <label class="chk-pill">
                <input type="radio" name="tujuan_kunjungan" value="<?php echo $v; ?>" required onchange="updateNav()">
                <span><?php echo "$ic $v"; ?></span>
              </label>
              <?php endforeach; ?>
            </div>
          </div>
          <?php endif; ?>

          <!-- Frekuensi -->
          <div>
            <div class="field-label">Seberapa sering Anda mengunjungi klinik ini? <span class="req">*</span></div>
            <select name="frekuensi" class="field-select" required onchange="updateNav()">
              <option value="" disabled selected>— Pilih frekuensi kunjungan —</option>
              <option value="Pertama kali">🌟 Pertama kali berkunjung</option>
              <option value="2-3 kali">🔄 2–3 kali kunjungan</option>
              <option value="4-6 kali">🔁 4–6 kali kunjungan</option>
              <option value="Lebih dari 6 kali">⭐ Lebih dari 6 kali — pelanggan setia!</option>
            </select>
          </div>

          <div class="step-nav">
            <div></div>
            <button type="button" onclick="goStep(2)" id="btn-1" class="btn-next btn-blue" disabled>
              Lanjut <span>→</span>
            </button>
          </div>
        </div>
      </div>

      <!-- ═══════════════════════════════════════
           STEP 2 — Penilaian Layanan
      ═══════════════════════════════════════ -->
      <div class="step-panel card" id="step-2">
        <div class="card-header">
          <div class="card-step-num" style="background:linear-gradient(135deg,#06b6d4,#2563eb)">2</div>
          <div>
            <div class="card-title"><?php echo $is_kunjungan ? 'Penilaian Layanan Medis' : 'Penilaian Pengalaman Kunjungan'; ?></div>
            <div class="card-sub"><?php echo $is_kunjungan ? 'Evaluasi layanan yang Anda terima sebagai pasien' : 'Evaluasi pengalaman saat mengunjungi klinik'; ?></div>
          </div>
        </div>
        <div class="card-body">
          <?php
          $emoji_opts = [['1','😡','Sangat Buruk'],['2','😕','Buruk'],['3','😐','Cukup'],['4','😊','Baik'],['5','🤩','Luar Biasa']];
          if ($is_kunjungan) {
            $q_list = [
              ['q1','😊','Keramahan & Kesopanan Petugas','Bagaimana sikap dokter, perawat, dan staf dalam melayani Anda?'],
              ['q2','⚡','Kecepatan & Ketepatan Pelayanan','Seberapa cepat Anda dipanggil dan ditangani oleh tenaga medis?'],
              ['q3','✨','Kebersihan Ruangan & Fasilitas','Bagaimana kondisi kebersihan ruang periksa dan fasilitas klinik?'],
              ['q4','💊','Kejelasan Informasi Medis & Obat','Apakah dokter menjelaskan kondisi, obat, dan anjuran dengan jelas?'],
            ];
          } else {
            $q_list = [
              ['q1','😊','Keramahan Petugas Resepsionis','Bagaimana sikap dan keramahan petugas di loket/resepsionis saat Anda datang?'],
              ['q2','🧹','Kebersihan & Kerapian Gedung','Bagaimana kondisi kebersihan area lobby, koridor, toilet, dan ruang tunggu?'],
              ['q3','🅿️','Kemudahan Akses & Area Parkir','Apakah mudah menemukan lokasi klinik dan mendapatkan tempat parkir?'],
              ['q4','ℹ️','Ketersediaan & Kejelasan Informasi','Apakah papan petunjuk, alur pelayanan, dan informasi di klinik mudah dipahami?'],
            ];
          }
          foreach ($q_list as [$name,$icon,$title,$desc]):
          ?>
          <div class="q-block" id="qb-<?php echo $name; ?>">
            <div class="q-row">
              <div class="q-title"><?php echo "$icon $title"; ?></div>
              <div class="q-check" id="qcheck-<?php echo $name; ?>">✓</div>
            </div>
            <div class="q-desc"><?php echo $desc; ?></div>
            <div class="emoji-row">
              <?php foreach ($emoji_opts as [$val,$em,$lb]): ?>
              <label class="emoji-opt">
                <input type="radio" name="<?php echo $name; ?>" value="<?php echo $val; ?>"
                  required onchange="markQ('<?php echo $name; ?>'); updateNav()">
                <div class="emoji-box">
                  <span class="e-icon"><?php echo $em; ?></span>
                  <span class="e-lbl"><?php echo $lb; ?></span>
                </div>
              </label>
              <?php endforeach; ?>
            </div>
          </div>
          <?php endforeach; ?>

          <div class="step-nav">
            <button type="button" onclick="goStep(1)" class="btn-back">← Kembali</button>
            <button type="button" onclick="goStep(3)" id="btn-2" class="btn-next btn-cyan" disabled>
              Lanjut →
            </button>
          </div>
        </div>
      </div>

      <!-- ═══════════════════════════════════════
           STEP 3 — Poli Spesifik (kunjungan) + NPS + Saran
      ═══════════════════════════════════════ -->
      <div class="step-panel card" id="step-3">
        <div class="card-header">
          <div class="card-step-num" style="background:linear-gradient(135deg,#22c55e,#15803d)">3</div>
          <div>
            <div class="card-title"><?php echo $is_kunjungan ? "Detail Poli & Rekomendasi" : "Rekomendasi & Pesan Akhir"; ?></div>
            <div class="card-sub">Langkah terakhir — hampir selesai! 🎉</div>
          </div>
        </div>
        <div class="card-body">

          <!-- Q5 Poli Spesifik (kunjungan only) -->
          <?php if ($is_kunjungan): ?>
          <div class="q-block" id="qb-q5">
            <div class="q-row">
              <div class="q-title"><?php echo $poli_data['icon'].' '.$poli_data['q_spesifik']; ?></div>
              <div class="q-check" id="qcheck-q5">✓</div>
            </div>
            <div class="emoji-row" style="margin-top:12px;">
              <?php foreach ($emoji_opts as [$val,$em,$lb]): ?>
              <label class="emoji-opt">
                <input type="radio" name="q5" value="<?php echo $val; ?>"
                  required onchange="markQ('q5'); updateNav()">
                <div class="emoji-box">
                  <span class="e-icon"><?php echo $em; ?></span>
                  <span class="e-lbl"><?php echo $lb; ?></span>
                </div>
              </label>
              <?php endforeach; ?>
            </div>
          </div>

          <!-- Akan kembali -->
          <div class="q-block">
            <div class="q-title" style="margin-bottom:12px;">🔄 Apakah Anda akan kembali menggunakan layanan <?php echo $poli_data['label']; ?>?</div>
            <div class="yn-row">
              <?php foreach ([['Ya','✅','Ya, pasti!','yn-yes'],['Mungkin','🤔','Mungkin saja','yn-maybe'],['Tidak','❌','Belum tentu','yn-no']] as [$v,$em,$lb,$cls]): ?>
              <label class="<?php echo $cls; ?>" style="flex:1;cursor:pointer;">
                <input type="radio" name="akan_kembali" value="<?php echo $v; ?>" required onchange="updateNav()">
                <div class="yn-box">
                  <span style="font-size:1.2rem;"><?php echo $em; ?></span>
                  <span><?php echo $lb; ?></span>
                </div>
              </label>
              <?php endforeach; ?>
            </div>
          </div>

          <!-- Waktu tunggu -->
          <div style="margin-bottom:24px;">
            <div class="field-label">⏱️ Berapa lama waktu tunggu Anda? <span class="req">*</span></div>
            <select name="waktu_tunggu" class="field-select" required onchange="updateNav()">
              <option value="" disabled selected>— Pilih estimasi waktu —</option>
              <option value="< 15 menit">⚡ Kurang dari 15 menit</option>
              <option value="15-30 menit">🕐 15–30 menit</option>
              <option value="30-60 menit">🕑 30–60 menit</option>
              <option value="> 60 menit">🕒 Lebih dari 1 jam</option>
            </select>
          </div>

          <div class="section-divider"><span>Penilaian Akhir</span></div>
          <?php else: ?>
          <!-- Overall (umum only) -->
          <div class="q-block" id="qb-q5">
            <div class="q-row">
              <div class="q-title">🌟 Secara keseluruhan, bagaimana pengalaman kunjungan Anda?</div>
              <div class="q-check" id="qcheck-q5">✓</div>
            </div>
            <div class="emoji-row" style="margin-top:12px;">
              <?php foreach ($emoji_opts as [$val,$em,$lb]): ?>
              <label class="emoji-opt">
                <input type="radio" name="q5" value="<?php echo $val; ?>" onchange="markQ('q5'); updateNav()">
                <div class="emoji-box">
                  <span class="e-icon"><?php echo $em; ?></span>
                  <span class="e-lbl"><?php echo $lb; ?></span>
                </div>
              </label>
              <?php endforeach; ?>
            </div>
          </div>
          <?php endif; ?>

          <!-- NPS -->
          <div style="margin-bottom:24px;">
            <div class="q-title" style="margin-bottom:6px;">📣 Seberapa besar kemungkinan Anda merekomendasikan UNS Medical Center kepada orang lain?</div>
            <div class="q-desc" style="margin-bottom:12px;">0 = Sangat Tidak Mungkin &nbsp;·&nbsp; 10 = Sangat Mungkin</div>
            <div class="nps-grid">
              <?php for ($i=0;$i<=10;$i++): ?>
              <label class="nps-opt nps-<?php echo $i; ?>">
                <input type="radio" name="nps_score" value="<?php echo $i; ?>"
                  required onchange="onNpsChange(<?php echo $i; ?>); updateNav()">
                <div class="nps-box"><?php echo $i; ?></div>
              </label>
              <?php endfor; ?>
            </div>
            <div class="nps-labels">
              <span class="nps-label-txt" style="color:#dc2626;">😡 Tidak mungkin</span>
              <span class="nps-label-txt" style="color:#16a34a;">Pasti merekomendasikan 🎉</span>
            </div>
            <div class="nps-desc" id="nps-desc">Pilih angka di atas</div>
          </div>

          <!-- Saran -->
          <div>
            <div class="field-label">
              💬 Ada pesan atau saran untuk kami?
              <span style="font-weight:400;color:var(--gray-400);">(opsional)</span>
            </div>
            <textarea name="saran" class="field-textarea" rows="4" maxlength="500"
              placeholder="<?php echo $is_kunjungan
                ? 'Ceritakan pengalaman berobat Anda — apa yang sudah baik dan apa yang perlu ditingkatkan?'
                : 'Apa kesan Anda saat mengunjungi klinik kami? Ada saran untuk kenyamanan pengunjung?'; ?>"
              oninput="document.getElementById('char-count').textContent=this.value.length"></textarea>
            <div class="textarea-footer">
              <span class="textarea-hint">Semakin detail, semakin bermanfaat untuk kami 🙏</span>
              <span class="char-count"><span id="char-count">0</span>/500</span>
            </div>
          </div>

          <div class="step-nav">
            <button type="button" onclick="goStep(2)" class="btn-back">← Kembali</button>
            <button type="button" id="btn-final" onclick="confirmSubmit()"
              class="btn-next btn-green" disabled>
              🚀 Kirim Survei
            </button>
          </div>
        </div>
      </div>

      <button type="submit" id="real-submit" style="display:none;"></button>
    </form>
  </main>

<script>
const STEPS = <?php echo $total_steps; ?>;
const IS_KUNJUNGAN = <?php echo $is_kunjungan ? 'true' : 'false'; ?>;
let cur = 1;

function goStep(n) {
  document.getElementById('step-'+cur)?.classList.remove('active');
  document.getElementById('step-'+n)?.classList.add('active');
  cur = n;
  updateDots(); updateBar();
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

function updateDots() {
  for (let i = 1; i <= STEPS; i++) {
    const d = document.getElementById('dot-'+i);
    if (!d) continue;
    d.className = 'dot';
    if (i < cur) d.classList.add('done');
    else if (i === cur) d.classList.add('current');
    else d.classList.add('idle');
  }
}

function updateBar() {
  const pct = Math.round(cur / STEPS * 100);
  document.getElementById('nav-prog').style.width = pct + '%';
  document.getElementById('nav-label').textContent = `Langkah ${cur}/${STEPS}`;
}

function updateNav() {
  // Step 1 — kategori sudah otomatis dari session, cukup cek frekuensi & tujuan
  const frq = !!document.querySelector('[name=frekuensi]')?.value;
  const tuj = IS_KUNJUNGAN ? true : !!document.querySelector('[name=tujuan_kunjungan]:checked');
  setBtn('btn-1', frq && tuj);

  // Step 2
  const q1 = !!document.querySelector('[name=q1]:checked');
  const q2 = !!document.querySelector('[name=q2]:checked');
  const q3 = !!document.querySelector('[name=q3]:checked');
  const q4 = !!document.querySelector('[name=q4]:checked');
  setBtn('btn-2', q1 && q2 && q3 && q4);

  // Step 3 final — NPS required
  const nps = !!document.querySelector('[name=nps_score]:checked');
  if (IS_KUNJUNGAN) {
    const q5 = !!document.querySelector('[name=q5]:checked');
    const ak  = !!document.querySelector('[name=akan_kembali]:checked');
    const wkt = !!document.querySelector('[name=waktu_tunggu]')?.value;
    setBtn('btn-final', nps && q5 && ak && wkt);
  } else {
    setBtn('btn-final', nps);
  }
}

function setBtn(id, ok) {
  const b = document.getElementById(id);
  if (b) b.disabled = !ok;
}

function markQ(name) {
  const el = document.getElementById('qcheck-'+name);
  if (el) el.classList.add('done');
}

const NPS_LBL = {
  0:'😡 Sangat tidak mungkin merekomendasikan',
  1:'😡 Hampir tidak mungkin',
  2:'😠 Sangat kecil kemungkinannya',
  3:'🙁 Kecil kemungkinannya',
  4:'😕 Agak tidak mungkin',
  5:'😐 Netral, tergantung situasi',
  6:'🙂 Cukup mungkin',
  7:'😊 Kemungkinan besar merekomendasikan',
  8:'😄 Sangat mungkin!',
  9:'🤩 Hampir pasti merekomendasikan!',
  10:'🎉 Pasti merekomendasikan!'
};
const NPS_COLOR = {
  0:'#dc2626',1:'#dc2626',2:'#ef4444',3:'#f97316',4:'#f97316',
  5:'#6b7280',6:'#3b82f6',7:'#2563eb',8:'#16a34a',9:'#15803d',10:'#14532d'
};
function onNpsChange(v) {
  const el = document.getElementById('nps-desc');
  el.textContent = NPS_LBL[v] || '';
  el.style.color = NPS_COLOR[v] || '#6b7280';
  el.style.fontWeight = '800';
}

function confirmSubmit() {
  Swal.fire({
    title: 'Kirim Survei Sekarang?',
    html: `<p style="font-size:.88rem;color:#475569;line-height:1.7;margin-top:4px;">
      Pastikan semua jawaban sudah sesuai.<br>
      <strong style="color:#1e293b;">Survei tidak dapat diubah setelah dikirim.</strong>
    </p>`,
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: '✅ Ya, Kirim!',
    cancelButtonText: '← Periksa Lagi',
    confirmButtonColor: '#22c55e',
    cancelButtonColor: '#6b7280',
    customClass: { popup: 'swal-popup-round' }
  }).then(r => { if (r.isConfirmed) document.getElementById('real-submit').click(); });
}

function confirmExit() {
  Swal.fire({
    title: 'Keluar dari survei?',
    text: 'Jawaban yang sudah diisi akan hilang.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Ya, Keluar',
    cancelButtonText: 'Lanjut Isi',
    confirmButtonColor: '#ef4444',
    cancelButtonColor: '#2563eb',
  }).then(r => { if (r.isConfirmed) window.location.href = 'dashboard.php'; });
}

updateNav(); updateBar();
</script>
</body>
</html>