<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\AttendanceSetting;
use App\Models\Employee;
use App\Models\Grade;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Rombel;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Attendance and permission history keep the class a student was in at the
 * time, even after the student moves class or is promoted.
 */
class AttendanceClassHistoryTest extends TestCase
{
    use RefreshDatabase;

    private const LOCATION = ['latitude' => -6.2, 'longitude' => 106.816666, 'accuracy' => 5];

    private User $admin;
    private Rombel $viiA;
    private Rombel $viiiA;
    private Student $andi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin',
            'username' => 'admin',
            'password' => bcrypt('rahasia123'),
            'role_id' => Role::firstOrCreate(['name' => 'admin'])->id,
        ]);

        $year = AcademicYear::create(['year_name' => '2025/2026', 'status' => 'active']);
        [$g7, $g8] = Grade::orderBy('name')->get()->all();
        $this->viiA = Rombel::create(['academic_year_id' => $year->id, 'grade_id' => $g7->id, 'name' => 'VII A']);
        $this->viiiA = Rombel::create(['academic_year_id' => $year->id, 'grade_id' => $g8->id, 'name' => 'VIII A']);

        $this->andi = Student::create([
            'nisn' => '001',
            'full_name' => 'Andi',
            'rombel_id' => $this->viiA->id,
            'is_active' => true,
        ]);

        // Monday - Friday schedule so the attendance page is open at 08:00
        foreach (range(1, 5) as $day) {
            Schedule::create(['type' => 'siswa', 'day_of_week' => $day, 'start_time' => '07:00:00', 'end_time' => '14:00:00']);
        }
        AttendanceSetting::updateOrCreate(['setting_key' => 'require_selfie_photo'], ['setting_value' => 'false']);
    }

    /**
     * The report pages use PostgreSQL-only SQL (EXTRACT(YEAR FROM ...)), like
     * production. CI runs the suite on PostgreSQL; a local SQLite run skips these.
     */
    private function requirePostgres(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Report pages need PostgreSQL (run with DB_CONNECTION=pgsql).');
        }
    }

    private function clockInOn(string $date): void
    {
        $this->travelTo(Carbon::parse($date . ' 08:00:00'));
        $this->postJson('/student/attendance/clock-in', ['identifier' => '001', 'location' => self::LOCATION])
            ->assertJson(['success' => true]);
    }

    private function promoteAndi(): void
    {
        $this->actingAs($this->admin)->post(route('admin.students.promotion.process'), [
            'source_rombel_id' => $this->viiA->id,
            'student_ids' => [$this->andi->id],
            'action' => 'move',
            'target_rombel_id' => $this->viiiA->id,
        ])->assertSessionHas('success');
        auth()->logout();
    }

    public function test_attendance_keeps_the_class_at_the_time_after_promotion(): void
    {
        $this->requirePostgres();

        $this->clockInOn('2026-09-28');   // in VII A
        $this->promoteAndi();
        $this->clockInOn('2026-09-29');   // in VIII A

        $old = Attendance::whereDate('date', '2026-09-28')->first();
        $new = Attendance::whereDate('date', '2026-09-29')->first();
        $this->assertSame($this->viiA->id, $old->rombel_id);
        $this->assertSame($this->viiiA->id, $new->rombel_id);
        $this->assertSame('VII A', $old->rombel_name);

        // Report filtered by the old class still shows the old attendance only
        $report = fn (Rombel $rombel) => $this->actingAs($this->admin)
            ->get(route('admin.reports.students', ['month' => 9, 'year' => 2026, 'rombel_id' => $rombel->id]))
            ->assertOk()
            ->viewData('attendances');

        $viiRows = collect($report($this->viiA)->items());
        $this->assertSame([$old->id], $viiRows->pluck('id')->all());

        $viiiRows = collect($report($this->viiiA)->items());
        $this->assertSame([$new->id], $viiiRows->pluck('id')->all());
    }

    public function test_monitoring_report_uses_the_class_at_the_time(): void
    {
        $this->requirePostgres();

        $this->clockInOn('2026-09-28');
        $this->promoteAndi();

        $monitor = User::create([
            'name' => 'Monitor',
            'username' => 'monitor',
            'password' => bcrypt('rahasia123'),
            'role_id' => Role::firstOrCreate(['name' => 'monitoring'])->id,
        ]);

        $rows = $this->actingAs($monitor)
            ->get(route('monitoring.students', ['month' => 9, 'year' => 2026, 'rombel_id' => $this->viiA->id]))
            ->assertOk()
            ->assertSee('VII A')
            ->viewData('attendances');

        $this->assertCount(1, collect(method_exists($rows, 'items') ? $rows->items() : $rows));
    }

    public function test_student_permission_keeps_the_class_at_the_time(): void
    {
        $this->actingAs($this->admin)->post(route('admin.permissions.students.store'), [
            'student_id' => $this->andi->id,
            'start_date' => '2026-09-28',
            'end_date' => '2026-09-28',
            'reason' => 'Sakit',
        ]);

        $permission = Permission::first();
        $this->assertSame($this->viiA->id, $permission->rombel_id);

        $this->promoteAndi();
        $this->assertSame($this->viiA->id, $permission->fresh()->rombel_id);
        $this->assertSame('VII A', $permission->fresh()->rombel_name);
    }

    public function test_class_follows_the_new_student_when_a_record_is_reassigned(): void
    {
        $budi = Student::create(['nisn' => '002', 'full_name' => 'Budi', 'rombel_id' => $this->viiiA->id, 'is_active' => true]);

        $attendance = Attendance::create([
            'attendable_type' => Student::class,
            'attendable_id' => $this->andi->id,
            'date' => '2026-09-28',
        ]);
        $this->assertSame($this->viiA->id, $attendance->rombel_id);

        $attendance->update(['attendable_id' => $budi->id]);
        $this->assertSame($this->viiiA->id, $attendance->fresh()->rombel_id);
    }

    public function test_employee_records_have_no_class(): void
    {
        $user = User::create([
            'name' => 'Guru',
            'username' => 'guru',
            'password' => bcrypt('rahasia123'),
            'role_id' => Role::firstOrCreate(['name' => 'pegawai'])->id,
        ]);
        $employee = Employee::create(['user_id' => $user->id, 'full_name' => 'Guru', 'position' => 'Guru', 'is_active' => true]);

        $attendance = Attendance::create([
            'attendable_type' => Employee::class,
            'attendable_id' => $employee->id,
            'date' => '2026-09-28',
        ]);

        $this->assertNull($attendance->rombel_id);
    }
}
