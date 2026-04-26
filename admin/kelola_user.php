<?php
session_start();
include '../server/koneksi.php';

if (!isset($_SESSION['nik']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php"); exit();
}

// ── Hapus User (via POST untuk keamanan CSRF-like) ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_id'])) {
    $id_hapus = (int)$_POST['hapus_id'];
    $stmt_hapus = mysqli_prepare($koneksi, "DELETE FROM users WHERE id = ? AND role = 'user'");
    mysqli_stmt_bind_param($stmt_hapus, "i", $id_hapus);
    $ok = mysqli_stmt_execute($stmt_hapus);
    mysqli_stmt_close($stmt_hapus);
    $_SESSION['flash'] = $ok
        ? ['type'=>'success','title'=>'Berhasil','message'=>'Data pasien berhasil dihapus.']
        : ['type'=>'error',  'title'=>'Gagal',   'message'=>'Gagal menghapus data pasien.'];
    header("Location: kelola_user.php"); exit();
}

// ── Ambil Data Pasien ──
$result_users = mysqli_query($koneksi, "SELECT * FROM users WHERE role = 'user' ORDER BY id DESC");
$total = mysqli_num_rows($result_users);

// Flash message
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
    .clay-nav{background:rgba(255,255,255,.95);backdrop-filter:blur(16px);border-bottom:1px solid #e2e8f0;box-shadow:0 2px 10px rgba(0,0,0,.04);}
    .admin-card{background:#fff;border-radius:24px;box-shadow:0 10px 30px rgba(0,0,0,.06);border:1px solid #f1f5f9;padding:1.5rem;}
    .styled-table{width:100%;border-collapse:collapse;}
    .styled-table thead tr{background:#f8fafc;}
    .styled-table th{padding:12px 16px;border-bottom:2px solid #e2e8f0;font-size:.8rem;font-weight:700;color:#475569;text-align:left;white-space:nowrap;}
    .styled-table td{padding:12px 16px;border-bottom:1px solid #f1f5f9;font-size:.875rem;vertical-align:middle;}
    .styled-table tbody tr:hover{background:#f8fafc;}
    .btn-del{background:#fef2f2;color:#ef4444;border:1.5px solid #fecaca;padding:6px 14px;border-radius:10px;
      font-size:.75rem;font-weight:700;cursor:pointer;transition:.2s;border:none;}
    .btn-del:hover{background:#ef4444;color:white;}
  </style>
</head>
<body>
  <nav class="clay-nav sticky top-0 z-50">
    <div class="max-w-6xl mx-auto px-4 py-3.5 flex items-center justify-between">
      <div class="flex items-center gap-3">
        <a href="dashboard.php" class="w-9 h-9 bg-gray-100 hover:bg-gray-200 rounded-xl flex items-center justify-center transition text-base" title="Kembali">←</a>
        <div>
          <p class="font-extrabold text-gray-800">Kelola Data Pasien</p>
          <p class="text-xs text-gray-400">Total <?php echo $total; ?> pasien terdaftar</p>
        </div>
      </div>
      <span class="text-sm font-bold text-blue-600">Admin <?php echo htmlspecialchars($_SESSION['nama']); ?></span>
    </div>
  </nav>

  <main class="max-w-6xl mx-auto px-4 py-8">
    <div class="admin-card">
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-5">
        <div>
          <h2 class="font-bold text-gray-800">Daftar Pasien Terdaftar</h2>
          <p class="text-xs text-gray-400">Role: user · Diurutkan terbaru</p>
        </div>
        <div class="flex gap-2 w-full sm:w-auto">
          <input type="text" id="search-input" placeholder="🔍 Cari nama / NIK..."
            class="border border-gray-200 rounded-xl px-3 py-2 text-sm outline-none focus:border-blue-400 flex-1 sm:w-52"/>
          <span class="text-sm font-bold text-blue-600 bg-blue-50 border border-blue-100 px-4 py-2 rounded-xl whitespace-nowrap">
            <?php echo $total; ?> Pasien
          </span>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="styled-table" id="user-table">
          <thead>
            <tr>
              <th>No.</th>
              <th>NIK</th>
              <th>Nama Lengkap</th>
              <th>No. WhatsApp</th>
              <th class="text-center">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $no = 1;
            if ($total > 0) {
                while ($row = mysqli_fetch_assoc($result_users)) { ?>
            <tr>
              <td class="text-gray-400 font-medium"><?php echo $no++; ?></td>
              <td class="font-mono text-gray-700 tracking-wider text-xs"><?php echo htmlspecialchars($row['nik']); ?></td>
              <td class="font-semibold text-gray-800"><?php echo htmlspecialchars($row['nama']); ?></td>
              <td class="text-gray-600"><?php echo htmlspecialchars($row['no_hp']); ?></td>
              <td class="text-center">
                <button onclick="hapusUser(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars(addslashes($row['nama'])); ?>')"
                  class="btn-del">🗑️ Hapus</button>
              </td>
            </tr>
            <?php }} else { ?>
            <tr><td colspan="5" class="text-center py-10 text-gray-400 italic">Belum ada pasien terdaftar.</td></tr>
            <?php } ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>

  <!-- Hidden form untuk POST delete -->
  <form id="form-hapus" method="POST" action="kelola_user.php" style="display:none;">
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
        html: `Yakin ingin menghapus data <strong>${nama}</strong>?<br><small class="text-gray-400">Tindakan ini tidak bisa dibatalkan.</small>`,
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
