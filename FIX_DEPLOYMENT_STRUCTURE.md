# FIX DEPLOYMENT STRUCTURE - CRITICAL ISSUE

## Error yang Terdeteksi:

```
PHP Fatal error: Failed opening required
'/home/mtsnkot4/siab.mtsn2kotamalang.sch.id/bootstrap/app.php'
```

## Analisis Masalah:

**Path yang digunakan:** `/home/mtsnkot4/siab.mtsn2kotamalang.sch.id/`

**Masalah:** File Laravel tidak ada atau struktur folder salah!

---

## CEK STRUKTUR SAAT INI

**Jalankan via SSH atau cPanel Terminal:**

```bash
# Cek struktur folder
ls -la /home/mtsnkot4/

# Cek apa yang ada di domain folder
ls -la /home/mtsnkot4/siab.mtsn2kotamalang.sch.id/

# Cek apakah ada folder aplikasi terpisah
ls -la /home/mtsnkot4/ | grep absensi
```

---

## STRUKTUR YANG BENAR untuk cPanel dengan Subdomain

Ada 2 pilihan struktur:

### **OPSI A: Semua di Document Root (Tidak Recommended)**

```
/home/mtsnkot4/
└── siab.mtsn2kotamalang.sch.id/  (document root)
    ├── app/
    ├── bootstrap/
    ├── config/
    ├── database/
    ├── public/
    │   ├── index.php
    │   ├── .htaccess
    │   └── build/
    ├── resources/
    ├── routes/
    ├── storage/
    ├── vendor/
    ├── .env
    └── artisan
```

**Masalah:** Semua file Laravel accessible via web (security risk!)

---

### **OPSI B: Aplikasi di Luar Document Root (RECOMMENDED)**

```
/home/mtsnkot4/
├── absensi_app/                    ← Aplikasi Laravel (private)
│   ├── app/
│   ├── bootstrap/
│   ├── config/
│   ├── database/
│   ├── resources/
│   ├── routes/
│   ├── storage/
│   ├── vendor/
│   ├── .env
│   └── artisan
│
└── siab.mtsn2kotamalang.sch.id/    ← Document root (public)
    ├── .htaccess
    ├── index.php                    ← Modified to point to ../absensi_app/
    ├── build/
    │   ├── manifest.json
    │   └── assets/
    ├── favicon.ico
    └── robots.txt
```

**Ini struktur yang paling aman dan recommended!**

---

## SOLUSI: Setup Struktur yang Benar

### LANGKAH 1: Identifikasi Struktur Saat Ini

```bash
# Cek apa yang ada sekarang
ls -la /home/mtsnkot4/

# Kemungkinan output:
# - siab.mtsn2kotamalang.sch.id/  (folder domain)
# - public_html/                   (jika ada)
# - absensi_app/                   (jika sudah upload)
```

---

### LANGKAH 2: Setup Folder Aplikasi (Jika Belum Ada)

**Via SSH:**

```bash
cd /home/mtsnkot4/

# Buat folder aplikasi (jika belum ada)
mkdir -p absensi_app

# Set permission
chmod 755 absensi_app
```

**Upload semua file Laravel ke `/home/mtsnkot4/absensi_app/`:**

**Via FTP/FileManager, upload:**
- `app/`
- `bootstrap/`
- `config/`
- `database/`
- `resources/`
- `routes/`
- `storage/`
- `vendor/` (atau install via composer)
- `.env`
- `artisan`
- `composer.json`
- `composer.lock`

---

### LANGKAH 3: Setup Document Root

**Opsi 3a: Via cPanel Subdomain Manager (EASIEST)**

1. Login ke **cPanel**
2. Klik **Subdomains**
3. Cari subdomain: `siab.mtsn2kotamalang.sch.id`
4. Klik **Manage** atau edit icon
5. Ubah **Document Root** menjadi: `/home/mtsnkot4/siab.mtsn2kotamalang.sch.id`
6. Atau jika ingin menggunakan folder berbeda, buat folder baru dan arahkan ke sana

**Opsi 3b: Manual Setup**

```bash
# Kosongkan folder domain (backup dulu jika perlu!)
cd /home/mtsnkot4/siab.mtsn2kotamalang.sch.id/

# Backup jika ada file penting
mkdir -p /home/mtsnkot4/backup_old
cp -r * /home/mtsnkot4/backup_old/ 2>/dev/null || true

# Hapus semua isi (HATI-HATI!)
rm -rf *
rm -rf .*  # Hapus hidden files kecuali . dan ..
```

---

### LANGKAH 4: Upload File Public

**Upload file dari `public/` ke `/home/mtsnkot4/siab.mtsn2kotamalang.sch.id/`:**

Via FTP/FileManager:
- `public/build/` → upload ke `/siab.mtsn2kotamalang.sch.id/build/`
- `public/.htaccess.production` → upload dan rename ke `/siab.mtsn2kotamalang.sch.id/.htaccess`
- `public/index.php.production` → upload dan rename ke `/siab.mtsn2kotamalang.sch.id/index.php`
- `public/favicon.ico` → upload ke `/siab.mtsn2kotamalang.sch.id/`
- `public/robots.txt` → upload ke `/siab.mtsn2kotamalang.sch.id/`

---

### LANGKAH 5: Edit index.php

**Edit `/home/mtsnkot4/siab.mtsn2kotamalang.sch.id/index.php`:**

```bash
cd /home/mtsnkot4/siab.mtsn2kotamalang.sch.id/
nano index.php
```

**Isi file harus seperti ini:**

```php
<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Path ke aplikasi Laravel
$appPath = __DIR__.'/../absensi_app';

// Maintenance mode check
if (file_exists($maintenance = $appPath.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register Composer autoloader
require $appPath.'/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once $appPath.'/bootstrap/app.php';

$kernel = $app->make(Kernel::class);

$response = $kernel->handle(
    $request = Request::capture()
)->send();

$kernel->terminate($request, $response);
```

**Save:** Ctrl+O, Enter, Ctrl+X

---

### LANGKAH 6: Edit .htaccess

**Edit `/home/mtsnkot4/siab.mtsn2kotamalang.sch.id/.htaccess`:**

```bash
nano .htaccess
```

**Pastikan berisi:**

```apache
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # Redirect Trailing Slashes
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    # Send Requests To Front Controller
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
```

**Save:** Ctrl+O, Enter, Ctrl+X

---

### LANGKAH 7: Setup .env

**Edit `/home/mtsnkot4/absensi_app/.env`:**

```bash
cd /home/mtsnkot4/absensi_app/
nano .env
```

**Pastikan:**

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://siab.mtsn2kotamalang.sch.id

# Database
DB_CONNECTION=pgsql
DB_HOST=localhost
DB_PORT=5432
DB_DATABASE=mtsnkot4_dbname
DB_USERNAME=mtsnkot4_dbuser
DB_PASSWORD=your_password

# JANGAN ada VITE_* variables!
```

**Save:** Ctrl+O, Enter, Ctrl+X

---

### LANGKAH 8: Set Permissions

```bash
cd /home/mtsnkot4/absensi_app/

# Set ownership
chown -R mtsnkot4:mtsnkot4 .

# Set permissions
chmod -R 755 .
chmod -R 775 storage bootstrap/cache

# Jika masih error:
chmod -R 777 storage bootstrap/cache
```

---

### LANGKAH 9: Install Dependencies & Migrate

```bash
cd /home/mtsnkot4/absensi_app/

# Install composer dependencies
composer install --no-dev --optimize-autoloader

# Generate key jika belum
php artisan key:generate

# Migrate database
php artisan migrate:fresh --force

# Seed data
php artisan db:seed --class=DatabaseSeeder

# Cache config
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

### LANGKAH 10: Verifikasi

**Test di browser:**

```
https://siab.mtsn2kotamalang.sch.id
```

**Harus muncul halaman login Laravel dengan styling yang benar!**

---

## VERIFIKASI STRUKTUR FINAL

```bash
# Cek struktur akhir
tree -L 2 /home/mtsnkot4/

# Atau:
ls -la /home/mtsnkot4/absensi_app/ | head -20
ls -la /home/mtsnkot4/siab.mtsn2kotamalang.sch.id/
```

**Output yang benar:**

```
/home/mtsnkot4/
├── absensi_app/
│   ├── app/
│   ├── bootstrap/
│   ├── .env
│   └── artisan
└── siab.mtsn2kotamalang.sch.id/
    ├── build/
    ├── .htaccess
    └── index.php
```

---

## TROUBLESHOOTING

### Error: "bootstrap/app.php not found"

**Cek path di index.php:**

```bash
cat /home/mtsnkot4/siab.mtsn2kotamalang.sch.id/index.php | grep appPath
```

Harus menunjuk ke folder yang benar (biasanya `../absensi_app`)

### Error: "Permission denied"

```bash
chmod -R 777 /home/mtsnkot4/absensi_app/storage
chmod -R 777 /home/mtsnkot4/absensi_app/bootstrap/cache
```

### Error: "Class not found"

```bash
cd /home/mtsnkot4/absensi_app/
composer dump-autoload --optimize
```

### Website masih error 500

**Cek log:**

```bash
tail -50 /home/mtsnkot4/absensi_app/storage/logs/laravel.log
```

---

## QUICK SETUP SCRIPT

Copy dan jalankan ini di SSH:

```bash
#!/bin/bash
cd /home/mtsnkot4/absensi_app/
composer install --no-dev --optimize-autoloader
composer dump-autoload --optimize
chmod -R 775 storage bootstrap/cache
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan migrate:fresh --force
php artisan db:seed --class=DatabaseSeeder
php artisan config:cache
php artisan route:cache
php artisan view:cache
echo "Setup complete! Test di browser: https://siab.mtsn2kotamalang.sch.id"
```

---

Jika masih ada masalah, kirim output dari:

```bash
ls -la /home/mtsnkot4/
ls -la /home/mtsnkot4/absensi_app/
cat /home/mtsnkot4/siab.mtsn2kotamalang.sch.id/index.php
```
