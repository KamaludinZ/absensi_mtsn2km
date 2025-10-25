# Debug Environment Detection

## Tolong jalankan command ini di production server:

### 1. Cek environment Laravel
```bash
cd /home/username/absensi_app
php artisan tinker
```

Lalu di dalam tinker:
```php
app()->environment()
// Harus return: "production"

app()->environment('production')
// Harus return: true

exit
```

### 2. Cek apakah file foto ada di public_html
```bash
ls -la /home/username/public_html/selfies/employee/ | grep employee_30
```

### 3. Jika TIDAK ADA, cek di storage (berarti masih tersimpan di tempat lama)
```bash
ls -la /home/username/absensi_app/storage/app/public/selfies/employee/ | grep employee_30
```

### 4. Cek isi database
```bash
php artisan tinker
```

Di tinker:
```php
\App\Models\Attendance::where('attendable_id', 30)
    ->where('attendable_type', 'App\Models\Employee')
    ->whereNotNull('selfie_path_in')
    ->orderBy('id', 'desc')
    ->first()
    ->selfie_path_in;
```

Harusnya return: `selfies/employee/employee_30_in_1761199587.jpg`

### 5. Test manual akses file
Buka di browser:
```
https://siab.mtsn2kotamalang.sch.id/selfies/employee/employee_30_in_1761199587.jpg
```

Jika 404, coba:
```
https://siab.mtsn2kotamalang.sch.id/storage/selfies/employee/employee_30_in_1761199587.jpg
```

## Kirim Hasilnya
Tolong copy hasil dari command di atas dan kirim ke sini.
