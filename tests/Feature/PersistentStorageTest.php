<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Support\PersistentStorage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Admins are warned when, in Docker, upload or backup folders are not
 * persistent volumes (their files would be lost on redeploy).
 */
class PersistentStorageTest extends TestCase
{
    use RefreshDatabase;

    private function mountInfo(array $mountPoints): string
    {
        $lines = ['22 1 0:21 / / rw,relatime - overlay overlay rw'];
        foreach ($mountPoints as $i => $point) {
            $lines[] = (30 + $i) . " 22 8:1 /var/lib/docker/volumes/v{$i}/_data " . str_replace(' ', '\040', $point) . ' rw,relatime - ext4 /dev/sda1 rw';
        }

        return implode("\n", $lines) . "\n";
    }

    public function test_all_volumes_mounted_means_no_warning(): void
    {
        $info = $this->mountInfo([base_path('storage'), public_path('storage'), public_path('uploads')]);

        $this->assertSame([], PersistentStorage::notPersisted($info));
    }

    public function test_missing_volumes_are_listed(): void
    {
        $info = $this->mountInfo([base_path('storage')]);

        $this->assertSame(
            [public_path('storage'), public_path('uploads')],
            array_keys(PersistentStorage::notPersisted($info))
        );
    }

    public function test_no_check_outside_docker(): void
    {
        config(['app.in_docker' => false]);

        $this->assertSame([], PersistentStorage::notPersisted());
    }

    public function test_admin_sees_the_warning_in_docker_without_volumes(): void
    {
        if (!is_readable('/proc/self/mountinfo')) {
            $this->markTestSkipped('Needs /proc/self/mountinfo (Linux).');
        }
        config(['app.in_docker' => true]);

        $admin = User::create([
            'name' => 'Admin',
            'username' => 'admin',
            'password' => bcrypt('rahasia123'),
            'role_id' => Role::firstOrCreate(['name' => 'admin'])->id,
        ]);

        // The test folders are not volumes
        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Penyimpanan belum permanen')
            ->assertSee(public_path('uploads'));
    }
}
