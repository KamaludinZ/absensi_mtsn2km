<?php

namespace App\Http\Controllers\Admin;

use App\Exports\StudentRecapExport;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Grade;
use App\Models\Permission;
use App\Models\Rombel;
use App\Models\Student;
use App\Services\ReportHeader;
use App\Services\StudentAttendanceRecap;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Student attendance recap (Hadir / Terlambat / Sakit / Izin / Alpa) per
 * class level, rombel or single student, with Excel and PDF export.
 */
class StudentReportController extends Controller
{
    public function index(Request $request)
    {
        $report = $this->report($request);

        $grades = Grade::orderBy('name')->get();
        $rombels = Rombel::orderBy('name')->get(['id', 'name', 'grade_id']);
        $students = Student::where('is_active', true)->orderBy('full_name')->get(['id', 'full_name', 'rombel_id']);

        return view('admin.reports.students.index', $report + compact('grades', 'rombels', 'students'));
    }

    public function export(Request $request)
    {
        $request->validate(['format' => 'required|in:excel,pdf']);

        $report = $this->report($request);
        $report['headerSettings'] = ReportHeader::settings();
        $report['generatedAt'] = now()->format('d/m/Y H:i');

        $fileName = 'rekap_absensi_siswa_' . str($report['scopeLabel'])->slug('_') . '_'
            . $report['start']->format('Ymd') . '-' . $report['end']->format('Ymd');

        if ($request->format === 'excel') {
            return Excel::download(new StudentRecapExport($report), $fileName . '.xlsx');
        }

        $orientation = $report['studentRow'] ? 'portrait' : 'landscape';

        return Pdf::loadView('admin.reports.students.pdf', $report)
            ->setPaper('a4', $orientation)
            ->download($fileName . '.pdf');
    }

    /**
     * Everything the page and the exports show for the requested filters.
     */
    private function report(Request $request): array
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'month' => 'nullable|integer|between:1,12',
            'year' => 'nullable|integer|between:2000,2100',
            'grade_id' => 'nullable|integer|exists:grades,id',
            'rombel_id' => 'nullable|integer|exists:rombels,id',
            'student_id' => 'nullable|integer|exists:students,id',
        ]);

        [$start, $end] = $this->period($request);

        $grade = $request->filled('grade_id') ? Grade::find($request->grade_id) : null;
        $rombel = $request->filled('rombel_id') ? Rombel::with('grade')->find($request->rombel_id) : null;
        $student = $request->filled('student_id') ? Student::withTrashed()->with('rombel.grade')->find($request->student_id) : null;

        // Only records made while the student was in the selected class count
        $rombelIds = null;
        if ($rombel) {
            $rombelIds = [$rombel->id];
        } elseif ($grade) {
            $rombelIds = Rombel::where('grade_id', $grade->id)->pluck('id')->map(fn ($id) => (int) $id)->all();
        }

        $students = $this->students($student, $rombelIds, $start, $end);
        $recap = StudentAttendanceRecap::build($students, $start, $end, $student ? null : $rombelIds);

        $studentRow = $student ? $recap['rows']->first() : null;
        $calendar = $studentRow ? StudentAttendanceRecap::calendar($studentRow, $start, $end, $recap['holidays']) : null;

        $totals = array_fill_keys(StudentAttendanceRecap::COUNTED, 0);
        foreach ($recap['rows'] as $row) {
            foreach ($row['counts'] as $code => $count) {
                $totals[$code] += $count;
            }
        }

        // Permissions still waiting for approval are not counted as Sakit/Izin yet
        $pendingPermissions = Permission::where('permissible_type', Student::class)
            ->whereIn('permissible_id', $students->pluck('id'))
            ->where('status', 'pending')
            ->whereDate('start_date', '<=', $end->toDateString())
            ->whereDate('end_date', '>=', $start->toDateString())
            ->count();

        if ($student) {
            $scopeLabel = $student->full_name;
        } elseif ($rombel) {
            $scopeLabel = 'Rombel ' . $rombel->name;
        } elseif ($grade) {
            $scopeLabel = 'Kelas ' . $grade->name;
        } else {
            $scopeLabel = 'Semua Kelas';
        }

        return [
            'start' => $start,
            'end' => $end,
            'grade' => $grade,
            'rombel' => $rombel,
            'student' => $student,
            'scopeLabel' => $scopeLabel,
            'rows' => $recap['rows'],
            'schoolDays' => $recap['school_days'],
            'holidays' => $recap['holidays'],
            'totals' => $totals,
            'studentRow' => $studentRow,
            'calendar' => $calendar,
            'pendingPermissions' => $pendingPermissions,
            'labels' => StudentAttendanceRecap::LABELS,
        ];
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function period(Request $request): array
    {
        if ($request->filled('start_date') || $request->filled('end_date')) {
            $start = Carbon::parse($request->input('start_date', $request->end_date))->startOfDay();
            $end = Carbon::parse($request->input('end_date', $request->start_date))->startOfDay();
        } elseif ($request->filled('month') && $request->filled('year')) {
            $start = Carbon::create((int) $request->year, (int) $request->month, 1)->startOfDay();
            $end = $start->copy()->endOfMonth()->startOfDay();
        } else {
            $start = Carbon::today()->startOfMonth();
            $end = Carbon::today()->endOfMonth()->startOfDay();
        }

        // Keep the recap to at most one year
        if ($start->diffInDays($end) > 366) {
            $end = $start->copy()->addYear()->subDay();
        }

        return [$start, $end];
    }

    /**
     * Students in the recap: the active students of the selected classes, plus
     * students who left those classes but have records there in the period.
     */
    private function students(?Student $student, ?array $rombelIds, Carbon $start, Carbon $end)
    {
        if ($student) {
            return collect([$student]);
        }

        $query = Student::with('rombel.grade');

        if ($rombelIds === null) {
            $query->where('is_active', true);
        } else {
            $between = [$start->toDateString(), $end->toDateString()];
            $withRecords = Attendance::where('attendable_type', Student::class)
                ->whereIn('rombel_id', $rombelIds)
                ->whereBetween('date', $between)
                ->pluck('attendable_id')
                ->merge(Permission::where('permissible_type', Student::class)
                    ->whereIn('rombel_id', $rombelIds)
                    ->whereDate('start_date', '<=', $between[1])
                    ->whereDate('end_date', '>=', $between[0])
                    ->pluck('permissible_id'))
                ->unique();

            $query->where(function ($q) use ($rombelIds, $withRecords) {
                $q->where(fn ($q) => $q->where('is_active', true)->whereIn('rombel_id', $rombelIds))
                    ->orWhereIn('id', $withRecords);
            });
        }

        return $query->get()
            ->sortBy(fn ($s) => [$s->rombel->name ?? '', strtolower($s->full_name)])
            ->values();
    }
}
