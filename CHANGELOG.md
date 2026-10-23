# Changelog

Semua perubahan penting pada proyek ini akan didokumentasikan dalam file ini.

Format ini didasarkan pada [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
dan proyek ini mengikuti [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.2.9] - 2026-10-23

### Added
- Student attendance without login: NISN or RFID card (tap on a USB RFID reader), only open during the student attendance hours.
- RFID card registration per student in Admin (type the number or tap the card).
- Class promotion / transfer menu; graduated students are deactivated for attendance and RFID.
- Attendance and permission history keep the class the student was in at the time.
- Student attendance recap, per-day student schedules, student import with an Excel template.
- Docker / Coolify deployment (Dockerfile or Docker Compose), see `DEPLOY_COOLIFY.md`.
- Automatic database backups: daily, before every update that changes the database, and before a restore (all listed in Admin > Backup).
- Admin warnings for an unpatched PHP version and for data folders that are not persistent volumes.

### Changed
- Employee "Absen Pulang" before the scheduled end time now asks for confirmation; the 10-minute cooldown was removed.
- Automatic IP blocking removed (the school shares one public IP); suspicious requests are still rejected and logged.
- Database restore runs in a single transaction and replaces the whole database: it either fully succeeds or changes nothing.
- Admin panel updater is disabled on Docker (`DISABLE_WEB_UPDATER`); update by redeploying.

### Fixed
- Clock-in/clock-out times shown 7 hours off on a database server in UTC (e.g. Coolify): the database connection now uses `DB_TIMEZONE` (Asia/Jakarta); `app:fix-attendance-timezone` repairs times saved before the fix.
- Accidental "Absen Pulang" right after "Absen Masuk".
- "Buat Backup" saved files in a folder the backup list never showed.
- Admin dashboard error 500 on PostgreSQL when no academic year is active.
- Fixes from the security and function audit (permission checks, login rate limit per user, secrets kept out of logs and backups).

### Security
- PHP pinned to 8.4.27 (PHP2Shell, CVE-2026-109618 / CVE-2026-109619); `session.upload_progress` turned off.

## [1.2.8] - 2026-09-03

### Fixed
#### Report Filters - New Employees Not Showing Due to Missing Salary Filter
- **Fixed**: Critical bug where new active employees don't appear in reports even though they show in dropdown
- **Root Cause**: Salary eager loading was using `latest('created_at')` without filtering by month/year
- **Issue**:
  - Salary table has `month` and `year` columns (per-month salary settings)
  - New employees added in July/August had salary set only for those months
  - When viewing September reports, query returned wrong month's salary or NULL
  - Employees with NULL salary were skipped by `if (!$salary) { continue; }`
- **Impact**: New employees added since late July were invisible in all report pages
- **Fix**: Added month/year filter to salary eager loading across all report methods
- **Changes Applied**:
  ```php
  // Before (WRONG - gets any month's salary)
  Employee::with(['salary' => function($query) {
      $query->latest('created_at');
  }])

  // After (CORRECT - gets specific month/year salary)
  Employee::with(['salary' => function($query) use ($selectedMonth, $selectedYear) {
      $query->where('month', $selectedMonth)
            ->where('year', $selectedYear)
            ->latest('created_at');
  }])
  ```
- **Files Modified**:
  - `app/Http/Controllers/Admin/ReportController.php` (8 methods)
  - `app/Http/Controllers/Monitoring/ReportController.php` (1 method)
- **Fixed Methods**:
  - `mealAllowance()` + `downloadMealAllowancePdf()`
  - `transportAllowance()` + `downloadTransportAllowancePdf()`
  - `performanceAllowance()` + `downloadPerformanceAllowancePdf()`
  - `salaryReport()` + `downloadSalaryPdf()`
  - Monitoring `salaryReport()`

### Technical Details
- Salary model structure: One salary record per employee per month/year
- Without month/year filter, `latest('created_at')` returns most recently created salary (could be wrong month)
- New employees need salary configured for each month separately
- Historical data preserved: Old months retain their salary settings

## [1.2.7] - 2026-09-03

### Fixed - HOTFIX
#### Report Filters - Critical Fix for Employee Status Logic
- **CRITICAL HOTFIX**: Reverted incorrect filter logic from v1.2.6
- **Issue**: Version 1.2.6 incorrectly used `whereNull('deleted_at')` instead of `where('is_active', true)`
- **Root Cause**: System uses `is_active` column (boolean) to mark active/inactive employees, NOT soft deletes
- **Impact**: In production, inactive employees were showing and new active employees were hidden
- **Fix**: Restored correct filter `where('is_active', true)` across all report methods
- **Database Facts**:
  - Total employees: 36
  - Active (is_active=1): 33 employees
  - Inactive (is_active=0): 3 employees (Budi Santoso, Guswinayu Pangestuti, Dewi Kusuma)
  - Soft deleted employees: 0 (system doesn't use soft delete for employee status)
- **Files Modified**:
  - `app/Http/Controllers/Admin/ReportController.php` (11 locations)
  - `app/Http/Controllers/Monitoring/ReportController.php` (2 locations)

## [1.2.6] - 2026-09-03 [REVERTED]

### Fixed
#### Report Filters - Active Employee Status (INCORRECT - REVERTED IN 1.2.7)
- **Fixed**: Bug kritis dimana pegawai non-aktif masih muncul dan pegawai aktif baru tidak muncul di laporan
- **NOTE**: This version had incorrect logic and was immediately fixed in v1.2.7
- **Impact**: Semua laporan (web & PDF) sekarang hanya menampilkan pegawai dengan status aktif (not soft deleted)
- **Files Modified**:
  - `app/Http/Controllers/Admin/ReportController.php` (12 methods)
  - `app/Http/Controllers/Monitoring/ReportController.php` (2 methods)
- **Changes**:
  - Replaced `where('is_active', true)` with `whereNull('deleted_at')`
  - Simplified complex `deleted_at` logic to consistent active-only filter
  - Applied filter to both web display and PDF download methods
- **Affected Pages**:
  - `/admin/reports/salary` (display + PDF)
  - `/admin/reports/meal-allowance` (display + PDF)
  - `/admin/reports/transport-allowance` (display + PDF)
  - `/admin/reports/performance-allowance` (display + PDF)
  - `/monitoring/reports/salary` (display + PDF)
  - `/monitoring/reports/meal-allowance` (display + PDF)
  - `/monitoring/reports/transport-allowance` (display + PDF)
  - `/monitoring/reports/performance-allowance` (display + PDF)
- **Data Integrity**:
  - Historical data for inactive employees remains stored in database
  - Inactive employee attendance/salary records are preserved
  - Employees can be reactivated and their data will be visible again
  - Soft delete ensures no data loss

### Technical Details
#### Soft Delete Logic
- Employee model uses `SoftDeletes` trait
- Inactive employees: `deleted_at IS NOT NULL`
- Active employees: `deleted_at IS NULL`
- All related data (attendance, salary, permissions) remain intact regardless of employee status
- Reactivating employee (setting `deleted_at = NULL`) restores visibility in all reports

## [1.1.2] - 2025-11-27

### Fixed
#### Status Kehadiran - Status Calculation Bug
- **Fixed**: Bug kritis pada method `calculateTimeDifference()` yang menyebabkan status kehadiran terbalik
- **Impact**: Status "Terlambat", "Tepat Waktu", dan "Pulang Awal" sekarang ditampilkan dengan akurat
- **Files Modified**:
  - `app/Http/Controllers/Admin/ReportController.php` (line 2069)
  - `app/Http/Controllers/Employee/ReportController.php` (line 434)
- **Details**: Perhitungan selisih waktu diubah dari `$actual->diffInSeconds($scheduled)` menjadi `$scheduled->diffInSeconds($actual)` untuk tanda yang benar
- **Affected Pages**:
  - `/admin/reports/employees/{employee}/salary/{year}/{month}`
  - `/employee/salary-report/{year}/{month}`
  - PDF reports untuk semua laporan gaji

#### Employee Attendance - Location Display Settings
- **Fixed**: Bagian "Lokasi Anda" di `/employee/attendance` sekarang menghormati setting dari `/admin/location-settings`
- **Impact**: Koordinat dan informasi lokasi hanya ditampilkan jika "Tampilkan dengan Titik Koordinat" aktif
- **Files Modified**:
  - `resources/views/employee/attendance.blade.php` (line 44-82, 138-167)
- **Features**:
  - Seluruh box "Lokasi Anda" disembunyikan saat koordinat dinonaktifkan
  - Popup peta hanya menampilkan koordinat jika setting aktif
  - Leaflet CSS/JS hanya dimuat jika peta aktif

### Added
#### Salary Settings - Month/Year Filtering
- **New Feature**: Filter bulan dan tahun di `/admin/employees/salary-settings`
- **Impact**: Pengaturan gaji sekarang dapat diatur per bulan tanpa mempengaruhi data historis
- **Files Added**:
  - `database/migrations/2025_11_27_084807_add_month_year_to_salaries_table.php`
- **Files Modified**:
  - `app/Models/Salary.php` - Menambahkan kolom `month` dan `year` ke fillable
  - `app/Http/Controllers/Admin/EmployeeController.php` - Update logic salary settings
  - `resources/views/admin/employees/salary-settings.blade.php` - UI filter
  - `app/Http/Controllers/Admin/ReportController.php` - Filter salary by month/year
  - `app/Http/Controllers/Employee/ReportController.php` - Filter salary by month/year
- **Database Changes**:
  - Added `month` (tinyint) column to `salaries` table
  - Added `year` (smallint) column to `salaries` table
  - Added unique constraint: `salaries_employee_month_year_unique`

#### Employee Shifts - Auto-Copy from Previous Month
- **New Feature**: Shift bulan sebelumnya otomatis di-copy ke bulan berikutnya
- **Impact**: Admin tidak perlu setup shift manual setiap bulan, namun tetap bisa diubah
- **Files Modified**:
  - `app/Http/Controllers/Admin/EmployeeShiftController.php` - Method `autoCopyShiftsFromPreviousMonth()`
- **Logic**:
  - Auto-copy dilakukan saat membuka bulan baru tanpa shift
  - Menghormati hari aktif dari `/admin/holidays/active-days`
  - Mengecualikan hari libur yang tercatat
  - Mapping tanggal yang sama (contoh: tanggal 15 bulan lalu → tanggal 15 bulan ini)

### Changed
#### View Improvements - Salary Settings
- **UI Enhancement**: Error message Carbon di salary settings diperbaiki
- **Files Modified**:
  - `resources/views/admin/employees/salary-settings.blade.php`
  - `app/Http/Controllers/Admin/EmployeeController.php`
- **Details**: Type casting untuk `month` dan `year` parameters agar konsisten

## [1.1.1] - 2025-11-26

### Added
#### Attendance Corrections - New Module
- **New Feature**: Fitur koreksi absensi untuk pegawai dan approval oleh admin
- **Files Added**:
  - `app/Models/AttendanceCorrection.php`
  - `app/Http/Controllers/Admin/AttendanceCorrectionController.php`
  - `app/Http/Controllers/Employee/AttendanceCorrectionController.php`
  - `database/migrations/2025_11_21_101429_create_attendance_corrections_table.php`
  - `database/migrations/2025_11_21_105005_add_supporting_document_to_attendance_corrections_table.php`
  - `resources/views/admin/attendance_corrections/`
  - `resources/views/employee/attendance_corrections/`

#### System Monitoring
- **New Feature**: Monitoring performa sistem dan resource usage
- **Files Added**:
  - `app/Http/Controllers/Admin/SystemMonitorController.php`
  - `resources/views/admin/monitoring/`

#### Salary Management
- **New Feature**: Sistem pengelolaan gaji pegawai
- **Files Added**:
  - `app/Models/Salary.php`
  - `database/migrations/2025_11_21_130903_create_salaries_table.php`

### Enhanced
#### Permission System
- **Enhancement**: Menambahkan tipe permission dan approval workflow
- **Files Modified**:
  - `app/Models/Permission.php`
  - `database/migrations/2025_11_21_091914_add_type_to_permissions_table.php`
  - `database/migrations/2025_11_25_124656_add_approved_by_to_permissions_table.php`

#### Employee & Student Data
- **Enhancement**: Menambahkan NIP untuk pegawai dan NISN untuk siswa
- **Files Added**:
  - `database/migrations/2025_11_25_094744_add_nip_to_employees_table.php`
  - `database/migrations/2025_11_25_094751_add_nisn_to_students_table.php`

## [1.1.0] - 2025-11-20

### Initial Release Features
- User authentication system (Admin, Employee, Student)
- Attendance management with geolocation
- Shift scheduling system
- Permission/leave management
- Reporting system
- Student attendance tracking
- Grade (Rombel) management
- Holiday & active days configuration
- Security logging
- Database backup & restore
