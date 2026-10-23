<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DatabaseBackup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use ZipArchive;
use Carbon\Carbon;

class BackupController extends Controller
{
    public function index()
    {
        $backups = [];
        app(DatabaseBackup::class)->moveMisplacedBackups();

        if (Storage::disk('backups')->exists('backups')) {
            $files = Storage::disk('backups')->files('backups');

            foreach ($files as $file) {
                $backups[] = [
                    'name' => basename($file),
                    'size' => Storage::disk('backups')->size($file),
                    'date' => Storage::disk('backups')->lastModified($file),
                    'path' => $file,
                ];
            }

            // Sort by date descending
            usort($backups, function ($a, $b) {
                return $b['date'] - $a['date'];
            });
        }

        return view('admin.backup.index', compact('backups'));
    }

    public function create()
    {
        try {
            $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
            $backupName = 'backup_full_' . $timestamp . '.zip';
            // Same folder the backup list, download and restore use
            $backupPath = Storage::disk('backups')->path('backups/' . $backupName);

            // Ensure backup directory exists
            if (!File::exists(dirname($backupPath))) {
                File::makeDirectory(dirname($backupPath), 0755, true);
            }

            $zip = new ZipArchive();
            if ($zip->open($backupPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
                throw new \Exception('Tidak dapat membuat file ZIP');
            }

            // 1. Backup Database
            $dbBackup = $this->createDatabaseBackup();
            if ($dbBackup) {
                $zip->addFile($dbBackup, 'database.sql');
            }

            // 2. Backup selfie photos (attendance photos) from the "public" disk,
            //    the same place the app saves and serves them from
            $selfiePath = Storage::disk('public')->path('selfies');
            if (File::exists($selfiePath)) {
                $this->addDirectoryToZip($zip, $selfiePath, 'files/selfies');
            }

            // 3. Backup settings/configuration files
            $settingsPath = storage_path('app/settings');
            if (File::exists($settingsPath)) {
                $this->addDirectoryToZip($zip, $settingsPath, 'files/settings');
            }

            // The .env file is intentionally NOT included: it holds the database
            // password and APP_KEY, and backup files get downloaded and copied
            // around. Keep a copy of .env somewhere safe separately.

            // 4. Add backup metadata
            $metadata = [
                'backup_date' => Carbon::now()->toDateTimeString(),
                'app_name' => config('app.name'),
                'app_version' => File::exists(base_path('VERSION')) ? File::get(base_path('VERSION')) : 'unknown',
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'database_type' => config('database.default'),
            ];
            $zip->addFromString('backup_info.json', json_encode($metadata, JSON_PRETTY_PRINT));

            $zip->close();

            // Clean up temporary database dump
            if ($dbBackup && File::exists($dbBackup)) {
                File::delete($dbBackup);
            }

            return redirect()->route('admin.backup.index')
                ->with('success', 'Backup lengkap berhasil dibuat (Database + Files).');
        } catch (\Exception $e) {
            Log::error('Backup creation failed: ' . $e->getMessage());
            return redirect()->route('admin.backup.index')
                ->with('error', 'Gagal membuat backup: ' . $e->getMessage());
        }
    }

    private function createDatabaseBackup()
    {
        try {
            $dbType = config('database.default');
            $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
            $tempFile = storage_path('app/temp_db_' . $timestamp . '.sql');

            if ($dbType === 'mysql') {
                $host = config('database.connections.mysql.host');
                $port = config('database.connections.mysql.port');
                $database = config('database.connections.mysql.database');
                $username = config('database.connections.mysql.username');
                $password = config('database.connections.mysql.password');

                $command = sprintf(
                    'mysqldump -h%s -P%s -u%s -p%s %s > %s 2>&1',
                    escapeshellarg($host),
                    escapeshellarg($port),
                    escapeshellarg($username),
                    escapeshellarg($password),
                    escapeshellarg($database),
                    escapeshellarg($tempFile)
                );

                exec($command, $output, $returnVar);

                if ($returnVar !== 0 || !File::exists($tempFile)) {
                    throw new \Exception('mysqldump failed');
                }
            } else if ($dbType === 'pgsql') {
                app(DatabaseBackup::class)->dump($tempFile);
            } else {
                // Fallback: Export using Laravel's DB facade
                $tables = DB::select('SHOW TABLES');
                $sqlDump = "-- Database Backup\n-- Generated: " . Carbon::now() . "\n\n";

                foreach ($tables as $table) {
                    $tableName = array_values((array)$table)[0];
                    $sqlDump .= "-- Table: $tableName\n";
                    $sqlDump .= "DROP TABLE IF EXISTS `$tableName`;\n";

                    $createTable = DB::select("SHOW CREATE TABLE `$tableName`")[0];
                    $sqlDump .= $createTable->{'Create Table'} . ";\n\n";

                    $rows = DB::table($tableName)->get();
                    foreach ($rows as $row) {
                        $values = array_map(function($val) {
                            return is_null($val) ? 'NULL' : "'" . addslashes($val) . "'";
                        }, (array)$row);
                        $sqlDump .= "INSERT INTO `$tableName` VALUES (" . implode(',', $values) . ");\n";
                    }
                    $sqlDump .= "\n";
                }

                File::put($tempFile, $sqlDump);
            }

            return $tempFile;
        } catch (\Exception $e) {
            Log::error('Database backup failed: ' . $e->getMessage());
            return null;
        }
    }

    private function addDirectoryToZip(ZipArchive $zip, $sourcePath, $zipPath)
    {
        $files = File::allFiles($sourcePath);
        foreach ($files as $file) {
            $relativePath = str_replace($sourcePath . DIRECTORY_SEPARATOR, '', $file->getRealPath());
            $zip->addFile($file->getRealPath(), $zipPath . '/' . $relativePath);
        }
    }

    public function download($filename)
    {
        if (!$this->isValidBackupName($filename)) {
            return redirect()->route('admin.backup.index')
                ->with('error', 'Nama file backup tidak valid.');
        }

        $path = 'backups/' . $filename;

        if (!Storage::disk('backups')->exists($path)) {
            return redirect()->route('admin.backup.index')
                ->with('error', 'File backup tidak ditemukan.');
        }

        // Get the full file path and download
        $fullPath = Storage::disk('backups')->path($path);

        return response()->download($fullPath, $filename);
    }

    public function delete($filename)
    {
        if (!$this->isValidBackupName($filename)) {
            return redirect()->route('admin.backup.index')
                ->with('error', 'Nama file backup tidak valid.');
        }

        $path = 'backups/' . $filename;

        if (Storage::disk('backups')->exists($path)) {
            Storage::disk('backups')->delete($path);

            return redirect()->route('admin.backup.index')
                ->with('success', 'File backup berhasil dihapus.');
        }

        return redirect()->route('admin.backup.index')
            ->with('error', 'File backup tidak ditemukan.');
    }
    
    public function showRestore()
    {
        $backups = [];
        app(DatabaseBackup::class)->moveMisplacedBackups();

        if (Storage::disk('backups')->exists('backups')) {
            $files = Storage::disk('backups')->files('backups');

            foreach ($files as $file) {
                $backups[] = [
                    'name' => basename($file),
                    'size' => Storage::disk('backups')->size($file),
                    'date' => Storage::disk('backups')->lastModified($file),
                    'path' => $file,
                ];
            }

            // Sort by date descending
            usort($backups, function ($a, $b) {
                return $b['date'] - $a['date'];
            });
        }

        return view('admin.backup.restore', compact('backups'));
    }
    
    public function uploadBackup(Request $request)
    {
        $request->validate([
            'backup_file' => 'required|file|mimes:zip|max:512000' // Max 500MB
        ]);

        try {
            $file = $request->file('backup_file');
            $filename = 'uploaded_' . Carbon::now()->format('Y-m-d_H-i-s') . '.zip';

            // Store to backups disk
            $path = $file->storeAs('backups', $filename, 'backups');

            return redirect()->route('admin.backup.restore')
                ->with('success', 'File backup berhasil diupload: ' . $filename);
        } catch (\Exception $e) {
            Log::error('Backup upload error: ' . $e->getMessage());
            return redirect()->route('admin.backup.restore')
                ->with('error', 'Gagal mengupload file backup: ' . $e->getMessage());
        }
    }

    public function restore(Request $request)
    {
        $request->validate([
            'filename' => 'required|string'
        ]);

        $filename = $request->filename;
        if (!$this->isValidBackupName($filename)) {
            return redirect()->route('admin.backup.restore')
                ->with('error', 'Nama file backup tidak valid.');
        }
        $backupPath = 'backups/' . $filename;

        if (!Storage::disk('backups')->exists($backupPath)) {
            return redirect()->route('admin.backup.restore')
                ->with('error', 'File backup tidak ditemukan.');
        }

        try {
            $fullPath = Storage::disk('backups')->path($backupPath);
            $tempDir = storage_path('app/temp_restore_' . time());

            // Extract ZIP file
            $zip = new ZipArchive();
            if ($zip->open($fullPath) !== TRUE) {
                throw new \Exception('Gagal membuka file ZIP backup.');
            }

            mkdir($tempDir, 0755, true);
            $zip->extractTo($tempDir);
            $zip->close();

            // 0. Safety copy of the current database first, so a wrong
            //    restore can be undone from the backup list
            $safetyBackup = DatabaseBackup::supported()
                ? app(DatabaseBackup::class)->create('prerestore')
                : null;

            // 1. Restore Database
            $sqlFile = $tempDir . '/database.sql';
            if (File::exists($sqlFile)) {
                $this->restoreDatabase($sqlFile);
            } else {
                // Look for SQL file in db-dumps or root (backward compatibility)
                $sqlFiles = array_merge(
                    glob($tempDir . '/db-dumps/*.sql'),
                    glob($tempDir . '/*.sql')
                );
                if (!empty($sqlFiles)) {
                    $this->restoreDatabase($sqlFiles[0]);
                }
            }

            // 2. Restore Files (selfies/photos). The destination is inside the
            //    web root, so only image files are copied - never scripts.
            $selfiesSource = $tempDir . '/files/selfies';
            if (File::exists($selfiesSource)) {
                $this->restoreImages($selfiesSource, Storage::disk('public')->path('selfies'));
            }

            // 3. Restore Settings
            $settingsSource = $tempDir . '/files/settings';
            if (File::exists($settingsSource)) {
                $settingsDest = storage_path('app/settings');
                File::ensureDirectoryExists($settingsDest);
                File::copyDirectory($settingsSource, $settingsDest);
            }

            // .env is never restored from a backup file (older backups may still
            // contain one); configuration must be restored by hand.

            // Clean up
            $this->deleteDirectory($tempDir);

            return redirect()->route('admin.backup.index')
                ->with('success', 'Restore berhasil dilakukan (Database + Files).'
                    . ($safetyBackup ? ' Data sebelum restore disimpan di ' . $safetyBackup . '.' : ''));
        } catch (\Exception $e) {
            Log::error('Backup restore error: ' . $e->getMessage());

            // Clean up on error
            $tempDirPattern = storage_path('app/temp_restore_*');
            foreach (glob($tempDirPattern) as $dir) {
                if (is_dir($dir)) {
                    $this->deleteDirectory($dir);
                }
            }

            return redirect()->route('admin.backup.restore')
                ->with('error', 'Gagal melakukan restore: ' . $e->getMessage());
        }
    }

    private function restoreDatabase($sqlFile)
    {
        $dbType = config('database.default');

        if ($dbType === 'mysql') {
            $host = config('database.connections.mysql.host');
            $port = config('database.connections.mysql.port');
            $database = config('database.connections.mysql.database');
            $username = config('database.connections.mysql.username');
            $password = config('database.connections.mysql.password');

            $command = sprintf(
                'mysql -h%s -P%s -u%s -p%s %s < %s 2>&1',
                escapeshellarg($host),
                escapeshellarg($port),
                escapeshellarg($username),
                escapeshellarg($password),
                escapeshellarg($database),
                escapeshellarg($sqlFile)
            );

            exec($command, $output, $returnVar);

            if ($returnVar !== 0) {
                throw new \Exception('MySQL restore failed: ' . implode("\n", $output));
            }
        } else if ($dbType === 'pgsql') {
            // One transaction: on any error nothing changes
            app(DatabaseBackup::class)->restore($sqlFile);
        } else {
            // Fallback: Use Laravel DB
            $sqlContent = File::get($sqlFile);
            DB::unprepared($sqlContent);
        }
    }
    
    private function isValidBackupName($filename): bool
    {
        return is_string($filename) && preg_match('/^[A-Za-z0-9_\-.]+\.zip$/', $filename) === 1
            && !str_contains($filename, '..');
    }

    private function restoreImages(string $source, string $destination): void
    {
        $allowed = ['jpg', 'jpeg', 'png'];

        foreach (File::allFiles($source) as $file) {
            if (!in_array(strtolower($file->getExtension()), $allowed, true)) {
                Log::warning('Backup restore: skipped non-image file ' . $file->getRelativePathname());
                continue;
            }

            $target = $destination . DIRECTORY_SEPARATOR . $file->getRelativePathname();
            File::ensureDirectoryExists(dirname($target));
            File::copy($file->getRealPath(), $target);
        }
    }

    private function deleteDirectory($dir) {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), array('.', '..'));
        
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                unlink($path);
            }
        }
        
        rmdir($dir);
    }
}
