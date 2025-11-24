<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Schedule;
use App\Models\User;
use App\Models\LocationSetting;
use App\Models\AttendanceSetting;
use App\Helpers\AttendanceHelper;
use Carbon\Carbon;
use Intervention\Image\Laravel\Facades\Image;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AttendanceController extends Controller
{
    public function index()
    {
        /** @var User $user */
        $user = Auth::user();
        $employee = $user->employee;

        if (!$employee) {
            return redirect()->back()->with('error', 'Data pegawai tidak ditemukan.');
        }

        $today = Carbon::today();
        $attendance = Attendance::where('attendable_type', Employee::class)
            ->where('attendable_id', $employee->id)
            ->whereDate('date', $today)
            ->first();

        // Get attendance settings
        $requireSelfie = AttendanceSetting::where('setting_key', 'require_selfie_photo')->first();
        $requireCheckIn = AttendanceSetting::where('setting_key', 'require_check_in')->first();
        $requireCheckOut = AttendanceSetting::where('setting_key', 'require_check_out')->first();

        $attendanceSettings = [
            'require_selfie_photo' => $requireSelfie ? filter_var($requireSelfie->setting_value, FILTER_VALIDATE_BOOLEAN) : true,
            'require_check_in' => $requireCheckIn ? filter_var($requireCheckIn->setting_value, FILTER_VALIDATE_BOOLEAN) : true,
            'require_check_out' => $requireCheckOut ? filter_var($requireCheckOut->setting_value, FILTER_VALIDATE_BOOLEAN) : true,
        ];

        // Get location display settings
        $displayWithCoordinates = LocationSetting::where('setting_key', 'display_with_coordinates')->first();
        $displayWithMap = LocationSetting::where('setting_key', 'display_with_map')->first();

        $locationSettings = [
            'display_with_coordinates' => $displayWithCoordinates ? filter_var($displayWithCoordinates->setting_value, FILTER_VALIDATE_BOOLEAN) : true,
            'display_with_map' => $displayWithMap ? filter_var($displayWithMap->setting_value, FILTER_VALIDATE_BOOLEAN) : true,
        ];

        return view('employee.attendance', compact('employee', 'attendance', 'attendanceSettings', 'locationSettings'));
    }

    public function clockIn(Request $request)
    {
        try {
            // Check attendance settings
            $requireSelfie = AttendanceSetting::where('setting_key', 'require_selfie_photo')->first();
            $requireSelfiePhoto = $requireSelfie ? filter_var($requireSelfie->setting_value, FILTER_VALIDATE_BOOLEAN) : true;

            // Build validation rules dynamically based on settings
            $validationRules = [
                'location' => 'array|nullable',
                'location.latitude' => 'required_with:location|numeric|between:-90,90',
                'location.longitude' => 'required_with:location|numeric|between:-180,180',
                'location.accuracy' => 'required_with:location|numeric|min:0',
            ];

            // Add selfie validation only if required
            if ($requireSelfiePhoto) {
                $validationRules['selfie'] = [
                    'required',
                    'string',
                    'regex:/^data:image\/(?:jpeg|png|jpg);base64,[A-Za-z0-9+\/=]+$/',
                    'max:2000000' // 2MB max file size
                ];
            } else {
                $validationRules['selfie'] = 'nullable|string|regex:/^data:image\/(?:jpeg|png|jpg);base64,[A-Za-z0-9+\/=]+$/|max:2000000';
            }

            $request->validate($validationRules);

            /** @var User $user */
            $user = Auth::user();
            $employee = $user->employee;

            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data pegawai tidak ditemukan.',
                ]);
            }

            // Check location requirements
            $locationSetting = LocationSetting::where('setting_key', 'location_required')->first();
            $locationRequired = $locationSetting ? filter_var($locationSetting->setting_value, FILTER_VALIDATE_BOOLEAN) : false;

            if ($locationRequired && (!$request->has('location') || empty($request->location))) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lokasi diperlukan untuk absensi. Harap izinkan akses lokasi.',
                ]);
            }

            // Check geofence if location is provided
            if ($request->has('location') && !empty($request->location)) {
                $isValidLocation = $this->validateGeofence(
                    $request->location['latitude'],
                    $request->location['longitude'],
                    $request->location['accuracy']
                );

                if (!$isValidLocation) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Lokasi Anda berada di luar area geofence yang ditentukan.',
                    ]);
                }
            }

            // Check attendance
            $today = Carbon::today();
            $attendance = Attendance::where('attendable_type', Employee::class)
                ->where('attendable_id', $employee->id)
                ->whereDate('date', $today)
                ->first();

            if ($attendance && $attendance->clock_in) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda sudah melakukan absen masuk hari ini.',
                ]);
            }

            // Process selfie only if provided
            $selfiePath = null;
            if ($request->has('selfie') && !empty($request->selfie)) {
                $selfiePath = $this->processSelfie($request->selfie, 'employee', $employee->id, 'in');
            }

            // Get location data if provided
            $latitude = null;
            $longitude = null;
            $accuracy = null;
            
            if ($request->has('location') && !empty($request->location)) {
                $location = $request->location;
                $latitude = isset($location['latitude']) ? floatval($location['latitude']) : null;
                $longitude = isset($location['longitude']) ? floatval($location['longitude']) : null;
                $accuracy = isset($location['accuracy']) ? floatval($location['accuracy']) : null;
            }

            // Determine status
            $now = Carbon::now();
            $schedule = Schedule::where('type', 'pegawai')
                ->where('day_of_week', $now->dayOfWeek)
                ->first();

            $status = 'Tepat Waktu';
            if ($schedule) {
                $scheduleTime = Carbon::parse($schedule->start_time);
                if ($now->gt($scheduleTime)) {
                    $status = 'Terlambat';
                }
            }

            // Get shift information for the employee
            $shiftInfo = null;
            if ($schedule) {
                $shiftInfo = $schedule;
            } else {
                // Fallback to default schedule
                $shiftInfo = Schedule::where('type', 'pegawai')
                    ->where('day_of_week', $now->dayOfWeek)
                    ->first();
            }
            
            // Create or update attendance
            if ($attendance) {
                $attendance->update([
                    'clock_in' => $now,
                    'clock_in_latitude' => $latitude,
                    'clock_in_longitude' => $longitude,
                    'clock_in_accuracy' => $accuracy,
                    'selfie_path_in' => $selfiePath,
                    'status_in' => $status,
                    'shift_name' => $shiftInfo ? $shiftInfo->shift_name : null,
                    'shift_type' => $shiftInfo ? $shiftInfo->shift_type : null,
                    'scheduled_start_time' => $shiftInfo ? $shiftInfo->start_time : null,
                    'scheduled_end_time' => $shiftInfo ? $shiftInfo->end_time : null,
                ]);
            } else {
                Attendance::create([
                    'attendable_type' => Employee::class,
                    'attendable_id' => $employee->id,
                    'date' => $today,
                    'clock_in' => $now,
                    'clock_in_latitude' => $latitude,
                    'clock_in_longitude' => $longitude,
                    'clock_in_accuracy' => $accuracy,
                    'selfie_path_in' => $selfiePath,
                    'status_in' => $status,
                    'shift_name' => $shiftInfo ? $shiftInfo->shift_name : null,
                    'shift_type' => $shiftInfo ? $shiftInfo->shift_type : null,
                    'scheduled_start_time' => $shiftInfo ? $shiftInfo->start_time : null,
                    'scheduled_end_time' => $shiftInfo ? $shiftInfo->end_time : null,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Absen masuk berhasil dicatat.',
                'status' => $status,
                'redirect_url' => '/employee/attendance/report' // Redirect to report page after successful attendance
            ]);
        } catch (\Exception $e) {
            /** @var User|null $logUser */
            $logUser = Auth::user();
            Log::error('Employee clock-in error: ' . $e->getMessage(), [
                'user_id' => $logUser?->id ?? null,
                'employee_id' => $logUser?->employee?->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memproses absensi. Silakan coba lagi.',
            ], 500);
        }
    }

    public function clockOut(Request $request)
    {
        try {
            // Check attendance settings
            $requireSelfie = AttendanceSetting::where('setting_key', 'require_selfie_photo')->first();
            $requireSelfiePhoto = $requireSelfie ? filter_var($requireSelfie->setting_value, FILTER_VALIDATE_BOOLEAN) : true;

            $requireCheckInSetting = AttendanceSetting::where('setting_key', 'require_check_in')->first();
            $requireCheckIn = $requireCheckInSetting ? filter_var($requireCheckInSetting->setting_value, FILTER_VALIDATE_BOOLEAN) : true;

            // Build validation rules dynamically based on settings
            $validationRules = [
                'location' => 'array|nullable',
                'location.latitude' => 'required_with:location|numeric|between:-90,90',
                'location.longitude' => 'required_with:location|numeric|between:-180,180',
                'location.accuracy' => 'required_with:location|numeric|min:0',
            ];

            // Add selfie validation only if required
            if ($requireSelfiePhoto) {
                $validationRules['selfie'] = [
                    'required',
                    'string',
                    'regex:/^data:image\/(?:jpeg|png|jpg);base64,[A-Za-z0-9+\/=]+$/',
                    'max:2000000' // 2MB max file size
                ];
            } else {
                $validationRules['selfie'] = 'nullable|string|regex:/^data:image\/(?:jpeg|png|jpg);base64,[A-Za-z0-9+\/=]+$/|max:2000000';
            }

            $request->validate($validationRules);

            /** @var User $user */
            $user = Auth::user();
            $employee = $user->employee;

            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data pegawai tidak ditemukan.',
                ]);
            }

            // Check location requirements
            $locationSetting = LocationSetting::where('setting_key', 'location_required')->first();
            $locationRequired = $locationSetting ? filter_var($locationSetting->setting_value, FILTER_VALIDATE_BOOLEAN) : false;

            if ($locationRequired && (!$request->has('location') || empty($request->location))) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lokasi diperlukan untuk absensi. Harap izinkan akses lokasi.',
                ]);
            }

            // Check geofence if location is provided
            if ($request->has('location') && !empty($request->location)) {
                $isValidLocation = $this->validateGeofence(
                    $request->location['latitude'],
                    $request->location['longitude'],
                    $request->location['accuracy']
                );

                if (!$isValidLocation) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Lokasi Anda berada di luar area geofence yang ditentukan.',
                    ]);
                }
            }

            // Check attendance
            $today = Carbon::today();
            $attendance = Attendance::where('attendable_type', Employee::class)
                ->where('attendable_id', $employee->id)
                ->whereDate('date', $today)
                ->first();

            // ENFORCEMENT OF 'require_check_in'
            if ($requireCheckIn && (!$attendance || !$attendance->clock_in)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda belum melakukan absen masuk hari ini. Absen masuk diwajibkan.',
                ]);
            }

            // If clock-in is NOT required, and there's no attendance record yet, create one for clock-out
            // This handles the case where a user only clocks out.
            if (!$requireCheckIn && !$attendance) {
                 $attendance = Attendance::create([
                    'attendable_type' => Employee::class,
                    'attendable_id' => $employee->id,
                    'date' => $today,
                    // clock_in remains null
                ]);
            }

            if ($attendance->clock_out) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda sudah melakukan absen keluar hari ini.',
                ]);
            }

            // Process selfie only if provided
            $selfiePath = null;
            if ($request->has('selfie') && !empty($request->selfie)) {
                $selfiePath = $this->processSelfie($request->selfie, 'employee', $employee->id, 'out');
            }

            // Determine status
            $now = Carbon::now();
            $schedule = Schedule::where('type', 'pegawai')
                ->where('day_of_week', $now->dayOfWeek)
                ->first();

            $status = 'Tepat Waktu';
            if ($schedule) {
                $scheduleTime = Carbon::parse($schedule->end_time);
                if ($now->lt($scheduleTime)) {
                    $status = 'Pulang Awal';
                }
            }

            // Get shift information for the employee
            $shiftInfo = null;
            if ($schedule) {
                $shiftInfo = $schedule;
            } else {
                // Fallback to default schedule
                $shiftInfo = Schedule::where('type', 'pegawai')
                    ->where('day_of_week', $now->dayOfWeek)
                    ->first();
            }
            
            // Get location data if provided
            $latitude = null;
            $longitude = null;
            $accuracy = null;
            
            if ($request->has('location') && !empty($request->location)) {
                $location = $request->location;
                $latitude = isset($location['latitude']) ? floatval($location['latitude']) : null;
                $longitude = isset($location['longitude']) ? floatval($location['longitude']) : null;
                $accuracy = isset($location['accuracy']) ? floatval($location['accuracy']) : null;
            }

            $attendance->update([
                'clock_out' => $now,
                'clock_out_latitude' => $latitude,
                'clock_out_longitude' => $longitude,
                'clock_out_accuracy' => $accuracy,
                'selfie_path_out' => $selfiePath,
                'status_out' => $status,
                'shift_name' => $shiftInfo ? $shiftInfo->shift_name : null,
                'shift_type' => $shiftInfo ? $shiftInfo->shift_type : null,
                'scheduled_start_time' => $shiftInfo ? $shiftInfo->start_time : null,
                'scheduled_end_time' => $shiftInfo ? $shiftInfo->end_time : null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Absen keluar berhasil dicatat.',
                'status' => $status,
                'redirect_url' => '/employee/attendance/report' // Redirect to report page after successful attendance
            ]);
        } catch (\Exception $e) {
            /** @var User|null $logUser */
            $logUser = Auth::user();
            Log::error('Employee clock-out error: ' . $e->getMessage(), [
                'user_id' => $logUser?->id ?? null,
                'employee_id' => $logUser?->employee?->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memproses absensi. Silakan coba lagi.',
            ], 500);
        }
    }

    public function report(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();
        $employee = $user->employee;

        if (!$employee) {
            return redirect()->back()->with('error', 'Data pegawai tidak ditemukan.');
        }

        $query = Attendance::where('attendable_type', Employee::class)
            ->where('attendable_id', $employee->id);

        // Get all academic years for the dropdown
        $academicYears = \App\Models\AcademicYear::orderBy('year_name', 'desc')->get();

        // Set default academic year to active one if none provided
        $activeAcademicYear = \App\Models\AcademicYear::where('status', 'active')->first();
        $selectedAcademicYearId = $request->filled('academic_year_id') ? $request->academic_year_id : ($activeAcademicYear ? $activeAcademicYear->id : null);

        // Filter by academic year if selected
        if ($selectedAcademicYearId) {
            $academicYear = \App\Models\AcademicYear::find($selectedAcademicYearId);
            if ($academicYear) {
                // Extract start and end years from academic year name (e.g., "2024-2025")
                $yearParts = explode('-', $academicYear->year_name);
                $startYear = intval($yearParts[0]);
                $endYear = isset($yearParts[1]) ? intval($yearParts[1]) : $startYear;
                
                // If academic year spans two calendar years (e.g., 2024-2025), we need to consider that
                $startDate = $startYear . '-01-01';
                $endDate = $endYear . '-12-31';
                
                $query->whereBetween('date', [$startDate, $endDate]);
            }
        }

        // Additionally filter by month if provided
        $selectedMonth = $request->filled('month') ? $request->month : now()->month;
        $query->whereMonth('date', $selectedMonth);

        // Handle sorting
        $sortField = $request->get('sort', 'date'); // Default sort by date
        $sortDirection = $request->get('direction', 'desc'); // Default descending

        // Validate sort field to prevent SQL injection
        $allowedSortFields = ['date', 'clock_in', 'clock_out', 'status_in', 'status_out'];
        if (!in_array($sortField, $allowedSortFields)) {
            $sortField = 'date';
        }

        // Validate sort direction
        $sortDirection = strtolower($sortDirection) === 'asc' ? 'asc' : 'desc';

        $attendances = $query->orderBy($sortField, $sortDirection)
            ->with(['attendable']) // Load employee relationship
            ->paginate(20)
            ->appends([
                'sort' => $sortField,
                'direction' => $sortDirection,
                'month' => $selectedMonth,
                'academic_year_id' => $selectedAcademicYearId
            ]);

        // Pre-load employee shifts and schedules to avoid N+1 queries in view
        $employeeId = $employee->id;
        $dates = $attendances->pluck('date');

        $employeeShifts = \App\Models\EmployeeShift::where('employee_id', $employeeId)
            ->whereIn('date', $dates)
            ->with('schedule')
            ->get()
            ->keyBy(function($shift) {
                return $shift->date->format('Y-m-d');
            });

        $schedules = Schedule::where('type', 'pegawai')
            ->get()
            ->keyBy('day_of_week');

        // Get available months for the dropdown
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

        // Get time difference format setting
        $timeDiffFormatSetting = \App\Models\AttendanceSetting::where('setting_key', 'time_difference_format')->first();
        $timeDiffFormat = $timeDiffFormatSetting ? $timeDiffFormatSetting->setting_value : 'time';

        return view('employee.report', compact('attendances', 'months', 'academicYears', 'selectedAcademicYearId', 'employeeShifts', 'schedules', 'sortField', 'sortDirection', 'timeDiffFormat', 'selectedMonth'));
    }

    public function export(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();
        $employee = $user->employee;

        if (!$employee) {
            return redirect()->back()->with('error', 'Data pegawai tidak ditemukan.');
        }

        $query = Attendance::where('attendable_type', Employee::class)
            ->where('attendable_id', $employee->id);

        // Get all academic years for reference
        $academicYears = \App\Models\AcademicYear::orderBy('year_name', 'desc')->get();

        // Apply academic year filter if selected
        $selectedAcademicYearId = $request->filled('academic_year_id') ? $request->academic_year_id : null;
        if ($selectedAcademicYearId) {
            $academicYear = \App\Models\AcademicYear::find($selectedAcademicYearId);
            if ($academicYear) {
                $yearParts = explode('-', $academicYear->year_name);
                $startYear = intval($yearParts[0]);
                $endYear = isset($yearParts[1]) ? intval($yearParts[1]) : $startYear;
                
                $startDate = $startYear . '-01-01';
                $endDate = $endYear . '-12-31';
                
                $query->whereBetween('date', [$startDate, $endDate]);
            }
        }

        // Additionally filter by month if provided
        $month = $request->filled('month') ? $request->month : now()->month;
        $query->whereMonth('date', $month);

        $attendances = $query->orderBy('date', 'desc')
            ->with(['attendable']) // Load employee relationship
            ->get();

        $format = $request->input('format', 'pdf'); // Default to PDF

        if ($format === 'excel') {
            return $this->exportToExcel($attendances, $employee);
        } else {
            return $this->exportToPdf($attendances, $employee);
        }
    }

    private function exportToExcel($attendances, $employee)
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header
        $sheet->setCellValue('A1', 'LAPORAN ABSENSI PEGAWAI');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->mergeCells('A1:F1');

        $sheet->setCellValue('A3', 'Nama Pegawai');
        $sheet->setCellValue('B3', ': ' . $employee->full_name);
        $sheet->setCellValue('A4', 'Jabatan');
        $sheet->setCellValue('B4', ': ' . $employee->position);

        // Table header
        $headers = ['Tanggal', 'Nama Shift', 'Jam Masuk', 'Absensi Masuk', 'Selisih Masuk', 'Jam Keluar', 'Absensi Pulang', 'Selisih Keluar'];
        $column = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($column . '6', $header);
            $sheet->getStyle($column . '6')->getFont()->setBold(true);
            $sheet->getStyle($column . '6')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('E2E8F0');
            $column++;
        }

        // Data rows
        $row = 7;
        foreach ($attendances as $attendance) {
            $shiftName = $this->getShiftName($attendance);
            $schedule = $this->getSchedule($attendance);
            $inTimeDiff = $this->getInTimeDifference($attendance);
            $outTimeDiff = $this->getOutTimeDifference($attendance);

            $sheet->setCellValue('A' . $row, $attendance->date ? $attendance->date->format('d/m/Y') : '-');
            $sheet->setCellValue('B' . $row, $shiftName);
            $sheet->setCellValue('C' . $row, $schedule ? Carbon::parse($schedule->start_time)->format('H:i:s') : '-');
            $sheet->setCellValue('D' . $row, $attendance->clock_in ? $attendance->clock_in->format('H:i:s') : '-');
            $sheet->setCellValue('E' . $row, $inTimeDiff);
            $sheet->setCellValue('F' . $row, $schedule ? Carbon::parse($schedule->end_time)->format('H:i:s') : '-');
            $sheet->setCellValue('G' . $row, $attendance->clock_out ? $attendance->clock_out->format('H:i:s') : '-');
            $sheet->setCellValue('H' . $row, $outTimeDiff);
            $row++;
        }

        // Set column widths
        $sheet->getColumnDimension('A')->setWidth(15);
        $sheet->getColumnDimension('B')->setWidth(20);
        $sheet->getColumnDimension('C')->setWidth(15);
        $sheet->getColumnDimension('D')->setWidth(15);
        $sheet->getColumnDimension('E')->setWidth(20);
        $sheet->getColumnDimension('F')->setWidth(15);
        $sheet->getColumnDimension('G')->setWidth(15);
        $sheet->getColumnDimension('H')->setWidth(20);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $fileName = 'laporan_absensi_' . $employee->full_name . '_' . date('Y-m-d') . '.xlsx';

        ob_clean(); // Clean any previous output
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $fileName . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }

    private function exportToPdf($attendances, $employee)
    {
        // Pre-compute all data to avoid calling private methods from view
        $processedAttendances = $attendances->map(function($attendance) {
            $schedule = self::getSchedule($attendance);

            return [
                'date' => $attendance->date ? $attendance->date->format('d/m/Y') : '-',
                'shift_name' => $schedule ? $schedule->shift_name : 'N/A',
                'schedule_start' => $schedule ? Carbon::parse($schedule->start_time)->format('H:i:s') : '-',
                'schedule_end' => $schedule ? Carbon::parse($schedule->end_time)->format('H:i:s') : '-',
                'clock_in' => $attendance->clock_in ? $attendance->clock_in->format('H:i:s') : '-',
                'clock_out' => $attendance->clock_out ? $attendance->clock_out->format('H:i:s') : '-',
                'in_diff' => self::getInTimeDifference($attendance),
                'out_diff' => self::getOutTimeDifference($attendance),
            ];
        });

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('employee.report-export-pdf', [
            'attendances' => $processedAttendances,
            'employee' => $employee
        ])->setPaper('a4', 'portrait')
          ->setOption('isRemoteEnabled', false)
          ->setOption('isFontSubsettingEnabled', false);

        $fileName = 'laporan_absensi_' . $employee->full_name . '_' . date('Y-m-d') . '.pdf';
        return $pdf->download($fileName);
    }

    private static function getShiftName($attendance)
    {
        $schedule = self::getSchedule($attendance);
        return $schedule ? $schedule->shift_name : 'N/A';
    }

    private static function getSchedule($attendance)
    {
        // Try to get shift from employee_shifts table
        $employeeShift = \App\Models\EmployeeShift::where('employee_id', $attendance->attendable_id)
            ->where('date', $attendance->date)
            ->with('schedule')
            ->first();

        if ($employeeShift && $employeeShift->schedule) {
            return $employeeShift->schedule;
        }

        // Try to get schedule by day of week
        $dayOfWeek = $attendance->date->dayOfWeek;
        return Schedule::where('type', 'pegawai')
            ->where('day_of_week', $dayOfWeek)
            ->first();
    }

    private static function getInTimeDifference($attendance)
    {
        return AttendanceHelper::formatCheckInDifference($attendance);
    }

    private static function getOutTimeDifference($attendance)
    {
        return AttendanceHelper::formatCheckOutDifference($attendance);
    }

    private function processSelfie($base64Image, $type, $id, $direction)
    {
        // Verify the image is a valid base64 encoded image
        if (!preg_match('/^data:image\/(?:jpeg|png|jpg);base64,[A-Za-z0-9+\/=]+$/',$base64Image)) {
            throw new \InvalidArgumentException('Invalid image format');
        }

        // Remove data URI prefix
        $image = preg_replace('/^data:image\/(?:jpeg|png|jpg);base64,/', '', $base64Image);
        $image = str_replace(' ', '+', $image);

        // Verify base64 is valid
        if (!base64_decode($image, true)) {
            throw new \InvalidArgumentException('Invalid base64 image data');
        }

        $imageData = base64_decode($image);

        // Validate image content by attempting to create image instance
        try {
            $img = Image::read($imageData);
        } catch (\Exception $e) {
            throw new \InvalidArgumentException('Invalid image file');
        }

        // Verify image dimensions are reasonable
        $width = $img->width();
        $height = $img->height();
        if ($width > 5000 || $height > 5000) {
            throw new \InvalidArgumentException('Image dimensions are too large');
        }

        // Create filename with security in mind
        $filename = $type . '_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $id) . '_' . $direction . '_' . time() . '.jpg';
        $directory = 'selfies/' . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $type);

        // Validate directory path to prevent directory traversal
        if (strpos($directory, '..') !== false || strpos($directory, '/') === 0) {
            throw new \InvalidArgumentException('Invalid directory path');
        }

        // Detect environment and use appropriate storage path
        // Production: public/storage/selfies (without symlink, inside web root)
        // Development: storage/app/public/selfies (with symlink)
        $isProduction = config('app.env') === 'production';

        if ($isProduction) {
            // Production: Save to public/storage/selfies (inside web root, no symlink needed)
            // Create folder if not exists
            if (!file_exists(public_path('storage'))) {
                @mkdir(public_path('storage'), 0755, true);
                @mkdir(public_path('storage/selfies/employee'), 0755, true);
                @mkdir(public_path('storage/selfies/student'), 0755, true);
            }

            $path = public_path('storage/' . $directory . '/' . $filename);

            // Create directory if not exists
            $directoryPath = public_path('storage/' . $directory);
            if (!file_exists($directoryPath)) {
                mkdir($directoryPath, 0755, true);
            }
        } else {
            // Local development: Use storage with symlink
            Storage::disk('public')->makeDirectory($directory);
            $path = storage_path('app/public/' . $directory . '/' . $filename);
        }

        // Sanitize filename to prevent directory traversal
        $path = str_replace(["../", "./", "..\\", ".\\"], '', $path);

        // Verify the final path is within the allowed directory
        $allowedPath = $isProduction ? public_path('storage') : storage_path('app/public/');
        $realPath = realpath(dirname($path)) ?: dirname($path);
        
        // Normalize path separators to ensure consistent comparison (use forward slashes)
        $normalizedAllowedPath = str_replace('\\', '/', $allowedPath);
        $normalizedRealPath = str_replace('\\', '/', $realPath);
        
        // Check if the path starts with the allowed path
        // strpos returns 0 when the needle is found at the beginning of the haystack
        // So we want to check if it IS 0, meaning the path starts with the allowed path
        if (strpos($normalizedRealPath, $normalizedAllowedPath) !== 0) {
            // For debugging, log the paths
            \Log::debug('Path validation failed', [
                'allowed_path' => $allowedPath,
                'real_path' => $realPath,
                'normalized_allowed' => $normalizedAllowedPath,
                'normalized_real' => $normalizedRealPath,
                'position' => strpos($normalizedRealPath, $normalizedAllowedPath)
            ]);
            throw new \InvalidArgumentException('Invalid file path');
        }

        // Resize image to max width of 800px while maintaining aspect ratio
        $img->scaleDown(width: 800);

        // Start with high quality (85) and decrease until file size is under 500KB
        $maxFileSize = 500 * 1024; // 500KB in bytes
        $quality = 85;
        $compressed = false;

        // Try different quality levels until we get under the target size or reach minimum quality
        while ($quality >= 30) {
            // Save with current quality
            $img->toJpeg($quality)->save($path);

            // Check the file size
            $fileSize = filesize($path);

            if ($fileSize <= $maxFileSize) {
                $compressed = true;
                break;
            }

            // Reduce quality for next attempt
            $quality -= 5;
        }

        // If still over size limit after reducing quality, try further resizing
        if (!$compressed) {
            // Calculate new dimensions that should get us under the size limit
            $img->scaleDown(width: 600);

            // Try again with reduced size and quality
            $quality = 75;
            while ($quality >= 20) {
                $img->toJpeg($quality)->save($path);
                $fileSize = filesize($path);

                if ($fileSize <= $maxFileSize) {
                    $compressed = true;
                    break;
                }

                $quality -= 5;
            }
        }

        // If still over size limit, just save with minimum quality
        if (!$compressed) {
            $img->toJpeg(20)->save($path);
        }

        return $directory . '/' . $filename;
    }
    
    /**
     * Validate if the provided location is within the geofence
     */
    private function validateGeofence($latitude, $longitude, $accuracy)
    {
        // Get geofence settings
        $geofenceCenterLat = LocationSetting::where('setting_key', 'geofence_center_lat')->first();
        $geofenceCenterLng = LocationSetting::where('setting_key', 'geofence_center_lng')->first();
        $geofenceRadius = LocationSetting::where('setting_key', 'geofence_radius')->first();

        // If no geofence settings exist, allow location
        if (!$geofenceCenterLat || !$geofenceCenterLng || !$geofenceRadius) {
            \Log::debug('No geofence settings found, allowing location');
            return true;
        }

        $centerLat = floatval($geofenceCenterLat->setting_value);
        $centerLng = floatval($geofenceCenterLng->setting_value);
        $radius = floatval($geofenceRadius->setting_value);

        // Calculate distance between current location and geofence center
        $distance = $this->calculateDistance($latitude, $longitude, $centerLat, $centerLng);

        // Enhanced validation with more flexible accuracy handling:
        // 1. If user is within the strict radius, always allow
        $withinStrictRadius = $distance <= $radius;
        
        if ($withinStrictRadius) {
            \Log::debug('Location within strict radius, allowing', [
                'user_lat' => $latitude,
                'user_lng' => $longitude,
                'center_lat' => $centerLat,
                'center_lng' => $centerLng,
                'radius' => $radius,
                'distance' => $distance
            ]);
            return true;
        }

        // For users outside strict radius, apply more lenient accuracy handling
        // Ensure accuracy is reasonable but be more forgiving (max 200 meters instead of 100)
        $safeAccuracy = min(floatval($accuracy), 200.0);

        // Calculate max allowed distance with more generous accuracy buffer
        // Allow up to 75% of radius as accuracy buffer for better tolerance
        $maxAllowedDistance = $radius + min($safeAccuracy, $radius * 0.75);

        // Log for debugging purposes
        \Log::debug('Geofence validation - Extended check', [
            'user_lat' => $latitude,
            'user_lng' => $longitude,
            'center_lat' => $centerLat,
            'center_lng' => $centerLng,
            'radius' => $radius,
            'accuracy' => $accuracy,
            'safe_accuracy' => $safeAccuracy,
            'distance' => $distance,
            'max_allowed_distance' => $maxAllowedDistance,
            'within_strict_radius' => $withinStrictRadius
        ]);

        $result = $distance <= $maxAllowedDistance;
        
        if (!$result) {
            \Log::warning('Location outside geofence', [
                'user_lat' => $latitude,
                'user_lng' => $longitude,
                'center_lat' => $centerLat,
                'center_lng' => $centerLng,
                'radius' => $radius,
                'distance' => $distance,
                'max_allowed_distance' => $maxAllowedDistance
            ]);
        }
        
        return $result;
    }

    /**
     * Calculate distance between two coordinates using Haversine formula
     */
    private function calculateDistance($lat1, $lng1, $lat2, $lng2)
    {
        $earthRadius = 6371000; // Earth's radius in meters

        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($lng1);
        $latTo = deg2rad($lat2);
        $lonTo = deg2rad($lng2);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));

        return $angle * $earthRadius;
    }
    
    /**
     * Test method for geofence validation (for debugging purposes)
     */
    public function testGeofence()
    {
        // This is a temporary test method for geofence validation
        // It will be removed in production
        
        $testCases = [
            [
                'name' => 'Within strict radius',
                'lat' => -7.99478,
                'lng' => 112.67384,
                'accuracy' => 10
            ],
            [
                'name' => 'Slightly outside strict radius with low accuracy',
                'lat' => -7.99578,
                'lng' => 112.67383,
                'accuracy' => 10
            ],
            [
                'name' => 'Slightly outside strict radius with high accuracy',
                'lat' => -7.99578,
                'lng' => 112.67383,
                'accuracy' => 50
            ],
            [
                'name' => 'Significantly outside',
                'lat' => -7.99678,
                'lng' => 112.67383,
                'accuracy' => 10
            ]
        ];
        
        $results = [];
        
        foreach ($testCases as $case) {
            $result = $this->validateGeofence($case['lat'], $case['lng'], $case['accuracy']);
            
            // Calculate actual distance for reporting
            $geofenceCenterLat = \App\Models\LocationSetting::where('setting_key', 'geofence_center_lat')->first();
            $geofenceCenterLng = \App\Models\LocationSetting::where('setting_key', 'geofence_center_lng')->first();
            $geofenceRadius = \App\Models\LocationSetting::where('setting_key', 'geofence_radius')->first();
            
            $distance = 0;
            if ($geofenceCenterLat && $geofenceCenterLng) {
                $distance = $this->calculateDistance(
                    $case['lat'], 
                    $case['lng'], 
                    floatval($geofenceCenterLat->setting_value),
                    floatval($geofenceCenterLng->setting_value)
                );
            }
            
            $results[] = [
                'name' => $case['name'],
                'coordinates' => $case['lat'] . ', ' . $case['lng'],
                'accuracy' => $case['accuracy'],
                'distance' => round($distance, 2),
                'accepted' => $result ? '✅ ACCEPTED' : '❌ REJECTED'
            ];
        }
        
        return response()->json($results);
    }
}