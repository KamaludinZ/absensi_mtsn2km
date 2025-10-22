<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\Schedule;
use App\Models\EmployeeShift;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class EmployeeShiftController extends Controller
{
    public function index()
    {
        // Get current month and year
        $month = request('month', now()->month);
        $year = request('year', now()->year);
        
        // Get all active employees
        $employees = Employee::where('is_active', true)->orderBy('full_name')->get();
        
        // Get all schedules
        $schedules = Schedule::where('type', 'pegawai')->orderBy('shift_type')->orderBy('day_of_week')->get();
        
        // Generate dates for the selected month
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();
        $dates = [];
        $currentDate = $startDate->copy();
        
        while ($currentDate <= $endDate) {
            $dates[] = $currentDate->copy();
            $currentDate->addDay();
        }
        
        // Get existing employee shifts for this month
        $employeeShifts = EmployeeShift::whereBetween('date', [$startDate, $endDate])
            ->where('is_active', true)
            ->with('schedule') // Eager load schedule relationship
            ->get()
            ->groupBy('employee_id');

        // Debug logging
        Log::info('Employee shifts loaded', [
            'month' => $month,
            'year' => $year,
            'total_shifts' => $employeeShifts->flatten()->count(),
            'employees_with_shifts' => $employeeShifts->keys()->toArray(),
            'sample_shift' => $employeeShifts->flatten()->first() ? [
                'employee_id' => $employeeShifts->flatten()->first()->employee_id,
                'schedule_id' => $employeeShifts->flatten()->first()->schedule_id,
                'date' => $employeeShifts->flatten()->first()->date,
                'date_formatted' => $employeeShifts->flatten()->first()->date->format('Y-m-d'),
            ] : null
        ]);

        // Get disabled dates for this month from session
        $disabledDates = collect(session("disabled_dates_{$year}_{$month}", []));

        return view('admin.employee-shifts.index', compact('employees', 'schedules', 'dates', 'employeeShifts', 'month', 'year', 'disabledDates'));
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:2030',
            'disabled_dates' => 'array',
            'disabled_dates.*' => 'date_format:Y-m-d',
        ]);
        
        $month = $request->month;
        $year = $request->year;
        $disabledDates = collect($request->disabled_dates ?? []);
        
        // Store disabled dates in session
        session(["disabled_dates_{$year}_{$month}" => $disabledDates->toArray()]);
        
        // Get all active employees
        $employees = Employee::where('is_active', true)->pluck('id');
        
        // Delete existing shifts for this month
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();
        
        EmployeeShift::whereBetween('date', [$startDate, $endDate])->delete();
        
        // Save new shifts
        $savedCount = 0;
        foreach ($employees as $employeeId) {
            for ($day = 1; $day <= $endDate->day; $day++) {
                $date = Carbon::create($year, $month, $day);
                $dateString = $date->format('Y-m-d');

                // Skip disabled dates
                if ($disabledDates->contains($dateString)) {
                    continue;
                }

                $shiftKey = "shift_{$employeeId}_{$day}";
                if ($request->has($shiftKey) && $request->input($shiftKey)) {
                    EmployeeShift::create([
                        'employee_id' => $employeeId,
                        'schedule_id' => $request->input($shiftKey),
                        'date' => $date,
                        'is_active' => true,
                    ]);
                    $savedCount++;
                }
            }
        }

        Log::info('Employee shifts saved from form', [
            'month' => $month,
            'year' => $year,
            'saved_count' => $savedCount,
            'disabled_dates_count' => $disabledDates->count()
        ]);

        // Apply shift changes to attendance records
        $this->applyShiftsToAttendance($month, $year);

        return redirect()->back()->with('success', "Pengaturan shift pegawai berhasil disimpan ({$savedCount} shift) dan diterapkan ke absensi.");
    }
    
    public function getShiftsByDate(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
        ]);

        $date = Carbon::parse($request->date);
        $dateString = $date->format('Y-m-d');

        $shifts = EmployeeShift::where('date', '>=', $dateString . ' 00:00:00')
            ->where('date', '<=', $dateString . ' 23:59:59')
            ->where('is_active', true)
            ->with(['employee', 'schedule'])
            ->get();

        return response()->json($shifts);
    }

    public function export(Request $request)
    {
        $month = $request->get('month', now()->month);
        $year = $request->get('year', now()->year);

        // Get all active employees
        $employees = Employee::where('is_active', true)->orderBy('full_name')->get();

        // Generate dates for the selected month
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();
        $dates = [];
        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            $dates[] = $currentDate->copy();
            $currentDate->addDay();
        }

        // Get existing employee shifts for this month
        $employeeShifts = EmployeeShift::whereBetween('date', [$startDate, $endDate])
            ->where('is_active', true)
            ->with('schedule')
            ->get()
            ->groupBy('employee_id');

        // Create new Spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set headers
        $sheet->setCellValue('A1', 'Nama Pegawai');
        $sheet->setCellValue('B1', 'Jabatan'); // Add position column
        $colIndex = 3; // Start from column C (index 3) for dates
        foreach ($dates as $date) {
            $columnLetter = Coordinate::stringFromColumnIndex($colIndex);
            $sheet->setCellValue($columnLetter . '1', $date->format('d'));
            $sheet->setCellValue($columnLetter . '2', $date->translatedFormat('l'));
            $colIndex++;
        }

        // Fill data
        $row = 3;
        foreach ($employees as $employee) {
            $sheet->setCellValue('A' . $row, $employee->full_name);
            $sheet->setCellValue('B' . $row, $employee->position ?? '-'); // Add position in column B
            $colIndex = 3; // Start from column C

            foreach ($dates as $date) {
                $shiftName = '';

                if (isset($employeeShifts[$employee->id])) {
                    $shift = $employeeShifts[$employee->id]->first(function($shift) use ($date) {
                        return $shift->date->format('Y-m-d') === $date->format('Y-m-d');
                    });

                    if ($shift && $shift->schedule) {
                        if ($shift->schedule->shift_name) {
                            $shiftName = $shift->schedule->shift_name;
                        } elseif ($shift->schedule->shift_type) {
                            $shiftName = ucfirst($shift->schedule->shift_type);
                        } else {
                            $shiftName = $shift->schedule->start_time . '-' . $shift->schedule->end_time;
                        }
                    }
                }

                $columnLetter = Coordinate::stringFromColumnIndex($colIndex);
                $sheet->setCellValue($columnLetter . $row, $shiftName);
                $colIndex++;
            }

            $row++;
        }

        // Auto size columns
        for ($i = 1; $i < $colIndex; $i++) {
            $sheet->getColumnDimensionByColumn($i)->setAutoSize(true);
        }

        // Save file
        $writer = new Xlsx($spreadsheet);
        $fileName = 'shift_pegawai_' . $year . '_' . str_pad($month, 2, '0', STR_PAD_LEFT) . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), $fileName);

        $writer->save($tempFile);

        return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls',
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:2030',
        ]);

        $month = $request->month;
        $year = $request->year;

        try {
            // Load the uploaded file
            $file = $request->file('file');
            $spreadsheet = IOFactory::load($file->getPathname());
            $sheet = $spreadsheet->getActiveSheet();

            Log::info('Employee shifts import started', [
                'month' => $month,
                'year' => $year,
                'file_name' => $file->getClientOriginalName()
            ]);

            // Get all active employees indexed by name (trimmed and lowercase for better matching)
            $employees = Employee::where('is_active', true)->get();
            $employeeMap = [];
            foreach ($employees as $employee) {
                // Create case-insensitive, whitespace-trimmed mapping
                $key = strtolower(trim($employee->full_name));
                $employeeMap[$key] = $employee;
            }

            // Get all valid schedules and extract valid shift names
            $schedules = Schedule::where('type', 'pegawai')->get();
            $validShiftNames = [];
            
            foreach ($schedules as $schedule) {
                $shiftName = '';
                if ($schedule->shift_name) {
                    $shiftName = $schedule->shift_name;
                } elseif ($schedule->shift_type) {
                    $shiftName = ucfirst($schedule->shift_type);
                } else {
                    $shiftName = $schedule->start_time . '-' . $schedule->end_time;
                }
                
                $validShiftNames[] = $shiftName;
            }
            
            // Generate dates for the selected month
            $startDate = Carbon::create($year, $month, 1)->startOfMonth();
            $endDate = Carbon::create($year, $month, 1)->endOfMonth();
            $dates = [];
            $currentDate = $startDate->copy();

            while ($currentDate <= $endDate) {
                $dates[] = $currentDate->copy();
                $currentDate->addDay();
            }

            // Create the schedule map for validation
            $scheduleMap = [];
            foreach ($schedules as $schedule) {
                $shiftName = '';
                if ($schedule->shift_name) {
                    $shiftName = $schedule->shift_name;
                } elseif ($schedule->shift_type) {
                    $shiftName = ucfirst($schedule->shift_type);
                } else {
                    $shiftName = $schedule->start_time . '-' . $schedule->end_time;
                }

                // Store with trimmed key for better matching
                $scheduleMap[trim($shiftName)] = $schedule;
            }
            
            // First, let's create a mapping of Excel columns to actual dates by reading the header row
            // Column structure from export: A=Nama Pegawai, B=Jabatan, C onwards=Dates
            $dateColumns = [];
            $colIndex = 3; // Start from column C (index 3) - columns A and B are Nama Pegawai and Jabatan

            // Determine the number of columns by checking the header row
            // We'll check each column until we find an empty cell in row 1 (day numbers)
            while (true) {
                $columnLetter = Coordinate::stringFromColumnIndex($colIndex);
                $excelDayValue = $sheet->getCell($columnLetter . '1')->getValue();
                if ($excelDayValue === null || $excelDayValue === '') break; // Stop if column is empty

                // Check if the value looks like a day number (is numeric and between 1-31)
                $dayNumber = is_numeric($excelDayValue) ? (int)$excelDayValue : null;
                if ($dayNumber !== null && $dayNumber >= 1 && $dayNumber <= 31) {
                    // Find if this day exists in our expected dates for the month
                    foreach ($dates as $date) {
                        if ($date->day == $dayNumber) {
                            $dateColumns[$colIndex] = $date;
                            break;
                        }
                    }
                }
                $colIndex++;

                // Safety check to prevent infinite loop
                if ($colIndex > 100) break;
            }

            Log::info('Date columns detected', [
                'count' => count($dateColumns),
                'columns' => array_keys($dateColumns)
            ]);

            // Parse Excel data with validation
            $importedData = [];
            $row = 3; // Start from row 3 (after headers)
            $invalidShifts = [];
            $skippedEmployees = [];

            while ($sheet->getCell('A' . $row)->getValue()) {
                $employeeNameRaw = $sheet->getCell('A' . $row)->getValue();
                $employeeName = strtolower(trim($employeeNameRaw));

                if (isset($employeeMap[$employeeName])) {
                    $employee = $employeeMap[$employeeName];

                    // Loop through all date columns that exist in the Excel file
                    foreach ($dateColumns as $colIndex => $date) {
                        $columnLetter = Coordinate::stringFromColumnIndex($colIndex);
                        $shiftNameRaw = $sheet->getCell($columnLetter . $row)->getValue();
                        $shiftName = trim($shiftNameRaw);

                        if ($shiftName && !isset($scheduleMap[$shiftName])) {
                            // Invalid shift name found, collect the error but continue to check other cells
                            $invalidShifts[] = [
                                'employee' => $employeeNameRaw,
                                'date' => $date->format('Y-m-d'),
                                'shift' => $shiftName
                            ];
                        } elseif ($shiftName && isset($scheduleMap[$shiftName])) {
                            // Valid shift found, add to import data
                            $importedData[] = [
                                'employee_id' => $employee->id,
                                'schedule_id' => $scheduleMap[$shiftName]->id,
                                'date' => $date->format('Y-m-d'),
                                'shift_name' => $shiftName,
                            ];
                        }
                        // Note: Empty cells are ignored (not added to import data)
                    }
                } else {
                    // Track employees that couldn't be found
                    if (!in_array($employeeNameRaw, $skippedEmployees)) {
                        $skippedEmployees[] = $employeeNameRaw;
                    }
                }

                $row++;
            }

            Log::info('Excel data parsed', [
                'total_rows_processed' => $row - 3,
                'valid_records' => count($importedData),
                'invalid_shifts' => count($invalidShifts),
                'skipped_employees' => count($skippedEmployees)
            ]);

            // Check for errors before importing
            $errors = [];

            // If there are any invalid shift names, collect them
            if (!empty($invalidShifts)) {
                $errorDetails = [];
                foreach ($invalidShifts as $invalid) {
                    $errorDetails[] = "Pegawai: {$invalid['employee']}, Tanggal: {$invalid['date']}, Shift: '{$invalid['shift']}'";
                }
                $errors[] = 'Terdapat nama shift yang tidak valid. Harap gunakan nama shift yang sudah terdaftar di /admin/schedules/pegawai. Detail: ' . implode(' | ', array_slice($errorDetails, 0, 5)) . (count($errorDetails) > 5 ? ' ... dan ' . (count($errorDetails) - 5) . ' lainnya' : '');
            }

            // If there are employees that couldn't be found
            if (!empty($skippedEmployees)) {
                $errors[] = 'Pegawai berikut tidak ditemukan atau tidak aktif: ' . implode(', ', array_slice($skippedEmployees, 0, 10)) . (count($skippedEmployees) > 10 ? ' ... dan ' . (count($skippedEmployees) - 10) . ' lainnya' : '');
            }

            // If no data was imported
            if (empty($importedData) && empty($errors)) {
                $errors[] = 'Tidak ada data valid yang dapat diimport. Pastikan format Excel sesuai dengan template export dan berisi data shift.';
            }

            // Return with errors if any
            if (!empty($errors)) {
                return redirect()->back()
                    ->with('error', 'Import gagal: ' . implode(' | ', $errors))
                    ->withInput();
            }

            // Delete existing shifts for this month
            $deletedCount = EmployeeShift::whereBetween('date', [$startDate, $endDate])->delete();

            Log::info('Deleted existing shifts', [
                'deleted_count' => $deletedCount,
                'date_range' => [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]
            ]);

            // Insert imported data
            $insertedCount = 0;
            foreach ($importedData as $data) {
                try {
                    EmployeeShift::create([
                        'employee_id' => $data['employee_id'],
                        'schedule_id' => $data['schedule_id'],
                        'date' => $data['date'],
                        'is_active' => true,
                    ]);
                    $insertedCount++;
                } catch (\Exception $e) {
                    Log::error('Failed to insert shift', [
                        'data' => $data,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            Log::info('Employee shifts imported', [
                'month' => $month,
                'year' => $year,
                'expected_count' => count($importedData),
                'inserted_count' => $insertedCount
            ]);

            // Verify data was saved
            $verifyCount = EmployeeShift::whereBetween('date', [$startDate, $endDate])->count();
            Log::info('Verification after import', [
                'count_in_db' => $verifyCount
            ]);

            // Apply shift changes to attendance records immediately after import
            // This ensures data is saved and applied, user can still edit if needed
            $this->applyShiftsToAttendance($month, $year);

            return redirect()->route('admin.employee-shifts.index', ['month' => $month, 'year' => $year])
                ->with('success', 'Data shift berhasil diimport (' . $insertedCount . ' record) dan diterapkan ke absensi pegawai. Silakan refresh halaman jika data belum tampil.');
        } catch (\Exception $e) {
            Log::error('Error importing employee shifts: ' . $e->getMessage(), [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return redirect()->back()
                ->with('error', 'Terjadi kesalahan saat mengimport data: ' . $e->getMessage() . ' (Lihat log untuk detail lengkap)')
                ->withInput();
        }
    }

    private function applyShiftsToAttendance($month, $year)
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();

        // Get all employees for this month
        $employees = Employee::where('is_active', true)->pluck('id');

        // Delete existing attendance records for this month for employees
        Attendance::whereBetween('date', [$startDate, $endDate])
            ->where('attendable_type', Employee::class)
            ->whereIn('attendable_id', $employees->toArray())
            ->delete();

        // Get all shifts for this month
        $shifts = EmployeeShift::whereBetween('date', [$startDate, $endDate])
            ->where('is_active', true)
            ->with('schedule')
            ->get();

        foreach ($shifts as $shift) {
            if ($shift->schedule) { // Check if schedule exists
                // Create attendance record only for days with assigned shifts
                Attendance::create(
                    [
                        'attendable_type' => Employee::class,
                        'attendable_id' => $shift->employee_id,
                        'date' => $shift->date,
                        'shift_name' => $shift->schedule->shift_name,
                        'shift_type' => $shift->schedule->shift_type,
                        'scheduled_start_time' => $shift->schedule->start_time,
                        'scheduled_end_time' => $shift->schedule->end_time,
                    ]
                );
            }
        }
    }
}