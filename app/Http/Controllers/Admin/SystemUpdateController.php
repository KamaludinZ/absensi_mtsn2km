<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use ZipArchive;

class SystemUpdateController extends Controller
{
    /**
     * Show the system update page
     */
    public function index()
    {
        // Get last update info if exists
        $updateLogPath = storage_path('logs/system_updates.log');
        $lastUpdate = null;

        if (File::exists($updateLogPath)) {
            $logs = File::get($updateLogPath);
            $lines = array_filter(explode("\n", $logs));
            if (count($lines) > 0) {
                $lastUpdate = end($lines);
            }
        }

        return view('admin.system-update.index', compact('lastUpdate'));
    }

    /**
     * Handle ZIP file upload and system update
     */
    public function upload(Request $request)
    {
        if (config('app.disable_web_updater')) {
            return back()->with('error', 'Update lewat panel admin dinonaktifkan di server ini (Docker/Coolify). Untuk memperbarui aplikasi, lakukan Redeploy dari Coolify.');
        }

        // Validate the upload
        $request->validate([
            'update_zip' => 'required|file|mimes:zip|max:102400', // Max 100MB
        ]);

        try {
            $zipFile = $request->file('update_zip');
            $tempPath = storage_path('app/temp_updates');
            $extractPath = storage_path('app/extracted_update');

            // Create temp directories if they don't exist
            if (!File::exists($tempPath)) {
                File::makeDirectory($tempPath, 0755, true);
            }

            if (!File::exists($extractPath)) {
                File::makeDirectory($extractPath, 0755, true);
            } else {
                // Clean existing extracted files
                File::deleteDirectory($extractPath);
                File::makeDirectory($extractPath, 0755, true);
            }

            // Move uploaded file to temp location
            $zipPath = $tempPath . '/' . time() . '_update.zip';
            $zipFile->move($tempPath, basename($zipPath));

            // Extract ZIP file
            $zip = new ZipArchive;
            if ($zip->open($zipPath) !== true) {
                return back()->with('error', 'Gagal membuka file ZIP. File mungkin rusak.');
            }

            $zip->extractTo($extractPath);
            $zip->close();

            // Validate extracted contents
            $validationResult = $this->validateUpdateContents($extractPath);
            if (!$validationResult['valid']) {
                // Clean up
                File::delete($zipPath);
                File::deleteDirectory($extractPath);
                return back()->with('error', 'Validasi gagal: ' . $validationResult['message']);
            }

            // Backup current system
            $backupResult = $this->createBackup();
            if (!$backupResult['success']) {
                File::delete($zipPath);
                File::deleteDirectory($extractPath);
                return back()->with('error', 'Gagal membuat backup: ' . $backupResult['message']);
            }

            // Apply updates
            $updateResult = $this->applyUpdates($extractPath);

            if ($updateResult['success']) {
                // Log the update
                $this->logUpdate($zipFile->getClientOriginalName(), $backupResult['backup_path']);

                // Clean up temp files
                File::delete($zipPath);
                File::deleteDirectory($extractPath);

                // Clear caches
                Artisan::call('config:clear');
                Artisan::call('cache:clear');
                Artisan::call('route:clear');
                Artisan::call('view:clear');

                return back()->with('success', 'Update berhasil diterapkan! Backup tersimpan di: ' . basename($backupResult['backup_path']));
            } else {
                // Restore from backup
                $this->restoreBackup($backupResult['backup_path']);

                File::delete($zipPath);
                File::deleteDirectory($extractPath);

                return back()->with('error', 'Update gagal diterapkan: ' . $updateResult['message'] . '. System dikembalikan ke backup.');
            }

        } catch (\Exception $e) {
            Log::error('System update failed: ' . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Validate the extracted update contents
     */
    private function validateUpdateContents($extractPath)
    {
        // Check if there's a validation file
        $validationFile = $extractPath . '/update_manifest.json';

        if (!File::exists($validationFile)) {
            return [
                'valid' => false,
                'message' => 'File update_manifest.json tidak ditemukan. ZIP harus berisi manifest update.'
            ];
        }

        try {
            $manifest = json_decode(File::get($validationFile), true);

            if (!isset($manifest['version']) || !isset($manifest['files'])) {
                return [
                    'valid' => false,
                    'message' => 'Format manifest tidak valid. Harus berisi version dan files.'
                ];
            }

            if (!is_array($manifest['files'])) {
                return ['valid' => false, 'message' => 'Format manifest tidak valid: files harus berupa daftar.'];
            }

            // Check for dangerous files
            $dangerousExtensions = ['.exe', '.bat', '.cmd', '.sh', '.ps1'];
            foreach ($manifest['files'] as $file) {
                $pathError = $this->unsafePathReason($file);
                if ($pathError) {
                    return [
                        'valid' => false,
                        'message' => 'Path file tidak diizinkan (' . $pathError . '): ' . (is_string($file) ? $file : '?')
                    ];
                }

                $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                if (in_array('.' . $extension, $dangerousExtensions)) {
                    return [
                        'valid' => false,
                        'message' => 'File berbahaya terdeteksi: ' . $file
                    ];
                }
            }

            return ['valid' => true, 'manifest' => $manifest];

        } catch (\Exception $e) {
            return [
                'valid' => false,
                'message' => 'Error parsing manifest: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Update files must stay inside the application and must not touch
     * secrets, dependencies, runtime data or git metadata.
     */
    private function unsafePathReason($file): ?string
    {
        if (!is_string($file) || $file === '') {
            return 'kosong';
        }

        $normalized = str_replace('\\', '/', $file);

        if (str_starts_with($normalized, '/') || preg_match('/^[A-Za-z]:/', $normalized)) {
            return 'path absolut';
        }

        if (in_array('..', explode('/', $normalized), true)) {
            return 'keluar dari folder aplikasi';
        }

        $relative = preg_replace('#^(\./)+#', '', $normalized);
        $protected = ['.env', 'vendor/', 'storage/', '.git/', 'bootstrap/cache/', 'node_modules/'];
        foreach ($protected as $prefix) {
            if (str_starts_with($relative, $prefix)) {
                return 'folder/file dilindungi';
            }
        }

        return null;
    }

    /**
     * Create backup of current system
     */
    private function createBackup()
    {
        try {
            $backupPath = storage_path('app/backups');

            if (!File::exists($backupPath)) {
                File::makeDirectory($backupPath, 0755, true);
            }

            $backupFile = $backupPath . '/backup_' . date('Y-m-d_H-i-s') . '.zip';

            $zip = new ZipArchive;
            if ($zip->open($backupFile, ZipArchive::CREATE) !== true) {
                return ['success' => false, 'message' => 'Tidak dapat membuat file backup'];
            }

            // Backup critical directories
            $criticalPaths = [
                'app',
                'config',
                'routes',
                'resources/views',
                'public'
            ];

            $basePath = base_path();

            foreach ($criticalPaths as $path) {
                $fullPath = $basePath . '/' . $path;
                if (File::exists($fullPath)) {
                    $this->addToZip($zip, $fullPath, $path);
                }
            }

            $zip->close();

            return ['success' => true, 'backup_path' => $backupFile];

        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Recursively add files to ZIP
     */
    private function addToZip($zip, $sourcePath, $localPath)
    {
        if (File::isDirectory($sourcePath)) {
            $files = File::files($sourcePath);
            $directories = File::directories($sourcePath);

            foreach ($files as $file) {
                $zip->addFile($file->getPathname(), $localPath . '/' . $file->getFilename());
            }

            foreach ($directories as $directory) {
                $dirName = basename($directory);
                $this->addToZip($zip, $directory, $localPath . '/' . $dirName);
            }
        } else {
            $zip->addFile($sourcePath, $localPath);
        }
    }

    /**
     * Apply updates from extracted files
     */
    private function applyUpdates($extractPath)
    {
        try {
            $manifestFile = $extractPath . '/update_manifest.json';
            $manifest = json_decode(File::get($manifestFile), true);

            $basePath = base_path();
            $updatedFiles = [];

            foreach ($manifest['files'] as $file) {
                $sourcePath = $extractPath . '/' . $file;
                $destinationPath = $basePath . '/' . $file;

                if (!File::exists($sourcePath)) {
                    continue;
                }

                // Create directory if it doesn't exist
                $destinationDir = dirname($destinationPath);
                if (!File::exists($destinationDir)) {
                    File::makeDirectory($destinationDir, 0755, true);
                }

                // Copy file
                if (File::copy($sourcePath, $destinationPath)) {
                    $updatedFiles[] = $file;
                } else {
                    return [
                        'success' => false,
                        'message' => 'Gagal menyalin file: ' . $file
                    ];
                }
            }

            // Run migrations if specified
            if (isset($manifest['run_migrations']) && $manifest['run_migrations']) {
                Artisan::call('migrate', ['--force' => true]);
            }

            return [
                'success' => true,
                'updated_files' => $updatedFiles,
                'count' => count($updatedFiles)
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Restore system from backup
     */
    private function restoreBackup($backupPath)
    {
        try {
            $zip = new ZipArchive;
            if ($zip->open($backupPath) !== true) {
                return false;
            }

            $zip->extractTo(base_path());
            $zip->close();

            return true;

        } catch (\Exception $e) {
            Log::error('Backup restoration failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Log the update
     */
    private function logUpdate($filename, $backupPath)
    {
        $logPath = storage_path('logs/system_updates.log');
        $logEntry = sprintf(
            "[%s] Update applied from: %s | Backup: %s\n",
            now()->format('Y-m-d H:i:s'),
            $filename,
            basename($backupPath)
        );

        File::append($logPath, $logEntry);
    }

    /**
     * Show backup list
     */
    public function backups()
    {
        $backupPath = storage_path('app/backups');
        $backups = [];

        if (File::exists($backupPath)) {
            $files = File::files($backupPath);
            foreach ($files as $file) {
                $backups[] = [
                    'name' => $file->getFilename(),
                    'size' => $this->formatBytes($file->getSize()),
                    'date' => date('Y-m-d H:i:s', $file->getMTime()),
                    'path' => $file->getPathname()
                ];
            }

            // Sort by date descending
            usort($backups, function($a, $b) {
                return strtotime($b['date']) - strtotime($a['date']);
            });
        }

        return view('admin.system-update.backups', compact('backups'));
    }

    /**
     * Download backup file
     */
    public function downloadBackup($filename)
    {
        if (!preg_match('/^[A-Za-z0-9_\-.]+\.zip$/', $filename) || str_contains($filename, '..')) {
            return back()->with('error', 'Nama file backup tidak valid.');
        }

        $backupPath = storage_path('app/backups/' . $filename);

        if (!File::exists($backupPath)) {
            return back()->with('error', 'File backup tidak ditemukan.');
        }

        return response()->download($backupPath);
    }

    /**
     * Format bytes to human readable
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
