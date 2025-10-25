<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GitHubService
{
    private $token;
    private $owner;
    private $repo;
    private $baseUrl = 'https://api.github.com';

    public function __construct()
    {
        $this->token = config('services.github.token');
        $this->owner = config('services.github.owner');
        $this->repo = config('services.github.repo');
    }

    /**
     * Get latest release from GitHub
     */
    public function getLatestRelease()
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'Accept' => 'application/vnd.github.v3+json',
                'User-Agent' => 'SIAB-Laravel-App'
            ])->get("{$this->baseUrl}/repos/{$this->owner}/{$this->repo}/releases/latest");

            if ($response->successful()) {
                $release = $response->json();

                return [
                    'success' => true,
                    'version' => $release['tag_name'] ?? null,
                    'name' => $release['name'] ?? null,
                    'body' => $release['body'] ?? null,
                    'published_at' => $release['published_at'] ?? null,
                    'download_url' => $this->getZipballUrl($release),
                    'assets' => $release['assets'] ?? [],
                ];
            }

            Log::warning('GitHub API error: ' . $response->body());

            return [
                'success' => false,
                'message' => 'Gagal mengambil data dari GitHub: ' . $response->status()
            ];

        } catch (\Exception $e) {
            Log::error('GitHub API exception: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get all releases from GitHub
     */
    public function getAllReleases($perPage = 10)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'Accept' => 'application/vnd.github.v3+json',
                'User-Agent' => 'SIAB-Laravel-App'
            ])->get("{$this->baseUrl}/repos/{$this->owner}/{$this->repo}/releases", [
                'per_page' => $perPage
            ]);

            if ($response->successful()) {
                $releases = $response->json();

                return [
                    'success' => true,
                    'releases' => array_map(function($release) {
                        return [
                            'version' => $release['tag_name'] ?? null,
                            'name' => $release['name'] ?? null,
                            'body' => $release['body'] ?? null,
                            'published_at' => $release['published_at'] ?? null,
                            'download_url' => $this->getZipballUrl($release),
                            'prerelease' => $release['prerelease'] ?? false,
                        ];
                    }, $releases)
                ];
            }

            return [
                'success' => false,
                'message' => 'Gagal mengambil data releases dari GitHub'
            ];

        } catch (\Exception $e) {
            Log::error('GitHub getAllReleases exception: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Download release ZIP from GitHub
     */
    public function downloadRelease($version)
    {
        try {
            Log::info("Downloading release from GitHub: {$version}");

            // Get release info
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'Accept' => 'application/vnd.github.v3+json',
                'User-Agent' => 'SIAB-Laravel-App'
            ])->get("{$this->baseUrl}/repos/{$this->owner}/{$this->repo}/releases/tags/{$version}");

            if (!$response->successful()) {
                throw new \Exception('Release tidak ditemukan: ' . $version);
            }

            $release = $response->json();

            // Try to find ZIP asset first
            $downloadUrl = null;
            if (!empty($release['assets'])) {
                foreach ($release['assets'] as $asset) {
                    if (str_ends_with($asset['name'], '.zip')) {
                        $downloadUrl = $asset['browser_download_url'];
                        break;
                    }
                }
            }

            // Fallback to zipball_url if no ZIP asset found
            if (!$downloadUrl) {
                $downloadUrl = $release['zipball_url'];
            }

            Log::info("Download URL: {$downloadUrl}");

            // Download the file
            $fileResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'Accept' => 'application/vnd.github.v3+json',
                'User-Agent' => 'SIAB-Laravel-App'
            ])->timeout(300) // 5 minutes timeout for large files
              ->get($downloadUrl);

            if (!$fileResponse->successful()) {
                throw new \Exception('Gagal download file dari GitHub');
            }

            // Save to temporary file
            $tempPath = storage_path('app/temp/github-update-' . time() . '.zip');
            $tempDir = dirname($tempPath);

            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            file_put_contents($tempPath, $fileResponse->body());

            Log::info("Downloaded to: {$tempPath}");

            return [
                'success' => true,
                'path' => $tempPath,
                'size' => filesize($tempPath)
            ];

        } catch (\Exception $e) {
            Log::error('Download release error: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Check if update is available
     */
    public function checkForUpdates($currentVersion)
    {
        $release = $this->getLatestRelease();

        if (!$release['success']) {
            return $release;
        }

        $latestVersion = $release['version'];

        // Remove 'v' prefix for comparison
        $current = ltrim($currentVersion, 'v');
        $latest = ltrim($latestVersion, 'v');

        // Compare versions
        $updateAvailable = version_compare($latest, $current, '>');

        return [
            'success' => true,
            'update_available' => $updateAvailable,
            'current_version' => $currentVersion,
            'latest_version' => $latestVersion,
            'release_info' => $release
        ];
    }

    /**
     * Get repository information
     */
    public function getRepoInfo()
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'Accept' => 'application/vnd.github.v3+json',
                'User-Agent' => 'SIAB-Laravel-App'
            ])->get("{$this->baseUrl}/repos/{$this->owner}/{$this->repo}");

            if ($response->successful()) {
                $repo = $response->json();

                return [
                    'success' => true,
                    'name' => $repo['name'] ?? null,
                    'full_name' => $repo['full_name'] ?? null,
                    'description' => $repo['description'] ?? null,
                    'private' => $repo['private'] ?? false,
                    'updated_at' => $repo['updated_at'] ?? null,
                    'url' => $repo['html_url'] ?? null,
                ];
            }

            return [
                'success' => false,
                'message' => 'Gagal mengambil info repository'
            ];

        } catch (\Exception $e) {
            Log::error('GitHub getRepoInfo exception: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Verify GitHub token is valid
     */
    public function verifyToken()
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'Accept' => 'application/vnd.github.v3+json',
                'User-Agent' => 'SIAB-Laravel-App'
            ])->get("{$this->baseUrl}/user");

            return [
                'success' => $response->successful(),
                'message' => $response->successful() ? 'Token valid' : 'Token invalid'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get zipball URL from release
     */
    private function getZipballUrl($release)
    {
        // Check if there's a ZIP asset
        if (!empty($release['assets'])) {
            foreach ($release['assets'] as $asset) {
                if (str_ends_with($asset['name'], '.zip')) {
                    return $asset['browser_download_url'];
                }
            }
        }

        // Fallback to zipball_url
        return $release['zipball_url'] ?? null;
    }

    /**
     * Get configuration status
     */
    public function getConfigStatus()
    {
        return [
            'configured' => !empty($this->token) && !empty($this->owner) && !empty($this->repo),
            'token' => !empty($this->token) ? substr($this->token, 0, 10) . '...' : 'Not set',
            'owner' => $this->owner ?? 'Not set',
            'repo' => $this->repo ?? 'Not set',
        ];
    }
}
