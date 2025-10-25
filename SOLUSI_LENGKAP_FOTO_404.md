# SOLUSI LENGKAP - Foto 404 Karena Path Salah

## 🎯 Masalah yang Ditemukan

Foto tersimpan di:
```
❌ /home/mtnkot4/absensi_app/public/selfies/  (TIDAK bisa diakses web)
```

Seharusnya di:
```
✅ /home/mtnkot4/public_html/selfies/  (Bisa diakses web)
```

---

## 📋 Langkah-Langkah Fix (Via cPanel - MUDAH!)

### **STEP 1: Copy Foto yang Sudah Ada**

1. Login ke **cPanel**
2. Buka **File Manager**
3. Navigate ke: `/home/mtnkot4/absensi_app/public/`
4. Klik kanan folder **`selfies`** → **Copy**
5. Navigate ke: `/home/mtnkot4/public_html/`
6. Klik **Paste** di sini
7. ✅ Sekarang ada folder `/home/mtnkot4/public_html/selfies/`

---

### **STEP 2: Upload index.php yang Sudah Fix**

**File yang akan diupload:**
```
C:\absensi_mtsn2km\public\index.php.cpanel_fixed
```

**Upload ke:**
```
/home/mtnkot4/public_html/index.php
```

**Via File Manager:**
1. Navigate ke: `/home/mtnkot4/public_html/`
2. **BACKUP** file lama: Klik kanan `index.php` → Rename → `index.php.backup`
3. **Upload** file baru: `index.php.cpanel_fixed`
4. **Rename** file yang baru diupload: `index.php.cpanel_fixed` → `index.php`

---

### **STEP 3: Upload .htaccess yang Sudah Fix**

**File yang akan diupload:**
```
C:\absensi_mtsn2km\public\.htaccess.FIXED
```

**Upload ke:**
```
/home/mtnkot4/public_html/.htaccess
```

**Via File Manager:**
1. Navigate ke: `/home/mtnkot4/public_html/`
2. **BACKUP** file lama: Klik kanan `.htaccess` → Rename → `.htaccess.backup`
3. **Upload** file baru: `.htaccess.FIXED`
4. **Rename** file yang baru diupload: `.htaccess.FIXED` → `.htaccess`

---

### **STEP 4: Set Permission (PENTING!)**

Via File Manager:
1. Navigate ke: `/home/mtnkot4/public_html/selfies/`
2. Klik kanan folder **`selfies`** → **Change Permissions**
3. Set ke: **`755`** (rwxr-xr-x)
4. ✅ Centang **"Recurse into subdirectories"**
5. Klik **Change Permissions**

---

### **STEP 5: Clear Cache**

**Via cPanel Terminal** (jika ada):
```bash
cd /home/mtnkot4/absensi_app
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

**ATAU Via File Manager** (jika tidak ada Terminal):
1. Navigate ke: `/home/mtnkot4/absensi_app/storage/framework/views/`
2. **Delete** semua file `.php` (jangan delete `.gitignore`)
3. Navigate ke: `/home/mtnkot4/absensi_app/bootstrap/cache/`
4. **Delete** semua file `.php` (jangan delete `.gitignore`)

---

### **STEP 6: Test!**

#### A. Test Foto Lama (yang sudah di-copy)

Buka URL ini di browser:
```
https://siab.mtsn2kotamalang.sch.id/selfies/employee/employee_30_in_1761202104.jpg
```

**✅ Harus bisa dibuka!** (tidak 404 lagi)

#### B. Test Upload Foto Baru

1. Login sebagai pegawai
2. Klik "Absen Masuk"
3. Ambil foto → Kirim
4. **Cek di File Manager:**
   - Navigate: `/home/mtnkot4/public_html/selfies/employee/`
   - **Harus ada file JPG baru!**
5. Refresh halaman laporan
6. **Foto harus muncul!** ✅

#### C. Test Halaman Laporan

Buka:
```
https://siab.mtsn2kotamalang.sch.id/employee/attendance/report
```

**✅ Semua foto harus muncul!**

---

## 🔧 Yang Diperbaiki

### 1. **index.php**
Menambahkan override `public_path()`:
```php
$app->bind('path.public', function() {
    return realpath(__DIR__);
});
```

Sekarang `public_path()` return:
- ✅ `/home/mtnkot4/public_html/` (BENAR!)
- ❌ Bukan `/home/mtnkot4/absensi_app/public/` (SALAH!)

### 2. **.htaccess**
Menambahkan exception untuk folder statis:
```apache
RewriteCond %{REQUEST_URI} ^/selfies [NC,OR]
RewriteCond %{REQUEST_URI} ^/build [NC,OR]
RewriteCond %{REQUEST_URI} ^/storage [NC]
RewriteRule .* - [L]
```

Sekarang request ke `/selfies/` **tidak** di-redirect ke Laravel.

### 3. **Copy Foto**
Foto yang sudah tersimpan di tempat salah, dipindahkan ke tempat yang benar.

---

## ✅ Checklist

Pastikan semua langkah sudah dilakukan:

- [ ] **STEP 1:** Copy folder `selfies` dari `absensi_app/public` ke `public_html`
- [ ] **STEP 2:** Upload `index.php.cpanel_fixed` → rename jadi `index.php`
- [ ] **STEP 3:** Upload `.htaccess.FIXED` → rename jadi `.htaccess`
- [ ] **STEP 4:** Set permission folder `selfies` ke `755` (recursive)
- [ ] **STEP 5:** Clear cache Laravel
- [ ] **STEP 6A:** Test foto lama bisa diakses via URL
- [ ] **STEP 6B:** Test upload foto baru
- [ ] **STEP 6C:** Test halaman laporan - semua foto muncul

---

## 🚨 Troubleshooting

### Jika Foto Masih 404

**Cek:**
1. Apakah folder `/home/mtnkot4/public_html/selfies/` ada?
2. Apakah permission-nya `755`?
3. Apakah file `.htaccess` sudah diupdate?
4. Apakah file `index.php` sudah diupdate?
5. Clear browser cache (Ctrl+Shift+R)

### Jika Website Error 500

**Kemungkinan:**
- Syntax error di `index.php` atau `.htaccess`
- Path salah di `index.php`

**Solusi:**
- Restore file backup: `.htaccess.backup` dan `index.php.backup`
- Kirim error message ke saya

### Jika Upload Foto Baru Masih Tersimpan di Tempat Salah

**Cek `index.php`:**
```bash
cat /home/mtnkot4/public_html/index.php | grep "path.public"
```

Harus ada baris:
```php
$app->bind('path.public', function() {
```

Jika tidak ada, berarti file `index.php` belum terupdate.

---

## 📞 Need Help?

Jika ada masalah, kirim ke saya:
1. Screenshot error di browser
2. Screenshot File Manager di folder `/home/mtnkot4/public_html/`
3. Hasil test: apakah URL `https://siab.../selfies/employee/xxx.jpg` bisa diakses?

---

## 🎉 Setelah Berhasil

Foto akan:
- ✅ Tersimpan di `/home/mtnkot4/public_html/selfies/`
- ✅ Bisa diakses via `https://siab.../selfies/employee/xxx.jpg`
- ✅ Muncul di halaman laporan
- ✅ Tidak ada error 404 lagi

**Happy Coding!** 🚀
