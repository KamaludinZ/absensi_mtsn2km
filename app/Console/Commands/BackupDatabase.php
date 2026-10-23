<?php

namespace App\Console\Commands;

use App\Services\DatabaseBackup;
use Illuminate\Console\Command;

/**
 * Automatic database backup into the Admin > Backup list.
 *
 *   app:backup-database                    daily backup (scheduler)
 *   app:backup-database --kind=premigrate --if-pending-migrations
 *                                          before migrations on deploy
 */
class BackupDatabase extends Command
{
    protected $signature = 'app:backup-database
        {--kind=auto : auto, premigrate or prerestore}
        {--if-pending-migrations : Only back up when there are migrations to run}
        {--keep= : How many automatic backups of this kind to keep (default: BACKUP_KEEP)}';

    protected $description = 'Back up the database (shown in Admin > Backup) and remove old automatic backups';

    public function handle(DatabaseBackup $backup): int
    {
        $kind = (string) $this->option('kind');
        if (!in_array($kind, DatabaseBackup::AUTO_KINDS, true)) {
            $this->error('Unknown --kind, use one of: ' . implode(', ', DatabaseBackup::AUTO_KINDS));
            return self::FAILURE;
        }

        if (!DatabaseBackup::supported()) {
            $this->line('Automatic backups need PostgreSQL, skipped.');
            return self::SUCCESS;
        }

        if ($this->option('if-pending-migrations')) {
            $pending = $this->pendingMigrations();
            if ($pending === null) {
                $this->line('New database (no tables yet), backup skipped.');
                return self::SUCCESS;
            }
            if ($pending === 0) {
                $this->line('No pending migrations, backup skipped.');
                return self::SUCCESS;
            }
            $this->line("{$pending} pending migration(s): backing up the database first.");
        }

        $name = $backup->create($kind);
        $this->info("Backup saved: storage/app/backups/{$name}");

        $keep = (int) ($this->option('keep') ?: config('app.backup_keep', 14));
        $deleted = $backup->prune($keep);
        if ($deleted > 0) {
            $this->line("Removed {$deleted} old automatic backup(s), keeping the newest {$keep} of each kind.");
        }

        return self::SUCCESS;
    }

    /** null = migrations table does not exist yet (empty database) */
    private function pendingMigrations(): ?int
    {
        $migrator = app('migrator');
        if (!$migrator->repositoryExists()) {
            return null;
        }

        $files = $migrator->getMigrationFiles([database_path('migrations')]);
        $ran = $migrator->getRepository()->getRan();

        return count(array_diff(array_keys($files), $ran));
    }
}
