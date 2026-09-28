<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Grade;
use App\Models\Role;
use App\Models\Rombel;
use App\Models\Schedule;
use App\Models\Setting;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The student schedule has its own page (one row per school day) and is
 * independent of the employee schedules and working days.
 */
class StudentScheduleTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin',
            'username' => 'admin',
            'password' => bcrypt('rahasia123'),
            'role_id' => Role::firstOrCreate(['name' => 'admin'])->id,
        ]);
    }

    private function days(array $active): array
    {
        $days = [];
        foreach (range(0, 6) as $day) {
            $days[$day] = in_array($day, $active, true)
                ? ['active' => '1', 'start_time' => '07:00', 'end_time' => '13:30']
                : ['active' => '0', 'start_time' => '07:00', 'end_time' => '13:30'];
        }

        return $days;
    }

    public function test_page_lists_every_day_with_its_schedule(): void
    {
        Schedule::create(['type' => 'siswa', 'day_of_week' => 1, 'start_time' => '07:15:00', 'end_time' => '14:00:00']);

        $this->actingAs($this->admin)
            ->get(route('admin.schedules.custom.siswa'))
            ->assertOk()
            ->assertSee('Senin')
            ->assertSee('Minggu')
            ->assertSee('07:15');
    }

    public function test_saving_sets_one_schedule_per_checked_day_and_leaves_employees_alone(): void
    {
        $employee = Schedule::create(['type' => 'pegawai', 'day_of_week' => 1, 'start_time' => '07:00:00', 'end_time' => '15:00:00']);
        // Leftovers from the old shared form: two schedules on Monday, one on Sunday
        Schedule::create(['type' => 'siswa', 'day_of_week' => 1, 'start_time' => '07:30:00', 'end_time' => '14:00:00', 'shift_name' => 'Pagi']);
        Schedule::create(['type' => 'siswa', 'day_of_week' => 1, 'start_time' => '12:00:00', 'end_time' => '16:00:00']);
        Schedule::create(['type' => 'siswa', 'day_of_week' => 0, 'start_time' => '07:30:00', 'end_time' => '14:00:00']);

        $this->actingAs($this->admin)
            ->put(route('admin.schedules.custom.siswa.update'), ['days' => $this->days([1, 2, 3, 4, 5, 6])])
            ->assertRedirect(route('admin.schedules.custom.siswa'))
            ->assertSessionHas('success');

        $student = Schedule::where('type', 'siswa')->orderBy('day_of_week')->get();
        $this->assertSame([1, 2, 3, 4, 5, 6], $student->pluck('day_of_week')->map(fn ($d) => (int) $d)->all());
        $this->assertSame('07:00', Carbon::parse($student->first()->start_time)->format('H:i'));
        $this->assertSame('13:30', Carbon::parse($student->first()->end_time)->format('H:i'));
        $this->assertNull($student->first()->shift_name);

        $this->assertModelExists($employee);
        $this->assertSame(1, Schedule::where('type', 'pegawai')->count());
    }

    public function test_end_time_must_be_after_start_time(): void
    {
        $days = $this->days([1]);
        $days[1]['end_time'] = '06:00';

        $this->actingAs($this->admin)
            ->from(route('admin.schedules.custom.siswa'))
            ->put(route('admin.schedules.custom.siswa.update'), ['days' => $days])
            ->assertSessionHasErrors('days.1.end_time');

        $this->assertSame(0, Schedule::where('type', 'siswa')->count());
    }

    public function test_general_schedule_form_is_for_employees_only(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.schedules.custom.store'), ['type' => 'siswa', 'start_time' => '07:00', 'end_time' => '14:00'])
            ->assertSessionHasErrors('type');

        $student = Schedule::create(['type' => 'siswa', 'day_of_week' => 1, 'start_time' => '07:00:00', 'end_time' => '14:00:00']);
        $this->actingAs($this->admin)
            ->get(route('admin.schedules.custom.edit', $student))
            ->assertRedirect(route('admin.schedules.custom.siswa'));
    }

    public function test_student_attendance_page_follows_student_days_not_employee_working_days(): void
    {
        $year = AcademicYear::create(['year_name' => '2025/2026', 'status' => 'active']);
        $rombel = Rombel::create(['academic_year_id' => $year->id, 'grade_id' => Grade::first()->id, 'name' => 'VII A']);
        Student::create(['nisn' => '001', 'full_name' => 'Andi', 'rombel_id' => $rombel->id, 'is_active' => true]);

        // Employees work Monday - Friday only, students also have school on Saturday
        Setting::set('active_days', json_encode([1, 2, 3, 4, 5]));
        $this->actingAs($this->admin)->put(route('admin.schedules.custom.siswa.update'), ['days' => $this->days([1, 2, 3, 4, 5, 6])]);
        auth()->logout();

        $this->travelTo(Carbon::parse('2026-10-03 08:00:00')); // Saturday
        $this->get('/student/attendance')->assertOk();

        $this->travelTo(Carbon::parse('2026-10-04 08:00:00')); // Sunday
        $this->get('/student/attendance')->assertForbidden();
    }
}
