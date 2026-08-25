<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
            $backupPath = storage_path('app/backups/backups/' . $backupName);

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

            // 2. Backup selfie photos (attendance photos)
            $selfiePath = storage_path('app/public/selfies');
            if (File::exists($selfiePath)) {
                $this->addDirectoryToZip($zip, $selfiePath, 'files/selfies');
            }

            // 3. Backup settings/configuration files
            $settingsPath = storage_path('app/settings');
            if (File::exists($settingsPath)) {
                $this->addDirectoryToZip($zip, $settingsPath, 'files/settings');
            }

            // 4. Backup .env file (configuration)
            $envFile = base_path('.env');
            if (File::exists($envFile)) {
                $zip->addFile($envFile, 'config/.env');
            }

            // 5. Add backup metadata
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
                $host = config('database.connections.pgsql.host');
                $port = config('database.connections.pgsql.port');
                $database = config('database.connections.pgsql.database');
                $username = config('database.connections.pgsql.username');
                $password = config('database.connections.pgsql.password');

                putenv("PGPASSWORD=" . $password);

                $command = sprintf(
                    'pg_dump -h %s -p %s -U %s -d %s -f %s 2>&1',
                    escapeshellarg($host),
                    escapeshellarg($port),
                    escapeshellarg($username),
                    escapeshellarg($database),
                    escapeshellarg($tempFile)
                );

                exec($command, $output, $returnVar);
                putenv("PGPASSWORD");

                if ($returnVar !== 0 || !File::exists($tempFile)) {
                    throw new \Exception('pg_dump failed');
                }
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

            // 2. Restore Files (selfies/photos)
            $selfiesSource = $tempDir . '/files/selfies';
            if (File::exists($selfiesSource)) {
                $selfiesDest = storage_path('app/public/selfies');
                File::ensureDirectoryExists($selfiesDest);
                File::copyDirectory($selfiesSource, $selfiesDest);
            }

            // 3. Restore Settings
            $settingsSource = $tempDir . '/files/settings';
            if (File::exists($settingsSource)) {
                $settingsDest = storage_path('app/settings');
                File::ensureDirectoryExists($settingsDest);
                File::copyDirectory($settingsSource, $settingsDest);
            }

            // 4. Restore .env (optional - with confirmation)
            $envSource = $tempDir . '/config/.env';
            if (File::exists($envSource) && $request->input('restore_env') === 'yes') {
                File::copy($envSource, base_path('.env'));
            }

            // Clean up
            $this->deleteDirectory($tempDir);

            return redirect()->route('admin.backup.index')
                ->with('success', 'Restore berhasil dilakukan (Database + Files).');
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
            $host = config('database.connections.pgsql.host');
            $port = config('database.connections.pgsql.port');
            $database = config('database.connections.pgsql.database');
            $username = config('database.connections.pgsql.username');
            $password = config('database.connections.pgsql.password');

            putenv("PGPASSWORD=" . $password);

            $command = sprintf(
                'psql -h %s -p %s -U %s -d %s -f %s 2>&1',
                escapeshellarg($host),
                escapeshellarg($port),
                escapeshellarg($username),
                escapeshellarg($database),
                escapeshellarg($sqlFile)
            );

            exec($command, $output, $returnVar);
            putenv("PGPASSWORD");

            if ($returnVar !== 0) {
                throw new \Exception('PostgreSQL restore failed: ' . implode("\n", $output));
            }
        } else {
            // Fallback: Use Laravel DB
            $sqlContent = File::get($sqlFile);
            DB::unprepared($sqlContent);
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
