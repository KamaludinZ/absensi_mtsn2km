# Login lewat Super App (SSO)

Siswa, guru, dan pegawai bisa masuk ke Absensi tanpa mengetik password: masuk di
**Super App**, lalu klik **Absensi** di kartu *Aplikasi Madrasah Lainnya*. Halaman login
Absensi juga punya tombol **Masuk lewat Super App** yang membuka dashboard Super App.

Cara kerja lengkap ada di dokumentasi Super App: `docs/sso` di repo `super_app_madrasah`.
Singkatnya, Super App mengirim pengguna ke `https://<alamat-absensi>/sso/masuk?tiket=...`.
Tiket itu berlaku 60 detik dan hanya sekali pakai. Absensi menukarnya ke Super App dari
server, memakai kunci rahasia.

## Siapa yang bisa masuk

| Jenis di Super App | Dicocokkan ke data Absensi | Setelah masuk |
|---|---|---|
| `guru`, `pegawai` | **Data pegawai** (`employees.nip`) = NIP, Peg ID, atau NUPTK dari Super App. Kalau tidak ada nomor yang cocok, dipakai **username** akun login, asalkan NIP-nya tidak berbeda. | Akun login pegawai itu (dashboard pegawai, atau admin/monitoring bila akunnya berperan itu). |
| `siswa` | **Data siswa** (`students.nisn`) = NISN dari Super App | Halaman **Absensi Saya**: status hari ini dan riwayat absensi per bulan (hanya melihat). |
| `alumni` | Ditolak | – |

Syaratnya, data harus **aktif** di Absensi:

- Pegawai: status aktif, dan sudah punya akun login (Admin > Pegawai).
- Siswa: status aktif dan belum lulus.

Kalau data dinonaktifkan, login lewat Super App langsung ditolak. Siswa yang sedang membuka
Absensi Saya juga langsung dikeluarkan. Akun admin atau monitoring yang tidak terhubung ke data
pegawai tetap masuk dengan username dan password.

Absen siswa tetap dilakukan di sekolah, lewat halaman absen siswa (NISN) atau kartu RFID.

## Pemasangan

### 1. Daftarkan Absensi di Super App (admin Super App)

**Admin → Sistem & Utilitas → Aplikasi Terhubung (SSO) → Tambah aplikasi**:

| Isian | Nilai |
|---|---|
| Kode aplikasi | `absensi` |
| Nama | Absensi MTsN 2 Kota Malang |
| Alamat penerima tiket | `https://<alamat-absensi>/sso/masuk`, misalnya `https://siab.mtsn2kotamalang.sch.id/sso/masuk` |
| Peran yang boleh membuka | **Semua Siswa**, **Semua Guru**, **Semua Pegawai** |

Setelah disimpan, Super App menampilkan **kunci rahasia** (diawali `sso_`). Kunci ini hanya
tampil **sekali**. Kalau hilang, klik **Kunci baru**; kunci lama langsung tidak berlaku.

### 2. Isi Environment Variables di Absensi

Coolify → aplikasi Absensi → **Environment Variables** (atau `.env` di hosting biasa):

```env
SUPERAPP_SSO_URL=https://<alamat-super-app>/api/sso
SUPERAPP_SSO_KUNCI=sso_xxxxxxxxxxxxxxxxxxxxxxxx
# Opsional: alamat dashboard Super App untuk tombol login.
# Kosong = alamat SUPERAPP_SSO_URL tanpa /api/sso.
SUPERAPP_URL=
```

Lalu **Redeploy**. Di hosting biasa, jalankan `php artisan config:clear` (atau `config:cache`).
Tombol **Masuk lewat Super App** baru muncul di halaman login setelah `SUPERAPP_SSO_URL` dan
`SUPERAPP_SSO_KUNCI` terisi.

Kunci hanya disimpan di server. Jangan menaruhnya di kode, di git, atau mengirimnya lewat chat.

### 3. Cek koneksi dari server Absensi

Di tab **Terminal** aplikasi:

```bash
curl -s -H "Authorization: Bearer $SUPERAPP_SSO_KUNCI" "$SUPERAPP_SSO_URL/cek"
# {"aplikasi":"absensi","nama":"Absensi MTsN 2 Kota Malang","umur_tiket_detik":60}
```

### 4. Siapkan data agar cocok

- **Guru/pegawai:** isi **NIP** di data pegawai Absensi dengan nilai yang sama seperti di Super
  App. ASN memakai NIP 18 digit; non-ASN memakai Peg ID atau NUPTK yang sama dengan di Super App.
- **Siswa:** NISN di Absensi harus sama dengan NISN siswa di Super App.

## Pesan yang mungkin muncul di halaman login

| Pesan | Artinya |
|---|---|
| Tiket tidak ditemukan / sudah kedaluwarsa / sudah dipakai | Buka lagi Absensi dari dashboard Super App (tiket hanya 60 detik dan sekali pakai). |
| Login lewat Super App belum diatur | `SUPERAPP_SSO_URL` atau `SUPERAPP_SSO_KUNCI` belum diisi. |
| Super App tidak dapat dihubungi | Server Absensi tidak bisa mengakses Super App (alamat salah atau Super App sedang mati). |
| Kunci tidak valid | Kunci di Absensi salah atau sudah diganti di Super App. |
| Data Anda belum terdaftar sebagai pegawai/siswa | NIP/NISN di Absensi berbeda dengan di Super App, atau pegawai belum punya akun login. |
| Data ... sedang nonaktif | Pegawai/siswa nonaktif atau sudah lulus di Absensi. |
| Akun Super App Anda tidak berhak | Jenis pengguna bukan siswa/guru/pegawai, atau akses dicabut admin Super App. |

Semua login dan penolakan lewat Super App tercatat di **Admin → Keamanan** (log keamanan).
