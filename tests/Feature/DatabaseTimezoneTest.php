<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * clock_in / clock_out are "timestamp with time zone". After moving to a
 * database server in UTC (Coolify), times must still show in WIB.
 */
class DatabaseTimezoneTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Needs PostgreSQL (run with DB_CONNECTION=pgsql).');
        }
    }

    private function insertRow(string $clockIn, ?string $clockOut, string $createdAt, string $updatedAt): int
    {
        return DB::table('attendances')->insertGetId([
            'attendable_type' => Employee::class,
            'attendable_id' => 1,
            'date' => substr($createdAt, 0, 10),
            'clock_in' => DB::raw("'{$clockIn}'::timestamptz"),
            'clock_out' => $clockOut ? DB::raw("'{$clockOut}'::timestamptz") : null,
            'created_at' => $createdAt,
            'updated_at' => $updatedAt,
        ]);
    }

    public function test_connection_uses_the_app_time_zone(): void
    {
        $this->assertSame('Asia/Jakarta', DB::selectOne('show timezone')->TimeZone);
    }

    public function test_restored_times_show_in_wib(): void
    {
        // A moved-over row: 07:05 WIB = 00:05 UTC
        $id = $this->insertRow('2026-10-20 00:05:00+00', '2026-10-20 08:00:00+00', '2026-10-20 07:05:00', '2026-10-20 15:00:00');

        $attendance = Attendance::find($id);
        $this->assertSame('07:05', $attendance->clock_in->format('H:i'));
        $this->assertSame('15:00', $attendance->clock_out->format('H:i'));
    }

    public function test_new_times_round_trip(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-10-23 07:10:00'));
        $attendance = Attendance::create([
            'attendable_type' => Employee::class,
            'attendable_id' => 1,
            'date' => '2026-10-23',
            'clock_in' => now(),
        ]);

        $this->assertSame('07:10', $attendance->fresh()->clock_in->format('H:i'));
        // Stored as the right moment: 07:10 WIB = 00:10 UTC
        $this->assertSame('00:10', DB::selectOne("select to_char(clock_in at time zone 'UTC', 'HH24:MI') as t from attendances")->t);
    }

    public function test_fix_command_repairs_only_times_saved_with_the_wrong_zone(): void
    {
        // Saved on Coolify before the fix: 07:05 WIB written as "07:05 UTC"
        $wrong = $this->insertRow('2026-10-23 07:05:00+00', '2026-10-23 15:02:00+00', '2026-10-23 07:05:03', '2026-10-23 15:02:01');
        // Correct rows (moved-over data, and clock-in without clock-out)
        $right = $this->insertRow('2026-10-20 00:05:00+00', '2026-10-20 08:00:00+00', '2026-10-20 07:05:00', '2026-10-20 15:00:00');
        $open = $this->insertRow('2026-10-23 00:30:00+00', null, '2026-10-23 07:30:00', '2026-10-23 07:30:00');

        $this->assertSame('14:05', Attendance::find($wrong)->clock_in->format('H:i'));

        // Dry run changes nothing
        $this->artisan('app:fix-attendance-timezone', ['--from' => 'UTC'])
            ->expectsOutputToContain('2 time(s) would be fixed')
            ->assertSuccessful();
        $this->assertSame('14:05', Attendance::find($wrong)->clock_in->format('H:i'));

        $this->artisan('app:fix-attendance-timezone', ['--from' => 'UTC', '--apply' => true])
            ->expectsOutputToContain('Fixed 2 time(s)')
            ->assertSuccessful();

        $this->assertSame('07:05', Attendance::find($wrong)->clock_in->format('H:i'));
        $this->assertSame('15:02', Attendance::find($wrong)->clock_out->format('H:i'));
        $this->assertSame('07:05', Attendance::find($right)->clock_in->format('H:i'));
        $this->assertSame('15:00', Attendance::find($right)->clock_out->format('H:i'));
        $this->assertSame('07:30', Attendance::find($open)->clock_in->format('H:i'));

        // Running it again finds nothing
        $this->artisan('app:fix-attendance-timezone', ['--from' => 'UTC'])
            ->expectsOutputToContain('No shifted times found')
            ->assertSuccessful();
    }

    public function test_nothing_to_fix_when_zones_match(): void
    {
        $this->artisan('app:fix-attendance-timezone', ['--from' => 'Asia/Jakarta'])
            ->expectsOutputToContain('nothing to fix')
            ->assertSuccessful();
    }
}
