# 🚀 Panduan Deploy Sederhana - Subdomain cPanel

## Langkah Singkat Deploy ke Subdomain

### 1️⃣ Persiapan di Komputer (5 menit)

**A. Build Project**
```bash
npm install
npm run build
```

**B. Siapkan File .env untuk Production**
Edit file `.env`:
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://absensi.namadomain.com
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_DATABASE=nama_database
DB_USERNAME=user_database
DB_PASSWORD=password_database
```

**C. Buat ZIP File**
- Compress seluruh project (kecuali folder `node_modules` dan `.git`)
- Beri nama: `absensi.zip`

---

### 2️⃣ Setup Subdomain di cPanel (2 menit)

1. Login ke **cPanel**
2. Cari dan klik **Subdomains**
3. Buat subdomain baru:
   - **Subdomain**: `absensi`
   - **Domain**: `namadomain.com`
   - **Document Root**: Otomatis jadi `/public_html/absensi` (biarkan)
4. Klik **Create**

---

### 3️⃣ Buat Database (2 menit)

1. Di cPanel, cari **PostgreSQL Databases** (atau MySQL Databases)
2. **Create Database**:
   - Nama: `absensi_db`
3. **Create User**:
   - Username: `absensi_user`
   - Password: (gunakan password kuat)
4. **Add User to Database**:
   - Pilih user dan database
   - Centang **ALL PRIVILEGES**
5. **Catat**: nama database, username, password

---

### 4️⃣ Upload File (5 menit)

1. Buka **File Manager** di cPanel
2. Navigasi ke `/public_html/`
3. Buat folder baru: `absensi_app`
4. Masuk ke folder `absensi_app`
5. Upload file `absensi.zip`
6. Klik kanan → **Extract**
7. Tunggu hingga selesai

---

### 5️⃣ Pindahkan File Public (3 menit)

1. Buka folder `public_html/absensi_app/public`
2. **Select All** (pilih semua file & folder di dalam public)
3. Klik **Copy** atau **Move**
4. Tujuan: `/public_html/absensi/`
5. Pastikan isi folder public sudah pindah ke `/public_html/absensi/`

**Struktur Akhir:**
```
/public_html/
├── absensi_app/          ← Aplikasi Laravel
│   ├── app/
│   ├── config/
│   ├── storage/
│   ├── .env
│   └── ...
└── absensi/              ← Subdomain (isi dari folder public)
    ├── build/
    ├── index.php
    └── .htaccess
```

---

### 6️⃣ Edit File index.php (2 menit)

1. Buka file `/public_html/absensi/index.php`
2. **Ganti baris** yang ada dengan ini:

```php
<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Maintenance mode
if (file_exists($maintenance = __DIR__.'/../../absensi_app/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Composer autoloader
require __DIR__.'/../../absensi_app/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__.'/../../absensi_app/bootstrap/app.php';

$kernel = $app->make(Kernel::class);

$response = $kernel->handle(
    $request = Request::capture()
)->send();

$kernel->terminate($request, $response);
```

3. **Save**

---

### 7️⃣ Set Permission (2 menit)

Di File Manager:

1. Klik kanan folder `/public_html/absensi_app/storage`
   - Change Permissions → **755** atau **775**

2. Klik kanan folder `/public_html/absensi_app/bootstrap/cache`
   - Change Permissions → **755** atau **775**

---

### 8️⃣ Buat Storage Link (2 menit)

**Cara 1: Via SSH** (jika ada akses SSH)
```bash
cd /public_html/absensi
ln -s ../../absensi_app/storage/app/public storage
```

**Cara 2: Via PHP Script** (jika tidak ada SSH)

1. Buat file baru di `/public_html/absensi/` bernama `link.php`
2. Isi file:
```php
<?php
symlink('../../absensi_app/storage/app/public', 'storage');
echo "Storage link created!";
```
3. Akses: `https://absensi.namadomain.com/link.php`
4. **HAPUS file `link.php`** setelah berhasil!

---

### 9️⃣ Jalankan Migration (3 menit)

**Cara 1: Via SSH** (jika ada akses SSH)
```bash
cd /public_html/absensi_app
php artisan migrate --force
```

**Cara 2: Via PHP Script** (jika tidak ada SSH)

1. Buat file `/public_html/absensi/migrate.php`
2. Isi file:
```php
<?php
require __DIR__.'/../../absensi_app/vendor/autoload.php';
$app = require_once __DIR__.'/../../absensi_app/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->call('migrate', ['--force' => true]);
echo "Migration complete!";
```
3. Akses: `https://absensi.namadomain.com/migrate.php`
4. **HAPUS file `migrate.php`** segera setelah selesai!

---

### 🔟 Test Aplikasi (2 menit)

Buka browser, akses: `https://absensi.namadomain.com`

**Checklist:**
- ✅ Halaman login muncul
- ✅ CSS dan tampilan normal (tidak berantakan)
- ✅ Bisa login
- ✅ Upload foto berfungsi
- ✅ Tidak ada error

---

## 🛠️ Troubleshooting Cepat

### ❌ Error 500
**Solusi:**
1. Check permissions folder `storage` dan `bootstrap/cache` (755/775)
2. Pastikan `.env` sudah benar (database, APP_KEY)
3. Lihat error di: `/public_html/absensi_app/storage/logs/laravel.log`

### ❌ Tampilan Berantakan (CSS tidak load)
**Solusi:**
1. Pastikan folder `build` sudah ada di `/public_html/absensi/build`
2. Clear cache browser (Ctrl + Shift + Delete)
3. Check file `.htaccess` ada di `/public_html/absensi/`

### ❌ Database Connection Error
**Solusi:**
1. Cek lagi `.env`: DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD
2. Pastikan database user punya akses ALL PRIVILEGES
3. Coba ganti `DB_HOST=localhost` atau `DB_HOST=127.0.0.1`

### ❌ Upload Foto Tidak Bisa
**Solusi:**
1. Pastikan storage link sudah dibuat
2. Check: `ls -la /public_html/absensi/storage` (via SSH)
3. Atau check di File Manager, ada folder `storage` di subdomain

---

## 📝 Catatan Penting

1. **APP_DEBUG=false** → Jangan lupa set false di production!
2. **APP_KEY** → Harus ada di .env (jika belum: `php artisan key:generate`)
3. **HTTPS** → Pastikan SSL sudah aktif untuk subdomain
4. **Backup** → Backup database dan files secara berkala

---

## ✅ Selesai!

Jika semua langkah diikuti, aplikasi absensi sudah bisa diakses di:
**https://absensi.namadomain.com**

Untuk panduan lengkap, lihat: `DEPLOYMENT.md`

---

**Update**: 2025-10-22
