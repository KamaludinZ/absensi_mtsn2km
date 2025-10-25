# Final Debug - Foto Masih 404

## Kemungkinan Penyebab & Solusi

### Penyebab 1: .htaccess Redirect Semua Request ke Laravel

**.htaccess yang SALAH:**
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^ index.php [L]
```
☝️ Ini akan redirect SEMUA request termasuk `/selfies/` ke Laravel!

**.htaccess yang BENAR:**
```apache
RewriteEngine On

# PENTING: Jangan redirect folder selfies
RewriteCond %{REQUEST_URI} !^/selfies
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^ index.php [L]
```

**SOLUSI:**
1. Login ke cPanel → File Manager
2. Navigate ke `/public_html/`
3. Edit file `.htaccess`
4. **TAMBAHKAN** baris ini **SEBELUM** `RewriteCond %{REQUEST_FILENAME} !-d`:
   ```apache
   RewriteCond %{REQUEST_URI} !^/selfies
   ```
5. Save

**Atau GANTI SELURUH ISI** `.htaccess` dengan ini:
```apache
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # PENTING: Jangan redirect folder selfies, build, dan storage
    RewriteCond %{REQUEST_URI} !^/selfies
    RewriteCond %{REQUEST_URI} !^/build
    RewriteCond %{REQUEST_URI} !^/storage

    # Redirect Trailing Slashes If Not A Folder...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    # Send Requests To Front Controller...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
```

---

### Penyebab 2: File Tidak Ada di Folder

**CEK MANUAL:**
1. cPanel → File Manager
2. Navigate ke `/public_html/selfies/employee/`
3. **Apakah ada file `employee_30_in_1761202104.jpg`?**

**Jika TIDAK ADA:**
- Foto tidak tersimpan (permission error atau path salah)
- Cek Laravel log: `/home/username/absensi_app/storage/logs/laravel.log`

**Jika ADA:**
- Masalah di .htaccess (lihat Penyebab 1)
- Atau permission salah (lihat Penyebab 3)

---

### Penyebab 3: Permission Salah

**SOLUSI:**
Via cPanel Terminal atau SSH:
```bash
# Set permission yang benar
chmod 755 /home/username/public_html/selfies
chmod 755 /home/username/public_html/selfies/employee
chmod 755 /home/username/public_html/selfies/student
chmod 644 /home/username/public_html/selfies/employee/*.jpg
```

Via cPanel File Manager:
1. Klik kanan folder `selfies` → Permissions
2. Set ke: `0755` (rwxr-xr-x)
3. Centang "Recurse into subdirectories"
4. Apply

---

### Penyebab 4: URL yang Dicoba Salah

**TEST AKSES LANGSUNG:**
Buka di browser:
```
https://siab.mtsn2kotamalang.sch.id/selfies/employee/employee_30_in_1761202104.jpg
```

**Jika 404:**
- Masalah di .htaccess (kemungkinan terbesar)
- Atau file tidak ada

**Jika BERHASIL:**
- Masalah di view cache
- Clear cache: `php artisan view:clear`

---

## Quick Test Script

Buat file `/home/username/public_html/test_selfies.php`:

```php
<?php
$file = 'selfies/employee/employee_30_in_1761202104.jpg';
$fullPath = __DIR__ . '/' . $file;

echo "Test Akses Foto\n\n";
echo "1. File path: $fullPath\n";
echo "2. File exists: " . (file_exists($fullPath) ? 'YES ✓' : 'NO ✗') . "\n";

if (file_exists($fullPath)) {
    echo "3. File size: " . filesize($fullPath) . " bytes\n";
    echo "4. Readable: " . (is_readable($fullPath) ? 'YES ✓' : 'NO ✗') . "\n";
    echo "5. Permission: " . substr(sprintf('%o', fileperms($fullPath)), -4) . "\n";
}

echo "\n6. URL: https://siab.mtsn2kotamalang.sch.id/$file\n";
echo "\n✓ Jika file exists = YES, coba buka URL di atas.\n";
echo "✓ Jika 404, masalah di .htaccess!\n";
?>
```

Akses: `https://siab.mtsn2kotamalang.sch.id/test_selfies.php`

---

## Solusi Tercepat (90% Berhasil)

### Via cPanel File Manager:

1. **Edit .htaccess**
   - Navigate ke `/public_html/`
   - Edit `.htaccess`
   - Tambahkan baris ini **SETELAH** `RewriteEngine On`:
   ```apache
   # Skip rewrite untuk folder selfies
   RewriteCond %{REQUEST_URI} ^/selfies [NC,OR]
   RewriteCond %{REQUEST_URI} ^/build [NC,OR]
   RewriteCond %{REQUEST_URI} ^/storage [NC]
   RewriteRule .* - [L]
   ```
   - Save

2. **Test Upload Foto Baru**
   - Login → Absen masuk → Upload foto
   - **Harus langsung muncul**

3. **Jika Masih 404**
   - Kirim isi lengkap file `.htaccess` Anda
   - Screenshot File Manager di folder `/public_html/selfies/employee/`
   - Test akses: `https://siab.../test_selfies.php`

---

## Debugging Command (Via SSH/Terminal)

```bash
# 1. Cek apakah file ada
ls -la /home/username/public_html/selfies/employee/ | grep employee_30

# 2. Cek .htaccess
cat /home/username/public_html/.htaccess

# 3. Test wget
cd /tmp
wget https://siab.mtsn2kotamalang.sch.id/selfies/employee/employee_30_in_1761202104.jpg
# Jika dapat "404 Not Found" → masalah di .htaccess
# Jika berhasil download → masalah di browser cache

# 4. Check Laravel routes
cd /home/username/absensi_app
php artisan route:list | grep selfies
# Harusnya TIDAK ADA route untuk /selfies
```

---

## Yang HARUS Anda Lakukan Sekarang

Pilih salah satu:

### Option A: Manual Check (Mudah via cPanel)
1. ✅ Buka cPanel File Manager
2. ✅ Cek folder `/public_html/selfies/employee/`
3. ✅ Apakah ada file `employee_30_in_1761202104.jpg`?
4. ✅ Kirim screenshot folder-nya ke sini
5. ✅ Copy-paste isi file `/public_html/.htaccess` ke sini

### Option B: Upload .htaccess Baru (Cepat)
Saya akan buat file `.htaccess` yang sudah fix. Upload langsung.

---

Mana yang lebih mudah untuk Anda? Pilih A atau B, nanti saya lanjutkan solusinya.
