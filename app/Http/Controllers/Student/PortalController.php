<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Permission;
use App\Models\Student;
use App\Services\StudentAttendanceRecap;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * "Absensi Saya": a student who signed in through Super App sees today's
 * status and their own attendance per month. View only; attendance itself is
 * still taken at school (NISN page or RFID card).
 */
class PortalController extends Controller
{
    public function index(Request $request)
    {
        /** @var Student $student */
        $student = Auth::guard('student')->user();

        // Deactivated or graduated since signing in
        if (!$student->canSignIn()) {
            return $this->signOut($request, 'Data siswa Anda di aplikasi Absensi sedang nonaktif. Hubungi admin Absensi.');
        }

        $request->validate(['bulan' => ['nullable', 'date_format:Y-m']]);
        $month = $request->filled('bulan')
            ? Carbon::createFromFormat('Y-m-d', $request->bulan . '-01')->startOfDay()
            : Carbon::today()->startOfMonth();
        if ($month->gt(Carbon::today())) {
            $month = Carbon::today()->startOfMonth();
        }
        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth()->startOfDay();

        $student->loadMissing('rombel');
        $recap = StudentAttendanceRecap::build(collect([$student]), $start, $end);
        $row = $recap['rows']->first();
        $calendar = StudentAttendanceRecap::calendar($row, $start, $end, $recap['holidays']);

        $today = Attendance::where('attendable_type', Student::class)
            ->where('attendable_id', $student->id)
            ->whereDate('date', Carbon::today())
            ->first();
        $todayPermission = $today ? null : Permission::where('permissible_type', Student::class)
            ->where('permissible_id', $student->id)
            ->whereIn('status', ['approved', 'pending'])
            ->whereDate('start_date', '<=', Carbon::today())
            ->whereDate('end_date', '>=', Carbon::today())
            ->orderByRaw("case when status = 'approved' then 0 else 1 end")
            ->first();

        return view('student.portal', [
            'student' => $student,
            'month' => $month,
            'previousMonth' => $month->copy()->subMonth(),
            'nextMonth' => $month->copy()->addMonth()->lte(Carbon::today()) ? $month->copy()->addMonth() : null,
            'row' => $row,
            // Newest day first, without days that have not come yet
            'calendar' => $calendar->filter(fn ($day) => $day['date']->lte(Carbon::today()))->reverse()->values(),
            'today' => $today,
            'todayPermission' => $todayPermission,
        ]);
    }

    public function logout(Request $request)
    {
        return $this->signOut($request);
    }

    private function signOut(Request $request, ?string $error = null)
    {
        Auth::guard('student')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $redirect = redirect()->route('login');

        return $error ? $redirect->withErrors(['sso' => $error]) : $redirect;
    }
}
