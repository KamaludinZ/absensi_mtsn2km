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

        $settings = [
            'require_selfie_photo' => $requireSelfie ? filter_var($requireSelfie->setting_value, FILTER_VALIDATE_BOOLEAN) : true,
            'require_check_in' => $requireCheckIn ? filter_var($requireCheckIn->setting_value, FILTER_VALIDATE_BOOLEAN) : true,
            'require_check_out' => $requireCheckOut ? filter_var($requireCheckOut->setting_value, FILTER_VALIDATE_BOOLEAN) : true,
            'time_difference_format' => $timeDiffFormat ? $timeDiffFormat->setting_value : 'time',
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

        return redirect()->route('admin.attendance-settings.index')->with('success', 'Pengaturan absensi berhasil diperbarui.');
    }
}
