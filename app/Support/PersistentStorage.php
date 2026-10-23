<?php

namespace App\Support;

/**
 * In Docker, the folders holding uploaded photos, documents, logos and
 * backups must be volumes (Coolify "Persistent Storage"); otherwise they are
 * wiped on every redeploy. This finds the ones that are not.
 */
class PersistentStorage
{
    /** @return array<string, string> path => what is stored there */
    public static function folders(): array
    {
        return [
            base_path('storage') => 'backup, log, kunci aplikasi',
            public_path('storage') => 'foto selfie, dokumen izin & koreksi',
            public_path('uploads') => 'logo kop laporan',
        ];
    }

    /**
     * Folders that are not a mounted volume. Empty outside Docker, or when
     * the mount table cannot be read.
     *
     * @return array<string, string>
     */
    public static function notPersisted(?string $mountInfo = null): array
    {
        if ($mountInfo === null) {
            if (!config('app.in_docker') || !is_readable('/proc/self/mountinfo')) {
                return [];
            }
            $mountInfo = (string) @file_get_contents('/proc/self/mountinfo');
        }

        $mountPoints = [];
        foreach (preg_split('/\R/', trim($mountInfo)) as $line) {
            $fields = explode(' ', $line);
            if (isset($fields[4])) {
                // Spaces etc. are written as octal escapes, e.g. \040
                $mountPoints[] = rtrim(preg_replace_callback('/\\\\([0-7]{3})/', fn ($m) => chr(octdec($m[1])), $fields[4]), '/');
            }
        }

        if ($mountPoints === []) {
            return [];
        }

        return array_filter(
            self::folders(),
            fn ($label, $path) => !in_array(rtrim($path, '/'), $mountPoints, true),
            ARRAY_FILTER_USE_BOTH
        );
    }
}
