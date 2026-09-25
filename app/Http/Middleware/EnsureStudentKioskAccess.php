<?php

namespace App\Http\Middleware;

use App\Services\StudentKiosk;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The student attendance page has no login, so it is only reachable from
 * registered school devices and only during the attendance hours.
 */
class EnsureStudentKioskAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if (StudentKiosk::requiresRegisteredDevice()) {
            $device = StudentKiosk::currentDevice($request);

            if (!$device) {
                return $this->deny(
                    $request,
                    'Perangkat tidak terdaftar',
                    'Absensi siswa hanya bisa dilakukan dari perangkat milik sekolah. Minta admin mendaftarkan perangkat ini melalui menu Data Siswa → Kiosk Absensi Siswa.'
                );
            }

            if (!$device->last_used_at || $device->last_used_at->lt(now()->subMinutes(5))) {
                $device->forceFill(['last_used_at' => now()])->save();
            }
        }

        $window = StudentKiosk::window();

        if (!$window['open']) {
            return $this->deny($request, 'Absensi siswa sedang ditutup', $window['reason']);
        }

        $request->attributes->set('kiosk_window', $window);

        return $next($request);
    }

    private function deny(Request $request, string $title, string $message): Response
    {
        if ($request->expectsJson() || $request->isMethod('post')) {
            return response()->json(['success' => false, 'message' => $message], 403);
        }

        return response()->view('student.attendance-closed', compact('title', 'message'), 403);
    }
}
