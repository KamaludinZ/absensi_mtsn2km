<?php

namespace App\Services;

use App\Models\AttendanceSetting;

/**
 * School letterhead and signer shown on the PDF reports.
 */
class ReportHeader
{
    public static function settings(): array
    {
        $schoolName = AttendanceSetting::where('setting_key', 'school_name')->first();
        $schoolFullName = AttendanceSetting::where('setting_key', 'school_full_name')->first();
        $schoolAddress = AttendanceSetting::where('setting_key', 'school_address')->first();
        $schoolPhone = AttendanceSetting::where('setting_key', 'school_phone')->first();
        $schoolEmail = AttendanceSetting::where('setting_key', 'school_email')->first();
        $leftLogoPath = AttendanceSetting::where('setting_key', 'left_logo_path')->first();
        $rightLogoPath = AttendanceSetting::where('setting_key', 'right_logo_path')->first();
        $headmasterName = AttendanceSetting::where('setting_key', 'headmaster_name')->first();
        $headmasterNip = AttendanceSetting::where('setting_key', 'headmaster_nip')->first();
        $adminTataUsahaName = AttendanceSetting::where('setting_key', 'admin_tata_usaha_name')->first();
        $adminTataUsahaNip = AttendanceSetting::where('setting_key', 'admin_tata_usaha_nip')->first();
        $activeSigner = AttendanceSetting::where('setting_key', 'active_signer')->first();

        // Convert logos to base64 for PDF
        $leftLogoBase64 = '';
        $rightLogoBase64 = '';

        if ($leftLogoPath && $leftLogoPath->setting_value) {
            $fullPath = public_path($leftLogoPath->setting_value);
            if (file_exists($fullPath)) {
                $imageData = base64_encode(file_get_contents($fullPath));
                $imageType = pathinfo($fullPath, PATHINFO_EXTENSION);
                $leftLogoBase64 = 'data:image/' . $imageType . ';base64,' . $imageData;
            }
        }

        if ($rightLogoPath && $rightLogoPath->setting_value) {
            $fullPath = public_path($rightLogoPath->setting_value);
            if (file_exists($fullPath)) {
                $imageData = base64_encode(file_get_contents($fullPath));
                $imageType = pathinfo($fullPath, PATHINFO_EXTENSION);
                $rightLogoBase64 = 'data:image/' . $imageType . ';base64,' . $imageData;
            }
        }

        $activeSignerValue = $activeSigner ? $activeSigner->setting_value : 'headmaster';
        $headmasterNameVal = $headmasterName ? $headmasterName->setting_value : '';
        $headmasterNipVal = $headmasterNip ? $headmasterNip->setting_value : '';
        $tataUsahaNameVal = $adminTataUsahaName ? $adminTataUsahaName->setting_value : '';
        $tataUsahaNipVal = $adminTataUsahaNip ? $adminTataUsahaNip->setting_value : '';

        // Determine active signer display values
        if ($activeSignerValue === 'tata_usaha') {
            $activeSignerName = $tataUsahaNameVal ?: '................................';
            $activeSignerNip = $tataUsahaNipVal ?: '................................';
            $activeSignerTitle = 'Kepala Tata Usaha';
        } else {
            $activeSignerName = $headmasterNameVal ?: '................................';
            $activeSignerNip = $headmasterNipVal ?: '................................';
            $activeSignerTitle = 'Kepala Madrasah';
        }

        return [
            'school_name' => $schoolName ? $schoolName->setting_value : 'MTsN 2 KOTA MALANG',
            'school_full_name' => $schoolFullName ? $schoolFullName->setting_value : 'MADRASAH TSANAWIYAH NEGERI 2 KOTA MALANG',
            'school_address' => $schoolAddress ? $schoolAddress->setting_value : 'Jl. KH. Hasyim Asy\'ari No. 1, Kota Malang, Jawa Timur 65144',
            'school_phone' => $schoolPhone ? $schoolPhone->setting_value : '(0341) 123456',
            'school_email' => $schoolEmail ? $schoolEmail->setting_value : 'info@mtsn2kotamalang.sch.id',
            'left_logo' => $leftLogoBase64,
            'right_logo' => $rightLogoBase64,
            'headmaster_name' => $headmasterNameVal,
            'headmaster_nip' => $headmasterNipVal,
            'admin_tata_usaha_name' => $tataUsahaNameVal,
            'admin_tata_usaha_nip' => $tataUsahaNipVal,
            'active_signer' => $activeSignerValue,
            'active_signer_name' => $activeSignerName,
            'active_signer_nip' => $activeSignerNip,
            'active_signer_title' => $activeSignerTitle,
        ];
    }
}
