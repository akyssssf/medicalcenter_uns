<?php
require_once __DIR__ . "/../server/bootstrap.php";
if (!isset($_SESSION['nik']) || $_SESSION['role'] !== 'admin') {
    header("Location: /login.php"); exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_id'])) {
    $id_hapus = (int)$_POST['hapus_id'];
    $stmt_hapus = mysqli_prepare($koneksi, "DELETE FROM users WHERE id = ? AND role = 'user'");
    mysqli_stmt_bind_param($stmt_hapus, "i", $id_hapus);
    $ok = mysqli_stmt_execute($stmt_hapus);
    mysqli_stmt_close($stmt_hapus);
    $_SESSION['flash'] = $ok
        ? ['type'=>'success','title'=>'Berhasil','message'=>'Data pasien berhasil dihapus.']
        : ['type'=>'error',  'title'=>'Gagal',   'message'=>'Gagal menghapus data pasien.'];
    header("Location: /admin/kelola_user.php"); exit();
}

$result_users = mysqli_query($koneksi, "SELECT * FROM users WHERE role = 'user' ORDER BY id DESC");
$total = mysqli_num_rows($result_users);
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Kelola Pasien — Admin Panel</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    *{font-family:'Plus Jakarta Sans',sans-serif;box-sizing:border-box;}
    body{background:linear-gradient(135deg,#f8fafc 0%,#eff6ff 100%);min-height:100vh;}

    /* Nav */
    .clay-nav{background:rgba(255,255,255,.97);backdrop-filter:blur(16px);
      border-bottom:1px solid #e2e8f0;box-shadow:0 2px 10px rgba(0,0,0,.04);
      position:sticky;top:0;z-index:50;}
    .nav-inner{max-width:960px;margin:0 auto;padding:0 20px;height:58px;
      display:flex;align-items:center;justify-content:space-between;gap:12px;}
    .back-btn{width:36px;height:36px;background:#f1f5f9;border-radius:10px;
      display:flex;align-items:center;justify-content:center;text-decoration:none;
      font-size:1rem;flex-shrink:0;transition:background .2s;}
    .back-btn:hover{background:#e2e8f0;}

    /* Card */
    .admin-card{background:#fff;border-radius:20px;box-shadow:0 8px 24px rgba(0,0,0,.06);
      border:1px solid #f1f5f9;padding:1.25rem;}

    /* Table */
    .tbl-wrap{overflow-x:auto;-webkit-overflow-scrolling:touch;}
    .styled-table{width:100%;border-collapse:collapse;min-width:560px;}
    .styled-table thead tr{background:#f8fafc;}
    .styled-table th{padding:11px 14px;border-bottom:2px solid #e2e8f0;font-size:.78rem;
      font-weight:700;color:#475569;text-align:left;white-space:nowrap;}
    .styled-table td{padding:11px 14px;border-bottom:1px solid #f1f5f9;
      font-size:.84rem;vertical-align:middle;}
    .styled-table tbody tr:hover{background:#f8fafc;}
    .btn-del{background:#fef2f2;color:#ef4444;border:1.5px solid #fecaca;
      padding:6px 14px;border-radius:10px;font-size:.75rem;font-weight:700;
      cursor:pointer;transition:.2s;border:none;}
    .btn-del:hover{background:#ef4444;color:white;}

    /* Search + badge */
    .search-input{border:1.5px solid #e2e8f0;border-radius:12px;padding:8px 14px;
      font-size:.85rem;outline:none;transition:border-color .2s;width:100%;}
    .search-input:focus{border-color:#3b82f6;}

    /* ── TABLET (≤768px) ── */
    @media(max-width:768px){
      .nav-inner{padding:0 14px;height:52px;}
      .nav-title{font-size:.88rem;}
      .nav-sub{font-size:.68rem;}
      .admin-card{padding:1rem;border-radius:16px;}
      .header-row{flex-direction:column;align-items:flex-start;gap:10px;}
      .header-right{width:100%;}
      .count-badge{display:none;}
      .styled-table th{padding:9px 10px;font-size:.73rem;}
      .styled-table td{padding:9px 10px;font-size:.8rem;}
    }

    /* ── MOBILE (≤480px) ── */
    @media(max-width:480px){
      .nav-inner{padding:0 10px;height:48px;}
      .back-btn{width:32px;height:32px;font-size:.9rem;}
      .admin-card{padding:.85rem;border-radius:14px;}
      /* Sembunyikan kolom No & No WA di mobile */
      .styled-table th:nth-child(1),
      .styled-table td:nth-child(1),
      .styled-table th:nth-child(4),
      .styled-table td:nth-child(4){display:none;}
      .styled-table th{padding:8px 8px;font-size:.7rem;}
      .styled-table td{padding:8px 8px;font-size:.76rem;}
      .btn-del{padding:5px 10px;font-size:.7rem;}
      .nik-cell{font-size:.65rem !important;letter-spacing:0 !important;}
    }
  </style>
</head>
<body>
  <nav class="clay-nav">
    <div class="nav-inner">
      <div style="display:flex;align-items:center;gap:10px;">
        <a href="/admin/dashboard.php" class="back-btn">←</a>
        <div>
          <p class="nav-title" style="font-weight:800;color:#1e293b;line-height:1.2;">Kelola Data Pasien</p>
          <p class="nav-sub" style="font-size:.72rem;color:#94a3b8;">Total <?php echo $total; ?> pasien terdaftar</p>
        </div>
      </div>
      <span style="font-size:.82rem;font-weight:700;color:#2563eb;">
        Admin <?php echo htmlspecialchars(explode(' ',$_SESSION['nama'])[0]); ?>
      </span>
    </div>
  </nav>

  <main style="max-width:960px;margin:0 auto;padding:20px 20px 60px;">
    <div class="admin-card">

      <!-- Header -->
      <div class="header-row" style="display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:16px;">
        <div>
          <h2 style="font-weight:800;font-size:.95rem;color:#1e293b;">Daftar Pasien Terdaftar</h2>
          <p style="font-size:.72rem;color:#94a3b8;margin-top:2px;">Role: user · Diurutkan terbaru</p>
        </div>
        <div class="header-right" style="display:flex;gap:8px;align-items:center;">
          <input type="text" id="search-input" placeholder="🔍 Cari nama / NIK..." class="search-input" style="max-width:220px;"/>
          <span class="count-badge" style="font-size:.78rem;font-weight:700;color:#2563eb;background:#eff6ff;
            border:1px solid #bfdbfe;padding:7px 14px;border-radius:10px;white-space:nowrap;flex-shrink:0;">
            <?php echo $total; ?> Pasien
          </span>
        </div>
      </div>

      <!-- Table -->
      <div class="tbl-wrap">
        <table class="styled-table" id="user-table">
          <thead>
            <tr>
              <th>No.</th>
              <th>NIK</th>
              <th>Nama Lengkap</th>
              <th>No. WhatsApp</th>
              <th style="text-align:center;">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php $no = 1;
            if ($total > 0) {
                while ($row = mysqli_fetch_assoc($result_users)) { ?>
            <tr>
              <td style="color:#94a3b8;font-weight:600;"><?php echo $no++; ?></td>
              <td class="nik-cell" style="font-family:monospace;color:#475569;font-size:.78rem;letter-spacing:.05em;">
                <?php echo htmlspecialchars($row['nik']); ?>
              </td>
              <td style="font-weight:700;color:#1e293b;"><?php echo htmlspecialchars($row['nama']); ?></td>
              <td style="color:#64748b;"><?php echo htmlspecialchars($row['no_hp']); ?></td>
              <td style="text-align:center;">
                <button onclick="hapusUser(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars(addslashes($row['nama'])); ?>')"
                  class="btn-del">🗑️ Hapus</button>
              </td>
            </tr>
            <?php }} else { ?>
            <tr><td colspan="5" style="text-align:center;padding:40px;color:#94a3b8;font-style:italic;">
              Belum ada pasien terdaftar.
            </td></tr>
            <?php } ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>

  <form id="form-hapus" method="POST" action="/admin/kelola_user.php" style="display:none;">
    <input type="hidden" name="hapus_id" id="hapus-id-input">
  </form>

  <script>
    <?php if ($flash): ?>
    Swal.fire({
      icon: '<?php echo $flash['type']; ?>',
      title: '<?php echo htmlspecialchars($flash['title']); ?>',
      text: '<?php echo htmlspecialchars($flash['message']); ?>',
      confirmButtonColor: '#2563eb',
      timer: 3000, timerProgressBar: true,
    });
    <?php endif; ?>

    function hapusUser(id, nama) {
      Swal.fire({
        title: 'Hapus Pasien?',
        html: `Yakin ingin menghapus data <strong>${nama}</strong>?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: '🗑️ Ya, Hapus',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
      }).then(result => {
        if (result.isConfirmed) {
          document.getElementById('hapus-id-input').value = id;
          document.getElementById('form-hapus').submit();
        }
      });
    }

    document.getElementById('search-input').addEventListener('input', function() {
      const q = this.value.toLowerCase();
      document.querySelectorAll('#user-table tbody tr').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
      });
    });
  </script>
</body>
</html>
