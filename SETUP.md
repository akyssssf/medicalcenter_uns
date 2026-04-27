# Setup Guide — UNS Medical Center

## Prasyarat
- Akun Vercel
- Database MySQL/MariaDB yang bisa diakses dari internet (PlanetScale, Railway, Aiven, dll)

## Langkah Deploy

### 1. Import Database
Import file `uns_medicalcenterDB.sql` ke database MySQL kamu.
**Penting:** Tabel `sessions` WAJIB ada agar login/register/survei berfungsi.

### 2. Set Environment Variables di Vercel
Di dashboard Vercel → Settings → Environment Variables, tambahkan:
```
DB_HOST     = <host database kamu>
DB_USER     = <username database>
DB_PASS     = <password database>
DB_NAME     = uns_medicalcenterDB
DB_PORT     = 3306
```

### 3. Deploy ke Vercel
```bash
vercel --prod
```

## Akun Demo

| Role  | NIK              | Password   |
|-------|------------------|------------|
| Admin | 1111111111111111 | Admin123   |
| User  | 3514010101010001 | (lihat db) |

## Fitur

| Fitur          | URL               | Status |
|----------------|-------------------|--------|
| Landing Page   | /                 | ✅     |
| Login          | /login.php        | ✅     |
| Register       | /login.php (tab)  | ✅     |
| Lupa Password  | /login.php (modal)| ✅     |
| Dashboard User | /dashboard.php    | ✅     |
| Survei         | /survei.php       | ✅     |
| Riwayat        | /riwayat_kunjungan.php | ✅ |
| Admin Panel    | /admin/dashboard.php | ✅  |

## Alur Fitur

### Login
- Masuk menggunakan NIK atau No. HP + password
- Rate limiting: 5x percobaan → dikunci 15 menit

### Register
- NIK 16 digit, nama, No. HP, kategori, password (min 6 char + angka + huruf kapital)

### Lupa Password
- Verifikasi dengan NIK + No. HP yang terdaftar
- Reset password langsung (tanpa email)

### Survei
**Jalur Kunjungan (via token/tanggal):**
1. Login → Dashboard → Masukkan token atau pilih tanggal kunjungan
2. Sistem verifikasi kunjungan di database
3. Isi survei (3 langkah)

**Jalur Umum:**
1. Login → Dashboard → Klik "Isi Survei Tanpa Kunjungan"
2. Isi survei (3 langkah)

## Troubleshooting

### Session tidak tersimpan / selalu logout
→ Pastikan tabel `sessions` sudah dibuat di database

### Koneksi database gagal (Error 503)
→ Cek environment variables di Vercel, pastikan DB bisa diakses dari IP Vercel

### SSL error saat koneksi DB
→ Jika DB tidak support SSL, ganti di `bootstrap.php`:
   Hapus baris `mysqli_ssl_set(...)` dan ubah flag `MYSQLI_CLIENT_SSL` menjadi `0`
