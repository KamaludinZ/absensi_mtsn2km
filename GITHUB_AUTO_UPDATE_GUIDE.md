# Panduan GitHub Auto-Update

Sistem update otomatis dari GitHub repository untuk SIAB MTsN 2 Kota Malang.

---

## ✨ Fitur

- ✅ **Auto-detect** update terbaru dari GitHub releases
- ✅ **Download otomatis** dari GitHub repository
- ✅ **Backup otomatis** sebelum update
- ✅ **Rollback** jika update gagal
- ✅ **Changelog** dari GitHub release notes
- ✅ **Fallback** manual ZIP upload jika GitHub tidak tersedia

---

## 🔧 Konfigurasi

### 1. Generate GitHub Personal Access Token

1. Login ke GitHub: https://github.com
2. Buka **Settings** → **Developer settings** → **Personal access tokens** → **Tokens (classic)**
3. Klik **Generate new token** → **Generate new token (classic)**
4. Berikan nama: `SIAB Auto-Update Token`
5. Pilih scope/permissions:
   - ✅ `repo` (Full control of private repositories)
   - ✅ `read:packages` (Download packages from GitHub Package Registry)
6. Klik **Generate token**
7. **COPY TOKEN** dan simpan (tidak bisa dilihat lagi!)

### 2. Konfigurasi di .env

Tambahkan ke file `.env`:

```env
# GitHub Auto-Update Configuration
GITHUB_TOKEN=ghp_YourPersonalAccessTokenHere
GITHUB_REPO_OWNER=KamaludinZ
GITHUB_REPO_NAME=absensi_mtsn2km
```

**PENTING**:
- Jangan commit file `.env` ke Git!
- Token sudah ada di `.env` Anda saat ini
- Pastikan token memiliki akses ke repository

### 3. Verifikasi Konfigurasi

Setelah setup, buka menu **Admin → Update Aplikasi**.

Jika berhasil, Anda akan melihat:
- ✅ Badge "Terhubung" berwarna hijau
- ✅ Info repository: KamaludinZ/absensi_mtsn2km
- ✅ Button "Periksa Update"

---

## 📖 Cara Menggunakan

### Cek Update Otomatis

1. Login sebagai **Admin**
2. Buka menu **Update Aplikasi**
3. Sistem otomatis cek update saat halaman dibuka
4. Atau klik button **"Periksa Update"** untuk manual check

### Install Update dari GitHub

Jika ada update tersedia:

1. Akan muncul notifikasi **"Update Tersedia!"** (kuning)
2. Lihat detail:
   - Versi terbaru
   - Release notes/changelog
   - Tanggal rilis
3. Klik button **"Download & Install Update"**
4. Konfirmasi update
5. Tunggu proses selesai (2-5 menit)
6. Verifikasi versi baru di dashboard

### Proses Update Otomatis

Sistem akan:
1. ✅ **Backup** aplikasi saat ini (otomatis)
2. ✅ **Download** ZIP dari GitHub release
3. ✅ **Extract** dan validasi struktur
4. ✅ **Apply** update files ke aplikasi
5. ✅ **Run** database migrations
6. ✅ **Clear** cache (config, route, view)
7. ✅ **Cleanup** temporary files

### Rollback Jika Diperlukan

Jika update bermasalah:
1. Scroll ke bagian **"Daftar Backup"**
2. Pilih backup yang ingin dikembalikan
3. Klik **"Rollback"**
4. Konfirmasi
5. Aplikasi kembali ke versi sebelumnya

---

## 🚀 Untuk Developer: Membuat Release di GitHub

### 1. Persiapan Kode

```bash
# Update code dan test
git add .
git commit -m "Feature: Add new reporting module"

# Update VERSION file
echo "v1.1.0" > VERSION
git add VERSION
git commit -m "Bump version to v1.1.0"
```

### 2. Build Production Assets

```bash
# Install dependencies
composer install --no-dev --optimize-autoloader
npm install

# Build untuk production
npm run build

# Test build
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 3. Create Git Tag

```bash
# Create tag
git tag v1.1.0

# Push dengan tags
git push origin main --tags
```

### 4. Buat GitHub Release

**Via Web UI (Recommended):**

1. Buka repository di GitHub
2. Klik **Releases** → **Draft a new release**
3. Pilih tag: `v1.1.0`
4. Release title: `v1.1.0 - New Reporting Module`
5. Describe this release:
   ```markdown
   ## What's New
   - ✨ Added advanced reporting module
   - 🐛 Fixed timezone issue in attendance
   - ⚡ Improved performance by 50%

   ## Breaking Changes
   None

   ## Installation
   Automatic update via Admin panel.
   ```
6. **PENTING**: Upload file ZIP (opsional, tapi recommended):
   - Buat ZIP dengan script: `./create-update-package.sh`
   - Attach file: `update-v1.1.0.zip`
7. Klik **Publish release**

**Via GitHub CLI:**

```bash
# Install gh CLI: https://cli.github.com/

# Create release dengan file ZIP
gh release create v1.1.0 \
  --title "v1.1.0 - New Reporting Module" \
  --notes "$(cat CHANGELOG.md)" \
  update-v1.1.0.zip
```

### 5. Test Auto-Update

1. Login ke admin panel production
2. Buka **Update Aplikasi**
3. Klik **"Periksa Update"**
4. Verifikasi muncul notifikasi update
5. Test download & install
6. Verifikasi aplikasi berfungsi normal
7. Test rollback jika perlu

---

## 📝 Format Release Notes

Gunakan format Markdown untuk changelog:

```markdown
## v1.1.0 - 2025-10-24

### Added
- ✨ Advanced reporting with custom filters
- ✨ Export to Excel format
- ✨ Real-time dashboard

### Changed
- ⚡ Improved attendance performance (50% faster)
- 🎨 Updated schedule page UI
- 🔧 Optimized database queries

### Fixed
- 🐛 Fixed duplicate attendance bug
- 🐛 Fixed photo upload >2MB error
- 🐛 Fixed timezone issue in reports

### Security
- 🔒 Added rate limiting for API
- 🔒 Improved selfie upload validation
- 🔒 Updated Laravel to 11.x
```

---

## 🔍 Troubleshooting

### Error: "GitHub API error: 401"

**Penyebab**: Token invalid atau expired

**Solusi**:
1. Generate token baru di GitHub
2. Update `.env`:
   ```env
   GITHUB_TOKEN=ghp_NewTokenHere
   ```
3. Clear config cache:
   ```bash
   php artisan config:clear
   ```

### Error: "Release tidak ditemukan"

**Penyebab**: Tag/release belum dibuat di GitHub

**Solusi**:
1. Pastikan tag sudah push: `git push --tags`
2. Buat release di GitHub dengan tag tersebut
3. Pastikan release status **Published** (bukan Draft)

### Error: "Gagal download dari GitHub"

**Penyebab**:
- Network issue
- File ZIP tidak ada di release
- Token tidak punya akses

**Solusi**:
1. Cek koneksi internet
2. Pastikan release punya file ZIP
3. Atau biarkan sistem download zipball otomatis
4. Periksa token punya akses ke repo (scope `repo`)

### Error: "Invalid Laravel structure"

**Penyebab**: Package ZIP tidak punya struktur folder Laravel

**Solusi**:
1. Pastikan ZIP dibuat dengan script `create-update-package.sh`
2. Atau upload manual ZIP yang benar
3. ZIP harus punya folder: app, public, resources, routes

### Token Sudah Ada tapi Tidak Terdeteksi

**Solusi**:
```bash
# Clear config cache
php artisan config:clear

# Verify .env loaded
php artisan tinker
>>> config('services.github.token')
>>> config('services.github.owner')
>>> config('services.github.repo')
```

---

## 🔐 Keamanan

### Proteksi Token

- ✅ Token disimpan di `.env` (tidak di-commit ke Git)
- ✅ Token di-mask di UI (hanya show 10 char pertama)
- ✅ Token hanya accessible oleh Admin role
- ✅ HTTPS required untuk GitHub API

### Permissions yang Dibutuhkan

Token minimal butuh:
- `repo` (untuk private repository)
- `read:packages` (untuk download releases)

Token **TIDAK** perlu:
- `write` permissions (read-only)
- `delete` permissions
- `admin` permissions

### Best Practices

1. **Generate token khusus** untuk auto-update (bukan personal token umum)
2. **Set expiration** pada token (e.g., 1 tahun)
3. **Rotate token** secara berkala
4. **Revoke token** jika tidak digunakan
5. **Monitor usage** di GitHub settings

---

## 📊 Monitoring

### Log Update

Semua update dicatat di:
- `storage/logs/laravel.log` - Detail technical logs
- `storage/logs/update_history.log` - Update summary
- Admin UI - Riwayat update (20 terakhir)

### GitHub API Rate Limit

- **Authenticated**: 5,000 requests/hour
- **Unauthenticated**: 60 requests/hour

Check rate limit:
```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
  https://api.github.com/rate_limit
```

---

## 🆚 GitHub Auto-Update vs Manual Upload

| Feature | GitHub Auto-Update | Manual Upload |
|---------|-------------------|---------------|
| **Setup** | Perlu token & config | Tidak perlu setup |
| **Speed** | Cepat (direct download) | Perlu upload file |
| **Changelog** | Otomatis dari release notes | Tidak ada |
| **Version Check** | Otomatis detect update | Manual check |
| **Backup** | Otomatis | Otomatis |
| **Rollback** | Ya | Ya |
| **Best For** | Production (regular updates) | Emergency/offline |

---

## 📚 Resources

- **GitHub Personal Access Tokens**: https://github.com/settings/tokens
- **GitHub Releases API**: https://docs.github.com/en/rest/releases
- **GitHub CLI**: https://cli.github.com/
- **Semantic Versioning**: https://semver.org/

---

## 🤝 Support

Jika mengalami masalah:
- 📧 Email: dev@mtsn2kotamalang.sch.id
- 📱 WhatsApp: [Support Number]
- 🐛 GitHub Issues: https://github.com/KamaludinZ/absensi_mtsn2km/issues

---

**Last Updated**: 2025-10-24
**Version**: 1.0
