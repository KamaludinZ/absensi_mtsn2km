# Changelog

Semua perubahan penting pada proyek ini akan didokumentasikan dalam file ini.

Format ini didasarkan pada [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
dan proyek ini mengikuti [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
