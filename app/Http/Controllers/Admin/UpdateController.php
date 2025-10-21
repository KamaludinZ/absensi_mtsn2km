<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class UpdateController extends Controller
{
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
     * Check for updates
     */
    public function check()
    {
        try {
            $currentVersion = $this->getCurrentVersion();

            // Check if git is available
            if (!$this->isGitAvailable()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Git tidak tersedia di sistem ini.'
                ]);
            }

            // Fetch latest from remote
            exec('git fetch origin 2>&1', $output, $returnCode);

            if ($returnCode !== 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal mengecek update dari remote repository.'
                ]);
            }

            // Get latest commit hash
            exec('git rev-parse origin/main 2>&1', $latestCommit);
            exec('git rev-parse HEAD 2>&1', $currentCommit);

            $hasUpdate = trim($latestCommit[0] ?? '') !== trim($currentCommit[0] ?? '');

            // Get commits behind
            exec('git rev-list HEAD..origin/main --count 2>&1', $commitsBehind);
            $behindCount = (int)($commitsBehind[0] ?? 0);

            return response()->json([
                'success' => true,
                'hasUpdate' => $hasUpdate,
                'currentVersion' => $currentVersion,
                'behindCount' => $behindCount,
                'message' => $hasUpdate
                    ? "Ada {$behindCount} update tersedia."
                    : 'Aplikasi sudah menggunakan versi terbaru.'
            ]);

        } catch (\Exception $e) {
            Log::error('Error checking for updates: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Perform update
     */
    public function update(Request $request)
    {
        try {
            if (!$this->isGitAvailable()) {
                return back()->with('error', 'Git tidak tersedia di sistem ini.');
            }

            $beforeVersion = $this->getCurrentVersion();

            // Create backup before update
            Log::info('Creating backup before update...');
            Artisan::call('backup:run --only-db');

            // Stash local changes
            exec('git stash 2>&1', $stashOutput);
            Log::info('Stashed local changes: ' . implode("\n", $stashOutput));

            // Pull latest changes
            exec('git pull origin main 2>&1', $pullOutput, $pullReturnCode);

            if ($pullReturnCode !== 0) {
                Log::error('Git pull failed: ' . implode("\n", $pullOutput));
                return back()->with('error', 'Gagal melakukan update: ' . implode("\n", $pullOutput));
            }

            // Run migrations
            Log::info('Running migrations...');
            Artisan::call('migrate', ['--force' => true]);

            // Clear caches
            Log::info('Clearing caches...');
            Artisan::call('optimize:clear');

            // Install/update dependencies if composer.json changed
            if (in_array('composer.json', $pullOutput) || in_array('composer.lock', $pullOutput)) {
                Log::info('Installing composer dependencies...');
                exec('composer install --no-dev --optimize-autoloader 2>&1', $composerOutput);
                Log::info('Composer output: ' . implode("\n", $composerOutput));
            }

            $afterVersion = $this->getCurrentVersion();

            // Log update
            $this->logUpdate($beforeVersion, $afterVersion);

            Log::info("Update completed successfully from {$beforeVersion} to {$afterVersion}");

            return back()->with('success', "Aplikasi berhasil diupdate dari versi {$beforeVersion} ke {$afterVersion}.");

        } catch (\Exception $e) {
            Log::error('Error during update: ' . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan saat update: ' . $e->getMessage());
        }
    }

    /**
     * Get current version from git
     */
    private function getCurrentVersion()
    {
        try {
            if (!$this->isGitAvailable()) {
                return 'Unknown';
            }

            exec('git describe --tags --abbrev=0 2>&1', $tagOutput, $tagReturn);

            if ($tagReturn === 0 && !empty($tagOutput[0])) {
                return trim($tagOutput[0]);
            }

            exec('git rev-parse --short HEAD 2>&1', $commitOutput);
            return trim($commitOutput[0] ?? 'Unknown');

        } catch (\Exception $e) {
            return 'Unknown';
        }
    }

    /**
     * Check if git is available
     */
    private function isGitAvailable()
    {
        exec('git --version 2>&1', $output, $returnCode);
        return $returnCode === 0;
    }

    /**
     * Get git status
     */
    private function getGitStatus()
    {
        try {
            if (!$this->isGitAvailable()) {
                return [
                    'available' => false,
                    'branch' => 'N/A',
                    'lastCommit' => 'N/A',
                    'lastCommitDate' => 'N/A'
                ];
            }

            exec('git branch --show-current 2>&1', $branchOutput);
            exec('git log -1 --pretty=format:"%h - %s" 2>&1', $lastCommit);
            exec('git log -1 --pretty=format:"%ar" 2>&1', $lastCommitDate);

            return [
                'available' => true,
                'branch' => trim($branchOutput[0] ?? 'N/A'),
                'lastCommit' => trim($lastCommit[0] ?? 'N/A'),
                'lastCommitDate' => trim($lastCommitDate[0] ?? 'N/A')
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
