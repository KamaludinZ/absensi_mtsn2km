<?php

namespace App\Services;

class AssetService
{
    protected $manifest = null;
    protected $manifestLoaded = false;

    /**
     * Get asset path from manifest.json
     *
     * @param string $path
     * @return string
     */
    public function getAsset($path)
    {
        // Load manifest once
        if (!$this->manifestLoaded) {
            $this->loadManifest();
        }

        // If no manifest or entry not found, return direct path
        if (empty($this->manifest) || !isset($this->manifest[$path])) {
            // Try to return build path directly
            $directPath = 'build/' . str_replace('resources/', '', $path);
            return asset($directPath);
        }

        // Return from manifest
        return asset('build/' . $this->manifest[$path]['file']);
    }

    /**
     * Load manifest.json
     */
    protected function loadManifest()
    {
        $this->manifestLoaded = true;

        $manifestPath = public_path('build/manifest.json');

        if (!file_exists($manifestPath)) {
            return;
        }

        try {
            $content = file_get_contents($manifestPath);
            $this->manifest = json_decode($content, true);
        } catch (\Exception $e) {
            // Silently fail and use fallback
            $this->manifest = null;
        }
    }

    /**
     * Get CSS asset URL
     */
    public function css($path = 'resources/css/app.css')
    {
        return $this->getAsset($path);
    }

    /**
     * Get JS asset URL
     */
    public function js($path = 'resources/js/app.js')
    {
        return $this->getAsset($path);
    }
}
