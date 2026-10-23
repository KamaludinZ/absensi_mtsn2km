# Deploy ke Coolify (Docker)

Panduan ini untuk memasang aplikasi Absensi MTsN 2 Kota Malang di server
yang memakai [Coolify](https://coolify.io). Ada **dua cara**, pilih salah satu:

| | **Cara A: Dockerfile** | **Cara B: Docker Compose** |
|---|---|---|
| Isi | Aplikasi saja; database PostgreSQL dibuat terpisah di Coolify | Aplikasi + database PostgreSQL dalam satu resource |
| Penyimpanan permanen | Dibuat manual (3 volume) | Otomatis dari `docker-compose.yml` |
| Backup terjadwal dari Coolify (bisa ke S3) | Ya, dari halaman database | Pakai backup otomatis aplikasi (lihat bawah) |
| Update tanpa jeda (rolling update) | Ya | Ada jeda beberapa detik saat restart |
| Cocok untuk | Produksi, ingin fitur backup Coolify | Paling mudah dipasang |

Kedua cara memakai image yang sama dan **sama-sama aman untuk update dan redeploy**.

```
Pengunjung ──HTTPS──> Proxy Coolify (SSL otomatis) ──HTTP:80──> Container Absensi ──> PostgreSQL
                                                                   │
                                                                   └── volume: foto, dokumen, logo, backup, log
```

## Yang diurus otomatis setiap container menyala

- Menunggu database siap, lalu menjalankan migrasi database.
- **Backup database otomatis sebelum update** yang mengubah database
  (`backup_premigrate_...zip`). Kalau backup ini gagal, update **dibatalkan**
  dan data tidak berubah.
- **Backup database harian** pukul 01:00 (`backup_auto_...zip`). Disimpan
  14 terbaru, backup buatan admin tidak pernah dihapus.
- Semua backup muncul di **Admin > Backup** dan bisa diunduh atau di-restore.
- **Restore aman:** sebelum restore, data saat itu dibackup dulu
  (`backup_prerestore_...zip`). Restore berjalan dalam satu transaksi: kalau ada
  yang gagal, database tidak berubah sama sekali.
- Kunci aplikasi (`APP_KEY`) dibuat sekali dan disimpan di volume, jadi tidak
  berubah saat redeploy.
- Peringatan di log deploy dan banner merah untuk admin jika folder data
  belum memakai volume permanen, atau jika versi PHP masih rentan.
- Cache config/route/view, izin folder, logo bawaan, health check `/up`.
- IP asli pengunjung dari proxy Coolify; hanya `index.php` yang dijalankan;
  foto selfie dan dokumen izin hanya bisa dibuka setelah login.

---

## Cara A: Dockerfile + database Coolify

### A1. Buat database PostgreSQL

1. Di Coolify buka project Anda, klik **+ New** → **Database** → **PostgreSQL**
   (versi 16, 17, atau 18).
2. Isi nama database, user, dan password (atau pakai yang dibuat Coolify), lalu **Start**.
3. Catat alamat **internal** (bukan public):
   - Host: bagian setelah `@` dan sebelum `:5432` pada *Postgres URL (internal)*
   - Port `5432`, Database, Username, Password
4. Disarankan: tab **Backups** database → aktifkan jadwal harian (dan tujuan S3 bila ada).

> Aplikasi dan database harus di **server dan project/environment yang sama**.

### A2. Buat aplikasi

1. **+ New** → **Private Repository (with GitHub App)** (atau Public Repository),
   pilih repo `absensi_mtsn2km`, branch `main`.
2. **Build Pack**: **Dockerfile**.
3. **Ports Exposes**: `80`.
4. **Domains**: misalnya `https://siab.mtsn2kotamalang.sch.id`. Arahkan DNS
   (A record) ke IP server Coolify. SSL dibuat otomatis.

### A3. Environment Variables

Tab **Environment Variables** (bisa ditempel sekaligus di *Developer view*):

```env
APP_NAME="MTsN 2 Absensi"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://siab.mtsn2kotamalang.sch.id
APP_KEY=
APP_LOCALE=id
APP_FALLBACK_LOCALE=en

LOG_CHANNEL=stack
LOG_STACK=single,stderr
LOG_LEVEL=warning

DB_CONNECTION=pgsql
DB_HOST=isi-host-internal-dari-A1
DB_PORT=5432
DB_DATABASE=isi-nama-database
DB_USERNAME=isi-username
DB_PASSWORD=isi-password

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true
CACHE_STORE=database
QUEUE_CONNECTION=sync
FILESYSTEM_DISK=local

DISABLE_WEB_UPDATER=true

# Hanya untuk instalasi BARU dengan database kosong
INITIAL_ADMIN_PASSWORD=
```

> Jika ada pilihan **Build Variable / Available at Buildtime**, matikan untuk
> `APP_KEY` dan `DB_PASSWORD`. Image ini tidak butuh variabel saat build.

### A4. Persistent Storage (WAJIB untuk Cara A)

Tab **Persistent Storage** → **+ Add** → *Volume*, tambahkan 3 volume:

| Nama volume (bebas) | Destination Path (harus persis) | Isi |
|---|---|---|
| `absensi-storage` | `/var/www/html/storage` | backup, log, kunci aplikasi |
| `absensi-public-storage` | `/var/www/html/public/storage` | foto selfie, dokumen izin & koreksi |
| `absensi-uploads` | `/var/www/html/public/uploads` | logo kop laporan |

Jika ada yang terlewat, log deploy menampilkan
`WARNING: ... is NOT a persistent volume` dan admin melihat banner merah
**"Penyimpanan belum permanen"**.

### A5. Deploy

Klik **Deploy**, tunggu baris `[entrypoint] Ready. Starting nginx + php-fpm.`
di log, lalu buka domain. Lanjut ke [Setelah deploy pertama](#setelah-deploy-pertama).

---

## Cara B: Docker Compose (aplikasi + database)

### B1. Buat resource

1. **+ New** → **Private Repository (with GitHub App)** (atau Public Repository),
   pilih repo `absensi_mtsn2km`, branch `main`.
2. **Build Pack**: **Docker Compose**.
3. **Docker Compose Location**: pastikan `/docker-compose.yml`
   (bukan `docker-compose.local.yml`, itu hanya untuk komputer sendiri).
4. Klik **Save / Load Compose**. Coolify membaca dua service: `app` dan `db`.

### B2. Environment Variables

Tab **Environment Variables**, isi minimal:

```env
APP_URL=https://siab.mtsn2kotamalang.sch.id
DB_PASSWORD=buat-password-panjang-dan-acak
# Hanya untuk instalasi BARU dengan database kosong
INITIAL_ADMIN_PASSWORD=
```

Opsional: `APP_KEY` (salin dari server lama jika pindahan; kosong = dibuat
otomatis), `DB_DATABASE` dan `DB_USERNAME` (bawaan `absensi`), serta variabel
backup di tabel [Pengaturan tambahan](#pengaturan-tambahan).

> **Jangan ubah `DB_PASSWORD` setelah deploy pertama.** PostgreSQL hanya
> memakainya saat database pertama kali dibuat. Jika diubah, aplikasi tidak
> bisa masuk ke database (kembalikan ke nilai lama untuk memperbaikinya).

Jika `APP_URL` atau `DB_PASSWORD` kosong, deploy berhenti dengan pesan
`required variable ... is missing a value`. Ini disengaja.

### B3. Domain

Di daftar service, pada service **app** isi **Domains**, misalnya
`https://siab.mtsn2kotamalang.sch.id`. Service **db** tidak diberi domain
(database tidak boleh dibuka dari internet).

### B4. Deploy

Klik **Deploy**. Volume dibuat otomatis dari `docker-compose.yml`:
`absensi-storage`, `absensi-public-storage`, `absensi-uploads` (data
aplikasi) dan `absensi-db` (database). Tidak perlu membuat Persistent Storage
manual.

---

## Setelah deploy pertama

- **Instalasi baru (database kosong):** isi `INITIAL_ADMIN_PASSWORD` sebelum
  deploy. Login dengan username `admin` dan password tersebut, **segera ganti
  password**, lalu kosongkan lagi `INITIAL_ADMIN_PASSWORD`.
- **Pindahan dari server lama:** lihat [Pindah data dari server lama](#pindah-data-dari-server-lama).
- Jika `APP_KEY` dikosongkan, salin kunci yang dibuat otomatis ke Environment
  Variables agar tersimpan juga di Coolify. Lihat isinya di Terminal:
  `cat /var/www/html/storage/app/private/.app_key`.

## Aturan agar data selalu aman

1. **Jangan hapus resource** aplikasi/database di Coolify. Jika terpaksa,
   jangan centang pilihan untuk menghapus volume.
2. **Cara A:** jangan ubah *Destination Path* di Persistent Storage.
   **Cara B:** jangan ganti nama atau hapus volume di `docker-compose.yml`.
   Nama baru berarti volume baru yang kosong.
3. **Jangan ganti versi utama PostgreSQL** (misalnya 17 ke 18) tanpa backup dan
   restore. Versi baru tidak bisa membaca folder data versi lama.
4. **Jaga `APP_KEY` tetap sama.**
5. **Simpan salinan di luar server:** unduh backup terbaru dari
   **Admin > Backup** minimal seminggu sekali, atau aktifkan backup S3 di
   database Coolify (Cara A). Backup di server tidak menolong jika servernya rusak.

## Update aplikasi

1. Perubahan kode di-*merge* ke branch `main` di GitHub.
2. Di Coolify klik **Redeploy** (atau aktifkan **Auto Deploy**).
3. Yang terjadi otomatis:
   - Image baru dibuat, container baru menyala dengan volume yang sama.
   - Jika ada perubahan database, database **dibackup dulu**
     (`backup_premigrate_...zip`), baru migrasi dijalankan.
   - Jika backup gagal, update dibatalkan dan log menjelaskan sebabnya; data tetap utuh.

### Membatalkan update yang bermasalah

1. Kembalikan kode ke versi sebelumnya: di GitHub *revert* perubahan lalu
   Redeploy, atau pakai fitur **Rollback** deployment di Coolify jika tersedia.
2. Jika data juga perlu dikembalikan: **Admin > Backup > Restore** → pilih
   `backup_premigrate_...` dengan tanggal update tersebut.

## Backup dan restore

| Jenis | Nama file | Kapan |
|---|---|---|
| Manual | `backup_full_...zip` (database + foto) | Tombol **Buat Backup** di Admin > Backup |
| Harian | `backup_auto_...zip` (database) | Setiap hari pukul `AUTO_BACKUP_TIME` |
| Sebelum update | `backup_premigrate_...zip` | Saat redeploy yang mengubah database |
| Sebelum restore | `backup_prerestore_...zip` | Setiap kali restore dijalankan |

Semua ada di volume `absensi-storage` (folder `storage/app/backups`). Backup
otomatis disimpan `BACKUP_KEEP` buah terbaru per jenis. Backup manual dan file
yang diunggah tidak pernah dihapus otomatis.

**Restore** mengganti seluruh isi database dengan isi backup (termasuk akun
pengguna). Prosesnya satu transaksi, jadi kalau gagal di tengah jalan, database
tetap seperti sebelumnya.

Backup manual dari terminal:

```bash
su-exec www-data php artisan app:backup-database
```

## Pindah data dari server lama

Berlaku untuk Cara A dan B.

### A. Database (lewat Admin > Backup)

1. Deploy dulu sebagai instalasi baru (dengan `INITIAL_ADMIN_PASSWORD`) dan
   salin `APP_KEY` dari `.env` server lama ke Environment Variables, lalu Redeploy.
2. Di server lama, buat file backup:

   ```bash
   pg_dump --no-owner --no-acl -h 127.0.0.1 -U USER_LAMA -d DB_LAMA -f database.sql
   zip backup_lama.zip database.sql
   ```

3. Di aplikasi baru: **Admin > Backup > Restore** → unggah `backup_lama.zip` → **Restore**.
4. Setelah restore, login memakai akun dan password dari server lama.
   Redeploy sekali lagi agar migrasi yang lebih baru dijalankan.

### B. File (foto selfie, dokumen, logo)

Lewat SSH di server Coolify (nama container terlihat di Coolify atau dengan `docker ps`):

```bash
scp -r user@server-lama:/path/aplikasi/public/storage ./absensi-public-storage
scp -r user@server-lama:/path/aplikasi/public/uploads ./absensi-uploads

docker cp ./absensi-public-storage/. <container-aplikasi>:/var/www/html/public/storage/
docker cp ./absensi-uploads/. <container-aplikasi>:/var/www/html/public/uploads/
docker exec <container-aplikasi> chown -R www-data:www-data /var/www/html/public/storage /var/www/html/public/uploads
```

## Pengaturan tambahan

| Variabel | Bawaan | Keterangan |
|---|---|---|
| `AUTO_BACKUP` | `true` | Backup database harian. |
| `AUTO_BACKUP_TIME` | `01:00` | Jam backup harian (WIB). |
| `BACKUP_KEEP` | `14` | Jumlah backup otomatis yang disimpan per jenis. |
| `PRE_MIGRATION_BACKUP` | `true` | Backup sebelum update yang mengubah database. Isi `false` hanya jika backup selalu gagal dan Anda sudah punya backup lain. |
| `PHP_FPM_MAX_CHILDREN` | `10` | Jumlah proses PHP. Kira-kira 50 MB RAM per proses. Server 2 GB: 8–10, server 4 GB: 20. |
| `RUN_MIGRATIONS` | `true` | Isi `false` jika ingin menjalankan migrasi sendiri. |
| `DB_WAIT_TIMEOUT` | `60` | Berapa detik menunggu database saat container menyala. |
| `MAIL_*` | – | Jika ingin mengirim email. |

## Update keamanan PHP

Versi PHP ditulis tetap di `Dockerfile` (`ARG PHP_VERSION`). Saat ini
**8.4.27**, rilis 21 Oktober 2026 yang menambal celah kritis **PHP2Shell**
(CVE-2026-109618 dan CVE-2026-109619). Build sengaja **gagal** jika versi PHP di
image lebih lama dari `PHP_MIN_SAFE`.

- Setelah perubahan versi PHP di-merge ke `main`: **Redeploy**.
- Cek versi yang berjalan: tab **Terminal** → `php -v`.
- Untuk rilis keamanan PHP berikutnya, naikkan `PHP_VERSION` dan `PHP_MIN_SAFE`
  di `Dockerfile` serta daftar di `app/Support/PhpSecurity.php`.

## Menjalankan perintah artisan

Tab **Terminal** aplikasi (Cara B: pilih container `app`), jalankan sebagai
user web server:

```bash
su-exec www-data php artisan about
su-exec www-data php artisan migrate:status
su-exec www-data php artisan app:backup-database
```

## Pemecahan masalah

| Gejala | Penyebab & solusi |
|---|---|
| Log: `cannot connect to the database` | Cara A: `DB_HOST`/`DB_PASSWORD` salah atau database beda server; pakai alamat **internal**. Cara B: `DB_PASSWORD` diubah setelah deploy pertama; kembalikan nilai lama. |
| Log: `the backup before the database update failed, so the update was NOT run` | Baca pesan di atasnya. Paling sering: versi PostgreSQL server lebih baru dari `pg_dump` di image (lihat baris berikut). Data tidak berubah. |
| `pg_dump ... server version mismatch` | Image memakai `pg_dump` 18 (cocok untuk server 18 ke bawah). Untuk server yang lebih baru, ubah `PG_CLIENT_VERSION` di `Dockerfile`. |
| Log: `The database is empty. Set INITIAL_ADMIN_PASSWORD` | Database kosong: isi `INITIAL_ADMIN_PASSWORD` lalu Redeploy, atau pindahkan data lama. |
| Deploy Cara B: `required variable APP_URL/DB_PASSWORD is missing a value` | Isi variabel tersebut di Environment Variables. |
| Banner merah "Penyimpanan belum permanen" | Cara A: Persistent Storage belum lengkap (A4). Cara B: jangan hapus bagian `volumes` di `docker-compose.yml`. |
| Halaman tanpa gaya (CSS) atau link `http://` | `APP_URL` harus `https://domain-anda`, domain di Coolify juga `https://`. |
| Error `419 Page Expired` saat login | `SESSION_SECURE_COOKIE=true` hanya untuk HTTPS; bersihkan cookie browser. |
| Semua pengguna logout setelah redeploy | `APP_KEY` berubah. Tulis `APP_KEY` tetap di Environment Variables. |
| Unggah file besar gagal (413) | Batas 512 MB sudah diatur; jika memakai Cloudflare, batas paket gratisnya 100 MB. |

## Mencoba di komputer sendiri (opsional)

Butuh Docker Desktop.

```bash
cp .env.docker.example .env      # lalu isi DB_PASSWORD dan INITIAL_ADMIN_PASSWORD
docker compose -f docker-compose.yml -f docker-compose.local.yml up -d --build
# buka http://localhost:8080
```

## Isi file Docker di repo ini

| File | Fungsi |
|---|---|
| `Dockerfile` | Image: PHP 8.4 + ekstensi, Composer, aset Vite/Tailwind, nginx, supervisor, pg_dump/psql. |
| `docker-compose.yml` | Cara B (Coolify Docker Compose): aplikasi + PostgreSQL + volume. |
| `docker-compose.local.yml`, `.env.docker.example` | Tambahan untuk mencoba di komputer sendiri (port 8080). |
| `.dockerignore` | Menjaga file rahasia (`.env`, `.git`) keluar dari image. |
| `docker/entrypoint.sh` | Saat container menyala: folder, cek volume, APP_KEY, tunggu DB, backup sebelum update, migrasi, cache. |
| `docker/supervisord.conf` | Menjalankan nginx, php-fpm, dan penjadwal backup harian. |
| `docker/nginx.conf` | Web server: IP asli dari proxy, HTTPS, blokir file PHP lain & file privat. |
| `docker/php/php.ini`, `docker/php/fpm-pool.conf` | Pengaturan PHP. |
| `.github/workflows/docker.yml`, `.github/scripts/compose-e2e.sh` | GitHub Actions: build image, uji Cara A, dan uji Cara B (install, redeploy, update dengan migrasi baru, restart; data harus tetap ada). |
