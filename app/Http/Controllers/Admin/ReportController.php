<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Student;
use App\Models\Rombel;
use App\Models\Setting;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\AttendanceExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $query = Attendance::with('attendable')
            ->where('attendable_type', Employee::class);

        // Filter by date range if provided
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        } elseif ($request->filled('start_date')) {
            $query->where('date', '>=', $request->start_date);
        } elseif ($request->filled('end_date')) {
            $query->where('date', '<=', $request->end_date);
        }

        // Filter by month and year if provided
        if ($request->filled('month') && $request->filled('year')) {
            $query->whereMonth('date', $request->month)
                  ->whereYear('date', $request->year);
        }

        // Filter by specific employee if provided
        if ($request->filled('employee_id')) {
            $query->where('attendable_id', $request->employee_id);
        }

        // Sorting
        $sortField = $request->get('sort', 'date');
        $sortDirection = $request->get('direction', 'desc');

        // Validate sort direction
        if (!in_array($sortDirection, ['asc', 'desc'])) {
            $sortDirection = 'desc';
        }

        // Apply sorting based on field
        switch ($sortField) {
            case 'full_name':
            case 'position':
                // For relationship fields, we need to join or sort in collection
                // Get all results first, then sort
                $attendances = $query->get()->sortBy(function($attendance) use ($sortField) {
                    return strtolower($attendance->attendable->$sortField ?? '');
                }, SORT_REGULAR, $sortDirection === 'desc');

                // Manually paginate
                $perPage = 20;
                $currentPage = request()->get('page', 1);
                $attendances = new \Illuminate\Pagination\LengthAwarePaginator(
                    $attendances->forPage($currentPage, $perPage),
                    $attendances->count(),
                    $perPage,
                    $currentPage,
                    ['path' => request()->url(), 'query' => request()->query()]
                );
                break;

            case 'clock_in':
            case 'clock_out':
                $query->orderBy($sortField, $sortDirection);
                $attendances = $query->orderBy('date', 'desc')->paginate(20);
                break;

            case 'date':
            default:
                $query->orderBy('date', $sortDirection);
                $attendances = $query->orderBy('created_at', 'desc')->paginate(20);
                break;
        }

        // Get all employees for the dropdown
        $employees = Employee::orderBy('full_name')->get();

        // Get available months/years for the dropdown
        $months = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember'
        ];

        $years = Attendance::where('attendable_type', Employee::class)
            ->selectRaw('EXTRACT(YEAR FROM date) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year');

        // Calculate summary statistics
        $summaryQuery = Attendance::where('attendable_type', Employee::class);
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $summaryQuery->whereBetween('date', [$request->start_date, $request->end_date]);
        } elseif ($request->filled('start_date')) {
            $summaryQuery->where('date', '>=', $request->start_date);
        } elseif ($request->filled('end_date')) {
            $summaryQuery->where('date', '<=', $request->end_date);
        }
        
        if ($request->filled('month') && $request->filled('year')) {
            $summaryQuery->whereMonth('date', $request->month)
                         ->whereYear('date', $request->year);
        }

        if ($request->filled('employee_id')) {
            $summaryQuery->where('attendable_id', $request->employee_id);
        }

        $totalAttendances = $summaryQuery->count();

        // Count present (on time) - both clock in and out on time
        $presentCount = (clone $summaryQuery)->where(function($q) {
            $q->where('status_in', 'Tepat Waktu')
              ->orWhere('status_out', 'Tepat Waktu');
        })->count();

        // Count absent - records where clock_in or clock_out is null (didn't attend)
        $absentCount = (clone $summaryQuery)->where(function($q) {
            $q->whereNull('clock_in')
              ->orWhereNull('clock_out');
        })->count();

        // Count late arrivals by checking status_in (should be 'Terlambat' as set by the controller)
        $lateCount = (clone $summaryQuery)->where('status_in', 'Terlambat')->count();

        // Count early departures by checking status_out (should be 'Pulang Awal' as set by the controller)
        $earlyDepartureCount = (clone $summaryQuery)->where('status_out', 'Pulang Awal')->count();

        // Get permission statistics for the same date range
        $permissionQuery = \App\Models\Permission::where('permissible_type', Employee::class);
        if ($request->filled('start_date') && $request->filled('end_date')) {
            // Filter by date range considering the permission date range
            $permissionQuery->where(function($q) use ($request) {
                $q->whereBetween('start_date', [$request->start_date, $request->end_date])
                  ->orWhereBetween('end_date', [$request->start_date, $request->end_date])
                  ->orWhere(function($subQ) use ($request) {
                      $subQ->where('start_date', '<=', $request->start_date)
                           ->where('end_date', '>=', $request->end_date);
                  });
            });
        } elseif ($request->filled('start_date')) {
            $permissionQuery->where('start_date', '>=', $request->start_date);
        } elseif ($request->filled('end_date')) {
            $permissionQuery->where('end_date', '<=', $request->end_date);
        }
        
        if ($request->filled('month') && $request->filled('year')) {
            // Filter by month and year
            $permissionQuery->where(function($q) use ($request) {
                $q->whereMonth('start_date', $request->month)
                  ->whereYear('start_date', $request->year)
                  ->orWhere(function($subQ) use ($request) {
                      $subQ->whereMonth('end_date', $request->month)
                           ->whereYear('end_date', $request->year);
                  })
                  ->orWhere(function($subQ) use ($request) {
                      $subQ->where('start_date', '<=', Carbon::create($request->year, $request->month, 1)->endOfMonth())
                           ->where('end_date', '>=', Carbon::create($request->year, $request->month, 1)->startOfMonth());
                  });
            });
        }

        if ($request->filled('employee_id')) {
            $permissionQuery->where('permissible_id', $request->employee_id);
        }

        $permissionCount = $permissionQuery->count();

        $summary = [
            'total_attendances' => $totalAttendances,
            'present' => $presentCount,
            'absent' => $absentCount,
            'late' => $lateCount,
            'early_departure' => $earlyDepartureCount,
            'permissions' => $permissionCount
        ];

        return view('admin.reports.employees', compact('attendances', 'employees', 'months', 'years', 'summary'));
    }
    
    public function students()
    {
        $rombels = Rombel::with('academicYear')->orderBy('name')->get();
        return view('admin.reports.index', compact('rombels'));
    }

    public function studentReports(Request $request)
    {
        $query = Attendance::with(['attendable.rombel', 'attendable'])
            ->where('attendable_type', Student::class)
            ->orderBy('date', 'desc')
            ->orderBy('clock_in', 'asc');

        // Apply filters
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        }

        if ($request->filled('rombel_id')) {
            $studentIds = Student::where('rombel_id', $request->rombel_id)->pluck('id');
            $query->whereIn('attendable_id', $studentIds);
        }

        if ($request->filled('student_name')) {
            $studentIds = Student::where('full_name', 'ILIKE', '%' . $request->student_name . '%')->pluck('id');
            $query->whereIn('attendable_id', $studentIds);
        }

        $attendances = $query->paginate(20);
        $rombels = Rombel::orderBy('name')->get();

        return view('admin.reports.student-reports', compact('attendances', 'rombels'));
    }

    public function generate(Request $request)
    {
        $request->validate([
            'type' => 'required|in:employee,student',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'format' => 'required|in:excel,pdf',
        ]);

        $query = Attendance::with('attendable')
            ->whereBetween('date', [$request->start_date, $request->end_date]);

        if ($request->type === 'employee') {
            $query->where('attendable_type', Employee::class);

            // Filter by specific employee if provided
            if ($request->filled('employee_id')) {
                $query->where('attendable_id', $request->employee_id);
            }
        } else {
            $query->where('attendable_type', Student::class);

            // Filter by rombel if provided
            if ($request->filled('rombel_id')) {
                $studentIds = Student::where('rombel_id', $request->rombel_id)->pluck('id');
                $query->whereIn('attendable_id', $studentIds);
            }
        }

        // Get all attendances first
        $attendances = $query->get();

        // Sort by employee/student name first, then by date
        $attendances = $attendances->sort(function($a, $b) {
            // First sort by name (ascending)
            $nameComparison = strcmp(
                strtolower($a->attendable->full_name ?? ''),
                strtolower($b->attendable->full_name ?? '')
            );

            // If names are the same, sort by date (ascending for chronological order)
            if ($nameComparison === 0) {
                return $a->date <=> $b->date;
            }

            return $nameComparison;
        })->values(); // Reset array keys

        if ($request->format === 'excel') {
            return $this->exportExcel($attendances, $request->type, $request->start_date, $request->end_date);
        } else {
            return $this->exportPdf($attendances, $request->type, $request->start_date, $request->end_date);
        }
    }

    private function exportExcel($attendances, $type, $startDate, $endDate)
    {
        $filename = 'laporan_absensi_' . $type . '_' . date('YmdHis') . '.xlsx';

        return Excel::download(
            new AttendanceExport($attendances, $type, $startDate, $endDate),
            $filename
        );
    }

    private function exportPdf($attendances, $type, $startDate, $endDate)
    {
        $schoolName = Setting::get('school_name', 'MTsN 2 Kota Malang');
        $schoolAddress = Setting::get('school_address', 'Kota Malang');
        $reportHeaderPath = Setting::get('report_header_path');

        $data = [
            'attendances' => $attendances,
            'type' => $type,
            'startDate' => Carbon::parse($startDate)->format('d/m/Y'),
            'endDate' => Carbon::parse($endDate)->format('d/m/Y'),
            'schoolName' => $schoolName,
            'schoolAddress' => $schoolAddress,
            'reportHeaderPath' => $reportHeaderPath,
            'generatedAt' => Carbon::now()->format('d/m/Y H:i'),
        ];

        $pdf = Pdf::loadView('admin.reports.pdf', $data);

        $filename = 'laporan_absensi_' . $type . '_' . date('YmdHis') . '.pdf';

        return $pdf->download($filename);
    }
}
