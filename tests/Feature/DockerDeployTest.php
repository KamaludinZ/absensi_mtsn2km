<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Pieces used by the Docker / Coolify deployment (see DEPLOY_COOLIFY.md).
 */
class DockerDeployTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_install_seeds_an_empty_database_with_the_given_admin_password(): void
    {
        $this->artisan('app:first-install', ['--admin-password' => 'RahasiaBaru123'])->assertSuccessful();

        $admin = User::where('username', 'admin')->first();
        $this->assertNotNull($admin);
        $this->assertSame('admin', $admin->role->name);
        $this->assertTrue(Hash::check('RahasiaBaru123', $admin->password));
        $this->assertFalse(Hash::check('password', $admin->password));
    }

    public function test_first_install_needs_a_strong_enough_password(): void
    {
        $this->artisan('app:first-install', ['--admin-password' => 'pendek'])->assertFailed();

        $this->assertSame(0, User::count());
    }

    public function test_first_install_does_nothing_when_users_exist(): void
    {
        $existing = User::create([
            'name' => 'Admin',
            'username' => 'admin',
            'password' => Hash::make('passwordlama'),
            'role_id' => Role::firstOrCreate(['name' => 'admin'])->id,
        ]);

        $this->artisan('app:first-install', ['--admin-password' => 'RahasiaBaru123'])->assertSuccessful();

        $this->assertSame(1, User::count());
        $this->assertTrue(Hash::check('passwordlama', $existing->fresh()->password));
    }

    public function test_web_updaters_are_off_when_disabled(): void
    {
        config(['app.disable_web_updater' => true]);

        $admin = User::create([
            'name' => 'Admin',
            'username' => 'admin',
            'password' => Hash::make('rahasia123'),
            'role_id' => Role::firstOrCreate(['name' => 'admin'])->id,
        ]);

        $this->actingAs($admin)
            ->from(route('admin.update.index'))
            ->post(route('admin.update.update'))
            ->assertRedirect(route('admin.update.index'))
            ->assertSessionHas('error');

        $this->actingAs($admin)
            ->from(route('admin.system-update.index'))
            ->post(route('admin.system-update.upload'), [
                'update_zip' => UploadedFile::fake()->create('update.zip', 1, 'application/zip'),
            ])
            ->assertRedirect(route('admin.system-update.index'))
            ->assertSessionHas('error');
    }
}
