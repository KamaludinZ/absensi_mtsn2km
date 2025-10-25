# Setup Database Production - Fix Error "Column email does not exist"

## Masalah
Error: `SQLSTATE[42703]: Undefined column: 7 ERROR: column "email" of relation "users" does not exist`

**Penyebab:** Struktur tabel di database production tidak sesuai dengan migrasi terbaru.

## Solusi

### OPSI 1: Fresh Migration (Database Kosong/Baru - RECOMMENDED)

**Gunakan ini jika:**
- Database masih kosong atau baru setup
- Tidak ada data production yang perlu dipertahankan
- Ini adalah deployment pertama kali

**Langkah-langkah via SSH:**

```bash
# 1. Masuk ke folder aplikasi
cd /home/mtsnkot4/absensi_app/

# 2. Drop semua tabel dan migrasi ulang
php artisan migrate:fresh

# 3. Jalankan seeder untuk data awal (tanpa dummy data)
php artisan db:seed --class=DatabaseSeeder

# 4. Clear cache
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

**CATATAN:** `migrate:fresh` akan **MENGHAPUS SEMUA DATA** di database!

---

### OPSI 2: Fresh Migration + Dummy Data (Development/Testing)

**Gunakan ini untuk development atau testing server:**

```bash
cd /home/mtsnkot4/absensi_app/

# Drop semua dan migrate + seed sekaligus
php artisan migrate:fresh --seed

# Clear cache
php artisan optimize:clear
php artisan config:cache
```

**CATATAN:** Ini akan membuat data dummy untuk testing.

---

### OPSI 3: Reset Database dari cPanel (Jika SSH tidak tersedia)

**Via cPanel PhpPgAdmin atau Database Manager:**

1. **Login ke cPanel** → **PostgreSQL Databases** atau **PhpPgAdmin**

2. **Drop semua tabel** (atau drop database dan buat ulang):
   ```sql
   -- Drop semua tabel (jalankan satu per satu)
   DROP TABLE IF EXISTS attendances CASCADE;
   DROP TABLE IF EXISTS permissions CASCADE;
   DROP TABLE IF EXISTS payrolls CASCADE;
   DROP TABLE IF EXISTS employee_shifts CASCADE;
   DROP TABLE IF EXISTS schedules CASCADE;
   DROP TABLE IF EXISTS students CASCADE;
   DROP TABLE IF EXISTS employees CASCADE;
   DROP TABLE IF EXISTS rombels CASCADE;
   DROP TABLE IF EXISTS grades CASCADE;
   DROP TABLE IF EXISTS academic_years CASCADE;
   DROP TABLE IF EXISTS users CASCADE;
   DROP TABLE IF EXISTS roles CASCADE;
   DROP TABLE IF EXISTS settings CASCADE;
   DROP TABLE IF EXISTS holidays CASCADE;
   DROP TABLE IF EXISTS security_logs CASCADE;
   DROP TABLE IF EXISTS login_attempts CASCADE;
   DROP TABLE IF EXISTS sessions CASCADE;
   DROP TABLE IF EXISTS cache CASCADE;
   DROP TABLE IF EXISTS cache_locks CASCADE;
   DROP TABLE IF EXISTS jobs CASCADE;
   DROP TABLE IF EXISTS job_batches CASCADE;
   DROP TABLE IF EXISTS failed_jobs CASCADE;
   DROP TABLE IF EXISTS password_reset_tokens CASCADE;
   DROP TABLE IF EXISTS personal_access_tokens CASCADE;
   DROP TABLE IF EXISTS migrations CASCADE;
   ```

3. **Via cPanel Terminal atau SSH**, jalankan migrasi:
   ```bash
   cd /home/mtsnkot4/absensi_app/
   php artisan migrate
   php artisan db:seed --class=DatabaseSeeder
   ```

---

### OPSI 4: Manual Migration Check (Jika ada data yang perlu dipertahankan)

**Jika ada data production yang HARUS dipertahankan:**

```bash
cd /home/mtsnkot4/absensi_app/

# 1. Cek status migrasi
php artisan migrate:status

# 2. Rollback ke migrasi tertentu (jika perlu)
php artisan migrate:rollback --step=5

# 3. Jalankan migrasi yang belum dijalankan
php artisan migrate

# 4. Seed hanya data yang diperlukan (skip DummyDataSeeder)
php artisan db:seed --class=DatabaseSeeder
```

**TAPI**, jika tetap error, berarti ada konflik struktur. Perlu migration baru untuk fix.

---

## Verifikasi Database Setelah Migration

**Cek struktur tabel `users` via SQL:**

```sql
-- Via PhpPgAdmin atau psql
SELECT column_name, data_type, is_nullable
FROM information_schema.columns
WHERE table_name = 'users'
ORDER BY ordinal_position;
```

**Harusnya kolom users:**
- id (bigint)
- name (varchar)
- username (varchar) - UNIQUE
- password (varchar)
- role_id (bigint) - FOREIGN KEY
- remember_token (varchar)
- created_at (timestamp)
- updated_at (timestamp)
- deleted_at (timestamp) - NULLABLE

**TIDAK ADA** kolom `email` di tabel `users`!

---

## Setelah Migration Berhasil

1. **Cek login admin default:**
   - Username: `admin`
   - Password: `password`

2. **SEGERA ganti password admin:**
   ```bash
   php artisan tinker
   ```
   ```php
   $user = App\Models\User::where('username', 'admin')->first();
   $user->password = bcrypt('password_baru_yang_aman');
   $user->save();
   exit
   ```

3. **Clear dan optimize:**
   ```bash
   php artisan optimize:clear
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

---

## Troubleshooting

### Error: "Access denied for user"
**Solusi:** Pastikan kredensial database di `.env` benar:
```env
DB_CONNECTION=pgsql
DB_HOST=localhost
DB_PORT=5432
DB_DATABASE=mtsnkot4_absensi  # Sesuaikan dengan nama DB Anda
DB_USERNAME=mtsnkot4_dbuser   # Sesuaikan
DB_PASSWORD=your_db_password  # Sesuaikan
```

### Error: "Migration table not found"
**Solusi:**
```bash
php artisan migrate:install
php artisan migrate
```

### Error: "Class not found" atau "Composer autoload"
**Solusi:**
```bash
cd /home/mtsnkot4/absensi_app/
composer install --no-dev --optimize-autoloader
php artisan optimize:clear
```

### DummyDataSeeder Error (tapi ingin skip)
**Edit DatabaseSeeder.php** - Comment line 106:
```php
// $this->call(DummyDataSeeder::class);
```

Atau jalankan seeder tanpa DummyData:
```bash
php artisan db:seed --class=DatabaseSeeder
```

---

## Catatan Penting untuk Production

1. **JANGAN run DummyDataSeeder di production** - ini hanya untuk development
2. **Backup database** sebelum migrate:fresh (jika ada data)
3. **Set .env dengan benar** sebelum migrate:
   ```env
   APP_ENV=production
   APP_DEBUG=false
   ```
4. **Ganti password default** setelah seeding
5. **Test login** setelah semua selesai

---

## Quick Command Reference

```bash
# Fresh start (HAPUS SEMUA DATA)
php artisan migrate:fresh

# Fresh + Seed data awal saja (tanpa dummy)
php artisan migrate:fresh
php artisan db:seed --class=DatabaseSeeder

# Cek status migrasi
php artisan migrate:status

# Clear all cache
php artisan optimize:clear
php artisan config:cache

# Test database connection
php artisan tinker
>>> DB::connection()->getPdo();
>>> exit
```

---

Pilih opsi yang sesuai dengan kondisi server production Anda. **Untuk deployment pertama kali, gunakan OPSI 1**.
