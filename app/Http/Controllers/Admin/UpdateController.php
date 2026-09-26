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

    public function __construct()
    {
        $this->githubToken = env('GITHUB_TOKEN');
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
            // Validate GitHub token
            if (empty($this->githubToken)) {
                return response()->json([
                    'success' => false,
                    'message' => 'GitHub token belum dikonfigurasi. Silakan tambahkan GITHUB_TOKEN di file .env'
                ], 400);
            }

            // Get latest releases from GitHub
            $response = Http::withToken($this->githubToken)
                ->timeout(30)
                ->get("https://api.github.com/repos/{$this->repoOwner}/{$this->repoName}/releases");

            if (!$response->successful()) {
                $statusCode = $response->status();
                $errorMessage = 'Gagal mengecek update dari GitHub.';

                if ($statusCode === 401) {
                    $errorMessage = 'Token GitHub tidak valid atau sudah expired. Silakan perbarui GITHUB_TOKEN di .env';
                } elseif ($statusCode === 404) {
                    $errorMessage = 'Repository tidak ditemukan. Periksa GITHUB_REPO_OWNER dan GITHUB_REPO_NAME di .env';
                } elseif ($statusCode === 403) {
                    $errorMessage = 'Akses ditolak. Periksa permission token GitHub atau rate limit telah tercapai.';
                }

                return response()->json([
                    'success' => false,
                    'message' => $errorMessage . ' (HTTP ' . $statusCode . ')'
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
        if (config('app.disable_web_updater')) {
            return back()->with('error', 'Update lewat panel admin dinonaktifkan di server ini (Docker/Coolify). Untuk memperbarui aplikasi, lakukan Redeploy dari Coolify.');
        }

        try {
            // Validate GitHub token
            if (empty($this->githubToken)) {
                return back()->with('error', 'GitHub token belum dikonfigurasi. Silakan tambahkan GITHUB_TOKEN di file .env');
            }

            // Create backup before update
            Log::info('Creating backup before update...');
            Artisan::call('backup:run --only-db');

            $currentVersion = $this->getCurrentVersion();

            // Get latest release from GitHub
            $response = Http::withToken($this->githubToken)
                ->timeout(30)
                ->get("https://api.github.com/repos/{$this->repoOwner}/{$this->repoName}/releases/latest");

            if (!$response->successful()) {
                $statusCode = $response->status();
                if ($statusCode === 401) {
                    return back()->with('error', 'Token GitHub tidak valid atau sudah expired.');
                } elseif ($statusCode === 404) {
                    return back()->with('error', 'Repository tidak ditemukan atau tidak ada release.');
                }
                return back()->with('error', 'Gagal mengambil informasi update dari GitHub (HTTP ' . $statusCode . ').');
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
     * Perform actual update process
     */
    private function simulateUpdate($extractedDir)
    {
        Log::info('Starting file update from: ' . $extractedDir);

        // Files and directories to exclude from update
        $excludePaths = [
            '.env',
            '.env.example',
            'storage',
            'bootstrap/cache',
            '.git',
            '.gitignore',
            'node_modules',
            'vendor',
            'VERSION',
            'public/storage',
        ];

        try {
            // Copy files recursively
            $this->copyDirectory($extractedDir, base_path(), $excludePaths);

            Log::info('Files updated successfully');
        } catch (\Exception $e) {
            Log::error('Error updating files: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Copy directory recursively with exclusions
     */
    private function copyDirectory($source, $destination, $exclude = [])
    {
        if (!is_dir($source)) {
            return;
        }

        $dir = opendir($source);

        while (false !== ($file = readdir($dir))) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $sourcePath = $source . DIRECTORY_SEPARATOR . $file;
            $destPath = $destination . DIRECTORY_SEPARATOR . $file;

            // Check if this path should be excluded
            $shouldExclude = false;
            foreach ($exclude as $excludePath) {
                if ($file === $excludePath || str_starts_with($file, $excludePath)) {
                    $shouldExclude = true;
                    break;
                }
            }

            if ($shouldExclude) {
                Log::info('Skipping excluded path: ' . $file);
                continue;
            }

            if (is_dir($sourcePath)) {
                // Create directory if it doesn't exist
                if (!File::exists($destPath)) {
                    File::makeDirectory($destPath, 0755, true);
                }
                // Recursively copy directory
                $this->copyDirectory($sourcePath, $destPath, $exclude);
            } else {
                // Copy file
                File::copy($sourcePath, $destPath);
                Log::info('Copied: ' . $file);
            }
        }

        closedir($dir);
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

        // Fallback to default if VERSION file doesn't exist
        return '1.0.0';
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
     * Get Git status
     */
    private function getGitStatus()
    {
        try {
            // Check if .git directory exists
            if (!File::exists(base_path('.git'))) {
                return [
                    'available' => false,
                    'branch' => 'N/A',
                    'lastCommit' => 'N/A',
                    'lastCommitDate' => 'N/A',
                    'message' => 'Git repository tidak ditemukan'
                ];
            }

            // Try to get git branch
            $branch = trim(shell_exec('git branch --show-current 2>&1') ?? 'unknown');

            // Try to get last commit hash
            $lastCommit = trim(shell_exec('git rev-parse --short HEAD 2>&1') ?? 'unknown');

            // Try to get last commit date
            $lastCommitDate = trim(shell_exec('git log -1 --format=%cd --date=format:"%Y-%m-%d %H:%M:%S" 2>&1') ?? 'unknown');

            // Check if git command is available
            if (str_contains($branch, 'not found') || str_contains($branch, 'not recognized')) {
                return [
                    'available' => false,
                    'branch' => 'N/A',
                    'lastCommit' => 'N/A',
                    'lastCommitDate' => 'N/A',
                    'message' => 'Git command tidak tersedia'
                ];
            }

            return [
                'available' => true,
                'branch' => $branch,
                'lastCommit' => $lastCommit,
                'lastCommitDate' => $lastCommitDate
            ];
        } catch (\Exception $e) {
            return [
                'available' => false,
                'branch' => 'N/A',
                'lastCommit' => 'N/A',
                'lastCommitDate' => 'N/A',
                'message' => 'Error: ' . $e->getMessage()
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