<?php

namespace App\Support;

/**
 * Knows which PHP releases still have publicly known critical security
 * holes, so admins on a non-Docker server are told to update PHP.
 */
class PhpSecurity
{
    /**
     * First safe release of each supported branch. Latest entry: the
     * 19 Nov 2026 release (session and PHP-FPM RCE fixes, CVE-2026-115560,
     * CVE-2026-126714, CVE-2026-129289 and others). It also includes the
     * PHP2Shell fixes (CVE-2026-109618 / CVE-2026-109619) of 21 Oct 2026.
     * Branches older than 8.2 no longer get fixes at all.
     */
    public const MIN_SAFE = [
        '8.2' => '8.2.36',
        '8.3' => '8.3.37',
        '8.4' => '8.4.28',
        '8.5' => '8.5.13',
    ];

    public static function isPatched(string $version = PHP_VERSION): bool
    {
        $branch = implode('.', array_slice(explode('.', $version), 0, 2));

        if (isset(self::MIN_SAFE[$branch])) {
            return version_compare($version, self::MIN_SAFE[$branch], '>=');
        }

        // Newer branches than the list were released with the fixes
        return version_compare($branch, max(array_keys(self::MIN_SAFE)), '>');
    }

    public static function recommendedVersion(string $version = PHP_VERSION): string
    {
        $branch = implode('.', array_slice(explode('.', $version), 0, 2));

        return self::MIN_SAFE[$branch] ?? self::MIN_SAFE['8.4'];
    }
}
