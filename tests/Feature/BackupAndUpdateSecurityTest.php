<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class BackupAndUpdateSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin',
            'username' => 'admin',
            'password' => bcrypt('rahasia123'),
            'role_id' => Role::firstOrCreate(['name' => 'admin'])->id,
        ]);
    }

    private function makeZip(string $path, array $files): void
    {
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        foreach ($files as $name => $content) {
            $zip->addFromString($name, $content);
        }
        $zip->close();
    }

    public function test_restore_copies_only_images_into_the_web_root(): void
    {
        Storage::fake('public');
        Storage::fake('backups');
        Storage::disk('backups')->makeDirectory('backups');

        $this->makeZip(Storage::disk('backups')->path('backups/backup_test.zip'), [
            'database.sql' => 'SELECT 1;',
            'files/selfies/employee/employee_1_in_1.jpg' => 'jpeg-bytes',
            'files/selfies/employee/shell.php' => '<?php echo "pwned";',
            'config/.env' => 'APP_KEY=attacker',
        ]);

        $envBefore = File::exists(base_path('.env')) ? File::get(base_path('.env')) : null;

        $this->actingAs($this->admin())
            ->post(route('admin.backup.restore.execute'), ['filename' => 'backup_test.zip', 'restore_env' => 'yes'])
            ->assertRedirect(route('admin.backup.index'));

        Storage::disk('public')->assertExists('selfies/employee/employee_1_in_1.jpg');
        Storage::disk('public')->assertMissing('selfies/employee/shell.php');
        $this->assertSame($envBefore, File::exists(base_path('.env')) ? File::get(base_path('.env')) : null);
    }

    public function test_backup_file_names_are_validated(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.backup.restore.execute'), ['filename' => '../../.env'])
            ->assertSessionHas('error', 'Nama file backup tidak valid.');
    }

    public function test_system_update_rejects_paths_outside_the_app_or_to_secrets(): void
    {
        $admin = $this->admin();

        foreach (['../evil.php', '.env', 'vendor/autoload.php', '/tmp/x.php'] as $target) {
            $zipPath = tempnam(sys_get_temp_dir(), 'upd') . '.zip';
            $this->makeZip($zipPath, [
                'update_manifest.json' => json_encode(['version' => '9.9.9', 'files' => [$target]]),
                'evil.php' => '<?php // should never be copied',
            ]);

            $this->actingAs($admin)
                ->post(route('admin.system-update.upload'), [
                    'update_zip' => new UploadedFile($zipPath, 'update.zip', 'application/zip', null, true),
                ])
                ->assertSessionHas('error', fn ($message) => str_contains($message, 'Path file tidak diizinkan'));
        }

        $this->assertFileDoesNotExist(dirname(base_path()) . '/evil.php');
    }
}
