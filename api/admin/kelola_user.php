<?php
require_once __DIR__ . "/../server/bootstrap.php";
if (!isset($_SESSION['nik']) || $_SESSION['role'] !== 'admin') {
    header("Location: /login.php"); exit();
}

// ── TAMBAH ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'tambah') {
    $nik      = preg_replace('/[^0-9]/', '', trim($_POST['nik'] ?? ''));
    $nama     = trim($_POST['nama'] ?? '');
    $no_hp    = preg_replace('/[^0-9]/', '', trim($_POST['no_hp'] ?? ''));
    $kategori = in_array($_POST['kategori'] ?? '', ['Mahasiswa','Dosen','Karyawan','Umum'])
                ? $_POST['kategori'] : 'Umum';
    $pw       = $_POST['password'] ?? '';
    $errors   = [];
    if (strlen($nik) !== 16)  $errors[] = 'NIK harus 16 digit';
    if (strlen($nama) < 2)    $errors[] = 'Nama terlalu pendek';
    if (strlen($no_hp) < 9)   $errors[] = 'No. HP tidak valid';
    if (strlen($pw) < 6 || !preg_match('/[0-9]/',$pw) || !preg_match('/[A-Z]/',$pw))
        $errors[] = 'Password min 6 karakter, mengandung angka & huruf besar';
    if (empty($errors)) {
        $cek = mysqli_prepare($koneksi, "SELECT id FROM users WHERE nik=? OR no_hp=?");
        mysqli_stmt_bind_param($cek, "ss", $nik, $no_hp);
        mysqli_stmt_execute($cek); mysqli_stmt_store_result($cek);
        if (mysqli_stmt_num_rows($cek) > 0) $errors[] = 'NIK atau No. HP sudah terdaftar';
        mysqli_stmt_close($cek);
    }
    if (empty($errors)) {
        $hash = password_hash($pw, PASSWORD_BCRYPT, ['cost'=>12]);
        $ins  = mysqli_prepare($koneksi,
            "INSERT INTO users (nik,nama,kategori,no_hp,password,role) VALUES (?,?,?,?,?,'user')");
        mysqli_stmt_bind_param($ins, "sssss", $nik, $nama, $kategori, $no_hp, $hash);
        $_SESSION['flash'] = mysqli_stmt_execute($ins)
            ? ['type'=>'success','title'=>'Pasien Ditambahkan','message'=>"Data $nama berhasil disimpan."]
            : ['type'=>'error',  'title'=>'Gagal','message'=>'Terjadi kesalahan sistem.'];
        mysqli_stmt_close($ins);
    } else {
        $_SESSION['flash'] = ['type'=>'error','title'=>'Validasi Gagal','message'=>implode(' · ', $errors)];
    }
    header("Location: /admin/kelola_user.php"); exit();
}

// ── EDIT ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'edit') {
    $id       = (int)$_POST['edit_id'];
    $nama     = trim($_POST['nama'] ?? '');
    $no_hp    = preg_replace('/[^0-9]/', '', trim($_POST['no_hp'] ?? ''));
    $kategori = in_array($_POST['kategori'] ?? '', ['Mahasiswa','Dosen','Karyawan','Umum'])
                ? $_POST['kategori'] : 'Umum';
    $pw       = $_POST['password'] ?? '';
    $errors   = [];
    if (strlen($nama) < 2)  $errors[] = 'Nama terlalu pendek';
    if (strlen($no_hp) < 9) $errors[] = 'No. HP tidak valid';
    $cek2 = mysqli_prepare($koneksi, "SELECT id FROM users WHERE no_hp=? AND id!=?");
    mysqli_stmt_bind_param($cek2, "si", $no_hp, $id);
    mysqli_stmt_execute($cek2); mysqli_stmt_store_result($cek2);
    if (mysqli_stmt_num_rows($cek2) > 0) $errors[] = 'No. HP sudah dipakai pengguna lain';
    mysqli_stmt_close($cek2);
    if (empty($errors)) {
        if (!empty($pw)) {
            if (strlen($pw)<6 || !preg_match('/[0-9]/',$pw) || !preg_match('/[A-Z]/',$pw)) {
                $errors[] = 'Password baru tidak memenuhi syarat';
            } else {
                $hash = password_hash($pw, PASSWORD_BCRYPT, ['cost'=>12]);
                $upd  = mysqli_prepare($koneksi,
                    "UPDATE users SET nama=?,kategori=?,no_hp=?,password=? WHERE id=? AND role='user'");
                mysqli_stmt_bind_param($upd, "ssssi", $nama, $kategori, $no_hp, $hash, $id);
            }
        } else {
            $upd = mysqli_prepare($koneksi,
                "UPDATE users SET nama=?,kategori=?,no_hp=? WHERE id=? AND role='user'");
            mysqli_stmt_bind_param($upd, "sssi", $nama, $kategori, $no_hp, $id);
        }
    }
    if (empty($errors) && isset($upd)) {
        $_SESSION['flash'] = mysqli_stmt_execute($upd)
            ? ['type'=>'success','title'=>'Data Diperbarui','message'=>"Data $nama berhasil diubah."]
            : ['type'=>'error',  'title'=>'Gagal','message'=>'Gagal memperbarui data.'];
        mysqli_stmt_close($upd);
    } elseif (!empty($errors)) {
        $_SESSION['flash'] = ['type'=>'error','title'=>'Validasi Gagal','message'=>implode(' · ', $errors)];
    }
    header("Location: /admin/kelola_user.php"); exit();
}

// ── HAPUS ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_id'])) {
    $id_hapus = (int)$_POST['hapus_id'];
    $del = mysqli_prepare($koneksi, "DELETE FROM users WHERE id=? AND role='user'");
    mysqli_stmt_bind_param($del, "i", $id_hapus);
    $ok = mysqli_stmt_execute($del); mysqli_stmt_close($del);
    $_SESSION['flash'] = $ok
        ? ['type'=>'success','title'=>'Berhasil','message'=>'Data pasien berhasil dihapus.']
        : ['type'=>'error',  'title'=>'Gagal',   'message'=>'Gagal menghapus data pasien.'];
    header("Location: /admin/kelola_user.php"); exit();
}

// ── AMBIL DATA ──
$result_users = mysqli_query($koneksi, "SELECT * FROM users WHERE role='user' ORDER BY id DESC");
$total = mysqli_num_rows($result_users);
$users = [];
while ($r = mysqli_fetch_assoc($result_users)) $users[] = $r;

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
    .nav-inner{max-width:1040px;margin:0 auto;padding:0 20px;height:58px;
      display:flex;align-items:center;justify-content:space-between;gap:12px;}
    .back-btn{width:36px;height:36px;background:#f1f5f9;border-radius:10px;
      display:flex;align-items:center;justify-content:center;text-decoration:none;
      font-size:1rem;flex-shrink:0;transition:background .2s;}
    .back-btn:hover{background:#e2e8f0;}

    /* Layout */
    .page-wrap{max-width:1040px;margin:0 auto;padding:20px 20px 60px;}
    .main-grid{display:grid;grid-template-columns:300px 1fr;gap:20px;align-items:start;}

    /* Cards */
    .admin-card{background:#fff;border-radius:20px;
      box-shadow:0 8px 24px rgba(0,0,0,.06);border:1px solid #f1f5f9;padding:1.4rem;}

    /* Form */
    .clay-label{font-size:.75rem;font-weight:700;color:#475569;display:block;margin-bottom:5px;}
    .clay-input{background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:12px;
      padding:.65rem 1rem;width:100%;outline:none;font-size:.88rem;
      color:#1e293b;transition:border-color .2s;}
    .clay-input:focus{border-color:#3b82f6;box-shadow:0 0 0 3px rgba(59,130,246,.1);}
    .clay-select{background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:12px;
      padding:.65rem 1rem;width:100%;outline:none;font-size:.88rem;
      color:#1e293b;appearance:none;cursor:pointer;}
    .clay-select:focus{border-color:#3b82f6;}
    .btn-primary{background:linear-gradient(135deg,#3b82f6,#2563eb);color:#fff;
      font-weight:700;padding:.7rem;border-radius:12px;cursor:pointer;
      border:none;width:100%;font-size:.88rem;transition:all .2s;}
    .btn-primary:hover{transform:translateY(-1px);box-shadow:0 4px 12px rgba(37,99,235,.3);}
    .btn-secondary{background:#fff;color:#475569;font-weight:700;padding:.7rem;
      border-radius:12px;cursor:pointer;border:1.5px solid #e2e8f0;width:100%;
      font-size:.85rem;transition:all .2s;}
    .btn-secondary:hover{background:#f8fafc;}

    /* Table */
    .tbl-wrap{overflow-x:auto;-webkit-overflow-scrolling:touch;}
    .styled-table{width:100%;border-collapse:collapse;min-width:500px;}
    .styled-table thead tr{background:#f8fafc;}
    .styled-table th{padding:11px 14px;border-bottom:2px solid #e2e8f0;
      font-size:.76rem;font-weight:700;color:#475569;text-align:left;white-space:nowrap;}
    .styled-table td{padding:10px 14px;border-bottom:1px solid #f1f5f9;
      font-size:.83rem;vertical-align:middle;}
    .styled-table tbody tr:hover{background:#fafcff;}

    /* Action btns */
    .btn-edit{background:#eff6ff;color:#2563eb;border:none;padding:5px 12px;
      border-radius:8px;font-size:.72rem;font-weight:700;cursor:pointer;transition:.2s;}
    .btn-edit:hover{background:#2563eb;color:#fff;}
    .btn-del{background:#fef2f2;color:#ef4444;border:none;padding:5px 12px;
      border-radius:8px;font-size:.72rem;font-weight:700;cursor:pointer;transition:.2s;}
    .btn-del:hover{background:#ef4444;color:#fff;}

    /* Kategori badge */
    .kat{display:inline-block;padding:2px 9px;border-radius:999px;font-size:.67rem;font-weight:700;}
    .kat-Mahasiswa{background:#eff6ff;color:#1d4ed8;}
    .kat-Dosen{background:#faf5ff;color:#6d28d9;}
    .kat-Karyawan{background:#fefce8;color:#854d0e;}
    .kat-Umum{background:#f0fdf4;color:#166534;}

    /* Search */
    .search-input{border:1.5px solid #e2e8f0;border-radius:12px;padding:8px 14px;
      font-size:.84rem;outline:none;transition:border-color .2s;width:100%;}
    .search-input:focus{border-color:#3b82f6;}

    /* ── Modal (in-page, no fixed) ── */
    .modal-bg{display:none;background:rgba(0,0,0,.4);border-radius:20px;
      padding:20px;margin-top:20px;}
    .modal-bg.show{display:block;}
    @keyframes slideDown{from{opacity:0;transform:translateY(-10px)}to{opacity:1;transform:translateY(0)}}
    .modal-inner{background:#fff;border-radius:18px;padding:1.4rem;
      max-width:460px;margin:0 auto;
      box-shadow:0 16px 48px rgba(0,0,0,.16);
      animation:slideDown .22s ease both;}

    /* ── TABLET (≤900px) ── */
    @media(max-width:900px){
      .main-grid{grid-template-columns:1fr;}
      .nav-inner{padding:0 14px;height:52px;}
      .admin-card{padding:1.1rem;border-radius:16px;}
    }

    /* ── MOBILE (≤480px) ── */
    @media(max-width:480px){
      .nav-inner{padding:0 10px;height:48px;}
      .back-btn{width:32px;height:32px;}
      .page-wrap{padding:12px 10px 40px;}
      .admin-card{padding:.9rem;border-radius:14px;}
      .header-row{flex-direction:column;align-items:flex-start !important;gap:10px;}
      .header-right{width:100%;}
      /* Sembunyikan kolom No & No WA */
      .styled-table th:nth-child(1),
      .styled-table td:nth-child(1),
      .styled-table th:nth-child(4),
      .styled-table td:nth-child(4){display:none;}
      .styled-table th,.styled-table td{padding:8px;}
      .nik-cell{font-size:.64rem !important;letter-spacing:0 !important;}
      .btn-edit,.btn-del{padding:4px 8px;font-size:.68rem;}
    }
  </style>
</head>
<body>

<nav class="clay-nav">
  <div class="nav-inner">
    <div style="display:flex;align-items:center;gap:10px;">
      <a href="/admin/dashboard.php" class="back-btn">←</a>
      <div>
        <p style="font-weight:800;font-size:.92rem;color:#1e293b;line-height:1.2;">Kelola Data Pasien</p>
        <p style="font-size:.7rem;color:#94a3b8;">Total <?php echo $total; ?> pasien · CRUD lengkap</p>
      </div>
    </div>
    <span style="font-size:.82rem;font-weight:700;color:#2563eb;">
      Admin <?php echo htmlspecialchars(explode(' ',$_SESSION['nama'])[0]); ?>
    </span>
  </div>
</nav>

<div class="page-wrap">
  <div class="main-grid">

    <!-- ══ KIRI: Form Tambah ══ -->
    <div class="admin-card">
      <div style="display:flex;align-items:center;gap:8px;margin-bottom:16px;">
        <div style="width:32px;height:32px;background:#eff6ff;border-radius:10px;
          display:flex;align-items:center;justify-content:center;font-size:1rem;">➕</div>
        <h2 style="font-weight:800;font-size:.92rem;color:#1e293b;">Tambah Pasien Baru</h2>
      </div>
      <form method="POST" action="/admin/kelola_user.php" style="display:flex;flex-direction:column;gap:11px;">
        <input type="hidden" name="aksi" value="tambah"/>
        <div>
          <label class="clay-label">NIK (16 Digit)</label>
          <input type="text" name="nik" maxlength="16" inputmode="numeric"
            oninput="this.value=this.value.replace(/[^0-9]/g,'')"
            class="clay-input" placeholder="3519xxxxxxxxxxxx" required/>
        </div>
        <div>
          <label class="clay-label">Nama Lengkap</label>
          <input type="text" name="nama" class="clay-input" placeholder="Nama lengkap pasien" required/>
        </div>
        <div>
          <label class="clay-label">Kategori</label>
          <div style="position:relative;">
            <select name="kategori" class="clay-select" required>
              <option value="Mahasiswa">🎓 Mahasiswa</option>
              <option value="Dosen">👨‍🏫 Dosen</option>
              <option value="Karyawan">💼 Karyawan</option>
              <option value="Umum">👤 Umum</option>
            </select>
            <span style="position:absolute;right:12px;top:50%;transform:translateY(-50%);
              pointer-events:none;color:#94a3b8;font-size:.75rem;">▾</span>
          </div>
        </div>
        <div>
          <label class="clay-label">No. WhatsApp</label>
          <input type="text" name="no_hp" inputmode="numeric"
            oninput="this.value=this.value.replace(/[^0-9]/g,'')"
            class="clay-input" placeholder="081234567890" required/>
        </div>
        <div>
          <label class="clay-label">Password</label>
          <input type="password" name="password" class="clay-input"
            placeholder="Min 6 kar + angka + huruf besar" required autocomplete="new-password"/>
          <p style="font-size:.67rem;color:#94a3b8;margin-top:3px;">Contoh: Pasien123</p>
        </div>
        <button type="submit" class="btn-primary" style="margin-top:4px;">
          💾 Simpan Pasien
        </button>
      </form>
    </div>

    <!-- ══ KANAN: Tabel + Modal Edit ══ -->
    <div>
      <div class="admin-card">
        <!-- Header -->
        <div class="header-row" style="display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:14px;">
          <div>
            <h2 style="font-weight:800;font-size:.92rem;color:#1e293b;">Daftar Pasien Terdaftar</h2>
            <p style="font-size:.7rem;color:#94a3b8;margin-top:2px;">Klik ✏️ untuk edit · 🗑️ untuk hapus</p>
          </div>
          <div class="header-right" style="display:flex;gap:8px;align-items:center;flex-shrink:0;">
            <input type="text" id="search-input" placeholder="🔍 Cari..." class="search-input" style="max-width:180px;"/>
            <span style="font-size:.75rem;font-weight:700;color:#2563eb;background:#eff6ff;
              border:1px solid #bfdbfe;padding:6px 12px;border-radius:10px;white-space:nowrap;">
              <?php echo $total; ?> Pasien
            </span>
          </div>
        </div>

        <!-- Tabel -->
        <div class="tbl-wrap">
          <table class="styled-table" id="user-table">
            <thead>
              <tr>
                <th>No.</th>
                <th>NIK</th>
                <th>Nama & Kategori</th>
                <th>No. WA</th>
                <th style="text-align:center;">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php if (count($users) > 0):
                foreach ($users as $i => $row): ?>
              <tr>
                <td style="color:#94a3b8;font-weight:600;"><?php echo $i+1; ?></td>
                <td class="nik-cell" style="font-family:monospace;color:#475569;font-size:.75rem;letter-spacing:.04em;">
                  <?php echo htmlspecialchars($row['nik']); ?>
                </td>
                <td>
                  <p style="font-weight:700;color:#1e293b;"><?php echo htmlspecialchars($row['nama']); ?></p>
                  <span class="kat kat-<?php echo htmlspecialchars($row['kategori']); ?>">
                    <?php echo htmlspecialchars($row['kategori']); ?>
                  </span>
                </td>
                <td style="color:#64748b;"><?php echo htmlspecialchars($row['no_hp']); ?></td>
                <td>
                  <div style="display:flex;gap:5px;justify-content:center;">
                    <button class="btn-edit" onclick="openEdit(
                      <?php echo $row['id']; ?>,
                      '<?php echo htmlspecialchars(addslashes($row['nik'])); ?>',
                      '<?php echo htmlspecialchars(addslashes($row['nama'])); ?>',
                      '<?php echo htmlspecialchars(addslashes($row['kategori'])); ?>',
                      '<?php echo htmlspecialchars(addslashes($row['no_hp'])); ?>'
                    )">✏️ Edit</button>
                    <button class="btn-del" onclick="hapusUser(
                      <?php echo $row['id']; ?>,
                      '<?php echo htmlspecialchars(addslashes($row['nama'])); ?>'
                    )">🗑️ Hapus</button>
                  </div>
                </td>
              </tr>
              <?php endforeach; else: ?>
              <tr>
                <td colspan="5" style="text-align:center;padding:40px;color:#94a3b8;font-style:italic;">
                  Belum ada pasien terdaftar.
                </td>
              </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- ══ MODAL EDIT (in-page, bukan fixed) ══ -->
      <div id="modal-bg" class="modal-bg">
        <div class="modal-inner">
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
            <div style="display:flex;align-items:center;gap:8px;">
              <div style="width:30px;height:30px;background:#fef9c3;border-radius:8px;
                display:flex;align-items:center;justify-content:center;font-size:.9rem;">✏️</div>
              <h3 style="font-weight:800;font-size:.95rem;color:#1e293b;">Edit Data Pasien</h3>
            </div>
            <button onclick="closeEdit()" style="background:#f1f5f9;border:none;
              border-radius:8px;width:28px;height:28px;cursor:pointer;
              font-size:.85rem;color:#64748b;display:flex;align-items:center;justify-content:center;">✕</button>
          </div>

          <form method="POST" action="/admin/kelola_user.php" style="display:flex;flex-direction:column;gap:11px;">
            <input type="hidden" name="aksi" value="edit"/>
            <input type="hidden" name="edit_id" id="edit-id"/>

            <div>
              <label class="clay-label">NIK <span style="color:#94a3b8;font-weight:400;">(tidak bisa diubah)</span></label>
              <input type="text" id="edit-nik" class="clay-input"
                style="background:#f1f5f9;color:#94a3b8;cursor:not-allowed;" readonly/>
            </div>
            <div>
              <label class="clay-label">Nama Lengkap</label>
              <input type="text" name="nama" id="edit-nama" class="clay-input" required/>
            </div>
            <div>
              <label class="clay-label">Kategori</label>
              <div style="position:relative;">
                <select name="kategori" id="edit-kategori" class="clay-select" required>
                  <option value="Mahasiswa">🎓 Mahasiswa</option>
                  <option value="Dosen">👨‍🏫 Dosen</option>
                  <option value="Karyawan">💼 Karyawan</option>
                  <option value="Umum">👤 Umum</option>
                </select>
                <span style="position:absolute;right:12px;top:50%;transform:translateY(-50%);
                  pointer-events:none;color:#94a3b8;font-size:.75rem;">▾</span>
              </div>
            </div>
            <div>
              <label class="clay-label">No. WhatsApp</label>
              <input type="text" name="no_hp" id="edit-nohp" inputmode="numeric"
                oninput="this.value=this.value.replace(/[^0-9]/g,'')" class="clay-input" required/>
            </div>
            <div>
              <label class="clay-label">
                Password Baru
                <span style="color:#94a3b8;font-weight:400;">(kosongkan jika tidak diganti)</span>
              </label>
              <input type="password" name="password" id="edit-pw" class="clay-input"
                placeholder="Min 6 kar + angka + huruf besar" autocomplete="new-password"/>
            </div>

            <div style="display:flex;gap:10px;margin-top:4px;">
              <button type="button" onclick="closeEdit()" class="btn-secondary">Batal</button>
              <button type="submit" class="btn-primary" style="flex:2;">💾 Simpan Perubahan</button>
            </div>
          </form>
        </div>
      </div>

    </div><!-- end kanan -->
  </div><!-- end main-grid -->
</div><!-- end page-wrap -->

<!-- Form hapus -->
<form id="form-hapus" method="POST" action="/admin/kelola_user.php" style="display:none;">
  <input type="hidden" name="hapus_id" id="hapus-id-input">
</form>

<script>
  <?php if ($flash): ?>
  document.addEventListener('DOMContentLoaded', function() {
    Swal.fire({
      icon: '<?php echo $flash['type']; ?>',
      title: '<?php echo htmlspecialchars($flash['title']); ?>',
      text: '<?php echo htmlspecialchars($flash['message']); ?>',
      confirmButtonColor: '#2563eb',
      timer: 3500, timerProgressBar: true,
    });
  });
  <?php endif; ?>

  function openEdit(id, nik, nama, kategori, nohp) {
    document.getElementById('edit-id').value       = id;
    document.getElementById('edit-nik').value      = nik;
    document.getElementById('edit-nama').value     = nama;
    document.getElementById('edit-kategori').value = kategori;
    document.getElementById('edit-nohp').value     = nohp;
    document.getElementById('edit-pw').value       = '';
    const modal = document.getElementById('modal-bg');
    modal.classList.add('show');
    // Scroll ke modal supaya kelihatan
    setTimeout(() => modal.scrollIntoView({behavior:'smooth', block:'start'}), 50);
  }

  function closeEdit() {
    document.getElementById('modal-bg').classList.remove('show');
  }

  document.addEventListener('keydown', e => { if (e.key === 'Escape') closeEdit(); });

  function hapusUser(id, nama) {
    Swal.fire({
      title: 'Hapus Pasien?',
      html: `Yakin ingin menghapus data <strong>${nama}</strong>?<br>
        <small style="color:#94a3b8;">Tindakan ini tidak bisa dibatalkan.</small>`,
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
