# Deployment Guide - Version 1.1.2
**Sistem Absensi MTsN 2 Kota Malang**

Tanggal: 27 November 2025
Versi: 1.1.2

---

## Ringkasan Update

Update versi 1.1.2 ini berisi perbaikan bug kritis dan penambahan fitur baru:

### Bug Fixes
1. Status Kehadiran - Perbaikan perhitungan status Terlambat, Tepat Waktu, Pulang Awal
2. Location Privacy - Tampilan koordinat di halaman absensi pegawai sesuai setting

### New Features
1. Salary Settings Month/Year - Pengaturan gaji per bulan tanpa merusak data historis
2. Auto-Copy Employee Shifts - Shift bulan sebelumnya otomatis di-copy ke bulan baru

---

## Files yang Diperbarui

### Database Migrations (NEW)
database/migrations/2025_11_21_130903_create_salaries_table.php
database/migrations/2025_11_27_084807_add_month_year_to_salaries_table.php

### Models
app/Models/Salary.php

### Controllers
app/Http/Controllers/Admin/EmployeeController.php
app/Http/Controllers/Admin/EmployeeShiftController.php
app/Http/Controllers/Admin/ReportController.php
app/Http/Controllers/Employee/ReportController.php

### Views
resources/views/admin/employees/salary-settings.blade.php
resources/views/employee/attendance.blade.php

### Documentation (NEW)
CHANGELOG.md
DEPLOYMENT_GUIDE_v1.1.2.md

---

## Langkah Deployment

### Step 1: Backup Production

Login ke /admin/backup dan buat backup database
Download backup file ke lokasi aman

### Step 2: Update Code

Upload 16 files yang diperbarui ke server production

**PENTING:** Pastikan file migration `create_salaries_table.php` diupload SEBELUM menjalankan migrate!

### Step 3: Database Migration

php artisan migrate --force

### Step 4: Clear Cache

php artisan config:clear
php artisan cache:clear
php artisan view:clear

### Step 5: Test

Test salary settings dengan filter month/year
Test employee shifts auto-copy
Test attendance status display
Test location privacy settings

---

## Critical Files List

WAJIB DIUPLOAD:

1. database/migrations/2025_11_21_130903_create_salaries_table.php
2. database/migrations/2025_11_27_084807_add_month_year_to_salaries_table.php
3. app/Models/Salary.php
4. app/Http/Controllers/Admin/EmployeeController.php
5. app/Http/Controllers/Admin/EmployeeShiftController.php
6. app/Http/Controllers/Admin/ReportController.php
7. app/Http/Controllers/Employee/ReportController.php
8. resources/views/admin/employees/salary-settings.blade.php
9. resources/views/employee/attendance.blade.php
10. CHANGELOG.md
11. DEPLOYMENT_GUIDE_v1.1.2.md
12. FILES_TO_UPLOAD_v1.1.2.txt
13. PRODUCTION_FIX_GUIDE.md
14. VERSION
15. package.json
16. README.md

---

## Database Changes

Table: salaries (NEW TABLE - will be created if not exists)
- id (bigint)
- employee_id (bigint, foreign key to employees)
- base_salary (decimal 15,2)
- meal_allowance (decimal 15,2)
- transport_allowance (decimal 15,2)
- performance_allowance (decimal 15,2)
- other_allowances (decimal 15,2)
- total_salary (decimal 15,2)
- month (tinyint, default 1) - NEW COLUMN
- year (smallint, default 2025) - NEW COLUMN
- created_at, updated_at (timestamps)
- Added unique constraint: (employee_id, month, year)

---

## Troubleshooting

Jika mengalami error "relation salaries does not exist":
- Baca file **PRODUCTION_FIX_GUIDE.md** untuk solusi lengkap
- File migration `create_salaries_table.php` harus diupload terlebih dahulu

---

## Rollback Procedure

Jika terjadi masalah:

1. Restore database dari backup via /admin/backup
2. Replace files dengan versi sebelumnya
3. php artisan migrate:rollback --step=1
4. php artisan cache:clear

---

## Support

Email: it@mtsn2kotamalang.sch.id
Phone: (0341) XXX-XXXX
