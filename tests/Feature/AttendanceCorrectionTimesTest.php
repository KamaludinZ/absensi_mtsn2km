<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The original and corrected times of a correction request are saved and
 * shown in the columns of /admin/attendance-corrections.
 */
class AttendanceCorrectionTimesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $pegawai;
    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->travelTo(Carbon::parse('2026-11-05 10:00:00'));

        $make = fn (string $role, string $username) => User::create([
            'name' => ucfirst($username),
            'username' => $username,
            'password' => bcrypt('rahasia123'),
            'role_id' => Role::firstOrCreate(['name' => $role])->id,
        ]);
        $this->admin = $make('admin', 'admin');
        $this->pegawai = $make('pegawai', 'siti');
        $this->employee = Employee::create(['full_name' => 'Siti Aminah', 'position' => 'Guru', 'is_active' => true, 'user_id' => $this->pegawai->id]);

        Attendance::create([
            'attendable_type' => Employee::class,
            'attendable_id' => $this->employee->id,
            'date' => '2026-11-03',
            'clock_in' => '2026-11-03 07:12:00',
            'clock_out' => '2026-11-03 15:40:00',
            'status_in' => 'Tepat Waktu',
        ]);
    }

    private function pdf(): UploadedFile
    {
        return UploadedFile::fake()->create('bukti.pdf', 10, 'application/pdf');
    }

    public function test_employee_request_saves_and_shows_original_and_corrected_times(): void
    {
        $this->actingAs($this->pegawai)->post(route('employee.attendance-corrections.store'), [
            'date' => '2026-11-03',
            'corrected_clock_in' => '07:00',
            'corrected_clock_out' => '16:00',
            'reason' => 'Mesin absen error',
            'supporting_document' => $this->pdf(),
        ])->assertSessionHasNoErrors();

        $correction = AttendanceCorrection::sole();
        $this->assertSame('07:12', $correction->original_clock_in->format('H:i'));
        $this->assertSame('07:00', $correction->corrected_clock_in->format('H:i'));
        $this->assertSame('15:40', $correction->original_clock_out->format('H:i'));
        $this->assertSame('16:00', $correction->corrected_clock_out->format('H:i'));

        $this->actingAs($this->admin)
            ->get(route('admin.attendance-corrections.index', ['month' => 11, 'year' => 2026]))
            ->assertOk()
            ->assertSeeInOrder(['03/11/2026', 'Siti Aminah', '07:12', '07:00', '15:40', '16:00', 'Mesin absen error']);
    }

    public function test_request_without_any_corrected_time_is_rejected(): void
    {
        $this->actingAs($this->pegawai)->post(route('employee.attendance-corrections.store'), [
            'date' => '2026-11-03',
            'corrected_clock_in' => '',
            'corrected_clock_out' => '',
            'reason' => 'Lupa',
            'supporting_document' => $this->pdf(),
        ])->assertSessionHasErrors(['corrected_clock_in' => 'Isi Jam Masuk (Koreksi) atau Jam Pulang (Koreksi), minimal salah satu.']);

        $this->actingAs($this->admin)->post(route('admin.attendance-corrections.store'), [
            'correctable_type' => Employee::class,
            'correctable_id' => $this->employee->id,
            'date' => '2026-11-03',
            'reason' => 'Lupa',
            'supporting_document' => $this->pdf(),
        ])->assertSessionHasErrors('corrected_clock_in');

        $this->assertSame(0, AttendanceCorrection::count());
    }

    public function test_one_corrected_time_is_enough(): void
    {
        $this->actingAs($this->pegawai)->post(route('employee.attendance-corrections.store'), [
            'date' => '2026-11-03',
            'corrected_clock_out' => '16:00',
            'reason' => 'Lupa absen pulang',
            'supporting_document' => $this->pdf(),
        ])->assertSessionHasNoErrors();

        $correction = AttendanceCorrection::sole();
        $this->assertNull($correction->corrected_clock_in);
        $this->assertSame('16:00', $correction->corrected_clock_out->format('H:i'));
    }

    public function test_request_form_keeps_the_typed_times_after_a_failed_submit(): void
    {
        // The script that loads the recorded times used to empty the
        // corrected time fields when the form came back with an error.
        $this->actingAs($this->pegawai)
            ->get(route('employee.attendance-corrections.create'))
            ->assertOk()
            ->assertDontSee("correctedClockInInput.value = ''", false)
            ->assertDontSee("correctedClockOutInput.value = ''", false);
    }
}
