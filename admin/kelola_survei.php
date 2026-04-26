<?php
session_start();
include '../server/koneksi.php';

if (!isset($_SESSION['nik']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php"); exit();
}

$stmt = mysqli_prepare($koneksi,
    "SELECT s.id, s.email, s.kategori, s.q1, s.q2, s.q3, s.q4, s.saran, s.created_at,
            u.nama, u.no_hp
     FROM surveys s
     LEFT JOIN users u ON s.email = u.nik
     ORDER BY s.id ASC"
);
mysqli_stmt_execute($stmt);
$result_survei = mysqli_stmt_get_result($stmt);

$total_resp  = 0;
$sum         = [0, 0, 0, 0];
$data_survei = [];
$kat_count   = [];
$score_dist  = [1=>0, 2=>0, 3=>0, 4=>0, 5=>0];

while ($row = mysqli_fetch_assoc($result_survei)) {
    $rata = ($row['q1'] + $row['q2'] + $row['q3'] + $row['q4']) / 4;
    $row['rata_rata'] = number_format($rata, 2);
    $data_survei[]    = $row;
    $sum[0] += $row['q1']; $sum[1] += $row['q2'];
    $sum[2] += $row['q3']; $sum[3] += $row['q4'];
    $k = $row['kategori'];
    $kat_count[$k] = ($kat_count[$k] ?? 0) + 1;
    $r = (int)round($rata);
    $score_dist[$r] = ($score_dist[$r] ?? 0) + 1;
    $total_resp++;
}
mysqli_stmt_close($stmt);

$avg_global = $total_resp > 0 ? number_format(array_sum($sum) / ($total_resp * 4), 2) : '0.00';
$avg_q = $total_resp > 0
    ? array_map(fn($s) => number_format($s / $total_resp, 2), $sum)
    : ['0.00','0.00','0.00','0.00'];

$puas     = ($score_dist[4] + $score_dist[5]);
$pct_puas = $total_resp > 0 ? round($puas / $total_resp * 100) : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Laporan Survei — Admin Panel</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
  <!-- Chart.js dari CDN yang lebih stabil -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
  <style>
    *{font-family:'Plus Jakarta Sans',sans-serif;box-sizing:border-box;}
    body{background:linear-gradient(135deg,#f8fafc 0%,#eff6ff 100%);min-height:100vh;}
    .clay-nav{background:rgba(255,255,255,.95);backdrop-filter:blur(16px);
      border-bottom:1px solid #e2e8f0;box-shadow:0 2px 12px rgba(0,0,0,.04);}
    .card{background:#fff;border-radius:20px;box-shadow:0 8px 24px rgba(0,0,0,.06);
      border:1px solid #f1f5f9;padding:1.5rem;}
    .stat-box{background:#fff;border-radius:18px;border:1px solid #e2e8f0;
      padding:1.25rem 1.5rem;box-shadow:0 4px 14px rgba(0,0,0,.04);transition:transform .2s;}
    .stat-box:hover{transform:translateY(-2px);}

    /* Progress bars */
    .prog-track{background:#f1f5f9;border-radius:999px;overflow:hidden;height:10px;width:100%;}
    .prog-fill{height:100%;border-radius:999px;width:0%;
      transition:width 1.3s cubic-bezier(.4,0,.2,1);}
    .kat-track{background:#f1f5f9;border-radius:999px;overflow:hidden;height:8px;flex:1;}
    .kat-fill{height:100%;border-radius:999px;width:0%;
      transition:width 1.3s cubic-bezier(.4,0,.2,1);}

    /* Gauge */
    .gauge-wrap{position:relative;display:inline-flex;align-items:center;justify-content:center;}
    .gauge-wrap svg{transform:rotate(-90deg);}
    .gauge-label{position:absolute;text-align:center;pointer-events:none;}

    /* Table */
    .badge{display:inline-block;padding:3px 10px;border-radius:99px;font-size:.72rem;font-weight:700;}
    .badge-excellent{background:#dcfce7;color:#166534;}
    .badge-good{background:#fef9c3;color:#854d0e;}
    .badge-poor{background:#fee2e2;color:#991b1b;}
    .q-score{width:28px;height:28px;border-radius:8px;
      display:inline-flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:800;}
    .q5{background:#dcfce7;color:#166534;} .q4{background:#dbeafe;color:#1d4ed8;}
    .q3{background:#fef9c3;color:#854d0e;} .q12{background:#fee2e2;color:#991b1b;}
    .styled-table{width:100%;border-collapse:collapse;min-width:820px;}
    .styled-table thead tr{background:#f8fafc;}
    .styled-table th{padding:11px 14px;border-bottom:2px solid #e2e8f0;font-size:.78rem;
      font-weight:700;color:#475569;text-align:left;white-space:nowrap;}
    .styled-table td{padding:11px 14px;border-bottom:1px solid #f1f5f9;
      font-size:.84rem;vertical-align:middle;}
    .styled-table tbody tr:hover{background:#f8fafc;}
    @media print{.no-print{display:none!important;}}
  </style>
</head>
<body>

<!-- NAV -->
<nav class="clay-nav sticky top-0 z-50 no-print">
  <div class="max-w-7xl mx-auto px-4 py-3.5 flex items-center justify-between">
    <div class="flex items-center gap-3">
      <a href="dashboard.php"
        class="w-9 h-9 bg-gray-100 hover:bg-gray-200 rounded-xl flex items-center justify-center transition text-lg">←</a>
      <div>
        <p class="font-extrabold text-gray-800">Laporan Kepuasan Pasien</p>
        <p class="text-xs text-gray-400">Data Aktual · <?php echo $total_resp; ?> Responden</p>
      </div>
    </div>
    <div class="flex gap-2">
      <button onclick="exportCSV()"
        class="text-xs font-bold text-green-700 bg-green-50 hover:bg-green-100 border border-green-200 px-4 py-2 rounded-xl transition">
        📥 Export CSV
      </button>
      <button onclick="window.print()"
        class="text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded-xl transition">
        🖨️ Cetak
      </button>
    </div>
  </div>
</nav>

<main class="max-w-7xl mx-auto px-4 py-6 space-y-5">

  <!-- STAT BOXES -->
  <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
    <div class="stat-box flex items-center gap-3">
      <div class="w-12 h-12 rounded-2xl bg-blue-100 flex items-center justify-center text-2xl flex-shrink-0">👥</div>
      <div><p class="text-xs text-gray-400 font-bold">Total Responden</p>
        <h3 class="text-2xl font-extrabold text-gray-800"><?php echo $total_resp; ?></h3></div>
    </div>
    <div class="stat-box flex items-center gap-3">
      <div class="w-12 h-12 rounded-2xl bg-yellow-100 flex items-center justify-center text-2xl flex-shrink-0">⭐</div>
      <div><p class="text-xs text-gray-400 font-bold">Indeks Kepuasan</p>
        <h3 class="text-2xl font-extrabold text-gray-800"><?php echo $avg_global; ?><span class="text-xs text-gray-400">/5</span></h3></div>
    </div>
    <div class="stat-box flex items-center gap-3">
      <div class="w-12 h-12 rounded-2xl bg-green-100 flex items-center justify-center text-2xl flex-shrink-0">😊</div>
      <div><p class="text-xs text-gray-400 font-bold">Keramahan (Q1)</p>
        <h3 class="text-2xl font-extrabold text-gray-800"><?php echo $avg_q[0]; ?></h3></div>
    </div>
    <div class="stat-box flex items-center gap-3">
      <div class="w-12 h-12 rounded-2xl bg-purple-100 flex items-center justify-center text-2xl flex-shrink-0">🩺</div>
      <div><p class="text-xs text-gray-400 font-bold">Kualitas Medis (Q4)</p>
        <h3 class="text-2xl font-extrabold text-gray-800"><?php echo $avg_q[3]; ?></h3></div>
    </div>
  </div>

  <?php if ($total_resp > 0): ?>

  <!-- CHARTS ROW -->
  <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

    <!-- KIRI: Bar + Line Chart -->
    <div class="space-y-5">
      <!-- Bar Chart -->
      <div class="card">
        <h3 class="font-bold text-gray-700 mb-1">📊 Rata-rata Per Aspek Penilaian</h3>
        <p class="text-xs text-gray-400 mb-3">Skor 1–5 per aspek layanan</p>
        <div style="position:relative;height:200px;width:100%;">
          <canvas id="barChart"></canvas>
        </div>
      </div>

      <!-- Line Chart -->
      <div class="card">
        <h3 class="font-bold text-gray-700 mb-1">📉 Tren Kepuasan Per Pengisian</h3>
        <p class="text-xs text-gray-400 mb-3">Dari pengisian pertama hingga terbaru</p>
        <div style="position:relative;height:160px;width:100%;">
          <canvas id="lineChart"></canvas>
        </div>
      </div>
    </div>

    <!-- KANAN: Gauge + Progress Bars + Distribusi -->
    <div class="card space-y-5">

      <!-- Gauge -->
      <div>
        <h3 class="font-bold text-gray-700 mb-3">🎯 Tingkat Kepuasan Keseluruhan</h3>
        <div class="flex items-center gap-5">
          <div class="gauge-wrap flex-shrink-0">
            <svg width="120" height="120" viewBox="0 0 120 120">
              <circle cx="60" cy="60" r="48" fill="none" stroke="#e2e8f0" stroke-width="12"/>
              <circle id="gauge-arc" cx="60" cy="60" r="48" fill="none"
                stroke="#3b82f6" stroke-width="12" stroke-linecap="round"
                stroke-dasharray="301.59" stroke-dashoffset="301.59"
                style="transition:stroke-dashoffset 1.4s cubic-bezier(.4,0,.2,1);"/>
              <defs>
                <linearGradient id="gGrad" x1="0%" y1="0%" x2="100%" y2="0%">
                  <stop offset="0%" stop-color="#3b82f6"/>
                  <stop offset="100%" stop-color="#22c55e"/>
                </linearGradient>
              </defs>
            </svg>
            <div class="gauge-label">
              <p class="font-extrabold text-gray-800 text-2xl leading-none" id="gauge-num">0%</p>
              <p class="text-[9px] text-gray-400 font-bold mt-0.5">PUAS</p>
            </div>
          </div>
          <div class="flex-1 space-y-2 text-xs">
            <?php
            $dist_items = [
              [5,'Sangat Puas','#22c55e'],
              [4,'Puas','#3b82f6'],
              [3,'Cukup','#f59e0b'],
              [2,'Kurang','#f97316'],
              [1,'Sangat Kurang','#ef4444'],
            ];
            foreach ($dist_items as [$skor,$lbl,$col]): ?>
            <div class="flex items-center justify-between">
              <span class="flex items-center gap-1.5 font-semibold text-gray-600">
                <span class="w-2.5 h-2.5 rounded-full inline-block flex-shrink-0"
                  style="background:<?php echo $col; ?>"></span>
                <?php echo $lbl; ?>
              </span>
              <span class="font-bold text-gray-700"><?php echo $score_dist[$skor]; ?> org</span>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <hr class="border-gray-100"/>

      <!-- Progress Bars Per Aspek -->
      <div>
        <h3 class="font-bold text-gray-700 mb-3">📈 Progress Skor Per Aspek</h3>
        <div class="space-y-3">
          <?php
          $asp = [
            ['😊','Keramahan Petugas',   $avg_q[0],'#3b82f6'],
            ['⚡','Kecepatan Pelayanan', $avg_q[1],'#06b6d4'],
            ['✨','Kebersihan Fasilitas',$avg_q[2],'#22c55e'],
            ['🩺','Kualitas Medis',      $avg_q[3],'#a855f7'],
          ];
          foreach ($asp as [$ic,$lb,$vl,$cl]):
            $pct = round($vl / 5 * 100);
            $grade = $vl >= 4.5?'Luar Biasa':($vl>=4?'Baik':($vl>=3?'Cukup':'Perlu Perhatian'));
          ?>
          <div>
            <div class="flex justify-between items-center mb-1.5">
              <span class="text-sm font-semibold text-gray-600"><?php echo "$ic $lb"; ?></span>
              <div class="flex items-center gap-2">
                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full"
                  style="background:<?php echo $cl; ?>22;color:<?php echo $cl; ?>">
                  <?php echo $grade; ?>
                </span>
                <span class="text-sm font-extrabold text-gray-800"><?php echo $vl; ?>/5</span>
              </div>
            </div>
            <div class="prog-track">
              <div class="prog-fill animate-bar" data-w="<?php echo $pct; ?>%"
                style="background:linear-gradient(90deg,<?php echo $cl; ?>88,<?php echo $cl; ?>);"></div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <hr class="border-gray-100"/>

      <!-- Distribusi Kategori -->
      <div>
        <h3 class="font-bold text-gray-700 mb-3">👥 Distribusi Kategori Responden</h3>
        <div class="space-y-2.5">
          <?php
          $kc = ['Mahasiswa'=>'#3b82f6','Dosen'=>'#a855f7','Karyawan'=>'#f59e0b','Umum'=>'#22c55e'];
          arsort($kat_count);
          foreach ($kat_count as $kat => $cnt):
            $kpct = round($cnt / $total_resp * 100);
            $col  = $kc[$kat] ?? '#94a3b8';
          ?>
          <div>
            <div class="flex justify-between items-center mb-1">
              <span class="text-xs font-semibold text-gray-600"><?php echo htmlspecialchars($kat); ?></span>
              <span class="text-xs font-bold text-gray-500"><?php echo $cnt; ?> org (<?php echo $kpct; ?>%)</span>
            </div>
            <div class="flex items-center gap-2">
              <div class="kat-track">
                <div class="kat-fill animate-bar" data-w="<?php echo $kpct; ?>%"
                  style="background:<?php echo $col; ?>;"></div>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

    </div><!-- end kanan -->
  </div>

  <?php endif; ?>

  <!-- DATA TABLE -->
  <div class="card">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
      <div>
        <h2 class="font-bold text-gray-800">Detail Feedback Responden</h2>
        <p class="text-xs text-gray-400">Q1=Keramahan · Q2=Kecepatan · Q3=Kebersihan · Q4=Kualitas Medis</p>
      </div>
      <input type="text" id="search-input" placeholder="🔍 Cari nama / NIK..."
        class="border border-gray-200 rounded-xl px-3 py-2 text-sm outline-none focus:border-blue-400 w-full sm:w-52 no-print"/>
    </div>
    <div class="overflow-x-auto">
      <table class="styled-table" id="survey-table">
        <thead>
          <tr>
            <th>No</th><th>Responden</th><th>Kategori</th>
            <th class="text-center">Q1</th><th class="text-center">Q2</th>
            <th class="text-center">Q3</th><th class="text-center">Q4</th>
            <th class="text-center">Rata-rata</th><th>Saran</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($total_resp > 0):
            $no = 1;
            function qClass($v){return $v>=5?'q5':($v>=4?'q4':($v>=3?'q3':'q12'));}
            foreach ($data_survei as $row):
              $rata  = $row['rata_rata'];
              $badge = $rata>=4?'excellent':($rata>=3?'good':'poor');
              $label = $rata>=4?'😊 Puas':($rata>=3?'😐 Cukup':'😞 Kurang');
          ?>
          <tr>
            <td class="text-gray-400"><?php echo $no++; ?></td>
            <td>
              <p class="font-bold text-gray-800 text-sm"><?php echo htmlspecialchars($row['nama'] ?? 'User Dihapus'); ?></p>
              <p class="text-xs text-gray-400 font-mono"><?php echo htmlspecialchars($row['email']); ?></p>
            </td>
            <td><span class="bg-gray-100 text-gray-600 px-3 py-1 rounded-full text-xs font-bold border border-gray-200">
              <?php echo htmlspecialchars($row['kategori']); ?></span></td>
            <td class="text-center"><span class="q-score <?php echo qClass((int)$row['q1']); ?>"><?php echo $row['q1']; ?></span></td>
            <td class="text-center"><span class="q-score <?php echo qClass((int)$row['q2']); ?>"><?php echo $row['q2']; ?></span></td>
            <td class="text-center"><span class="q-score <?php echo qClass((int)$row['q3']); ?>"><?php echo $row['q3']; ?></span></td>
            <td class="text-center"><span class="q-score <?php echo qClass((int)$row['q4']); ?>"><?php echo $row['q4']; ?></span></td>
            <td class="text-center">
              <span class="badge badge-<?php echo $badge; ?>"><?php echo $label; ?></span>
              <div class="text-xs font-bold text-gray-400 mt-0.5"><?php echo $rata; ?>/5</div>
            </td>
            <td class="text-gray-500 text-xs italic max-w-xs">
              <?php echo $row['saran']
                ? '"'.htmlspecialchars(mb_strimwidth($row['saran'],0,70,'…')).'"'
                : '<span class="text-gray-300">—</span>'; ?>
            </td>
          </tr>
          <?php endforeach; else: ?>
          <tr><td colspan="9" class="text-center py-10 text-gray-400 italic">Belum ada data survei.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</main>

<script>
/* Tunggu DOM + Chart.js benar-benar siap */
window.addEventListener('load', function () {

<?php if ($total_resp > 0): ?>

  /* ── 1. BAR CHART ── */
  try {
    const barCtx = document.getElementById('barChart').getContext('2d');
    new Chart(barCtx, {
      type: 'bar',
      data: {
        labels: ['😊 Keramahan','⚡ Kecepatan','✨ Kebersihan','🩺 Kualitas Medis'],
        datasets: [{
          data: [<?php echo implode(',', $avg_q); ?>],
          backgroundColor: ['rgba(59,130,246,.75)','rgba(6,182,212,.75)','rgba(34,197,94,.75)','rgba(168,85,247,.75)'],
          borderColor:      ['#2563eb','#0891b2','#16a34a','#7c3aed'],
          borderWidth: 2, borderRadius: 10, borderSkipped: false,
        }]
      },
      options: {
        responsive: true, maintainAspectRatio: false,
        animation: { duration: 1000, easing: 'easeOutQuart' },
        scales: {
          y: { min:0, max:5, ticks:{ stepSize:1, font:{size:11}, color:'#94a3b8' }, grid:{color:'rgba(0,0,0,.05)'} },
          x: { ticks:{ font:{size:11}, color:'#374151' }, grid:{display:false} }
        },
        plugins: {
          legend: { display:false },
          tooltip: { callbacks:{ label: c => ` Skor: ${c.parsed.y} / 5` } }
        }
      }
    });
  } catch(e){ console.error('Bar chart error:', e); }

  /* ── 2. LINE CHART ── */
  try {
    const lineCtx = document.getElementById('lineChart').getContext('2d');
    const trendData = [<?php echo implode(',', array_map(fn($r)=>$r['rata_rata'], $data_survei)); ?>];
    new Chart(lineCtx, {
      type: 'line',
      data: {
        labels: trendData.map((_,i) => `#${i+1}`),
        datasets: [{
          label: 'Rata-rata',
          data: trendData,
          borderColor: '#3b82f6',
          backgroundColor: 'rgba(59,130,246,.08)',
          pointBackgroundColor: '#2563eb',
          pointRadius: 5, pointHoverRadius: 7,
          borderWidth: 2.5, fill: true, tension: 0.4,
        }]
      },
      options: {
        responsive: true, maintainAspectRatio: false,
        animation: { duration: 1200, easing: 'easeOutQuart' },
        scales: {
          y: { min:0, max:5, ticks:{ stepSize:1, font:{size:10}, color:'#94a3b8' }, grid:{color:'rgba(0,0,0,.04)'} },
          x: { ticks:{ font:{size:10}, color:'#94a3b8' }, grid:{display:false} }
        },
        plugins: {
          legend: { display:false },
          tooltip: { callbacks:{ label: c => ` Nilai: ${c.parsed.y} / 5` } }
        }
      }
    });
  } catch(e){ console.error('Line chart error:', e); }

  /* ── 3. GAUGE ANIMASI ── */
  (function(){
    const arc      = document.getElementById('gauge-arc');
    const numEl    = document.getElementById('gauge-num');
    const target   = <?php echo $pct_puas; ?>;
    const circumf  = 301.59; // 2 * PI * 48
    arc.style.stroke = 'url(#gGrad)'; // pakai gradient
    let cur = 0;
    const step = target / 60;
    const t = setInterval(() => {
      cur = Math.min(cur + step, target);
      arc.style.strokeDashoffset = circumf - (circumf * cur / 100);
      numEl.textContent = Math.round(cur) + '%';
      if (cur >= target) clearInterval(t);
    }, 16);
  })();

  /* ── 4. PROGRESS BAR ANIMASI (IntersectionObserver) ── */
  const bars = document.querySelectorAll('.animate-bar');
  const observer = new IntersectionObserver(entries => {
    entries.forEach(e => {
      if (e.isIntersecting) {
        // Delay kecil agar terasa smooth
        setTimeout(() => {
          e.target.style.width = e.target.getAttribute('data-w');
        }, 200);
        observer.unobserve(e.target);
      }
    });
  }, { threshold: 0.2 });
  bars.forEach(b => observer.observe(b));

<?php endif; ?>

  /* ── 5. SEARCH TABLE ── */
  document.getElementById('search-input')?.addEventListener('input', function(){
    const q = this.value.toLowerCase();
    document.querySelectorAll('#survey-table tbody tr').forEach(row => {
      row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
  });

}); // end window.load

/* ── 6. EXPORT CSV ── */
function exportCSV(){
  const headers = ['No','Nama','NIK','Kategori','Q1','Q2','Q3','Q4','Rata-rata','Saran'];
  const rows = [];
  document.querySelectorAll('#survey-table tbody tr').forEach((row,i) => {
    const c = row.querySelectorAll('td');
    if (c.length < 2) return;
    rows.push([
      i+1,
      c[1].querySelector('p')?.textContent.trim()||'',
      c[1].querySelectorAll('p')[1]?.textContent.trim()||'',
      c[2].textContent.trim(),
      c[3].textContent.trim(), c[4].textContent.trim(),
      c[5].textContent.trim(), c[6].textContent.trim(),
      c[7].querySelector('div')?.textContent.trim()||'',
      '"'+(c[8].textContent.trim().replace(/"/g,'""'))+'"',
    ].join(','));
  });
  const csv  = [headers.join(','),...rows].join('\n');
  const blob = new Blob(['\uFEFF'+csv],{type:'text/csv;charset=utf-8;'});
  const url  = URL.createObjectURL(blob);
  const a    = Object.assign(document.createElement('a'),
    {href:url,download:'laporan_survei_uns.csv'});
  a.click(); URL.revokeObjectURL(url);
}
</script>
</body>
</html>