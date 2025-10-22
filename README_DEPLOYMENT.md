# 🚀 Quick Deployment Guide - Absensi MTsN 2

## 📦 Files Created

1. **DEPLOYMENT.md** - Panduan lengkap deployment (detailed)
2. **DEPLOYMENT_CHECKLIST.md** - Checklist step-by-step (quick reference)
3. **deploy-helper.php** - Helper script untuk deployment via browser

---

## ⚡ Quick Start (5 Menit)

### 1. Persiapan di Local (2 menit)

```bash
# Build assets
npm install
npm run build

# Optimize Laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Buat ZIP (exclude node_modules, .git)
# Gunakan File Explorer atau command line
```

### 2. Setup di cPanel (2 menit)

1. **Database**: Create database + user via cPanel
2. **Upload**: Upload ZIP ke `/home/username/absensi_app`
3. **Extract**: Extract file
4. **Public**: Copy isi folder `public` ke `public_html`

### 3. Konfigurasi (1 menit)

1. **Edit .env** di `absensi_app/`:
   ```env
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://yourdomain.com
   DB_CONNECTION=pgsql
   DB_HOST=127.0.0.1
   DB_DATABASE=your_db_name
   DB_USERNAME=your_db_user
   DB_PASSWORD=your_db_pass
   ```

2. **Edit index.php** di `public_html/`:
   ```php
   require __DIR__.'/../absensi_app/vendor/autoload.php';
   $app = require_once __DIR__.'/../absensi_app/bootstrap/app.php';
   ```

3. **Upload deploy-helper.php** ke `public_html/`

4. **Akses** `https://yourdomain.com/deploy-helper.php?password=YOUR_PASSWORD`
   - Run: Check System
   - Run: Create Symlink
   - Run: Migration
   - Run: Optimize

5. **DELETE** `deploy-helper.php`

---

## 📁 Struktur Folder Final

```
/home/username/
├── absensi_app/              # Laravel application
│   ├── app/
│   ├── bootstrap/
│   ├── config/
│   ├── database/
│   ├── resources/
│   ├── routes/
│   ├── storage/
│   │   ├── app/
│   │   │   └── public/       # Uploaded files disini
│   │   ├── framework/
│   │   └── logs/
│   ├── vendor/
│   ├── .env                  # Environment config
│   └── artisan
│
└── public_html/              # Web root (public)
    ├── build/                # Compiled assets (CSS/JS)
    ├── storage -> ../absensi_app/storage/app/public  # Symlink
    ├── .htaccess
    ├── favicon.ico
    ├── index.php             # Entry point
    └── robots.txt
```

---

## 🔐 Default Credentials

Setelah deployment, buat user admin via database atau seeder:

```sql
-- Admin default (jika menggunakan seeder)
-- Email/Username: Lihat di database seeder
-- Password: Sesuai seeder atau yang dibuat manual
```

---

## ✅ Verification Checklist

Setelah deploy, pastikan:

- [ ] Homepage bisa diakses
- [ ] CSS/JS loaded (tidak ada 404)
- [ ] Login page berfungsi
- [ ] Bisa login dengan credentials yang benar
- [ ] Upload foto berfungsi
- [ ] Database CRUD berfungsi
- [ ] Laporan PDF bisa di-generate
- [ ] Tidak ada error di `storage/logs/laravel.log`

---

## 🐛 Troubleshooting Cepat

### 500 Error
```bash
1. Check storage/logs/laravel.log
2. Set chmod 775 storage/ -R
3. Set chmod 775 bootstrap/cache/ -R
4. Verify .env configuration
```

### Assets 404
```bash
1. Verify public/build/ folder exists
2. Check .htaccess in public_html
3. Clear browser cache
```

### Database Error
```bash
1. Check DB credentials in .env
2. Verify DB_HOST (localhost atau 127.0.0.1)
3. Test connection via phpPgAdmin/phpMyAdmin
```

### Upload Error
```bash
1. Check storage symlink: ls -la public_html/storage
2. Set chmod 775 storage/app/public -R
3. Verify PHP upload_max_filesize
```

---

## 📞 Support Files

- **Full Guide**: `DEPLOYMENT.md`
- **Checklist**: `DEPLOYMENT_CHECKLIST.md`
- **Helper Script**: `deploy-helper.php` (DELETE after use!)

---

## ⚠️ Security Reminders

1. ✅ Set `APP_DEBUG=false`
2. ✅ Set `APP_ENV=production`
3. ✅ Delete `deploy-helper.php`
4. ✅ Set proper file permissions
5. ✅ Use strong database password
6. ✅ Enable HTTPS
7. ✅ Keep `.env` secure (chmod 600)

---

## 🎯 Production Ready!

Jika semua checklist ✅, aplikasi siap digunakan!

**Next Steps**:
1. Setup regular backups
2. Monitor logs
3. Train users
4. Document any custom configurations

---

**Questions?** Refer to `DEPLOYMENT.md` for detailed explanations.
