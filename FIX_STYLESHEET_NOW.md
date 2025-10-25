# FIX STYLESHEET ERROR - LANGKAH DEMI LANGKAH

## Error yang Muncul:
```
Verify stylesheet URLs
This page failed to load a stylesheet from a URL.
```

---

## SOLUSI LANGSUNG (Via SSH / cPanel Terminal)

### LANGKAH 1: CEK ENVIRONMENT
```bash
cd /home/mtsnkot4/absensi_app/

# Cek environment saat ini
php artisan env

# Harus output: "Current application environment: production"
# Jika output "local" atau lainnya, lanjut ke langkah 1a
```

#### Langkah 1a: Set Environment ke Production
```bash
# Edit file .env
nano .env

# Cari baris APP_ENV dan ubah:
# DARI: APP_ENV=local
# JADI: APP_ENV=production

# Cari baris APP_DEBUG dan ubah:
# DARI: APP_DEBUG=true
# JADI: APP_DEBUG=false

# HAPUS baris ini jika ada:
# VITE_APP_NAME=...
# (hapus semua baris yang dimulai dengan VITE_)

# Save: Ctrl+O, Enter, Ctrl+X
```

---

### LANGKAH 2: CEK FILE BUILD
```bash
# Cek apakah folder build ada
ls -la /home/mtsnkot4/public_html/build/

# Harus menampilkan:
# - manifest.json
# - assets/ (folder)

# Jika TIDAK ADA, lanjut ke langkah 2a
```

#### Langkah 2a: Upload Folder Build

**DI LOCAL (komputer Anda):**
```bash
# Jalankan build
npm run build

# Setelah selesai, upload folder public/build/ ke server
```

**Upload via FTP/FileManager:**
- Source: `public/build/` (dari local)
- Destination: `/home/mtsnkot4/public_html/build/`
- Upload **SEMUA ISI** folder build (manifest.json + folder assets)

**Verifikasi di server:**
```bash
# Cek lagi
ls -la /home/mtsnkot4/public_html/build/

# Harus ada:
# drwxr-xr-x  2 user user 4096 ... assets
# -rw-r--r--  1 user user  xxx ... manifest.json

# Cek isi manifest
cat /home/mtsnkot4/public_html/build/manifest.json

# Harus menampilkan JSON yang valid
```

---

### LANGKAH 3: JALANKAN DIAGNOSTIC
```bash
cd /home/mtsnkot4/absensi_app/

# Jalankan command diagnostic (jika sudah upload file CheckAssets.php)
php artisan assets:check

# Baca output dan ikuti rekomendasi yang diberikan
```

**Jika command tidak ditemukan**, artinya file `CheckAssets.php` belum ter-upload. Lanjutkan langkah manual.

---

### LANGKAH 4: CLEAR CACHE
```bash
cd /home/mtsnkot4/absensi_app/

# Clear semua cache
php artisan config:clear
php artisan view:clear
php artisan cache:clear
php artisan route:clear

# Re-cache untuk production
php artisan config:cache
php artisan view:cache
```

---

### LANGKAH 5: CEK PERMISSION
```bash
# Set permission untuk folder build
chmod -R 755 /home/mtsnkot4/public_html/build/
```

---

### LANGKAH 6: VERIFIKASI DI BROWSER

1. **Buka browser** dan akses website Anda
2. **Tekan F12** untuk buka DevTools
3. **Pilih tab "Network"**
4. **Reload halaman** (Ctrl+R atau F5)
5. **Filter "CSS"** di Network tab

**Yang harus muncul:**
- Request ke URL seperti: `https://yourdomain.com/build/assets/app-Cv3Cf-7s.css`
- Status: **200 OK** (dengan warna hijau/biru)
- Size: beberapa KB (ukuran file CSS)

**Jika muncul:**
- Status **404**: File tidak ada, ulangi langkah 2
- Request ke `localhost:5173`: APP_ENV masih local, ulangi langkah 1
- Status **403**: Permission error, ulangi langkah 5

---

## TROUBLESHOOTING KHUSUS

### Problem: Manifest.json tidak ada

**Solusi:**
```bash
# Di LOCAL:
cd C:\absensi_mtsn2km\
npm run build

# Pastikan tidak ada error
# Upload public/build/ ke server
```

### Problem: APP_ENV tidak berubah

**Solusi:**
```bash
cd /home/mtsnkot4/absensi_app/

# Check langsung
cat .env | grep APP_ENV

# Harus output: APP_ENV=production
# Jika tidak, edit manual:
nano .env
# Ubah APP_ENV=production
# Save (Ctrl+O, Enter, Ctrl+X)

# Clear config
php artisan config:clear
php artisan config:cache

# Check lagi
php artisan env
```

### Problem: CSS file 404

**Cek file ada:**
```bash
# List semua file CSS di build/assets/
ls -la /home/mtsnkot4/public_html/build/assets/*.css

# Harus ada file seperti: app-Cv3Cf-7s.css atau app-*.css

# Jika tidak ada, berarti folder build tidak lengkap
# Upload ulang dari local
```

### Problem: Permission denied

**Fix permission:**
```bash
cd /home/mtsnkot4/

# Set permission untuk public_html
chmod -R 755 public_html/build/

# Set ownership (ganti 'mtsnkot4' dengan username Anda)
chown -R mtsnkot4:mtsnkot4 public_html/build/
```

---

## CEK CEPAT - ONE-LINER

Jalankan command ini untuk cek semua sekaligus:

```bash
cd /home/mtsnkot4/absensi_app/ && \
echo "=== ENV CHECK ===" && \
grep APP_ENV .env && \
echo "" && \
echo "=== MANIFEST CHECK ===" && \
ls -la ../public_html/build/manifest.json && \
echo "" && \
echo "=== ASSETS CHECK ===" && \
ls -la ../public_html/build/assets/ | head -5 && \
echo "" && \
echo "=== CONFIG CHECK ===" && \
php artisan env
```

**Output yang BENAR:**
```
=== ENV CHECK ===
APP_ENV=production

=== MANIFEST CHECK ===
-rw-r--r-- 1 user user 456 ... manifest.json

=== ASSETS CHECK ===
total 123
-rw-r--r-- 1 user user 45678 ... app-Cv3Cf-7s.css
-rw-r--r-- 1 user user 12345 ... app-KNSYXkWY.js
...

=== CONFIG CHECK ===
Current application environment: production
```

---

## JIKA SEMUA GAGAL - HARDCODE SEMENTARA

**Edit langsung view di server:**

```bash
cd /home/mtsnkot4/absensi_app/
nano resources/views/layouts/app.blade.php
```

**Cari baris sekitar 12-27** (yang ada `@if` dan `@vite`), **REPLACE SEMUA** dengan:

```html
<!-- TEMPORARY HARDCODE -->
<link rel="stylesheet" href="/build/assets/app-Cv3Cf-7s.css">
```

**Cari baris sekitar 92-107** (yang ada JS), **REPLACE** dengan:

```html
<!-- TEMPORARY HARDCODE -->
<script type="module" src="/build/assets/app-KNSYXkWY.js"></script>
```

**Save dan clear cache:**
```bash
php artisan view:clear
```

**CATATAN:** Ini solusi SEMENTARA. Setelah berhasil, kembalikan ke kode asli yang dinamis.

---

## CHECKLIST FINAL

- [ ] `cd /home/mtsnkot4/absensi_app/ && php artisan env` → output "production"
- [ ] `cat .env | grep APP_ENV` → output "APP_ENV=production"
- [ ] `cat .env | grep APP_DEBUG` → output "APP_DEBUG=false"
- [ ] `cat .env | grep VITE` → **TIDAK ADA OUTPUT** (tidak ada VITE_*)
- [ ] `ls /home/mtsnkot4/public_html/build/manifest.json` → file exists
- [ ] `ls /home/mtsnkot4/public_html/build/assets/*.css` → minimal 1 file CSS
- [ ] `php artisan config:clear && php artisan view:clear` → success
- [ ] Browser DevTools → Network → CSS → Status 200 OK

**Jika semua ✓, stylesheet error HARUS sudah hilang!**

---

## Masih Error?

**Copy paste hasil command ini:**
```bash
cd /home/mtsnkot4/absensi_app/ && php artisan assets:check
```

Dan kirim outputnya untuk diagnostic lebih lanjut.
