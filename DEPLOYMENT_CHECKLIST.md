# 📋 Deployment Checklist - Absensi MTsN 2

## Pre-Deployment (Di Local)

### Build & Optimize
```bash
- [ ] npm install
- [ ] npm run build
- [ ] php artisan config:cache
- [ ] php artisan route:cache
- [ ] php artisan view:cache
- [ ] Verify public/build folder terisi
```

### Environment Setup
```bash
- [ ] Copy .env.example ke .env
- [ ] Set APP_ENV=production
- [ ] Set APP_DEBUG=false
- [ ] Set APP_URL ke domain production
- [ ] Update database credentials
- [ ] Verify APP_KEY exists
```

### File Preparation
```bash
- [ ] Remove node_modules
- [ ] Remove .git
- [ ] Remove tests folder (optional)
- [ ] Create ZIP/TAR file
```

---

## Server Setup (cPanel)

### Database
```bash
- [ ] Create PostgreSQL/MySQL database
- [ ] Create database user
- [ ] Grant ALL privileges to user
- [ ] Note: Host, DB Name, Username, Password
```

### File Upload
```bash
- [ ] Upload ZIP file ke cPanel
- [ ] Extract di /home/username/absensi_app
- [ ] Copy isi folder public ke public_html
- [ ] Verify semua folder ter-upload
```

### File Structure
```
/home/username/
├── absensi_app/        ← Laravel app (outside public)
│   ├── app/
│   ├── bootstrap/
│   ├── config/
│   ├── storage/
│   ├── vendor/
│   └── .env
└── public_html/        ← Public folder
    ├── build/
    ├── .htaccess
    └── index.php
```

---

## Configuration

### 1. Update index.php
```bash
- [ ] Edit public_html/index.php
- [ ] Point paths to ../absensi_app/
- [ ] Verify bootstrap path correct
```

### 2. Setup .htaccess
```bash
- [ ] Create/verify .htaccess di public_html
- [ ] Add rewrite rules
- [ ] Add security headers
- [ ] Set PHP limits
```

### 3. Set Permissions
```bash
- [ ] chmod 775 storage/ -R
- [ ] chmod 775 bootstrap/cache/ -R
- [ ] chmod 600 .env
```

### 4. Create Storage Symlink
```bash
- [ ] Via SSH: ln -s ../absensi_app/storage/app/public storage
- [ ] Via PHP script (create_symlink.php)
- [ ] Verify symlink created
```

### 5. PHP Configuration
```bash
- [ ] Set upload_max_filesize = 20M
- [ ] Set post_max_size = 20M
- [ ] Set max_execution_time = 300
- [ ] Set memory_limit = 512M
```

---

## Database Migration

### Via SSH (Recommended)
```bash
- [ ] cd /home/username/absensi_app
- [ ] php artisan migrate --force
- [ ] php artisan db:seed --force (if needed)
```

### Via PHP Script (No SSH)
```bash
- [ ] Create migrate.php in public_html
- [ ] Run https://domain.com/migrate.php
- [ ] DELETE migrate.php immediately!
```

---

## Security Check

```bash
- [ ] APP_DEBUG=false
- [ ] APP_ENV=production
- [ ] .env not publicly accessible
- [ ] Remove debug files
- [ ] Remove migration scripts
- [ ] Force HTTPS enabled
- [ ] Check file permissions
```

---

## Testing

### Basic Tests
```bash
- [ ] Homepage loads
- [ ] CSS/JS loads correctly
- [ ] No console errors
```

### Authentication
```bash
- [ ] Admin login works
- [ ] Pegawai login works
- [ ] Monitoring login works
- [ ] Logout works
```

### Core Features
```bash
- [ ] Absensi pegawai works
- [ ] Absensi siswa works
- [ ] Upload selfie works
- [ ] Perizinan works
- [ ] Laporan bisa di-generate
- [ ] Export PDF works
- [ ] Export Excel works
```

### Performance
```bash
- [ ] Page load < 3 seconds
- [ ] Images load properly
- [ ] Database queries optimized
```

---

## Post-Deployment

### Initial Setup
```bash
- [ ] Login as admin
- [ ] Setup jadwal (schedules)
- [ ] Import pegawai (if needed)
- [ ] Import siswa & rombel (if needed)
- [ ] Set tahun ajaran aktif
- [ ] Test all features
```

### Monitoring
```bash
- [ ] Setup error monitoring
- [ ] Check storage/logs/laravel.log
- [ ] Monitor server resources
- [ ] Setup backup cron job
```

### Documentation
```bash
- [ ] Document admin credentials
- [ ] Document database info
- [ ] Document server details
- [ ] Share access to stakeholders
```

---

## Common Issues & Quick Fixes

### 500 Error
```bash
→ Check storage/logs/laravel.log
→ Verify .env configuration
→ Check permissions (755/775)
→ Run php artisan config:clear
```

### Assets Not Loading
```bash
→ Verify public/build folder exists
→ Check .htaccess rules
→ Clear browser cache
→ Check symlink storage
```

### Database Error
```bash
→ Verify DB credentials in .env
→ Check DB host (localhost vs 127.0.0.1)
→ Verify user privileges
→ Test connection via phpMyAdmin/phpPgAdmin
```

### Upload Failed
```bash
→ Check storage permissions
→ Verify PHP upload limits
→ Check disk space
→ Verify symlink created
```

---

## Backup Strategy

### Before Deployment
```bash
- [ ] Backup local database
- [ ] Backup local files
- [ ] Tag current version in git
```

### Regular Backups
```bash
- [ ] Setup daily DB backup (cPanel/cron)
- [ ] Setup weekly file backup
- [ ] Test restore procedure
- [ ] Store backups off-server
```

---

## Emergency Rollback Plan

```bash
1. [ ] Keep backup of old version
2. [ ] Document rollback steps
3. [ ] Have database backup ready
4. [ ] Know how to restore from backup
```

---

## Contact & Support

**Developer**: [Your Name]
**Deployed**: [Date]
**Laravel Version**: 11.x
**PHP Version**: 8.2+
**Database**: PostgreSQL/MySQL

---

✅ **Checklist Complete**: Ready for production!
