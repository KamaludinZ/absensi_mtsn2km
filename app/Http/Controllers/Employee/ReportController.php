<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\Employee;
use App\Models\Attendance;
use App\Models\EmployeeShift;
use App\Services\EmployeeAllowanceCalculator;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    public function salaryReport(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $employee = $user->employee;

        if (!$employee) {
            return redirect()->route('employee.dashboard')->with('error', 'Data pegawai tidak ditemukan.');
        }

        $selectedYear = $request->input('year', Carbon::now()->year);

        // Get unique years from employee shifts
        $yearsFromShifts = EmployeeShift::where('employee_id', $employee->id)
            ->selectRaw('EXTRACT(YEAR FROM date) as year')
            ->distinct()
            ->pluck('year')
            ->map(fn($year) => (int)$year);

        // Get unique years from attendance records
        $yearsFromAttendance = Attendance::where('attendable_type', Employee::class)
            ->where('attendable_id', $employee->id)
            ->selectRaw('EXTRACT(YEAR FROM date) as year')
            ->distinct()
            ->pluck('year')
            ->map(fn($year) => (int)$year);

        // Combine and sort unique years
        $allYears = $yearsFromShifts->merge($yearsFromAttendance)->unique()->sortDesc()->values();

        $currentYear = (int)Carbon::now()->year;
        
        // Ensure current year and a few past years are always present
        $maxShownYear = $currentYear;
        $minShownYear = $currentYear - 2; // Show at least current year and 2 past years

        // Adjust min/max based on actual data
        if ($allYears->isNotEmpty()) {
            $minShownYear = min($minShownYear, $allYears->min());
            $maxShownYear = max($maxShownYear, $allYears->max());
        }

        // Generate the final list of years for the dropdown
        $years = collect(range($maxShownYear, $minShownYear))->sortDesc()->values();

        if ($years->isEmpty()) {
            $years = collect([$currentYear]);
        }

        // Same counting rules as the admin allowance and salary reports
        $summaries = [];
        for ($month = 1; $month <= 12; $month++) {
            $loaded = EmployeeAllowanceCalculator::loadMonth([$employee->id], (int) $selectedYear, $month)[$employee->id];
            $result = EmployeeAllowanceCalculator::summarize(null, (int) $selectedYear, $month, $loaded);

            $summaries[] = [
                'month_number' => $month,
                'month_name' => Carbon::create($selectedYear, $month, 1)->isoFormat('MMMM'),
                'active_days' => $result['active_days'],
                'present_days' => $result['present_days'],
                'absent_days' => $result['absent_days'],
                'on_time_arrival_days' => $result['on_time_arrival_days'],
                'on_time_departure_days' => $result['on_time_departure_days'],
                'on_time_days' => $result['on_time_both_days'],
                'late_days' => $result['late_arrival_days'],
                'early_leave_days' => $result['early_departure_days'],
                'sick_days' => $result['sick_days'],
                'leave_days' => $result['leave_days'],
                'official_duty_days' => $result['official_duty_days'],
            ];
        }

        return view('employee.salary.index', compact('summaries', 'years', 'selectedYear'));
    }

    public function salaryReportDetail(Request $request, $year, $month)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $employee = $user->employee;

        if (!$employee) {
            return redirect()->route('employee.dashboard')->with('error', 'Data pegawai tidak ditemukan.');
        }

        $monthName = Carbon::create($year, $month)->isoFormat('MMMM');

        $data = $this->getSalaryDetailData($employee, $year, $month);

        return view('employee.salary.show', array_merge($data, [
            'year' => $year,
            'month' => $month, // Pass month number as well
            'monthName' => $monthName
        ]));
    }

    private function getSalaryDetailData(Employee $employee, $year, $month)
    {
        $salary = EmployeeAllowanceCalculator::salaryFor($employee, (int) $year, (int) $month);

        if (!$salary) {
            return [
                'employee' => $employee,
                'summary' => null,
                'detailData' => [],
                'permissionMap' => [],
            ];
        }

        $loaded = EmployeeAllowanceCalculator::loadMonth([$employee->id], (int) $year, (int) $month)[$employee->id];
        $result = EmployeeAllowanceCalculator::summarize($salary, (int) $year, (int) $month, $loaded);

        $detailData = $result['days'];
        $permissionMap = $loaded['permissions'];
        $summary = [
            'base_salary' => $result['base_salary'],
            'total_meal_allowance' => $result['total_meal_allowance'],
            'total_transport_allowance' => $result['total_transport_allowance'],
            'total_performance_allowance' => $result['total_performance_allowance'],
            'total_salary' => $result['total_salary'],
        ];

        return compact('employee', 'detailData', 'summary', 'permissionMap');
    }

    public function generateSalaryPdf(Request $request, $year, $month)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $employee = $user->employee;

        if (!$employee) {
            return redirect()->route('employee.dashboard')->with('error', 'Data pegawai tidak ditemukan.');
        }

        // Get header settings from database
        $schoolName = \App\Models\AttendanceSetting::where('setting_key', 'school_name')->first();
        $schoolFullName = \App\Models\AttendanceSetting::where('setting_key', 'school_full_name')->first();
        $schoolAddress = \App\Models\AttendanceSetting::where('setting_key', 'school_address')->first();
        $schoolPhone = \App\Models\AttendanceSetting::where('setting_key', 'school_phone')->first();
        $schoolEmail = \App\Models\AttendanceSetting::where('setting_key', 'school_email')->first();
        $leftLogoPath = \App\Models\AttendanceSetting::where('setting_key', 'left_logo_path')->first();
        $rightLogoPath = \App\Models\AttendanceSetting::where('setting_key', 'right_logo_path')->first();
        $headmasterName = \App\Models\AttendanceSetting::where('setting_key', 'headmaster_name')->first();
        $headmasterNip = \App\Models\AttendanceSetting::where('setting_key', 'headmaster_nip')->first();
        $adminTataUsahaName = \App\Models\AttendanceSetting::where('setting_key', 'admin_tata_usaha_name')->first();
        $adminTataUsahaNip = \App\Models\AttendanceSetting::where('setting_key', 'admin_tata_usaha_nip')->first();
        $activeSigner = \App\Models\AttendanceSetting::where('setting_key', 'active_signer')->first();

        // Convert logos to base64 for PDF
        $leftLogoBase64 = '';
        $rightLogoBase64 = '';

        if ($leftLogoPath && $leftLogoPath->setting_value) {
            $fullPath = public_path($leftLogoPath->setting_value);
            if (file_exists($fullPath)) {
                $imageData = base64_encode(file_get_contents($fullPath));
                $imageType = pathinfo($fullPath, PATHINFO_EXTENSION);
                $leftLogoBase64 = 'data:image/' . $imageType . ';base64,' . $imageData;
            }
        }

        if ($rightLogoPath && $rightLogoPath->setting_value) {
            $fullPath = public_path($rightLogoPath->setting_value);
            if (file_exists($fullPath)) {
                $imageData = base64_encode(file_get_contents($fullPath));
                $imageType = pathinfo($fullPath, PATHINFO_EXTENSION);
                $rightLogoBase64 = 'data:image/' . $imageType . ';base64,' . $imageData;
            }
        }

        $activeSignerValue = $activeSigner ? $activeSigner->setting_value : 'headmaster';
        $headmasterNameVal = $headmasterName ? $headmasterName->setting_value : '';
        $headmasterNipVal = $headmasterNip ? $headmasterNip->setting_value : '';
        $tataUsahaNameVal = $adminTataUsahaName ? $adminTataUsahaName->setting_value : '';
        $tataUsahaNipVal = $adminTataUsahaNip ? $adminTataUsahaNip->setting_value : '';

        if ($activeSignerValue === 'tata_usaha') {
            $activeSignerName = $tataUsahaNameVal ?: '................................';
            $activeSignerNip = $tataUsahaNipVal ?: '................................';
            $activeSignerTitle = 'Kepala Tata Usaha';
        } else {
            $activeSignerName = $headmasterNameVal ?: '................................';
            $activeSignerNip = $headmasterNipVal ?: '................................';
            $activeSignerTitle = 'Kepala Madrasah';
        }

        $headerSettings = [
            'school_name' => $schoolName ? $schoolName->setting_value : 'MTsN 2 KOTA MALANG',
            'school_full_name' => $schoolFullName ? $schoolFullName->setting_value : 'MADRASAH TSANAWIYAH NEGERI 2 KOTA MALANG',
            'school_address' => $schoolAddress ? $schoolAddress->setting_value : 'Jl. KH. Hasyim Asy\'ari No. 1, Kota Malang, Jawa Timur 65144',
            'school_phone' => $schoolPhone ? $schoolPhone->setting_value : '(0341) 123456',
            'school_email' => $schoolEmail ? $schoolEmail->setting_value : 'info@mtsn2kotamalang.sch.id',
            'left_logo' => $leftLogoBase64,
            'right_logo' => $rightLogoBase64,
            'headmaster_name' => $headmasterNameVal,
            'headmaster_nip' => $headmasterNipVal,
            'admin_tata_usaha_name' => $tataUsahaNameVal,
            'admin_tata_usaha_nip' => $tataUsahaNipVal,
            'active_signer' => $activeSignerValue,
            'active_signer_name' => $activeSignerName,
            'active_signer_nip' => $activeSignerNip,
            'active_signer_title' => $activeSignerTitle,
        ];

        $data = $this->getSalaryDetailData($employee, $year, $month);
        $monthName = Carbon::create($year, $month)->isoFormat('MMMM');

        $pdf = Pdf::loadView('employee.salary.pdf', array_merge($data, [
            'year' => $year,
            'month' => $month,
            'monthName' => $monthName,
            'headerSettings' => $headerSettings,
        ]))
            ->setPaper('a4', 'portrait');

        $filename = 'laporan_gaji_' . str_replace(' ', '_', $employee->full_name) . '_' . $monthName . '_' . $year . '.pdf';
        return $pdf->download($filename);
    }
}
