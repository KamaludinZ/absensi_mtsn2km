# Panduan Deployment ke cPanel

## 📋 Persyaratan Server

### Minimum Requirements:
- **PHP**: 8.2 atau lebih tinggi
- **PostgreSQL**: 12 atau lebih tinggi (atau MySQL 5.7+/MariaDB 10.3+)
- **Composer**: 2.x
- **Node.js**: 18.x atau lebih tinggi (untuk build assets)
- **Memory Limit**: 512MB (recommended)
- **Max Execution Time**: 300 detik

### PHP Extensions yang Diperlukan:
```
- BCMath
- Ctype
- Fileinfo
- JSON
- Mbstring
- OpenSSL
- PDO
- PDO_PGSQL (atau PDO_MYSQL)
- Tokenizer
- XML
- GD atau Imagick (untuk image processing)
- Zip
```

---

## 🔧 Persiapan Sebelum Deploy

### 1. Build Assets di Local
```bash
# Install dependencies
npm install

# Build untuk production
npm run build

# Pastikan folder public/build sudah terisi
```

### 2. Optimize Laravel
```bash
# Clear semua cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Optimize untuk production
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 3. Update .env untuk Production
```env
APP_NAME="Absensi MTsN 2 Kota Malang"
APP_ENV=production
APP_KEY=base64:YOUR_APP_KEY_HERE
APP_DEBUG=false
APP_URL=https://yourdomain.com

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=your_database_name
DB_USERNAME=your_database_user
DB_PASSWORD=your_database_password

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120

# Nonaktifkan vite di production
VITE_ENABLED=false
```

---

## 📦 File yang Perlu Di-Upload

### File/Folder yang HARUS di-upload:
```
✅ app/
✅ bootstrap/
✅ config/
✅ database/
✅ public/ (termasuk public/build)
✅ resources/
✅ routes/
✅ storage/
✅ vendor/ (hasil composer install)
✅ .env (sudah dikonfigurasi untuk production)
✅ artisan
✅ composer.json
✅ composer.lock
```

### File/Folder yang TIDAK perlu di-upload:
```
❌ node_modules/
❌ .git/
❌ .env.example
❌ tests/
❌ phpunit.xml
❌ vite.config.js
❌ package.json
❌ package-lock.json
❌ README.md
```

---

## 🚀 Langkah-langkah Deployment

### Opsi 1: Upload via File Manager cPanel

#### Step 1: Compress Project
Di local, compress seluruh project (kecuali folder yang tidak perlu):
```bash
# Di Windows
# Buat ZIP file manual atau gunakan command
tar -czf absensi.tar.gz --exclude=node_modules --exclude=.git .

# Atau buat ZIP via File Explorer
```

#### Step 2: Upload ke cPanel
1. Login ke cPanel
2. Buka **File Manager**
3. Navigate ke `public_html` atau folder domain Anda
4. Upload file `absensi.tar.gz`
5. Extract file tersebut
6. **Penting**: Pindahkan isi folder `public` ke `public_html`

#### Step 3: Struktur Folder di Server
```
/home/username/
├── absensi_app/          # Folder aplikasi Laravel (di luar public_html)
│   ├── app/
│   ├── bootstrap/
│   ├── config/
│   ├── database/
│   ├── resources/
│   ├── routes/
│   ├── storage/
│   ├── vendor/
│   └── .env
│
└── public_html/          # Document root (isi dari folder public Laravel)
    ├── build/
    ├── storage/          # Symlink ke ../absensi_app/storage/app/public
    ├── .htaccess
    └── index.php         # Modified to point to ../absensi_app/bootstrap/app.php
```

### Opsi 2: Upload via FTP/SFTP

1. Gunakan FileZilla atau WinSCP
2. Upload semua file sesuai struktur di atas
3. Pastikan permissions sudah benar

---

## 🔐 Konfigurasi Server

### 1. Setup Database di cPanel

1. Login ke cPanel
2. Buka **PostgreSQL Databases** (atau MySQL Databases)
3. **Create Database**:
   - Database name: `absensi_db`
4. **Create User**:
   - Username: `absensi_user`
   - Password: (gunakan password yang kuat)
5. **Add User to Database**:
   - Select user dan database
   - Grant ALL PRIVILEGES
6. Catat informasi:
   - Database Host (biasanya `localhost` atau `127.0.0.1`)
   - Database Name
   - Username
   - Password

### 2. Update File index.php di public_html

Edit `public_html/index.php`:
```php
<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../absensi_app/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../absensi_app/vendor/autoload.php';

// Bootstrap Laravel and handle the request...
$app = require_once __DIR__.'/../absensi_app/bootstrap/app.php';

$kernel = $app->make(Kernel::class);

$response = $kernel->handle(
    $request = Request::capture()
)->send();

$kernel->terminate($request, $response);
```

### 3. Setup .htaccess di public_html

Buat/Edit `public_html/.htaccess`:
```apache
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # Redirect Trailing Slashes If Not A Folder...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    # Send Requests To Front Controller...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>

# Disable directory browsing
Options -Indexes

# Protect .env file
<FilesMatch "^\.env">
    Order allow,deny
    Deny from all
</FilesMatch>

# PHP configuration
<IfModule mod_php8.c>
    php_value upload_max_filesize 20M
    php_value post_max_size 20M
    php_value max_execution_time 300
    php_value max_input_time 300
    php_value memory_limit 512M
</IfModule>
```

### 4. Set Permissions

Via SSH atau File Manager, set permissions:
```bash
# Set permission untuk storage dan cache
chmod -R 775 storage/
chmod -R 775 bootstrap/cache/

# Set ownership (jika menggunakan SSH)
chown -R username:username storage/
chown -R username:username bootstrap/cache/
```

Di cPanel File Manager:
- Klik kanan folder `storage` → Change Permissions → 755 atau 775
- Klik kanan folder `bootstrap/cache` → Change Permissions → 755 atau 775

### 5. Create Storage Symlink

Via SSH:
```bash
cd public_html
ln -s ../absensi_app/storage/app/public storage
```

Atau via PHP (buat file `create_symlink.php` di public_html):
```php
<?php
symlink('../absensi_app/storage/app/public', 'storage');
echo "Symlink created successfully!";
// Hapus file ini setelah dijalankan
```

Akses `https://yourdomain.com/create_symlink.php`, lalu hapus file tersebut.

---

## 🗄️ Database Migration

### Via SSH (Recommended):
```bash
cd /home/username/absensi_app
php artisan migrate --force
php artisan db:seed --force
```

### Via PHP Script (jika tidak ada SSH):
Buat file `migrate.php` di public_html:
```php
<?php
// HAPUS FILE INI SETELAH SELESAI MIGRASI!

require __DIR__.'/../absensi_app/vendor/autoload.php';
$app = require_once __DIR__.'/../absensi_app/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$status = $kernel->call('migrate', ['--force' => true]);

echo $status === 0 ? 'Migration successful!' : 'Migration failed!';
echo "\n\nIMPORTANT: Delete this file immediately!";
```

Akses `https://yourdomain.com/migrate.php`, lalu **HAPUS file ini segera!**

---

## ⚙️ Konfigurasi PHP di cPanel

### Via php.ini atau .user.ini

Buat file `.user.ini` di folder `absensi_app/`:
```ini
upload_max_filesize = 20M
post_max_size = 20M
max_execution_time = 300
max_input_time = 300
memory_limit = 512M
```

### Via cPanel MultiPHP INI Editor

1. Login ke cPanel
2. Buka **MultiPHP INI Editor**
3. Pilih domain Anda
4. Set:
   - `upload_max_filesize`: 20M
   - `post_max_size`: 20M
   - `max_execution_time`: 300
   - `memory_limit`: 512M

---

## 🔒 Security Checklist

### ✅ Sebelum Go Live:

1. **Environment**:
   - [ ] `APP_ENV=production`
   - [ ] `APP_DEBUG=false`
   - [ ] `APP_KEY` sudah di-generate

2. **Database**:
   - [ ] Credentials aman
   - [ ] Database user hanya punya akses yang diperlukan

3. **File Permissions**:
   - [ ] `.env` tidak bisa diakses publik (chmod 600)
   - [ ] `storage/` writable (755 atau 775)
   - [ ] `bootstrap/cache/` writable (755 atau 775)

4. **Web Security**:
   - [ ] Force HTTPS enabled
   - [ ] CSRF protection aktif
   - [ ] Rate limiting configured

5. **Cleanup**:
   - [ ] Hapus file migrasi temporary
   - [ ] Hapus file test/debug
   - [ ] Hapus `.git` folder

---

## 🧪 Testing Setelah Deploy

### Checklist Testing:

1. **Homepage**:
   - [ ] Halaman utama bisa diakses
   - [ ] CSS/JS loaded dengan benar
   - [ ] Tidak ada error di console browser

2. **Authentication**:
   - [ ] Login pegawai berfungsi
   - [ ] Login admin berfungsi
   - [ ] Login monitoring berfungsi
   - [ ] Logout berfungsi

3. **Upload Files**:
   - [ ] Upload foto absensi berfungsi
   - [ ] Upload dokumen perizinan berfungsi
   - [ ] File bisa dilihat

4. **Database Operations**:
   - [ ] CRUD pegawai berfungsi
   - [ ] CRUD siswa berfungsi
   - [ ] Absensi berfungsi
   - [ ] Laporan bisa di-generate

5. **Performance**:
   - [ ] Halaman load < 3 detik
   - [ ] Tidak ada N+1 queries
   - [ ] Cache berfungsi

---

## 🔧 Troubleshooting

### Error: "500 Internal Server Error"

**Solusi**:
1. Check error logs di cPanel → Error Logs
2. Pastikan `.env` sudah benar
3. Check permissions storage dan bootstrap/cache
4. Run `php artisan config:clear`

### Error: "No application encryption key has been specified"

**Solusi**:
```bash
php artisan key:generate --force
```

### Error: Database Connection Failed

**Solusi**:
1. Verify database credentials di `.env`
2. Check database host (bisa `localhost`, `127.0.0.1`, atau hostname lain)
3. Pastikan database user punya privileges

### Assets Tidak Load (404)

**Solusi**:
1. Pastikan folder `public/build` sudah di-upload
2. Check `.htaccess` di public_html
3. Verify symlink storage sudah dibuat

### Permission Denied Errors

**Solusi**:
```bash
chmod -R 775 storage/
chmod -R 775 bootstrap/cache/
```

---

## 📊 Monitoring & Maintenance

### Log Files:
- Application logs: `storage/logs/laravel.log`
- Error logs: cPanel → Error Logs
- Access logs: cPanel → Raw Access Logs

### Regular Maintenance:
```bash
# Clear cache (via SSH atau cron)
php artisan cache:clear
php artisan view:clear

# Clear old logs
php artisan log:clear

# Optimize
php artisan optimize
```

### Backup Strategy:
1. **Database**: Backup via cPanel → phpMyAdmin/phpPgAdmin
2. **Files**: Backup via cPanel → Backup Wizard
3. **Frequency**: Harian untuk database, Mingguan untuk files

---

## 📝 Post-Deployment Notes

### Credentials Default:
Setelah deployment, login dengan:
- **Admin**: Sesuai dengan seeder atau yang dibuat manual
- **Monitoring**: Sesuai dengan data di database
- **Pegawai**: Sesuai dengan data pegawai

### First Time Setup:
1. Login sebagai admin
2. Setup jadwal (schedules)
3. Import data pegawai (jika belum)
4. Import data siswa dan rombel
5. Setup tahun ajaran aktif
6. Test fitur absensi
7. Test fitur laporan

### Update .env sesuai kebutuhan:
- Email configuration (jika ada)
- Storage disk (s3, etc)
- Cache driver (redis, memcached)
- Queue driver (jika pakai queue)

---

## 🆘 Support

Jika mengalami masalah:
1. Check error logs
2. Verify semua configuration
3. Test di local environment dulu
4. Review deployment checklist

---

## 📚 Resources

- [Laravel Deployment Documentation](https://laravel.com/docs/10.x/deployment)
- [cPanel Documentation](https://docs.cpanel.net/)
- [PostgreSQL Documentation](https://www.postgresql.org/docs/)

---

**Last Updated**: 2025-10-22
**Laravel Version**: 11.x
**PHP Version**: 8.2+
