# Quick Setup Guide
## MTsN 2 Kota Malang Attendance System

### Prerequisites
- PHP 8.2 or higher
- PostgreSQL 13 or higher
- Composer installed
- Laravel requirements met

### Step-by-Step Setup

#### 1. Environment Configuration
The `.env` file should already be configured with PostgreSQL. Verify these settings:
```
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=your_database_name
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

#### 2. Install Dependencies (if not already done)
```bash
composer install
```

#### 3. Generate Application Key (if not already done)
```bash
php artisan key:generate
```

#### 4. Run Migrations
This will create all database tables:
```bash
php artisan migrate
```

#### 5. Seed the Database
This will create roles, admin user, sample data, and default settings:
```bash
php artisan db:seed
```

#### 6. Create Storage Link
This is required for storing and serving selfie images:
```bash
php artisan storage:link
```

#### 7. Publish Backup Configuration
This publishes the Spatie backup configuration:
```bash
php artisan vendor:publish --provider="Spatie\Backup\BackupServiceProvider"
```

#### 8. Create Required Directories
Ensure these directories exist with proper permissions:
```bash
mkdir -p storage/app/public/selfies/employee
mkdir -p storage/app/public/selfies/student
chmod -R 775 storage bootstrap/cache
```

#### 9. Start the Development Server
```bash
php artisan serve
```

The application will be available at `http://127.0.0.1:8000`

### Default Login Credentials

#### Admin Account
- **URL:** http://127.0.0.1:8000/login
- **Username:** admin
- **Password:** password

#### Role Access:
- **Admin:** Full system access
- **Pegawai:** After creating an employee with user account
- **Monitoring:** After creating a monitoring user

### Testing the Application

#### 1. Test Public Student Attendance
1. Go to http://127.0.0.1:8000
2. Click "Mulai Absensi" on Student card

4. Allow camera access when prompted
5. Take selfie and submit

#### 2. Test Admin Functions
1. Login with admin credentials
2. Navigate to "Pegawai" to add employees
3. Navigate to "Siswa" to add students
4. Check "Rombel" and "Tahun Pelajaran" for class management
5. Test report generation under "Laporan"

#### 3. Test Employee Functions
1. Create an employee with user account from admin panel
2. Logout and login with employee credentials
3. Navigate to "Absensi" to test clock in/out
4. Submit permission requests under "Perizinan"

#### 4. Test Monitoring Functions
1. Create a monitoring user from admin panel
2. Login with monitoring credentials
3. View live attendance under "Monitor Pegawai" and "Monitor Siswa"
4. Approve/reject permissions under "Perizinan"

### Common Issues & Solutions

#### Issue: Camera not working
- **Solution:** Ensure you're using HTTPS or localhost (camera API requires secure context)
- Check browser permissions for camera access

#### Issue: Storage link not working
- **Solution:**
  ```bash
  rm public/storage  # Remove if exists
  php artisan storage:link
  ```

#### Issue: Permission denied on storage
- **Solution:**
  ```bash
  chmod -R 775 storage bootstrap/cache
  chown -R www-data:www-data storage bootstrap/cache  # On Linux
  ```

#### Issue: PostgreSQL connection failed
- **Solution:** Check if PostgreSQL is running and credentials are correct in `.env`

#### Issue: Class not found errors
- **Solution:**
  ```bash
  composer dump-autoload
  php artisan clear-compiled
  php artisan config:clear
  ```

### Production Deployment Notes

For production deployment on local server:

1. **Set environment to production:**
   ```
   APP_ENV=production
   APP_DEBUG=false
   ```

2. **Optimize Laravel:**
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   composer install --optimize-autoloader --no-dev
   ```

3. **Configure Web Server (Nginx example):**
   ```nginx
   server {
       listen 80;
       server_name your-domain.local;
       root /path/to/absensi_mtsn2km/public;

       add_header X-Frame-Options "SAMEORIGIN";
       add_header X-Content-Type-Options "nosniff";

       index index.php;

       location / {
           try_files $uri $uri/ /index.php?$query_string;
       }

       location ~ \.php$ {
           fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
           fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
           include fastcgi_params;
       }

       location ~ /\.(?!well-known).* {
           deny all;
       }
   }
   ```

4. **Set up scheduled backup (optional):**
   Add to crontab:
   ```bash
   0 2 * * * cd /path/to/absensi_mtsn2km && php artisan backup:run --only-db
   ```

5. **Set proper permissions:**
   ```bash
   chown -R www-data:www-data /path/to/absensi_mtsn2km
   chmod -R 755 /path/to/absensi_mtsn2km
   chmod -R 775 /path/to/absensi_mtsn2km/storage
   chmod -R 775 /path/to/absensi_mtsn2km/bootstrap/cache
   ```

### Support & Customization

The application is built following Laravel best practices and is fully customizable:

- **Models:** `app/Models/`
- **Controllers:** `app/Http/Controllers/`
- **Views:** `resources/views/`
- **Routes:** `routes/web.php`
- **Migrations:** `database/migrations/`

For detailed implementation information, see `IMPLEMENTATION_SUMMARY.md`

### Features Ready to Use

✅ Role-based access control (Admin, Pegawai, Monitoring)
✅ Student attendance
✅ Selfie-based employee attendance
✅ Photo compression and local storage
✅ Permission management system
✅ Excel import/export
✅ PDF report generation
✅ Database backup functionality
✅ Active/inactive status tracking
✅ Real-time attendance monitoring
✅ Responsive UI with Tailwind CSS

### Getting Help

If you encounter any issues:
1. Check Laravel logs in `storage/logs/laravel.log`
2. Verify database connection and migrations
3. Ensure all dependencies are installed
4. Check file permissions on storage directories
5. Review `IMPLEMENTATION_SUMMARY.md` for architecture details

---

**System is ready for use!** Login as admin and start configuring your school's data.
