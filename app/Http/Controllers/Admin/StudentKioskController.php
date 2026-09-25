<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceDevice;
use App\Models\AttendanceSetting;
use App\Services\StudentKiosk;
use Illuminate\Http\Request;

class StudentKioskController extends Controller
{
    public function index(Request $request)
    {
        $devices = AttendanceDevice::with('creator')->orderByDesc('created_at')->get();
        $currentDevice = StudentKiosk::currentDevice($request);
        $window = StudentKiosk::window();

        $settings = [
            'require_registered_device' => StudentKiosk::requiresRegisteredDevice(),
            'open_minutes_before' => StudentKiosk::openMinutesBefore(),
            'close_minutes_after' => StudentKiosk::closeMinutesAfter(),
        ];

        return view('admin.student-kiosk.index', compact('devices', 'currentDevice', 'window', 'settings'));
    }

    public function updateSettings(Request $request)
    {
        $request->validate([
            'require_registered_device' => 'required|boolean',
            'open_minutes_before' => 'required|integer|min:0|max:240',
            'close_minutes_after' => 'required|integer|min:0|max:240',
        ]);

        AttendanceSetting::updateOrCreate(
            ['setting_key' => 'student_require_registered_device'],
            [
                'setting_value' => $request->boolean('require_registered_device') ? 'true' : 'false',
                'description' => 'Absensi siswa hanya dari perangkat sekolah yang terdaftar',
            ]
        );

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
            ->with('success', 'Pengaturan kiosk absensi siswa berhasil disimpan.');
    }

    /**
     * Register the browser the admin is using right now as a school device.
     */
    public function registerDevice(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
        ]);

        [$device, $token] = StudentKiosk::registerDevice($request->name, $request->user()->id);

        // Long-lived, encrypted, HTTP-only cookie that identifies this device.
        $cookie = cookie()->forever(StudentKiosk::COOKIE, $token);

        return redirect()->route('admin.student-kiosk.index')
            ->withCookie($cookie)
            ->with('success', 'Perangkat "' . $device->name . '" berhasil didaftarkan. Halaman absensi siswa sekarang bisa dibuka dari browser ini.');
    }

    public function toggleDevice(AttendanceDevice $device)
    {
        $device->update(['is_active' => !$device->is_active]);

        return redirect()->route('admin.student-kiosk.index')
            ->with('success', 'Perangkat "' . $device->name . '" ' . ($device->is_active ? 'diaktifkan kembali.' : 'dinonaktifkan.'));
    }

    public function destroyDevice(AttendanceDevice $device)
    {
        $name = $device->name;
        $device->delete();

        return redirect()->route('admin.student-kiosk.index')
            ->with('success', 'Perangkat "' . $name . '" dihapus.');
    }
}
