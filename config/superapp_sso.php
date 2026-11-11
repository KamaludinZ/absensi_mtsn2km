<?php

// Login lewat Super App (SSO tiket). Lihat SSO_SUPERAPP.md.
// Nilai rahasia diambil dari .env / Environment Variables Coolify, jangan ditulis di file ini.

return [
    // Alamat API SSO Super App, mis. https://superapp.madrasah.sch.id/api/sso
    'url' => env('SUPERAPP_SSO_URL'),

    // Kunci rahasia aplikasi Absensi (diawali "sso_"), ditampilkan sekali saat aplikasi didaftarkan di Super App.
    'kunci' => env('SUPERAPP_SSO_KUNCI'),

    // Tujuan tombol "Masuk lewat Super App" di halaman login (dashboard Super App).
    // Kosong: diambil dari SUPERAPP_SSO_URL tanpa akhiran /api/sso.
    'alamat_superapp' => env('SUPERAPP_URL'),

    // Pemeriksaan ulang di sisi Absensi: jenis pengguna Super App yang boleh masuk.
    // Hak akses utama tetap diatur admin Super App.
    'jenis_diizinkan' => ['siswa', 'guru', 'pegawai'],

    // Batas waktu menghubungi Super App (detik).
    'timeout' => 10,
];
