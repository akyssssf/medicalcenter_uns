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
    $pw = $_POST['password'] ?? '';
    $errors = [];
    if (strlen($nik) !== 16) $errors[] = 'NIK harus 16 digit';
    if (strlen($nama) < 2)   $errors[] = 'Nama terlalu pendek';
    if (strlen($no_hp) < 9)  $errors[] = 'No. HP tidak valid';
    if (strlen($pw) < 6 || !preg_match('/[0-9]/',$pw) || !preg_match('/[A-Z]/',$pw))
        $errors[] = 'Password min 6 kar + angka + huruf besar';
    if (empty($errors)) {
        $cek = mysqli_prepare($koneksi,"SELECT id FROM users WHERE nik=? OR no_hp=?");
        mysqli_stmt_bind_param($cek,"ss",$nik,$no_hp);
        mysqli_stmt_execute($cek); mysqli_stmt_store_result($cek);
        if (mysqli_stmt_num_rows($cek)>0) $errors[]='NIK atau No. HP sudah terdaftar';
        mysqli_stmt_close($cek);
    }
    if (empty($errors)) {
        $hash = password_hash($pw,PASSWORD_BCRYPT,['cost'=>12]);
        $ins  = mysqli_prepare($koneksi,"INSERT INTO users (nik,nama,kategori,no_hp,password,role) VALUES (?,?,?,?,?,'user')");
        mysqli_stmt_bind_param($ins,"sssss",$nik,$nama,$kategori,$no_hp,$hash);
        $_SESSION['flash'] = mysqli_stmt_execute($ins)
            ? ['type'=>'success','title'=>'Berhasil','message'=>"Data $nama berhasil disimpan."]
            : ['type'=>'error','title'=>'Gagal','message'=>'Terjadi kesalahan sistem.'];
        mysqli_stmt_close($ins);
    } else {
        $_SESSION['flash'] = ['type'=>'error','title'=>'Validasi Gagal','message'=>implode(' · ',$errors)];
    }
    header("Location: /admin/kelola_user.php"); exit();
}

// ── EDIT ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'edit') {
    $id       = (int)$_POST['edit_id'];
    $nama     = trim($_POST['nama'] ?? '');
    $no_hp    = preg_replace('/[^0-9]/','',trim($_POST['no_hp'] ?? ''));
    $kategori = in_array($_POST['kategori'] ?? '',['Mahasiswa','Dosen','Karyawan','Umum'])
                ? $_POST['kategori'] : 'Umum';
    $pw = $_POST['password'] ?? '';
    $errors = [];
    if (strlen($nama) < 2)  $errors[] = 'Nama terlalu pendek';
    if (strlen($no_hp) < 9) $errors[] = 'No. HP tidak valid';
    $cek2 = mysqli_prepare($koneksi,"SELECT id FROM users WHERE no_hp=? AND id!=?");
    mysqli_stmt_bind_param($cek2,"si",$no_hp,$id);
    mysqli_stmt_execute($cek2); mysqli_stmt_store_result($cek2);
    if (mysqli_stmt_num_rows($cek2)>0) $errors[]='No. HP sudah dipakai pengguna lain';
    mysqli_stmt_close($cek2);
    if (empty($errors)) {
        if (!empty($pw)) {
            if (strlen($pw)<6||!preg_match('/[0-9]/',$pw)||!preg_match('/[A-Z]/',$pw)) {
                $errors[]='Password baru tidak memenuhi syarat';
            } else {
                $hash = password_hash($pw,PASSWORD_BCRYPT,['cost'=>12]);
                $upd  = mysqli_prepare($koneksi,"UPDATE users SET nama=?,kategori=?,no_hp=?,password=? WHERE id=? AND role='user'");
                mysqli_stmt_bind_param($upd,"ssssi",$nama,$kategori,$no_hp,$hash,$id);
            }
        } else {
            $upd = mysqli_prepare($koneksi,"UPDATE users SET nama=?,kategori=?,no_hp=? WHERE id=? AND role='user'");
            mysqli_stmt_bind_param($upd,"sssi",$nama,$kategori,$no_hp,$id);
        }
    }
    if (empty($errors) && isset($upd)) {
        $_SESSION['flash'] = mysqli_stmt_execute($upd)
            ? ['type'=>'success','title'=>'Diperbarui','message'=>"Data $nama berhasil diubah."]
            : ['type'=>'error','title'=>'Gagal','message'=>'Gagal memperbarui data.'];
        mysqli_stmt_close($upd);
    } elseif (!empty($errors)) {
        $_SESSION['flash'] = ['type'=>'error','title'=>'Validasi Gagal','message'=>implode(' · ',$errors)];
    }
    header("Location: /admin/kelola_user.php"); exit();
}

// ── HAPUS ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_id'])) {
    $id_hapus = (int)$_POST['hapus_id'];
    $del = mysqli_prepare($koneksi,"DELETE FROM users WHERE id=? AND role='user'");
    mysqli_stmt_bind_param($del,"i",$id_hapus);
    $ok = mysqli_stmt_execute($del); mysqli_stmt_close($del);
    $_SESSION['flash'] = $ok
        ? ['type'=>'success','title'=>'Berhasil','message'=>'Data pasien berhasil dihapus.']
        : ['type'=>'error','title'=>'Gagal','message'=>'Gagal menghapus data pasien.'];
    header("Location: /admin/kelola_user.php"); exit();
}

$result_users = mysqli_query($koneksi,"SELECT * FROM users WHERE role='user' ORDER BY id DESC");
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
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: 'Plus Jakarta Sans', sans-serif;
      background: linear-gradient(135deg, #f8fafc 0%, #eff6ff 100%);
      min-height: 100vh;
      font-size: 14px;
    }

    /* ── NAV ── */
    .nav {
      background: #fff;
      border-bottom: 1px solid #e2e8f0;
      box-shadow: 0 2px 8px rgba(0,0,0,.05);
      position: sticky; top: 0; z-index: 50;
    }
    .nav-inner {
      display: flex; align-items: center; justify-content: space-between;
      padding: 0 16px; height: 52px; gap: 10px;
    }
    .nav-left { display: flex; align-items: center; gap: 8px; min-width: 0; }
    .back-btn {
      width: 34px; height: 34px; min-width: 34px;
      background: #f1f5f9; border-radius: 10px;
      display: flex; align-items: center; justify-content: center;
      text-decoration: none; font-size: 16px; color: #374151;
      transition: background .2s;
    }
    .back-btn:hover { background: #e2e8f0; }
    .nav-text { min-width: 0; }
    .nav-text h1 { font-size: 14px; font-weight: 800; color: #1e293b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .nav-text p  { font-size: 11px; color: #94a3b8; }

    /* ── PAGE ── */
    .page { padding: 14px 12px 60px; display: flex; flex-direction: column; gap: 14px; }

    /* ── CARD ── */
    .card {
      background: #fff;
      border-radius: 16px;
      border: 1px solid #e8eef6;
      box-shadow: 0 4px 16px rgba(0,0,0,.06);
      padding: 16px;
    }
    .card-head {
      display: flex; align-items: center; gap: 8px; margin-bottom: 14px;
    }
    .card-icon {
      width: 30px; height: 30px; border-radius: 9px;
      display: flex; align-items: center; justify-content: center;
      font-size: 14px; flex-shrink: 0;
    }
    .card-head h2 { font-size: 14px; font-weight: 800; color: #1e293b; }

    /* ── FORM ── */
    .form-stack { display: flex; flex-direction: column; gap: 10px; }
    .field { display: flex; flex-direction: column; gap: 4px; }
    .lbl { font-size: 12px; font-weight: 700; color: #475569; }
    .lbl-hint { font-weight: 400; color: #94a3b8; }
    .inp, .sel {
      width: 100%; padding: 10px 12px;
      background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 10px;
      font-size: 13px; color: #1e293b; font-family: inherit;
      outline: none; transition: border-color .2s;
    }
    .inp:focus, .sel:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,.1); }
    .inp.ro { background: #f1f5f9; color: #94a3b8; cursor: not-allowed; }
    .sel { appearance: none; cursor: pointer; }
    .sel-wrap { position: relative; }
    .sel-arr { position: absolute; right: 10px; top: 50%; transform: translateY(-50%); pointer-events: none; color: #94a3b8; font-size: 11px; }
    .hint { font-size: 11px; color: #94a3b8; }

    /* ── BUTTONS ── */
    .btn-blue {
      width: 100%; padding: 11px; border-radius: 11px; border: none;
      background: linear-gradient(135deg, #3b82f6, #2563eb);
      color: #fff; font-size: 13px; font-weight: 700;
      cursor: pointer; transition: all .2s; font-family: inherit;
    }
    .btn-blue:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(37,99,235,.3); }
    .btn-gray {
      width: 100%; padding: 11px; border-radius: 11px;
      border: 1.5px solid #e2e8f0; background: #fff;
      color: #64748b; font-size: 13px; font-weight: 700;
      cursor: pointer; transition: background .2s; font-family: inherit;
    }
    .btn-gray:hover { background: #f8fafc; }
    .btn-row { display: flex; gap: 8px; margin-top: 4px; }
    .btn-row .btn-gray { flex: 1; }
    .btn-row .btn-blue { flex: 2; }

    /* ── TABLE SECTION ── */
    .tbl-head {
      display: flex; align-items: center; justify-content: space-between;
      gap: 8px; margin-bottom: 12px; flex-wrap: wrap;
    }
    .tbl-head-title h2 { font-size: 14px; font-weight: 800; color: #1e293b; }
    .tbl-head-title p  { font-size: 11px; color: #94a3b8; margin-top: 2px; }
    .tbl-search-row { display: flex; gap: 8px; align-items: center; width: 100%; margin-top: 8px; }
    .search-inp {
      flex: 1; padding: 8px 12px; border: 1.5px solid #e2e8f0; border-radius: 10px;
      font-size: 13px; outline: none; font-family: inherit; transition: border-color .2s;
    }
    .search-inp:focus { border-color: #3b82f6; }
    .badge-count {
      font-size: 12px; font-weight: 700; color: #2563eb;
      background: #eff6ff; border: 1px solid #bfdbfe;
      padding: 6px 10px; border-radius: 8px; white-space: nowrap; flex-shrink: 0;
    }

    /* ── TABLE ── */
    .tbl-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; border-radius: 10px; }
    table { width: 100%; border-collapse: collapse; }
    thead tr { background: #f8fafc; }
    th {
      padding: 9px 10px; border-bottom: 2px solid #e2e8f0;
      font-size: 11px; font-weight: 700; color: #64748b;
      text-align: left; white-space: nowrap;
    }
    td { padding: 9px 10px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
    tbody tr:hover { background: #fafcff; }
    .td-nama { font-size: 13px; font-weight: 700; color: #1e293b; }
    .td-nik  { font-size: 11px; font-family: monospace; color: #64748b; }
    .td-wa   { font-size: 12px; color: #64748b; }
    .td-no   { font-size: 12px; color: #94a3b8; font-weight: 600; }

    /* ── KAT BADGE ── */
    .kat { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 10px; font-weight: 700; }
    .kat-Mahasiswa { background: #eff6ff; color: #1d4ed8; }
    .kat-Dosen     { background: #faf5ff; color: #6d28d9; }
    .kat-Karyawan  { background: #fefce8; color: #854d0e; }
    .kat-Umum      { background: #f0fdf4; color: #166534; }

    /* ── ACTION BUTTONS ── */
    .act { display: flex; gap: 4px; justify-content: center; }
    .btn-e, .btn-d {
      border: none; border-radius: 7px;
      padding: 5px 9px; font-size: 11px; font-weight: 700;
      cursor: pointer; transition: .18s; font-family: inherit; white-space: nowrap;
    }
    .btn-e { background: #eff6ff; color: #2563eb; }
    .btn-e:hover { background: #2563eb; color: #fff; }
    .btn-d { background: #fef2f2; color: #ef4444; }
    .btn-d:hover { background: #ef4444; color: #fff; }

    /* ── MODAL EDIT (in-page, NO position fixed) ── */
    .modal-wrap { display: none; padding-top: 14px; }
    .modal-wrap.show { display: block; }
    @keyframes sd { from{opacity:0;transform:translateY(-8px)} to{opacity:1;transform:translateY(0)} }
    .modal-box {
      background: #fff; border-radius: 16px; padding: 16px;
      border: 1.5px solid #bfdbfe;
      box-shadow: 0 8px 32px rgba(37,99,235,.12);
      animation: sd .2s ease both;
    }
    .modal-head {
      display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px;
    }
    .modal-title { display: flex; align-items: center; gap: 8px; }
    .modal-title h3 { font-size: 14px; font-weight: 800; color: #1e293b; }
    .modal-close {
      background: #f1f5f9; border: none; border-radius: 7px;
      width: 26px; height: 26px; cursor: pointer; font-size: 13px;
      color: #64748b; display: flex; align-items: center; justify-content: center;
    }
    .modal-close:hover { background: #e2e8f0; }

    /* ── TABLET ≤ 768px ── */
    @media (max-width: 768px) {
      .page { padding: 12px 12px 60px; }
    }
  </style>
</head>
<body>

<nav class="nav">
  <div class="nav-inner">
    <div class="nav-left">
      <a href="/admin/dashboard.php" class="back-btn">←</a>
      <div class="nav-text">
        <h1>Kelola Data Pasien</h1>
        <p><?php echo $total; ?> pasien terdaftar</p>
      </div>
    </div>
  </div>
</nav>

<div class="page">

  <!-- ══ FORM TAMBAH ══ -->
  <div class="card">
    <div class="card-head">
      <div class="card-icon" style="background:#eff6ff;">➕</div>
      <h2>Tambah Pasien Baru</h2>
    </div>
    <form method="POST" action="/admin/kelola_user.php" class="form-stack">
      <input type="hidden" name="aksi" value="tambah"/>
      <div class="field">
        <label class="lbl">NIK (16 Digit)</label>
        <input type="text" name="nik" maxlength="16" inputmode="numeric"
          oninput="this.value=this.value.replace(/[^0-9]/g,'')"
          class="inp" placeholder="3519xxxxxxxxxxxx" required/>
      </div>
      <div class="field">
        <label class="lbl">Nama Lengkap</label>
        <input type="text" name="nama" class="inp" placeholder="Nama lengkap pasien" required/>
      </div>
      <div class="field">
        <label class="lbl">Kategori</label>
        <div class="sel-wrap">
          <select name="kategori" class="sel" required>
            <option value="Mahasiswa">🎓 Mahasiswa</option>
            <option value="Dosen">👨‍🏫 Dosen</option>
            <option value="Karyawan">💼 Karyawan</option>
            <option value="Umum">👤 Umum</option>
          </select>
          <span class="sel-arr">▾</span>
        </div>
      </div>
      <div class="field">
        <label class="lbl">No. WhatsApp</label>
        <input type="text" name="no_hp" inputmode="numeric"
          oninput="this.value=this.value.replace(/[^0-9]/g,'')"
          class="inp" placeholder="081234567890" required/>
      </div>
      <div class="field">
        <label class="lbl">Password</label>
        <input type="password" name="password" class="inp"
          placeholder="Min 6 kar + angka + huruf besar" required autocomplete="new-password"/>
        <span class="hint">Contoh: Pasien123</span>
      </div>
      <button type="submit" class="btn-blue" style="margin-top:4px;">💾 Simpan Pasien</button>
    </form>
  </div>

  <!-- ══ TABEL ══ -->
  <div class="card">
    <div class="tbl-head">
      <div class="tbl-head-title">
        <h2>Daftar Pasien Terdaftar</h2>
        <p>Klik ✏️ untuk edit · 🗑️ untuk hapus</p>
      </div>
    </div>
    <div class="tbl-search-row">
      <input type="text" id="search-input" placeholder="🔍 Cari nama / NIK..." class="search-inp"/>
      <span class="badge-count"><?php echo $total; ?> Pasien</span>
    </div>
    <div style="height:10px;"></div>

    <div class="tbl-wrap">
      <table id="user-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Nama &amp; Kategori</th>
            <th>NIK</th>
            <th style="text-align:center;">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (count($users) > 0):
            foreach ($users as $i => $row): ?>
          <tr>
            <td class="td-no"><?php echo $i+1; ?></td>
            <td>
              <div class="td-nama"><?php echo htmlspecialchars($row['nama']); ?></div>
              <span class="kat kat-<?php echo htmlspecialchars($row['kategori']); ?>">
                <?php echo htmlspecialchars($row['kategori']); ?>
              </span>
            </td>
            <td class="td-nik"><?php echo htmlspecialchars($row['nik']); ?></td>
            <td>
              <div class="act">
                <button class="btn-e" onclick="openEdit(
                  <?php echo $row['id']; ?>,
                  '<?php echo htmlspecialchars(addslashes($row['nik'])); ?>',
                  '<?php echo htmlspecialchars(addslashes($row['nama'])); ?>',
                  '<?php echo htmlspecialchars(addslashes($row['kategori'])); ?>',
                  '<?php echo htmlspecialchars(addslashes($row['no_hp'])); ?>'
                )">✏️ Edit</button>
                <button class="btn-d" onclick="hapusUser(
                  <?php echo $row['id']; ?>,
                  '<?php echo htmlspecialchars(addslashes($row['nama'])); ?>'
                )">🗑️ Hapus</button>
              </div>
            </td>
          </tr>
          <?php endforeach; else: ?>
          <tr>
            <td colspan="4" style="text-align:center;padding:36px;color:#94a3b8;font-style:italic;">
              Belum ada pasien terdaftar.
            </td>
          </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- ══ MODAL EDIT (in-page) ══ -->
  <div id="modal-wrap" class="modal-wrap">
    <div class="modal-box">
      <div class="modal-head">
        <div class="modal-title">
          <span style="font-size:16px;">✏️</span>
          <h3>Edit Data Pasien</h3>
        </div>
        <button class="modal-close" onclick="closeEdit()">✕</button>
      </div>
      <form method="POST" action="/admin/kelola_user.php" class="form-stack">
        <input type="hidden" name="aksi" value="edit"/>
        <input type="hidden" name="edit_id" id="edit-id"/>
        <div class="field">
          <label class="lbl">NIK <span class="lbl-hint">(tidak bisa diubah)</span></label>
          <input type="text" id="edit-nik" class="inp ro" readonly/>
        </div>
        <div class="field">
          <label class="lbl">Nama Lengkap</label>
          <input type="text" name="nama" id="edit-nama" class="inp" required/>
        </div>
        <div class="field">
          <label class="lbl">Kategori</label>
          <div class="sel-wrap">
            <select name="kategori" id="edit-kat" class="sel" required>
              <option value="Mahasiswa">🎓 Mahasiswa</option>
              <option value="Dosen">👨‍🏫 Dosen</option>
              <option value="Karyawan">💼 Karyawan</option>
              <option value="Umum">👤 Umum</option>
            </select>
            <span class="sel-arr">▾</span>
          </div>
        </div>
        <div class="field">
          <label class="lbl">No. WhatsApp</label>
          <input type="text" name="no_hp" id="edit-hp" inputmode="numeric"
            oninput="this.value=this.value.replace(/[^0-9]/g,'')" class="inp" required/>
        </div>
        <div class="field">
          <label class="lbl">Password Baru <span class="lbl-hint">(kosongkan jika tidak diganti)</span></label>
          <input type="password" name="password" id="edit-pw" class="inp"
            placeholder="Min 6 kar + angka + huruf besar" autocomplete="new-password"/>
        </div>
        <div class="btn-row">
          <button type="button" onclick="closeEdit()" class="btn-gray">Batal</button>
          <button type="submit" class="btn-blue">💾 Simpan</button>
        </div>
      </form>
    </div>
  </div>

</div><!-- end page -->

<form id="form-hapus" method="POST" action="/admin/kelola_user.php" style="display:none;">
  <input type="hidden" name="hapus_id" id="hapus-id">
</form>

<script>
  <?php if ($flash): ?>
  document.addEventListener('DOMContentLoaded', () => {
    Swal.fire({
      icon: '<?php echo $flash['type']; ?>',
      title: '<?php echo htmlspecialchars($flash['title']); ?>',
      text: '<?php echo htmlspecialchars($flash['message']); ?>',
      confirmButtonColor: '#2563eb',
      timer: 3500, timerProgressBar: true,
    });
  });
  <?php endif; ?>

  function openEdit(id, nik, nama, kat, hp) {
    document.getElementById('edit-id').value  = id;
    document.getElementById('edit-nik').value = nik;
    document.getElementById('edit-nama').value = nama;
    document.getElementById('edit-kat').value  = kat;
    document.getElementById('edit-hp').value   = hp;
    document.getElementById('edit-pw').value   = '';
    const w = document.getElementById('modal-wrap');
    w.classList.add('show');
    setTimeout(() => w.scrollIntoView({ behavior:'smooth', block:'start' }), 50);
  }

  function closeEdit() {
    document.getElementById('modal-wrap').classList.remove('show');
  }

  document.addEventListener('keydown', e => { if (e.key === 'Escape') closeEdit(); });

  function hapusUser(id, nama) {
    Swal.fire({
      title: 'Hapus Pasien?',
      html: `Yakin hapus <strong>${nama}</strong>? Tidak bisa dibatalkan.`,
      icon: 'warning', showCancelButton: true,
      confirmButtonText: '🗑️ Hapus', cancelButtonText: 'Batal',
      confirmButtonColor: '#ef4444', cancelButtonColor: '#6b7280',
    }).then(r => {
      if (r.isConfirmed) {
        document.getElementById('hapus-id').value = id;
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
