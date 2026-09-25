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

if (!function_exists('sortableLink')) {
    /**
     * Column header link that toggles sorting and keeps the current filters.
     * Used by the list views (was previously declared inside each view,
     * which fails with "Cannot redeclare" when a view renders twice).
     */
    function sortableLink($label, $sortField, $currentSort, $currentDirection)
    {
        $newDirection = 'asc';
        $icon = '';

        if ($currentSort == $sortField) {
            $newDirection = $currentDirection == 'asc' ? 'desc' : 'asc';
            $icon = $currentDirection == 'asc' ? ' ↑' : ' ↓';
        }

        // Build query string preserving filters
        $params = request()->except(['sort', 'direction', 'page']);
        $params['sort'] = $sortField;
        $params['direction'] = $newDirection;

        $url = request()->url() . '?' . http_build_query($params);

        $class = $currentSort == $sortField ? 'text-blue-600 font-semibold' : 'text-gray-700 hover:text-blue-600';

        return '<a href="' . e($url) . '" class="' . $class . '">' . e($label) . $icon . '</a>';
    }
}
