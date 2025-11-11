# Setup GitHub Update Feature

Fitur update otomatis memungkinkan aplikasi untuk mengunduh dan menginstall update terbaru dari GitHub Releases.

## Persyaratan

1. Akun GitHub dengan akses ke repository `KamaludinZ/absensi_mtsn2km`
2. GitHub Personal Access Token dengan scope `repo`
3. Koneksi internet yang stabil

## Cara Mendapatkan GitHub Personal Access Token

### Langkah 1: Login ke GitHub
1. Buka https://github.com dan login dengan akun Anda
2. Pastikan Anda memiliki akses ke repository yang akan diupdate

### Langkah 2: Generate Personal Access Token
1. Klik foto profil di kanan atas → **Settings**
2. Scroll ke bawah, klik **Developer settings** di menu kiri
3. Klik **Personal access tokens** → **Tokens (classic)**
4. Klik tombol **Generate new token** → **Generate new token (classic)**

### Langkah 3: Konfigurasi Token
1. **Note**: Beri nama token (contoh: "Absensi MTsN 2 Update Token")
2. **Expiration**: Pilih masa berlaku token (disarankan: 90 days atau No expiration)
3. **Select scopes**: Centang **repo** (Full control of private repositories)
   - Ini akan otomatis mencentang semua sub-scope di bawah repo
4. Scroll ke bawah dan klik **Generate token**

### Langkah 4: Copy Token
1. Token akan ditampilkan HANYA SEKALI
2. Copy token yang dihasilkan (format: `ghp_xxxxxxxxxxxxx`)
3. **PENTING**: Simpan token ini dengan aman, Anda tidak akan bisa melihatnya lagi

## Konfigurasi di Aplikasi

### 1. Edit File .env
Buka file `.env` di root directory aplikasi dan tambahkan/update konfigurasi berikut:

```env
# GitHub Update Configuration
GITHUB_TOKEN=ghp_your_token_here
GITHUB_REPO_OWNER=KamaludinZ
GITHUB_REPO_NAME=absensi_mtsn2km
```

Ganti `ghp_your_token_here` dengan token yang Anda dapatkan dari GitHub.

### 2. Clear Configuration Cache
Jalankan command berikut untuk memastikan konfigurasi baru terbaca:

```bash
php artisan config:clear
php artisan config:cache
```

### 3. Verifikasi Konfigurasi
1. Login ke aplikasi sebagai Admin
2. Buka menu **Update Aplikasi**
3. Klik tombol **Cek Update**
4. Jika berhasil, Anda akan melihat status update terbaru

## Cara Menggunakan Fitur Update

### Cek Update
1. Buka halaman **Admin** → **Update Aplikasi**
2. Klik tombol **Cek Update**
3. Sistem akan mengecek apakah ada versi baru di GitHub Releases
4. Jika ada update, detail versi baru akan ditampilkan

### Jalankan Update
1. Setelah memastikan ada update tersedia
2. Klik tombol **Jalankan Update**
3. Konfirmasi update
4. **PENTING**: Jangan tutup browser selama proses update

### Proses Update
Saat Anda menjalankan update, sistem akan:
1. Membuat backup database otomatis
2. Mengunduh file update dari GitHub Releases
3. Mengekstrak dan copy file baru (kecuali .env, storage, vendor)
4. Menjalankan database migration jika ada
5. Membersihkan cache aplikasi
6. Update file VERSION

## Troubleshooting

### Error: "GitHub token belum dikonfigurasi"
**Solusi**: Pastikan `GITHUB_TOKEN` sudah diisi di file `.env`

### Error: "Token GitHub tidak valid atau sudah expired"
**Solusi**:
1. Token mungkin sudah expired, generate token baru
2. Token tidak memiliki permission yang tepat, pastikan scope `repo` tercentang

### Error: "Repository tidak ditemukan"
**Solusi**: Periksa `GITHUB_REPO_OWNER` dan `GITHUB_REPO_NAME` di `.env`

### Error: "Akses ditolak" atau "rate limit"
**Solusi**:
1. GitHub API memiliki rate limit (60 request/jam tanpa auth, 5000 request/jam dengan auth)
2. Tunggu beberapa saat sebelum mencoba lagi

### Update Gagal Ditengah Proses
**Solusi**:
1. Cek log aplikasi di `storage/logs/laravel.log`
2. Restore database dari backup di menu **Backup & Restore**
3. Hubungi developer untuk bantuan

## Keamanan

1. **Jangan commit file .env ke repository**
2. **Jangan share token GitHub ke orang lain**
3. **Rotate token secara berkala** (generate token baru setiap beberapa bulan)
4. **Gunakan expiration date** pada token untuk keamanan lebih baik
5. **Revoke token** yang sudah tidak digunakan di GitHub Settings

## Catatan Penting

1. Update hanya akan mengambil file dari **GitHub Releases**, bukan dari commit biasa
2. Developer harus membuat Release di GitHub agar update tersedia
3. File yang tidak akan ditimpa saat update:
   - `.env` (konfigurasi environment)
   - `storage/` (file upload, logs, dll)
   - `vendor/` (dependencies)
   - `node_modules/`
   - `.git/`
4. Backup database otomatis dibuat sebelum update
5. Pastikan permission folder sudah benar setelah update

## Support

Jika mengalami masalah:
1. Cek dokumentasi ini kembali
2. Lihat error message dan coba solusi di bagian Troubleshooting
3. Hubungi developer: KamaludinZ (https://github.com/KamaludinZ)
