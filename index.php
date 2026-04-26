<?php
session_start();
include 'server/koneksi.php';

$is_logged_in = isset($_SESSION['nik']);

$query  = "SELECT kategori, q1, q2, q3, q4 FROM surveys";
$result = mysqli_query($koneksi, $query);
$total_resp = 0;
$sum_q = [0,0,0,0];
$kat_count = [];

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $sum_q[0]+=$row['q1']; $sum_q[1]+=$row['q2'];
        $sum_q[2]+=$row['q3']; $sum_q[3]+=$row['q4'];
        $k = $row['kategori'];
        $kat_count[$k] = ($kat_count[$k]??0)+1;
        $total_resp++;
    }
}
$avg_global = $total_resp>0 ? number_format(array_sum($sum_q)/($total_resp*4),1) : '0.0';
$avg_q = $total_resp>0 ? array_map(fn($s)=>number_format($s/$total_resp,1),$sum_q) : ['0','0','0','0'];

// Flash message (dari prosesSurvei redirect)
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>UNS Medical Center — Survei Kepuasan Pasien</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet"/>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  
  <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>

  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>UNS Medical Center — Survei Kepuasan Pasien</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet"/>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    *{font-family:'Plus Jakarta Sans',sans-serif;box-sizing:border-box;margin:0;padding:0;}
    body{background:linear-gradient(135deg,#dbeafe 0%,#e0f2fe 45%,#f0fdf4 100%);min-height:100vh;overflow-x:hidden;}

    /* ── Blobs ── */
    .blob{position:fixed;border-radius:50%;filter:blur(80px);pointer-events:none;z-index:0;}
    .blob-1{width:500px;height:500px;background:radial-gradient(circle,#bfdbfe,#93c5fd);
      opacity:.35;top:-120px;left:-120px;animation:b1 12s ease-in-out infinite alternate;}
    .blob-2{width:400px;height:400px;background:radial-gradient(circle,#a5f3fc,#67e8f9);
      opacity:.25;bottom:-80px;right:-80px;animation:b2 10s ease-in-out infinite alternate;}
    .blob-3{width:300px;height:300px;background:radial-gradient(circle,#bbf7d0,#86efac);
      opacity:.2;top:50%;right:10%;animation:b3 14s ease-in-out infinite alternate;}
    @keyframes b1{to{transform:translate(30px,20px) scale(1.1);}}
    @keyframes b2{to{transform:translate(-20px,30px) scale(1.08);}}
    @keyframes b3{to{transform:translate(15px,-25px) scale(1.12);}}

    /* ── Clay Cards ── */
    .clay-card{background:#fff;border-radius:28px;
      box-shadow:8px 8px 24px rgba(99,149,210,.2),-2px -2px 8px rgba(255,255,255,.9),
        inset 3px 3px 8px rgba(255,255,255,.85),inset -3px -3px 8px rgba(180,210,245,.3);
      border:1.5px solid rgba(255,255,255,.8);}
    .clay-card-hover{transition:transform .25s ease,box-shadow .25s ease;}
    .clay-card-hover:hover{transform:translateY(-5px);
      box-shadow:12px 16px 32px rgba(99,149,210,.28),-2px -2px 10px rgba(255,255,255,.95),
        inset 3px 3px 8px rgba(255,255,255,.85),inset -3px -3px 8px rgba(180,210,245,.3);}

    /* ── Buttons ── */
    .clay-btn{border-radius:16px;font-weight:700;border:none;cursor:pointer;
      box-shadow:5px 5px 14px rgba(59,130,246,.35),-1px -1px 5px rgba(255,255,255,.7),
        inset 2px 2px 5px rgba(255,255,255,.4),inset -2px -2px 5px rgba(37,99,235,.25);
      transition:all .2s ease;}
    .clay-btn:hover{transform:translateY(-3px) scale(1.02);}
    .clay-btn:active{transform:translateY(0) scale(.98);}

    /* ── Nav ── */
    .clay-nav{background:rgba(255,255,255,.9);backdrop-filter:blur(20px);
      border-bottom:1.5px solid rgba(255,255,255,.6);box-shadow:0 4px 24px rgba(99,149,210,.12);}

    /* ── Hero ── */
    .hero-bg{background:linear-gradient(135deg,#0f2057 0%,#1e3a8a 40%,#1e40af 70%,#0369a1 100%);
      border-radius:28px;overflow:hidden;position:relative;
      box-shadow:8px 8px 32px rgba(15,32,87,.4),inset 3px 3px 12px rgba(255,255,255,.08);}
    .hero-bg::before{content:'';position:absolute;inset:0;
      background:radial-gradient(ellipse at 70% 30%,rgba(255,255,255,.07) 0%,transparent 60%),
        radial-gradient(ellipse at 20% 80%,rgba(56,189,248,.15) 0%,transparent 50%);
      pointer-events:none;}
    /* floating circles di hero */
    .hero-circle{position:absolute;border-radius:50%;background:rgba(255,255,255,.06);
      animation:heroFloat 6s ease-in-out infinite;}
    @keyframes heroFloat{0%,100%{transform:translateY(0);}50%{transform:translateY(-15px);}}

    .stat-pill{background:rgba(255,255,255,.15);border:1.5px solid rgba(255,255,255,.25);
      border-radius:50px;padding:.45rem 1rem;backdrop-filter:blur(8px);}

    /* ── Poli Slider FIX ── */
    .poli-outer{overflow:hidden;border-radius:16px;width:100%;}
    .poli-track{display:flex;width:100%;transition:transform .45s cubic-bezier(.4,0,.2,1);}
    .poli-slide{min-width:100%;width:100%;flex-shrink:0;padding:16px 18px;box-sizing:border-box;
      border-radius:16px;display:flex;flex-direction:column;gap:12px;}
    .poli-dot{height:7px;border-radius:4px;background:#dbeafe;border:1.5px solid #bfdbfe;
      transition:all .28s ease;cursor:pointer;}
    .poli-dot.active{background:#3b82f6;border-color:#2563eb;width:22px;}
    .poli-dot:not(.active){width:7px;}

    /* ── Info Cards ── */
    .info-icon-wrap{width:44px;height:44px;border-radius:14px;display:flex;
      align-items:center;justify-content:center;font-size:1.3rem;flex-shrink:0;
      box-shadow:inset 2px 2px 5px rgba(255,255,255,.8);}

    /* ── Stat Bar ── */
    .sbar-track{background:#f1f8ff;border-radius:999px;overflow:hidden;height:8px;}
    .sbar-fill{height:100%;border-radius:999px;width:0%;
      transition:width 1.3s cubic-bezier(.4,0,.2,1);}

    /* ── Rating Stars ── */
    .star-filled{color:#f59e0b;}
    .star-empty{color:#e2e8f0;}

    /* ── Scroll Reveal ── */
    .reveal{opacity:0;transform:translateY(28px);transition:opacity .65s ease,transform .65s ease;}
    .reveal.visible{opacity:1;transform:translateY(0);}
    .reveal-left{opacity:0;transform:translateX(-28px);transition:opacity .65s ease,transform .65s ease;}
    .reveal-left.visible{opacity:1;transform:translateX(0);}
    .reveal-right{opacity:0;transform:translateX(28px);transition:opacity .65s ease,transform .65s ease;}
    .reveal-right.visible{opacity:1;transform:translateX(0);}

    /* ── Pulse Badge ── */
    @keyframes pulse-dot{0%,100%{transform:scale(1);opacity:1;}50%{transform:scale(1.4);opacity:.6;}}
    .pulse-dot{width:8px;height:8px;border-radius:50%;background:#22c55e;
      display:inline-block;animation:pulse-dot 1.8s ease-in-out infinite;}

    /* ── Ticker / Marquee ── */
    .ticker-wrap{overflow:hidden;background:rgba(255,255,255,.6);
      border:1.5px solid rgba(255,255,255,.7);border-radius:999px;}
    .ticker-inner{display:flex;gap:2.5rem;animation:tickerMove 22s linear infinite;white-space:nowrap;}
    @keyframes tickerMove{from{transform:translateX(0);}to{transform:translateX(-50%);}}

    /* ── Quote rotate ── */
    #quote-text{transition:opacity .4s ease;}

    /* ── Number counter ── */
    .count-num{display:inline-block;}

    /* ── CTA Banner ── */
    .cta-banner{background:linear-gradient(135deg,#0f2057,#1e40af,#0891b2);
      border-radius:24px;padding:2.5rem 2rem;text-align:center;position:relative;overflow:hidden;}
    .cta-banner::before{content:'';position:absolute;inset:0;
      background:radial-gradient(ellipse at center,rgba(255,255,255,.06),transparent 70%);
      pointer-events:none;}
  </style>
</head>
<body class="overflow-x-hidden">
  <div class="blob blob-1"></div>
  <div class="blob blob-2"></div>
  <div class="blob blob-3"></div>

  <?php if ($flash): ?>
  <script>
  document.addEventListener('DOMContentLoaded',()=>Swal.fire({
    icon:'<?php echo $flash["type"]; ?>',
    title:'<?php echo addslashes($flash["title"]); ?>',
    text:'<?php echo addslashes($flash["message"]); ?>',
    confirmButtonColor:'#2563eb'
  }));
  </script>
  <?php endif; ?>

  <!-- ══════════════ NAVBAR ══════════════ -->
  <nav class="clay-nav sticky top-0 z-50">
    <div class="max-w-6xl mx-auto px-4 sm:px-5 py-3 flex items-center justify-between gap-3">
      <div class="flex items-center gap-2.5 min-w-0">
        <img src="https://uns.ac.id/id/wp-content/uploads/2023/06/logo-uns-biru.png" alt="Logo UNS"
          class="h-8 sm:h-10 object-contain flex-shrink-0"
          style="filter:drop-shadow(0 2px 6px rgba(30,58,138,.25))"/>
        <div class="hidden sm:block">
          <p class="font-extrabold text-gray-800 text-sm leading-tight">UNS Medical Center</p>
          <p class="text-blue-400 text-xs font-semibold flex items-center gap-1">
            <span class="pulse-dot"></span> Survei Kepuasan Pasien
          </p>
        </div>
      </div>

      <!-- Ticker Info -->
      <div class="ticker-wrap flex-1 max-w-xs hidden md:block px-4 py-1.5">
        <div class="ticker-inner text-xs font-semibold text-gray-500">
          <span>🏥 Pelayanan Profesional &amp; Terjangkau</span>
          <span>⭐ Rating <?php echo $avg_global; ?>/5 dari <?php echo $total_resp; ?> responden</span>
          <span>⏰ Buka Senin–Jumat 07.00–21.00 WIB</span>
          <span>📍 Kampus UNS Surakarta</span>
          <!-- duplikat untuk seamless loop -->
          <span>🏥 Pelayanan Profesional &amp; Terjangkau</span>
          <span>⭐ Rating <?php echo $avg_global; ?>/5 dari <?php echo $total_resp; ?> responden</span>
          <span>⏰ Buka Senin–Jumat 07.00–21.00 WIB</span>
          <span>📍 Kampus UNS Surakarta</span>
        </div>
      </div>

      <div class="flex items-center gap-2 flex-shrink-0">
        <?php if ($is_logged_in): ?>
          <span class="text-xs font-bold text-gray-500 hidden sm:inline">
            👤 <?php echo htmlspecialchars($_SESSION['nama']); ?>
          </span>
          <a href="dashboard.php"
            class="clay-btn bg-gradient-to-r from-blue-600 to-blue-500 text-white px-4 py-2 text-xs">
            Dashboard →
          </a>
        <?php else: ?>
          <a href="login.php"
            class="clay-btn bg-gradient-to-r from-blue-600 to-blue-500 text-white px-4 sm:px-5 py-2 text-xs sm:text-sm">
            Login Pasien
          </a>
        <?php endif; ?>
      </div>
    </div>
  </nav>

  <main class="max-w-6xl mx-auto px-4 sm:px-5 py-6 sm:py-8 space-y-6 pb-10 relative z-10">

    <!-- ══════════════ HERO ══════════════ -->
    <div class="hero-bg p-6 sm:p-10 reveal">
      <!-- decorative circles -->
      <div class="hero-circle w-64 h-64" style="top:-60px;right:-40px;animation-delay:0s;"></div>
      <div class="hero-circle w-40 h-40" style="bottom:-30px;left:5%;animation-delay:2s;"></div>
      <div class="hero-circle w-24 h-24" style="top:20%;right:20%;animation-delay:4s;opacity:.04;"></div>

      <div class="relative z-10 flex flex-col lg:flex-row lg:items-center gap-8">
        <div class="flex-1">
          <div class="flex flex-wrap items-center gap-2 mb-3">
            <span class="stat-pill text-white text-xs font-semibold">👋 Selamat Datang di</span>
            <span class="stat-pill text-white text-xs font-semibold">
              <span class="pulse-dot mr-1"></span> Layanan Aktif
            </span>
          </div>
          <h1 class="text-white font-black text-2xl sm:text-4xl lg:text-5xl leading-tight">
            UNS Medical<br/>
            <span style="background:linear-gradient(90deg,#7dd3fc,#34d399);-webkit-background-clip:text;-webkit-text-fill-color:transparent;">
              Center
            </span>
          </h1>
          <p class="text-blue-100 text-sm sm:text-base mt-3 max-w-lg leading-7">
            Klinik kesehatan terpadu Universitas Sebelas Maret Surakarta. Kami melayani dengan hati — bantu kami tumbuh dengan mengisi survei kepuasan Anda.
          </p>
          <div class="flex flex-wrap gap-2 mt-4">
            <span class="stat-pill text-white text-xs font-semibold">✅ Anonim &amp; Aman</span>
            <span class="stat-pill text-white text-xs font-semibold">⏱ &lt; 2 Menit</span>
            <span class="stat-pill text-white text-xs font-semibold">🔒 Data Terenkripsi</span>
          </div>
          <div class="mt-6 flex flex-wrap gap-3">
            <?php if ($is_logged_in): ?>
              <button onclick="location.href='dashboard.php'"
                class="clay-btn bg-white text-blue-800 px-6 py-3 text-sm">
                Buka Dashboard Survei →
              </button>
            <?php else: ?>
              <button onclick="location.href='login.php'"
                class="clay-btn bg-white text-blue-800 px-6 py-3 text-sm">
                Login &amp; Isi Survei →
              </button>
              <button onclick="location.href='login.php'"
                class="px-6 py-3 text-sm font-bold text-white border-2 border-white/30
                  rounded-2xl hover:bg-white/10 transition">
                Daftar Akun
              </button>
            <?php endif; ?>
          </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-2 gap-3 lg:w-56 flex-shrink-0">
          <div class="stat-pill flex flex-col items-center py-4 px-3 gap-1 rounded-2xl">
            <span class="text-white font-black text-3xl count-num" data-target="<?php echo $total_resp; ?>">0</span>
            <span class="text-blue-200 text-xs font-semibold text-center">Responden</span>
          </div>
          <div class="stat-pill flex flex-col items-center py-4 px-3 gap-1 rounded-2xl">
            <span class="text-white font-black text-3xl"><?php echo $avg_global; ?></span>
            <span class="text-blue-200 text-xs font-semibold text-center">Rating /5</span>
          </div>
          <div class="stat-pill flex flex-col items-center py-4 px-3 gap-1 rounded-2xl">
            <span class="text-white font-black text-3xl">3</span>
            <span class="text-blue-200 text-xs font-semibold text-center">Poli Aktif</span>
          </div>
          <div class="stat-pill flex flex-col items-center py-4 px-3 gap-1 rounded-2xl">
            <span class="text-white font-black text-3xl">14+</span>
            <span class="text-blue-200 text-xs font-semibold text-center">Tahun Melayani</span>
          </div>
        </div>
      </div>
    </div>

    <!-- ══════════════ INFO + POLI SLIDER ══════════════ -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 items-stretch">

      <!-- Tentang -->
      <div class="clay-card clay-card-hover p-5 sm:p-6 reveal-left flex flex-col gap-4">
        <h2 class="font-extrabold text-gray-800 text-base flex items-center gap-2">
          <span class="w-8 h-8 bg-blue-100 rounded-xl flex items-center justify-center text-base shadow-inner">🏥</span>
          Tentang UNS Medical Center
        </h2>
        <p class="text-gray-500 text-sm leading-6">
          Unit pelayanan kesehatan terpadu milik Universitas Sebelas Maret Surakarta, melayani sivitas akademika dan masyarakat umum secara profesional, terjangkau, dan ramah sejak 2010.
        </p>

        <!-- Jam Operasional -->
        <div>
          <p class="text-xs font-bold text-gray-400 mb-2">🕐 Jam Operasional</p>
          <div class="space-y-2">
            <div class="flex justify-between items-center bg-blue-50 rounded-xl px-3 py-2.5">
              <span class="text-gray-600 font-semibold text-xs flex items-center gap-1.5">📅 Senin – Jumat</span>
              <span class="font-bold text-blue-700 text-xs">07.00 – 21.00 WIB</span>
            </div>
            <div class="flex justify-between items-center bg-green-50 rounded-xl px-3 py-2.5">
              <span class="text-gray-600 font-semibold text-xs flex items-center gap-1.5">📅 Sabtu</span>
              <span class="font-bold text-green-700 text-xs">07.00 – 14.00 WIB</span>
            </div>
            <div class="flex justify-between items-center bg-red-50 rounded-xl px-3 py-2.5">
              <span class="text-gray-600 font-semibold text-xs flex items-center gap-1.5">🔴 Minggu &amp; Hari Libur</span>
              <span class="font-bold text-red-500 text-xs">Tutup</span>
            </div>
          </div>
        </div>

        <!-- Kontak & Lokasi -->
        <div>
          <p class="text-xs font-bold text-gray-400 mb-2">📬 Kontak &amp; Lokasi</p>
          <div class="space-y-2">
            <a href="https://maps.google.com/?q=UNS+Medical+Center+Surakarta" target="_blank"
              class="flex items-center gap-2.5 bg-gray-50 hover:bg-blue-50 border border-gray-100
                hover:border-blue-200 rounded-xl px-3 py-2.5 transition group">
              <span class="text-lg">📍</span>
              <span class="text-xs text-gray-600 font-semibold group-hover:text-blue-600 transition">
                Jl. Ir. Sutami No.36A, Kentingan, Surakarta
              </span>
            </a>
            <a href="tel:02716469944"
              class="flex items-center gap-2.5 bg-gray-50 hover:bg-green-50 border border-gray-100 hover:border-green-200 rounded-xl px-3 py-2.5 transition group">
              <span class="text-lg">📞</span>
              <span class="text-xs text-gray-600 group-hover:text-green-700 font-semibold">(0271) 646994</span>
            </a>
            <a href="mailto:medicalcenter@uns.ac.id"
              class="flex items-center gap-2.5 bg-gray-50 hover:bg-purple-50 border border-gray-100 hover:border-purple-200 rounded-xl px-3 py-2.5 transition group">
              <span class="text-lg">✉️</span>
              <span class="text-xs text-gray-600 group-hover:text-purple-700 font-semibold">medicalcenter@uns.ac.id</span>
            </a>
          </div>
        </div>

        <!-- CTA Daftar -->
        <a href="#daftar"
          class="flex items-center justify-center gap-2 bg-gradient-to-r from-blue-500 to-blue-600
            hover:from-blue-600 hover:to-blue-700 text-white text-sm font-bold py-3 rounded-2xl
            shadow-md hover:shadow-lg transition-all active:scale-95">
          📋 Daftar Antrian Sekarang
        </a>

        <!-- Quote Rotator -->
        <div class="mt-auto bg-gradient-to-r from-blue-50 to-cyan-50 border border-blue-100 rounded-xl p-3">
          <p id="quote-text" class="text-blue-800 text-xs font-semibold italic leading-5">
            "Kesehatan bukan segalanya, tetapi tanpa kesehatan segalanya menjadi tidak berarti."
          </p>
          <p class="text-blue-400 text-[10px] font-bold mt-1.5">💡 Tips Kesehatan Hari Ini</p>
        </div>
      </div>

      <!-- POLI SLIDER -->
      <div class="clay-card clay-card-hover p-5 sm:p-6 reveal-right flex flex-col gap-4">
        <!-- Header -->
        <div class="flex items-center justify-between">
          <h3 class="font-bold text-gray-700 text-sm sm:text-base flex items-center gap-2">
            <span class="w-8 h-8 bg-green-50 rounded-xl flex items-center justify-center text-base shadow-inner">🏥</span>
            Layanan Poli Kami
          </h3>
          <div class="flex gap-1.5">
            <button id="poli-prev" class="w-8 h-8 bg-gray-100 hover:bg-blue-100 rounded-xl flex items-center justify-center text-gray-500 hover:text-blue-600 transition text-sm font-bold">‹</button>
            <button id="poli-next" class="w-8 h-8 bg-gray-100 hover:bg-blue-100 rounded-xl flex items-center justify-center text-gray-500 hover:text-blue-600 transition text-sm font-bold">›</button>
          </div>
        </div>

        <!-- Slide area -->
        <div class="poli-outer">
          <div class="poli-track" id="poli-track"></div>
        </div>

        <!-- Dots -->
        <div class="flex gap-1.5 justify-center" id="poli-dots"></div>

        <!-- Quick-jump tabs -->
        <div class="pt-3 border-t border-gray-100">
          <p class="text-xs font-bold text-gray-400 mb-2">Semua Layanan:</p>
          <div class="flex flex-wrap gap-1.5" id="poli-tabs"></div>
        </div>
      </div>
    </div>

    <!-- ══════════════ FITUR / KEUNGGULAN ══════════════ -->
    <div class="reveal">
      <h2 class="font-extrabold text-gray-800 text-lg sm:text-xl mb-4 text-center">
        Mengapa Memilih UNS Medical Center?
      </h2>
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4">
        <?php
        $features = [
          ['💊','Obat Bersubsidi','Harga obat terjangkau untuk sivitas akademika UNS','bg-blue-50','text-blue-600'],
          ['👨‍⚕️','Dokter Berpengalaman','Ditangani dokter spesialis &amp; umum bersertifikat','bg-green-50','text-green-600'],
          ['🔒','Data Aman','Rekam medis dijaga kerahasiaannya sesuai regulasi','bg-purple-50','text-purple-600'],
          ['⚡','Pelayanan Cepat','Sistem antrian digital meminimalkan waktu tunggu','bg-orange-50','text-orange-600'],
        ];
        foreach ($features as [$icon,$title,$desc,$bg,$tc]): ?>
        <div class="clay-card clay-card-hover p-4 text-center">
          <div class="w-12 h-12 <?php echo $bg; ?> rounded-2xl flex items-center justify-center
            text-2xl mx-auto mb-3 shadow-inner"><?php echo $icon; ?></div>
          <h3 class="font-bold text-gray-800 text-xs sm:text-sm mb-1"><?php echo $title; ?></h3>
          <p class="text-gray-400 text-[10px] sm:text-xs leading-5"><?php echo $desc; ?></p>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- ══════════════ STATISTIK KEPUASAN ══════════════ -->
    <?php if ($total_resp > 0): ?>
    <div class="clay-card p-5 sm:p-6 reveal">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-5">
        <div>
          <h2 class="font-extrabold text-gray-800 text-base sm:text-lg">📊 Statistik Kepuasan Pasien</h2>
          <p class="text-xs text-gray-400 mt-0.5">Berdasarkan <strong><?php echo $total_resp; ?></strong> responden aktual</p>
        </div>
        <div class="flex items-center gap-3 bg-green-50 border border-green-200 rounded-2xl px-4 py-2.5">
          <div>
            <p class="text-2xl font-black text-green-700"><?php echo $avg_global; ?></p>
            <p class="text-[10px] text-green-400 font-bold">INDEKS / 5.00</p>
          </div>
          <!-- Star rating visual -->
          <div class="text-lg">
            <?php
            $stars = round((float)$avg_global);
            for ($i=1;$i<=5;$i++) echo $i<=$stars?'⭐':'☆';
            ?>
          </div>
        </div>
      </div>

      <!-- 4 Aspek Progress Bars -->
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <?php
        $aspek = [
          ['😊','Keramahan & Kesopanan Petugas',$avg_q[0],'#3b82f6'],
          ['⚡','Kecepatan & Ketepatan Pelayanan',$avg_q[1],'#06b6d4'],
          ['✨','Kebersihan & Kenyamanan Fasilitas',$avg_q[2],'#22c55e'],
          ['🩺','Kualitas Penanganan Medis',$avg_q[3],'#a855f7'],
        ];
        foreach ($aspek as [$ic,$lb,$vl,$cl]):
          $pct = round($vl/5*100);
        ?>
        <div>
          <div class="flex justify-between items-center mb-1.5">
            <span class="text-xs font-semibold text-gray-600"><?php echo "$ic $lb"; ?></span>
            <span class="text-xs font-extrabold text-gray-800"><?php echo $vl; ?>/5</span>
          </div>
          <div class="sbar-track">
            <div class="sbar-fill reveal-bar" data-w="<?php echo $pct; ?>%"
              style="background:linear-gradient(90deg,<?php echo $cl; ?>88,<?php echo $cl; ?>)"></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Distribusi Kategori -->
      <?php if (!empty($kat_count)): ?>
      <div class="mt-5 pt-4 border-t border-gray-100">
        <p class="text-xs font-bold text-gray-500 mb-3">Distribusi Responden per Kategori:</p>
        <div class="flex flex-wrap gap-2">
          <?php
          $kc=['Mahasiswa'=>['bg-blue-100','text-blue-700'],'Dosen'=>['bg-purple-100','text-purple-700'],
               'Karyawan'=>['bg-orange-100','text-orange-700'],'Umum'=>['bg-green-100','text-green-700']];
          foreach ($kat_count as $kat=>$cnt):
            [$bg,$tc] = $kc[$kat]??['bg-gray-100','text-gray-700'];
          ?>
          <span class="<?php echo "$bg $tc"; ?> px-3 py-1.5 rounded-full text-xs font-bold">
            <?php echo htmlspecialchars($kat); ?>: <?php echo $cnt; ?> org
          </span>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- ══════════════ PROSEDUR SURVEI ══════════════ -->
    <div class="reveal">
      <h2 class="font-extrabold text-gray-800 text-lg sm:text-xl mb-4 text-center">
        Cara Mengisi Survei
      </h2>
      <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
        <?php
        $steps = [
          ['1','Buat Akun','Daftarkan diri dengan NIK KTP dan nomor WhatsApp Anda.','#3b82f6'],
          ['2','Login','Masuk menggunakan NIK atau nomor HP yang terdaftar.','#06b6d4'],
          ['3','Verifikasi Kunjungan','Masukkan token dari struk atau pilih tanggal kunjungan.','#22c55e'],
          ['4','Isi & Kirim','Beri penilaian jujur — hanya butuh 1–2 menit!','#a855f7'],
        ];
        foreach ($steps as [$n,$t,$d,$c]): ?>
        <div class="clay-card clay-card-hover p-4 relative overflow-hidden">
          <div class="absolute -right-3 -top-3 text-7xl font-black opacity-[.06]"
            style="color:<?php echo $c; ?>"><?php echo $n; ?></div>
          <div class="w-8 h-8 rounded-xl flex items-center justify-center text-white text-sm font-extrabold mb-3 shadow"
            style="background:<?php echo $c; ?>"><?php echo $n; ?></div>
          <h3 class="font-bold text-gray-800 text-sm mb-1"><?php echo $t; ?></h3>
          <p class="text-gray-400 text-xs leading-5"><?php echo $d; ?></p>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- ══════════════ CTA BANNER ══════════════ -->
    <div class="cta-banner reveal">
      <div class="relative z-10">
        <p class="text-blue-200 text-sm font-semibold mb-2">Suara Anda Sangat Berarti 💙</p>
        <h2 class="text-white font-black text-2xl sm:text-3xl mb-3">
          Bantu Kami Melayani Lebih Baik
        </h2>
        <p class="text-blue-200 text-sm max-w-md mx-auto mb-6 leading-6">
          Setiap penilaian yang Anda berikan menjadi bahan evaluasi nyata untuk meningkatkan mutu layanan klinik kami.
        </p>
        <?php if ($is_logged_in): ?>
          <button onclick="location.href='dashboard.php'"
            class="clay-btn bg-white text-blue-800 px-8 py-3.5 text-sm">
            Isi Survei Sekarang →
          </button>
        <?php else: ?>
          <button onclick="location.href='login.php'"
            class="clay-btn bg-white text-blue-800 px-8 py-3.5 text-sm">
            Login &amp; Mulai Survei →
          </button>
        <?php endif; ?>
      </div>
    </div>

  </main>
  <!-- ══════════════ BPS SECTION REDESIGN ══════════════ -->
  <div class="max-w-6xl mx-auto px-4 sm:px-5 mb-10 reveal">
    <div class="clay-card p-6 sm:p-8" style="border-top:4px solid #2563eb;">

      <!-- Header -->
      <div class="flex flex-col gap-4 mb-6">
        <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
          <div>
            <div class="flex items-center gap-2 mb-1">
              <span class="w-9 h-9 bg-blue-600 rounded-xl flex items-center justify-center text-white text-lg shadow">🏥</span>
              <h2 class="font-black text-gray-800 text-lg sm:text-xl">Sebaran Fasilitas Kesehatan</h2>
            </div>
            <p class="text-xs text-gray-400 ml-11">RS Umum · RS Khusus · Puskesmas Rawat Inap · Puskesmas Non RI — BPS 2025</p>
          </div>

          <!-- Badge live -->
          <div class="flex items-center gap-2 bg-green-50 border border-green-200 rounded-2xl px-3 py-1.5 self-start">
            <span class="pulse-dot"></span>
            <span class="text-xs font-bold text-green-700">Data Live BPS</span>
          </div>
        </div>

        <!-- Controls Row -->
        <div class="flex flex-col sm:flex-row gap-3 items-stretch sm:items-center">

          <!-- Wilayah Dropdown — SEMUA 38 PROVINSI -->
          <div class="flex-1">
            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1 block">Pilih Wilayah</label>
            <select id="wilayahBPS" onchange="fetchDataBPS()"
              class="w-full border border-blue-200 text-blue-800 text-xs font-bold rounded-xl px-3 py-2.5 bg-blue-50 outline-none cursor-pointer transition hover:bg-blue-100 focus:ring-2 focus:ring-blue-300">
              <option value="0000000">🇮🇩 Nasional (Indonesia)</option>
              <optgroup label="── Jawa ──">
                <option value="3100000">📍 DKI Jakarta</option>
                <option value="3200000">📍 Jawa Barat</option>
                <option value="3300000" selected>📍 Jawa Tengah</option>
                <option value="3400000">📍 DI Yogyakarta</option>
                <option value="3500000">📍 Jawa Timur</option>
                <option value="3600000">📍 Banten</option>
              </optgroup>
              <optgroup label="── Sumatera ──">
                <option value="1100000">📍 Aceh</option>
                <option value="1200000">📍 Sumatera Utara</option>
                <option value="1300000">📍 Sumatera Barat</option>
                <option value="1400000">📍 Riau</option>
                <option value="1500000">📍 Jambi</option>
                <option value="1600000">📍 Sumatera Selatan</option>
                <option value="1700000">📍 Bengkulu</option>
                <option value="1800000">📍 Lampung</option>
                <option value="1900000">📍 Kep. Bangka Belitung</option>
                <option value="2100000">📍 Kepulauan Riau</option>
              </optgroup>
              <optgroup label="── Bali & Nusa Tenggara ──">
                <option value="5100000">📍 Bali</option>
                <option value="5200000">📍 Nusa Tenggara Barat</option>
                <option value="5300000">📍 Nusa Tenggara Timur</option>
              </optgroup>
              <optgroup label="── Kalimantan ──">
                <option value="6100000">📍 Kalimantan Barat</option>
                <option value="6200000">📍 Kalimantan Tengah</option>
                <option value="6300000">📍 Kalimantan Selatan</option>
                <option value="6400000">📍 Kalimantan Timur</option>
                <option value="6500000">📍 Kalimantan Utara</option>
              </optgroup>
              <optgroup label="── Sulawesi ──">
                <option value="7100000">📍 Sulawesi Utara</option>
                <option value="7200000">📍 Sulawesi Tengah</option>
                <option value="7300000">📍 Sulawesi Selatan</option>
                <option value="7400000">📍 Sulawesi Tenggara</option>
                <option value="7500000">📍 Gorontalo</option>
                <option value="7600000">📍 Sulawesi Barat</option>
              </optgroup>
              <optgroup label="── Maluku & Papua ──">
                <option value="8100000">📍 Maluku</option>
                <option value="8200000">📍 Maluku Utara</option>
                <option value="9100000">📍 Papua Barat</option>
                <option value="9400000">📍 Papua Barat Daya</option>
                <option value="9200000">📍 Papua</option>
                <option value="9300000">📍 Papua Selatan</option>
                <option value="9500000">📍 Papua Pegunungan</option>
                <option value="9600000">📍 Papua Tengah</option>
              </optgroup>
            </select>
          </div>

          <!-- Chart Type Tabs -->
          <div class="sm:self-end">
            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1 block">Jenis Grafik</label>
            <div class="flex gap-1.5 bg-gray-100 rounded-xl p-1">
              <button onclick="setChartType('bar')" id="btn-bar"
                class="chart-type-btn active-chart-btn flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-bold transition-all">
                📊 Batang
              </button>
              <button onclick="setChartType('line')" id="btn-line"
                class="chart-type-btn flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-bold transition-all">
                📈 Garis
              </button>
              <button onclick="setChartType('doughnut')" id="btn-doughnut"
                class="chart-type-btn flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-bold transition-all">
                🍩 Donat
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Loading & Error State -->
      <div id="bps-loading" class="flex flex-col items-center justify-center py-16 gap-3">
        <div style="width:40px;height:40px;border:4px solid #dbeafe;border-top-color:#2563eb;border-radius:50%;animation:spin .8s linear infinite;"></div>
        <p class="text-sm font-semibold text-gray-400">Memuat data BPS...</p>
      </div>
      <div id="bps-error" class="hidden flex-col items-center justify-center py-16 gap-3">
        <span class="text-4xl">⚠️</span>
        <p class="text-sm font-bold text-red-500">Gagal memuat data dari BPS.</p>
        <button onclick="fetchDataBPS()" class="clay-btn bg-blue-600 text-white px-4 py-2 text-xs">Coba Lagi</button>
      </div>

      <!-- Summary Cards (dinamis) -->
      <div id="bps-summary" class="hidden grid grid-cols-2 sm:grid-cols-4 gap-3 mb-5"></div>

      <!-- Chart -->
      <div id="bps-chart-wrap" class="hidden" style="position:relative; min-height:320px; width:100%;">
        <canvas id="bpsChartMain"></canvas>
      </div>

      <!-- Footer -->
      <div class="mt-5 pt-4 border-t border-gray-100 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2">
        <p class="text-[10px] text-gray-400 italic">🔗 Sumber: WebAPI Badan Pusat Statistik Indonesia (webapi.bps.go.id) · Dataset ID 25 · 2025</p>
        <span id="bps-update-time" class="text-[10px] text-gray-300 font-semibold"></span>
      </div>
    </div>
  </div>

  <style>
  @keyframes spin{to{transform:rotate(360deg);}}
  .chart-type-btn{color:#94a3b8;background:transparent;}
  .active-chart-btn{background:#fff !important;color:#2563eb !important;
    box-shadow:0 2px 8px rgba(37,99,235,.15);}
  </style>
  <!-- ══════════════ FOOTER ══════════════ -->
  <footer class="relative z-10 text-center py-6 px-4">
    <p class="text-gray-400 text-xs">
      © <?php echo date('Y'); ?> UNS Medical Center · Universitas Sebelas Maret Surakarta
      &nbsp;·&nbsp; <a href="#" class="hover:text-blue-500 transition">Kebijakan Privasi</a>
    </p>
  </footer>

<script>
/* ══════════════════════════════════════════
   POLI SLIDER — completely rewritten & fixed
══════════════════════════════════════════ */
const POLI = [
  {
    icon:'🏥', name:'Poli Umum',
    color:'#eff6ff', accent:'#2563eb', border:'#bfdbfe', tag:'bg-blue-50 text-blue-700 border-blue-200',
    desc:'Pemeriksaan dan pengobatan keluhan kesehatan umum oleh dokter berpengalaman. Tersedia konsultasi, pemeriksaan fisik, dan penanganan penyakit ringan hingga sedang.',
    jam:'Setiap hari kerja', jamWIB:'07.00 – 21.00 WIB',
    layanan:['Konsultasi Umum','Pemeriksaan Fisik','Resep Obat','Surat Keterangan Sehat'],
    dokter:'dr. Umum Bersertifikat',
    biaya:'BPJS & Umum',
    tip:'Bawa kartu identitas & kartu BPJS untuk kemudahan pendaftaran.'
  },
  {
    icon:'🦷', name:'Poli Gigi',
    color:'#f0fdf4', accent:'#15803d', border:'#bbf7d0', tag:'bg-green-50 text-green-700 border-green-200',
    desc:'Perawatan gigi dan mulut komprehensif oleh dokter gigi bersertifikat. Meliputi pembersihan karang gigi, tambal gigi komposit, dan pencabutan dengan anestesi lokal.',
    jam:'Senin – Sabtu', jamWIB:'07.00 – 14.00 WIB',
    layanan:['Cabut Gigi','Tambal Gigi Komposit','Scaling / Karang Gigi','Konsultasi Gigi'],
    dokter:'drg. Spesialis Gigi',
    biaya:'BPJS & Umum',
    tip:'Sikat gigi sebelum datang dan hindari makan berat 1 jam sebelum tindakan.'
  },
  {
    icon:'🤱', name:'KIA / KB',
    color:'#fdf2f8', accent:'#9d174d', border:'#fbcfe8', tag:'bg-pink-50 text-pink-700 border-pink-200',
    desc:'Layanan Kesehatan Ibu dan Anak serta konsultasi Keluarga Berencana. Meliputi pemeriksaan kehamilan, tumbuh kembang anak, imunisasi dasar, dan konseling KB.',
    jam:'Senin – Jumat', jamWIB:'07.00 – 14.00 WIB',
    layanan:['Pemeriksaan Kehamilan','Imunisasi Bayi & Anak','Konsultasi KB','Tumbuh Kembang Anak'],
    dokter:'dr./Bidan Terlatih',
    biaya:'BPJS & Umum',
    tip:'Bawa buku KIA atau catatan imunisasi anak untuk pemantauan yang optimal.'
  },
  {
    icon:'🧪', name:'Lab & Farmasi',
    color:'#fefce8', accent:'#854d0e', border:'#fde68a', tag:'bg-yellow-50 text-yellow-700 border-yellow-200',
    desc:'Laboratorium klinik dasar dan apotek internal bersubsidi dalam satu atap. Hasil lab cepat dan obat langsung tersedia sesuai resep dokter tanpa perlu keluar gedung.',
    jam:'Setiap hari kerja', jamWIB:'07.00 – 20.00 WIB',
    layanan:['Cek Darah Lengkap','Cek Gula & Kolesterol','Urinalisis','Apotek Bersubsidi'],
    dokter:'Analis & Apoteker',
    biaya:'BPJS & Umum',
    tip:'Untuk cek darah puasa, sebaiknya tidak makan minimal 8 jam sebelum pengambilan sampel.'
  }
];

let idx = 0, timer;
const track = document.getElementById('poli-track');
const dotsEl = document.getElementById('poli-dots');
const tabsEl = document.getElementById('poli-tabs');

// Build slides — rich content, no empty space
track.innerHTML = POLI.map(p => `
  <div class="poli-slide" style="background:${p.color};border:1.5px solid ${p.border};">
    <!-- Header -->
    <div style="display:flex;align-items:center;gap:10px;">
      <span style="font-size:2rem;line-height:1;flex-shrink:0;">${p.icon}</span>
      <div>
        <p style="font-weight:800;font-size:1rem;color:${p.accent};margin:0;">${p.name}</p>
        <span style="font-size:.68rem;background:${p.border};color:${p.accent};
          border-radius:99px;padding:2px 10px;font-weight:700;display:inline-block;margin-top:3px;">
          📅 ${p.jam} · ${p.jamWIB}
        </span>
      </div>
    </div>
    <!-- Deskripsi -->
    <p style="font-size:.78rem;color:#475569;line-height:1.75;margin:0;">${p.desc}</p>
    <!-- Layanan grid -->
    <div>
      <p style="font-size:.65rem;font-weight:700;color:${p.accent};opacity:.7;margin:0 0 5px;text-transform:uppercase;letter-spacing:.05em;">Layanan Tersedia</p>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:5px;">
        ${p.layanan.map(l=>`
          <span style="background:${p.border};color:${p.accent};
            font-size:.68rem;font-weight:700;padding:5px 10px;border-radius:8px;
            display:flex;align-items:center;gap:4px;">
            <span style="opacity:.6;">✓</span> ${l}
          </span>`).join('')}
      </div>
    </div>
    <!-- Info bawah -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px;margin-top:2px;">
      <div style="background:white;border-radius:10px;padding:7px 10px;border:1px solid ${p.border};">
        <p style="font-size:.6rem;color:#94a3b8;font-weight:700;margin:0 0 2px;text-transform:uppercase;">Tenaga Medis</p>
        <p style="font-size:.72rem;color:${p.accent};font-weight:700;margin:0;">${p.dokter}</p>
      </div>
      <div style="background:white;border-radius:10px;padding:7px 10px;border:1px solid ${p.border};">
        <p style="font-size:.6rem;color:#94a3b8;font-weight:700;margin:0 0 2px;text-transform:uppercase;">Pembayaran</p>
        <p style="font-size:.72rem;color:${p.accent};font-weight:700;margin:0;">${p.biaya}</p>
      </div>
    </div>
    <!-- Tip -->
    <div style="background:white;border-radius:10px;padding:8px 12px;border:1px solid ${p.border};display:flex;gap:8px;align-items:flex-start;">
      <span style="font-size:.9rem;flex-shrink:0;">💡</span>
      <p style="font-size:.7rem;color:#64748b;margin:0;line-height:1.6;font-style:italic;">${p.tip}</p>
    </div>
  </div>`).join('');

// Build dots
dotsEl.innerHTML = POLI.map((_,i)=>
  `<button class="poli-dot${i===0?' active':''}" onclick="goSlide(${i},true)"></button>`
).join('');

// Build quick-jump tabs
tabsEl.innerHTML = POLI.map((p,i)=>
  `<button onclick="goSlide(${i},true)"
    class="text-xs ${p.tag} border px-2.5 py-1 rounded-full font-semibold hover:opacity-80 transition"
    id="poli-tab-${i}">
    ${p.icon} ${p.name}
  </button>`
).join('');

function goSlide(i, manual=false) {
  if (manual) { clearInterval(timer); startAuto(); }
  idx = ((i % POLI.length) + POLI.length) % POLI.length;
  track.style.transform = `translateX(-${idx * 100}%)`;
  dotsEl.querySelectorAll('.poli-dot').forEach((d,j) => {
    d.classList.toggle('active', j===idx);
  });
}

function startAuto() {
  timer = setInterval(() => goSlide(idx+1), 4000);
}

document.getElementById('poli-prev').onclick = () => goSlide(idx-1, true);
document.getElementById('poli-next').onclick = () => goSlide(idx+1, true);

// Pause on hover
track.parentElement.addEventListener('mouseenter', () => clearInterval(timer));
track.parentElement.addEventListener('mouseleave', startAuto);
startAuto();

/* ══════════════════════════════════════════
   SCROLL REVEAL
══════════════════════════════════════════ */
const revObs = new IntersectionObserver(entries => {
  entries.forEach(e => {
    if (e.isIntersecting) {
      e.target.classList.add('visible');
      revObs.unobserve(e.target);
    }
  });
}, { threshold: 0.08 });

document.querySelectorAll('.reveal,.reveal-left,.reveal-right').forEach((el,i) => {
  el.style.transitionDelay = (i * 0.05) + 's';
  revObs.observe(el);
});

/* ══════════════════════════════════════════
   STAT BARS (IntersectionObserver)
══════════════════════════════════════════ */
const barObs = new IntersectionObserver(entries => {
  entries.forEach(e => {
    if (e.isIntersecting) {
      setTimeout(() => { e.target.style.width = e.target.getAttribute('data-w'); }, 300);
      barObs.unobserve(e.target);
    }
  });
}, { threshold: 0.3 });
document.querySelectorAll('.reveal-bar').forEach(b => barObs.observe(b));

/* ══════════════════════════════════════════
   NUMBER COUNTER ANIMASI
══════════════════════════════════════════ */
document.querySelectorAll('.count-num[data-target]').forEach(el => {
  const target = +el.getAttribute('data-target');
  if (!target) return;
  let cur = 0;
  const step  = Math.max(1, Math.ceil(target / 50));
  const t = setInterval(() => {
    cur = Math.min(cur + step, target);
    el.textContent = cur;
    if (cur >= target) clearInterval(t);
  }, 30);
});

/* ══════════════════════════════════════════
   HEALTH QUOTE ROTATOR
══════════════════════════════════════════ */
const QUOTES = [
  '"Kesehatan bukan segalanya, tetapi tanpa kesehatan segalanya menjadi tidak berarti."',
  '"Jaga kesehatan sebelum sakit — mencegah selalu lebih baik dari mengobati."',
  '"Tubuh yang sehat adalah taman jiwa yang bahagia." — Francis Bacon',
  '"Minum 8 gelas air per hari, tidur cukup 7–8 jam, dan olahraga 30 menit setiap hari."',
  '"Senyum adalah obat mujarab — baik untuk Anda maupun orang di sekitar Anda."',
];
let qi = 0;
const qEl = document.getElementById('quote-text');
setInterval(() => {
  qEl.style.opacity = '0';
  setTimeout(() => {
    qi = (qi+1)%QUOTES.length;
    qEl.textContent = QUOTES[qi];
    qEl.style.opacity = '1';
  }, 400);
}, 5000);

/* ══════════════════════════════════════════
   BPS DATA — PARSER BENAR SESUAI STRUKTUR JSON
   Struktur: data[0]=paginasi, data[1]=isi tabel
   data[1].kolom  = { keyAcak: { nama_variabel, ... }, ... }
   data[1].data[] = { label, variables: { keyAcak: { value } } }
══════════════════════════════════════════ */
let chartBps   = null;
let currentChartType = 'bar';

// State global hasil parse
let gProvinsiList = []; // [{label, vars:{key:angka}}]
let gKolom        = []; // [{key, nama}] — 4 jenis faskes
let gNamaWilayah  = '';
let gIsNasional   = true;
let gActiveKolom  = null; // null = semua (grouped), atau key tertentu

// Warna untuk tiap jenis faskes — 8 slot agar aman meski BPS return >4 kolom
const FASKES_COLORS = [
  { bg:'rgba(59,130,246,.8)',  border:'#2563eb', light:'#dbeafe', text:'#1d4ed8'  }, // RS Umum      — biru
  { bg:'rgba(168,85,247,.8)', border:'#9333ea', light:'#f3e8ff', text:'#7e22ce'  }, // RS Khusus    — ungu
  { bg:'rgba(34,197,94,.8)',  border:'#16a34a', light:'#dcfce7', text:'#15803d'  }, // PKM RI       — hijau
  { bg:'rgba(245,158,11,.8)', border:'#d97706', light:'#fef9c3', text:'#b45309'  }, // PKM Non RI   — kuning
  { bg:'rgba(239,68,68,.8)',  border:'#dc2626', light:'#fee2e2', text:'#b91c1c'  }, // Klinik       — merah
  { bg:'rgba(20,184,166,.8)', border:'#0d9488', light:'#ccfbf1', text:'#0f766e'  }, // Posyandu     — teal
  { bg:'rgba(251,146,60,.8)', border:'#ea580c', light:'#ffedd5', text:'#c2410c'  }, // cadangan — oranye
  { bg:'rgba(99,102,241,.8)', border:'#4f46e5', light:'#e0e7ff', text:'#4338ca'  }, // cadangan — indigo
];
// Helper: ambil warna dengan fallback aman walau index > panjang array
function getFaskesColor(i) {
  return FASKES_COLORS[i % FASKES_COLORS.length];
}

/* ── Pilih jenis faskes (filter chart) ── */
function setActiveKolom(key) {
  gActiveKolom = (gActiveKolom === key) ? null : key; // toggle
  document.querySelectorAll('.faskes-pill').forEach(b => {
    b.classList.toggle('ring-2', b.dataset.key === gActiveKolom);
    b.classList.toggle('ring-offset-1', b.dataset.key === gActiveKolom);
  });
  renderBpsChart();
}

/* ── Tipe grafik ── */
function setChartType(type) {
  currentChartType = type;
  document.querySelectorAll('.chart-type-btn').forEach(b => b.classList.remove('active-chart-btn'));
  document.getElementById('btn-' + type).classList.add('active-chart-btn');
  renderBpsChart();
}

/* ── State UI ── */
function showBpsState(state) {
  // loading & error states
  const loadEl  = document.getElementById('bps-loading');
  const errorEl = document.getElementById('bps-error');
  if (loadEl)  { loadEl.classList.toggle('hidden', state !== 'loading'); loadEl.classList.toggle('flex', state === 'loading'); }
  if (errorEl) { errorEl.classList.toggle('hidden', state !== 'error');  errorEl.classList.toggle('flex', state === 'error'); }
  // chart wrap
  const wrap = document.getElementById('bps-chart-wrap');
  if (wrap) wrap.classList.toggle('hidden', state !== 'ready');
  // summary hanya disembunyikan saat loading/error — saat ready, renderSummaryCards() yang handle
  const sumEl = document.getElementById('bps-summary');
  if (sumEl && state !== 'ready') { sumEl.classList.add('hidden'); sumEl.classList.remove('grid'); }
}

/* ── Summary cards (4 jenis faskes) ── */
function renderSummaryCards() {
  const el = document.getElementById('bps-summary');
  if (!el) return;

  el.innerHTML = gKolom.map((kol, i) => {
    // Nasional: sum semua provinsi. Provinsi: ambil dari 1 baris
    const total = gIsNasional
      ? gProvinsiList.reduce((s, p) => s + (p.vars[kol.key] || 0), 0)
      : (gProvinsiList[0]?.vars[kol.key] || 0);
    const c = getFaskesColor(i);
    const isActive = (gActiveKolom === kol.key);
    const short = kol.nama
      .replace('Jumlah ','')
      .replace('Rumah Sakit','RS')
      .replace(' Rawat Inap',' Rawat Inap')
      .replace(' Non Rawat Inap',' Non RI');
    return `
      <div class="faskes-pill rounded-2xl p-3 sm:p-4 flex flex-col gap-1 cursor-pointer transition-all
        ${isActive ? 'ring-2 ring-offset-1' : ''}"
        style="background:${c.light};border:1.5px solid ${c.border}40;"
        data-key="${kol.key}" onclick="setActiveKolom('${kol.key}')"
        title="Klik untuk filter grafik — ${short}">
        <p class="text-[9px] sm:text-[10px] font-bold uppercase tracking-wider" style="color:${c.text}">${short}</p>
        <p class="text-xl sm:text-2xl font-black" style="color:${c.text}">${total.toLocaleString('id-ID')}</p>
        <p class="text-[9px] sm:text-[10px]" style="color:${c.text}88">${gNamaWilayah}</p>
      </div>`;
  }).join('');
  el.classList.remove('hidden');
  el.classList.add('grid');
}

/* ── Render chart utama ── */
function renderBpsChart() {
  const canvas = document.getElementById('bpsChartMain');
  if (!canvas) return;
  const ctx = canvas.getContext('2d');
  if (chartBps) { chartBps.destroy(); chartBps = null; }

  const isDoughnut = currentChartType === 'doughnut';

  // Mode: Nasional → X=provinsi, dataset=tiap jenis faskes (atau 1 jenis jika filter)
  //       Provinsi  → X=jenis faskes, dataset=1 (atau filter)
  let labels, datasets;

  if (gIsNasional) {
    // X = nama provinsi, grouped bar 4 dataset (atau 1 jika filter)
    labels = gProvinsiList.map(p => p.label);
    const kolomToShow = gActiveKolom
      ? gKolom.filter(k => k.key === gActiveKolom)
      : gKolom;

    datasets = kolomToShow.map((kol, i) => {
      const ci = gKolom.indexOf(kol);
      const c  = getFaskesColor(ci);
      const short = kol.nama.replace('Jumlah ','').replace(' Rawat Inap',' RI').replace(' Non Rawat Inap',' Non RI');
      return {
        label: short,
        data:  gProvinsiList.map(p => p.vars[kol.key] || 0),
        backgroundColor: c.bg,
        borderColor: c.border,
        borderWidth: 2,
        borderRadius: isDoughnut ? 0 : 8,
        tension: 0.4,
        fill: currentChartType === 'line',
        pointBackgroundColor: '#fff',
        pointBorderWidth: 2,
        pointRadius: 4,
        hoverOffset: 10,
      };
    });

    // Doughnut mode nasional → jumlah tiap jenis faskes (total)
    if (isDoughnut) {
      const kolomToShow2 = gActiveKolom ? gKolom.filter(k=>k.key===gActiveKolom) : gKolom;
      labels   = kolomToShow2.map(k => k.nama.replace('Jumlah ','').replace(' Rawat Inap',' RI').replace(' Non Rawat Inap',' Non RI'));
      datasets = [{
        data: kolomToShow2.map(kol => gProvinsiList.reduce((s,p)=>s+(p.vars[kol.key]||0),0)),
        backgroundColor: kolomToShow2.map((_,i)=>getFaskesColor(gKolom.indexOf(kolomToShow2[i])).bg),
        borderColor:     kolomToShow2.map((_,i)=>getFaskesColor(gKolom.indexOf(kolomToShow2[i])).border),
        borderWidth: 2, hoverOffset: 14,
      }];
    }

  } else {
    // Provinsi spesifik → X = jenis faskes, 1 bar per jenis
    const prov = gProvinsiList[0];
    const kolomToShow = gActiveKolom ? gKolom.filter(k=>k.key===gActiveKolom) : gKolom;
    labels = kolomToShow.map(k => k.nama.replace('Jumlah ','').replace(' Rawat Inap',' RI').replace(' Non Rawat Inap',' Non RI'));
    const vals = kolomToShow.map(k => prov ? (prov.vars[k.key]||0) : 0);
    const bgC  = kolomToShow.map(k => getFaskesColor(gKolom.indexOf(k)).bg);
    const brC  = kolomToShow.map(k => getFaskesColor(gKolom.indexOf(k)).border);
    datasets = [{
      label: gNamaWilayah,
      data: vals,
      backgroundColor: bgC,
      borderColor: brC,
      borderWidth: 2,
      borderRadius: isDoughnut ? 0 : 12,
      barThickness: kolomToShow.length <= 2 ? 80 : undefined,
      tension: 0.4, fill: currentChartType==='line',
      pointBackgroundColor:'#fff', pointBorderWidth:2, pointRadius:6,
      hoverOffset: 14,
    }];
  }

  chartBps = new Chart(ctx, {
    type: currentChartType,
    data: { labels, datasets },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      animation: { duration: 600, easing: 'easeOutQuart' },
      plugins: {
        legend: {
          display: (isDoughnut || (gIsNasional && !gActiveKolom && currentChartType!=='doughnut')),
          position: 'bottom',
          labels: { padding: 16, font:{ size:11, weight:'bold' }, usePointStyle:true, boxWidth:10 }
        },
        tooltip: {
          backgroundColor:'#1e293b', padding:12, cornerRadius:12,
          titleFont:{ size:13, weight:'bold' }, bodyFont:{ size:12 },
          callbacks: {
            label: c => {
              const v = c.parsed.y ?? c.parsed;
              return `  ${c.dataset.label || labels[c.dataIndex]}: ${Number(v).toLocaleString('id-ID')} unit`;
            }
          }
        }
      },
      scales: isDoughnut ? {} : {
        y: {
          beginAtZero:true,
          stacked: false,
          grid:{ color:'rgba(0,0,0,0.04)' },
          ticks:{ font:{size:10}, color:'#94a3b8',
            callback: v => v >= 1000 ? (v/1000).toFixed(1)+'rb' : v }
        },
        x: {
          grid:{ display:false },
          ticks: {
            font:{ size: gIsNasional ? 9 : 11, weight:'700' },
            color:'#334155',
            maxRotation: gIsNasional ? 45 : 0,
          }
        }
      }
    }
  });
}

/* ══════════════════════════════════════════
   BPS — Selalu fetch NASIONAL (0000000), lalu filter di JS.
   Ini satu-satunya cara reliable karena endpoint per-provinsi
   BPS mengembalikan struktur berbeda (data kab/kota, kolom hilang, dll).
══════════════════════════════════════════ */

// Cache data nasional agar tidak re-fetch setiap ganti provinsi
let gAllRowsCache   = null; // semua baris hasil parse
let gKolomCache     = null; // definisi kolom (stabil)
let gFetchPromise   = null; // promise in-flight agar tidak double-fetch

/* ── Parse raw BPS JSON → {kolom, allRows} ── */
function parseBpsJson(json) {
  if (json.error) throw new Error(json.error);
  const dataArr = json.data;
  if (!Array.isArray(dataArr) || dataArr.length === 0) throw new Error('Struktur tidak dikenal');

  // Cari elemen yang punya kolom valid + data tidak kosong
  let tabel = null;
  for (const c of dataArr) {
    if (!c || typeof c !== 'object') continue;
    const kolRaw = c.kolom;
    const hasKolom = kolRaw &&
      ((typeof kolRaw === 'object' && !Array.isArray(kolRaw) && Object.keys(kolRaw).length > 0) ||
       (Array.isArray(kolRaw) && kolRaw.length > 0));
    if (hasKolom && Array.isArray(c.data) && c.data.length > 0) { tabel = c; break; }
  }
  // Fallback: elemen manapun yang punya data[]
  if (!tabel) tabel = dataArr.find(d => d && Array.isArray(d.data) && d.data.length > 0);
  if (!tabel) throw new Error('Tabel data tidak ditemukan');

  // Parse kolom
  let kolom = [];
  const kr = tabel.kolom;
  if (kr && typeof kr === 'object' && !Array.isArray(kr) && Object.keys(kr).length > 0) {
    kolom = Object.entries(kr).map(([key, meta]) => ({
      key,
      nama: (typeof meta === 'object' ? meta?.nama_variabel || meta?.nama || key : String(meta)) || key,
    }));
  } else if (Array.isArray(kr) && kr.length > 0) {
    kolom = kr.map((m, i) => ({ key: m?.key || m?.id || String(i), nama: m?.nama || m?.nama_variabel || m?.label || String(i) }));
  } else {
    // Infer dari variables baris pertama
    const firstVars = tabel.data[0]?.variables || tabel.data[0]?.vars || {};
    const keys = Object.keys(firstVars);
    if (!keys.length) throw new Error('Kolom tidak dapat ditentukan dari respons BPS');
    kolom = keys.map(k => ({ key: k, nama: k }));
  }

  // Parse baris
  const allRows = tabel.data.map(row => {
    const vars = {};
    kolom.forEach(kol => {
      const cell = row.variables?.[kol.key] ?? row.vars?.[kol.key];
      let raw = '0';
      if (cell != null) raw = (typeof cell === 'object') ? (cell?.value_raw ?? cell?.value ?? '0') : cell;
      else if (row[kol.key] != null) raw = row[kol.key];
      vars[kol.key] = parseInt(String(raw).replace(/[^0-9-]/g, ''), 10) || 0;
    });
    return {
      label:        row.label || row.label_raw || row.nama || row.name || '?',
      kode_wilayah: row.kode_wilayah ?? row.kode ?? row.id ?? null,
      vars,
    };
  });

  return { kolom, allRows };
}

/* ── Pastikan data nasional ter-load (dengan cache) ── */
function ensureNasionalLoaded() {
  if (gAllRowsCache && gKolomCache) return Promise.resolve();
  if (gFetchPromise) return gFetchPromise;

  gFetchPromise = fetch('proses/fetchBPS.php?wilayah=0000000')
    .then(r => { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
    .then(json => {
      const { kolom, allRows } = parseBpsJson(json);
      gKolomCache   = kolom;
      gAllRowsCache = allRows.filter(p =>
        p.kode_wilayah && Number(p.kode_wilayah) !== 0 &&
        Object.values(p.vars).some(v => v > 0)
      );
      if (!gAllRowsCache.length) gAllRowsCache = allRows;
      console.log('[BPS] Cache loaded:', gAllRowsCache.length, 'provinsi |',
        gKolomCache.map(k => k.nama).join(', '));
      gFetchPromise = null;
    })
    .catch(err => { gFetchPromise = null; throw err; });

  return gFetchPromise;
}

/* ── Main entry: ganti wilayah & render ── */
function fetchDataBPS() {
  const select = document.getElementById('wilayahBPS');
  if (!select) return;
  const kodeWilayah = select.value;
  gNamaWilayah = select.options[select.selectedIndex].text
    .replace(/📍\s*/g,'').replace(/🇮🇩\s*/g,'').trim();
  gIsNasional  = (kodeWilayah === '0000000');
  gActiveKolom = null;

  const sumEl = document.getElementById('bps-summary');
  if (sumEl) { sumEl.innerHTML = ''; sumEl.classList.add('hidden'); sumEl.classList.remove('grid'); }
  showBpsState('loading');

  ensureNasionalLoaded()
    .then(() => {
      gKolom = gKolomCache;

      if (gIsNasional) {
        gProvinsiList = gAllRowsCache;
      } else {
        const kodeInt = parseInt(kodeWilayah, 10);
        const kodeStr = String(kodeWilayah).replace(/^0+/, '');

        // Strategi 1: cocok integer
        let found = gAllRowsCache.filter(p => Number(p.kode_wilayah) === kodeInt);
        // Strategi 2: cocok string tanpa leading zeros
        if (!found.length) found = gAllRowsCache.filter(p => String(p.kode_wilayah).replace(/^0+/,'') === kodeStr);
        // Strategi 3: cocok nama label
        if (!found.length) {
          const nm = gNamaWilayah.toLowerCase();
          found = gAllRowsCache.filter(p => {
            const lb = (p.label || '').toLowerCase();
            return lb.includes(nm) || nm.includes(lb);
          });
        }

        console.log('[BPS] Filter provinsi:', found.length, 'baris | kode:', kodeInt);
        if (!found.length) throw new Error('Data tidak ditemukan untuk: ' + gNamaWilayah);
        gProvinsiList = found;
      }

      console.log('[BPS] gProvinsiList:', gProvinsiList.length, '| sample:', gProvinsiList[0]);
      renderSummaryCards();
      renderBpsChart();
      showBpsState('ready');

      const now = new Date();
      document.getElementById('bps-update-time').textContent =
        'Diperbarui: ' + now.toLocaleTimeString('id-ID',{hour:'2-digit',minute:'2-digit'});
    })
    .catch(err => {
      console.error('BPS Error:', err);
      showBpsState('error');
    });
}

document.addEventListener('DOMContentLoaded', () => { fetchDataBPS(); });

</script>
</body>
</html>