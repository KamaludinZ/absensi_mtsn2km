<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceSetting;
use App\Models\Employee;
use App\Models\Role;
use App\Models\Schedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Reported problem: right after "Absen Masuk", an "Absen Pulang" got recorded
 * too. An accidental clock-out before the scheduled end time must no longer
 * be recorded without an explicit confirmation.
 */
class EmployeeClockOutConfirmationTest extends TestCase
{
    use RefreshDatabase;

    private const LOCATION = ['latitude' => -6.2, 'longitude' => 106.816666, 'accuracy' => 5];

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Guru',
            'username' => 'guru',
            'password' => bcrypt('rahasia123'),
            'role_id' => Role::firstOrCreate(['name' => 'pegawai'])->id,
        ]);
        Employee::create(['user_id' => $this->user->id, 'full_name' => 'Guru', 'position' => 'Guru', 'is_active' => true]);

        // Monday 07:00 - 15:00. Create it "on" that Monday: SQLite stores the
        // datetime-cast time with the current date (PostgreSQL keeps only the time).
        $this->at('06:00:00');
        Schedule::create(['type' => 'pegawai', 'day_of_week' => 1, 'start_time' => '07:00:00', 'end_time' => '15:00:00']);
        AttendanceSetting::updateOrCreate(['setting_key' => 'require_selfie_photo'], ['setting_value' => 'false']);
    }

    private function at(string $time): void
    {
        $this->travelTo(Carbon::parse('2026-09-28 ' . $time));
    }

    private function clockIn()
    {
        return $this->actingAs($this->user)->postJson(route('employee.attendance.clock-in'), ['location' => self::LOCATION]);
    }

    private function clockOut(array $extra = [])
    {
        return $this->actingAs($this->user)->postJson(route('employee.attendance.clock-out'), ['location' => self::LOCATION] + $extra);
    }

    public function test_accidental_clock_out_right_after_clock_in_is_not_recorded(): void
    {
        $this->at('07:05:00');
        $this->clockIn()->assertJson(['success' => true]);

        $this->at('07:05:04');
        $this->clockOut()
            ->assertOk()
            ->assertJson(['success' => false, 'needs_confirmation' => true])
            ->assertJsonFragment(['message' => 'Jadwal pulang Anda hari ini pukul 15:00, sekarang baru pukul 07:05 (absen masuk pukul 07:05). Yakin ingin absen PULANG sekarang?']);

        $this->assertNull(Attendance::first()->clock_out);
    }

    public function test_confirmed_early_clock_out_is_recorded(): void
    {
        $this->at('07:05:00');
        $this->clockIn();

        $this->at('11:00:00');
        $this->clockOut(['confirm_early' => true])->assertJson(['success' => true]);

        $attendance = Attendance::first();
        $this->assertSame('11:00', $attendance->clock_out->format('H:i'));
        $this->assertSame('Pulang Awal', $attendance->status_out);
    }

    public function test_clock_out_after_the_scheduled_end_needs_no_confirmation(): void
    {
        $this->at('07:05:00');
        $this->clockIn();

        $this->at('15:10:00');
        $this->clockOut()->assertJson(['success' => true]);

        $this->assertNotNull(Attendance::first()->clock_out);
    }

    public function test_second_clock_in_reports_the_recorded_time(): void
    {
        $this->at('07:05:00');
        $this->clockIn();

        $this->at('07:06:00');
        $this->clockIn()->assertJson([
            'success' => false,
            'already_clocked_in' => true,
            'message' => 'Anda sudah absen masuk hari ini pukul 07:05.',
        ]);
    }

    public function test_page_shows_clock_in_time_and_scheduled_end(): void
    {
        $this->at('07:05:00');
        $this->clockIn();

        $this->at('08:00:00');
        $this->actingAs($this->user)->get(route('employee.attendance.index'))
            ->assertOk()
            ->assertSee("clockInTime: '07:05'", false)
            ->assertSee("scheduledEnd: '15:00'", false)
            ->assertDontSee('COOLDOWN_DURATION', false);
    }
}
