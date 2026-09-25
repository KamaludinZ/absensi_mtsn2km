<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\AttendanceDevice;
use App\Models\AttendanceSetting;
use App\Models\Grade;
use App\Models\Holiday;
use App\Models\Role;
use App\Models\Rombel;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\User;
use App\Services\StudentKiosk;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentKioskTest extends TestCase
{
    use RefreshDatabase;

    private const MONDAY = '2026-09-28';
    private const LOCATION = ['latitude' => -6.2, 'longitude' => 106.816666, 'accuracy' => 5];

    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        // Send cookies with JSON requests too, like a browser's same-origin fetch()
        $this->withCredentials();

        $year = AcademicYear::create(['year_name' => '2025/2026', 'status' => 'active']);
        $rombel = Rombel::create([
            'academic_year_id' => $year->id,
            'grade_id' => Grade::first()->id,
            'name' => 'VII A',
        ]);
        $this->student = Student::create([
            'nisn' => '0012345678',
            'full_name' => 'Siswa Uji',
            'rombel_id' => $rombel->id,
            'is_active' => true,
        ]);

        // Monday schedule 07:00 - 14:00, default window opens 60 min before / closes 60 min after
        Schedule::create(['type' => 'siswa', 'day_of_week' => 1, 'start_time' => '07:00:00', 'end_time' => '14:00:00']);
        AttendanceSetting::updateOrCreate(['setting_key' => 'require_selfie_photo'], ['setting_value' => 'false']);
    }

    private function at(string $time): void
    {
        $this->travelTo(Carbon::parse(self::MONDAY . ' ' . $time));
    }

    private function registeredDevice(): string
    {
        [, $token] = StudentKiosk::registerDevice('Tablet Lobi', null);

        return $token;
    }

    public function test_unregistered_device_cannot_open_or_submit(): void
    {
        $this->at('08:00:00');

        $this->get('/student/attendance')
            ->assertForbidden()
            ->assertSee('Perangkat tidak terdaftar');

        $this->postJson('/student/attendance/clock-in', ['nisn' => $this->student->nisn, 'location' => self::LOCATION])
            ->assertForbidden();

        $this->assertSame(0, Attendance::count());
    }

    public function test_deactivated_device_is_rejected(): void
    {
        $this->at('08:00:00');
        $token = $this->registeredDevice();
        AttendanceDevice::query()->update(['is_active' => false]);

        $this->withCookie(StudentKiosk::COOKIE, $token)->get('/student/attendance')->assertForbidden();
    }

    public function test_registered_device_can_clock_in_with_nisn(): void
    {
        $this->at('07:10:00');
        $token = $this->registeredDevice();

        $this->withCookie(StudentKiosk::COOKIE, $token)->get('/student/attendance')
            ->assertOk()
            ->assertSee('Masukkan NISN');

        $this->withCookie(StudentKiosk::COOKIE, $token)
            ->postJson('/student/attendance/get-student-info', ['nisn' => $this->student->nisn])
            ->assertOk()
            ->assertJson(['success' => true, 'student' => ['full_name' => 'Siswa Uji', 'rombel' => 'VII A']]);

        $this->withCookie(StudentKiosk::COOKIE, $token)
            ->postJson('/student/attendance/clock-in', ['nisn' => $this->student->nisn, 'location' => self::LOCATION])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSame(1, Attendance::whereNotNull('clock_in')->count());
    }

    public function test_unknown_or_inactive_nisn_is_rejected(): void
    {
        $this->at('08:00:00');
        $token = $this->registeredDevice();

        $this->withCookie(StudentKiosk::COOKIE, $token)
            ->postJson('/student/attendance/clock-in', ['nisn' => '9999999999', 'location' => self::LOCATION])
            ->assertJson(['success' => false]);

        $this->student->update(['is_active' => false]);
        $this->withCookie(StudentKiosk::COOKIE, $token)
            ->postJson('/student/attendance/clock-in', ['nisn' => $this->student->nisn, 'location' => self::LOCATION])
            ->assertJson(['success' => false]);

        $this->assertSame(0, Attendance::count());
    }

    public function test_page_is_closed_outside_attendance_hours(): void
    {
        $token = $this->registeredDevice();

        // 05:59 is before 07:00 - 60 min
        $this->at('05:59:00');
        $this->withCookie(StudentKiosk::COOKIE, $token)->get('/student/attendance')
            ->assertForbidden()
            ->assertSee('06:00');

        // 06:00 and 15:00 are the edges of the window
        $this->at('06:00:00');
        $this->withCookie(StudentKiosk::COOKIE, $token)->get('/student/attendance')->assertOk();
        $this->at('15:00:00');
        $this->withCookie(StudentKiosk::COOKIE, $token)->get('/student/attendance')->assertOk();

        // 15:01 is after 14:00 + 60 min
        $this->at('15:01:00');
        $this->withCookie(StudentKiosk::COOKIE, $token)
            ->postJson('/student/attendance/clock-out', ['nisn' => $this->student->nisn, 'location' => self::LOCATION])
            ->assertForbidden();
    }

    public function test_window_follows_admin_settings(): void
    {
        $token = $this->registeredDevice();
        AttendanceSetting::updateOrCreate(['setting_key' => 'student_open_minutes_before'], ['setting_value' => '15']);

        $this->at('06:30:00');
        $this->withCookie(StudentKiosk::COOKIE, $token)->get('/student/attendance')->assertForbidden();

        $this->at('06:45:00');
        $this->withCookie(StudentKiosk::COOKIE, $token)->get('/student/attendance')->assertOk();
    }

    public function test_page_is_closed_on_holidays_and_days_without_schedule(): void
    {
        $token = $this->registeredDevice();

        Holiday::create(['name' => 'Libur Uji', 'date' => self::MONDAY, 'is_active' => true]);
        $this->at('08:00:00');
        $this->withCookie(StudentKiosk::COOKIE, $token)->get('/student/attendance')
            ->assertForbidden()
            ->assertSee('Libur Uji');

        // Tuesday has no student schedule
        $this->travelTo(Carbon::parse('2026-09-29 08:00:00'));
        $this->withCookie(StudentKiosk::COOKIE, $token)->get('/student/attendance')->assertForbidden();
    }

    public function test_student_list_endpoints_are_gone(): void
    {
        $this->get('/api/students/by-rombel/' . $this->student->rombel_id)->assertNotFound();
        $this->get('/student/attendance/get-students/' . $this->student->rombel_id)->assertNotFound();
    }

    public function test_admin_can_register_the_current_browser_as_a_device(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'username' => 'admin',
            'password' => bcrypt('rahasia123'),
            'role_id' => Role::firstOrCreate(['name' => 'admin'])->id,
        ]);

        $response = $this->actingAs($admin)
            ->post('/admin/student-kiosk/devices', ['name' => 'Tablet Lobi'])
            ->assertRedirect('/admin/student-kiosk')
            ->assertCookie(StudentKiosk::COOKIE);

        $device = AttendanceDevice::first();
        $this->assertSame('Tablet Lobi', $device->name);

        // The cookie holds the plain token; only its hash is stored.
        $token = $response->getCookie(StudentKiosk::COOKIE)->getValue();
        $this->assertSame(StudentKiosk::hashToken($token), $device->token_hash);

        $this->actingAs($admin)->get('/admin/student-kiosk')->assertOk()->assertSee('Tablet Lobi');
    }
}
