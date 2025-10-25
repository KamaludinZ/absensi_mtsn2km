<?php

/**
 * Get Vite asset from manifest.json for production
 * Falls back to Vite dev server in local environment
 *
 * @param string $asset
 * @return string
 */
if (!function_exists('vite_asset')) {
    function vite_asset($asset)
    {
        // In local environment, use Vite directive
        if (app()->environment('local')) {
            return null; // Will be handled by @vite directive
        }

        // In production, read from manifest.json
        $manifestPath = public_path('build/manifest.json');

        if (!file_exists($manifestPath)) {
            // Fallback: try to construct the path
            return asset('build/' . $asset);
        }

        $manifest = json_decode(file_get_contents($manifestPath), true);

        if (!isset($manifest[$asset])) {
            return asset('build/' . $asset);
        }

        return asset('build/' . $manifest[$asset]['file']);
    }
}

/**
 * Get all Vite assets (CSS and JS) from manifest
 *
 * @param string $entry Entry point (e.g., 'resources/css/app.css')
 * @return array
 */
if (!function_exists('vite_assets')) {
    function vite_assets($entry)
    {
        $manifestPath = public_path('build/manifest.json');

        if (!file_exists($manifestPath)) {
            return [];
        }

        $manifest = json_decode(file_get_contents($manifestPath), true);

        if (!isset($manifest[$entry])) {
            return [];
        }

        $assets = [
            'css' => [],
            'js' => [],
        ];

        // Get CSS files
        if (isset($manifest[$entry]['css'])) {
            foreach ($manifest[$entry]['css'] as $css) {
                $assets['css'][] = asset('build/' . $css);
            }
        }

        // Get main file
        if (isset($manifest[$entry]['file'])) {
            $assets['js'][] = asset('build/' . $manifest[$entry]['file']);
        }

        // Get imported chunks
        if (isset($manifest[$entry]['imports'])) {
            foreach ($manifest[$entry]['imports'] as $import) {
                if (isset($manifest[$import]['file'])) {
                    $assets['js'][] = asset('build/' . $manifest[$import]['file']);
                }
            }
        }

        return $assets;
    }
}
