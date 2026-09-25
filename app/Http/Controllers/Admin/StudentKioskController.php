<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceSetting;
use App\Services\StudentKiosk;
use Illuminate\Http\Request;

/**
 * Opening hours of the public student attendance page.
 */
class StudentKioskController extends Controller
{
    public function index()
    {
        $window = StudentKiosk::window();

        $settings = [
            'open_minutes_before' => StudentKiosk::openMinutesBefore(),
            'close_minutes_after' => StudentKiosk::closeMinutesAfter(),
        ];

        return view('admin.student-kiosk.index', compact('window', 'settings'));
    }

    public function updateSettings(Request $request)
    {
        $request->validate([
            'open_minutes_before' => 'required|integer|min:0|max:240',
            'close_minutes_after' => 'required|integer|min:0|max:240',
        ]);

        AttendanceSetting::updateOrCreate(
            ['setting_key' => 'student_open_minutes_before'],
            [
                'setting_value' => (string) $request->integer('open_minutes_before'),
                'description' => 'Halaman absensi siswa dibuka N menit sebelum jam masuk',
            ]
        );

        AttendanceSetting::updateOrCreate(
            ['setting_key' => 'student_close_minutes_after'],
            [
                'setting_value' => (string) $request->integer('close_minutes_after'),
                'description' => 'Halaman absensi siswa ditutup N menit setelah jam pulang',
            ]
        );

        return redirect()->route('admin.student-kiosk.index')
            ->with('success', 'Jam absensi siswa berhasil disimpan.');
    }
}
