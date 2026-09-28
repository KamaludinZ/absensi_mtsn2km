<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\Grade;
use App\Models\Holiday;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Rombel;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Student attendance recap: school days from the student schedule and
 * holidays, Sakit/Izin from approved student permissions, the rest Alpa.
 */
class StudentAttendanceRecapTest extends TestCase
{
    use RefreshDatabase;

    // Monday 28 Sep - Friday 2 Oct 2026, Wednesday is a holiday
    private const PERIOD = ['start_date' => '2026-09-28', 'end_date' => '2026-10-02'];

    private User $admin;
    private Rombel $viiA;
    private Rombel $viiB;
    private Rombel $viiiA;
    private Student $andi;
    private Student $budi;
    private Student $citra;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-10-02 15:00:00'));

        $this->admin = User::create([
            'name' => 'Admin',
            'username' => 'admin',
            'password' => bcrypt('rahasia123'),
            'role_id' => Role::firstOrCreate(['name' => 'admin'])->id,
        ]);

        $year = AcademicYear::create(['year_name' => '2026/2027', 'status' => 'active']);
        [$g7, $g8] = Grade::orderBy('name')->get()->all();
        $this->viiA = Rombel::create(['academic_year_id' => $year->id, 'grade_id' => $g7->id, 'name' => 'VII A']);
        $this->viiB = Rombel::create(['academic_year_id' => $year->id, 'grade_id' => $g7->id, 'name' => 'VII B']);
        $this->viiiA = Rombel::create(['academic_year_id' => $year->id, 'grade_id' => $g8->id, 'name' => 'VIII A']);

        $this->andi = Student::create(['nisn' => '001', 'full_name' => 'Andi', 'rombel_id' => $this->viiA->id, 'is_active' => true]);
        $this->budi = Student::create(['nisn' => '002', 'full_name' => 'Budi', 'rombel_id' => $this->viiB->id, 'is_active' => true]);
        $this->citra = Student::create(['nisn' => '003', 'full_name' => 'Citra', 'rombel_id' => $this->viiiA->id, 'is_active' => true]);

        foreach (range(1, 5) as $day) {
            Schedule::create(['type' => 'siswa', 'day_of_week' => $day, 'start_time' => '07:00:00', 'end_time' => '14:00:00']);
        }
        Holiday::create(['name' => 'Libur Uji', 'date' => '2026-09-30', 'is_active' => true]);
    }

    private function attend(Student $student, string $date, string $status = 'Tepat Waktu'): void
    {
        Attendance::create([
            'attendable_type' => Student::class,
            'attendable_id' => $student->id,
            'date' => $date,
            'clock_in' => $date . ' 07:05:00',
            'status_in' => $status,
        ]);
    }

    private function permission(Student $student, string $from, string $to, string $type, string $status = 'approved'): Permission
    {
        return Permission::create([
            'permissible_type' => Student::class,
            'permissible_id' => $student->id,
            'start_date' => $from,
            'end_date' => $to,
            'reason' => 'Uji',
            'type' => $type,
            'status' => $status,
        ]);
    }

    private function recap(array $filters = [])
    {
        return $this->actingAs($this->admin)
            ->get(route('admin.reports.students', self::PERIOD + $filters))
            ->assertOk();
    }

    private function rowFor($response, Student $student): array
    {
        return $response->viewData('rows')->firstWhere('student.id', $student->id);
    }

    public function test_counts_present_late_sick_excused_and_absent_days(): void
    {
        $this->attend($this->andi, '2026-09-28');                 // Monday: on time
        $this->attend($this->andi, '2026-09-29', 'Terlambat');    // Tuesday: late
        $this->permission($this->andi, '2026-09-30', '2026-10-01', 'S'); // Wed holiday + Thursday sick
        // Friday: nothing -> Alpa

        $row = $this->rowFor($this->recap(['student_id' => $this->andi->id]), $this->andi);

        $this->assertSame(4, $row['effective_days']); // holiday is not a school day
        $this->assertSame(['H' => 1, 'T' => 1, 'S' => 1, 'I' => 0, 'A' => 1, 'M' => 0], $row['counts']);
        $this->assertEquals(50.0, $row['percentage']);
    }

    public function test_pending_permission_is_flagged_not_counted_as_absent(): void
    {
        $this->permission($this->andi, '2026-10-02', '2026-10-02', 'I', 'pending');
        $this->permission($this->andi, '2026-09-28', '2026-09-28', 'I', 'rejected');

        $response = $this->recap(['rombel_id' => $this->viiA->id])->assertSee('belum disetujui');
        $row = $this->rowFor($response, $this->andi);

        $this->assertSame(1, $row['counts']['M']);
        $this->assertSame(3, $row['counts']['A']); // rejected permission stays Alpa
        $this->assertSame(1, $response->viewData('pendingPermissions'));
    }

    public function test_permission_made_by_admin_counts_in_the_recap(): void
    {
        $this->actingAs($this->admin)->post(route('admin.permissions.students.store'), [
            'student_id' => $this->andi->id,
            'type' => 'I',
            'status' => 'approved',
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-02',
            'reason' => 'Acara keluarga',
        ])->assertRedirect(route('admin.permissions.students'));

        $permission = Permission::first();
        $this->assertSame('approved', $permission->status);
        $this->assertSame('I', $permission->type);
        $this->assertSame($this->admin->id, $permission->approved_by);
        $this->assertSame($this->viiA->id, $permission->rombel_id);

        $row = $this->rowFor($this->recap(['rombel_id' => $this->viiA->id]), $this->andi);
        $this->assertSame(2, $row['counts']['I']);

        // Editing it to Sakit moves the days to the Sakit column
        $this->actingAs($this->admin)->put(route('admin.permissions.students.update', $permission), [
            'student_id' => $this->andi->id,
            'type' => 'S',
            'status' => 'approved',
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-02',
            'reason' => 'Demam',
        ])->assertRedirect(route('admin.permissions.students'));

        $row = $this->rowFor($this->recap(['rombel_id' => $this->viiA->id]), $this->andi);
        $this->assertSame(0, $row['counts']['I']);
        $this->assertSame(2, $row['counts']['S']);
    }

    public function test_grade_and_rombel_filters(): void
    {
        $grade7 = $this->viiA->grade_id;

        $names = fn ($response) => $response->viewData('rows')->pluck('student.full_name')->all();

        $this->assertSame(['Andi', 'Budi'], $names($this->recap(['grade_id' => $grade7])));
        $this->assertSame(['Budi'], $names($this->recap(['grade_id' => $grade7, 'rombel_id' => $this->viiB->id])));
        $this->assertSame(['Andi', 'Budi', 'Citra'], $names($this->recap()));
    }

    public function test_records_from_a_previous_class_stay_with_that_class(): void
    {
        $this->attend($this->andi, '2026-09-28'); // in VII A
        $this->andi->update(['rombel_id' => $this->viiiA->id]);
        $this->attend($this->andi, '2026-09-29'); // in VIII A

        $old = $this->rowFor($this->recap(['rombel_id' => $this->viiA->id]), $this->andi);
        $this->assertSame(1, $old['counts']['H']);

        $new = $this->rowFor($this->recap(['rombel_id' => $this->viiiA->id]), $this->andi);
        $this->assertSame(1, $new['counts']['H']);
    }

    public function test_recap_exports_to_excel_and_pdf(): void
    {
        $this->attend($this->andi, '2026-09-28');

        foreach ([['rombel_id' => $this->viiA->id], ['student_id' => $this->andi->id]] as $filters) {
            $excel = $this->actingAs($this->admin)
                ->get(route('admin.reports.students.export', self::PERIOD + $filters + ['format' => 'excel']))
                ->assertOk();
            $this->assertStringContainsString('spreadsheetml', $excel->headers->get('content-type'));

            $pdf = $this->actingAs($this->admin)
                ->get(route('admin.reports.students.export', self::PERIOD + $filters + ['format' => 'pdf']))
                ->assertOk();
            $this->assertSame('application/pdf', $pdf->headers->get('content-type'));
        }
    }
}
