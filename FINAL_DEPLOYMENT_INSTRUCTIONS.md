# INSTRUKSI FINAL - PISAH FORM ABSENSI PEGAWAI & SISWA

## MASALAH YANG DIPERBAIKI
- ❌ **Error**: `syntax error, unexpected token "<"` di production
- ❌ **Penyebab**: Blade directive (`@foreach`, `{{ }}`) di dalam JavaScript template literal
- ✅ **Solusi**: Pisahkan form menjadi 2 (pegawai & siswa) tanpa dynamic dropdown

---

## FILE YANG SUDAH DIBUAT DI LOCAL

✅ `app/Http/Controllers/Admin/AttendanceController.php` - Sudah terupdate
✅ `resources/views/admin/attendances/create-employee.blade.php` - Form pegawai
✅ `resources/views/admin/attendances/create-student.blade.php` - Form siswa

---

## LANGKAH 1: UPDATE ROUTES

**File**: `routes/web.php`

**Cari bagian ini** (sekitar line 188-195):
```php
// Attendances
Route::prefix('attendances')->name('attendances.')->group(function () {
    Route::get('/create', [App\Http\Controllers\Admin\AttendanceController::class, 'create'])->name('create');
    Route::get('/{id}/edit', [App\Http\Controllers\Admin\AttendanceController::class, 'edit'])->name('edit');
    Route::put('/{id}', [App\Http\Controllers\Admin\AttendanceController::class, 'update'])->name('update');
    Route::post('/', [App\Http\Controllers\Admin\AttendanceController::class, 'store'])->name('store');
    Route::delete('/{id}', [App\Http\Controllers\Admin\AttendanceController::class, 'destroy'])->name('destroy');
});
```

**GANTI MENJADI**:
```php
// Attendances
Route::prefix('attendances')->name('attendances.')->group(function () {
    Route::get('/create', [App\Http\Controllers\Admin\AttendanceController::class, 'create'])->name('create');
    Route::get('/create-employee', [App\Http\Controllers\Admin\AttendanceController::class, 'createEmployee'])->name('create-employee');
    Route::get('/create-student', [App\Http\Controllers\Admin\AttendanceController::class, 'createStudent'])->name('create-student');
    Route::get('/{id}/edit', [App\Http\Controllers\Admin\AttendanceController::class, 'edit'])->name('edit');
    Route::put('/{id}', [App\Http\Controllers\Admin\AttendanceController::class, 'update'])->name('update');
    Route::post('/', [App\Http\Controllers\Admin\AttendanceController::class, 'store'])->name('store');
    Route::delete('/{id}', [App\Http\Controllers\Admin\AttendanceController::class, 'destroy'])->name('destroy');
});
```

---

## LANGKAH 2: UPDATE TOMBOL DI LAPORAN PEGAWAI

**File**: `resources/views/admin/reports/index.blade.php`

**Cari tombol "Tambah Absensi"** (sekitar line yang ada `route('admin.attendances.create')`):
```php
<a href="{{ route('admin.attendances.create') }}" ...>
    Tambah Absensi
</a>
```

**GANTI MENJADI**:
```php
<a href="{{ route('admin.attendances.create-employee') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
    + Tambah Absensi Pegawai
</a>
```

---

## LANGKAH 3: UPDATE TOMBOL DI LAPORAN SISWA

**File**: `resources/views/admin/reports/student-reports.blade.php`

**Cari tombol "Tambah Absensi"** (sekitar line yang ada `route('admin.attendances.create')`):
```php
<a href="{{ route('admin.attendances.create') }}" ...>
    Tambah Absensi
</a>
```

**GANTI MENJADI**:
```php
<a href="{{ route('admin.attendances.create-student') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
    + Tambah Absensi Siswa
</a>
```

---

## LANGKAH 4: UPLOAD FILE KE PRODUCTION

Upload file berikut ke production:

1. ✅ `app/Http/Controllers/Admin/AttendanceController.php`
2. ✅ `resources/views/admin/attendances/create-employee.blade.php`
3. ✅ `resources/views/admin/attendances/create-student.blade.php`
4. ✅ `routes/web.php` (setelah di-edit manual)
5. ✅ `resources/views/admin/reports/index.blade.php` (setelah di-edit manual)
6. ✅ `resources/views/admin/reports/student-reports.blade.php` (setelah di-edit manual)

---

## LANGKAH 5: CLEAR CACHE DI PRODUCTION

SSH ke production dan jalankan:

```bash
cd /home/mtsnkot4/siab.mtsn2kotamalang.sch.id

# Clear all caches
php artisan view:clear
php artisan route:clear
php artisan cache:clear
php artisan config:clear
php artisan optimize:clear

# Hapus compiled view yang error
rm -f storage/framework/views/43ba63614f9b53a3f20f9927b82fa153.php
rm -f storage/framework/views/*.php

# Optional: Restart PHP if you have opcache
# sudo systemctl restart php8.1-fpm
```

---

## LANGKAH 6: TEST

1. **Test Form Pegawai**:
   - Buka: `/admin/reports` (Laporan Pegawai)
   - Klik: "Tambah Absensi Pegawai"
   - Seharusnya muncul form tanpa error
   - Isi form dan submit

2. **Test Form Siswa**:
   - Buka: `/admin/reports/students` (Laporan Siswa)
   - Klik: "Tambah Absensi Siswa"
   - Seharusnya muncul form tanpa error
   - Isi form dan submit

---

## STRUKTUR ROUTE BARU

- `/admin/attendances/create` → Redirect ke `/admin/attendances/create-employee`
- `/admin/attendances/create-employee` → Form khusus pegawai
- `/admin/attendances/create-student` → Form khusus siswa

---

## JIKA MASIH ERROR

1. Cek file di production sudah terupload dengan benar
2. Pastikan tidak ada karakter aneh (BOM, line ending issues)
3. Cek permission file (chmod 644)
4. Clear browser cache (Ctrl+Shift+R)
5. Cek Laravel logs: `tail -f storage/logs/laravel.log`

---

## PERUBAHAN YANG DILAKUKAN

### Controller (AttendanceController.php)
- ➕ Method `createEmployee()` - Load form pegawai
- ➕ Method `createStudent()` - Load form siswa
- ↔️ Method `create()` - Redirect ke `createEmployee()`
- ✅ Method `store()` - Tetap sama (handle kedua tipe)

### Views
- ➕ `create-employee.blade.php` - Form khusus pegawai (TANPA Blade di JavaScript)
- ➕ `create-student.blade.php` - Form khusus siswa (TANPA Blade di JavaScript)
- ❌ `create.blade.php` - Tidak digunakan lagi (bisa dihapus)

### Routes
- ➕ `admin.attendances.create-employee`
- ➕ `admin.attendances.create-student`
- ✅ `admin.attendances.create` - Redirect

---

**Selesai!** 🎉

Setelah semua langkah di atas, form tambah absensi seharusnya berfungsi tanpa error di production.
