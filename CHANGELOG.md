# Changelog

All notable changes to SIAB MTsN 2 Kota Malang will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [v1.0.0] - 2025-10-24

### Added
- ✨ Sistem absensi pegawai dengan selfie verification
- ✨ Sistem absensi siswa dengan selfie verification
- ✨ Manajemen shift pegawai (Shift A, B, C, Libur)
- ✨ Manajemen jadwal siswa per hari
- ✨ Dashboard monitoring real-time untuk monitoring role
- ✨ Dashboard admin dengan statistik lengkap
- ✨ Laporan absensi pegawai dengan filter tanggal
- ✨ Laporan absensi siswa per rombel
- ✨ Export laporan ke PDF
- ✨ Sistem izin/sakit untuk pegawai dan siswa
- ✨ Approval izin oleh monitoring/admin
- ✨ Manajemen hari libur dan hari kerja
- ✨ Import data pegawai, siswa, rombel via Excel
- ✨ Backup dan restore database
- ✨ Update aplikasi via ZIP upload (untuk shared hosting)
- ✨ Security logging dan monitoring login attempts
- ✨ Rate limiting untuk API dan login

### Security
- 🔒 Authentication dengan session-based login
- 🔒 Role-based authorization (Admin, Monitoring, Pegawai)
- 🔒 Selfie photos stored in protected storage folder
- 🔒 Route-based photo serving with authentication
- 🔒 .htaccess protection untuk storage folder
- 🔒 CSRF protection untuk semua forms
- 🔒 Password hashing dengan bcrypt
- 🔒 Rate limiting untuk login (5 attempts per minute)
- 🔒 Security headers middleware
- 🔒 Input validation dan sanitization

### Technical
- 🛠️ Laravel 11.x framework
- 🛠️ PostgreSQL database
- 🛠️ Intervention Image untuk image processing
- 🛠️ Alpine.js untuk reactive components
- 🛠️ Tailwind CSS untuk styling
- 🛠️ DomPDF untuk PDF generation
- 🛠️ Maatwebsite Excel untuk import/export
- 🛠️ Camera API integration untuk selfie capture
- 🛠️ Adaptive image compression (max 500KB per photo)
- 🛠️ Environment-aware storage paths (production/development)

### Performance
- ⚡ Database indexing untuk query optimization
- ⚡ Caching untuk grades, rombels, students
- ⚡ Query optimization dengan eager loading
- ⚡ Image compression untuk reduce storage
- ⚡ Route caching untuk faster routing
- ⚡ Config caching untuk faster startup

### Infrastructure
- 🌐 Shared hosting compatibility (cPanel)
- 🌐 No symlink required untuk production
- 🌐 ZIP-based update system
- 🌐 Automatic backup before updates
- 🌐 Rollback capability untuk failed updates
- 🌐 Production-ready .htaccess configuration

## [v1.0.3] - 2025-10-25

### Security
- 🔒 Fixed security vulnerability: Direct URL access to selfie photos now requires authentication
- 🔒 All /selfies/* routes now go through Laravel authentication system
- 🔒 Enhanced access control for employee/student photos with role-based permissions

### Added
- ➕ Route untuk secure access ke selfie photos dengan middleware authentication

### Fixed
- 🐛 Bug: Akses langsung ke URL foto selfie tanpa login sekarang di-block oleh sistem

---

## Upcoming Features (Planned)

### v1.1.0 (Next Release)
- [ ] Export laporan ke Excel format
- [ ] Dashboard analytics dengan charts
- [ ] Notifikasi email untuk approval izin
- [ ] Multi-language support (ID/EN)
- [ ] Dark mode theme
- [ ] Mobile app version (PWA)

### v1.2.0 (Future)
- [ ] Integration dengan fingerprint device
- [ ] Face recognition untuk absensi
- [ ] Geolocation verification
- [ ] Advanced reporting dengan custom filters
- [ ] API documentation dengan Swagger
- [ ] Real-time notifications dengan WebSocket

---

## Version History

- **v1.0.0** (2025-10-24) - Initial release with core features
  - Deployment: /siab.mtsn2kotamalang.sch.id
  - Environment: Production (Shared Hosting)
  - Database: PostgreSQL

---

## Upgrade Guide

### Dari Development ke Production v1.0.0

1. Setup production environment:
   ```bash
   cp .env.example .env
   # Edit .env dengan config production
   ```

2. Install dependencies:
   ```bash
   composer install --no-dev --optimize-autoloader
   npm install && npm run build
   ```

3. Run migrations:
   ```bash
   php artisan migrate --force
   ```

4. Setup storage folders:
   ```bash
   mkdir -p storage/selfies/employee
   mkdir -p storage/selfies/student
   chmod -R 755 storage
   ```

5. Clear & cache config:
   ```bash
   php artisan optimize:clear
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

---

## Contributors

- **Development Team**: MTsN 2 Kota Malang IT Team
- **Project Manager**: [Nama PM]
- **Lead Developer**: [Nama Lead Dev]
- **UI/UX Designer**: [Nama Designer]
- **Database Administrator**: [Nama DBA]

---

## License

Proprietary - MTsN 2 Kota Malang
© 2025 All rights reserved.

---

## Support

- 📧 Email: dev@mtsn2kotamalang.sch.id
- 📱 WhatsApp: [Support Number]
- 🐛 Bug Reports: [Issue Tracker URL]
- 📚 Documentation: /docs/

---

**Last Updated**: 2025-10-24
