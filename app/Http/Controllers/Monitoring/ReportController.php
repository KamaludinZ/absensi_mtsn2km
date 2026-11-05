<?php

namespace App\Http\Controllers\Monitoring;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\Attendance;
use App\Models\Schedule;
use App\Models\AttendanceSetting;
use App\Services\EmployeeAllowanceCalculator;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function salaryReport(Request $request)
    {
        $selectedMonth = $request->input('month', Carbon::now()->month);
        $selectedYear = $request->input('year', Carbon::now()->year);

        $employeeQuery = Employee::with(['salary' => function($query) use ($selectedMonth, $selectedYear) {
            $query->where('month', $selectedMonth)
                  ->where('year', $selectedYear)
                  ->latest('created_at');
        }])->where('is_active', true);

        // Filter by specific employee if provided
        if ($request->filled('employee_id')) {
            $employeeQuery->where('id', $request->employee_id);
        }

        $employees = $employeeQuery->orderBy('full_name')->get();
        $salaryData = EmployeeAllowanceCalculator::rows($employees, (int) $selectedYear, (int) $selectedMonth);

        // Sort the final data array based on the sort parameters
        $sortField = $request->get('sort', 'full_name');
        $sortDirection = $request->get('direction', 'asc');

        $salaryData = collect($salaryData)->sortBy(function($data) use ($sortField) {
            switch ($sortField) {
                case 'position':
                    return strtolower($data['employee']->position ?? '');
                case 'total_salary':
                    return $data['total_salary'];
                case 'total_meal_allowance':
                    return $data['total_meal_allowance'];
                case 'total_transport_allowance':
                    return $data['total_transport_allowance'];
                case 'total_performance_allowance':
                    return $data['total_performance_allowance'];
                case 'base_salary':
                    return $data['base_salary'];
                case 'present_days':
                    return $data['present_days'];
                case 'perfect_days':
                    return $data['perfect_days'];
                case 'full_name':
                default:
                    return strtolower($data['employee']->full_name ?? '');
            }
        }, SORT_REGULAR, $sortDirection === 'desc');

        $salaryData = $salaryData->values()->all();

        // Data for filters - only include active employees
        $allEmployees = Employee::where('is_active', true)->orderBy('full_name')->get();
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        // Get unique years from attendance records
        $yearsFromAttendance = Attendance::where('attendable_type', Employee::class)
            ->selectRaw('EXTRACT(YEAR FROM date) as year')
            ->distinct()
            ->pluck('year')
            ->map(fn($year) => (int)$year);

        // Determine min and max year for the dropdown
        $currentYear = (int)Carbon::now()->year;
        $minYear = $yearsFromAttendance->min();

        if ($minYear === null || $minYear > $currentYear) {
            // If no attendance years or all attendance years are in the future
            $minYear = $currentYear - 2; // Show current year and 2 past years
        } else {
            $minYear = min($minYear, $currentYear - 2); // Show at least 2 past years
        }

        // Generate the final list of years for the dropdown
        $years = collect(range($currentYear, $minYear))->sortDesc()->values();
        // Add one future year to ensure the current period is always selectable.
        $years->prepend($currentYear + 1);
        $years = $years->unique()->sortDesc()->values();

        if ($years->isEmpty()) {
            $years = collect([$currentYear]);
        }

        return view('monitoring.reports.salary', [
            'salaryData' => $salaryData,
            'employees' => $allEmployees,
            'months' => $months,
            'years' => $years,
            'selectedMonth' => $selectedMonth,
            'selectedYear' => $selectedYear,
        ]);
    }

    public function mealAllowance(Request $request)
    {
        // Redirect to admin meal allowance report with same parameters
        return app(\App\Http\Controllers\Admin\ReportController::class)->mealAllowance($request);
    }

    public function transportAllowance(Request $request)
    {
        // Redirect to admin transport allowance report with same parameters
        return app(\App\Http\Controllers\Admin\ReportController::class)->transportAllowance($request);
    }

    public function performanceAllowance(Request $request)
    {
        // Redirect to admin performance allowance report with same parameters
        return app(\App\Http\Controllers\Admin\ReportController::class)->performanceAllowance($request);
    }

    public function downloadMealAllowancePdf(Request $request)
    {
        // Delegate to admin controller for PDF download
        return app(\App\Http\Controllers\Admin\ReportController::class)->downloadMealAllowancePdf($request);
    }

    public function downloadTransportAllowancePdf(Request $request)
    {
        // Delegate to admin controller for PDF download
        return app(\App\Http\Controllers\Admin\ReportController::class)->downloadTransportAllowancePdf($request);
    }

    public function downloadPerformanceAllowancePdf(Request $request)
    {
        // Delegate to admin controller for PDF download
        return app(\App\Http\Controllers\Admin\ReportController::class)->downloadPerformanceAllowancePdf($request);
    }

    public function downloadSalaryPdf(Request $request)
    {
        // Delegate to admin controller for PDF download
        return app(\App\Http\Controllers\Admin\ReportController::class)->downloadSalaryPdf($request);
    }

    public function mealAllowanceDetail(Request $request, Employee $employee, $year, $month)
    {
        // Delegate to admin controller for detail view
        return app(\App\Http\Controllers\Admin\ReportController::class)->mealAllowanceDetail($request, $employee, $year, $month);
    }

    public function generateMealAllowancePdf(Request $request, Employee $employee, $year, $month)
    {
        // Delegate to admin controller for PDF generation
        return app(\App\Http\Controllers\Admin\ReportController::class)->generateMealAllowancePdf($request, $employee, $year, $month);
    }

    public function transportAllowanceDetail(Request $request, Employee $employee, $year, $month)
    {
        // Delegate to admin controller for detail view
        return app(\App\Http\Controllers\Admin\ReportController::class)->transportAllowanceDetail($request, $employee, $year, $month);
    }

    public function generateTransportAllowancePdf(Request $request, Employee $employee, $year, $month)
    {
        // Delegate to admin controller for PDF generation
        return app(\App\Http\Controllers\Admin\ReportController::class)->generateTransportAllowancePdf($request, $employee, $year, $month);
    }

    public function performanceAllowanceDetail(Request $request, Employee $employee, $year, $month)
    {
        // Delegate to admin controller for detail view
        return app(\App\Http\Controllers\Admin\ReportController::class)->performanceAllowanceDetail($request, $employee, $year, $month);
    }

    public function generatePerformanceAllowancePdf(Request $request, Employee $employee, $year, $month)
    {
        // Delegate to admin controller for PDF generation
        return app(\App\Http\Controllers\Admin\ReportController::class)->generatePerformanceAllowancePdf($request, $employee, $year, $month);
    }
}
