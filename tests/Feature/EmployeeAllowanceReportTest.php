<?php

namespace Tests\Feature;

use App\Exports\AttendanceExport;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\EmployeeShift;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Salary;
use App\Models\Schedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

/**
 * Allowance reports (uang makan, uang transport, tunjangan kinerja) count
 * days the same way on the summary page, the PDF and the detail page, and
 * judge late arrival / early departure like the /admin/reports screen:
 * from the clock time against the assigned shift, not the stored status.
 */
class EmployeeAllowanceReportTest extends TestCase
{
    use RefreshDatabase;

    private const PERIOD = ['month' => 10, 'year' => 2026];

    private User $admin;
    private Employee $employee;
    private Schedule $pagi;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-11-05 10:00:00'));

        $this->admin = User::create([
            'name' => 'Admin',
            'username' => 'admin',
            'password' => bcrypt('rahasia123'),
            'role_id' => Role::firstOrCreate(['name' => 'admin'])->id,
        ]);

        $this->employee = Employee::create(['full_name' => 'Siti', 'position' => 'Guru', 'is_active' => true]);
        Salary::create([
            'employee_id' => $this->employee->id,
            'month' => 10,
            'year' => 2026,
            'base_salary' => 0,
            'meal_allowance' => 20000,
            'transport_allowance' => 15000,
            'performance_allowance' => 50000,
        ]);

        // Default day schedule starts later than the assigned shift: the status
        // saved at clock-in is checked against this one and is therefore wrong.
        foreach (range(1, 5) as $day) {
            Schedule::create(['type' => 'pegawai', 'day_of_week' => $day, 'start_time' => '07:30:00', 'end_time' => '15:00:00']);
        }
        $this->pagi = Schedule::create([
            'type' => 'pegawai', 'day_of_week' => 1, 'shift_type' => 'pagi', 'shift_name' => 'Shift Pagi',
            'start_time' => '07:00:00', 'end_time' => '15:00:00',
        ]);

        // Monday 5 - Friday 9, Monday 12, Tuesday 13 October 2026 are shift days
        foreach (['05', '06', '07', '08', '09', '12', '13'] as $d) {
            $this->shift("2026-10-$d", $this->pagi);
        }

        // 5: clocked in 07:10 but stored "Tepat Waktu" (checked against 07:30)
        $this->attend('2026-10-05', '07:10:00', '15:00:00', 'Tepat Waktu', 'Tepat Waktu');
        // 6: times edited by an admin, status cleared
        $this->attend('2026-10-06', '07:20:00', '15:10:00', null, null);
        // 7: on time both ways, stale stored statuses
        $this->attend('2026-10-07', '06:50:00', '15:05:00', 'Terlambat', 'Pulang Awal');
        // 8: exactly at the start time, left early
        $this->attend('2026-10-08', '07:00:00', '14:30:00', 'Tepat Waktu', 'Tepat Waktu');
        // 9: no attendance at all -> Tidak Hadir
        // 12: approved sick leave Saturday 10 - Monday 12;
        // only Monday has a shift, so only Monday counts as Sakit
        Permission::create([
            'permissible_type' => Employee::class, 'permissible_id' => $this->employee->id,
            'start_date' => '2026-10-10', 'end_date' => '2026-10-12',
            'reason' => 'Demam', 'status' => 'approved', 'type' => 'S',
        ]);
        // 13: marked Dinas Luar on /admin/reports
        $this->attend('2026-10-13', null, null, 'DL', 'DL');

        // Saturday 10: attended without an assigned shift -> not counted
        $this->attend('2026-10-10', '08:00:00', '12:00:00', 'Tepat Waktu', 'Tepat Waktu');
        // Wednesday 14: shift whose schedule has no name -> not an active day
        $nameless = Schedule::create(['type' => 'pegawai', 'day_of_week' => 3, 'start_time' => '07:00:00', 'end_time' => '15:00:00']);
        $this->shift('2026-10-14', $nameless);
        $this->attend('2026-10-14', '06:55:00', '15:00:00', 'Tepat Waktu', 'Tepat Waktu');
    }

    private function shift(string $date, Schedule $schedule): void
    {
        EmployeeShift::create(['employee_id' => $this->employee->id, 'schedule_id' => $schedule->id, 'date' => $date, 'is_active' => true]);
    }

    private function attend(string $date, ?string $in, ?string $out, ?string $statusIn, ?string $statusOut): void
    {
        Attendance::create([
            'attendable_type' => Employee::class,
            'attendable_id' => $this->employee->id,
            'date' => $date,
            'clock_in' => $in ? "$date $in" : null,
            'clock_out' => $out ? "$date $out" : null,
            'status_in' => $statusIn,
            'status_out' => $statusOut,
        ]);
    }

    /**
     * The summary row as the report renders it. Taken from the PDF download,
     * which builds its rows with the same code as the summary page (the page
     * itself also runs a PostgreSQL-only year query that SQLite cannot run).
     */
    private function summaryRow(string $report, string $key): array
    {
        $captured = null;
        View::composer("admin.reports.$report-pdf", function ($view) use (&$captured, $key) {
            $captured = $view->getData()[$key];
        });

        $this->actingAs($this->admin)
            ->get(route("admin.reports.$report.download-pdf", self::PERIOD))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->assertCount(1, $captured);

        return $captured[0];
    }

    private function detail(string $route): array
    {
        $params = ['employee' => $this->employee->id, 'year' => 2026, 'month' => 10];
        $response = $this->actingAs($this->admin)->get(route($route, $params))->assertOk();

        return [
            collect($response->viewData('detailData'))->keyBy(fn ($row) => $row['date']->format('Y-m-d')),
            $response->viewData('summary'),
        ];
    }

    public function test_performance_allowance_counts_late_arrivals_like_the_attendance_report(): void
    {
        $row = $this->summaryRow('performance-allowance', 'performanceData');

        $this->assertSame(7, $row['active_days']);
        $this->assertSame(4, $row['present_days']);
        $this->assertSame(1, $row['absent_days']);
        $this->assertSame(2, $row['on_time_arrival_days'], '7 and 8 Oct (07:00:00 is exactly on time)');
        $this->assertSame(2, $row['late_arrival_days'], '5 and 6 Oct are late');
        $this->assertSame(3, $row['on_time_departure_days']);
        $this->assertSame(1, $row['early_departure_days']);
        $this->assertSame(1, $row['on_time_both_days']);
        $this->assertSame(1, $row['sick_days']);
        $this->assertSame(0, $row['leave_days']);
        $this->assertSame(1, $row['official_duty_days']);
        $this->assertEquals(50000, $row['total_performance_allowance']);
    }

    public function test_performance_detail_matches_summary(): void
    {
        [$days, $summary] = $this->detail('admin.reports.performance-allowance.detail');

        $this->assertSame('Terlambat', $days['2026-10-05']['status_in']);
        $this->assertSame('Terlambat', $days['2026-10-06']['status_in']);
        $this->assertSame('Tepat Waktu', $days['2026-10-06']['status_out']);
        $this->assertSame('Tepat Waktu', $days['2026-10-07']['status_in']);
        $this->assertSame('Tepat Waktu', $days['2026-10-07']['status_out']);
        $this->assertSame('Tepat Waktu', $days['2026-10-08']['status_in']);
        $this->assertSame('Pulang Awal', $days['2026-10-08']['status_out']);
        $this->assertSame('Tidak Hadir', $days['2026-10-09']['status_in']);
        $this->assertSame('Sakit', $days['2026-10-12']['status_in']);
        $this->assertSame('Dinas Luar', $days['2026-10-13']['status_in']);
        $this->assertSame('Tanpa Shift', $days['2026-10-10']['status_in']);

        $this->assertEquals(50000, $days['2026-10-07']['daily_performance_allowance']);
        $this->assertEquals(50000, $summary['total_performance_allowance']);
        $this->assertEquals(80000, $summary['total_meal_allowance']);
    }

    public function test_meal_allowance_summary_and_detail_agree(): void
    {
        $row = $this->summaryRow('meal-allowance', 'mealAllowanceData');
        $this->assertSame(7, $row['active_days']);
        $this->assertSame(4, $row['present_days']);
        $this->assertSame(1, $row['absent_days']);
        $this->assertSame(1, $row['sick_days']);
        $this->assertSame(1, $row['official_duty_days']);
        $this->assertEquals(80000, $row['total_meal_allowance']);

        [$days, $summary] = $this->detail('admin.reports.meal-allowance.detail');
        $this->assertEquals(80000, $summary['total_meal_allowance']);
        $this->assertEquals(0, $days['2026-10-14']['daily_meal_allowance'], 'nameless shift is not an active day');
    }

    public function test_transport_allowance_summary_and_detail_agree(): void
    {
        $row = $this->summaryRow('transport-allowance', 'transportAllowanceData');
        $this->assertSame(4, $row['present_days']);
        $this->assertEquals(60000, $row['total_transport_allowance']);

        [$days, $summary] = $this->detail('admin.reports.transport-allowance.detail');
        $this->assertEquals(60000, $summary['total_transport_allowance']);
        $this->assertEquals(0, $days['2026-10-10']['daily_transport_allowance'], 'no shift on Saturday');
    }

    public function test_salary_reports_use_the_same_counts(): void
    {
        $row = $this->summaryRow('salary', 'salaryData');
        $this->assertSame(4, $row['present_days']);
        $this->assertSame(1, $row['perfect_days']);
        $this->assertEquals(190000, $row['total_salary'], '80.000 makan + 60.000 transport + 50.000 kinerja');

        [$days, $summary] = $this->detail('admin.reports.salary.detail');
        $this->assertEquals(190000, $summary['total_salary']);
        $this->assertSame('Terlambat', $days['2026-10-05']['status_in']);
        $this->assertSame('Hari Libur', $days['2026-10-11']['status_in'], 'Sunday inside the sick leave, no shift');
    }

    public function test_employee_sees_the_same_salary_detail(): void
    {
        $user = User::create([
            'name' => 'Siti',
            'username' => 'siti',
            'password' => bcrypt('rahasia123'),
            'role_id' => Role::firstOrCreate(['name' => 'pegawai'])->id,
        ]);
        $this->employee->update(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->get(route('employee.salary.report.detail', ['year' => 2026, 'month' => 10]))
            ->assertOk();

        $this->assertEquals(190000, $response->viewData('summary')['total_salary']);
        $this->assertEquals(80000, $response->viewData('summary')['total_meal_allowance']);
    }

    public function test_attendance_exports_show_the_status_of_the_report_screen(): void
    {
        $attendance = Attendance::whereDate('date', '2026-10-05')->first();
        $row = (new AttendanceExport(collect([$attendance]), 'employee', null, null))->map($attendance);

        $this->assertSame('Shift Pagi', $row[3]);
        $this->assertSame('07:00:00', $row[4]);
        $this->assertSame('Terlambat', $row[7], 'stored "Tepat Waktu" was checked against 07:30');
        $this->assertSame('Tepat Waktu', $row[12]);

        $captured = null;
        View::composer('employee.report-export-pdf', function ($view) use (&$captured) {
            $captured = collect($view->getData()['attendances'])->keyBy('date');
        });
        $this->actingAs($this->admin)->post(route('admin.reports.generate'), [
            'type' => 'employee', 'format' => 'pdf', 'month' => 10, 'year' => 2026, 'employee_id' => $this->employee->id,
        ])->assertOk();

        $this->assertSame('Terlambat', $captured['05/10/2026']['status_in']);
        $this->assertSame('Terlambat', $captured['06/10/2026']['status_in'], 'status cleared by an edit');
        $this->assertSame('Tepat Waktu', $captured['06/10/2026']['status_out']);
        $this->assertSame('Tepat Waktu', $captured['08/10/2026']['status_in'], 'exactly 07:00:00');
        $this->assertSame('Dinas Luar', $captured['13/10/2026']['status_in']);
    }
}
