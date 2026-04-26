<?php
session_start();
include '../server/koneksi.php';

if (!isset($_SESSION['nik']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php"); exit();
}

// ── Tambah Admin ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_admin'])) {
    $nik    = preg_replace('/[^0-9]/', '', trim($_POST['nik'] ?? ''));
    $nama   = trim($_POST['nama'] ?? '');
    $no_hp  = preg_replace('/[^0-9]/', '', trim($_POST['no_hp'] ?? ''));
    $pw     = $_POST['password'] ?? '';

    $errors = [];
    if (strlen($nik) !== 16)    $errors[] = 'NIK harus 16 digit';
    if (strlen($nama) < 3)      $errors[] = 'Nama terlalu pendek';
    if (strlen($no_hp) < 9)     $errors[] = 'No HP tidak valid';
    if (strlen($pw) < 6 || !preg_match('/[0-9]/', $pw) || !preg_match('/[A-Z]/', $pw))
        $errors[] = 'Password tidak memenuhi syarat';

    if (empty($errors)) {
        $cek = mysqli_prepare($koneksi, "SELECT id FROM users WHERE nik = ?");
        mysqli_stmt_bind_param($cek, "s", $nik);
        mysqli_stmt_execute($cek);
        mysqli_stmt_store_result($cek);
        if (mysqli_stmt_num_rows($cek) > 0) {
            $errors[] = 'NIK sudah terdaftar';
        }
        mysqli_stmt_close($cek);
    }

    if (empty($errors)) {
        $hash = password_hash($pw, PASSWORD_BCRYPT, ['cost'=>12]);
        $ins  = mysqli_prepare($koneksi, "INSERT INTO users (nik, nama, no_hp, password, role) VALUES (?, ?, ?, ?, 'admin')");
        mysqli_stmt_bind_param($ins, "ssss", $nik, $nama, $no_hp, $hash);
        if (mysqli_stmt_execute($ins)) {
            $_SESSION['flash'] = ['type'=>'success','title'=>'Admin Ditambahkan','message'=>"Akun admin $nama berhasil dibuat."];
        } else {
            $_SESSION['flash'] = ['type'=>'error','title'=>'Gagal','message'=>'Terjadi kesalahan sistem.'];
        }
        mysqli_stmt_close($ins);
    } else {
        $_SESSION['flash'] = ['type'=>'error','title'=>'Validasi Gagal','message'=>implode('. ', $errors).'.'];
    }
    header("Location: kelola_admin.php"); exit();
}

// ── Hapus Admin (via POST) ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_id'])) {
    $id_hapus = (int)$_POST['hapus_id'];

    // Tidak boleh hapus diri sendiri
    $self = mysqli_prepare($koneksi, "SELECT nik FROM users WHERE id = ?");
    mysqli_stmt_bind_param($self, "i", $id_hapus);
    mysqli_stmt_execute($self);
    $res_self = mysqli_stmt_get_result($self);
    $data_self = mysqli_fetch_assoc($res_self);
    mysqli_stmt_close($self);

    if ($data_self && $data_self['nik'] === $_SESSION['nik']) {
        $_SESSION['flash'] = ['type'=>'error','title'=>'Tidak Diizinkan','message'=>'Anda tidak bisa menghapus akun Anda sendiri!'];
    } else {
        $del = mysqli_prepare($koneksi, "DELETE FROM users WHERE id = ? AND role = 'admin'");
        mysqli_stmt_bind_param($del, "i", $id_hapus);
        mysqli_stmt_execute($del);
        mysqli_stmt_close($del);
        $_SESSION['flash'] = ['type'=>'success','title'=>'Admin Dihapus','message'=>'Akses admin berhasil dicabut.'];
    }
    header("Location: kelola_admin.php"); exit();
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
    .clay-nav{background:rgba(255,255,255,.95);backdrop-filter:blur(16px);border-bottom:1px solid #e2e8f0;box-shadow:0 2px 10px rgba(0,0,0,.04);}
    .admin-card{background:#fff;border-radius:24px;box-shadow:0 10px 30px rgba(0,0,0,.06);border:1px solid #f1f5f9;padding:1.5rem;}
    .clay-input{background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:12px;padding:0.7rem 1rem;
      width:100%;outline:none;font-size:.9rem;transition:border-color .2s;}
    .clay-input:focus{border-color:#3b82f6;box-shadow:0 0 0 3px rgba(59,130,246,.1);}
    .btn-primary{background:linear-gradient(135deg,#3b82f6,#2563eb);color:white;font-weight:700;
      padding:0.75rem 1.5rem;border-radius:12px;transition:.2s;cursor:pointer;border:none;width:100%;}
    .btn-primary:hover{transform:translateY(-2px);box-shadow:0 5px 15px rgba(37,99,235,.3);}
  </style>
</head>
<body>
  <nav class="clay-nav sticky top-0 z-50">
    <div class="max-w-6xl mx-auto px-4 py-3.5 flex items-center gap-3">
      <a href="dashboard.php" class="w-9 h-9 bg-gray-100 hover:bg-gray-200 rounded-xl flex items-center justify-center transition text-base">←</a>
      <div>
        <p class="font-extrabold text-gray-800">Manajemen Admin</p>
        <p class="text-xs text-gray-400">Tambah atau cabut akses administrator</p>
      </div>
    </div>
  </nav>

  <main class="max-w-6xl mx-auto px-4 py-8 grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- FORM TAMBAH ADMIN -->
    <div class="lg:col-span-1">
      <div class="admin-card">
        <h2 class="font-bold text-gray-800 mb-4">➕ Tambah Admin Baru</h2>
        <form action="" method="POST" class="space-y-3">
          <div>
            <label class="text-xs font-bold text-gray-600 block mb-1">NIK (16 Digit)</label>
            <input type="text" name="nik" maxlength="16" inputmode="numeric"
              oninput="this.value=this.value.replace(/[^0-9]/g,'')"
              class="clay-input" placeholder="Masukkan NIK" required/>
          </div>
          <div>
            <label class="text-xs font-bold text-gray-600 block mb-1">Nama Lengkap</label>
            <input type="text" name="nama" class="clay-input" placeholder="Nama Admin" required/>
          </div>
          <div>
            <label class="text-xs font-bold text-gray-600 block mb-1">No. WhatsApp</label>
            <input type="text" name="no_hp" inputmode="numeric"
              oninput="this.value=this.value.replace(/[^0-9]/g,'')"
              class="clay-input" placeholder="08xxx" required/>
          </div>
          <div>
            <label class="text-xs font-bold text-gray-600 block mb-1">Password</label>
            <input type="password" name="password" class="clay-input" placeholder="Min 6 kar (angka + huruf besar)" required autocomplete="new-password"/>
          </div>
          <div class="pt-1">
            <button type="submit" name="tambah_admin" class="btn-primary">💾 Simpan Akun Admin</button>
          </div>
        </form>
      </div>
    </div>

    <!-- LIST ADMIN -->
    <div class="lg:col-span-2">
      <div class="admin-card">
        <h2 class="font-bold text-gray-800 mb-4">🛡️ Daftar Admin Aktif</h2>
        <div class="divide-y divide-gray-100">
          <?php while ($row = mysqli_fetch_assoc($result_admin)):
            $isSelf = $row['nik'] === $_SESSION['nik']; ?>
          <div class="flex items-center justify-between py-3.5">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-purple-100 flex items-center justify-center text-lg flex-shrink-0">
                <?php echo $isSelf ? '👑' : '🛡️'; ?>
              </div>
              <div>
                <p class="font-bold text-gray-800 text-sm"><?php echo htmlspecialchars($row['nama']); ?></p>
                <p class="text-xs text-gray-400 font-mono"><?php echo htmlspecialchars($row['nik']); ?></p>
              </div>
            </div>
            <?php if ($isSelf): ?>
              <span class="text-blue-500 text-xs font-bold bg-blue-50 px-3 py-1.5 rounded-full">Akun Anda</span>
            <?php else: ?>
              <button onclick="hapusAdmin(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars(addslashes($row['nama'])); ?>')"
                class="text-red-500 text-xs font-bold hover:bg-red-50 px-3 py-2 rounded-lg transition">
                Hapus Akses
              </button>
            <?php endif; ?>
          </div>
          <?php endwhile; ?>
        </div>
      </div>
    </div>
  </main>

  <form id="form-hapus" method="POST" action="kelola_admin.php" style="display:none;">
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
