<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\Employee;
use App\Models\Attendance;
use App\Models\Permission;
use App\Models\EmployeeShift;
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

        // Fetch all necessary data for the entire year
        $shifts = EmployeeShift::where('employee_id', $employee->id)
            ->whereYear('date', $selectedYear)
            ->with('schedule')
            ->get()
            ->keyBy(function ($shift) {
                return $shift->date->format('Y-m-d');
            });

        $attendances = Attendance::where('attendable_id', $employee->id)
            ->where('attendable_type', Employee::class)
            ->whereYear('date', $selectedYear)
            ->get()
            ->keyBy(function ($item) {
                return $item->date->format('Y-m-d');
            });

        $permissions = Permission::where('permissible_id', $employee->id)
            ->where('permissible_type', Employee::class)
            ->where('status', 'approved')
            ->where(function ($query) use ($selectedYear) {
                $query->whereYear('start_date', $selectedYear)
                      ->orWhereYear('end_date', $selectedYear);
            })
            ->get();

        $permissionMap = [];
        $permissionTypesFull = [
            'C' => 'Cuti',
            'S' => 'Sakit',
            'DL' => 'Dinas Luar',
        ];
        foreach ($permissions as $permission) {
            $current = Carbon::parse($permission->start_date);
            $end = Carbon::parse($permission->end_date);
            while ($current->lte($end)) {
                $permissionMap[$current->format('Y-m-d')] = $permissionTypesFull[$permission->type] ?? $permission->type;
                $current->addDay();
            }
        }
        
        $summaries = [];
        for ($month = 1; $month <= 12; $month++) {
            $monthStartDate = Carbon::create($selectedYear, $month, 1);

            $summary = [
                'month_number' => $month,
                'month_name' => $monthStartDate->isoFormat('MMMM'),
                'active_days' => 0,
                'present_days' => 0,
                'on_time_days' => 0,
                'late_days' => 0,
                'early_leave_days' => 0,
                'sick_days' => 0,
                'leave_days' => 0,
                'official_duty_days' => 0,
                'absent_days' => 0,
            ];

            for ($day = 1; $day <= $monthStartDate->daysInMonth; $day++) {
                $currentDate = Carbon::create($selectedYear, $month, $day);
                $dateKey = $currentDate->format('Y-m-d');

                $permissionType = $permissionMap[$dateKey] ?? null;
                $shift = $shifts->get($dateKey);
                $attendance = $attendances->get($dateKey);

                $isWorkDay = ($shift && !$permissionType);
                $isPresent = ($attendance && ($attendance->clock_in || $attendance->clock_out) && !$permissionType);

                // Count permission days
                if ($permissionType) {
                    if ($permissionType === 'Sakit') $summary['sick_days']++;
                    if ($permissionType === 'Cuti') $summary['leave_days']++;
                    if ($permissionType === 'Dinas Luar') $summary['official_duty_days']++;
                }

                // Count active (shift) days
                if ($shift) {
                    $summary['active_days']++;
                }
                
                // Count present, absent, and statuses only on work days
                if ($isWorkDay) {
                    if ($isPresent) {
                        $summary['present_days']++;
                        
                        $schedule = $shift->schedule;
                        if ($schedule) {
                            $statusIn = 'Tidak Hadir';
                            $statusOut = 'Tidak Hadir';

                            if ($attendance->clock_in) {
                                $diffIn = $this->calculateTimeDifference($attendance->clock_in, $schedule->start_time);
                                // Fixed: If actual > scheduled (positive seconds), then late
                                $statusIn = ($diffIn['seconds'] > 0) ? 'Terlambat' : 'Tepat Waktu';
                            }

                            if ($attendance->clock_out) {
                                $diffOut = $this->calculateTimeDifference($attendance->clock_out, $schedule->end_time);
                                // If actual < scheduled (negative seconds), then early departure
                                $statusOut = ($diffOut['seconds'] < 0) ? 'Pulang Awal' : 'Tepat Waktu';
                            } else {
                                $statusOut = 'Belum Pulang';
                            }

                            if ($statusIn === 'Terlambat') $summary['late_days']++;
                            if ($statusOut === 'Pulang Awal') $summary['early_leave_days']++;
                            if ($statusIn === 'Tepat Waktu' && $statusOut === 'Tepat Waktu') $summary['on_time_days']++;
                        }
                    } else {
                        // It's a workday, but the user was not present and had no permission
                        $summary['absent_days']++;
                    }
                }
            }
            $summaries[] = $summary;
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
        // Load salary relation (hasOne - no month/year filter needed)
        $employee->load('salary');
        $salary = $employee->salary;

        if (!$salary) {
            return [
                'employee' => $employee,
                'summary' => null,
                'detailData' => [],
                'permissionMap' => [],
            ];
        }

        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();
        $daysInMonth = $startDate->daysInMonth;

        $employeeShifts = \App\Models\EmployeeShift::where('employee_id', $employee->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->with('schedule')
            ->get()
            ->keyBy(function ($shift) {
                return $shift->date->format('Y-m-d');
            });

        $attendances = Attendance::where('attendable_id', $employee->id)
            ->where('attendable_type', Employee::class)
            ->whereBetween('date', [$startDate, $endDate])
            ->get()
            ->keyBy(function ($item) {
                return $item->date->format('Y-m-d');
            });

        $permissions = \App\Models\Permission::where('permissible_id', $employee->id)
            ->where('permissible_type', Employee::class)
            ->where('status', 'approved')
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate])
                    ->orWhere(function($q) use ($startDate, $endDate) {
                        $q->where('start_date', '<=', $startDate)
                          ->where('end_date', '>=', $endDate);
                    });
            })
            ->get();

        $permissionMap = [];
        $permissionTypesFull = ['C' => 'Cuti', 'S' => 'Sakit', 'DL' => 'Dinas Luar'];
        foreach ($permissions as $permission) {
            $current = Carbon::parse($permission->start_date)->max($startDate);
            $end = Carbon::parse($permission->end_date)->min($endDate);
            while ($current->lte($end)) {
                $permissionMap[$current->format('Y-m-d')] = $permissionTypesFull[$permission->type] ?? $permission->type;
                $current->addDay();
            }
        }

        $detailData = [];
        $totalMealAllowance = 0;
        $totalTransportAllowance = 0;
        $totalPerformanceAllowance = 0;

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $currentDate = Carbon::create($year, $month, $day);
            $dateKey = $currentDate->format('Y-m-d');
            
            $attendance = $attendances->get($dateKey);
            $employeeShift = $employeeShifts->get($dateKey);
            $schedule = $employeeShift ? $employeeShift->schedule : null;
            $permissionType = $permissionMap[$dateKey] ?? null;

            $dailyMealAllowance = 0;
            $dailyTransportAllowance = 0;
            $dailyPerformanceAllowance = 0;
            
            $statusIn = '-';
            $statusOut = '-';

            if ($permissionType) {
                $statusIn = $permissionType;
                $statusOut = $permissionType;
            } elseif ($schedule) {
                if ($attendance) {
                    if ($attendance->clock_in) {
                        $diffIn = $this->calculateTimeDifference($attendance->clock_in, $schedule->start_time);
                        // Fixed: If actual > scheduled (positive seconds), then late
                        $statusIn = ($diffIn['seconds'] > 0) ? 'Terlambat' : 'Tepat Waktu';
                    } else {
                        $statusIn = 'Tidak Hadir';
                    }

                    if ($attendance->clock_out) {
                        $diffOut = $this->calculateTimeDifference($attendance->clock_out, $schedule->end_time);
                        // If actual < scheduled (negative seconds), then early departure
                        $statusOut = ($diffOut['seconds'] < 0) ? 'Pulang Awal' : 'Tepat Waktu';
                    } else {
                        $statusOut = 'Belum Pulang';
                    }
                    
                    $isPresent = $attendance->clock_in !== null || $attendance->clock_out !== null;
                    if ($isPresent) {
                        $dailyMealAllowance = $salary->meal_allowance;
                        $dailyTransportAllowance = $salary->transport_allowance;
                        $totalMealAllowance += $dailyMealAllowance;
                        $totalTransportAllowance += $dailyTransportAllowance;
                        
                        $isPerfect = $statusIn === 'Tepat Waktu' && $statusOut === 'Tepat Waktu';
                        if ($isPerfect) {
                            $dailyPerformanceAllowance = $salary->performance_allowance;
                            $totalPerformanceAllowance += $dailyPerformanceAllowance;
                        }
                    }
                } else {
                    $statusIn = 'Tidak Hadir';
                    $statusOut = 'Tidak Hadir';
                }
            } else {
                $statusIn = 'Hari Libur';
                $statusOut = 'Hari Libur';
            }

            $detailData[] = [
                'date' => $currentDate,
                'attendance' => $attendance,
                'schedule' => $schedule,
                'status_in' => $statusIn,
                'status_out' => $statusOut,
                'daily_meal_allowance' => $dailyMealAllowance,
                'daily_transport_allowance' => $dailyTransportAllowance,
                'daily_performance_allowance' => $dailyPerformanceAllowance,
            ];
        }

        $totalSalary = $salary->base_salary + $totalMealAllowance + $totalTransportAllowance + $totalPerformanceAllowance;

        $summary = [
            'base_salary' => $salary->base_salary,
            'total_meal_allowance' => $totalMealAllowance,
            'total_transport_allowance' => $totalTransportAllowance,
            'total_performance_allowance' => $totalPerformanceAllowance,
            'total_salary' => $totalSalary,
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

    private function calculateTimeDifference($actualTime, $scheduleTime)
    {
        $actual = Carbon::parse($actualTime);
        $scheduled = Carbon::parse($scheduleTime)->setDate($actual->year, $actual->month, $actual->day);
        // Fixed: Calculate difference as scheduled - actual for correct sign
        // Positive = late (actual > scheduled), Negative = early (actual < scheduled)
        $diffSeconds = $scheduled->diffInSeconds($actual, false);

        return ['seconds' => $diffSeconds];
    }
}
