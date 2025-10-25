<?php

if (!function_exists('getAppVersion')) {
    /**
     * Get the current application version from VERSION file
     * 
     * @return string
     */
    function getAppVersion()
    {
        $versionFile = base_path('VERSION');
        
        if (file_exists($versionFile)) {
            return trim(file_get_contents($versionFile));
        }
        
        // Fallback version
        return '1.0.0';
    }
}

if (!function_exists('formatAppVersion')) {
    /**
     * Format the application version for display
     * 
     * @param string|null $version
     * @return string
     */
    function formatAppVersion($version = null)
    {
        if ($version === null) {
            $version = getAppVersion();
        }
        
        return 'v' . $version;
    }
}