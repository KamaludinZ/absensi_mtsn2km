# Panduan Update Aplikasi SIAB MTsN 2 Kota Malang

## Ringkasan

Aplikasi ini menggunakan sistem update berbasis **ZIP upload** yang dirancang khusus untuk **shared hosting** tanpa akses Git atau SSH.

---

## Cara Melakukan Update

### 1. Persiapan Package Update

Buat file ZIP dengan struktur folder Laravel lengkap yang berisi:

```
update-package.zip
├── app/
├── bootstrap/
├── config/
├── database/
│   └── migrations/
├── public/
├── resources/
├── routes/
├── artisan
├── composer.json
└── composer.lock
```

**PENTING**:
- File `.env` **TIDAK** akan di-replace saat update (untuk menjaga konfigurasi database)
- Folder `vendor/` **TIDAK** perlu dimasukkan (akan di-generate otomatis dengan `composer install`)
- Folder `storage/` **TIDAK** perlu dimasukkan (untuk menjaga data dan logs)

### 2. Upload Update via Admin Panel

1. Login sebagai **admin**
2. Buka menu **Update Aplikasi**
3. Klik tombol **"Pilih File ZIP"** atau drag-drop file ZIP
4. Klik **"Upload & Update"**

### 3. Proses Update Otomatis

Sistem akan otomatis melakukan:

1. ✅ **Backup lengkap** aplikasi saat ini (ke `storage/app/backups/`)
2. ✅ **Extract** file ZIP update
3. ✅ **Validasi** struktur folder Laravel
4. ✅ **Apply** file-file baru (replace folder & file)
5. ✅ **Run** database migrations
6. ✅ **Clear** semua cache (config, route, view, optimize)
7. ✅ **Cleanup** file temporary

### 4. Verifikasi Update

Setelah update selesai:
- Cek versi terbaru di halaman Update
- Cek log update history
- Test fitur-fitur aplikasi

---

## Rollback (Kembalikan ke Versi Sebelumnya)

Jika terjadi masalah setelah update:

1. Buka menu **Update Aplikasi**
2. Lihat daftar **Backup** di bagian bawah halaman
3. Pilih backup yang ingin dikembalikan
4. Klik tombol **"Rollback"**
5. Konfirmasi rollback

**PENTING**: Rollback akan mengembalikan **semua file aplikasi** ke kondisi sebelum update. Data di database **TIDAK** akan terpengaruh.

---

## Keamanan Update

### Backup Otomatis

- Setiap update **otomatis** membuat backup lengkap
- Backup disimpan di `storage/app/backups/backup_{timestamp}.zip`
- Backup mencakup: app, bootstrap, config, database, public, resources, routes, .env

### Validasi Package

Sistem akan memvalidasi package update:
- ✅ Format file harus ZIP
- ✅ Ukuran maksimal 100MB
- ✅ Harus ada folder wajib: app, public, resources, routes
- ❌ Jika tidak valid, update akan dibatalkan

### Proteksi Data

Yang **TIDAK** akan berubah saat update:
- File `.env` (konfigurasi database tetap aman)
- Folder `storage/` (logs, uploads, cache tetap aman)
- Folder `vendor/` (akan di-rebuild otomatis)
- Database (data tetap aman)

---

## Troubleshooting

### Update Gagal

Jika update gagal:
1. Lihat pesan error di halaman update
2. Cek log di `storage/logs/laravel.log`
3. Cek log update history
4. Lakukan rollback ke backup terakhir

### Backup Tidak Terbentuk

Pastikan:
- Folder `storage/app/backups/` ada dan writable (chmod 755)
- Disk space cukup untuk backup (minimal 2x ukuran aplikasi)
- PHP extension `ZipArchive` terinstall

### Migration Error

Jika migration error saat update:
- Update tetap akan selesai (migration error tidak membatalkan update)
- Migration error akan di-log tapi tidak menghentikan proses
- Jalankan manual: `php artisan migrate --force`

---

## Informasi Teknis

### Folder yang Di-Update

Folder berikut akan **di-replace** saat update:
- `app/` - Kode aplikasi
- `bootstrap/` - Bootstrap Laravel
- `config/` - Konfigurasi
- `database/` - Migrations & seeders
- `public/` - Assets & entry point
- `resources/` - Views & assets
- `routes/` - Route definitions

### File yang Di-Update

File berikut akan **di-replace** saat update:
- `artisan` - CLI tool
- `composer.json` - Dependencies
- `composer.lock` - Locked versions

### Yang TIDAK Di-Update

- `.env` - Environment config (PROTECTED)
- `storage/` - Logs, cache, uploads (PROTECTED)
- `vendor/` - Dependencies (REBUILD otomatis)

---

## Update Version Number

Untuk mengubah nomor versi aplikasi:

1. Edit file `VERSION` di root project
2. Ubah nomor versi (format: `v1.0.0`)
3. Commit & buat package update

Versi akan ditampilkan di:
- Admin panel → Update Aplikasi
- Footer aplikasi (opsional)

---

## Best Practices

### Sebelum Update

1. ✅ **Backup database manual** via cPanel PHPMyAdmin
2. ✅ **Test package** di local/development dulu
3. ✅ **Baca changelog** untuk mengetahui perubahan
4. ✅ **Pastikan disk space** cukup (minimal 2GB free)
5. ✅ **Catat versi saat ini** untuk reference

### Setelah Update

1. ✅ **Test semua fitur** utama (absensi, laporan, dll)
2. ✅ **Cek log errors** di `storage/logs/laravel.log`
3. ✅ **Verifikasi database** tidak ada data hilang
4. ✅ **Simpan backup** yang sukses untuk archive

### Maintenance

1. 🗑️ **Hapus backup lama** secara berkala (simpan 5-10 backup terakhir)
2. 📊 **Monitor disk usage** untuk mencegah disk penuh
3. 📝 **Dokumentasikan** setiap update yang dilakukan

---

## FAQ

### Q: Apakah data di database akan hilang?
**A**: Tidak. Update hanya replace file aplikasi, **TIDAK** mengubah data database.

### Q: Apakah perlu run composer install manual?
**A**: Tidak perlu. Sistem otomatis run migrations dan clear cache.

### Q: Berapa lama proses update?
**A**: Tergantung ukuran package. Biasanya 2-5 menit untuk package 20-50MB.

### Q: Apakah bisa update tanpa backup?
**A**: Tidak. Backup otomatis dan wajib untuk keamanan.

### Q: Maksimal berapa backup yang disimpan?
**A**: Unlimited. Namun disarankan hapus backup lama secara berkala untuk hemat disk.

### Q: Apakah file .env akan berubah?
**A**: Tidak. File `.env` **PROTECTED** dan tidak akan berubah saat update.

---

## Kontak Support

Jika mengalami masalah saat update:
- 📧 Email: dev@mtsn2kotamalang.sch.id
- 📱 WhatsApp: [nomor support]
- 🐛 Report Bug: [link issue tracker]

---

**Terakhir diupdate**: 2025-10-24
**Versi dokumen**: 1.0
