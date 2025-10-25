# Quick Fix - Error "Verify stylesheet URLs"

## Masalah
Error: "This page failed to load a stylesheet from a URL"

## Penyebab
Laravel mencoba menggunakan Vite dev server yang tidak tersedia di production.

## Solusi Cepat (5 Langkah)

### 1. Pastikan File Build Sudah Ter-upload
**Cek di server via SSH atau File Manager:**

```bash
# Cek apakah folder build ada
ls -la /home/mtsnkot4/public_html/build/

# Harus ada:
# - manifest.json
# - assets/ (folder berisi CSS dan JS)
```

**Jika tidak ada**, upload folder `public/build/` dari local ke server.

---

### 2. Set APP_ENV ke Production
**Edit file `.env` di server:**

```bash
cd /home/mtsnkot4/absensi_app/
nano .env
```

**Pastikan:**
```env
APP_ENV=production
APP_DEBUG=false
```

**JANGAN ada** `VITE_APP_NAME` atau variabel `VITE_*` lainnya!

---

### 3. Clear Cache
```bash
cd /home/mtsnkot4/absensi_app/

php artisan config:clear
php artisan view:clear
php artisan cache:clear
```

---

### 4. Upload File Layout yang Sudah Diupdate

**Upload file berikut dari local ke server:**
- `resources/views/layouts/app.blade.php`
- `resources/views/layouts/guest.blade.php`
- `app/Providers/AppServiceProvider.php`
- `app/Services/AssetService.php` (NEW)
- `app/helpers.php` (NEW)
- `composer.json` (UPDATED)

**Lalu di server:**
```bash
cd /home/mtsnkot4/absensi_app/

# Regenerate autoload
composer dump-autoload --optimize

# Clear views
php artisan view:clear
```

---

### 5. Verifikasi

**Buka browser dan cek:**
1. Inspect Element (F12) → Network tab
2. Reload halaman
3. Cek apakah CSS dimuat dengan status 200

**URL CSS seharusnya:**
```
https://yourdomain.com/build/assets/app-Cv3Cf-7s.css
```

**BUKAN:**
```
http://localhost:5173/@vite/client  ❌ INI SALAH!
```

---

## Troubleshooting

### ❌ CSS masih tidak muncul

**1. Cek APP_ENV:**
```bash
cd /home/mtsnkot4/absensi_app/
php artisan env
```
Harus output: `Current application environment: production`

**2. Cek manifest.json:**
```bash
cat /home/mtsnkot4/public_html/build/manifest.json
```

Harus berisi JSON yang valid seperti:
```json
{
  "resources/css/app.css": {
    "file": "assets/app-Cv3Cf-7s.css",
    "src": "resources/css/app.css",
    "isEntry": true
  },
  "resources/js/app.js": {
    "file": "assets/app-KNSYXkWY.js",
    ...
  }
}
```

**3. Cek file CSS ada:**
```bash
ls -la /home/mtsnkot4/public_html/build/assets/app-*.css
```

Harus ada file seperti `app-Cv3Cf-7s.css`

**4. Cek permissions:**
```bash
chmod -R 755 /home/mtsnkot4/public_html/build/
```

---

### ❌ Error 404 untuk CSS/JS

**Berarti file tidak ter-upload. Solusi:**

1. Di local, jalankan:
   ```bash
   npm run build
   ```

2. Upload folder `public/build/` **LENGKAP** ke `/home/mtsnkot4/public_html/build/`

3. Pastikan struktur:
   ```
   public_html/
   └── build/
       ├── manifest.json
       └── assets/
           ├── app-Cv3Cf-7s.css
           ├── app-KNSYXkWY.js
           └── alpine-BGGahNR6.js
   ```

---

### ❌ Masih muncul error Vite

**Berarti APP_ENV masih "local". Solusi:**

```bash
cd /home/mtsnkot4/absensi_app/

# Edit .env
nano .env

# Ubah APP_ENV=local menjadi APP_ENV=production
# Hapus semua VITE_* variables

# Save, lalu:
php artisan config:clear
php artisan config:cache
```

---

## Verifikasi Final

**Test di browser:**
1. Buka halaman → F12 → Network tab
2. Filter "CSS"
3. Reload
4. Status harus 200 OK
5. URL harus dari `/build/assets/`

**Test Styling:**
- Login page harus ada styling (Tailwind CSS)
- Sidebar harus muncul dengan benar
- Colors dan layout harus proper

---

## Jika Semua Gagal - Hardcode Manual

**Edit `resources/views/layouts/app.blade.php` line 12-27:**

```blade
<!-- HARDCODE SEMENTARA - GANTI SETELAH FIX -->
<link rel="stylesheet" href="{{ asset('build/assets/app-Cv3Cf-7s.css') }}">
```

**Dan line 92-107:**
```blade
<script type="module" src="{{ asset('build/assets/app-KNSYXkWY.js') }}"></script>
```

**Lalu clear cache:**
```bash
php artisan view:clear
```

**CATATAN:** Ini solusi temporary. Setelah fix, kembalikan ke kode asli.

---

## Checklist

- [ ] File `build/manifest.json` ada di server
- [ ] File `build/assets/*.css` dan `*.js` ada di server
- [ ] `.env` di server: `APP_ENV=production`
- [ ] `.env` di server: `APP_DEBUG=false`
- [ ] Tidak ada `VITE_*` variables di `.env`
- [ ] Run `php artisan config:clear`
- [ ] Run `php artisan view:clear`
- [ ] Run `composer dump-autoload --optimize`
- [ ] Upload layout files yang updated
- [ ] Test di browser: CSS dimuat dengan status 200

**Jika semua checklist ✓, error seharusnya sudah fix!**
