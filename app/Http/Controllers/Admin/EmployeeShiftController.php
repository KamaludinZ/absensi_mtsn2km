<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\Schedule;
use App\Models\EmployeeShift;
use App\Models\Attendance;
use Carbon\Carbon;
use App\Services\EmployeeShiftAutoFill;
use Illuminate\Support\Facades\DB;
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

        // Get employees who were active during this month or before
        // Use soft deletes to determine if employee was inactive during this month
        $employees = Employee::where(function($query) use ($month, $year) {
            // Currently active employees (not soft deleted)
            $query->whereNull('deleted_at')
                  // OR employees who were soft deleted after this month (were active during this month)
                  ->orWhere(function($q) use ($month, $year) {
                      $q->whereNotNull('deleted_at')
                        ->where('deleted_at', '>=', Carbon::create($year, $month, 1)->startOfMonth());
                  });
        })->orderBy('full_name')->get();

        // Only named shifts can be assigned (only they count as active days)
        $schedules = $this->namedSchedules();

        // Generate dates for the selected month
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();
        $dates = [];
        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            $dates[] = $currentDate->copy();
            $currentDate->addDay();
        }

        // Fill today..end of month from the shifts' Hari & Jabatan Penerapan
        // (only dates not handled before; assigned cells are never replaced)
        $hasAutoRules = EmployeeShiftAutoFill::hasRules();
        if ($hasAutoRules) {
            EmployeeShiftAutoFill::fillPending((int) $year, (int) $month);
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

        // Dates closed by the calendar: /admin/holidays/active-days and /admin/holidays
        $offDays = EmployeeShiftAutoFill::offDays($startDate, $endDate);

        // Assignments to shifts without a name (from before names were required)
        $namelessCount = $employeeShifts->flatten()->filter(fn ($shift) => $shift->schedule && !trim((string) $shift->schedule->shift_name))->count();

        return view('admin.employee-shifts.index', compact('employees', 'schedules', 'dates', 'employeeShifts', 'month', 'year', 'offDays', 'hasAutoRules', 'namelessCount'));
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:2030',
        ]);

        $month = $request->month;
        $year = $request->year;
        
        // Get employees who were active during this month or before
        $employees = Employee::where(function($query) use ($month, $year) {
            // Currently active employees (not soft deleted)
            $query->whereNull('deleted_at')
                  // OR employees who were soft deleted after this month (were active during this month)
                  ->orWhere(function($q) use ($month, $year) {
                      $q->whereNotNull('deleted_at')
                        ->where('deleted_at', '>=', Carbon::create($year, $month, 1)->startOfMonth());
                  });
        })->pluck('id');
        
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();

        // Holidays and non-active days are closed: the form does not change
        // them (shifts kept there belong to employees who did work that day)
        $offDays = EmployeeShiftAutoFill::offDays($startDate, $endDate);
        $validScheduleIds = $this->namedSchedules()->pluck('id')->map(fn ($id) => (string) $id)->all();

        // Shifts submitted in the form
        $submitted = [];
        foreach ($employees as $employeeId) {
            for ($day = 1; $day <= $endDate->day; $day++) {
                $dateString = Carbon::create($year, $month, $day)->format('Y-m-d');

                if (isset($offDays[$dateString])) {
                    continue;
                }

                $shiftKey = "shift_{$employeeId}_{$day}";
                if ($request->has($shiftKey) && in_array((string) $request->input($shiftKey), $validScheduleIds, true)) {
                    $submitted["{$employeeId}|{$dateString}"] = [
                        'employee_id' => $employeeId,
                        'schedule_id' => $request->input($shiftKey),
                        'date' => $dateString,
                    ];
                }
            }
        }

        $savedCount = DB::transaction(function () use ($startDate, $endDate, $submitted, $offDays) {
            // A shift that was removed (unchecked) takes its empty attendance row
            // with it. Attendance with a clock-in or clock-out is always kept,
            // and days whose shift stays are not touched at all.
            $existingShifts = EmployeeShift::whereBetween('date', [$startDate, $endDate])->get()
                ->reject(fn ($shift) => isset($offDays[$shift->date->format('Y-m-d')]));
            foreach ($existingShifts as $shift) {
                if (isset($submitted[$shift->employee_id . '|' . $shift->date->format('Y-m-d')])) {
                    continue;
                }
                Attendance::where('attendable_type', Employee::class)
                    ->where('attendable_id', $shift->employee_id)
                    ->whereDate('date', $shift->date->format('Y-m-d'))
                    ->whereNull('clock_in')
                    ->whereNull('clock_out')
                    // Keep days marked Cuti/Sakit/Dinas Luar on /admin/reports
                    ->where(fn ($q) => $q->whereNull('status_in')->orWhereNotIn('status_in', ['C', 'S', 'DL']))
                    ->delete();
            }

            EmployeeShift::whereIn('id', $existingShifts->pluck('id'))->delete();

            foreach ($submitted as $data) {
                EmployeeShift::create($data + ['is_active' => true]);
            }

            return count($submitted);
        });

        Log::info('Employee shifts saved from form', [
            'month' => $month,
            'year' => $year,
            'saved_count' => $savedCount,
            'off_days_count' => count($offDays),
        ]);

        // Apply shift changes to attendance records
        $this->applyShiftsToAttendance($month, $year);

        return redirect()->back()->with('success', "Pengaturan shift pegawai berhasil disimpan ({$savedCount} shift) dan diterapkan ke absensi.");
    }
    
    /**
     * "Terapkan Otomatis": fill the empty cells from today until the end of
     * the month from the shifts' Hari & Jabatan Penerapan.
     */
    public function autoFill(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:2100',
        ]);

        $back = redirect()->route('admin.employee-shifts.index', ['month' => $request->month, 'year' => $request->year]);

        if (!EmployeeShiftAutoFill::hasRules()) {
            return $back->with('error', 'Belum ada shift dengan Hari Penerapan dan Jabatan Penerapan. Atur di menu Jadwal Pegawai terlebih dahulu.');
        }

        $end = Carbon::create($request->year, $request->month, 1)->endOfMonth();
        if ($end->lt(Carbon::today())) {
            return $back->with('error', 'Pengisian otomatis hanya untuk hari ini dan seterusnya; bulan ini sudah lewat.');
        }

        $count = EmployeeShiftAutoFill::fillMonth((int) $request->year, (int) $request->month);

        return $back->with('success', $count > 0
            ? "{$count} shift diisi otomatis sesuai hari & jabatan penerapan (sel yang sudah terisi tidak diubah)."
            : 'Tidak ada sel kosong yang perlu diisi otomatis.');
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

        // Get employees who were active during this month or before
        $employees = Employee::where(function($query) use ($month, $year) {
            // Currently active employees (not soft deleted)
            $query->whereNull('deleted_at')
                  // OR employees who were soft deleted after this month (were active during this month)
                  ->orWhere(function($q) use ($month, $year) {
                      $q->whereNotNull('deleted_at')
                        ->where('deleted_at', '>=', Carbon::create($year, $month, 1)->startOfMonth());
                  });
        })->orderBy('full_name')->get();

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

                    // Named shifts only, so the file can be imported back
                    if ($shift && $shift->schedule && trim((string) $shift->schedule->shift_name)) {
                        $shiftName = $shift->schedule->shift_name;
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

            // Get employees who were active during this month or before, indexed by name
            $employees = Employee::where(function($query) use ($month, $year) {
                // Currently active employees (not soft deleted)
                $query->whereNull('deleted_at')
                      // OR employees who were soft deleted after this month (were active during this month)
                      ->orWhere(function($q) use ($month, $year) {
                          $q->whereNotNull('deleted_at')
                            ->where('deleted_at', '>=', Carbon::create($year, $month, 1)->startOfMonth());
                      });
            })->get();
            $employeeMap = [];
            foreach ($employees as $employee) {
                // Create case-insensitive, whitespace-trimmed mapping
                $key = strtolower(trim($employee->full_name));
                $employeeMap[$key] = $employee;
            }

            // Only named shifts are valid (only they count as active days)
            $schedules = $this->namedSchedules();

            // Generate dates for the selected month
            $startDate = Carbon::create($year, $month, 1)->startOfMonth();
            $endDate = Carbon::create($year, $month, 1)->endOfMonth();
            $dates = [];
            $currentDate = $startDate->copy();

            while ($currentDate <= $endDate) {
                $dates[] = $currentDate->copy();
                $currentDate->addDay();
            }

            // Shift name -> schedule, for validation
            $scheduleMap = [];
            foreach ($schedules as $schedule) {
                $scheduleMap[trim($schedule->shift_name)] = $schedule;
            }
            $offDays = EmployeeShiftAutoFill::offDays($startDate, $endDate);
            $skippedOffDays = 0;

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
                        } elseif ($shiftName && isset($offDays[$date->format('Y-m-d')])) {
                            // Holiday / non-active day: closed, not imported
                            $skippedOffDays++;
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
                $errors[] = 'Terdapat nama shift yang tidak valid. Harap gunakan nama shift yang sudah terdaftar (dan bernama) di /admin/schedules/pegawai. Detail: ' . implode(' | ', array_slice($errorDetails, 0, 5)) . (count($errorDetails) > 5 ? ' ... dan ' . (count($errorDetails) - 5) . ' lainnya' : '');
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

            // Replace this month's shifts on work days (closed dates stay as they are)
            $replaceable = EmployeeShift::whereBetween('date', [$startDate, $endDate])->get()
                ->reject(fn ($shift) => isset($offDays[$shift->date->format('Y-m-d')]));
            $deletedCount = EmployeeShift::whereIn('id', $replaceable->pluck('id'))->delete();

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
                ->with('success', 'Data shift berhasil diimport (' . $insertedCount . ' record) dan diterapkan ke absensi pegawai.'
                    . ($skippedOffDays ? " {$skippedOffDays} isian pada hari libur / bukan hari aktif dilewati." : ''));
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

        EmployeeShiftAutoFill::applyToAttendance(
            EmployeeShift::whereBetween('date', [$startDate, $endDate])
                ->where('is_active', true)
                ->with('schedule')
                ->get()
        );
    }

    /** Employee shifts that can be assigned: named ones only */
    private function namedSchedules()
    {
        return Schedule::where('type', 'pegawai')
            ->whereNotNull('shift_name')
            ->where('shift_name', '!=', '')
            ->orderBy('start_time')
            ->orderBy('shift_name')
            ->get();
    }
}
