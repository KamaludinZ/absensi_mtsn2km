<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

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
            Artisan::call('backup:run', ['--only-db' => true]);

            return redirect()->route('admin.backup.index')
                ->with('success', 'Backup database berhasil dibuat.');
        } catch (\Exception $e) {
            return redirect()->route('admin.backup.index')
                ->with('error', 'Gagal membuat backup: ' . $e->getMessage());
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
            // Get the backup file path
            $fullPath = Storage::disk('backups')->path($backupPath);

            // Since the backup file is likely a zip file, we need to handle it appropriately
            $zip = new \ZipArchive();
            if ($zip->open($fullPath) === TRUE) {
                // For security reasons, we'll create a temporary directory to extract the backup
                $tempDir = storage_path('app/temp_restore_' . time());
                mkdir($tempDir, 0755, true);

                // Extract the backup
                $zip->extractTo($tempDir);
                $zip->close();

                // Look for database dump files in db-dumps folder or root
                $sqlFiles = array_merge(
                    glob($tempDir . '/db-dumps/*.sql'),
                    glob($tempDir . '/*.sql')
                );

                if (!empty($sqlFiles)) {
                    $dbConnection = config('database.default');
                    $sqlFile = $sqlFiles[0]; // Use the first SQL file found

                    if ($dbConnection === 'pgsql') {
                        // PostgreSQL restore using psql command
                        $dbHost = config('database.connections.pgsql.host');
                        $dbPort = config('database.connections.pgsql.port');
                        $dbName = config('database.connections.pgsql.database');
                        $dbUser = config('database.connections.pgsql.username');
                        $dbPass = config('database.connections.pgsql.password');

                        // Set PGPASSWORD environment variable for passwordless authentication
                        putenv("PGPASSWORD=" . $dbPass);

                        // Execute psql command to restore database
                        $command = sprintf(
                            'psql -h %s -p %s -U %s -d %s -f %s 2>&1',
                            escapeshellarg($dbHost),
                            escapeshellarg($dbPort),
                            escapeshellarg($dbUser),
                            escapeshellarg($dbName),
                            escapeshellarg($sqlFile)
                        );

                        exec($command, $output, $returnVar);

                        // Unset PGPASSWORD
                        putenv("PGPASSWORD");

                        if ($returnVar !== 0) {
                            // Clean up
                            $this->deleteDirectory($tempDir);

                            Log::error('PostgreSQL restore failed: ' . implode("\n", $output));

                            return redirect()->route('admin.backup.restore')
                                ->with('error', 'Gagal melakukan restore PostgreSQL. Lihat log untuk detail.');
                        }
                    } else {
                        // MySQL/MariaDB restore using traditional method
                        $sqlContent = file_get_contents($sqlFile);

                        // Execute the SQL commands on the database
                        $db = app('db')->connection();

                        // Use unprepared for better compatibility with large SQL files
                        $db->unprepared($sqlContent);
                    }

                    // Clean up temporary directory
                    $this->deleteDirectory($tempDir);

                    return redirect()->route('admin.backup.index')
                        ->with('success', 'Restore database berhasil dilakukan.');
                } else {
                    // If no SQL files found, we might have a different backup format
                    // Clean up
                    $this->deleteDirectory($tempDir);

                    return redirect()->route('admin.backup.restore')
                        ->with('error', 'File backup tidak berisi data database yang dapat direstore.');
                }
            } else {
                return redirect()->route('admin.backup.restore')
                    ->with('error', 'Gagal membuka file backup.');
            }
        } catch (\Exception $e) {
            Log::error('Backup restore error: ' . $e->getMessage());

            // Make sure to clean up in case of error
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
