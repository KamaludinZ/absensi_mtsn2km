# 📸 Selfie Upload System - Technical Documentation

## Arsitektur Sistem

Sistem upload dan storage selfie dirancang untuk **production tanpa symlink**, dengan penyimpanan langsung di folder `storage/selfies/`.

---

## 🗂️ Struktur Folder

```
/siab.mtsn2kotamalang.sch.id/
│
├── public/                          ← Document Root (accessible via web)
│   ├── index.php
│   └── .htaccess
│
├── storage/                         ← Storage folder (NOT accessible via web)
│   ├── .htaccess                    ← Block direct access
│   ├── selfies/                     ← Foto absensi
│   │   ├── employee/                ← Foto pegawai
│   │   │   └── employee_123_in_1234567890.jpg
│   │   └── student/                 ← Foto siswa
│   │       └── student_456_in_1234567890.jpg
│   ├── app/
│   ├── framework/
│   └── logs/
│
└── app/
    └── Http/
        └── Controllers/
            ├── SelfieController.php         ← Serve selfie with security
            ├── Employee/
            │   └── AttendanceController.php ← Upload employee selfie
            └── Student/
                └── AttendanceController.php ← Upload student selfie
```

---

## 🔄 Alur Kerja Upload Foto

### 1. **Capture Foto (Browser)**
```javascript
// Di frontend (employee/student attendance page)
navigator.mediaDevices.getUserMedia({ video: true })
  → Capture from camera
  → Convert to base64 string
  → Send via AJAX POST
```

### 2. **Upload Process (Backend)**

**Controller:** `Employee/AttendanceController.php` atau `Student/AttendanceController.php`

**Method:** `processSelfie($base64Image, $type, $id, $direction)`

**Flow:**
```php
1. Validate base64 image format
   ✓ Check regex pattern
   ✓ Verify base64 valid

2. Decode and validate image
   ✓ Decode base64
   ✓ Create image instance (Intervention Image)
   ✓ Check dimensions (max 5000x5000)

3. Determine storage path
   IF APP_ENV === 'production'
       → Path: base_path('storage/selfies/{type}/{filename}')
       → Example: /siab.mtsn2kotamalang.sch.id/storage/selfies/employee/employee_1_in_123.jpg
   ELSE (development)
       → Path: storage_path('app/public/selfies/{type}/{filename}')

4. Create directory if not exists
   mkdir(path, 0755, recursive)

5. Compress and save image
   ✓ Resize to max width 800px
   ✓ Compress quality 85 → 30 (adaptive)
   ✓ Target max filesize: 500KB
   ✓ Save as JPEG

6. Return relative path
   → "selfies/employee/employee_1_in_123.jpg"
   → Saved to database (selfie_path_in or selfie_path_out)
```

**Filename Format:**
```
{type}_{id}_{direction}_{timestamp}.jpg

Contoh:
- employee_123_in_1698765432.jpg
- student_456_out_1698765432.jpg
```

### 3. **Save to Database**

```php
Attendance::create([
    'attendable_type' => Employee::class,  // or Student::class
    'attendable_id' => $employee->id,
    'date' => Carbon::today(),
    'clock_in' => Carbon::now(),
    'selfie_path_in' => 'selfies/employee/employee_123_in_1698765432.jpg',  ← Relative path
    // ... other fields
]);
```

---

## 🖼️ Alur Kerja Display Foto

### 1. **Request Foto dari View**

**View:** `monitoring/employees.blade.php`, `employee/report.blade.php`, dll.

```blade
@php
    // Extract type and filename from database path
    $pathParts = explode('/', $attendance->selfie_path_in);
    $type = $pathParts[1] ?? 'employee';        // employee or student
    $filename = $pathParts[2] ?? '';            // employee_123_in_1698765432.jpg

    // Generate route URL
    $imageUrl = route('selfie.show', [
        'type' => $type,
        'filename' => $filename
    ]);
@endphp

<img src="{{ $imageUrl }}" alt="Selfie">
```

**Generated URL:**
```
https://siab.mtsn2kotamalang.sch.id/selfies/employee/employee_123_in_1698765432.jpg
```

### 2. **Route Handler**

**File:** `routes/web.php`

```php
Route::get('/selfies/{type}/{filename}', [SelfieController::class, 'show'])
    ->middleware('auth')                    // Must be logged in
    ->where('type', 'employee|student')     // Only employee or student
    ->where('filename', '[a-zA-Z0-9_\-\.]+') // Security: filename pattern
    ->name('selfie.show');
```

### 3. **Security Check & Serve File**

**Controller:** `SelfieController.php`

**Method:** `show(Request $request, $type, $filename)`

```php
1. Validate input
   ✓ $type in ['employee', 'student']
   ✓ Sanitize filename: basename($filename)

2. Determine file path
   IF APP_ENV === 'production'
       → $filePath = base_path("storage/selfies/{$type}/{$filename}")
   ELSE
       → $filePath = storage_path("app/public/selfies/{$type}/{$filename}")

3. Check file exists
   IF !file_exists($filePath)
       → abort(404, 'File not found')

4. Security check: canViewSelfie($type, $filename)
   IF user is admin/monitoring
       → ALLOW (can view all)
   ELSE IF user is employee AND $type === 'employee'
       → Check if filename belongs to this employee
       → Parse filename: employee_{ID}_in/out_timestamp.jpg
       → IF ID matches employee ID → ALLOW
       → ELSE check in attendance records → ALLOW if found
   ELSE
       → DENY

5. Serve file
   ✓ Read file content
   ✓ Detect MIME type
   ✓ Return response with headers:
     - Content-Type: image/jpeg
     - Content-Disposition: inline
     - Cache-Control: private, max-age=3600
```

---

## 🔒 Security Features

### 1. **No Direct Access to Storage**

File `storage/.htaccess`:
```apache
# Block all direct access
Require all denied
```

Jika user coba akses:
```
https://siab.mtsn2kotamalang.sch.id/storage/selfies/employee/photo.jpg
```
→ **403 Forbidden**

### 2. **Authentication Required**

Semua request ke `/selfies/{type}/{filename}` harus login dulu:
```php
->middleware('auth')
```

### 3. **Authorization Check**

`canViewSelfie()` method di `SelfieController`:
- ✅ Admin/Monitoring → can view ALL selfies
- ✅ Employee → can view ONLY their own selfies
- ❌ Student → cannot view (handled by admin/monitoring)
- ❌ Guest → cannot view (must login)

### 4. **Input Validation**

```php
// Sanitize filename
$filename = basename($filename);  // Remove directory traversal

// Regex validation on route
->where('filename', '[a-zA-Z0-9_\-\.]+')

// Type validation
->where('type', 'employee|student')
```

### 5. **Path Traversal Prevention**

```php
// Remove dangerous patterns
$path = str_replace(['../', './', '..\\', '.\\'], '', $path);

// Verify final path is within allowed directory
if (strpos(realpath(dirname($path)) ?: dirname($path), $allowedPath) !== 0) {
    throw new \InvalidArgumentException('Invalid file path');
}
```

---

## 📊 Database Schema

**Table:** `attendances`

Relevant columns:
```sql
- selfie_path_in:  VARCHAR(255)   -- Path: "selfies/employee/employee_123_in_123.jpg"
- selfie_path_out: VARCHAR(255)   -- Path: "selfies/employee/employee_123_out_456.jpg"
```

**Path format:** `selfies/{type}/{filename}.jpg`

**TIDAK menyimpan:**
- ❌ Absolute path (`/home/user/...`)
- ❌ Full URL (`https://...`)
- ❌ Base64 data

---

## 🎯 Environment Detection

Sistem otomatis detect environment:

```php
$isProduction = config('app.env') === 'production';

if ($isProduction) {
    // Use: base_path('storage/selfies/...')
    // Path: /home/username/siab.mtsn2kotamalang.sch.id/storage/selfies/
} else {
    // Use: storage_path('app/public/selfies/...')
    // Path: /project/storage/app/public/selfies/
}
```

**Set di `.env`:**
```env
# Production
APP_ENV=production

# Development
APP_ENV=local
```

---

## 🖼️ Image Processing

### Compression Settings

```php
// Step 1: Resize
$img->scaleDown(width: 800);  // Max width 800px, maintain aspect ratio

// Step 2: Adaptive compression
$quality = 85;                 // Start with quality 85
$maxFileSize = 500 * 1024;     // Target: 500KB

while ($quality >= 30) {
    $img->toJpeg(quality: $quality)->save($path);
    $fileSize = filesize($path);

    if ($fileSize <= $maxFileSize) {
        break;  // Success!
    }

    $quality -= 5;  // Reduce quality
}

// Step 3: If still too large, resize more
if ($fileSize > $maxFileSize) {
    $img->scaleDown(width: 600);
    // Try again with quality 75 → 20
}
```

**Result:**
- Max width: 800px (or 600px if needed)
- Format: JPEG
- Quality: 20-85 (adaptive)
- File size: ≤ 500KB

---

## 🧪 Testing

### 1. Test Upload (Manual)

```bash
# Check folder exists
ls -la storage/selfies/employee/

# Upload via application
# Then check:
ls -la storage/selfies/employee/
# Should see new file: employee_123_in_1234567890.jpg

# Check filesize
du -h storage/selfies/employee/employee_123_in_1234567890.jpg
# Should be ≤ 500KB
```

### 2. Test Security

```bash
# Try direct access (should fail)
curl https://siab.mtsn2kotamalang.sch.id/storage/selfies/employee/test.jpg
# Expected: 403 Forbidden

# Try via route without login (should redirect to login)
curl https://siab.mtsn2kotamalang.sch.id/selfies/employee/test.jpg
# Expected: 302 Redirect to /login

# Try via route with login (should work)
curl -H "Cookie: laravel_session=..." \
     https://siab.mtsn2kotamalang.sch.id/selfies/employee/test.jpg
# Expected: 200 OK + image data
```

### 3. Test Permissions

```bash
# Check folder is writable
touch storage/selfies/employee/.test && rm storage/selfies/employee/.test
# Should succeed

# Check permissions
ls -ld storage/selfies/
# Should show: drwxr-xr-x (755)
```

---

## 🔧 Configuration Files

### `config/filesystems.php`

**NOT USED** untuk selfie! We bypass Laravel's filesystem and write directly.

### `routes/web.php`

```php
Route::get('/selfies/{type}/{filename}', [SelfieController::class, 'show'])
    ->middleware('auth')
    ->where('type', 'employee|student')
    ->where('filename', '[a-zA-Z0-9_\-\.]+')
    ->name('selfie.show');
```

### `storage/.htaccess`

```apache
Require all denied
```

---

## 🐛 Common Issues

### Issue: "Permission denied" saat upload

**Fix:**
```bash
chmod -R 755 storage
chown -R www-data:www-data storage  # or your webserver user
```

### Issue: Foto tidak muncul (404)

**Check:**
1. File exists? `ls storage/selfies/employee/`
2. Path di database benar? `SELECT selfie_path_in FROM attendances LIMIT 1;`
3. APP_ENV correct? `grep APP_ENV .env`

### Issue: 403 Forbidden saat akses via route

**Check:**
1. Sudah login?
2. User punya permission? (Check `canViewSelfie()` logic)
3. Check logs: `tail -f storage/logs/laravel.log`

---

## 📝 Maintenance

### Clean Old Photos

```bash
# Find photos older than 365 days
find storage/selfies/ -type f -mtime +365

# Delete (BE CAREFUL!)
find storage/selfies/ -type f -mtime +365 -delete
```

### Monitor Storage Usage

```bash
# Total size
du -sh storage/selfies/

# Count files
find storage/selfies/ -type f | wc -l

# Largest files
find storage/selfies/ -type f -exec du -h {} \; | sort -rh | head -20
```

---

## 🎓 Key Points

1. ✅ **No Symlink** - Files stored in `storage/selfies/`, served via controller
2. ✅ **Security** - Authentication + Authorization + Input validation
3. ✅ **Environment-aware** - Auto-detect production/development
4. ✅ **Compressed** - Max 500KB per image
5. ✅ **Protected** - `.htaccess` blocks direct access
6. ✅ **Scalable** - Can handle thousands of photos

---

**Happy Coding! 🚀**
