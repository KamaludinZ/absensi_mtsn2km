<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\EmployeeShift;
use App\Models\Holiday;
use App\Models\Role;
use App\Models\Salary;
use App\Models\Schedule;
use App\Models\Setting;
use App\Models\User;
use App\Services\EmployeeShiftAutoFill;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Shifts with Hari Penerapan and Jabatan Penerapan fill /admin/employee-shifts
 * by themselves: active employees with that position, today onwards, active
 * days that are not holidays, empty cells only, each date handled once.
 */
class EmployeeShiftAutoFillTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Employee $guru;
    private Employee $satpam;
    private Employee $guruNonaktif;
    private Employee $staf;
    private Schedule $pagi;
    private Schedule $jaga;

    protected function setUp(): void
    {
        parent::setUp();
        // Thursday 5 November 2026
        $this->travelTo(Carbon::parse('2026-11-05 06:00:00'));

        $this->admin = User::create([
            'name' => 'Admin',
            'username' => 'admin',
            'password' => bcrypt('rahasia123'),
            'role_id' => Role::firstOrCreate(['name' => 'admin'])->id,
        ]);

        $this->guru = Employee::create(['full_name' => 'Ani', 'position' => 'Guru', 'is_active' => true]);
        $this->satpam = Employee::create(['full_name' => 'Budi', 'position' => 'Satpam ', 'is_active' => true]);
        $this->guruNonaktif = Employee::create(['full_name' => 'Cici', 'position' => 'guru', 'is_active' => false]);
        $this->staf = Employee::create(['full_name' => 'Dedi', 'position' => 'Staf TU', 'is_active' => true]);

        Setting::set('active_days', json_encode([1, 2, 3, 4, 5]));
        Holiday::create(['name' => 'Libur Uji', 'date' => '2026-11-10', 'is_active' => true]);

        $this->pagi = Schedule::create([
            'type' => 'pegawai', 'day_of_week' => 0, 'shift_name' => 'Shift Pagi', 'start_time' => '07:00', 'end_time' => '15:00',
            'apply_days' => [1, 2, 3, 4, 5], 'apply_positions' => ['Guru'],
        ]);
        $this->jaga = Schedule::create([
            'type' => 'pegawai', 'day_of_week' => 0, 'shift_name' => 'Shift Jaga', 'start_time' => '06:00', 'end_time' => '14:00',
            'apply_days' => [1, 2, 3, 4, 5, 6, 7], 'apply_positions' => ['Satpam'],
        ]);
    }

    private function shiftsOf(Employee $employee): array
    {
        return EmployeeShift::where('employee_id', $employee->id)->orderBy('date')->get()
            ->mapWithKeys(fn ($s) => [$s->date->format('Y-m-d') => $s->schedule_id])
            ->all();
    }

    public function test_fills_active_employees_by_position_from_today_on_active_non_holiday_days(): void
    {
        $count = EmployeeShiftAutoFill::fillPending(2026, 11);

        // Weekdays 5..30 Nov = 18, minus the holiday on Tuesday 10
        $guru = $this->shiftsOf($this->guru);
        $this->assertCount(17, $guru);
        $this->assertSame('2026-11-05', array_key_first($guru), 'nothing before today');
        $this->assertArrayNotHasKey('2026-11-10', $guru, 'holiday');
        $this->assertArrayNotHasKey('2026-11-07', $guru, 'Saturday is not an active day');
        $this->assertSame([$this->pagi->id], array_values(array_unique($guru)));

        // Satpam applies all week, but weekends are not active days; position matched despite the trailing space
        $this->assertCount(17, $this->shiftsOf($this->satpam));
        $this->assertSame([], $this->shiftsOf($this->guruNonaktif), 'inactive employee');
        $this->assertSame([], $this->shiftsOf($this->staf), 'position without a shift');
        $this->assertSame(34, $count);

        // Same empty attendance rows as a manual save
        $placeholder = Attendance::where('attendable_id', $this->guru->id)->whereDate('date', '2026-11-05')->first();
        $this->assertSame('Shift Pagi', $placeholder->shift_name);
        $this->assertNull($placeholder->clock_in);
    }

    public function test_never_replaces_assigned_cells_and_keeps_cells_cleared_by_hand(): void
    {
        EmployeeShift::create(['employee_id' => $this->guru->id, 'schedule_id' => $this->jaga->id, 'date' => '2026-11-06', 'is_active' => true]);

        EmployeeShiftAutoFill::fillPending(2026, 11);
        $this->assertSame($this->jaga->id, $this->shiftsOf($this->guru)['2026-11-06'], 'manual shift kept');

        // Admin clears a day; the daily run does not bring it back
        EmployeeShift::where('employee_id', $this->guru->id)->whereDate('date', '2026-11-12')->delete();
        $this->assertSame(0, EmployeeShiftAutoFill::fillPending(2026, 11));
        $this->assertArrayNotHasKey('2026-11-12', $this->shiftsOf($this->guru));

        // ... until "Terapkan Otomatis" is used on purpose
        $this->assertSame(1, EmployeeShiftAutoFill::fillMonth(2026, 11));
        $this->assertSame($this->pagi->id, $this->shiftsOf($this->guru)['2026-11-12']);
    }

    public function test_without_rules_nothing_is_filled_and_dates_stay_pending(): void
    {
        Schedule::query()->update(['apply_days' => null, 'apply_positions' => null]);
        $this->assertSame(0, EmployeeShiftAutoFill::fillPending(2026, 11));

        $this->pagi->fresh()->update(['apply_days' => [1, 2, 3, 4, 5], 'apply_positions' => ['Guru']]);
        $this->assertSame(17, EmployeeShiftAutoFill::fillPending(2026, 11));
    }

    public function test_opening_the_shift_page_fills_the_month_and_button_fills_new_employees(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.employee-shifts.index', ['month' => 11, 'year' => 2026]))
            ->assertOk()
            ->assertSee('Terapkan Otomatis');
        $this->assertCount(17, $this->shiftsOf($this->guru));

        $baru = Employee::create(['full_name' => 'Eko', 'position' => 'Guru', 'is_active' => true]);
        $this->actingAs($this->admin)
            ->post(route('admin.employee-shifts.auto-fill'), ['month' => 11, 'year' => 2026])
            ->assertRedirect()
            ->assertSessionHas('success');
        $this->assertCount(17, $this->shiftsOf($baru));
    }

    public function test_daily_command_fills_the_current_month(): void
    {
        $this->artisan('shifts:auto-fill')->assertSuccessful();
        $this->assertCount(17, $this->shiftsOf($this->guru));
    }

    public function test_schedule_form_saves_days_and_positions_and_rejects_overlaps(): void
    {
        $this->actingAs($this->admin)->post(route('admin.schedules.custom.store'), [
            'type' => 'pegawai', 'shift_name' => 'Shift Siang', 'start_time' => '12:00', 'end_time' => '18:00',
            'apply_days' => [6], 'apply_positions' => ['Guru', 'Staf TU'],
        ])->assertRedirect(route('admin.schedules.custom.index'))->assertSessionHasNoErrors();

        $siang = Schedule::where('shift_name', 'Shift Siang')->first();
        $this->assertSame([6], $siang->apply_days);
        $this->assertSame(['Guru', 'Staf TU'], $siang->apply_positions);

        // Guru already has Shift Pagi on Monday
        $this->actingAs($this->admin)->post(route('admin.schedules.custom.store'), [
            'type' => 'pegawai', 'shift_name' => 'Shift Lain', 'start_time' => '08:00', 'end_time' => '16:00',
            'apply_days' => [1], 'apply_positions' => ['guru'],
        ])->assertSessionHasErrors('apply_positions');

        // Every shift needs a name, and names are unique
        $this->actingAs($this->admin)->post(route('admin.schedules.custom.store'), [
            'type' => 'pegawai', 'start_time' => '08:00', 'end_time' => '16:00',
        ])->assertSessionHasErrors('shift_name');
        $this->actingAs($this->admin)->post(route('admin.schedules.custom.store'), [
            'type' => 'pegawai', 'shift_name' => ' shift pagi ', 'start_time' => '08:00', 'end_time' => '16:00',
        ])->assertSessionHasErrors('shift_name');

        // Days and positions go together
        $this->actingAs($this->admin)->post(route('admin.schedules.custom.store'), [
            'type' => 'pegawai', 'shift_name' => 'Shift Sabtu', 'start_time' => '08:00', 'end_time' => '16:00',
            'apply_days' => [6],
        ])->assertSessionHasErrors('apply_positions');

        // Editing a shift does not conflict with itself
        $this->actingAs($this->admin)->put(route('admin.schedules.custom.update', $this->pagi), [
            'type' => 'pegawai', 'shift_name' => 'Shift Pagi', 'start_time' => '07:00', 'end_time' => '15:00',
            'apply_days' => [1, 2, 3, 4], 'apply_positions' => ['Guru'],
        ])->assertSessionHasNoErrors();
        $this->assertSame([1, 2, 3, 4], $this->pagi->fresh()->apply_days);

        $this->actingAs($this->admin)->get(route('admin.schedules.custom.pegawai'))
            ->assertOk()
            ->assertSee('Hari Penerapan')
            ->assertSee('Senin - Kamis');
    }

    public function test_saving_the_shift_table_keeps_recorded_attendance(): void
    {
        foreach (['2026-11-02', '2026-11-03', '2026-11-04'] as $date) {
            EmployeeShift::create(['employee_id' => $this->guru->id, 'schedule_id' => $this->pagi->id, 'date' => $date, 'is_active' => true]);
        }
        $kept = Attendance::create(['attendable_type' => Employee::class, 'attendable_id' => $this->guru->id, 'date' => '2026-11-02', 'clock_in' => '2026-11-02 06:55:00']);
        $clockedRemoved = Attendance::create(['attendable_type' => Employee::class, 'attendable_id' => $this->guru->id, 'date' => '2026-11-03', 'clock_in' => '2026-11-03 07:05:00']);
        $emptyRemoved = Attendance::create(['attendable_type' => Employee::class, 'attendable_id' => $this->guru->id, 'date' => '2026-11-04']);

        // Keep the 2nd, remove the 3rd and 4th
        $this->actingAs($this->admin)->post(route('admin.employee-shifts.store'), [
            'month' => 11, 'year' => 2026,
            "shift_{$this->guru->id}_2" => $this->pagi->id,
        ])->assertRedirect();

        $this->assertNotNull($kept->fresh(), 'attendance of a kept shift is never deleted');
        $this->assertSame('06:55:00', $kept->fresh()->clock_in->format('H:i:s'));
        $this->assertNotNull($clockedRemoved->fresh(), 'attendance with a clock-in is kept even when its shift is removed');
        $this->assertNull($emptyRemoved->fresh(), 'empty row of a removed shift goes');
    }

    public function test_salary_settings_offers_previous_month_values(): void
    {
        Salary::create([
            'employee_id' => $this->guru->id, 'month' => 10, 'year' => 2026,
            'base_salary' => 3000000, 'meal_allowance' => 20000, 'transport_allowance' => 15000, 'performance_allowance' => 50000,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.employees.salary-settings', ['month' => 11, 'year' => 2026]))
            ->assertOk()
            ->assertSee('Terapkan dari Oktober 2026');

        $previous = $response->viewData('previousSalaries');
        $this->assertSame(
            ['base_salary' => 3000000, 'meal_allowance' => 20000, 'transport_allowance' => 15000, 'performance_allowance' => 50000],
            $previous[$this->guru->id]
        );
        $this->assertArrayNotHasKey($this->satpam->id, $previous->all());

        // Nothing is stored until the form is saved
        $this->assertSame(0, Salary::where('month', 11)->count());
    }

    public function test_shift_page_dates_follow_active_days_and_holidays(): void
    {
        $nameless = Schedule::create(['type' => 'pegawai', 'day_of_week' => 1, 'start_time' => '07:30', 'end_time' => '15:00']);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.employee-shifts.index', ['month' => 11, 'year' => 2026]))
            ->assertOk()
            ->assertSee('Libur: Libur Uji');

        $offDays = $response->viewData('offDays');
        $this->assertSame('Bukan hari aktif', $offDays['2026-11-07']);
        $this->assertSame('Libur: Libur Uji', $offDays['2026-11-10']);
        $this->assertArrayNotHasKey('2026-11-09', $offDays);

        $this->assertNotContains($nameless->id, $response->viewData('schedules')->pluck('id')->all(), 'unnamed schedules cannot be picked');
    }

    public function test_saving_leaves_closed_dates_alone(): void
    {
        // Satpam worked on the holiday: shift and attendance stay
        EmployeeShift::create(['employee_id' => $this->satpam->id, 'schedule_id' => $this->jaga->id, 'date' => '2026-11-10', 'is_active' => true]);
        Attendance::create(['attendable_type' => Employee::class, 'attendable_id' => $this->satpam->id, 'date' => '2026-11-10', 'clock_in' => '2026-11-10 06:00:00']);

        $this->actingAs($this->admin)->post(route('admin.employee-shifts.store'), [
            'month' => 11, 'year' => 2026,
            "shift_{$this->guru->id}_9" => $this->pagi->id,
            "shift_{$this->guru->id}_10" => $this->pagi->id,  // holiday: ignored
            "shift_{$this->guru->id}_7" => $this->pagi->id,   // Saturday: ignored
        ])->assertRedirect();

        $this->assertSame(['2026-11-09' => $this->pagi->id], $this->shiftsOf($this->guru));
        $this->assertSame(['2026-11-10' => $this->jaga->id], $this->shiftsOf($this->satpam));
    }

    public function test_holiday_changes_release_and_restore_shifts(): void
    {
        EmployeeShiftAutoFill::fillPending(2026, 11);
        Attendance::where('attendable_id', $this->satpam->id)->whereDate('date', '2026-11-12')
            ->update(['clock_in' => '2026-11-12 06:02:00']);

        // New holiday: shifts go, except for whoever already worked that day
        $holiday = Holiday::create(['name' => 'Libur Baru', 'date' => '2026-11-12', 'is_active' => true]);
        $this->assertArrayNotHasKey('2026-11-12', $this->shiftsOf($this->guru));
        $this->assertNull(Attendance::where('attendable_id', $this->guru->id)->whereDate('date', '2026-11-12')->first(), 'empty row removed');
        $this->assertArrayHasKey('2026-11-12', $this->shiftsOf($this->satpam));

        // Moved to another day: the old date is filled again, the new one released
        $holiday->update(['date' => '2026-11-13']);
        $this->assertSame($this->pagi->id, $this->shiftsOf($this->guru)['2026-11-12']);
        $this->assertArrayNotHasKey('2026-11-13', $this->shiftsOf($this->guru));

        // Deactivated or deleted: filled again
        $holiday->update(['is_active' => false]);
        $this->assertArrayHasKey('2026-11-13', $this->shiftsOf($this->guru));
        Holiday::whereDate('date', '2026-11-10')->first()->delete();
        $this->assertArrayHasKey('2026-11-10', $this->shiftsOf($this->guru));
    }

    public function test_past_holiday_releases_shifts_of_employees_who_did_not_come(): void
    {
        foreach ([$this->guru, $this->satpam] as $employee) {
            EmployeeShift::create(['employee_id' => $employee->id, 'schedule_id' => $this->pagi->id, 'date' => '2026-11-03', 'is_active' => true]);
        }
        Attendance::create(['attendable_type' => Employee::class, 'attendable_id' => $this->satpam->id, 'date' => '2026-11-03', 'clock_in' => '2026-11-03 06:58:00']);

        Holiday::create(['name' => 'Libur Susulan', 'date' => '2026-11-03', 'is_active' => true]);

        $this->assertSame([], $this->shiftsOf($this->guru), 'not counted absent on a holiday');
        $this->assertArrayHasKey('2026-11-03', $this->shiftsOf($this->satpam), 'worked: still counted');
    }

    public function test_active_days_changes_release_and_fill_from_today(): void
    {
        EmployeeShiftAutoFill::fillPending(2026, 11);
        EmployeeShift::create(['employee_id' => $this->guru->id, 'schedule_id' => $this->pagi->id, 'date' => '2026-11-02', 'is_active' => true]);

        // Saturday becomes active, Monday does not
        $this->actingAs($this->admin)->post(route('admin.holidays.update-active-days'), ['active_days' => [2, 3, 4, 5, 6]])
            ->assertRedirect();

        $satpam = $this->shiftsOf($this->satpam);
        $this->assertArrayHasKey('2026-11-07', $satpam, 'Satpam applies on Saturday');
        $this->assertArrayNotHasKey('2026-11-07', $this->shiftsOf($this->guru), 'Guru does not');
        $this->assertArrayNotHasKey('2026-11-09', $satpam, 'Monday released');
        $this->assertArrayHasKey('2026-11-02', $this->shiftsOf($this->guru), 'past Monday untouched');
    }

    public function test_previous_month_is_no_longer_copied_by_date(): void
    {
        Schedule::query()->update(['apply_days' => null, 'apply_positions' => null]);
        EmployeeShift::create(['employee_id' => $this->guru->id, 'schedule_id' => $this->pagi->id, 'date' => '2026-11-16', 'is_active' => true]);

        $this->actingAs($this->admin)->get(route('admin.employee-shifts.index', ['month' => 12, 'year' => 2026]))->assertOk();

        $this->assertSame(0, EmployeeShift::whereDate('date', '>=', '2026-12-01')->count());
    }
}
