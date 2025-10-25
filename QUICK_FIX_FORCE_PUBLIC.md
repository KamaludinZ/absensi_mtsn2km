# Quick Fix: Force Save ke Public Folder

Jika Anda tidak punya akses SSH atau ingin solusi cepat, lakukan ini:

## Step 1: Buat Folder via cPanel File Manager

1. Login ke **cPanel**
2. Buka **File Manager**
3. Navigate ke `public_html`
4. Klik **+ Folder** (New Folder)
5. Buat folder bernama: `selfies`
6. Masuk ke folder `selfies`
7. Buat 2 folder di dalamnya:
   - `employee`
   - `student`

Struktur akhir:
```
public_html/
├── selfies/
│   ├── employee/
│   └── student/
├── index.php
└── ...
```

## Step 2: Set Permission via cPanel

1. Klik kanan folder `selfies` → **Change Permissions**
2. Set ke: `755` (rwxr-xr-x)
3. Centang **"Recurse into subdirectories"**
4. Klik **Change Permissions**

## Step 3: Upload File Controller yang Diupdate

Saya akan buat versi yang **FORCE** simpan di public folder.

**File:** `app/Http/Controllers/Employee/AttendanceController.php`

Cari baris sekitar 617-621, ganti dengan:

```php
// FORCE production mode - simpan langsung ke public folder
$isProduction = true; // Force ke production mode
```

**File:** `app/Http/Controllers/Student/AttendanceController.php`

Cari baris sekitar 373-377, ganti dengan:

```php
// FORCE production mode - simpan langsung ke public folder
$isProduction = true; // Force ke production mode
```

Upload kedua file ini ke server.

## Step 4: Clear Cache via cPanel Terminal

Jika ada akses Terminal di cPanel:

```bash
cd /home/username/absensi_app
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

Atau via File Manager, hapus folder:
- `bootstrap/cache/*.php` (semua file .php di sini kecuali .gitignore)

## Step 5: Test Upload Lagi

1. Login sebagai pegawai
2. Absen masuk
3. Ambil foto
4. Kirim
5. **Cek apakah foto muncul**

## Step 6: Verify File Ada

Via cPanel File Manager:
1. Navigate ke `public_html/selfies/employee/`
2. **Harus ada file JPG** dengan nama `employee_30_in_[timestamp].jpg`
3. Klik kanan file → **View** untuk memastikan gambarnya benar

## Step 7: Test Akses Langsung

Buka di browser:
```
https://siab.mtsn2kotamalang.sch.id/selfies/employee/employee_30_in_[timestamp].jpg
```

(Ganti [timestamp] dengan angka yang sesuai dari nama file)

**Harus bisa diakses**, tidak 404.

---

## Jika Masih 404 Setelah Step di Atas

Berarti ada masalah lain. Cek:

### 1. .htaccess di public_html
Buka file `public_html/.htaccess`, pastikan ada:

```apache
RewriteEngine On

# Jangan redirect untuk folder selfies
RewriteCond %{REQUEST_URI} !^/selfies/
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^ index.php [L]
```

### 2. Cek Error Log
Via cPanel:
1. **Metrics** → **Errors**
2. Cari error terkait selfies atau permission

### 3. PHP Version
Pastikan PHP version minimal 8.1:
1. cPanel → **MultiPHP Manager**
2. Set domain ke **PHP 8.1** atau **8.2**

---

## Kembalikan ke Auto-Detect (Opsional)

Setelah berhasil, Anda bisa kembalikan ke auto-detect dengan mengganti:

```php
$isProduction = true; // Force
```

Menjadi:

```php
$publicSelfiesPath = public_path('selfies');
$isProduction = file_exists($publicSelfiesPath) && is_dir($publicSelfiesPath);
```

Tapi karena folder `public_html/selfies` sudah ada, seharusnya deteksi otomatis juga akan bekerja.
