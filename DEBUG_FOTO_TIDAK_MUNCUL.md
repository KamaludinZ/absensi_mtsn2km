# Debug: Foto Tersimpan Tapi Tidak Muncul di Laporan

## PENTING: Jalankan di cPanel Terminal / SSH Production

### 1. Cek Path di Database
```bash
cd /home/username/absensi_app
php artisan tinker
```

Di tinker:
```php
// Ambil record terakhir employee 30
$att = \App\Models\Attendance::where('attendable_id', 30)
    ->where('attendable_type', 'App\Models\Employee')
    ->whereNotNull('selfie_path_in')
    ->orderBy('id', 'desc')
    ->first();

if ($att) {
    echo "ID: " . $att->id . "\n";
    echo "Path DB: " . $att->selfie_path_in . "\n";
    echo "Created: " . $att->created_at . "\n\n";

    // Cek public_path()
    echo "Public Path: " . public_path() . "\n";

    // Full path
    $fullPath = public_path($att->selfie_path_in);
    echo "Full Path: " . $fullPath . "\n";

    // Check if exists
    echo "File Exists: " . (file_exists($fullPath) ? 'YES ✓' : 'NO ✗') . "\n";

    // Check URL
    echo "URL: " . asset($att->selfie_path_in) . "\n";
} else {
    echo "Tidak ada record!\n";
}

exit
```

**KIRIM HASIL OUTPUT DI ATAS!**

### 2. Cek Apakah View Cache Sudah Di-Clear
```bash
cd /home/username/absensi_app

# Clear semua cache
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear

# Verify cache cleared
ls -la storage/framework/views/ | wc -l
# Harus menunjukkan angka kecil (2-5 file saja)
```

### 3. Test Akses File Langsung
Dari hasil step 1, copy URL-nya, lalu buka di browser:
```
https://siab.mtsn2kotamalang.sch.id/selfies/employee/employee_30_in_XXXXXX.jpg
```

**Apakah bisa diakses?**
- ✅ Jika BISA → Masalah di view/cache
- ❌ Jika TIDAK → Masalah di .htaccess atau permission

### 4. Cek Browser Console
Buka halaman laporan → Tekan F12 → Tab Console

Cari error yang berisi:
- 404 Not Found
- Failed to load resource
- selfies/employee

**Screenshot error-nya dan kirim!**

### 5. Inspect Element HTML
Buka halaman laporan → Klik kanan gambar (atau area kosong foto) → Inspect

Cek tag `<img>`, harusnya:
```html
<img src="https://siab.mtsn2kotamalang.sch.id/selfies/employee/employee_30_in_XXXXX.jpg" ...>
```

**Screenshot atau copy HTML-nya dan kirim!**

---

## Quick Fix Berdasarkan Hasil

### Jika Path di Database SALAH (masih pakai "storage/app/public/...")

**Solusi:** Update path di database
```bash
cd /home/username/absensi_app
php artisan tinker
```

```php
// Update semua path yang salah
\DB::table('attendances')
    ->whereNotNull('selfie_path_in')
    ->where('selfie_path_in', 'like', 'storage/app/public/selfies/%')
    ->update([
        'selfie_path_in' => \DB::raw("REPLACE(selfie_path_in, 'storage/app/public/', '')")
    ]);

\DB::table('attendances')
    ->whereNotNull('selfie_path_out')
    ->where('selfie_path_out', 'like', 'storage/app/public/selfies/%')
    ->update([
        'selfie_path_out' => \DB::raw("REPLACE(selfie_path_out, 'storage/app/public/', '')")
    ]);

echo "Updated!\n";
exit
```

### Jika View Cache Belum Clear

```bash
cd /home/username/absensi_app

# Hard clear
rm -rf storage/framework/views/*
rm -rf storage/framework/cache/data/*
rm -rf bootstrap/cache/*.php

# Rebuild cache
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Jika File Exists di Server Tapi 404

Cek `.htaccess` di public_html:

```bash
cat /home/username/public_html/.htaccess
```

Pastikan ada:
```apache
RewriteEngine On

# PENTING: Jangan redirect folder selfies
RewriteCond %{REQUEST_URI} !^/selfies
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^ index.php [L]
```

Jika TIDAK ADA baris `RewriteCond %{REQUEST_URI} !^/selfies`, tambahkan!

### Jika Browser Cache

Hard refresh browser:
- Chrome/Edge: `Ctrl + Shift + R`
- Firefox: `Ctrl + F5`

Atau buka Incognito/Private mode.

---

## Test Manual View Logic

Create file test: `/home/username/absensi_app/test_view.php`

```php
<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Test path
$path = 'selfies/employee/employee_30_in_1761201210.jpg';

echo "Testing Path: $path\n\n";

echo "1. public_path(): " . public_path() . "\n";
echo "2. Full path: " . public_path($path) . "\n";
echo "3. File exists: " . (file_exists(public_path($path)) ? 'YES' : 'NO') . "\n";
echo "4. asset(): " . asset($path) . "\n";
echo "5. Storage::url(): " . Storage::url($path) . "\n";

// Test view logic
$publicPath = public_path($path);
$imageUrl = file_exists($publicPath)
    ? asset($path)
    : Storage::url($path);

echo "\n6. Selected URL: $imageUrl\n";
```

Jalankan:
```bash
php test_view.php
```

**Kirim output-nya!**

---

## Kemungkinan Masalah & Solusi Cepat

| Gejala | Penyebab | Solusi |
|--------|----------|--------|
| Foto di folder tapi 404 saat diakses | .htaccess redirect | Edit .htaccess, exclude /selfies |
| Path di DB: `storage/app/public/selfies/...` | Upload sebelum fix | Update DB (query di atas) |
| HTML img src kosong | View cache | Clear view cache |
| Gambar tidak load | Browser cache | Hard refresh (Ctrl+Shift+R) |
| file_exists() return false | Path salah | Check public_path() output |

---

## Yang HARUS Anda Kirim

Untuk saya bisa bantu, kirim:

1. ✅ **Output dari Step 1** (PHP Tinker - cek path DB)
2. ✅ **Screenshot browser console** (F12 → Console tab)
3. ✅ **Screenshot atau copy HTML** element `<img>` (Inspect element)
4. ✅ **Test akses langsung** - apakah URL `https://siab.../selfies/employee/xxx.jpg` bisa dibuka?

Dengan info ini saya bisa tahu persis masalahnya di mana.
