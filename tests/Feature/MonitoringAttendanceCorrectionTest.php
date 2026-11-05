<?php

namespace Tests\Feature;

use App\Models\AttendanceCorrection;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Monitoring can look at /admin/attendance-corrections; creating, editing,
 * deleting, approving and rejecting stay with admin.
 */
class MonitoringAttendanceCorrectionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $monitoring;
    private User $pegawai;
    private AttendanceCorrection $correction;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-11-05 10:00:00'));

        $make = fn (string $role, string $username) => User::create([
            'name' => ucfirst($username),
            'username' => $username,
            'password' => bcrypt('rahasia123'),
            'role_id' => Role::firstOrCreate(['name' => $role])->id,
        ]);
        $this->admin = $make('admin', 'admin');
        $this->monitoring = $make('monitoring', 'kepala');
        $this->pegawai = $make('pegawai', 'siti');

        $employee = Employee::create(['full_name' => 'Siti Aminah', 'position' => 'Guru', 'is_active' => true, 'user_id' => $this->pegawai->id]);
        $this->correction = AttendanceCorrection::create([
            'correctable_type' => Employee::class,
            'correctable_id' => $employee->id,
            'date' => '2026-11-03',
            'corrected_clock_in' => '2026-11-03 07:00:00',
            'reason' => 'Lupa absen masuk',
            'status' => 'pending',
        ]);
    }

    public function test_monitoring_sees_the_list_without_actions(): void
    {
        $this->actingAs($this->monitoring)
            ->get(route('admin.attendance-corrections.index'))
            ->assertOk()
            ->assertSee('Siti Aminah')
            ->assertSee('Lupa absen masuk')
            ->assertSee('Mode lihat saja')
            ->assertSee('Koreksi Absensi') // menu link
            ->assertDontSee(route('admin.attendance-corrections.create'))
            ->assertDontSee(route('admin.attendance-corrections.approve', $this->correction))
            ->assertDontSee(route('admin.attendance-corrections.edit', $this->correction));
    }

    public function test_monitoring_cannot_change_corrections(): void
    {
        $this->actingAs($this->monitoring);

        $this->get(route('admin.attendance-corrections.create'))->assertForbidden();
        $this->get(route('admin.attendance-corrections.edit', $this->correction))->assertForbidden();
        $this->post(route('admin.attendance-corrections.approve', $this->correction))->assertForbidden();
        $this->post(route('admin.attendance-corrections.reject', $this->correction))->assertForbidden();
        $this->delete(route('admin.attendance-corrections.destroy', $this->correction))->assertForbidden();

        $this->assertSame('pending', $this->correction->fresh()->status);
    }

    public function test_admin_keeps_all_actions_and_employees_stay_out(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.attendance-corrections.index'))
            ->assertOk()
            ->assertSee(route('admin.attendance-corrections.create'))
            ->assertSee(route('admin.attendance-corrections.approve', $this->correction))
            ->assertDontSee('Mode lihat saja');

        $this->actingAs($this->pegawai)
            ->get(route('admin.attendance-corrections.index'))
            ->assertForbidden();
    }
}
