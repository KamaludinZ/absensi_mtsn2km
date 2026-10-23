<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\Process\Process;
use ZipArchive;

/**
 * PostgreSQL backups in the same ZIP format as Admin > Backup
 * (database.sql + backup_info.json), so every backup shows up in the
 * admin backup list and can be downloaded or restored from there.
 *
 * Used for the automatic backups: daily, before database migrations on
 * deploy, and before a restore.
 */
class DatabaseBackup
{
    /** Folder on the "backups" disk that Admin > Backup lists */
    public const DIR = 'backups';

    /** Automatic backup kinds; only these are ever deleted by the cleanup */
    public const AUTO_KINDS = ['auto', 'premigrate', 'prerestore'];

    public static function supported(): bool
    {
        return config('database.default') === 'pgsql';
    }

    /**
     * Dump the database into backups/backup_{kind}_{time}.zip and return
     * the file name.
     */
    public function create(string $kind = 'auto'): string
    {
        if (!self::supported()) {
            throw new RuntimeException('Backup otomatis hanya untuk database PostgreSQL.');
        }

        $disk = Storage::disk('backups');
        $disk->makeDirectory(self::DIR);

        $name = 'backup_' . $kind . '_' . Carbon::now()->format('Y-m-d_H-i-s') . '.zip';
        $zipPath = $disk->path(self::DIR . '/' . $name);
        $sqlPath = storage_path('app/temp_db_' . uniqid() . '.sql');

        try {
            $this->dump($sqlPath);

            $zip = new ZipArchive();
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('Tidak dapat membuat file ZIP backup.');
            }
            $zip->addFile($sqlPath, 'database.sql');
            $zip->addFromString('backup_info.json', json_encode([
                'backup_date' => Carbon::now()->toDateTimeString(),
                'backup_kind' => $kind,
                'app_name' => config('app.name'),
                'app_version' => File::exists(base_path('VERSION')) ? trim(File::get(base_path('VERSION'))) : 'unknown',
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'database_type' => 'pgsql',
            ], JSON_PRETTY_PRINT));
            $zip->close();
        } catch (\Throwable $e) {
            File::delete($zipPath);
            throw $e;
        } finally {
            File::delete($sqlPath);
        }

        return $name;
    }

    /**
     * Plain SQL dump without owner/privilege statements, so it can be
     * restored with another database user (e.g. after moving servers).
     */
    public function dump(string $sqlPath): void
    {
        $this->run([
            'pg_dump', '--no-owner', '--no-acl',
            '-h', (string) config('database.connections.pgsql.host'),
            '-p', (string) config('database.connections.pgsql.port'),
            '-U', (string) config('database.connections.pgsql.username'),
            '-d', (string) config('database.connections.pgsql.database'),
            '-f', $sqlPath,
        ], 'pg_dump');

        if (!File::exists($sqlPath) || File::size($sqlPath) === 0) {
            throw new RuntimeException('pg_dump tidak menghasilkan file.');
        }
    }

    /**
     * Replace the whole database with the dump, all in one transaction: if
     * any statement fails, nothing changes and the old data stays.
     */
    public function restore(string $sqlPath): void
    {
        $reset = storage_path('app/temp_reset_' . uniqid() . '.sql');
        // Start from an empty schema, so tables that are not in the backup
        // (e.g. from a newer migration) do not stay behind.
        File::put($reset, "DROP SCHEMA IF EXISTS public CASCADE;\nCREATE SCHEMA public;\n");

        try {
            $this->run([
                'psql', '-X', '-q',
                '-v', 'ON_ERROR_STOP=1',
                '--single-transaction',
                '-h', (string) config('database.connections.pgsql.host'),
                '-p', (string) config('database.connections.pgsql.port'),
                '-U', (string) config('database.connections.pgsql.username'),
                '-d', (string) config('database.connections.pgsql.database'),
                '-f', $reset,
                '-f', $sqlPath,
            ], 'Restore PostgreSQL');
        } finally {
            File::delete($reset);
        }
    }

    /**
     * Keep only the newest $keep automatic backups of each kind. Backups
     * made by hand or uploaded are never deleted.
     */
    public function prune(int $keep): int
    {
        $keep = max(1, $keep);
        $disk = Storage::disk('backups');
        $deleted = 0;

        foreach (self::AUTO_KINDS as $kind) {
            $files = collect($disk->files(self::DIR))
                ->filter(fn ($f) => str_starts_with(basename($f), 'backup_' . $kind . '_') && str_ends_with($f, '.zip'))
                ->sortByDesc(fn ($f) => basename($f))
                ->values();

            foreach ($files->slice($keep) as $file) {
                $disk->delete($file);
                $deleted++;
            }
        }

        return $deleted;
    }

    /**
     * Older versions saved "Buat Backup" files in backups/backups/, a folder
     * the backup list never showed. Move them to where they are listed.
     */
    public function moveMisplacedBackups(): void
    {
        $disk = Storage::disk('backups');
        foreach ($disk->files(self::DIR . '/backups') as $file) {
            $target = self::DIR . '/' . basename($file);
            if (str_ends_with($file, '.zip') && !$disk->exists($target)) {
                $disk->move($file, $target);
            }
        }
    }

    private function run(array $command, string $label): void
    {
        $process = new Process($command, base_path(), [
            'PGPASSWORD' => (string) config('database.connections.pgsql.password'),
            'PGCONNECT_TIMEOUT' => '10',
        ]);
        $process->setTimeout(1800);
        $process->run();

        if (!$process->isSuccessful()) {
            $error = trim($process->getErrorOutput() ?: $process->getOutput());
            throw new RuntimeException($label . ' gagal: ' . mb_substr($error, 0, 1000));
        }
    }
}
