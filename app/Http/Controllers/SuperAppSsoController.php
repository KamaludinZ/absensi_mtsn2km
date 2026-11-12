<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Student;
use App\Models\User;
use App\Services\SecurityService;
use App\Support\SuperAppId;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

/**
 * Penerima tiket login Super App (lihat SSO_SUPERAPP.md).
 *
 * Super App mengarahkan pengguna ke GET /sso/masuk?tiket=... (tiket 60 detik, sekali pakai). Tiket ditukar
 * ke Super App dari server ini memakai kunci rahasia, lalu dikenali lewat Super App ID ("id" dari Super App)
 * yang tersimpan di data GTK (employees.superapp_id) atau siswa (students.superapp_id).
 *
 * Pada login pertama (Super App ID belum tersimpan) akun dicocokkan sekali lewat nomornya, lalu Super App ID
 * disimpan:
 * - guru/pegawai: employees.nip = NIP / NIPM / Peg ID / NUPTK, atau username akun login;
 * - siswa: students.nisn = NISN.
 * Hanya GTK dan siswa yang aktif yang bisa masuk. Tidak ada password yang dikirim atau disimpan.
 */
class SuperAppSsoController extends Controller
{
    public function __construct(private SecurityService $security)
    {
    }

    public function masuk(Request $request)
    {
        $tiket = (string) $request->query('tiket', '');
        if ($tiket === '' || strlen($tiket) > 200) {
            return $this->gagal('Tiket tidak ditemukan. Buka aplikasi Absensi dari dashboard Super App.');
        }
        if (!self::aktif()) {
            $this->security->logEvent('sso_not_configured', 'SSO Super App belum diatur (SUPERAPP_SSO_URL / SUPERAPP_SSO_KUNCI kosong)', 'error');

            return $this->gagal('Login lewat Super App belum diatur di aplikasi ini.');
        }

        try {
            $respons = Http::withToken(config('superapp_sso.kunci'))
                ->acceptJson()
                ->timeout((int) config('superapp_sso.timeout', 10))
                ->post(rtrim(config('superapp_sso.url'), '/') . '/tukar', ['tiket' => $tiket]);
        } catch (ConnectionException $e) {
            $this->security->logEvent('sso_unreachable', 'Super App tidak dapat dihubungi', 'warning', ['error' => $e->getMessage()]);

            return $this->gagal('Super App tidak dapat dihubungi. Coba lagi beberapa saat.');
        }

        if (!$respons->successful()) {
            $this->security->logEvent('sso_rejected', 'Super App menolak tiket', 'warning', [
                'status' => $respons->status(),
                'kode' => $respons->json('detail.kode'),
            ]);

            return $this->gagal($respons->json('detail.pesan') ?: 'Login lewat Super App gagal. Coba buka lagi dari Super App.');
        }

        $pengguna = (array) $respons->json('pengguna', []);
        $jenis = $pengguna['jenis'] ?? null;
        $superAppId = trim((string) ($pengguna['id'] ?? ''));
        $konteks = ['superapp_id' => $superAppId, 'username_superapp' => $pengguna['username'] ?? null, 'jenis' => $jenis];

        if (!preg_match(SuperAppId::PATTERN, $superAppId)) {
            $this->security->logEvent('sso_denied', 'SSO ditolak: Super App ID kosong atau tidak valid', 'warning', $konteks);

            return $this->gagal('Data dari Super App tidak lengkap (Super App ID kosong). Hubungi admin Super App.');
        }

        if (!in_array($jenis, (array) config('superapp_sso.jenis_diizinkan', []), true)) {
            $this->security->logEvent('sso_denied', 'SSO ditolak: jenis pengguna tidak diizinkan', 'warning', $konteks);

            return $this->gagal('Akun Super App Anda tidak berhak membuka aplikasi Absensi.');
        }

        return $jenis === 'siswa'
            ? $this->masukSiswa($request, $pengguna, $superAppId, $konteks)
            : $this->masukPegawai($request, $pengguna, $superAppId, $konteks);
    }

    /** Tombol login ditampilkan hanya bila SSO sudah diatur. */
    public static function aktif(): bool
    {
        return filled(config('superapp_sso.url')) && filled(config('superapp_sso.kunci'));
    }

    /** Alamat dashboard Super App untuk tombol "Masuk lewat Super App". */
    public static function alamatSuperApp(): ?string
    {
        $alamat = config('superapp_sso.alamat_superapp')
            ?: preg_replace('#/api/sso/?$#', '', (string) config('superapp_sso.url'));

        return filled($alamat) ? $alamat : null;
    }

    private function masukPegawai(Request $request, array $pengguna, string $superAppId, array $konteks)
    {
        $employee = Employee::with('user.role')->where('superapp_id', $superAppId)->first();
        $hubungkan = false;
        if (!$employee) {
            $employee = $this->cariPegawai($pengguna, $konteks);
            if ($employee && filled($employee->superapp_id)) {
                return $this->sudahTerhubungLain('Data GTK', $konteks + ['employee_id' => $employee->id]);
            }
            $hubungkan = (bool) $employee;
        }
        $user = $employee?->user;

        if (!$employee || !$user) {
            $this->security->logEvent('sso_no_account', 'SSO: data pegawai tidak ditemukan di Absensi', 'info', $konteks);

            return $this->gagal('Data Anda belum terdaftar sebagai pegawai di aplikasi Absensi. Hubungi admin Absensi.');
        }
        if (!$employee->is_active) {
            $this->security->logEvent('sso_inactive', 'SSO ditolak: pegawai nonaktif', 'warning', $konteks + ['employee_id' => $employee->id]);

            return $this->gagal('Data pegawai Anda di aplikasi Absensi sedang nonaktif. Hubungi admin Absensi.');
        }

        if ($hubungkan) {
            $employee->update(['superapp_id' => $superAppId]);
            $this->security->logEvent('sso_linked', "Super App ID dihubungkan ke GTK {$employee->full_name}", 'info', $konteks + ['employee_id' => $employee->id]);
        }

        Auth::guard('student')->logout();
        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        $this->security->recordLoginAttempt($user->username, true);
        $this->security->logEvent('login_success', "User {$user->username} berhasil login lewat Super App", 'info', $konteks + [
            'user_id' => $user->id,
            'role' => $user->role->name,
        ]);

        return redirect()->intended($user->homeUrl());
    }

    private function masukSiswa(Request $request, array $pengguna, string $superAppId, array $konteks)
    {
        $nisn = trim((string) ($pengguna['nisn'] ?? ''));
        $student = Student::where('superapp_id', $superAppId)->first();
        $hubungkan = false;
        if (!$student) {
            $student = $nisn !== '' ? Student::where('nisn', $nisn)->first() : null;
            if ($student && filled($student->superapp_id)) {
                return $this->sudahTerhubungLain('Data siswa', $konteks + ['student_id' => $student->id]);
            }
            $hubungkan = (bool) $student;
        }

        if (!$student) {
            $this->security->logEvent('sso_no_account', 'SSO: data siswa tidak ditemukan di Absensi', 'info', $konteks + ['nisn' => $nisn]);

            return $this->gagal('Data Anda belum terdaftar sebagai siswa di aplikasi Absensi (NISN tidak ditemukan). Hubungi admin Absensi.');
        }
        if (!$student->canSignIn()) {
            $this->security->logEvent('sso_inactive', 'SSO ditolak: siswa nonaktif atau lulus', 'warning', $konteks + ['student_id' => $student->id]);

            return $this->gagal('Data siswa Anda di aplikasi Absensi sedang nonaktif. Hubungi admin Absensi.');
        }

        if ($hubungkan) {
            $student->update(['superapp_id' => $superAppId]);
            $this->security->logEvent('sso_linked', "Super App ID dihubungkan ke siswa {$student->nisn}", 'info', $konteks + ['student_id' => $student->id]);
        }

        Auth::guard('web')->logout();
        Auth::guard('student')->login($student);
        $request->session()->regenerate();

        $this->security->logEvent('login_success', "Siswa {$student->nisn} berhasil login lewat Super App", 'info', $konteks + ['student_id' => $student->id]);

        return redirect()->route('student.portal.index');
    }

    /**
     * Login pertama: pegawai dicocokkan lewat nomor pegawai (employees.nip berisi NIP, atau NIPM / Peg ID / NUPTK
     * untuk non-ASN). Bila tidak ada yang cocok, dicoba username akun login, asal nomornya tidak bertentangan.
     */
    private function cariPegawai(array $pengguna, array $konteks): ?Employee
    {
        $nomor = collect(['nip', 'nipm', 'peg_id', 'nuptk'])
            ->map(fn ($kunci) => trim((string) ($pengguna[$kunci] ?? '')))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($nomor !== []) {
            $employee = Employee::with('user.role')->whereIn('nip', $nomor)->first();
            if ($employee) {
                return $employee;
            }
        }

        $username = trim((string) ($pengguna['username'] ?? ''));
        if ($username === '') {
            return null;
        }

        $employee = User::where('username', $username)->first()?->employee;
        if ($employee && filled($employee->nip) && $nomor !== [] && !in_array($employee->nip, $nomor, true)) {
            // Username sama tetapi nomor pegawainya beda: kemungkinan orang lain, jangan dimasukkan.
            $this->security->logEvent('sso_mismatch', 'SSO ditolak: username cocok tetapi NIP berbeda', 'warning', $konteks + ['employee_id' => $employee->id]);

            return null;
        }

        return $employee?->loadMissing('user.role');
    }

    /** Akun lokal sudah terhubung ke Super App ID lain: kemungkinan orang lain, jangan dimasukkan. */
    private function sudahTerhubungLain(string $data, array $konteks)
    {
        $this->security->logEvent('sso_mismatch', 'SSO ditolak: akun sudah terhubung ke Super App ID lain', 'warning', $konteks);

        return $this->gagal($data . ' Anda di aplikasi Absensi sudah terhubung ke akun Super App lain. Hubungi admin Absensi.');
    }

    private function gagal(string $pesan)
    {
        return redirect()->route('login')->withErrors(['sso' => $pesan]);
    }
}
