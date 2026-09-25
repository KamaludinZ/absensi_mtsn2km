<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SupportingDocumentTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role, string $username): User
    {
        return User::create([
            'name' => ucfirst($username),
            'username' => $username,
            'password' => bcrypt('rahasia123'),
            'role_id' => Role::firstOrCreate(['name' => $role])->id,
        ]);
    }

    private function employee(string $username): array
    {
        $user = $this->user('pegawai', $username);
        $employee = Employee::create([
            'user_id' => $user->id,
            'full_name' => ucfirst($username),
            'position' => 'Guru',
            'is_active' => true,
        ]);

        return [$user, $employee];
    }

    public function test_only_owner_admin_and_monitoring_can_open_a_permission_document(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('permissions/surat.pdf', '%PDF-1.4 surat dokter');

        [$owner, $employee] = $this->employee('guru1');
        [$other] = $this->employee('guru2');

        $permission = Permission::create([
            'permissible_type' => Employee::class,
            'permissible_id' => $employee->id,
            'start_date' => '2026-09-25',
            'end_date' => '2026-09-25',
            'reason' => 'Sakit',
            'supporting_document' => 'permissions/surat.pdf',
            'status' => 'pending',
        ]);
        $url = route('documents.permission', $permission);

        $this->get($url)->assertRedirect('/login');
        $this->actingAs($other)->get($url)->assertForbidden();

        $this->actingAs($owner)->get($url)
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->actingAs($this->user('admin', 'admin'))->get($url)->assertOk();
        $this->actingAs($this->user('monitoring', 'monitor'))->get($url)->assertOk();
    }

    public function test_uploaded_document_gets_a_random_file_name(): void
    {
        Storage::fake('public');
        [$owner] = $this->employee('guru1');

        $this->actingAs($owner)->post(route('employee.permission.store'), [
            'start_date' => '2026-09-25',
            'end_date' => '2026-09-25',
            'reason' => 'Sakit',
            'supporting_document' => UploadedFile::fake()->create('surat dokter asli.pdf', 10, 'application/pdf'),
        ])->assertRedirect();

        $path = Permission::first()->supporting_document;
        $this->assertStringStartsWith('permissions/', $path);
        $this->assertStringNotContainsString('surat dokter asli', $path);
        Storage::disk('public')->assertExists($path);
    }
}
