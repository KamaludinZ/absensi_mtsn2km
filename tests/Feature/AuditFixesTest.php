<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\AttendanceSetting;
use App\Models\Grade;
use App\Models\Permission;
use App\Models\Rombel;
use App\Models\Schedule;
use App\Models\SecurityLog;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditFixesTest extends TestCase
{
    use RefreshDatabase;

    private function makeStudent(): Student
    {
        $year = AcademicYear::create(['year_name' => '2025/2026', 'status' => 'active']);
        $rombel = Rombel::create([
            'academic_year_id' => $year->id,
            'grade_id' => Grade::first()->id,
            'name' => 'VII A',
        ]);

        return Student::create([
            'nisn' => '0012345678',
            'full_name' => 'Siswa Uji',
            'rombel_id' => $rombel->id,
            'is_active' => true,
        ]);
    }

    public function test_student_can_clock_in(): void
    {
        $student = $this->makeStudent();
        Schedule::create([
            'type' => 'siswa',
            'day_of_week' => now()->dayOfWeek,
            'start_time' => '07:30:00',
            'end_time' => '14:00:00',
        ]);
        AttendanceSetting::updateOrCreate(
            ['setting_key' => 'require_selfie_photo'],
            ['setting_value' => 'false']
        );

        $response = $this->postJson('/student/attendance/clock-in', [
            'student_id' => $student->id,
            // Default geofence centre seeded by the location_settings migration
            'location' => ['latitude' => -6.2, 'longitude' => 106.816666, 'accuracy' => 5],
        ]);

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertTrue(
            Attendance::where('attendable_type', Student::class)
                ->where('attendable_id', $student->id)
                ->whereNotNull('clock_in')
                ->exists()
        );
    }

    public function test_guest_cannot_manage_student_permissions(): void
    {
        $student = $this->makeStudent();

        $this->get('/admin/permissions/students/create')->assertRedirect('/login');

        $this->post('/admin/permissions/students', [
            'student_id' => $student->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
            'reason' => 'uji',
        ])->assertRedirect('/login');

        $this->assertSame(0, Permission::count());
    }

    public function test_login_limit_is_per_username_not_whole_school(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['username' => 'guru1', 'password' => 'salah'])->assertRedirect();
        }

        // guru1 is now rate limited...
        $this->post('/login', ['username' => 'guru1', 'password' => 'salah'])->assertStatus(429);

        // ...but another teacher on the same school IP can still log in.
        $this->post('/login', ['username' => 'guru2', 'password' => 'salah'])->assertRedirect();
    }

    public function test_blocked_request_does_not_save_data_and_hides_password(): void
    {
        $student = $this->makeStudent();
        AttendanceSetting::updateOrCreate(
            ['setting_key' => 'require_selfie_photo'],
            ['setting_value' => 'false']
        );

        // Suspicious input must be rejected before the controller runs,
        // so nothing is saved.
        $this->post('/student/attendance/clock-in', [
            'student_id' => $student->id,
            'location' => ['latitude' => -6.2, 'longitude' => 106.816666, 'accuracy' => 5],
            'note' => 'x UNION SELECT password FROM users',
        ])->assertForbidden();
        $this->assertSame(0, Attendance::count());

        // Password values must never be written to the security log.
        $this->post('/login', [
            'username' => 'admin',
            'password' => 'rahasia--123',
        ])->assertForbidden();

        $log = SecurityLog::latest('id')->first();
        $this->assertNotNull($log);
        $this->assertStringNotContainsString('rahasia--123', json_encode($log->toArray()));
    }
}
