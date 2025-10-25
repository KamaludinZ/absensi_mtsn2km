# FIX: Foto Tersimpan di Tempat yang Salah

## Masalah Ditemukan!

**Foto tersimpan di:**
```
/home/mtnkot4/absensi_app/public/selfies/employee/employee_30_in_1761202104.jpg
```
❌ Folder ini TIDAK bisa diakses via web!

**Seharusnya tersimpan di:**
```
/home/mtnkot4/public_html/selfies/employee/employee_30_in_1761202104.jpg
```
✅ Folder ini adalah document root, bisa diakses via web!

## Penyebab

`public_path()` di Laravel masih nunjuk ke:
```php
/home/mtnkot4/absensi_app/public/  // SALAH!
```

Seharusnya nunjuk ke:
```php
/home/mtnkot4/public_html/  // BENAR!
```

---

## Solusi 1: Pindahkan Foto yang Sudah Ada (Via cPanel)

### Via File Manager:

1. Login cPanel → File Manager
2. Navigate ke `/home/mtnkot4/absensi_app/public/selfies/`
3. **Copy** folder `selfies` (klik kanan → Copy)
4. Navigate ke `/home/mtnkot4/public_html/`
5. **Paste** di sini
6. Sekarang struktur jadi:
   ```
   /home/mtnkot4/public_html/selfies/
   ├── employee/
   │   └── employee_30_in_1761202104.jpg
   └── student/
   ```

### Via Terminal/SSH:

```bash
# Copy semua foto dari absensi_app/public ke public_html
cp -r /home/mtnkot4/absensi_app/public/selfies /home/mtnkot4/public_html/

# Set permission
chmod 755 /home/mtnkot4/public_html/selfies -R
```

---

## Solusi 2: Fix index.php di public_html

File `/home/mtnkot4/public_html/index.php` harus di-update supaya Laravel tahu document root-nya di `public_html`, bukan `absensi_app/public`.

### Edit index.php

**Buka:** `/home/mtnkot4/public_html/index.php`

**Tambahkan** baris ini **SETELAH** `define('LARAVEL_START', ...)`:

```php
<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// ========================================
// IMPORTANT: Override public_path untuk cPanel
// ========================================
$_SERVER['DOCUMENT_ROOT'] = __DIR__;

/*
|--------------------------------------------------------------------------
| Production Configuration
|--------------------------------------------------------------------------
*/

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../absensi_app/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../absensi_app/vendor/autoload.php';

// Bootstrap Laravel and handle the request...
$app = require_once __DIR__.'/../absensi_app/bootstrap/app.php';

// ========================================
// CRITICAL: Override public_path
// ========================================
$app->bind('path.public', function() {
    return '/home/mtnkot4/public_html';
});

$kernel = $app->make(Kernel::class);

$response = $kernel->handle(
    $request = Request::capture()
)->send();

$kernel->terminate($request, $response);
```

**Atau versi lebih aman (auto-detect):**

```php
// ========================================
// CRITICAL: Override public_path
// ========================================
$app->bind('path.public', function() {
    return realpath(__DIR__);
});
```

---

## Solusi 3: Update Controller (Hardcode Path)

Jika solusi 1 & 2 tidak berhasil, hardcode path di controller.

**Edit:** `app/Http/Controllers/Employee/AttendanceController.php`

**Cari baris ~625:**
```php
if ($isProduction) {
    // Production: Save directly to public folder (cPanel shared hosting)
    $path = public_path($directory . '/' . $filename);
```

**GANTI dengan:**
```php
if ($isProduction) {
    // Production: Save directly to public_html (cPanel shared hosting)
    // Hardcode untuk cPanel: /home/mtnkot4/public_html
    $path = '/home/mtnkot4/public_html/' . $directory . '/' . $filename;
```

**Lakukan yang sama untuk Student/AttendanceController.php**

---

## Test Setelah Fix

### 1. Test public_path()

Buat file: `/home/mtnkot4/public_html/test_public_path.php`

```php
<?php
require __DIR__.'/../absensi_app/vendor/autoload.php';
$app = require_once __DIR__.'/../absensi_app/bootstrap/app.php';

// Override if needed
$app->bind('path.public', function() {
    return realpath(__DIR__);
});

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

echo "public_path(): " . public_path() . "\n";
echo "Expected: /home/mtnkot4/public_html\n";
echo "Match: " . (public_path() === '/home/mtnkot4/public_html' ? 'YES ✓' : 'NO ✗') . "\n";
```

Akses: `https://siab.mtsn2kotamalang.sch.id/test_public_path.php`

### 2. Upload Foto Baru

1. Login sebagai pegawai
2. Absen masuk → Upload foto
3. **Cek di cPanel File Manager:**
   - Navigate ke `/home/mtnkot4/public_html/selfies/employee/`
   - **Harus ada file JPG baru!**
4. Foto harus muncul di laporan

### 3. Test Akses Langsung

```
https://siab.mtsn2kotamalang.sch.id/selfies/employee/employee_30_in_1761202104.jpg
```

**Harus bisa diakses!**

---

## Rekomendasi: Gunakan Solusi Mana?

### Solusi Tercepat (5 menit):
✅ **Solusi 1** (Copy foto) + **Solusi 2** (Update index.php)

1. Copy folder `selfies` dari `absensi_app/public` ke `public_html`
2. Edit `public_html/index.php`, tambah:
   ```php
   $app->bind('path.public', function() {
       return realpath(__DIR__);
   });
   ```
3. Done!

### Solusi Paling Aman (10 menit):
✅ **Solusi 1** + **Solusi 2** + Update `.htaccess`

Sudah termasuk semua fix yang diperlukan.

### Solusi Emergency (2 menit):
✅ **Solusi 3** (Hardcode)

Langsung hardcode path di controller. Quick & dirty tapi works!

---

## File yang Perlu Diupdate

1. `/home/mtnkot4/public_html/index.php` - Tambah override public_path
2. `/home/mtnkot4/public_html/.htaccess` - Exclude /selfies dari rewrite
3. Copy: `/home/mtnkot4/absensi_app/public/selfies` → `/home/mtnkot4/public_html/selfies`

---

Pilih solusi mana yang mau dipakai? Saya akan buat file yang siap upload!
