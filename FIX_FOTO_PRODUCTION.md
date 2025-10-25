# Fix: Foto Tersimpan Tapi Tidak Tampil di Production

## Perubahan yang Dilakukan

### 1. **Deteksi Environment Lebih Reliable**

**Sebelumnya:** Menggunakan `app()->environment('production')` yang tidak reliable di beberapa shared hosting.

**Sekarang:** Menggunakan deteksi folder:
```php
// Cek apakah folder public/selfies exists
$publicSelfiesPath = public_path('selfies');
$isProduction = file_exists($publicSelfiesPath) && is_dir($publicSelfiesPath);
```

**Keuntungan:**
- ✅ Tidak bergantung pada konfigurasi APP_ENV
- ✅ Auto-detect berdasarkan struktur folder aktual
- ✅ Lebih compatible dengan berbagai hosting

### 2. **Display Foto Lebih Flexible**

View sekarang akan:
1. **Cek file di public folder** (production): `public/selfies/employee/...`
2. **Fallback ke storage** (local dev): `storage/app/public/selfies/...`

```php
$publicPath = public_path($attendance->selfie_path_in);
$imageUrl = file_exists($publicPath)
    ? asset($attendance->selfie_path_in)  // Production
    : Storage::url($attendance->selfie_path_in);  // Local
```

**Keuntungan:**
- ✅ Support foto lama di storage
- ✅ Support foto baru di public
- ✅ Tidak perlu migrasi file

## Cara Deploy Fix Ini ke Production

### Step 1: Upload File yang Diupdate
Upload file berikut ke server production:
```
app/Http/Controllers/Employee/AttendanceController.php
app/Http/Controllers/Student/AttendanceController.php
resources/views/employee/report.blade.php
resources/views/monitoring/employees.blade.php
resources/views/monitoring/students.blade.php
```

### Step 2: Di cPanel/SSH Production, Jalankan:

```bash
cd /home/username/absensi_app

# PENTING: Pastikan folder selfies ada di public_html
mkdir -p /home/username/public_html/selfies/employee
mkdir -p /home/username/public_html/selfies/student
chmod 755 /home/username/public_html/selfies -R

# Clear cache Laravel
php artisan config:clear
php artisan cache:clear
php artisan view:clear

# Optional: Re-cache untuk performa
php artisan config:cache
php artisan view:cache
```

### Step 3: Test Upload Foto Baru

1. Login sebagai pegawai
2. Klik "Absen Masuk"
3. Izinkan akses kamera (jika diminta)
4. Ambil foto → Kirim
5. **Cek apakah foto muncul di laporan**

### Step 4: Verify File Tersimpan

```bash
# Cek apakah file foto baru ada
ls -la /home/username/public_html/selfies/employee/

# Harus ada file JPG dengan timestamp terbaru
# Contoh: employee_30_in_1761199587.jpg
```

### Step 5: Test Akses Langsung

Buka di browser:
```
https://siab.mtsn2kotamalang.sch.id/selfies/employee/employee_30_in_1761199587.jpg
```

**Harus bisa diakses** (tidak 404)

## Troubleshooting

### Masalah 1: Foto Masih 404

**Penyebab:** File tidak tersimpan di public_html/selfies/

**Solusi:**
```bash
# Cek apakah folder writable
chmod 755 /home/username/public_html/selfies -R

# Cek ownership
chown username:username /home/username/public_html/selfies -R

# Cek apakah folder benar-benar ada
ls -la /home/username/public_html/ | grep selfies
```

### Masalah 2: Error Permission Denied

**Solusi:**
```bash
# Set permission yang benar
cd /home/username/public_html
chmod 755 selfies
chmod 755 selfies/employee
chmod 755 selfies/student

# Pastikan web server bisa write
chgrp nobody selfies -R
chmod g+w selfies -R
```

### Masalah 3: Foto Lama Tidak Muncul

**Penjelasan:** Foto lama tersimpan di `storage/app/public/selfies/` dan tidak bisa diakses di shared hosting.

**Solusi - Migrasi Foto Lama:**
```bash
# Copy foto lama ke public
cp -r /home/username/absensi_app/storage/app/public/selfies/* /home/username/public_html/selfies/

# Set permission
chmod 755 /home/username/public_html/selfies -R
```

## Testing Checklist

Setelah deploy, pastikan:

- [ ] Folder `public_html/selfies/employee` ada dan writable
- [ ] Folder `public_html/selfies/student` ada dan writable
- [ ] Upload foto baru berhasil
- [ ] Foto baru muncul di laporan
- [ ] File foto ada di `public_html/selfies/employee/`
- [ ] Foto bisa diakses langsung via URL
- [ ] Tidak ada error 404 di browser console

## Perbedaan Local vs Production

### Local Development
```
Foto disimpan: /absensi_app/storage/app/public/selfies/
Akses via: http://localhost/storage/selfies/employee/xxx.jpg
Symlink: public/storage → storage/app/public
```

### Production (cPanel)
```
Foto disimpan: /public_html/selfies/
Akses via: https://siab.mtsn2kotamalang.sch.id/selfies/employee/xxx.jpg
Symlink: TIDAK DIGUNAKAN (direct access)
```

## Kesimpulan

Dengan fix ini:
- ✅ Foto akan otomatis tersimpan di tempat yang benar
- ✅ View akan otomatis cari foto di lokasi yang benar
- ✅ Tidak perlu konfigurasi APP_ENV yang rumit
- ✅ Compatible dengan shared hosting dan VPS
