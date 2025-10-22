<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Schedule;
use Carbon\Carbon;
use Intervention\Image\Laravel\Facades\Image;
use Illuminate\Support\Facades\Storage;

class AttendanceController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $employee = $user->employee;

        if (!$employee) {
            return redirect()->back()->with('error', 'Data pegawai tidak ditemukan.');
        }

        $today = Carbon::today();
        $attendance = Attendance::where('attendable_type', Employee::class)
            ->where('attendable_id', $employee->id)
            ->whereDate('date', $today)
            ->first();

        return view('employee.attendance', compact('employee', 'attendance'));
    }

    public function clockIn(Request $request)
    {
        try {
            $request->validate([
                'selfie' => [
                    'required',
                    'string',
                    'regex:/^data:image\/(?:jpeg|png|jpg);base64,[A-Za-z0-9+\/=]+$/',
                    'max:2000000' // 2MB max file size
                ],
            ]);

            $employee = auth()->user()->employee;

            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data pegawai tidak ditemukan.',
                ]);
            }

            // Check if already clocked in today
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

            // Process selfie
            $selfiePath = $this->processSelfie($request->selfie, 'employee', $employee->id, 'in');

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
            \Log::error('Employee clock-in error: ' . $e->getMessage(), [
                'user_id' => auth()->id() ?? null,
                'employee_id' => auth()->user()->employee->id ?? null,
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
            $request->validate([
                'selfie' => [
                    'required',
                    'string',
                    'regex:/^data:image\/(?:jpeg|png|jpg);base64,[A-Za-z0-9+\/=]+$/',
                    'max:2000000' // 2MB max file size
                ],
            ]);

            $employee = auth()->user()->employee;

            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data pegawai tidak ditemukan.',
                ]);
            }

            // Check attendance
            $today = Carbon::today();
            $attendance = Attendance::where('attendable_type', Employee::class)
                ->where('attendable_id', $employee->id)
                ->whereDate('date', $today)
                ->first();

            if (!$attendance || !$attendance->clock_in) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda belum melakukan absen masuk hari ini.',
                ]);
            }

            if ($attendance->clock_out) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda sudah melakukan absen keluar hari ini.',
                ]);
            }

            // Process selfie
            $selfiePath = $this->processSelfie($request->selfie, 'employee', $employee->id, 'out');

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
            
            $attendance->update([
                'clock_out' => $now,
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
            \Log::error('Employee clock-out error: ' . $e->getMessage(), [
                'user_id' => auth()->id() ?? null,
                'employee_id' => auth()->user()->employee->id ?? null,
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
        $employee = auth()->user()->employee;

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
        $month = $request->filled('month') ? $request->month : now()->month;
        $query->whereMonth('date', $month);

        $attendances = $query->orderBy('date', 'desc')
            ->with(['attendable']) // Load employee relationship
            ->paginate(20);

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

        $schedules = \App\Models\Schedule::where('type', 'pegawai')
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

        return view('employee.report', compact('attendances', 'months', 'academicYears', 'selectedAcademicYearId', 'employeeShifts', 'schedules'));
    }

    public function export(Request $request)
    {
        $employee = auth()->user()->employee;

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
            $sheet->setCellValue('C' . $row, $schedule ? \Carbon\Carbon::parse($schedule->start_time)->format('H:i:s') : '-');
            $sheet->setCellValue('D' . $row, $attendance->clock_in ? $attendance->clock_in->format('H:i:s') : '-');
            $sheet->setCellValue('E' . $row, $inTimeDiff);
            $sheet->setCellValue('F' . $row, $schedule ? \Carbon\Carbon::parse($schedule->end_time)->format('H:i:s') : '-');
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
                'schedule_start' => $schedule ? \Carbon\Carbon::parse($schedule->start_time)->format('H:i:s') : '-',
                'schedule_end' => $schedule ? \Carbon\Carbon::parse($schedule->end_time)->format('H:i:s') : '-',
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
        return \App\Models\Schedule::where('type', 'pegawai')
            ->where('day_of_week', $dayOfWeek)
            ->first();
    }

    private static function getInTimeDifference($attendance)
    {
        $schedule = self::getSchedule($attendance);

        if ($attendance->clock_in && $schedule) {
            // Ambil waktu ABSENSI MASUK dalam format H:i:s
            $absensiMasukTime = \Carbon\Carbon::parse($attendance->clock_in)->format('H:i:s');
            list($h1, $i1, $s1) = explode(':', $absensiMasukTime);
            $absensiMasukDetik = ($h1 * 3600) + ($i1 * 60) + $s1;

            // Ambil waktu JAM MASUK dalam format H:i:s
            $jamMasukTime = \Carbon\Carbon::parse($schedule->start_time)->format('H:i:s');
            list($h2, $i2, $s2) = explode(':', $jamMasukTime);
            $jamMasukDetik = ($h2 * 3600) + ($i2 * 60) + $s2;

            // Hitung selisih: ABSENSI MASUK - JAM MASUK
            $selisihDetik = $absensiMasukDetik - $jamMasukDetik;

            // Convert ke format H:i:s
            $selisihAbs = abs($selisihDetik);

            $jam = floor($selisihAbs / 3600);
            $sisaSetelahJam = $selisihAbs - ($jam * 3600);
            $menit = floor($sisaSetelahJam / 60);
            $detik = $sisaSetelahJam - ($menit * 60);

            $formatWaktu = sprintf('%02d:%02d:%02d', $jam, $menit, $detik);

            if ($selisihDetik > 0) {
                // Absensi masuk > jam masuk (terlambat)
                return '+' . $formatWaktu;
            } elseif ($selisihDetik < 0) {
                // Absensi masuk < jam masuk (lebih awal)
                return '-' . $formatWaktu;
            } else {
                // Tepat waktu
                return '00:00:00';
            }
        }

        return '-';
    }

    private static function getOutTimeDifference($attendance)
    {
        $schedule = self::getSchedule($attendance);

        if ($attendance->clock_out && $schedule) {
            // Ambil waktu ABSENSI PULANG dalam format H:i:s
            $absensiPulangTime = \Carbon\Carbon::parse($attendance->clock_out)->format('H:i:s');
            list($h1, $i1, $s1) = explode(':', $absensiPulangTime);
            $absensiPulangDetik = ($h1 * 3600) + ($i1 * 60) + $s1;

            // Ambil waktu JAM PULANG dalam format H:i:s
            $jamPulangTime = \Carbon\Carbon::parse($schedule->end_time)->format('H:i:s');
            list($h2, $i2, $s2) = explode(':', $jamPulangTime);
            $jamPulangDetik = ($h2 * 3600) + ($i2 * 60) + $s2;

            // Hitung selisih: ABSENSI PULANG - JAM PULANG
            $selisihDetik = $absensiPulangDetik - $jamPulangDetik;

            // Convert ke format H:i:s
            $selisihAbs = abs($selisihDetik);

            $jam = floor($selisihAbs / 3600);
            $sisaSetelahJam = $selisihAbs - ($jam * 3600);
            $menit = floor($sisaSetelahJam / 60);
            $detik = $sisaSetelahJam - ($menit * 60);

            $formatWaktu = sprintf('%02d:%02d:%02d', $jam, $menit, $detik);

            if ($selisihDetik < 0) {
                // Absensi pulang < Jam pulang = Pulang Awal
                return '-' . $formatWaktu;
            } elseif ($selisihDetik > 0) {
                // Absensi pulang > Jam pulang = Pulang Lebih Lama
                return '+' . $formatWaktu;
            } else {
                // Tepat waktu
                return '00:00:00';
            }
        }

        return '-';
    }

    private function processSelfie($base64Image, $type, $id, $direction)
    {
        // Verify the image is a valid base64 encoded image
        if (!preg_match('/^data:image\/(?:jpeg|png|jpg);base64,[A-Za-z0-9+\/=]+$/', $base64Image)) {
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

        // Create directory if not exists
        Storage::disk('public')->makeDirectory($directory);

        $path = storage_path('app/public/' . $directory . '/' . $filename);

        // Sanitize filename to prevent directory traversal
        $path = str_replace(['../', './', '..\\', '.\\'], '', $path);

        // Verify the final path is within the allowed storage directory
        $allowedPath = storage_path('app/public/');
        if (strpos(realpath($path) ?: $path, $allowedPath) !== 0) {
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
}