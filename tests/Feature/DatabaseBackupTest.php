<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Services\DatabaseBackup;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

/**
 * Automatic backups (daily, before migrations, before a restore) and a
 * restore that either fully succeeds or changes nothing.
 *
 * pg_dump / psql use their own database connection, so these tests commit
 * their data to a freshly migrated database instead of using a test
 * transaction.
 */
class DatabaseBackupTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Backups need PostgreSQL (run with DB_CONNECTION=pgsql).');
        }

        $this->artisan('migrate:fresh');
        // Committed data stays behind: let the next RefreshDatabase test
        // start from a fresh database again.
        $this->beforeApplicationDestroyed(fn () => RefreshDatabaseState::$migrated = false);

        Storage::fake('backups');
    }

    private function admin(string $username = 'admin'): User
    {
        return User::create([
            'name' => 'Admin',
            'username' => $username,
            'password' => bcrypt('rahasia123'),
            'role_id' => Role::firstOrCreate(['name' => 'admin'])->id,
        ]);
    }

    private function sqlIn(string $zipName): string
    {
        $zip = new ZipArchive();
        $this->assertTrue($zip->open(Storage::disk('backups')->path('backups/' . $zipName)));
        $sql = $zip->getFromName('database.sql');
        $zip->close();

        return (string) $sql;
    }

    public function test_command_saves_a_backup_that_the_admin_list_shows(): void
    {
        $admin = $this->admin();

        $this->artisan('app:backup-database')->assertSuccessful();

        $files = Storage::disk('backups')->files('backups');
        $this->assertCount(1, $files);
        $name = basename($files[0]);
        $this->assertStringStartsWith('backup_auto_', $name);
        $this->assertStringContainsString('CREATE TABLE public.users', $this->sqlIn($name));
        $this->assertStringNotContainsString('OWNER TO', $this->sqlIn($name));

        $listed = $this->actingAs($admin)->get(route('admin.backup.index'))->assertOk()->viewData('backups');
        $this->assertSame([$name], array_column($listed, 'name'));
    }

    public function test_pre_migration_backup_only_runs_when_migrations_are_pending(): void
    {
        $this->artisan('app:backup-database', ['--kind' => 'premigrate', '--if-pending-migrations' => true])
            ->expectsOutputToContain('No pending migrations')
            ->assertSuccessful();
        $this->assertSame([], Storage::disk('backups')->files('backups'));

        // Pretend the latest migration has not run yet
        DB::table('migrations')->orderByDesc('id')->limit(1)->delete();

        $this->artisan('app:backup-database', ['--kind' => 'premigrate', '--if-pending-migrations' => true])
            ->assertSuccessful();
        $files = Storage::disk('backups')->files('backups');
        $this->assertCount(1, $files);
        $this->assertStringStartsWith('backup_premigrate_', basename($files[0]));
    }

    public function test_old_automatic_backups_are_removed_but_manual_ones_kept(): void
    {
        $disk = Storage::disk('backups');
        foreach (['01', '02', '03', '04'] as $day) {
            $disk->put("backups/backup_auto_2026-10-{$day}_01-00-00.zip", 'x');
        }
        $disk->put('backups/backup_full_2026-01-01_10-00-00.zip', 'manual');
        $disk->put('backups/uploaded_2026-01-01_10-00-00.zip', 'uploaded');

        $this->assertSame(2, app(DatabaseBackup::class)->prune(2));

        $left = collect($disk->files('backups'))->map(fn ($f) => basename($f))->sort()->values()->all();
        $this->assertSame([
            'backup_auto_2026-10-03_01-00-00.zip',
            'backup_auto_2026-10-04_01-00-00.zip',
            'backup_full_2026-01-01_10-00-00.zip',
            'uploaded_2026-01-01_10-00-00.zip',
        ], $left);
    }

    public function test_restore_replaces_the_whole_database(): void
    {
        $this->admin('guru_lama');
        $name = app(DatabaseBackup::class)->create('auto');

        // Changes after the backup: a new user and a table from a "newer" migration
        $this->admin('guru_baru');
        Schema::create('tabel_baru', fn ($t) => $t->id());

        $sql = storage_path('app/test_restore.sql');
        File::put($sql, $this->sqlIn($name));
        app(DatabaseBackup::class)->restore($sql);
        File::delete($sql);

        $this->assertSame(['guru_lama'], DB::table('users')->pluck('username')->all());
        $this->assertFalse(Schema::hasTable('tabel_baru'));
    }

    public function test_failed_restore_changes_nothing(): void
    {
        $this->admin('guru_lama');

        $sql = storage_path('app/test_broken.sql');
        File::put($sql, "CREATE TABLE public.users (id int);\nTHIS IS NOT SQL;\n");

        try {
            app(DatabaseBackup::class)->restore($sql);
            $this->fail('A broken backup must not restore.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('Restore PostgreSQL gagal', $e->getMessage());
        } finally {
            File::delete($sql);
        }

        $this->assertSame(['guru_lama'], DB::table('users')->pluck('username')->all());
    }

    public function test_admin_restore_keeps_a_copy_of_the_data_before_restoring(): void
    {
        $admin = $this->admin();
        $this->admin('guru_lama');
        $backup = app(DatabaseBackup::class)->create('auto');
        $this->admin('guru_baru');

        $this->actingAs($admin)
            ->post(route('admin.backup.restore.execute'), ['filename' => $backup])
            ->assertRedirect(route('admin.backup.index'))
            ->assertSessionHas('success');

        $this->assertSame(['admin', 'guru_lama'], DB::table('users')->orderBy('id')->pluck('username')->all());

        $safety = collect(Storage::disk('backups')->files('backups'))
            ->map(fn ($f) => basename($f))
            ->first(fn ($f) => str_starts_with($f, 'backup_prerestore_'));
        $this->assertNotNull($safety);
        $this->assertStringContainsString('guru_baru', $this->sqlIn($safety));
    }

    public function test_manual_backup_appears_in_the_list_and_old_misplaced_ones_are_moved(): void
    {
        $admin = $this->admin();
        // Where older versions wrongly saved "Buat Backup" files
        Storage::disk('backups')->put('backups/backups/backup_full_2026-01-01_10-00-00.zip', 'old');

        $this->actingAs($admin)->post(route('admin.backup.create'))->assertSessionHas('success');

        $names = array_column($this->actingAs($admin)->get(route('admin.backup.index'))->viewData('backups'), 'name');
        $this->assertCount(2, $names);
        $this->assertContains('backup_full_2026-01-01_10-00-00.zip', $names);
        $this->assertNotEmpty(preg_grep('/^backup_full_\d{4}-\d{2}-\d{2}_/', $names));
    }
}
