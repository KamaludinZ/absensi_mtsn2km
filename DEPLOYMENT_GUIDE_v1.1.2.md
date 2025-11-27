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

Upload 9 files yang diperbarui ke server production

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

1. database/migrations/2025_11_27_084807_add_month_year_to_salaries_table.php
2. app/Models/Salary.php
3. app/Http/Controllers/Admin/EmployeeController.php
4. app/Http/Controllers/Admin/EmployeeShiftController.php
5. app/Http/Controllers/Admin/ReportController.php
6. app/Http/Controllers/Employee/ReportController.php
7. resources/views/admin/employees/salary-settings.blade.php
8. resources/views/employee/attendance.blade.php
9. CHANGELOG.md
10. DEPLOYMENT_GUIDE_v1.1.2.md

---

## Database Changes

Table: salaries
- Added column: month (tinyint, default 1)
- Added column: year (smallint, default 2025)
- Added unique constraint: (employee_id, month, year)

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
