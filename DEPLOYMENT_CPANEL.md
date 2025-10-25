# Deployment ke cPanel Shared Hosting

## Ringkasan Perubahan

Aplikasi telah dimodifikasi untuk mendukung deployment di cPanel shared hosting dengan struktur folder berikut:
```
/home/username/absensi_app/       ← Folder aplikasi Laravel
/home/username/public_html/       ← Folder public (document root)
```

## Perubahan Utama

### 1. Storage Foto Absensi
- **Local Development**: Foto disimpan di `storage/app/public/selfies/` (menggunakan symlink)
- **Production (cPanel)**: Foto disimpan langsung di `public_html/selfies/` (tanpa symlink)

### 2. File yang Dimodifikasi
- `app/Http/Controllers/Employee/AttendanceController.php` - Method `processSelfie()`
- `app/Http/Controllers/Student/AttendanceController.php` - Method `processSelfie()`
- `resources/views/employee/report.blade.php` - Display foto dengan environment detection
- `resources/views/monitoring/employees.blade.php` - Display foto dengan environment detection
- `resources/views/monitoring/students.blade.php` - Display foto dengan environment detection
- `app/Http/Middleware/SecurityHeaders.php` - Izinkan akses kamera: `camera=(self)`

### 3. File untuk Production
- `public/index.php.production` - Index file untuk cPanel
- `public/.htaccess.production` - Apache config untuk production

## Cara Deploy ke cPanel

### Step 1: Upload Files
1. Upload semua file aplikasi ke `/home/username/absensi_app/`
2. Jangan upload folder `node_modules` dan `vendor`

### Step 2: Install Dependencies
```bash
cd /home/username/absensi_app
composer install --optimize-autoloader --no-dev
```

### Step 3: Setup Environment
```bash
cp .env.example .env
nano .env
```

Edit `.env`:
```env
APP_NAME="Absensi MTsN 2 Kota Malang"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://siab.mtsn2kotamalang.sch.id

DB_CONNECTION=pgsql
DB_HOST=localhost
DB_PORT=5432
DB_DATABASE=absensi_mtsn2km
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password
```

### Step 4: Generate Keys
```bash
php artisan key:generate
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Step 5: Setup Public Folder
```bash
# Copy index.php production version
cp /home/username/absensi_app/public/index.php.production /home/username/public_html/index.php

# Copy .htaccess production version
cp /home/username/absensi_app/public/.htaccess.production /home/username/public_html/.htaccess

# Copy assets
cp -r /home/username/absensi_app/public/build /home/username/public_html/
cp /home/username/absensi_app/public/favicon.svg /home/username/public_html/

# Create selfies directory
mkdir -p /home/username/public_html/selfies/employee
mkdir -p /home/username/public_html/selfies/student
chmod 755 /home/username/public_html/selfies
chmod 755 /home/username/public_html/selfies/employee
chmod 755 /home/username/public_html/selfies/student
```

### Step 6: Set Permissions
```bash
chmod -R 755 /home/username/absensi_app/storage
chmod -R 755 /home/username/absensi_app/bootstrap/cache
```

### Step 7: Run Migrations
```bash
php artisan migrate --force
```

### Step 8: PENTING - Edit index.php
Edit `/home/username/public_html/index.php` dan sesuaikan path:
```php
// Ganti 'username' dengan username cPanel Anda
if (file_exists($maintenance = __DIR__.'/../absensi_app/storage/framework/maintenance.php')) {
    require $maintenance;
}

require __DIR__.'/../absensi_app/vendor/autoload.php';
$app = require_once __DIR__.'/../absensi_app/bootstrap/app.php';
```

## Testing

### 1. Test Website
Buka: https://siab.mtsn2kotamalang.sch.id

### 2. Test Login
- Login sebagai admin/pegawai
- Pastikan tidak ada error 500

### 3. Test Upload Foto
- Login sebagai pegawai
- Klik "Absen Masuk"
- **PENTING**: Browser akan minta izin kamera, klik "Allow/Izinkan"
- Ambil foto selfie
- Kirim absensi
- Cek apakah foto muncul di laporan

### 4. Cek Foto Tersimpan
```bash
ls -la /home/username/public_html/selfies/employee/
```
Harus ada file JPG yang baru dibuat.

## Troubleshooting

### 1. Error 500 - Internal Server Error
```bash
# Cek log Laravel
tail -100 /home/username/absensi_app/storage/logs/laravel.log

# Cek permissions
chmod -R 755 /home/username/absensi_app/storage
chmod -R 755 /home/username/absensi_app/bootstrap/cache
```

### 2. Error 404 - Foto Tidak Ditemukan
```bash
# Pastikan folder selfies ada dan writable
ls -la /home/username/public_html/selfies/
chmod 755 /home/username/public_html/selfies
chmod 755 /home/username/public_html/selfies/employee
chmod 755 /home/username/public_html/selfies/student
```

### 3. Kamera Tidak Bisa Diakses di Chrome
- Pastikan website menggunakan HTTPS (bukan HTTP)
- Chrome memblokir akses kamera di HTTP
- Clear browser cache: Ctrl + Shift + Delete

### 4. Error Permission Denied saat Upload
```bash
# Set ownership (ganti username dengan user cPanel Anda)
chown -R username:username /home/username/public_html/selfies/
```

## Update Aplikasi

Ketika ada update kode:

```bash
cd /home/username/absensi_app

# Pull latest code
git pull origin main

# Update dependencies
composer install --optimize-autoloader --no-dev

# Clear cache
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# Re-cache
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Update assets if changed
cp -r /home/username/absensi_app/public/build /home/username/public_html/

# Run migrations if any
php artisan migrate --force
```

## Maintenance Mode

### Enable Maintenance
```bash
php artisan down --secret="kode-rahasia-123"
```

Akses dengan: `https://siab.mtsn2kotamalang.sch.id/kode-rahasia-123`

### Disable Maintenance
```bash
php artisan up
```

## Backup

### Backup Database
```bash
pg_dump -U db_user absensi_mtsn2km > backup_$(date +%Y%m%d).sql
```

### Backup Files
```bash
tar -czf backup_files_$(date +%Y%m%d).tar.gz \
  /home/username/absensi_app \
  /home/username/public_html/selfies
```

## Security Checklist

- [ ] APP_DEBUG=false di .env
- [ ] APP_ENV=production di .env
- [ ] SSL Certificate aktif (HTTPS)
- [ ] File .env tidak bisa diakses public
- [ ] Folder storage dan bootstrap/cache writable
- [ ] Folder selfies writable tapi secure
- [ ] Database credentials aman
- [ ] Backup rutin database dan files

## Contact

Jika ada masalah, hubungi developer atau cek dokumentasi Laravel:
- https://laravel.com/docs/11.x/deployment
