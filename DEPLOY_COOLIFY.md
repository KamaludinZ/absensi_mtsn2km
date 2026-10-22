# Deploy ke Coolify (Docker)

Panduan ini untuk memasang aplikasi Absensi MTsN 2 Kota Malang di server
yang memakai [Coolify](https://coolify.io). Aplikasi berjalan di dalam satu
container Docker (nginx + PHP 8.4) dan memakai database PostgreSQL dari
Coolify.

```
Pengunjung ──HTTPS──> Proxy Coolify (SSL otomatis) ──HTTP:80──> Container Absensi ──> PostgreSQL (Coolify)
                                                                   │
                                                                   └── 3 volume: foto selfie, logo, backup & log
```

Yang sudah diurus otomatis oleh image Docker setiap kali container menyala:

- Menunggu database siap, lalu menjalankan migrasi database (`migrate --force`).
- Membuat cache config/route/view supaya aplikasi cepat.
- Membuat folder yang dibutuhkan dan mengatur izin tulis.
- Mengisi logo sekolah bawaan ke volume yang masih kosong.
- Health check di alamat `/up` (Coolify tahu kapan aplikasi sudah siap).
- `pg_dump`/`psql` sudah terpasang, jadi menu **Admin > Backup** tetap jalan.
- IP asli pengunjung diteruskan dari proxy Coolify (untuk batas login dan log keamanan).
- File PHP selain `index.php` tidak pernah dijalankan; foto selfie dan dokumen
  izin hanya bisa dibuka lewat aplikasi setelah login.

---

## Langkah 1: Buat database PostgreSQL

1. Di Coolify buka project Anda, klik **+ New** → **Database** → **PostgreSQL**
   (pilih versi 17 atau 16).
2. Isi nama database, user, dan password (atau pakai yang dibuat Coolify), lalu **Start**.
3. Catat datanya. Yang dipakai adalah alamat **internal** (bukan public):
   - Host: nama/UUID container database (terlihat di *Postgres URL (internal)*, bagian setelah `@` dan sebelum `:5432`)
   - Port: `5432`
   - Database, Username, Password

> Aplikasi dan database harus berada di **server dan project/environment
> yang sama** di Coolify supaya bisa saling terhubung lewat jaringan internal.

## Langkah 2: Buat aplikasi

1. **+ New** → **Private Repository (with GitHub App)** (atau Public Repository),
   pilih repo `absensi_mtsn2km`, branch `main`.
2. **Build Pack**: pilih **Dockerfile** (bukan Nixpacks).
3. **Ports Exposes**: `80`.
4. **Domains**: isi alamat lengkap, misalnya `https://siab.mtsn2kotamalang.sch.id`.
   Arahkan DNS domain (A record) ke IP server Coolify. SSL dibuat otomatis.

## Langkah 3: Environment Variables

Buka tab **Environment Variables** aplikasi, lalu isi (bisa pakai mode
*Developer view* / tempel sekaligus):

```env
APP_NAME="MTsN 2 Absensi"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://siab.mtsn2kotamalang.sch.id
APP_KEY=base64:ISI_DENGAN_KUNCI_ANDA
APP_LOCALE=id
APP_FALLBACK_LOCALE=en

LOG_CHANNEL=stack
LOG_STACK=single,stderr
LOG_LEVEL=warning

DB_CONNECTION=pgsql
DB_HOST=isi-host-internal-dari-langkah-1
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

# Hanya untuk instalasi BARU dengan database kosong (lihat Langkah 5)
INITIAL_ADMIN_PASSWORD=
```

Penjelasan yang penting:

| Variabel | Keterangan |
|---|---|
| `APP_KEY` | **Wajib sama selamanya.** Jika pindah dari server lama, salin `APP_KEY` dari file `.env` server lama. Jika instalasi baru, buat dengan perintah `php artisan key:generate --show` (di komputer mana pun yang ada PHP, atau di Terminal container setelah deploy pertama). Jika dikosongkan, container membuat kunci sendiri dan menyimpannya di volume `storage` (lihat log deploy), tetapi lebih aman ditulis di sini. |
| `APP_URL` | Sama persis dengan domain, pakai `https://`. |
| `DB_*` | Dari Langkah 1 (alamat internal). |
| `SESSION_SECURE_COOKIE` | `true` karena situs memakai HTTPS. |
| `DISABLE_WEB_UPDATER` | `true`: menu update lewat panel admin dimatikan. Di Docker, kode aplikasi ada di dalam image, jadi update dilakukan dengan **Redeploy** (lihat bagian Update). |
| `INITIAL_ADMIN_PASSWORD` | Hanya untuk database kosong: membuat akun `admin` dengan password ini (minimal 8 karakter) beserta data awal (role, kelas, jadwal). Tidak berpengaruh jika database sudah berisi pengguna. |

Opsional:

| Variabel | Bawaan | Keterangan |
|---|---|---|
| `PHP_FPM_MAX_CHILDREN` | `10` | Jumlah proses PHP sekaligus. Kira-kira 50 MB RAM per proses. Server 2 GB: 8–10, server 4 GB: 20. |
| `RUN_MIGRATIONS` | `true` | Isi `false` jika ingin menjalankan migrasi sendiri. |
| `DB_WAIT_TIMEOUT` | `60` | Berapa detik menunggu database saat container menyala. |
| `MAIL_*` | – | Jika ingin mengirim email. |

> Jika Coolify menampilkan pilihan **Build Variable / Available at
> Buildtime** pada tiap variabel, matikan untuk `APP_KEY` dan `DB_PASSWORD`.
> Image ini tidak butuh variabel apa pun saat build.

## Langkah 4: Persistent Storage (WAJIB)

Tanpa ini, foto selfie, dokumen izin, logo, dan file backup akan **hilang
setiap redeploy**. Buka tab **Persistent Storage** → **+ Add** → *Volume*,
tambahkan 3 volume:

| Nama volume (bebas) | Destination Path (harus persis) | Isi |
|---|---|---|
| `absensi-storage` | `/var/www/html/storage` | backup (Admin > Backup), log, file sementara |
| `absensi-public-storage` | `/var/www/html/public/storage` | foto selfie, dokumen izin & koreksi absensi |
| `absensi-uploads` | `/var/www/html/public/uploads` | logo kop laporan |

## Langkah 5: Deploy

1. Klik **Deploy**. Build pertama butuh beberapa menit.
2. Di log deploy, tunggu baris `[entrypoint] Ready. Starting nginx + php-fpm.`
3. Buka domain Anda.

**Instalasi baru (database kosong):** isi `INITIAL_ADMIN_PASSWORD` sebelum
deploy. Setelah berhasil, login dengan username `admin` dan password
tersebut, lalu **segera ganti password** di menu profil dan kosongkan lagi
`INITIAL_ADMIN_PASSWORD` di Coolify.

**Pindahan dari server lama:** ikuti bagian berikut dulu, baru deploy.

---

## Pindah data dari server lama

### A. Database

1. Di server lama, buat dump database:

   ```bash
   pg_dump --no-owner --no-acl -Fc -h 127.0.0.1 -U USER_LAMA -d DB_LAMA -f absensi.dump
   ```

2. Masukkan ke database Coolify **yang masih kosong** (sebelum aplikasi
   pertama kali dideploy), salah satu cara:
   - Di halaman database Coolify, menu **Import Backup** → unggah `absensi.dump`
     (nama menu bisa sedikit berbeda tergantung versi Coolify), atau
   - Dari server Coolify (SSH):

     ```bash
     docker cp absensi.dump <container-database>:/tmp/absensi.dump
     docker exec -it <container-database> pg_restore --no-owner -U <user> -d <database> /tmp/absensi.dump
     ```

3. Salin `APP_KEY` dari `.env` server lama ke Environment Variables Coolify.
4. Deploy aplikasi. Migrasi yang belum ada akan dijalankan otomatis.

> Menu **Admin > Backup > Restore** di aplikasi ditujukan untuk memulihkan
> backup ke database yang sama. Untuk pindahan server, pakai cara di atas.

### B. File (foto selfie, dokumen, logo)

Setelah deploy pertama selesai, salin folder dari server lama ke container
(lewat SSH di server Coolify). Nama container terlihat di Coolify atau
dengan `docker ps`.

```bash
# dari server lama ke server Coolify (contoh memakai scp)
scp -r user@server-lama:/path/aplikasi/public/storage ./absensi-public-storage
scp -r user@server-lama:/path/aplikasi/public/uploads ./absensi-uploads

# masukkan ke container, lalu atur pemiliknya
docker cp ./absensi-public-storage/. <container-aplikasi>:/var/www/html/public/storage/
docker cp ./absensi-uploads/. <container-aplikasi>:/var/www/html/public/uploads/
docker exec <container-aplikasi> chown -R www-data:www-data /var/www/html/public/storage /var/www/html/public/uploads
```

---

## Update aplikasi

1. Perubahan kode di-*merge* ke branch `main` di GitHub.
2. Di Coolify klik **Redeploy** (atau aktifkan **Auto Deploy** agar otomatis
   setiap ada push ke `main`).
3. Migrasi database berjalan otomatis. Data di volume tetap aman.

Sebelum update besar, buat backup dulu di **Admin > Backup** dan unduh
filenya, atau gunakan fitur **Scheduled Backups** pada database di Coolify.

## Update keamanan PHP

Versi PHP ditulis tetap di `Dockerfile` (`ARG PHP_VERSION`). Saat ini
**8.4.27**, rilis 21 Oktober 2026 yang menambal celah kritis **PHP2Shell**
(CVE-2026-109618 dan CVE-2026-109619, eksekusi kode jarak jauh tanpa login).
Build juga sengaja dibuat **gagal** jika versi PHP di image lebih lama dari
`PHP_MIN_SAFE`, supaya server tidak pernah menjalankan PHP yang rentan.

- Setelah perubahan versi PHP di-merge ke `main`: klik **Redeploy** di Coolify.
- Cek versi yang berjalan: tab **Terminal** aplikasi → `php -v`.
- Admin juga melihat peringatan merah di setiap halaman jika versi PHP server
  masih rentan (berguna untuk server non-Docker seperti cPanel).
- Jika nanti ada rilis keamanan PHP baru, naikkan `PHP_VERSION` dan
  `PHP_MIN_SAFE` di `Dockerfile` serta daftar di `app/Support/PhpSecurity.php`.

## Backup

- **Dari aplikasi**: Admin > Backup (database + foto). File tersimpan di
  volume `absensi-storage` (`storage/app/backups`). Unduh secara berkala ke
  tempat lain.
- **Dari Coolify** (disarankan, otomatis): buka resource PostgreSQL →
  **Backups** → atur jadwal (misalnya setiap hari) dan, bila ada, tujuan S3.

## Menjalankan perintah artisan

Buka aplikasi di Coolify → tab **Terminal**, lalu jalankan sebagai user
web server, contoh:

```bash
su-exec www-data php artisan about
su-exec www-data php artisan migrate:status
```

## Pemecahan masalah

| Gejala | Penyebab & solusi |
|---|---|
| Log: `cannot connect to the database` | `DB_HOST`/`DB_PASSWORD` salah, atau database beda server/jaringan. Pakai alamat **internal** dari halaman database Coolify. |
| Log: `The database is empty. Set INITIAL_ADMIN_PASSWORD` | Database kosong: isi `INITIAL_ADMIN_PASSWORD` lalu Redeploy, atau impor database lama. |
| Halaman tampil tanpa gaya (CSS) atau link mengarah ke `http://` | `APP_URL` harus `https://domain-anda` dan domain di Coolify juga `https://`. |
| Error `419 Page Expired` saat login | Pastikan `SESSION_SECURE_COOKIE=true` hanya jika situs dibuka lewat HTTPS; bersihkan cookie browser. |
| Foto/logo hilang setelah redeploy | Persistent Storage belum dibuat atau Destination Path tidak persis sama (Langkah 4). |
| Semua pengguna logout setelah redeploy | `APP_KEY` berubah. Tulis `APP_KEY` tetap di Environment Variables. |
| Backup gagal `pg_dump ... server version mismatch` | Versi PostgreSQL server lebih baru dari 17. Build ulang image dengan build argument `PG_CLIENT_VERSION` sesuai versi server. |
| Unggah file besar gagal (413) | Batas unggah 512 MB sudah diatur di nginx & PHP; jika memakai Cloudflare, batas paket gratis Cloudflare 100 MB. |

## Mencoba di komputer sendiri (opsional)

Butuh Docker Desktop.

```bash
cp .env.docker.example .env      # lalu isi DB_PASSWORD dan INITIAL_ADMIN_PASSWORD
docker compose up -d --build
# buka http://localhost:8080
```

## Isi file Docker di repo ini

| File | Fungsi |
|---|---|
| `Dockerfile` | Membuat image: PHP 8.4 + ekstensi, Composer, build aset (Vite/Tailwind), nginx, supervisor, pg_dump. |
| `.dockerignore` | Menjaga file rahasia (`.env`, `.git`) dan file yang tidak perlu keluar dari image. |
| `docker/entrypoint.sh` | Langkah saat container menyala (folder, APP_KEY, tunggu DB, migrasi, cache). |
| `docker/nginx.conf` | Web server: IP asli dari proxy, HTTPS, blokir file PHP lain & file privat. |
| `docker/php/php.ini`, `docker/php/fpm-pool.conf` | Pengaturan PHP (batas unggah, zona waktu, OPcache, jumlah proses). |
| `docker/supervisord.conf` | Menjalankan nginx dan php-fpm bersama. |
| `docker-compose.yml`, `.env.docker.example` | Untuk mencoba lokal atau Coolify mode *Docker Compose*. |
| `.github/workflows/docker.yml` | GitHub Actions: build image dan uji jalan (health, login admin) di setiap PR. |
