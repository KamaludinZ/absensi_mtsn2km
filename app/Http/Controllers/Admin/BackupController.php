<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

class BackupController extends Controller
{
    public function index()
    {
        $backups = [];

        if (Storage::disk('local')->exists('backups')) {
            $files = Storage::disk('local')->files('backups');

            foreach ($files as $file) {
                $backups[] = [
                    'name' => basename($file),
                    'size' => Storage::disk('local')->size($file),
                    'date' => Storage::disk('local')->lastModified($file),
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

        if (!Storage::disk('local')->exists($path)) {
            return redirect()->route('admin.backup.index')
                ->with('error', 'File backup tidak ditemukan.');
        }

        return Storage::disk('local')->download($path);
    }

    public function delete($filename)
    {
        $path = 'backups/' . $filename;

        if (Storage::disk('local')->exists($path)) {
            Storage::disk('local')->delete($path);

            return redirect()->route('admin.backup.index')
                ->with('success', 'File backup berhasil dihapus.');
        }

        return redirect()->route('admin.backup.index')
            ->with('error', 'File backup tidak ditemukan.');
    }
    
    public function showRestore()
    {
        $backups = [];
        
        if (Storage::disk('local')->exists('backups')) {
            $files = Storage::disk('local')->files('backups');

            foreach ($files as $file) {
                $backups[] = [
                    'name' => basename($file),
                    'size' => Storage::disk('local')->size($file),
                    'date' => Storage::disk('local')->lastModified($file),
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
        
        if (!Storage::disk('local')->exists($backupPath)) {
            return redirect()->route('admin.backup.restore')
                ->with('error', 'File backup tidak ditemukan.');
        }
        
        try {
            // Get the backup file path
            $fullPath = storage_path('app/' . $backupPath);
            
            // Since the backup file is likely a zip file, we need to handle it appropriately
            $zip = new \ZipArchive();
            if ($zip->open($fullPath) === TRUE) {
                // For security reasons, we'll create a temporary directory to extract the backup
                $tempDir = storage_path('app/temp_restore_' . time());
                mkdir($tempDir, 0755, true);
                
                // Extract the backup
                $zip->extractTo($tempDir);
                $zip->close();
                
                // Look for database dump files (usually .sql files)
                $sqlFiles = glob($tempDir . '/*.sql');
                
                if (!empty($sqlFiles)) {
                    foreach ($sqlFiles as $sqlFile) {
                        $sqlContent = file_get_contents($sqlFile);
                        
                        // Execute the SQL commands on the database
                        $db = app('db')->connection();
                        
                        // Split the SQL content into individual statements
                        $statements = array_filter(
                            array_map('trim', explode(";\n", $sqlContent)),
                            function ($statement) {
                                return !empty($statement);
                            }
                        );
                        
                        // Execute each statement
                        foreach ($statements as $statement) {
                            if (trim($statement) !== '') {
                                $db->unprepared($statement);
                            }
                        }
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
            \Log::error('Backup restore error: ' . $e->getMessage());
            
            // Make sure to clean up in case of error
            $tempDir = storage_path('app/temp_restore_*');
            array_map('unlink', glob($tempDir));
            
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
