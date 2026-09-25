<?php

namespace App\Http\Middleware;

use App\Services\StudentKiosk;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The student attendance page has no login, so it is only open during the
 * attendance hours (see StudentKiosk::window()).
 */
class EnsureStudentKioskAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $window = StudentKiosk::window();

        if (!$window['open']) {
            $title = 'Absensi siswa sedang ditutup';
            $message = $window['reason'];

            if ($request->expectsJson() || $request->isMethod('post')) {
                return response()->json(['success' => false, 'message' => $message], 403);
            }

            return response()->view('student.attendance-closed', compact('title', 'message'), 403);
        }

        $request->attributes->set('kiosk_window', $window);

        return $next($request);
    }
}
