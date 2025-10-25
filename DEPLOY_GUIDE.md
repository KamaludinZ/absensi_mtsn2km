# Panduan Deploy ke Production (cPanel)

## Error: "Verify stylesheet URLs" - SOLUSI

Error ini terjadi karena Laravel masih mencoba menggunakan Vite dev server. Berikut langkah-langkah untuk memperbaikinya:

## 1. Build Assets untuk Production

Sebelum upload ke server, jalankan di local:

```bash
npm run build
```

Perintah ini akan:
- Generate file CSS/JS yang sudah dioptimasi di folder `public/build/`
- Update file `public/build/manifest.json`

## 2. Persiapan File untuk Upload

Pastikan folder dan file berikut ada dan ter-build dengan benar:
- `public/build/` (hasil dari npm run build)
- `public/build/manifest.json`
- `public/build/assets/`

## 3. Upload ke cPanel

### Struktur Folder di cPanel:
```
/home/username/
├── absensi_app/          (folder aplikasi Laravel)
│   ├── app/
│   ├── bootstrap/
│   ├── config/
│   ├── database/
│   ├── public/           (JANGAN upload ke sini)
│   ├── resources/
│   ├── routes/
│   ├── storage/
│   ├── vendor/
│   └── .env              (file .env production)
│
└── public_html/          (document root)
    ├── build/            (hasil npm run build - PENTING!)
    │   ├── assets/
    │   └── manifest.json
    ├── .htaccess         (dari .htaccess.production)
    └── index.php         (dari index.php.production)
```

### Langkah Upload:

1. **Upload folder aplikasi**:
   - Upload semua file Laravel (kecuali `public/`) ke `/home/username/absensi_app/`
   - Pastikan `vendor/` ter-upload (atau run `composer install --no-dev` di server)

2. **Upload file public**:
   - Upload isi folder `public/` ke `/home/username/public_html/`
   - **PENTING**: Pastikan folder `build/` dengan isi lengkapnya ter-upload!

3. **Setup file konfigurasi**:
   ```bash
   # Di server (via SSH atau cPanel Terminal)
   cd /home/username/public_html/

   # Copy file production
   cp index.php.production index.php
   cp .htaccess.production .htaccess
   ```

## 4. Konfigurasi .env di Server

Edit file `/home/username/absensi_app/.env`:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Sesuaikan database production
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=your_production_db
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

# JANGAN set VITE_ variables di production!
```

## 5. Sesuaikan Path di index.php

Edit `/home/username/public_html/index.php`:

```php
// Sesuaikan username Anda
if (file_exists($maintenance = __DIR__.'/../absensi_app/storage/framework/maintenance.php')) {
    require $maintenance;
}

require __DIR__.'/../absensi_app/vendor/autoload.php';

$app = require_once __DIR__.'/../absensi_app/bootstrap/app.php';
```

Ganti `/home/username/absensi_app/` dengan path server Anda yang sebenarnya.

## 6. Set Permissions (via SSH)

```bash
cd /home/username/absensi_app/

# Set permissions untuk storage dan cache
chmod -R 775 storage bootstrap/cache
chown -R username:username storage bootstrap/cache

# Jika ada error permission, coba:
chmod -R 777 storage bootstrap/cache
```

## 7. Cache Konfigurasi (Opsional tapi Disarankan)

```bash
cd /home/username/absensi_app/

# Cache untuk performa lebih baik
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Jika ada masalah, clear dulu:
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

## 8. Troubleshooting

### Jika masih muncul error "Verify stylesheet URLs":

1. **Cek file manifest.json**:
   ```bash
   cat /home/username/public_html/build/manifest.json
   ```
   Pastikan file ini ada dan berisi path yang benar.

2. **Cek file CSS ada**:
   ```bash
   ls -la /home/username/public_html/build/assets/
   ```
   Harus ada file seperti `app-Cv3Cf-7s.css`

3. **Cek APP_ENV**:
   ```bash
   cd /home/username/absensi_app/
   php artisan env
   ```
   Harus menunjukkan `production`, bukan `local`

4. **Clear semua cache**:
   ```bash
   php artisan optimize:clear
   ```

5. **Cek log error**:
   ```bash
   tail -f /home/username/absensi_app/storage/logs/laravel.log
   ```

### Jika assets tidak muncul:

1. Cek URL yang di-generate di browser (Inspect Element → Network)
2. Pastikan path di URL sesuai dengan struktur folder
3. Cek permissions folder `build/`
4. Pastikan `.htaccess` ter-copy dengan benar

## 9. Verifikasi

Buka browser dan:
1. Inspect Element (F12)
2. Tab Network → Filter CSS
3. Reload halaman
4. Cek apakah file CSS berhasil dimuat (status 200)

URL CSS seharusnya seperti:
```
https://yourdomain.com/build/assets/app-Cv3Cf-7s.css
```

## 10. Tips Penting

- **JANGAN** set `VITE_*` variables di `.env` production
- **SELALU** run `npm run build` sebelum upload
- **PASTIKAN** folder `build/` lengkap dengan `manifest.json` dan `assets/`
- **Set** `APP_ENV=production` dan `APP_DEBUG=false`
- **Gunakan** HTTPS jika sudah install SSL

## 11. Quick Checklist Deploy

- [ ] Run `npm run build` di local
- [ ] Upload semua file ke server
- [ ] Folder `public/build/` lengkap di `public_html/build/`
- [ ] File `.env` di server sudah `APP_ENV=production`
- [ ] File `index.php` di `public_html/` sudah sesuai path
- [ ] Permissions `storage/` dan `bootstrap/cache/` sudah benar (775 atau 777)
- [ ] Run `php artisan config:cache`
- [ ] Test di browser

---

## Catatan untuk Development

Jika kembali ke development mode setelah deploy:

```bash
# Set .env kembali ke local
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

# Clear cache
php artisan cache:clear
php artisan config:clear

# Run Vite dev server
npm run dev
```
