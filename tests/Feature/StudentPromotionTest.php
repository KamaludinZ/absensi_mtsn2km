<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\AttendanceSetting;
use App\Models\Grade;
use App\Models\Role;
use App\Models\Rombel;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentPromotionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Rombel $viiA;
    private Rombel $viiiA;
    private Rombel $ixA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin',
            'username' => 'admin',
            'password' => bcrypt('rahasia123'),
            'role_id' => Role::firstOrCreate(['name' => 'admin'])->id,
        ]);

        $old = AcademicYear::create(['year_name' => '2025/2026', 'status' => 'inactive']);
        $new = AcademicYear::create(['year_name' => '2026/2027', 'status' => 'active']);
        [$g7, $g8, $g9] = Grade::orderBy('name')->get()->all();

        $this->viiA = Rombel::create(['academic_year_id' => $old->id, 'grade_id' => $g7->id, 'name' => 'VII A']);
        $this->ixA = Rombel::create(['academic_year_id' => $old->id, 'grade_id' => $g9->id, 'name' => 'IX A']);
        $this->viiiA = Rombel::create(['academic_year_id' => $new->id, 'grade_id' => $g8->id, 'name' => 'VIII A']);
    }

    private function student(string $name, string $nisn, Rombel $rombel, ?string $rfid = null): Student
    {
        return Student::create([
            'nisn' => $nisn,
            'rfid_uid' => $rfid,
            'full_name' => $name,
            'rombel_id' => $rombel->id,
            'is_active' => true,
        ]);
    }

    public function test_page_lists_active_students_of_the_source_rombel(): void
    {
        $this->student('Andi', '001', $this->viiA);
        $this->student('Budi', '002', $this->viiA)->update(['is_active' => false]);

        $this->actingAs($this->admin)
            ->get(route('admin.students.promotion.index', ['rombel_id' => $this->viiA->id]))
            ->assertOk()
            ->assertSee('Andi')
            ->assertDontSee('Budi')
            ->assertSee('Tahun Ajaran 2026/2027');
    }

    public function test_selected_students_move_to_the_new_rombel(): void
    {
        $andi = $this->student('Andi', '001', $this->viiA);
        $budi = $this->student('Budi', '002', $this->viiA);   // stays behind (tinggal kelas)
        $other = $this->student('Citra', '003', $this->ixA);  // not in the source rombel

        $this->actingAs($this->admin)
            ->post(route('admin.students.promotion.process'), [
                'source_rombel_id' => $this->viiA->id,
                'student_ids' => [$andi->id, $other->id],
                'action' => 'move',
                'target_rombel_id' => $this->viiiA->id,
            ])
            ->assertRedirect(route('admin.students.promotion.index', ['rombel_id' => $this->viiA->id]))
            ->assertSessionHas('success', '1 siswa dipindahkan dari VII A ke VIII A.');

        $this->assertSame($this->viiiA->id, $andi->fresh()->rombel_id);
        $this->assertSame($this->viiA->id, $budi->fresh()->rombel_id);
        $this->assertSame($this->ixA->id, $other->fresh()->rombel_id);
        $this->assertTrue($andi->fresh()->is_active);
    }

    public function test_move_requires_a_different_target_rombel(): void
    {
        $andi = $this->student('Andi', '001', $this->viiA);

        $this->actingAs($this->admin)
            ->post(route('admin.students.promotion.process'), [
                'source_rombel_id' => $this->viiA->id,
                'student_ids' => [$andi->id],
                'action' => 'move',
                'target_rombel_id' => $this->viiA->id,
            ])
            ->assertSessionHasErrors('target_rombel_id');
    }

    public function test_graduated_students_are_inactive_and_cannot_use_attendance_or_rfid(): void
    {
        $andi = $this->student('Andi', '001', $this->ixA, 'AABB');
        $budi = $this->student('Budi', '002', $this->ixA, 'CCDD');

        $this->actingAs($this->admin)
            ->post(route('admin.students.promotion.process'), [
                'source_rombel_id' => $this->ixA->id,
                'student_ids' => [$andi->id, $budi->id],
                'action' => 'graduate',
                'release_rfid' => '0',
            ])
            ->assertSessionHas('success');

        $andi->refresh();
        $this->assertFalse($andi->is_active);
        $this->assertNotNull($andi->graduated_at);
        $this->assertSame('AABB', $andi->rfid_uid); // kept, but unusable

        // Attendance page refuses both NISN and card of a graduate
        Schedule::create(['type' => 'siswa', 'day_of_week' => 1, 'start_time' => '07:00:00', 'end_time' => '14:00:00']);
        AttendanceSetting::updateOrCreate(['setting_key' => 'require_selfie_photo'], ['setting_value' => 'false']);
        $this->travelTo(Carbon::parse('2026-09-28 08:00:00'));
        auth()->logout();

        foreach (['001', 'AABB'] as $identifier) {
            $this->postJson('/student/attendance/clock-in', [
                'identifier' => $identifier,
                'location' => ['latitude' => -6.2, 'longitude' => 106.816666, 'accuracy' => 5],
            ])->assertJson(['success' => false]);
        }
        $this->assertSame(0, Attendance::count());

        // Graduates are listed with the "Lulus" filter
        $this->actingAs($this->admin)
            ->get(route('admin.students.index', ['is_active' => 'graduated']))
            ->assertOk()
            ->assertSee('Andi')
            ->assertSee('Lulus ' . now()->format('Y'));
    }

    public function test_graduation_can_release_rfid_cards(): void
    {
        $andi = $this->student('Andi', '001', $this->ixA, 'AABB');

        $this->actingAs($this->admin)
            ->post(route('admin.students.promotion.process'), [
                'source_rombel_id' => $this->ixA->id,
                'student_ids' => [$andi->id],
                'action' => 'graduate',
                'release_rfid' => '1',
            ]);

        $this->assertNull($andi->fresh()->rfid_uid);

        // The released card can be given to a new student
        $new = $this->student('Dewi', '010', $this->viiA);
        $this->actingAs($this->admin)
            ->postJson(route('admin.students.rfid.assign', $new), ['rfid_uid' => 'AABB'])
            ->assertOk();
    }

    public function test_reactivating_a_graduate_clears_the_graduation(): void
    {
        $andi = $this->student('Andi', '001', $this->ixA);
        $andi->update(['is_active' => false, 'graduated_at' => now()]);

        $this->actingAs($this->admin)->put(route('admin.students.update', $andi), [
            'nisn' => '001',
            'full_name' => 'Andi',
            'rombel_id' => $this->ixA->id,
            'is_active' => 1,
        ])->assertSessionHasNoErrors();

        $andi->refresh();
        $this->assertTrue($andi->is_active);
        $this->assertNull($andi->graduated_at);
    }

    public function test_only_admin_can_promote(): void
    {
        $andi = $this->student('Andi', '001', $this->viiA);

        $this->post(route('admin.students.promotion.process'), [
            'source_rombel_id' => $this->viiA->id,
            'student_ids' => [$andi->id],
            'action' => 'graduate',
        ])->assertRedirect('/login');

        $this->assertTrue($andi->fresh()->is_active);
    }
}
