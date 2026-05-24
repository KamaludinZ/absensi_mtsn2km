<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AttendanceSetting;

class AttendanceSettingController extends Controller
{
    public function index()
    {
        $requireSelfie = AttendanceSetting::where('setting_key', 'require_selfie_photo')->first();
        $requireCheckIn = AttendanceSetting::where('setting_key', 'require_check_in')->first();
        $requireCheckOut = AttendanceSetting::where('setting_key', 'require_check_out')->first();
        $timeDiffFormat = AttendanceSetting::where('setting_key', 'time_difference_format')->first();

        // Header settings for PDF exports
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

        $settings = [
            'require_selfie_photo' => $requireSelfie ? filter_var($requireSelfie->setting_value, FILTER_VALIDATE_BOOLEAN) : true,
            'require_check_in' => $requireCheckIn ? filter_var($requireCheckIn->setting_value, FILTER_VALIDATE_BOOLEAN) : true,
            'require_check_out' => $requireCheckOut ? filter_var($requireCheckOut->setting_value, FILTER_VALIDATE_BOOLEAN) : true,
            'time_difference_format' => $timeDiffFormat ? $timeDiffFormat->setting_value : 'time',
            'school_name' => $schoolName ? $schoolName->setting_value : 'MTsN 2 KOTA MALANG',
            'school_full_name' => $schoolFullName ? $schoolFullName->setting_value : 'MADRASAH TSANAWIYAH NEGERI 2 KOTA MALANG',
            'school_address' => $schoolAddress ? $schoolAddress->setting_value : 'Jl. KH. Hasyim Asy\'ari No. 1, Kota Malang, Jawa Timur 65144',
            'school_phone' => $schoolPhone ? $schoolPhone->setting_value : '(0341) 123456',
            'school_email' => $schoolEmail ? $schoolEmail->setting_value : 'info@mtsn2kotamalang.sch.id',
            'left_logo_path' => $leftLogoPath ? $leftLogoPath->setting_value : '',
            'right_logo_path' => $rightLogoPath ? $rightLogoPath->setting_value : '',
            'headmaster_name' => $headmasterName ? $headmasterName->setting_value : '',
            'headmaster_nip' => $headmasterNip ? $headmasterNip->setting_value : '',
            'admin_tata_usaha_name' => $adminTataUsahaName ? $adminTataUsahaName->setting_value : '',
            'admin_tata_usaha_nip' => $adminTataUsahaNip ? $adminTataUsahaNip->setting_value : '',
            'active_signer' => $activeSigner ? $activeSigner->setting_value : 'headmaster',
        ];

        return view('admin.attendance-settings.index', compact('settings'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'require_selfie_photo' => 'required|boolean',
            'require_check_in' => 'required|boolean',
            'require_check_out' => 'required|boolean',
            'time_difference_format' => 'required|in:time,minutes',
            'school_name' => 'required|string|max:255',
            'school_full_name' => 'required|string|max:255',
            'school_address' => 'required|string|max:500',
            'school_phone' => 'nullable|string|max:50',
            'school_email' => 'nullable|email|max:100',
            'left_logo' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
            'right_logo' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
            'headmaster_name' => 'nullable|string|max:255',
            'headmaster_nip' => 'nullable|string|max:50',
            'admin_tata_usaha_name' => 'nullable|string|max:255',
            'admin_tata_usaha_nip' => 'nullable|string|max:50',
            'active_signer' => 'required|in:headmaster,tata_usaha',
        ]);

        // Update or create attendance settings
        AttendanceSetting::updateOrCreate(
            ['setting_key' => 'require_selfie_photo'],
            [
                'setting_value' => $request->require_selfie_photo ? 'true' : 'false',
                'description' => 'Wajibkan foto selfie saat absensi'
            ]
        );

        AttendanceSetting::updateOrCreate(
            ['setting_key' => 'require_check_in'],
            [
                'setting_value' => $request->require_check_in ? 'true' : 'false',
                'description' => 'Wajibkan absensi datang'
            ]
        );

        AttendanceSetting::updateOrCreate(
            ['setting_key' => 'require_check_out'],
            [
                'setting_value' => $request->require_check_out ? 'true' : 'false',
                'description' => 'Wajibkan absensi pulang'
            ]
        );

        AttendanceSetting::updateOrCreate(
            ['setting_key' => 'time_difference_format'],
            [
                'setting_value' => $request->time_difference_format,
                'description' => 'Format perhitungan selisih waktu: time (HH:mm:ss) atau minutes (total menit)'
            ]
        );

        // Update header settings for PDF exports
        AttendanceSetting::updateOrCreate(
            ['setting_key' => 'school_name'],
            [
                'setting_value' => $request->school_name,
                'description' => 'Nama sekolah (untuk header PDF)'
            ]
        );

        AttendanceSetting::updateOrCreate(
            ['setting_key' => 'school_full_name'],
            [
                'setting_value' => $request->school_full_name,
                'description' => 'Nama lengkap sekolah (untuk header PDF)'
            ]
        );

        AttendanceSetting::updateOrCreate(
            ['setting_key' => 'school_address'],
            [
                'setting_value' => $request->school_address,
                'description' => 'Alamat sekolah (untuk header PDF)'
            ]
        );

        AttendanceSetting::updateOrCreate(
            ['setting_key' => 'school_phone'],
            [
                'setting_value' => $request->school_phone,
                'description' => 'Nomor telepon sekolah (untuk header PDF)'
            ]
        );

        AttendanceSetting::updateOrCreate(
            ['setting_key' => 'school_email'],
            [
                'setting_value' => $request->school_email,
                'description' => 'Email sekolah (untuk header PDF)'
            ]
        );

        // Handle left logo upload
        if ($request->hasFile('left_logo')) {
            $leftLogo = $request->file('left_logo');
            $leftLogoName = 'left_logo_' . time() . '.' . $leftLogo->getClientOriginalExtension();
            $leftLogo->move(public_path('uploads/logos'), $leftLogoName);

            AttendanceSetting::updateOrCreate(
                ['setting_key' => 'left_logo_path'],
                [
                    'setting_value' => 'uploads/logos/' . $leftLogoName,
                    'description' => 'Path logo kiri (untuk header PDF)'
                ]
            );
        }

        // Handle right logo upload
        if ($request->hasFile('right_logo')) {
            $rightLogo = $request->file('right_logo');
            $rightLogoName = 'right_logo_' . time() . '.' . $rightLogo->getClientOriginalExtension();
            $rightLogo->move(public_path('uploads/logos'), $rightLogoName);

            AttendanceSetting::updateOrCreate(
                ['setting_key' => 'right_logo_path'],
                [
                    'setting_value' => 'uploads/logos/' . $rightLogoName,
                    'description' => 'Path logo kanan (untuk header PDF)'
                ]
            );
        }

        // Update headmaster info
        AttendanceSetting::updateOrCreate(
            ['setting_key' => 'headmaster_name'],
            [
                'setting_value' => $request->headmaster_name,
                'description' => 'Nama kepala madrasah (untuk tanda tangan PDF)'
            ]
        );

        AttendanceSetting::updateOrCreate(
            ['setting_key' => 'headmaster_nip'],
            [
                'setting_value' => $request->headmaster_nip,
                'description' => 'NIP kepala madrasah (untuk tanda tangan PDF)'
            ]
        );

        // Update admin tata usaha info
        AttendanceSetting::updateOrCreate(
            ['setting_key' => 'admin_tata_usaha_name'],
            [
                'setting_value' => $request->admin_tata_usaha_name,
                'description' => 'Nama kepala tata usaha (untuk tanda tangan PDF)'
            ]
        );

        AttendanceSetting::updateOrCreate(
            ['setting_key' => 'admin_tata_usaha_nip'],
            [
                'setting_value' => $request->admin_tata_usaha_nip,
                'description' => 'NIP kepala tata usaha (untuk tanda tangan PDF)'
            ]
        );

        // Update active signer
        AttendanceSetting::updateOrCreate(
            ['setting_key' => 'active_signer'],
            [
                'setting_value' => $request->active_signer,
                'description' => 'Pimpinan aktif yang menandatangani laporan: headmaster atau tata_usaha'
            ]
        );

        return redirect()->route('admin.attendance-settings.index')->with('success', 'Pengaturan absensi berhasil diperbarui.');
    }
}
