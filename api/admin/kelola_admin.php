<?php
require_once __DIR__ . "/../server/bootstrap.php";
if (!isset($_SESSION['nik']) || $_SESSION['role'] !== 'admin') {
    header("Location: /login.php"); exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_admin'])) {
    $nik   = preg_replace('/[^0-9]/', '', trim($_POST['nik'] ?? ''));
    $nama  = trim($_POST['nama'] ?? '');
    $no_hp = preg_replace('/[^0-9]/', '', trim($_POST['no_hp'] ?? ''));
    $pw    = $_POST['password'] ?? '';
    $errors = [];
    if (strlen($nik) !== 16)   $errors[] = 'NIK harus 16 digit';
    if (strlen($nama) < 3)     $errors[] = 'Nama terlalu pendek';
    if (strlen($no_hp) < 9)    $errors[] = 'No HP tidak valid';
    if (strlen($pw) < 6 || !preg_match('/[0-9]/', $pw) || !preg_match('/[A-Z]/', $pw))
        $errors[] = 'Password tidak memenuhi syarat';
    if (empty($errors)) {
        $cek = mysqli_prepare($koneksi, "SELECT id FROM users WHERE nik = ?");
        mysqli_stmt_bind_param($cek, "s", $nik);
        mysqli_stmt_execute($cek);
        mysqli_stmt_store_result($cek);
        if (mysqli_stmt_num_rows($cek) > 0) $errors[] = 'NIK sudah terdaftar';
        mysqli_stmt_close($cek);
    }
    if (empty($errors)) {
        $hash = password_hash($pw, PASSWORD_BCRYPT, ['cost'=>12]);
        $ins  = mysqli_prepare($koneksi, "INSERT INTO users (nik, nama, no_hp, password, role) VALUES (?, ?, ?, ?, 'admin')");
        mysqli_stmt_bind_param($ins, "ssss", $nik, $nama, $no_hp, $hash);
        $_SESSION['flash'] = mysqli_stmt_execute($ins)
            ? ['type'=>'success','title'=>'Admin Ditambahkan','message'=>"Akun admin $nama berhasil dibuat."]
            : ['type'=>'error','title'=>'Gagal','message'=>'Terjadi kesalahan sistem.'];
        mysqli_stmt_close($ins);
    } else {
        $_SESSION['flash'] = ['type'=>'error','title'=>'Validasi Gagal','message'=>implode('. ', $errors).'.'];
    }
    header("Location: /admin/kelola_admin.php"); exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_id'])) {
    $id_hapus = (int)$_POST['hapus_id'];
    $self = mysqli_prepare($koneksi, "SELECT nik FROM users WHERE id = ?");
    mysqli_stmt_bind_param($self, "i", $id_hapus);
    mysqli_stmt_execute($self);
    $data_self = mysqli_fetch_assoc(mysqli_stmt_get_result($self));
    mysqli_stmt_close($self);
    if ($data_self && $data_self['nik'] === $_SESSION['nik']) {
        $_SESSION['flash'] = ['type'=>'error','title'=>'Tidak Diizinkan','message'=>'Anda tidak bisa menghapus akun sendiri!'];
    } else {
        $del = mysqli_prepare($koneksi, "DELETE FROM users WHERE id = ? AND role = 'admin'");
        mysqli_stmt_bind_param($del, "i", $id_hapus);
        mysqli_stmt_execute($del);
        mysqli_stmt_close($del);
        $_SESSION['flash'] = ['type'=>'success','title'=>'Admin Dihapus','message'=>'Akses admin berhasil dicabut.'];
    }
    header("Location: /admin/kelola_admin.php"); exit();
}

$result_admin = mysqli_query($koneksi, "SELECT * FROM users WHERE role = 'admin' ORDER BY nama ASC");
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Kelola Admin — UNS Medical Center</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    *{font-family:'Plus Jakarta Sans',sans-serif;box-sizing:border-box;}
    body{background:linear-gradient(135deg,#f8fafc 0%,#eff6ff 100%);min-height:100vh;}

    .clay-nav{background:rgba(255,255,255,.97);backdrop-filter:blur(16px);
      border-bottom:1px solid #e2e8f0;box-shadow:0 2px 10px rgba(0,0,0,.04);
      position:sticky;top:0;z-index:50;}
    .nav-inner{max-width:960px;margin:0 auto;padding:0 20px;height:58px;
      display:flex;align-items:center;gap:12px;}
    .back-btn{width:36px;height:36px;background:#f1f5f9;border-radius:10px;
      display:flex;align-items:center;justify-content:center;text-decoration:none;
      font-size:1rem;flex-shrink:0;transition:background .2s;}
    .back-btn:hover{background:#e2e8f0;}

    .admin-card{background:#fff;border-radius:20px;box-shadow:0 8px 24px rgba(0,0,0,.06);
      border:1px solid #f1f5f9;padding:1.5rem;}
    .clay-input{background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:12px;
      padding:0.65rem 1rem;width:100%;outline:none;font-size:.88rem;transition:border-color .2s;}
    .clay-input:focus{border-color:#3b82f6;box-shadow:0 0 0 3px rgba(59,130,246,.1);}
    .clay-label{font-size:.75rem;font-weight:700;color:#475569;display:block;margin-bottom:5px;}
    .btn-primary{background:linear-gradient(135deg,#3b82f6,#2563eb);color:white;font-weight:700;
      padding:0.7rem 1.25rem;border-radius:12px;cursor:pointer;border:none;width:100%;
      font-size:.88rem;transition:.2s;}
    .btn-primary:hover{transform:translateY(-1px);box-shadow:0 4px 12px rgba(37,99,235,.3);}

    /* Admin list item */
    .admin-item{display:flex;align-items:center;justify-content:space-between;
      padding:12px 0;border-bottom:1px solid #f1f5f9;}
    .admin-item:last-child{border-bottom:none;padding-bottom:0;}
    .admin-avatar{width:42px;height:42px;border-radius:12px;background:#f5f3ff;
      display:flex;align-items:center;justify-content:center;font-size:1.2rem;flex-shrink:0;}

    /* Grid layout */
    .main-grid{display:grid;grid-template-columns:1fr 2fr;gap:20px;}

    /* ── TABLET (≤768px) ── */
    @media(max-width:768px){
      .nav-inner{padding:0 14px;height:52px;}
      .main-grid{grid-template-columns:1fr;gap:14px;}
      .admin-card{padding:1.1rem;border-radius:16px;}
    }

    /* ── MOBILE (≤480px) ── */
    @media(max-width:480px){
      .nav-inner{padding:0 10px;height:48px;}
      .back-btn{width:32px;height:32px;}
      .admin-card{padding:.9rem;border-radius:14px;}
      .clay-input{padding:.55rem .85rem;font-size:.82rem;}
      .admin-avatar{width:36px;height:36px;border-radius:10px;font-size:1rem;}
      .admin-item{padding:10px 0;gap:8px;}
    }
  </style>
</head>
<body>
  <nav class="clay-nav">
    <div class="nav-inner">
      <a href="/admin/dashboard.php" class="back-btn">←</a>
      <div>
        <p style="font-weight:800;font-size:.92rem;color:#1e293b;line-height:1.2;">Manajemen Admin</p>
        <p style="font-size:.7rem;color:#94a3b8;">Tambah atau cabut akses administrator</p>
      </div>
    </div>
  </nav>

  <main style="max-width:960px;margin:0 auto;padding:20px 20px 60px;">
    <div class="main-grid">

      <!-- FORM TAMBAH -->
      <div class="admin-card" style="height:fit-content;">
        <h2 style="font-weight:800;font-size:.92rem;color:#1e293b;margin-bottom:16px;">➕ Tambah Admin Baru</h2>
        <form action="" method="POST" style="display:flex;flex-direction:column;gap:12px;">
          <div>
            <label class="clay-label">NIK (16 Digit)</label>
            <input type="text" name="nik" maxlength="16" inputmode="numeric"
              oninput="this.value=this.value.replace(/[^0-9]/g,'')"
              class="clay-input" placeholder="Masukkan NIK" required/>
          </div>
          <div>
            <label class="clay-label">Nama Lengkap</label>
            <input type="text" name="nama" class="clay-input" placeholder="Nama Admin" required/>
          </div>
          <div>
            <label class="clay-label">No. WhatsApp</label>
            <input type="text" name="no_hp" inputmode="numeric"
              oninput="this.value=this.value.replace(/[^0-9]/g,'')"
              class="clay-input" placeholder="08xxx" required/>
          </div>
          <div>
            <label class="clay-label">Password</label>
            <input type="password" name="password" class="clay-input"
              placeholder="Min 6 kar (angka + huruf besar)" required autocomplete="new-password"/>
          </div>
          <button type="submit" name="tambah_admin" class="btn-primary" style="margin-top:4px;">
            💾 Simpan Akun Admin
          </button>
        </form>
      </div>

      <!-- DAFTAR ADMIN -->
      <div class="admin-card">
        <h2 style="font-weight:800;font-size:.92rem;color:#1e293b;margin-bottom:14px;">🛡️ Daftar Admin Aktif</h2>
        <div>
          <?php while ($row = mysqli_fetch_assoc($result_admin)):
            $isSelf = $row['nik'] === $_SESSION['nik']; ?>
          <div class="admin-item">
            <div style="display:flex;align-items:center;gap:12px;flex:1;min-width:0;">
              <div class="admin-avatar"><?php echo $isSelf ? '👑' : '🛡️'; ?></div>
              <div style="min-width:0;">
                <p style="font-weight:700;font-size:.88rem;color:#1e293b;
                  white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                  <?php echo htmlspecialchars($row['nama']); ?>
                </p>
                <p style="font-size:.68rem;color:#94a3b8;font-family:monospace;
                  white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                  <?php echo htmlspecialchars($row['nik']); ?>
                </p>
              </div>
            </div>
            <?php if ($isSelf): ?>
              <span style="font-size:.72rem;font-weight:700;color:#2563eb;background:#eff6ff;
                padding:5px 12px;border-radius:999px;flex-shrink:0;">Akun Anda</span>
            <?php else: ?>
              <button onclick="hapusAdmin(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars(addslashes($row['nama'])); ?>')"
                style="font-size:.72rem;font-weight:700;color:#ef4444;background:transparent;
                border:none;cursor:pointer;padding:5px 10px;border-radius:8px;
                transition:background .2s;flex-shrink:0;"
                onmouseover="this.style.background='#fef2f2'"
                onmouseout="this.style.background='transparent'">
                Hapus Akses
              </button>
            <?php endif; ?>
          </div>
          <?php endwhile; ?>
        </div>
      </div>

    </div>
  </main>

  <form id="form-hapus" method="POST" action="/admin/kelola_admin.php" style="display:none;">
    <input type="hidden" name="hapus_id" id="hapus-id-input">
  </form>

  <script>
    <?php if ($flash): ?>
    Swal.fire({
      icon: '<?php echo $flash['type']; ?>',
      title: '<?php echo htmlspecialchars($flash['title']); ?>',
      text: '<?php echo htmlspecialchars($flash['message']); ?>',
      confirmButtonColor: '#2563eb',
      timer: 3500, timerProgressBar: true,
    });
    <?php endif; ?>

    function hapusAdmin(id, nama) {
      Swal.fire({
        title: 'Cabut Akses Admin?',
        html: `Akses administrator untuk <strong>${nama}</strong> akan dicabut.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Cabut Akses',
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
  </script>
</body>
</html>
