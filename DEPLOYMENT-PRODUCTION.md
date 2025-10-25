# 🚀 Panduan Deployment Production - Sistem Absensi MTsN 2 Kota Malang

## 📋 Daftar Isi
1. [Persiapan](#persiapan)
2. [Upload ke Server](#upload-ke-server)
3. [Konfigurasi Production](#konfigurasi-production)
4. [Setup Storage Selfie](#setup-storage-selfie)
5. [Verifikasi Deployment](#verifikasi-deployment)
6. [Testing](#testing)
7. [Troubleshooting](#troubleshooting)

---

## 🎯 Persiapan

### Requirement Server Production:
- PHP >= 8.2
- PostgreSQL >= 13
- Apache/Nginx dengan mod_rewrite
- Composer
- Extension PHP: pdo, pdo_pgsql, mbstring, gd, fileinfo

### File yang Perlu Disiapkan:
- Semua file Laravel project
- Database backup (jika ada data dari development)

---

## 📤 Upload ke Server

### 1. Struktur Folder di cPanel

Pastikan struktur folder seperti ini:
```
/home/username/siab.mtsn2kotamalang.sch.id/
├── app/
├── bootstrap/
├── config/
├── database/
├── public/              ← DOCUMENT ROOT (penting!)
├── resources/
├── routes/
├── storage/             ← Folder untuk selfie (TANPA SYMLINK)
│   ├── selfies/         ← Foto absensi disimpan di sini
│   │   ├── employee/
│   │   └── student/
│   ├── app/
│   ├── framework/
│   └── logs/
├── vendor/
├── .env                 ← File konfigurasi (buat baru di production)
├── artisan
├── composer.json
└── setup-production-storage.php
```

### 2. Upload Files

**Via cPanel File Manager:**
1. Compress semua file project menjadi `.zip`
2. Upload file `.zip` ke folder `/home/username/siab.mtsn2kotamalang.sch.id/`
3. Extract file di server

**Via FTP/SFTP:**
1. Upload semua file kecuali:
   - `node_modules/` (akan diinstall ulang)
   - `.env` (buat baru di production)
   - `storage/` (akan dibuat otomatis)

### 3. Set Document Root

Di cPanel → Domains → domain siab.mtsn2kotamalang.sch.id:
- Document Root: `/home/username/siab.mtsn2kotamalang.sch.id/public`

---

## ⚙️ Konfigurasi Production

### 1. Buat File .env

Copy dari `.env.example` atau buat baru:

```bash
cd /home/username/siab.mtsn2kotamalang.sch.id
cp .env.example .env
```

Edit file `.env`:

```env
APP_NAME="MTsN 2 Absensi"
APP_ENV=production
APP_KEY=                          # Generate nanti
APP_DEBUG=false
APP_URL=https://siab.mtsn2kotamalang.sch.id
APP_TIMEZONE=Asia/Jakarta

# Database Production
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=nama_database_production
DB_USERNAME=username_database
DB_PASSWORD=password_database

# Session & Cache
SESSION_DRIVER=database
CACHE_STORE=database

# File System
FILESYSTEM_DISK=local

# Mail (jika ada)
MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=your-email@example.com
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@siab.mtsn2kotamalang.sch.id"
MAIL_FROM_NAME="${APP_NAME}"
```

### 2. Generate App Key

```bash
php artisan key:generate
```

### 3. Install Dependencies

```bash
composer install --optimize-autoloader --no-dev
```

### 4. Cache Configuration

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 5. Run Migrations

```bash
php artisan migrate --force
```

**PENTING:** `--force` diperlukan di production untuk konfirmasi.

### 6. Seed Data (jika perlu)

```bash
php artisan db:seed --force
```

---

## 📁 Setup Storage Selfie

### Cara 1: Menggunakan Script Otomatis (RECOMMENDED)

```bash
cd /home/username/siab.mtsn2kotamalang.sch.id
php setup-production-storage.php
```

Script ini akan:
- ✅ Membuat folder `storage/selfies/employee/`
- ✅ Membuat folder `storage/selfies/student/`
- ✅ Set permission yang benar (755)
- ✅ Membuat `.htaccess` untuk security
- ✅ Test write permission

### Cara 2: Manual

```bash
# Buat folder
mkdir -p storage/selfies/employee
mkdir -p storage/selfies/student

# Set permissions
chmod -R 755 storage
chmod -R 755 bootstrap/cache

# Test write permission
touch storage/selfies/employee/.test
rm storage/selfies/employee/.test
```

### Verifikasi Struktur Folder

Setelah setup, pastikan struktur seperti ini:

```
storage/
├── .htaccess           ← PENTING: Protect direct access
├── selfies/
│   ├── employee/      ← Foto pegawai
│   └── student/       ← Foto siswa
├── app/
├── framework/
│   ├── cache/
│   ├── sessions/
│   └── views/
└── logs/
```

---

## ✅ Verifikasi Deployment

### 1. Jalankan Script Verifikasi

```bash
php verify-production.php
```

Script ini akan mengecek:
- ✓ Konfigurasi .env
- ✓ Struktur folder
- ✓ Permissions
- ✓ File-file penting
- ✓ Write capability

### 2. Cek Manual

**Test Aplikasi Bisa Diakses:**
```
https://siab.mtsn2kotamalang.sch.id
```

**Test Route Selfie (harus login dulu):**
```
https://siab.mtsn2kotamalang.sch.id/selfies/employee/test.jpg
```
Harusnya return 404 (karena file belum ada) bukan 403/500.

---

## 🧪 Testing

### 1. Test Login
1. Akses: `https://siab.mtsn2kotamalang.sch.id/login`
2. Login dengan user admin
3. Pastikan dashboard tampil

### 2. Test Upload Foto Absensi (Pegawai)
1. Login sebagai pegawai
2. Pergi ke menu Absensi
3. Ambil foto selfie dan absen masuk
4. **Cek folder server:** `storage/selfies/employee/`
   - Harus ada file foto baru
   - Format: `employee_[ID]_in_[timestamp].jpg`

### 3. Test Upload Foto Absensi (Siswa)
1. Akses halaman absensi siswa
2. Pilih kelas dan siswa
3. Ambil foto dan absen masuk
4. **Cek folder server:** `storage/selfies/student/`
   - Harus ada file foto baru
   - Format: `student_[ID]_in_[timestamp].jpg`

### 4. Test Lihat Foto
1. Login sebagai admin/monitoring
2. Pergi ke menu Monitoring → Pegawai/Siswa
3. Klik foto selfie yang sudah diupload
4. Foto harus tampil dengan benar

### 5. Test Security
1. Coba akses foto via URL langsung (tanpa login):
   ```
   https://siab.mtsn2kotamalang.sch.id/selfies/employee/employee_1_in_123.jpg
   ```
   Harus redirect ke login (401/403)

2. Coba akses storage langsung:
   ```
   https://siab.mtsn2kotamalang.sch.id/storage/selfies/employee/employee_1_in_123.jpg
   ```
   Harus error 403 Forbidden (.htaccess protection)

---

## 🔒 Keamanan Production

### 1. File .htaccess di Storage

File `storage/.htaccess` sudah dibuat otomatis untuk block direct access:

```apache
# Deny all direct access to storage folder
<IfModule mod_authz_core.c>
    Require all denied
</IfModule>
```

### 2. Permissions yang Aman

```bash
# Folders
chmod 755 storage
chmod 755 storage/selfies
chmod 755 storage/selfies/employee
chmod 755 storage/selfies/student

# Laravel framework folders
chmod -R 755 storage/framework
chmod -R 755 bootstrap/cache

# Jangan pakai 777! Berbahaya!
```

### 3. Hide Sensitive Files

Pastikan file-file ini **TIDAK** accessible via browser:
- `.env` - Sudah di-block oleh Laravel `.htaccess`
- `composer.json` - Sudah di-block
- `setup-production-storage.php` - **HAPUS setelah setup!**
- `verify-production.php` - **HAPUS setelah verify!**

```bash
# Hapus script setup setelah selesai
rm setup-production-storage.php
rm verify-production.php
```

---

## 🐛 Troubleshooting

### Error: "Permission denied" saat upload foto

**Penyebab:** Folder `storage/selfies` tidak writable

**Solusi:**
```bash
chmod -R 755 storage
chown -R username:username storage  # Ganti 'username' dengan user cPanel
```

### Error: "File not found" saat lihat foto

**Penyebab:** Path salah atau file tidak ada

**Solusi:**
1. Cek di server apakah file benar-benar ada:
   ```bash
   ls -la storage/selfies/employee/
   ```

2. Cek di database path yang tersimpan:
   ```sql
   SELECT selfie_path_in FROM attendances WHERE selfie_path_in IS NOT NULL LIMIT 5;
   ```
   Path harus: `selfies/employee/filename.jpg`

3. Cek APP_ENV di `.env`:
   ```env
   APP_ENV=production  # HARUS production!
   ```

### Error: 403 Forbidden saat akses /selfies/employee/xxx.jpg

**Penyebab:** User tidak punya permission atau belum login

**Solusi:**
1. Pastikan sudah login
2. Cek role user (admin/monitoring bisa lihat semua, pegawai hanya bisa lihat miliknya)
3. Cek log error:
   ```bash
   tail -f storage/logs/laravel.log
   ```

### Foto ukurannya terlalu besar

**Penyebab:** Kompresi tidak bekerja

**Solusi:**
1. Pastikan extension GD terinstall:
   ```bash
   php -m | grep -i gd
   ```

2. Cek di kode controller bahwa compression berjalan
3. Foto otomatis di-compress max 500KB dengan quality 30-85

### Database connection error

**Penyebab:** Kredensial database salah atau PostgreSQL tidak running

**Solusi:**
1. Cek kredensial di `.env`
2. Test koneksi database:
   ```bash
   php artisan tinker
   >>> DB::connection()->getPdo();
   ```

---

## 📊 Monitoring Production

### 1. Log Files

```bash
# Application logs
tail -f storage/logs/laravel.log

# Apache/Nginx error logs (cPanel)
tail -f ~/logs/siab.mtsn2kotamalang.sch.id-error_log
```

### 2. Storage Usage

```bash
# Cek ukuran folder selfies
du -sh storage/selfies/

# Cek jumlah file
find storage/selfies/ -type f | wc -l
```

### 3. Database Backup

**Setup Auto Backup (jika ada):**
```bash
# Di cron job (via cPanel)
0 2 * * * cd /home/username/siab.mtsn2kotamalang.sch.id && php artisan backup:run
```

---

## 📝 Maintenance Mode

### Aktifkan Maintenance Mode:
```bash
php artisan down --refresh=15 --secret="rahasia123"
```

Akses: `https://siab.mtsn2kotamalang.sch.id/rahasia123` tetap bisa diakses

### Nonaktifkan Maintenance Mode:
```bash
php artisan up
```

---

## 🔄 Update Production

Jika ada update kode:

```bash
# 1. Backup dulu
cp -r siab.mtsn2kotamalang.sch.id siab.mtsn2kotamalang.sch.id.backup

# 2. Aktifkan maintenance
php artisan down

# 3. Pull/upload kode baru

# 4. Install dependencies
composer install --optimize-autoloader --no-dev

# 5. Run migrations (jika ada)
php artisan migrate --force

# 6. Clear & cache
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 7. Nonaktifkan maintenance
php artisan up
```

---

## 📞 Support

Jika ada masalah, cek:
1. `storage/logs/laravel.log` - Application errors
2. Server error logs via cPanel
3. Browser console (F12) - JavaScript errors
4. Network tab (F12) - Failed requests

---

## ✅ Checklist Deployment

Sebelum go-live, pastikan semua ini sudah dicek:

- [ ] File `.env` sudah dikonfigurasi dengan benar
- [ ] `APP_ENV=production` dan `APP_DEBUG=false`
- [ ] Database production sudah disetup dan migrate
- [ ] Folder `storage/selfies/employee/` dan `student/` ada dan writable
- [ ] File `storage/.htaccess` ada untuk protect direct access
- [ ] Script `setup-production-storage.php` sudah dijalankan
- [ ] Script `verify-production.php` menunjukkan "READY FOR PRODUCTION"
- [ ] Test upload foto pegawai berhasil
- [ ] Test upload foto siswa berhasil
- [ ] Test lihat foto via browser berhasil
- [ ] Test security: direct access ke storage di-block
- [ ] Composer dependencies terinstall
- [ ] Cache config/route/view sudah di-cache
- [ ] Script setup sudah dihapus dari server
- [ ] Backup database sudah dibuat
- [ ] SSL certificate sudah aktif (HTTPS)

---

**Selamat! Aplikasi Anda siap digunakan di production! 🎉**
