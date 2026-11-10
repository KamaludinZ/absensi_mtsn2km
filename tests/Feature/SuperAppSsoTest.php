<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Grade;
use App\Models\Role;
use App\Models\Rombel;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Login lewat Super App (SSO tiket): guru/pegawai masuk ke akun pegawainya,
 * siswa masuk ke "Absensi Saya". Hanya data yang aktif di Absensi.
 */
class SuperAppSsoTest extends TestCase
{
    use RefreshDatabase;

    private const URL = 'https://superapp.test/api/sso';
    private const KUNCI = 'sso_kunci_uji';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'superapp_sso.url' => self::URL,
            'superapp_sso.kunci' => self::KUNCI,
            'superapp_sso.alamat_superapp' => null,
        ]);
    }

    private function superAppSays(array $pengguna, int $status = 200): void
    {
        $body = $status === 200
            ? ['aplikasi' => 'absensi', 'pengguna' => $pengguna, 'masuk_pada' => now()->toIso8601String()]
            : ['detail' => $pengguna];

        Http::fake([self::URL . '/tukar' => Http::response($body, $status)]);
    }

    private function guru(array $user = [], array $employee = []): Employee
    {
        $u = User::create($user + [
            'name' => 'Budi',
            'username' => 'budi',
            'password' => bcrypt('rahasia123'),
            'role_id' => Role::firstOrCreate(['name' => 'pegawai'])->id,
        ]);

        return Employee::create($employee + [
            'user_id' => $u->id,
            'nip' => '198207152009012003',
            'full_name' => 'Budi Santoso',
            'position' => 'Guru',
            'is_active' => true,
        ]);
    }

    private function siswa(array $data = []): Student
    {
        $year = AcademicYear::firstOrCreate(['year_name' => '2026/2027'], ['status' => 'active']);
        $rombel = Rombel::firstOrCreate(
            ['name' => 'VII A'],
            ['academic_year_id' => $year->id, 'grade_id' => Grade::first()->id]
        );

        return Student::create($data + [
            'nisn' => '0012345678',
            'full_name' => 'Siti Aminah',
            'rombel_id' => $rombel->id,
            'is_active' => true,
        ]);
    }

    private function masuk(string $tiket = 'tiket-acak_123')
    {
        return $this->get('/sso/masuk?tiket=' . $tiket);
    }

    public function test_guru_masuk_lewat_nip_ke_dashboard_pegawai(): void
    {
        $employee = $this->guru();
        $this->superAppSays(['username' => 'budi.s', 'nip' => '198207152009012003', 'jenis' => 'guru']);

        $this->masuk()->assertRedirect(route('employee.dashboard'));

        $this->assertAuthenticatedAs($employee->user);
        Http::assertSent(fn (HttpRequest $r) => $r->url() === self::URL . '/tukar'
            && $r->hasHeader('Authorization', 'Bearer ' . self::KUNCI)
            && $r['tiket'] === 'tiket-acak_123');
    }

    public function test_pegawai_non_asn_masuk_lewat_peg_id(): void
    {
        $employee = $this->guru([], ['nip' => 'PEG-123']);
        $this->superAppSays(['username' => 'lain', 'nip' => null, 'peg_id' => 'PEG-123', 'jenis' => 'pegawai']);

        $this->masuk()->assertRedirect(route('employee.dashboard'));
        $this->assertAuthenticatedAs($employee->user);
    }

    public function test_cocok_lewat_username_bila_tidak_ada_nomor_pegawai(): void
    {
        $employee = $this->guru();
        $this->superAppSays(['username' => 'budi', 'jenis' => 'guru']);

        $this->masuk()->assertRedirect(route('employee.dashboard'));
        $this->assertAuthenticatedAs($employee->user);
    }

    public function test_username_sama_tetapi_nip_berbeda_ditolak(): void
    {
        $this->guru();
        $this->superAppSays(['username' => 'budi', 'nip' => '199001012020121001', 'jenis' => 'guru']);

        $this->masuk()->assertRedirect(route('login'))->assertSessionHasErrors('sso');
        $this->assertGuest();
    }

    public function test_pegawai_nonaktif_ditolak(): void
    {
        $this->guru([], ['is_active' => false]);
        $this->superAppSays(['nip' => '198207152009012003', 'jenis' => 'guru']);

        $this->masuk()->assertSessionHasErrors(['sso' => 'Data pegawai Anda di aplikasi Absensi sedang nonaktif. Hubungi admin Absensi.']);
        $this->assertGuest();
    }

    public function test_akun_admin_tanpa_data_pegawai_tidak_bisa_lewat_sso(): void
    {
        User::create([
            'name' => 'Admin',
            'username' => 'admin',
            'password' => bcrypt('rahasia123'),
            'role_id' => Role::firstOrCreate(['name' => 'admin'])->id,
        ]);
        $this->superAppSays(['username' => 'admin', 'jenis' => 'pegawai']);

        $this->masuk()->assertSessionHasErrors('sso');
        $this->assertGuest();
    }

    public function test_siswa_aktif_masuk_ke_absensi_saya(): void
    {
        $student = $this->siswa();
        $this->superAppSays(['username' => 'siti', 'nisn' => '0012345678', 'jenis' => 'siswa', 'kelas' => 'VII A']);

        $this->masuk()->assertRedirect(route('student.portal.index'));

        $this->assertAuthenticatedAs($student, 'student');
        $this->assertGuest('web');
    }

    public function test_siswa_nonaktif_atau_lulus_ditolak(): void
    {
        $this->siswa(['is_active' => false, 'graduated_at' => '2026-06-20']);
        $this->superAppSays(['nisn' => '0012345678', 'jenis' => 'siswa']);

        $this->masuk()->assertSessionHasErrors(['sso' => 'Data siswa Anda di aplikasi Absensi sedang nonaktif. Hubungi admin Absensi.']);
        $this->assertGuest('student');
    }

    public function test_nisn_tidak_dikenal_ditolak(): void
    {
        $this->siswa();
        $this->superAppSays(['nisn' => '9999999999', 'jenis' => 'siswa']);

        $this->masuk()->assertSessionHasErrors('sso');
        $this->assertGuest('student');
    }

    public function test_alumni_ditolak(): void
    {
        $this->siswa();
        $this->superAppSays(['nisn' => '0012345678', 'jenis' => 'alumni']);

        $this->masuk()->assertSessionHasErrors(['sso' => 'Akun Super App Anda tidak berhak membuka aplikasi Absensi.']);
    }

    public function test_pesan_galat_super_app_diteruskan(): void
    {
        $this->superAppSays(['kode' => 'tiket_kedaluwarsa', 'pesan' => 'Tiket sudah kedaluwarsa.'], 400);

        $this->masuk()->assertSessionHasErrors(['sso' => 'Tiket sudah kedaluwarsa.']);
        $this->assertGuest();
    }

    public function test_tanpa_tiket_atau_belum_diatur(): void
    {
        Http::fake();

        $this->get('/sso/masuk')->assertSessionHasErrors('sso');

        config(['superapp_sso.kunci' => null]);
        $this->masuk()->assertSessionHasErrors(['sso' => 'Login lewat Super App belum diatur di aplikasi ini.']);

        Http::assertNothingSent();
    }

    public function test_tiket_berisi_dua_strip_tidak_diblokir_filter_keamanan(): void
    {
        $student = $this->siswa();
        $this->superAppSays(['nisn' => '0012345678', 'jenis' => 'siswa']);

        $this->masuk('Ab--cD_e-f')->assertRedirect(route('student.portal.index'));
        $this->assertAuthenticatedAs($student, 'student');
    }

    public function test_tombol_super_app_hanya_tampil_bila_diatur(): void
    {
        $this->get('/login')->assertOk()->assertSee('Masuk lewat Super App')->assertSee('href="https://superapp.test"', false);

        config(['superapp_sso.alamat_superapp' => 'https://superapp.test/dashboard']);
        $this->get('/login')->assertSee('href="https://superapp.test/dashboard"', false);

        config(['superapp_sso.kunci' => null]);
        $this->get('/login')->assertOk()->assertDontSee('Masuk lewat Super App');
    }

    public function test_absensi_saya_menampilkan_riwayat_siswa_sendiri(): void
    {
        $this->travelTo(Carbon::parse('2026-11-10 10:00:00')); // Tuesday
        foreach (range(1, 5) as $day) {
            Schedule::create(['type' => 'siswa', 'day_of_week' => $day, 'start_time' => '07:00:00', 'end_time' => '14:00:00']);
        }
        $siti = $this->siswa();
        $andi = $this->siswa(['nisn' => '0099999999', 'full_name' => 'Andi']);

        Attendance::create(['attendable_type' => Student::class, 'attendable_id' => $siti->id, 'date' => '2026-11-10', 'clock_in' => '2026-11-10 06:55:00', 'status_in' => 'Tepat Waktu']);
        Attendance::create(['attendable_type' => Student::class, 'attendable_id' => $siti->id, 'date' => '2026-11-09', 'clock_in' => '2026-11-09 07:20:00', 'status_in' => 'Terlambat']);
        Attendance::create(['attendable_type' => Student::class, 'attendable_id' => $andi->id, 'date' => '2026-11-09', 'clock_in' => '2026-11-09 06:31:00', 'status_in' => 'Tepat Waktu']);

        $page = $this->actingAs($siti, 'student')->get(route('student.portal.index'))->assertOk()
            ->assertSee('Siti Aminah')
            ->assertSee('06:55')
            ->assertSee('07:20')
            ->assertDontSee('06:31')
            ->assertDontSee('Andi');

        $row = $page->viewData('row');
        $this->assertSame(1, $row['counts']['H']);
        $this->assertSame(1, $row['counts']['T']);
        // Nov 2 - 6 (Mon-Fri) without attendance: alpa
        $this->assertSame(5, $row['counts']['A']);

        // Older month
        $this->get(route('student.portal.index', ['bulan' => '2026-10']))->assertOk()->assertSee('Oktober 2026');
    }

    public function test_absensi_saya_butuh_login_siswa(): void
    {
        $this->get(route('student.portal.index'))->assertRedirect(route('login'));

        // A teacher session is not a student session
        $employee = $this->guru();
        $this->actingAs($employee->user)->get(route('student.portal.index'))->assertRedirect(route('login'));
    }

    public function test_siswa_dinonaktifkan_setelah_masuk_dikeluarkan(): void
    {
        $student = $this->siswa();
        $this->actingAs($student, 'student');
        $student->update(['is_active' => false]);

        $this->get(route('student.portal.index'))->assertRedirect(route('login'))->assertSessionHasErrors('sso');
        $this->assertGuest('student');
    }

    public function test_siswa_bisa_keluar(): void
    {
        $student = $this->siswa();

        $this->actingAs($student, 'student')->post(route('student.portal.logout'))->assertRedirect(route('login'));
        $this->assertGuest('student');
    }

    public function test_login_password_biasa_tetap_jalan(): void
    {
        $employee = $this->guru();

        $this->post('/login', ['username' => 'budi', 'password' => 'rahasia123'])->assertRedirect(route('employee.dashboard'));
        $this->assertAuthenticatedAs($employee->user);
        $this->assertTrue(Auth::guard('student')->guest());
    }
}
