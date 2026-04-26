<?php
session_start();
if (isset($_SESSION['email']) || isset($_SESSION['nik'])) {
    header("Location: index.php");
    exit();
}

// Bikin Token CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// AMBIL PESAN ALERT (Supaya tidak nyasar ke Dashboard)
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Login — UNS Medical Center</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
  <style>
    *{font-family:'Plus Jakarta Sans',sans-serif;box-sizing:border-box;}
    body{background:linear-gradient(135deg,#dbeafe 0%,#e0f2fe 50%,#f0fdf4 100%);min-height:100vh;overflow-x:hidden;}
    .blob{position:fixed;border-radius:50%;filter:blur(60px);pointer-events:none;z-index:0;}
    .clay-card{background:#fff;border-radius:28px; box-shadow:8px 8px 24px rgba(99,149,210,.22),-2px -2px 8px rgba(255,255,255,.9), inset 3px 3px 8px rgba(255,255,255,.85),inset -3px -3px 8px rgba(180,210,245,.35); border:1.5px solid rgba(255,255,255,.75);}
    .clay-btn{border-radius:16px;font-weight:700;letter-spacing:.3px;border:none;cursor:pointer; box-shadow:5px 5px 14px rgba(59,130,246,.35),-1px -1px 5px rgba(255,255,255,.7), inset 2px 2px 5px rgba(255,255,255,.4),inset -2px -2px 5px rgba(37,99,235,.25); transition:all .18s ease;}
    .clay-btn:hover{transform:translateY(-2px) scale(1.01);}
    .clay-input{background:#f1f8ff;border:1.5px solid rgba(147,197,253,.6);border-radius:14px; box-shadow:inset 2px 2px 6px rgba(180,210,245,.4),inset -2px -2px 5px rgba(255,255,255,.85); outline:none;width:100%;padding:.75rem 1rem;font-size:.95rem;color:#1e293b; transition:border-color .2s,box-shadow .2s;}
    .input-wrap{position:relative;}
    .eye-btn{position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none; cursor:pointer;color:#94a3b8;padding:4px;transition:color .15s;}
    .tab-track{background:#f1f5f9;border-radius:14px;padding:4px;display:flex;gap:4px;position:relative;margin-bottom:1.25rem;}
    .tab-slider{position:absolute;top:4px;bottom:4px;border-radius:10px; background:linear-gradient(135deg,#3b82f6,#2563eb); box-shadow:3px 3px 10px rgba(59,130,246,.35); transition:left .28s, width .28s;}
    .tab-btn{flex:1;padding:.65rem;font-size:.875rem;font-weight:700;border:none;background:transparent; cursor:pointer;border-radius:10px;position:relative;z-index:1; transition:color .2s;}
    .tab-btn.active{color:#fff;} .tab-btn.inactive{color:#64748b;}
    .form-wrap{position:relative;overflow:hidden;}
    .form-slide{transition:opacity .25s ease, transform .25s ease;}
    .form-slide.out-left{opacity:0;transform:translateX(-24px);pointer-events:none;position:absolute;top:0;left:0;right:0;}
    .form-slide.out-right{opacity:0;transform:translateX(24px);pointer-events:none;position:absolute;top:0;left:0;right:0;}
    .form-slide.in{opacity:1;transform:translateX(0);}
    .left-panel{background:linear-gradient(145deg,#1e3a6e 0%,#1e40af 50%,#1d4ed8 100%);}
    .mobile-logo-wrap{display:flex;align-items:center;gap:10px;margin-bottom:1.5rem; background:linear-gradient(135deg,#1e40af,#1d4ed8); border-radius:16px;padding:10px 14px;}
    .mobile-logo-wrap span{font-weight:800;color:#fff;font-size:.9rem;}
    .mobile-logo-wrap small{color:rgba(255,255,255,.7);font-size:.7rem;}
    .feat-pill{display:inline-flex;align-items:center;gap:6px; background:rgba(255,255,255,.14);border:1px solid rgba(255,255,255,.22); border-radius:999px;padding:6px 14px;font-size:.78rem;font-weight:600;color:rgba(255,255,255,.9);}
    label{font-size:.83rem;font-weight:600;color:#374151;margin-bottom:4px;display:block;}

    /* INI CSS MODAL YANG HILANG */
    .modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.4);backdrop-filter:blur(4px); z-index:999;display:none;align-items:center;justify-content:center;padding:1rem;}
    .modal-overlay.show{display:flex;}
    @keyframes popIn{from{opacity:0;transform:scale(.85)}to{opacity:1;transform:scale(1)}}
    .pop-in{animation:popIn .3s cubic-bezier(.34,1.56,.64,1) both;}
  </style>
</head>
<body class="flex items-center justify-center min-h-screen px-3 py-8 relative overflow-x-hidden">
  <div class="blob w-72 h-72 bg-blue-200 opacity-40" style="top:-5rem;left:-5rem;"></div>
  <div class="blob w-96 h-96 bg-cyan-200 opacity-25" style="bottom:-5rem;right:-5rem;"></div>
  
  <div class="relative z-10 w-full max-w-4xl flex flex-col lg:flex-row rounded-[28px] lg:rounded-[32px] bg-white overflow-hidden" style="box-shadow:0 20px 70px rgba(30,58,138,.22)">
    
    <div class="left-panel hidden lg:flex flex-col justify-between p-10 w-80 flex-shrink-0">
      <div class="flex items-center gap-3">
        <img src="https://senirupa.fkip.uns.ac.id/wp-content/uploads/2021/07/logo_putih.png" alt="Logo UNS" class="h-10 object-contain"/>
        <div><p class="font-extrabold text-white text-sm">UNS Medical Center</p><p class="text-blue-200 text-xs">Survei Kepuasan Pasien</p></div>
      </div>
      <div class="my-4 flex justify-center"><img src="https://stories.freepiklabs.com/storage/4688/Doctors-01.svg" class="w-56 h-56 object-contain drop-shadow-lg"/></div>
      <div>
        <h2 class="font-extrabold text-white text-xl leading-tight mb-2">Bantu Kami Melayani<br/>Lebih Baik 💙</h2>
        <p class="text-blue-200 text-xs leading-6 mb-4">Isi survei kepuasan dan bantu UNS Medical Center terus berkembang.</p>
        <div class="flex flex-col gap-2">
          <span class="feat-pill">🏥 8 Poli Layanan Lengkap</span>
          <span class="feat-pill">⭐ Rating 4.7 / 5.0</span>
        </div>
      </div>
    </div>

    <div class="flex-1 p-8 lg:p-12 relative z-20 bg-white">
      <div class="mobile-logo-wrap lg:hidden">
        <img src="https://senirupa.fkip.uns.ac.id/wp-content/uploads/2021/07/logo_putih.png" class="h-8 object-contain flex-shrink-0"/>
        <div><span>UNS Medical Center</span><small>Survei Kepuasan Pasien</small></div>
      </div>

      <h1 class="font-extrabold text-gray-800 text-xl sm:text-2xl mb-1">Selamat Datang 👋</h1>
      <p class="text-gray-400 text-sm mb-5">Masuk atau buat akun untuk mengisi survei kepuasan pasien.</p>

      <div class="tab-track" id="tab-track">
        <div class="tab-slider" id="tab-slider"></div>
        <button class="tab-btn active" id="tab-login" onclick="switchTab('login')">Masuk</button>
        <button class="tab-btn inactive" id="tab-register" onclick="switchTab('register')">Daftar</button>
      </div>

      <div class="form-wrap" id="form-wrap">
        
        <form id="form-login" action="proses/prosesLogin.php" method="POST" class="form-slide in space-y-4">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
          <div>
            <label for="login-identitas">NIK / No. WhatsApp</label>
            <input name="identitas" id="login-identitas" type="text" placeholder="Masukkan NIK atau No. HP" class="clay-input" required/>
          </div>
          <div>
            <label for="login-password">Password</label>
            <div class="input-wrap">
              <input name="password" id="login-password" type="password" placeholder="Masukkan password" class="clay-input" style="padding-right:2.75rem" required/>
              <button type="button" class="eye-btn" onclick="togglePwd('login-password',this)">👁️</button>
            </div>
            <a href="#" onclick="openReset()" class="text-[11px] font-bold text-blue-500 hover:text-blue-700 block text-right mt-2 transition-colors">Lupa Password?</a>
          </div>
          <button type="submit" class="clay-btn w-full py-3.5 bg-gradient-to-r from-blue-500 to-blue-600 text-white text-sm">Masuk ke Sistem →</button>
        </form>

        <form id="form-register" action="proses/prosesRegister.php" method="POST" class="form-slide out-right space-y-4">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
          <div>
            <label for="reg-nik">NIK (Wajib 16 Digit)</label>
            <input name="nik" id="reg-nik" type="text" maxlength="16" oninput="this.value = this.value.replace(/[^0-9]/g, ''); validateNik(this)" placeholder="Contoh: 3519xxxxxxxxxxxx" class="clay-input" required/>
            <p id="nik-hint" class="text-[10px] mt-1 text-gray-400">Masukkan 16 digit NIK KTP Anda</p>
          </div>

          <div>
            <label for="reg-name">Nama Lengkap</label>
            <input name="nama" id="reg-name" type="text" placeholder="Nama Lengkap" class="clay-input" required/>
          </div>

          <div>
            <label for="reg-hp">No. WhatsApp</label>
            <input name="no_hp" id="reg-hp" type="text" oninput="this.value = this.value.replace(/[^0-9]/g, '')" placeholder="Contoh: 081234567890" class="clay-input" required/>
          </div>

          <div>
            <label for="reg-kategori">Saya adalah... <span style="color:#ef4444;">*</span></label>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:4px;" id="kat-group">
              <?php foreach ([
                ['Mahasiswa','🎓'],['Dosen','👨‍🏫'],['Karyawan','💼'],['Umum','👤']
              ] as [$v,$ic]): ?>
              <label style="cursor:pointer;">
                <input type="radio" name="kategori" value="<?php echo $v; ?>"
                  class="hidden" required onchange="onKatPick()">
                <div class="kat-reg-box" style="display:flex;align-items:center;gap:8px;padding:10px 12px;
                  border:1.5px solid rgba(147,197,253,.5);border-radius:12px;background:#f1f8ff;
                  transition:all .18s;font-size:.82rem;font-weight:600;color:#374151;">
                  <span><?php echo $ic; ?></span><span><?php echo $v; ?></span>
                </div>
              </label>
              <?php endforeach; ?>
            </div>
          </div>

          <div>
            <label for="reg-password">Buat Password</label>
            <div class="input-wrap mb-2">
              <input name="password" id="reg-password" type="password" oninput="checkStrength(this.value)" placeholder="Minimal 6 karakter..." class="clay-input" style="padding-right:2.75rem" required/>
              <button type="button" class="eye-btn" onclick="togglePwd('reg-password',this)">👁️</button>
            </div>
            
            <div class="w-full h-1.5 bg-gray-200 rounded-full overflow-hidden flex gap-1">
              <div id="strength-bar-1" class="h-full w-1/3 bg-gray-300 transition-all"></div>
              <div id="strength-bar-2" class="h-full w-1/3 bg-gray-300 transition-all"></div>
              <div id="strength-bar-3" class="h-full w-1/3 bg-gray-300 transition-all"></div>
            </div>
            <div class="mt-2 space-y-1">
              <p id="req-len" class="text-[10px] text-gray-400 flex items-center gap-1">❌ Minimal 6 Karakter</p>
              <p id="req-num" class="text-[10px] text-gray-400 flex items-center gap-1">❌ Mengandung Angka</p>
              <p id="req-up" class="text-[10px] text-gray-400 flex items-center gap-1">❌ Mengandung Huruf Besar</p>
            </div>
          </div>

          <button type="submit" id="reg-btn" class="clay-btn w-full py-3.5 bg-gradient-to-r from-cyan-500 to-blue-500 text-white text-sm opacity-50 cursor-not-allowed" disabled>
            Daftar Pasien Baru 🚀
          </button>
        </form>

      </div> </div> </div> <div id="reset-modal" class="modal-overlay" role="dialog" aria-modal="true">
    <div class="clay-card p-6 sm:p-7 max-w-xs sm:max-w-sm w-full pop-in mx-4">
      <h3 class="font-extrabold text-gray-800 text-lg mb-1 text-center">Lupa Password 🔐</h3>
      <p class="text-gray-400 text-[11px] mb-5 text-center leading-relaxed">Verifikasi identitas Anda dengan memasukkan NIK dan No. WhatsApp yang terdaftar untuk mereset sandi.</p>
      
      <form action="proses/prosesReset.php" method="POST" class="space-y-3">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
        <div>
          <label class="text-xs font-bold text-gray-600 mb-1 block">NIK KTP (16 Digit)</label>
          <input name="nik" type="text" maxlength="16" oninput="this.value = this.value.replace(/[^0-9]/g, '')" class="clay-input py-2.5 text-sm" placeholder="Masukkan 16 digit NIK..." required/>
        </div>
        <div>
          <label class="text-xs font-bold text-gray-600 mb-1 block">Nomor WhatsApp</label>
          <input name="no_hp" type="text" oninput="this.value = this.value.replace(/[^0-9]/g, '')" class="clay-input py-2.5 text-sm" placeholder="Contoh: 081234567890" required/>
        </div>
        <div>
          <label class="text-xs font-bold text-gray-600 mb-1 block">Password Baru</label>
          <input name="password_baru" type="password" class="clay-input py-2.5 text-sm" placeholder="Min 6 kar (Angka & Huruf Besar)" required/>
        </div>
        
        <div class="flex gap-3 mt-5 pt-2">
          <button type="button" onclick="closeReset()" class="flex-1 py-2.5 rounded-2xl bg-gray-100 text-gray-600 font-bold text-sm hover:bg-gray-200 transition">Batal</button>
          <button type="submit" class="clay-btn flex-1 py-2.5 bg-gradient-to-r from-blue-500 to-cyan-500 text-white text-sm">Ubah Sandi</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    /* Fungsi Modal Lupa Password */
    function openReset() { document.getElementById('reset-modal').classList.add('show'); }
    function closeReset() { document.getElementById('reset-modal').classList.remove('show'); }

    /* Fungsi Animasi Tab Login/Register */
    let currentTab = 'login';
    function positionSlider(tab){
      const track = document.getElementById('tab-track'), btn = document.getElementById('tab-'+tab), slider = document.getElementById('tab-slider');
      slider.style.left = (btn.getBoundingClientRect().left - track.getBoundingClientRect().left) + 'px';
      slider.style.width = btn.getBoundingClientRect().width + 'px';
    }
    function switchTab(tab){
      if(tab === currentTab) return;
      const wasLogin = currentTab === 'login';
      currentTab = tab;
      positionSlider(tab);
      document.getElementById('tab-login').className  = 'tab-btn ' + (tab==='login' ? 'active' : 'inactive');
      document.getElementById('tab-register').className = 'tab-btn ' + (tab==='register' ? 'active' : 'inactive');
      
      const inForm = document.getElementById('form-'+tab), outForm = document.getElementById('form-'+(tab==='login'?'register':'login'));
      outForm.classList.remove('in'); outForm.classList.add(wasLogin ? 'out-left' : 'out-right');
      inForm.classList.remove('out-left','out-right');
      requestAnimationFrame(()=> { requestAnimationFrame(()=> inForm.classList.add('in')); });
    }
    window.addEventListener('load', ()=> positionSlider('login'));
    
    /* Toggle Mata Password */
    function togglePwd(id, btn){
      const inp = document.getElementById(id);
      inp.type = inp.type === 'password' ? 'text' : 'password';
    }

    /* Validasi NIK agar tepat 16 digit */
    function validateNik(input) {
      const hint = document.getElementById('nik-hint');
      if (input.value.length === 16) {
        hint.textContent = "✅ NIK Valid (16 Digit)";
        hint.classList.replace('text-gray-400', 'text-green-500');
      } else {
        hint.textContent = `⚠️ Masih ${input.value.length} digit (Butuh 16)`;
        hint.classList.replace('text-green-500', 'text-gray-400');
      }
      checkAllValid();
    }

    /* Cek Kekuatan Password */
    function checkStrength(pwd) {
      const reqs = {
        len: pwd.length >= 6,
        num: /[0-9]/.test(pwd),
        up: /[A-Z]/.test(pwd)
      };

      updateReqUI('req-len', reqs.len);
      updateReqUI('req-num', reqs.num);
      updateReqUI('req-up', reqs.up);

      let score = Object.values(reqs).filter(Boolean).length;
      
      const bar1 = document.getElementById('strength-bar-1');
      const bar2 = document.getElementById('strength-bar-2');
      const bar3 = document.getElementById('strength-bar-3');

      [bar1, bar2, bar3].forEach(b => b.className = 'h-full w-1/3 bg-gray-300 transition-all');

      if (score >= 1) bar1.classList.replace('bg-gray-300', 'bg-red-500');
      if (score >= 2) bar2.classList.replace('bg-gray-300', 'bg-yellow-500');
      if (score >= 3) bar3.classList.replace('bg-gray-300', 'bg-green-500');

      checkAllValid();
    }

    function updateReqUI(id, isValid) {
      const el = document.getElementById(id);
      if (isValid) {
        el.innerHTML = "✅ " + el.innerHTML.split(' ').slice(1).join(' ');
        el.classList.replace('text-gray-400', 'text-green-600');
      } else {
        el.innerHTML = "❌ " + el.innerHTML.split(' ').slice(1).join(' ');
        el.classList.replace('text-green-600', 'text-gray-400');
      }
    }

    /* ── Kategori box style saat dipilih ── */
    function onKatPick() {
      document.querySelectorAll('[name=kategori]').forEach(inp => {
        const box = inp.nextElementSibling;
        if (inp.checked) {
          box.style.borderColor = '#2563eb';
          box.style.background  = '#eff6ff';
          box.style.color       = '#1d4ed8';
        } else {
          box.style.borderColor = 'rgba(147,197,253,.5)';
          box.style.background  = '#f1f8ff';
          box.style.color       = '#374151';
        }
      });
      checkAllValid();
    }

    /* Aktifkan tombol Daftar Pasien Baru */
    function checkAllValid() {
      const isNikValid = document.getElementById('reg-nik').value.length === 16;
      const pwd = document.getElementById('reg-password').value;
      const isPwdValid = pwd.length >= 6 && /[0-9]/.test(pwd) && /[A-Z]/.test(pwd);
      const isKatValid = !!document.querySelector('[name=kategori]:checked');
      
      const btn = document.getElementById('reg-btn');
      const allOk = isNikValid && isPwdValid && isKatValid;
      btn.disabled = !allOk;
      btn.classList.toggle('opacity-50', !allOk);
      btn.classList.toggle('cursor-not-allowed', !allOk);
    }
  </script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <?php if ($flash): ?>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      Swal.fire({
        icon: '<?php echo htmlspecialchars($flash["type"]); ?>',
        title: '<?php echo htmlspecialchars($flash["title"]); ?>',
        text: '<?php echo htmlspecialchars($flash["message"]); ?>',
        confirmButtonColor: '#2563eb',
        borderRadius: '20px'
      });
    });
  </script>
  <?php endif; ?>
</body>
</html>
</body>
</html>