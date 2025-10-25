# Checklist Debug Production - Foto 404

## PENTING: Jalankan command ini di cPanel Terminal / SSH Production

### 1. Cek Struktur Folder Production
```bash
# Ganti 'username' dengan username cPanel Anda
# Contoh: /home/mtsn2km/

# Cek folder public_html
ls -la /home/username/public_html/ | grep selfies

# HASIL YANG DIHARAPKAN:
# drwxr-xr-x 4 username username 4096 Oct 23 12:00 selfies
```

**Jika TIDAK ADA folder 'selfies', jalankan:**
```bash
mkdir -p /home/username/public_html/selfies/employee
mkdir -p /home/username/public_html/selfies/student
chmod 755 /home/username/public_html/selfies -R
```

### 2. Cek Apakah File Foto Ada
```bash
# Cek di public_html (seharusnya di sini)
ls -la /home/username/public_html/selfies/employee/ | grep employee_30

# Cek juga di storage (kalau masih tersimpan di sini berarti deteksi salah)
ls -la /home/username/absensi_app/storage/app/public/selfies/employee/ | grep employee_30
```

**Kirim hasil dari kedua command di atas!**

### 3. Test Write Permission
```bash
# Test apakah bisa create file
touch /home/username/public_html/selfies/employee/test.txt

# Jika ERROR "Permission denied", jalankan:
chmod 755 /home/username/public_html/selfies -R
chown username:username /home/username/public_html/selfies -R

# Hapus file test
rm /home/username/public_html/selfies/employee/test.txt
```

### 4. Cek Laravel Log
```bash
tail -50 /home/username/absensi_app/storage/logs/laravel.log
```

Cari error yang berisi:
- "Permission denied"
- "Failed to open stream"
- "mkdir"
- "processSelfie"

### 5. Cek Path di Database
```bash
cd /home/username/absensi_app
php artisan tinker
```

Di dalam tinker, jalankan:
```php
// Cek record terakhir
$att = \App\Models\Attendance::whereNotNull('selfie_path_in')
    ->orderBy('id', 'desc')
    ->first();

echo "ID: " . $att->id . "\n";
echo "Path: " . $att->selfie_path_in . "\n";
echo "Created: " . $att->created_at . "\n";

// Cek apakah file exists
$fullPath = public_path($att->selfie_path_in);
echo "Full Path: " . $fullPath . "\n";
echo "File Exists: " . (file_exists($fullPath) ? 'YES' : 'NO') . "\n";

// Cek di storage
$storagePath = storage_path('app/public/' . $att->selfie_path_in);
echo "Storage Path: " . $storagePath . "\n";
echo "Storage Exists: " . (file_exists($storagePath) ? 'YES' : 'NO') . "\n";

exit
```

**COPY dan KIRIM hasil output di atas!**

### 6. Manual Test Upload
```bash
# Cek web server user
ps aux | grep -E 'httpd|apache|nginx' | head -1

# Test create file sebagai web server user
sudo -u nobody touch /home/username/public_html/selfies/employee/test2.txt

# Jika berhasil, hapus
sudo rm /home/username/public_html/selfies/employee/test2.txt
```

---

## Kemungkinan Masalah & Solusi

### A. Folder selfies TIDAK ADA di public_html
**Solusi:**
```bash
mkdir -p /home/username/public_html/selfies/employee
mkdir -p /home/username/public_html/selfies/student
chmod 755 /home/username/public_html/selfies -R
```

### B. File Tersimpan di STORAGE bukan PUBLIC
**Artinya:** Deteksi environment gagal, masih dianggap local

**Solusi:**
```bash
# Pastikan folder selfies ADA di public_html
ls -la /home/username/public_html/ | grep selfies

# Jika tidak ada, buat dulu
mkdir -p /home/username/public_html/selfies/employee
mkdir -p /home/username/public_html/selfies/student

# Clear cache Laravel
cd /home/username/absensi_app
php artisan config:clear
php artisan cache:clear
php artisan view:clear

# Test upload lagi
```

### C. Permission Error
**Solusi:**
```bash
# Set permission yang benar
chmod 755 /home/username/public_html/selfies
chmod 755 /home/username/public_html/selfies/employee
chmod 755 /home/username/public_html/selfies/student

# Set ownership
chown username:username /home/username/public_html/selfies -R

# Jika pakai Apache
chgrp nobody /home/username/public_html/selfies -R
chmod g+w /home/username/public_html/selfies -R
```

### D. Path Salah di index.php
Cek file `/home/username/public_html/index.php`:

```bash
cat /home/username/public_html/index.php | grep absensi_app
```

**Harus menunjukkan path yang benar:**
```php
require __DIR__.'/../absensi_app/vendor/autoload.php';
$app = require_once __DIR__.'/../absensi_app/bootstrap/app.php';
```

---

## Quick Fix Jika Semua Sudah Benar Tapi Masih Error

### Option 1: Force Production Mode
Edit file `AttendanceController.php`, baris 620-621:

**GANTI:**
```php
$publicSelfiesPath = public_path('selfies');
$isProduction = file_exists($publicSelfiesPath) && is_dir($publicSelfiesPath);
```

**DENGAN:**
```php
// Force production mode - TEMPORARY FOR TESTING
$isProduction = true;
```

Upload file yang diupdate, lalu test upload foto lagi.

**PENTING:** Ini HANYA untuk testing. Jika berhasil, kita tahu masalahnya di deteksi environment.

### Option 2: Pindahkan Foto dari Storage ke Public
Jika foto tersimpan di storage, pindahkan:

```bash
# Copy semua foto dari storage ke public
cp -r /home/username/absensi_app/storage/app/public/selfies/* /home/username/public_html/selfies/

# Set permission
chmod 755 /home/username/public_html/selfies -R
```

---

## Yang Perlu Anda Kirim ke Saya

Tolong jalankan command di atas dan kirim hasil dari:

1. ✅ Hasil: `ls -la /home/username/public_html/ | grep selfies`
2. ✅ Hasil: `ls -la /home/username/public_html/selfies/employee/ | grep employee_30`
3. ✅ Hasil: `ls -la /home/username/absensi_app/storage/app/public/selfies/employee/ | grep employee_30`
4. ✅ Hasil dari PHP Tinker (step 5)
5. ✅ Screenshot atau copy Laravel log error (jika ada)

Dengan info ini, saya bisa tahu persis di mana masalahnya dan kasih solusi yang tepat.
