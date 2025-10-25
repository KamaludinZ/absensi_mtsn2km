# How to Create Update Package

Panduan untuk developer dalam membuat package update aplikasi SIAB.

---

## Struktur Update Package

Package update adalah file ZIP dengan struktur folder Laravel standar:

```
update-v1.1.0.zip
├── app/                      # Semua file di folder app
│   ├── Console/
│   ├── Http/
│   │   ├── Controllers/
│   │   ├── Middleware/
│   │   └── ...
│   ├── Models/
│   └── ...
├── bootstrap/                # Bootstrap files
│   ├── app.php
│   ├── cache/
│   └── providers.php
├── config/                   # Config files
│   ├── app.php
│   ├── database.php
│   └── ...
├── database/                 # Migrations & seeders
│   ├── migrations/
│   ├── seeders/
│   └── ...
├── public/                   # Public assets
│   ├── build/
│   ├── index.php
│   └── ...
├── resources/                # Views & frontend assets
│   ├── css/
│   ├── js/
│   └── views/
├── routes/                   # Route definitions
│   ├── web.php
│   ├── api.php
│   └── console.php
├── artisan                   # Artisan CLI
├── composer.json             # Dependencies
├── composer.lock             # Locked versions
└── VERSION                   # Version number (v1.1.0)
```

---

## Step-by-Step: Membuat Update Package

### 1. Development & Testing

```bash
# Clone repository atau update local
git clone https://github.com/your-org/siab.git
cd siab

# Install dependencies
composer install
npm install && npm run build

# Test semua fitur
php artisan test

# Update VERSION file
echo "v1.1.0" > VERSION
```

### 2. Buat Migrations (Jika Ada Perubahan Database)

```bash
# Buat migration baru
php artisan make:migration add_new_field_to_employees_table

# Edit migration file di database/migrations/
# Test migration
php artisan migrate

# Rollback & test lagi
php artisan migrate:rollback
php artisan migrate
```

### 3. Update Nomor Versi

Edit file `VERSION`:
```
v1.1.0
```

Format versi: `v{MAJOR}.{MINOR}.{PATCH}`
- MAJOR: Breaking changes
- MINOR: New features (backward compatible)
- PATCH: Bug fixes

### 4. Commit Changes

```bash
git add .
git commit -m "Release v1.1.0: Add new employee reporting feature"
git tag v1.1.0
git push origin main --tags
```

### 5. Build Production Assets

```bash
# Build untuk production
npm run build

# Verify build output
ls -la public/build/
```

### 6. Buat ZIP Package

**Opsi A: Manual dengan Zip Tool**

```bash
# Create zip dengan folder yang diperlukan
zip -r update-v1.1.0.zip \
  app/ \
  bootstrap/ \
  config/ \
  database/ \
  public/ \
  resources/ \
  routes/ \
  artisan \
  composer.json \
  composer.lock \
  VERSION \
  -x "*.git*" \
  -x "*node_modules*" \
  -x "*vendor*" \
  -x "*.env*" \
  -x "*storage/*"
```

**Opsi B: Menggunakan Script**

Buat file `create-update-package.sh`:

```bash
#!/bin/bash

# Read version from VERSION file
VERSION=$(cat VERSION)
FILENAME="update-${VERSION}.zip"

echo "Creating update package: $FILENAME"

# Create temporary directory
TEMP_DIR="temp-update-package"
rm -rf $TEMP_DIR
mkdir -p $TEMP_DIR

# Copy required folders
cp -r app $TEMP_DIR/
cp -r bootstrap $TEMP_DIR/
cp -r config $TEMP_DIR/
cp -r database $TEMP_DIR/
cp -r public $TEMP_DIR/
cp -r resources $TEMP_DIR/
cp -r routes $TEMP_DIR/

# Copy required files
cp artisan $TEMP_DIR/
cp composer.json $TEMP_DIR/
cp composer.lock $TEMP_DIR/
cp VERSION $TEMP_DIR/

# Create ZIP from temp directory
cd $TEMP_DIR
zip -r ../$FILENAME .
cd ..

# Cleanup
rm -rf $TEMP_DIR

# Show result
ls -lh $FILENAME
echo "✅ Package created: $FILENAME"
```

Jalankan:
```bash
chmod +x create-update-package.sh
./create-update-package.sh
```

**Opsi C: Windows (PowerShell)**

```powershell
# Read version
$version = Get-Content VERSION
$filename = "update-$version.zip"

Write-Host "Creating update package: $filename"

# Create temp folder
$tempDir = "temp-update-package"
Remove-Item -Path $tempDir -Recurse -Force -ErrorAction SilentlyContinue
New-Item -ItemType Directory -Path $tempDir

# Copy folders
Copy-Item -Path "app" -Destination "$tempDir/app" -Recurse
Copy-Item -Path "bootstrap" -Destination "$tempDir/bootstrap" -Recurse
Copy-Item -Path "config" -Destination "$tempDir/config" -Recurse
Copy-Item -Path "database" -Destination "$tempDir/database" -Recurse
Copy-Item -Path "public" -Destination "$tempDir/public" -Recurse
Copy-Item -Path "resources" -Destination "$tempDir/resources" -Recurse
Copy-Item -Path "routes" -Destination "$tempDir/routes" -Recurse

# Copy files
Copy-Item -Path "artisan" -Destination "$tempDir/"
Copy-Item -Path "composer.json" -Destination "$tempDir/"
Copy-Item -Path "composer.lock" -Destination "$tempDir/"
Copy-Item -Path "VERSION" -Destination "$tempDir/"

# Create ZIP
Compress-Archive -Path "$tempDir/*" -DestinationPath $filename -Force

# Cleanup
Remove-Item -Path $tempDir -Recurse -Force

# Show result
Get-Item $filename | Format-Table Name, Length
Write-Host "✅ Package created: $filename"
```

### 7. Verify Package

Ekstrak dan verifikasi:

```bash
# Extract untuk verify
unzip update-v1.1.0.zip -d verify-package/

# Check struktur
tree verify-package/

# Verify folder wajib ada
ls -la verify-package/app
ls -la verify-package/public
ls -la verify-package/resources
ls -la verify-package/routes

# Check ukuran
du -sh update-v1.1.0.zip

# Cleanup
rm -rf verify-package/
```

### 8. Test Package di Development

1. Buat backup environment development
2. Upload package via admin panel
3. Verify update berhasil
4. Test semua fitur
5. Rollback dan test rollback

### 9. Release Package

Upload ke:
- GitHub Releases
- Google Drive (untuk tim internal)
- Server file hosting

---

## Checklist Sebelum Release

- [ ] Semua fitur baru sudah tested
- [ ] Database migrations sudah tested (up & down)
- [ ] File VERSION sudah diupdate
- [ ] Production assets sudah di-build (`npm run build`)
- [ ] CHANGELOG.md sudah diupdate
- [ ] Git commit & tag sudah dibuat
- [ ] Package ZIP sudah dibuat
- [ ] Package sudah di-verify (extract & check)
- [ ] Package sudah di-test di development
- [ ] Dokumentasi update sudah disiapkan
- [ ] Ukuran package < 100MB

---

## Changelog Format

Buat file `CHANGELOG.md`:

```markdown
# Changelog

## [v1.1.0] - 2025-10-24

### Added
- Fitur laporan pegawai dengan filter advanced
- Export laporan ke Excel format baru
- Dashboard monitoring real-time

### Changed
- Improve performance absensi siswa (50% faster)
- Update UI halaman jadwal
- Optimize database queries

### Fixed
- Fix bug absensi ganda di hari yang sama
- Fix error upload foto > 2MB
- Fix timezone issue di laporan

### Security
- Add rate limiting untuk API attendance
- Improve validation selfie upload
- Update dependencies (Laravel 11.x)

## [v1.0.0] - 2025-10-01

### Initial Release
- Sistem absensi pegawai & siswa
- Manajemen jadwal shift
- Laporan absensi
- Backup & restore
```

---

## Yang TIDAK Boleh Dimasukkan ke Package

**JANGAN** masukkan:
- ❌ `.env` file (setiap environment beda)
- ❌ `vendor/` folder (akan di-generate ulang)
- ❌ `node_modules/` folder (development only)
- ❌ `storage/` folder (berisi data & logs production)
- ❌ `.git/` folder (version control)
- ❌ File backup atau temporary
- ❌ File config khusus development (`.env.example` ok)

**WAJIB** masukkan:
- ✅ `app/` - Kode aplikasi
- ✅ `bootstrap/` - Bootstrap Laravel
- ✅ `config/` - Konfigurasi
- ✅ `database/migrations/` - Schema updates
- ✅ `public/build/` - Production assets
- ✅ `resources/views/` - Blade templates
- ✅ `routes/` - Routes
- ✅ `artisan` - CLI tool
- ✅ `composer.json` & `composer.lock` - Dependencies
- ✅ `VERSION` - Version number

---

## Troubleshooting Package Creation

### Package Terlalu Besar (> 100MB)

Solusi:
1. Pastikan `vendor/` dan `node_modules/` tidak masuk
2. Optimize images di `public/` (compress)
3. Remove old compiled assets di `public/build/`
4. Remove test files yang tidak perlu

### Missing Dependencies

Jika ada error "Class not found" setelah update:

```bash
# Login ke cPanel Terminal atau SSH
cd /siab.mtsn2kotamalang.sch.id
composer install --no-dev --optimize-autoloader
php artisan optimize:clear
php artisan config:cache
```

### Migration Failed

Jika migration error saat update:

1. Check `storage/logs/laravel.log`
2. Run manual migration:
   ```bash
   php artisan migrate --force
   ```
3. Jika masih error, check database structure
4. Rollback jika perlu

---

## Advanced: Automated Package Creation

Buat GitHub Action untuk otomatis build package saat tag release:

`.github/workflows/release.yml`:

```yaml
name: Create Update Package

on:
  push:
    tags:
      - 'v*'

jobs:
  build:
    runs-on: ubuntu-latest

    steps:
      - uses: actions/checkout@v3

      - name: Setup Node.js
        uses: actions/setup-node@v3
        with:
          node-version: '18'

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'

      - name: Install Dependencies
        run: |
          composer install --no-dev --optimize-autoloader
          npm install

      - name: Build Assets
        run: npm run build

      - name: Create Package
        run: |
          VERSION=$(cat VERSION)
          zip -r "update-${VERSION}.zip" \
            app bootstrap config database public resources routes \
            artisan composer.json composer.lock VERSION \
            -x "*.git*" -x "*node_modules*" -x "*vendor*"

      - name: Upload Release Asset
        uses: actions/upload-release-asset@v1
        env:
          GITHUB_TOKEN: ${{ secrets.GITHUB_TOKEN }}
        with:
          upload_url: ${{ github.event.release.upload_url }}
          asset_path: ./update-${{ github.ref_name }}.zip
          asset_name: update-${{ github.ref_name }}.zip
          asset_content_type: application/zip
```

---

## Security Best Practices

1. **Never commit sensitive data**
   - `.env` → add to `.gitignore`
   - API keys → use environment variables
   - Database credentials → use `.env`

2. **Verify dependencies**
   ```bash
   composer audit
   npm audit
   ```

3. **Use semantic versioning**
   - v1.0.0 → v1.0.1 (patch: bug fix)
   - v1.0.0 → v1.1.0 (minor: new features)
   - v1.0.0 → v2.0.0 (major: breaking changes)

4. **Test migrations thoroughly**
   - Test `migrate` (up)
   - Test `migrate:rollback` (down)
   - Test on copy of production database

---

## Contact

Jika ada pertanyaan tentang package creation:
- 📧 dev@mtsn2kotamalang.sch.id
- 📚 Documentation: /docs/

---

**Last Updated**: 2025-10-24
