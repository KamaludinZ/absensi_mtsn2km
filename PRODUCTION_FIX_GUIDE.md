# Panduan Mengatasi Error Migration Production
**Version 1.1.2 - Salaries Table Issue**

---

## ERROR YANG TERJADI

```
SQLSTATE[42P01]: Undefined table: 7 ERROR: relation "salaries" does not exist
```

Migration `2025_11_27_084807_add_month_year_to_salaries_table` gagal karena table `salaries` belum dibuat di production.

---

## PENYEBAB

File migration `2025_11_21_130903_create_salaries_table.php` tidak diupload ke production pada update sebelumnya.

---

## SOLUSI - LANGKAH DEMI LANGKAH

### STEP 1: Cek Status Migration di Production

Login ke server production via SSH atau terminal, lalu jalankan:

```bash
php artisan migrate:status
```

Cari baris berikut:
- `2025_11_21_130903_create_salaries_table` - Apakah ada? Statusnya apa?
- `2025_11_27_084807_add_month_year_to_salaries_table` - Statusnya FAILED

### STEP 2: Upload File Migration yang Kurang

Upload file berikut ke server production:

```
database/migrations/2025_11_21_130903_create_salaries_table.php
```

**Lokasi di server:**
```
/path/to/your/app/database/migrations/2025_11_21_130903_create_salaries_table.php
```

### STEP 3: Rollback Migration yang Gagal

Jika migration `add_month_year_to_salaries_table` sudah tercatat tapi failed:

```bash
php artisan migrate:rollback --step=1
```

Atau jika perlu rollback manual, jalankan di database:

```sql
DELETE FROM migrations
WHERE migration = '2025_11_27_084807_add_month_year_to_salaries_table';
```

### STEP 4: Jalankan Migration Ulang

Sekarang jalankan migration dengan urutan yang benar:

```bash
php artisan migrate --force
```

Output yang benar:

```
2025_11_21_130903_create_salaries_table .................. DONE
2025_11_27_084807_add_month_year_to_salaries_table ....... DONE
```

### STEP 5: Verifikasi Table

Cek apakah table sudah dibuat dengan benar:

```bash
php artisan tinker --execute="echo \Illuminate\Support\Facades\Schema::hasTable('salaries') ? 'Table EXISTS' : 'Table NOT FOUND';"
```

Harus output: `Table EXISTS`

Cek kolom:

```bash
php artisan tinker --execute="var_dump(\Illuminate\Support\Facades\Schema::getColumnListing('salaries'));"
```

Harus ada kolom: `id`, `employee_id`, `base_salary`, `meal_allowance`, `transport_allowance`, `performance_allowance`, `other_allowances`, `total_salary`, `month`, `year`, `created_at`, `updated_at`

### STEP 6: Clear Cache

```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan config:cache
php artisan route:cache
```

### STEP 7: Test Aplikasi

1. Buka browser dan akses `/admin/employees/salary-settings`
2. Pastikan halaman load tanpa error
3. Cek filter Month/Year muncul
4. Coba simpan salary untuk pegawai
5. Cek apakah data tersimpan

---

## ALTERNATIF - Jika Rollback Tidak Bekerja

Jika rollback tidak bekerja, Anda bisa membuat table secara manual via SQL:

### Login ke PostgreSQL

```bash
psql -U postgres -d nama_database_production
```

### Jalankan SQL Berikut

```sql
-- Create salaries table
CREATE TABLE IF NOT EXISTS salaries (
    id BIGSERIAL PRIMARY KEY,
    employee_id BIGINT NOT NULL,
    base_salary DECIMAL(15,2) DEFAULT 0,
    meal_allowance DECIMAL(15,2) DEFAULT 0,
    transport_allowance DECIMAL(15,2) DEFAULT 0,
    performance_allowance DECIMAL(15,2) DEFAULT 0,
    other_allowances DECIMAL(15,2) DEFAULT 0,
    total_salary DECIMAL(15,2) DEFAULT 0,
    month SMALLINT NOT NULL DEFAULT 1,
    year SMALLINT NOT NULL DEFAULT 2025,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT salaries_employee_id_foreign
        FOREIGN KEY (employee_id)
        REFERENCES employees(id)
        ON DELETE CASCADE,
    CONSTRAINT salaries_employee_month_year_unique
        UNIQUE (employee_id, month, year)
);

-- Insert migration record
INSERT INTO migrations (migration, batch)
VALUES
    ('2025_11_21_130903_create_salaries_table', (SELECT COALESCE(MAX(batch), 0) + 1 FROM migrations)),
    ('2025_11_27_084807_add_month_year_to_salaries_table', (SELECT MAX(batch) FROM migrations));
```

### Keluar dari PostgreSQL

```sql
\q
```

Lalu lanjut ke STEP 6 (Clear Cache).

---

## COMPLETE FILE LIST - Version 1.1.2

Pastikan 15 files ini sudah diupload:

1. ✅ database/migrations/2025_11_21_130903_create_salaries_table.php
2. ✅ database/migrations/2025_11_27_084807_add_month_year_to_salaries_table.php
3. ✅ app/Models/Salary.php
4. ✅ app/Http/Controllers/Admin/EmployeeController.php
5. ✅ app/Http/Controllers/Admin/EmployeeShiftController.php
6. ✅ app/Http/Controllers/Admin/ReportController.php
7. ✅ app/Http/Controllers/Employee/ReportController.php
8. ✅ resources/views/admin/employees/salary-settings.blade.php
9. ✅ resources/views/employee/attendance.blade.php
10. ✅ CHANGELOG.md
11. ✅ DEPLOYMENT_GUIDE_v1.1.2.md
12. ✅ FILES_TO_UPLOAD_v1.1.2.txt
13. ✅ VERSION
14. ✅ package.json
15. ✅ README.md

---

## TROUBLESHOOTING

### Error: "Foreign key constraint violation"

Jika ada error foreign key, pastikan table `employees` sudah ada:

```bash
php artisan tinker --execute="echo \Illuminate\Support\Facades\Schema::hasTable('employees') ? 'Employees table EXISTS' : 'ERROR: Employees table NOT FOUND';"
```

### Error: "Duplicate entry"

Jika ada error duplicate saat insert:

```sql
-- Cek data yang duplikat
SELECT employee_id, month, year, COUNT(*)
FROM salaries
GROUP BY employee_id, month, year
HAVING COUNT(*) > 1;

-- Hapus duplikat (hati-hati!)
DELETE FROM salaries a USING salaries b
WHERE a.id < b.id
  AND a.employee_id = b.employee_id
  AND a.month = b.month
  AND a.year = b.year;
```

### Error: "Migration table not found"

Recreate migration table:

```bash
php artisan migrate:install
```

---

## CONTACT SUPPORT

Jika masih ada masalah setelah mengikuti panduan ini:

**Email:** it@mtsn2kotamalang.sch.id
**Phone:** (0341) XXX-XXXX

Sertakan informasi:
- Output dari `php artisan migrate:status`
- Screenshot error message
- Versi PHP: `php -v`
- Versi PostgreSQL: `psql --version`

---

**IMPORTANT:** Pastikan sudah backup database sebelum melakukan perubahan apapun!
