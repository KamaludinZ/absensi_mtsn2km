<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Grade;
use App\Models\Role;
use App\Models\Rombel;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentRfidTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Rombel $rombel;

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
        $this->rombel = Rombel::create(['academic_year_id' => $year->id, 'grade_id' => Grade::first()->id, 'name' => 'VII A']);
    }

    private function student(string $name, string $nisn, ?string $rfid = null): Student
    {
        return Student::create([
            'nisn' => $nisn,
            'rfid_uid' => $rfid,
            'full_name' => $name,
            'rombel_id' => $this->rombel->id,
            'is_active' => true,
        ]);
    }

    public function test_admin_sees_the_rfid_page_with_progress(): void
    {
        $this->student('Andi', '001', 'AABB');
        $this->student('Budi', '002');

        $this->actingAs($this->admin)->get(route('admin.students.rfid.index'))
            ->assertOk()
            ->assertSee('1 dari 2 siswa aktif sudah punya kartu')
            ->assertSee('Andi')
            ->assertSee('Budi');

        $this->actingAs($this->admin)->get(route('admin.students.rfid.index', ['status' => 'unregistered']))
            ->assertOk()
            ->assertSee('Budi')
            ->assertDontSee('Andi');
    }

    public function test_tapped_card_is_normalized_and_saved(): void
    {
        $budi = $this->student('Budi', '002');

        $this->actingAs($this->admin)
            ->postJson(route('admin.students.rfid.assign', $budi), ['rfid_uid' => ' 04 a1 b2 c3 '])
            ->assertOk()
            ->assertJson(['success' => true, 'rfid_uid' => '04A1B2C3']);

        $this->assertSame('04A1B2C3', $budi->fresh()->rfid_uid);
    }

    public function test_card_already_used_by_another_student_is_rejected_with_owner_name(): void
    {
        $this->student('Andi', '001', 'AABB');
        $budi = $this->student('Budi', '002');

        $this->actingAs($this->admin)
            ->postJson(route('admin.students.rfid.assign', $budi), ['rfid_uid' => 'aabb'])
            ->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonFragment(['message' => 'Kartu ini sudah terdaftar untuk Andi (VII A).']);

        $this->assertNull($budi->fresh()->rfid_uid);
    }

    public function test_card_can_be_removed(): void
    {
        $andi = $this->student('Andi', '001', 'AABB');

        $this->actingAs($this->admin)
            ->deleteJson(route('admin.students.rfid.remove', $andi))
            ->assertOk();

        $this->assertNull($andi->fresh()->rfid_uid);
    }

    public function test_card_is_freed_when_student_is_deleted(): void
    {
        $andi = $this->student('Andi', '001', 'AABB');
        $budi = $this->student('Budi', '002');

        $this->actingAs($this->admin)->delete(route('admin.students.destroy', $andi));

        $this->actingAs($this->admin)
            ->postJson(route('admin.students.rfid.assign', $budi), ['rfid_uid' => 'AABB'])
            ->assertOk();
    }

    public function test_card_can_be_set_from_the_student_form(): void
    {
        $this->actingAs($this->admin)->post(route('admin.students.store'), [
            'nisn' => '003',
            'rfid_uid' => 'de ad be ef',
            'full_name' => 'Citra',
            'rombel_id' => $this->rombel->id,
            'is_active' => 1,
        ])->assertRedirect(route('admin.students.index'));

        $citra = Student::where('nisn', '003')->first();
        $this->assertSame('DEADBEEF', $citra->rfid_uid);

        // Duplicate card on another student is refused with a clear message
        $dina = $this->student('Dina', '004');
        $this->actingAs($this->admin)->put(route('admin.students.update', $dina), [
            'nisn' => '004',
            'rfid_uid' => 'deadbeef',
            'full_name' => 'Dina',
            'rombel_id' => $this->rombel->id,
            'is_active' => 1,
        ])->assertSessionHasErrors(['rfid_uid' => 'Kartu RFID ini sudah terdaftar untuk siswa lain.']);

        // Clearing the field removes the card
        $this->actingAs($this->admin)->put(route('admin.students.update', $citra), [
            'nisn' => '003',
            'rfid_uid' => '',
            'full_name' => 'Citra',
            'rombel_id' => $this->rombel->id,
            'is_active' => 1,
        ])->assertSessionHasNoErrors();
        $this->assertNull($citra->fresh()->rfid_uid);
    }

    public function test_non_admin_cannot_manage_cards(): void
    {
        $andi = $this->student('Andi', '001');

        $this->postJson(route('admin.students.rfid.assign', $andi), ['rfid_uid' => 'AABB'])->assertUnauthorized();
        $this->get(route('admin.students.rfid.index'))->assertRedirect('/login');
    }
}
