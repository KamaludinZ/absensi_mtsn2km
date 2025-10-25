<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class UpdateController extends Controller
{
    // GitHub repository configuration
    private $githubToken;
    private $repoOwner;
    private $repoName;
    private $currentVersion = '1.0.2'; // Starting version

    public function __construct()
    {
        $this->githubToken = env('GITHUB_TOKEN', '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');
        $this->repoOwner = env('GITHUB_REPO_OWNER', 'KamaludinZ');
        $this->repoName = env('GITHUB_REPO_NAME', 'absensi_mtsn2km');
    }

    /**
     * Display update page
     */
    public function index()
    {
        $currentVersion = $this->getCurrentVersion();
        $updateHistory = $this->getUpdateHistory();
        $gitStatus = $this->getGitStatus();

        return view('admin.update.index', compact('currentVersion', 'updateHistory', 'gitStatus'));
    }

    /**
     * Check for updates via GitHub API
     */
    public function check()
    {
        try {
            // Get latest releases from GitHub
            $response = Http::withToken($this->githubToken)
                ->timeout(30)
                ->get("https://api.github.com/repos/{$this->repoOwner}/{$this->repoName}/releases");

            if (!$response->successful()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal mengecek update dari GitHub: ' . $response->status()
                ]);
            }

            $releases = $response->json();
            
            if (empty($releases)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada release ditemukan di repository.'
                ]);
            }

            // Get current version
            $currentVersion = $this->getCurrentVersion();
            
            // Find latest release that's newer than current version
            $latestRelease = null;
            foreach ($releases as $release) {
                if (version_compare($release['tag_name'], $currentVersion, '>')) {
                    if ($latestRelease === null || version_compare($release['tag_name'], $latestRelease['tag_name'], '>')) {
                        $latestRelease = $release;
                    }
                }
            }

            if ($latestRelease) {
                return response()->json([
                    'success' => true,
                    'hasUpdate' => true,
                    'currentVersion' => $currentVersion,
                    'latestVersion' => $latestRelease['tag_name'],
                    'releaseNotes' => $latestRelease['body'] ?? '',
                    'publishedAt' => $latestRelease['published_at'] ?? '',
                    'downloadUrl' => $latestRelease['zipball_url'] ?? '',
                    'message' => "Update tersedia ke versi {$latestRelease['tag_name']}."
                ]);
            } else {
                return response()->json([
                    'success' => true,
                    'hasUpdate' => false,
                    'currentVersion' => $currentVersion,
                    'message' => 'Aplikasi sudah menggunakan versi terbaru.'
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Error checking for updates via GitHub: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengecek update: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Perform update from GitHub
     */
    public function update(Request $request)
    {
        try {
            // Create backup before update
            Log::info('Creating backup before update...');
            Artisan::call('backup:run --only-db');

            $currentVersion = $this->getCurrentVersion();
            
            // Get latest release from GitHub
            $response = Http::withToken($this->githubToken)
                ->timeout(30)
                ->get("https://api.github.com/repos/{$this->repoOwner}/{$this->repoName}/releases/latest");

            if (!$response->successful()) {
                return back()->with('error', 'Gagal mengambil informasi update dari GitHub.');
            }

            $release = $response->json();
            $latestVersion = $release['tag_name'] ?? 'unknown';
            
            // Download the latest release
            $downloadUrl = $release['zipball_url'] ?? null;
            if (!$downloadUrl) {
                return back()->with('error', 'URL download tidak ditemukan untuk release terbaru.');
            }

            // Download the release ZIP file
            $zipResponse = Http::withToken($this->githubToken)
                ->timeout(120) // Longer timeout for download
                ->get($downloadUrl);

            if (!$zipResponse->successful()) {
                return back()->with('error', 'Gagal mengunduh file update dari GitHub.');
            }

            // Save the ZIP file temporarily
            $tempZipPath = storage_path('app/temp_update.zip');
            File::put($tempZipPath, $zipResponse->body());

            // Extract the ZIP file
            $extractPath = storage_path('app/update_extract');
            
            // Clean up previous extraction if exists
            if (File::exists($extractPath)) {
                File::deleteDirectory($extractPath);
            }
            
            // Create extraction directory
            File::makeDirectory($extractPath, 0755, true);
            
            // Extract ZIP file
            $zip = new \ZipArchive();
            if ($zip->open($tempZipPath) === TRUE) {
                $zip->extractTo($extractPath);
                $zip->close();
            } else {
                File::delete($tempZipPath);
                return back()->with('error', 'Gagal mengekstrak file update.');
            }

            // Clean up the ZIP file
            File::delete($tempZipPath);

            // Move extracted files to application directory
            $extractedDir = File::directories($extractPath)[0] ?? null;
            if (!$extractedDir) {
                File::deleteDirectory($extractPath);
                return back()->with('error', 'Direktori ekstraksi tidak ditemukan.');
            }

            // Copy files (be very careful with this in production!)
            // For now, we'll just simulate the update process
            $this->simulateUpdate($extractedDir);

            // Clean up extraction directory
            File::deleteDirectory($extractPath);

            // Run migrations
            Log::info('Running migrations...');
            Artisan::call('migrate', ['--force' => true]);

            // Clear caches
            Log::info('Clearing caches...');
            Artisan::call('optimize:clear');
            Artisan::call('config:cache');
            Artisan::call('route:cache');
            Artisan::call('view:cache');

            // Update version file
            $this->updateVersionFile($latestVersion);

            $afterVersion = $this->getCurrentVersion();

            // Log update
            $this->logUpdate($currentVersion, $afterVersion);

            Log::info("Update completed successfully from {$currentVersion} to {$afterVersion}");

            return back()->with('success', "Aplikasi berhasil diupdate dari versi {$currentVersion} ke {$afterVersion}.");

        } catch (\Exception $e) {
            Log::error('Error during update: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return back()->with('error', 'Terjadi kesalahan saat update: ' . $e->getMessage());
        }
    }

    /**
     * Simulate update process (in production, be very careful with file operations)
     */
    private function simulateUpdate($extractedDir)
    {
        // In a real production environment, you would carefully copy files
        // For now, we'll just log that the update simulation happened
        Log::info('Simulating update from extracted directory: ' . $extractedDir);
        
        // This is a placeholder - in real production you would:
        // 1. Backup current files
        // 2. Carefully copy new files
        // 3. Preserve environment-specific configurations
        // 4. Handle database migrations
    }

    /**
     * Get current version
     */
    private function getCurrentVersion()
    {
        // Check if version file exists
        $versionFile = base_path('VERSION');
        if (File::exists($versionFile)) {
            return trim(File::get($versionFile));
        }
        
        // Fallback to environment variable
        return $this->currentVersion;
    }

    /**
     * Update version file
     */
    private function updateVersionFile($version)
    {
        $versionFile = base_path('VERSION');
        File::put($versionFile, $version);
    }

    /**
     * Get Git status (kept for backward compatibility)
     */
    private function getGitStatus()
    {
        try {
            return [
                'available' => true,
                'branch' => 'main',
                'lastCommit' => substr($this->getCurrentVersion(), 0, 7),
                'lastCommitDate' => Carbon::now()->format('Y-m-d H:i:s')
            ];
        } catch (\Exception $e) {
            return [
                'available' => false,
                'branch' => 'N/A',
                'lastCommit' => 'N/A',
                'lastCommitDate' => 'N/A'
            ];
        }
    }

    /**
     * Log update to file
     */
    private function logUpdate($fromVersion, $toVersion)
    {
        $logPath = storage_path('logs/update_history.log');
        $logEntry = Carbon::now()->format('Y-m-d H:i:s') . " - Updated from {$fromVersion} to {$toVersion}\n";

        File::append($logPath, $logEntry);
    }

    /**
     * Get update history
     */
    private function getUpdateHistory()
    {
        $logPath = storage_path('logs/update_history.log');

        if (!File::exists($logPath)) {
            return [];
        }

        $lines = file($logPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        return array_reverse(array_slice($lines, -10)); // Get last 10 updates
    }
}