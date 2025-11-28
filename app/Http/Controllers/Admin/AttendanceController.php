<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Student;
use App\Models\Schedule;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    public function edit($id)
    {
        $attendance = Attendance::with('attendable')->findOrFail($id);

        // Load attendable with its relationships
        if ($attendance->attendable_type === Student::class) {
            $attendance->load('attendable.rombel');
        } else {
            $attendance->load('attendable');
        }

        // Get all employees
        $employees = Employee::orderBy('full_name')->get();

        // Get schedules
        $schedules = Schedule::where('type', 'pegawai')->get();

        // Get the referring URL query parameters
        $previousUrl = url()->previous();
        $query = parse_url($previousUrl, PHP_URL_QUERY);
        $queryParamString = $query ? '?' . $query : '';

        // Calculate computed status_in if not manually set
        $computedStatusIn = null;
        if (empty($attendance->status_in) && $attendance->clock_in) {
            $schedule = $this->getSchedule($attendance);
            if ($schedule) {
                $clockIn = \Carbon\Carbon::parse($attendance->clock_in);
                $startTime = \Carbon\Carbon::parse($schedule->start_time)->setDate($clockIn->year, $clockIn->month, $clockIn->day);
                $diffInSeconds = $clockIn->diffInSeconds($startTime, false);
                $computedStatusIn = ($diffInSeconds > 0) ? 'Terlambat' : 'Tepat Waktu';
            } elseif (!$attendance->clock_in) {
                $computedStatusIn = 'Tidak Hadir';
            }
        } else {
            // For permission types, convert codes to display values
            if (in_array($attendance->status_in, ['C', 'S', 'DL'])) {
                $statusMap = ['C' => 'Cuti', 'S' => 'Sakit', 'DL' => 'Dinas Luar'];
                $computedStatusIn = $statusMap[$attendance->status_in];
            }
        }

        // Calculate computed status_out if not manually set
        $computedStatusOut = null;
        if (empty($attendance->status_out) && $attendance->clock_out) {
            $schedule = $this->getSchedule($attendance);
            if ($schedule) {
                $clockOut = \Carbon\Carbon::parse($attendance->clock_out);
                $endTime = \Carbon\Carbon::parse($schedule->end_time)->setDate($clockOut->year, $clockOut->month, $clockOut->day);
                $diffInSeconds = $clockOut->diffInSeconds($endTime, false);
                $computedStatusOut = ($diffInSeconds < 0) ? 'Pulang Awal' : 'Tepat Waktu';
            } elseif (!$attendance->clock_out) {
                $computedStatusOut = 'Belum Pulang';
            }
        } else {
            // For permission types, convert codes to display values
            if (in_array($attendance->status_out, ['C', 'S', 'DL'])) {
                $statusMap = ['C' => 'Cuti', 'S' => 'Sakit', 'DL' => 'Dinas Luar'];
                $computedStatusOut = $statusMap[$attendance->status_out];
            }
        }

        return view('admin.attendances.edit', compact(
            'attendance',
            'employees',
            'schedules',
            'queryParamString',
            'computedStatusIn',
            'computedStatusOut'
        ));
    }

    private function getSchedule($attendance)
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
        if ($dayOfWeek == 0) $dayOfWeek = 7; // Convert Sunday (0) to 7
        return Schedule::where('type', 'pegawai')
            ->where('day_of_week', $dayOfWeek)
            ->first();
    }

    public function update(Request $request, $id)
    {
        $attendance = Attendance::findOrFail($id);

        $request->validate([
            'date' => 'required|date',
            'clock_in' => 'nullable|date_format:H:i:s,H:i',
            'clock_out' => 'nullable|date_format:H:i:s,H:i',
            'status_in' => 'nullable|string',
            'status_out' => 'nullable|string',
        ]);

        // Prepare update data
        $updateData = [
            'date' => $request->date,
        ];

        if ($request->filled('clock_in')) {
            $updateData['clock_in'] = Carbon::parse($request->date . ' ' . $request->clock_in);
        } else {
            $updateData['clock_in'] = null;
        }

        if ($request->filled('clock_out')) {
            $updateData['clock_out'] = Carbon::parse($request->date . ' ' . $request->clock_out);
        } else {
            $updateData['clock_out'] = null;
        }

        if ($request->filled('status_in')) {
            $statusIn = $request->status_in;
            // Convert display status back to internal code if it's a permission type
            if (in_array($statusIn, ['Cuti', 'Sakit', 'Dinas Luar'])) {
                $reverseStatusMap = ['Cuti' => 'C', 'Sakit' => 'S', 'Dinas Luar' => 'DL'];
                $statusIn = $reverseStatusMap[$statusIn];
                $updateData['status_in'] = $statusIn; // User explicitly selected a permission
            }
            // For other values, keep as is (user wants to manually set specific status)
            else {
                $updateData['status_in'] = $statusIn;
            }
        }
        // If clock_in value is in request and different from current value (or null to time), and previous status was computed (not permission),
        // then clear status_in to allow recomputation
        else if ($request->has('clock_in') && !in_array($attendance->status_in, ['C', 'S', 'DL'])) {
            // User changed clock_in, clear the old computed status to allow new computation
            $updateData['status_in'] = null;
        }
        // In other cases where status_in is not provided, keep existing value
        else {
            // Don't change the status_in value if it wasn't explicitly submitted
        }

        // Handle status_out: convert display text back to internal codes if needed
        if ($request->filled('status_out')) {
            $statusOut = $request->status_out;
            // Convert display status back to internal code if it's a permission type
            if (in_array($statusOut, ['Cuti', 'Sakit', 'Dinas Luar'])) {
                $reverseStatusMap = ['Cuti' => 'C', 'Sakit' => 'S', 'Dinas Luar' => 'DL'];
                $statusOut = $reverseStatusMap[$statusOut];
                $updateData['status_out'] = $statusOut; // User explicitly selected a permission
            }
            // For other values, keep as is (user wants to manually set specific status)
            else {
                $updateData['status_out'] = $statusOut;
            }
        }
        // If clock_out value is in request and different from current value (or null to time), and previous status was computed (not permission),
        // then clear status_out to allow recomputation
        else if ($request->has('clock_out') && !in_array($attendance->status_out, ['C', 'S', 'DL'])) {
            // User changed clock_out, clear the old computed status to allow new computation
            $updateData['status_out'] = null;
        }
        // In other cases where status_out is not provided, keep existing value
        else {
            // Don't change the status_out value if it wasn't explicitly submitted
        }

        $attendance->update($updateData);

        // Determine the route based on attendable type
        $route = $attendance->attendable_type === Student::class ? 'admin.reports.students' : 'admin.reports.index';

        // Preserve query parameters from the hidden input or referring page
        $redirectUrl = route($route);
        if ($request->filled('redirect_params')) {
            // Use the query parameters that were stored in the hidden input
            $redirectUrl .= $request->redirect_params;
        } else {
            // Fallback: try to get from the referring page
            $previousUrl = url()->previous();
            $query = parse_url($previousUrl, PHP_URL_QUERY);
            if ($query) {
                $redirectUrl .= '?' . $query;
            }
        }

        return redirect($redirectUrl)
            ->with('success', $attendance->attendable_type === Student::class ? 'Data absensi siswa berhasil diperbarui.' : 'Data absensi pegawai berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $attendance = Attendance::findOrFail($id);
        $isStudent = $attendance->attendable_type === Student::class;
        $attendance->delete();

        // Determine the route based on attendable type
        $route = $isStudent ? 'admin.reports.students' : 'admin.reports.index';

        // Get the original request parameters from the referring page
        $previousUrl = url()->previous();
        $query = parse_url($previousUrl, PHP_URL_QUERY);
        $redirectUrl = route($route);
        if ($query) {
            $redirectUrl .= '?' . $query;
        }

        return redirect($redirectUrl)
            ->with('success', $isStudent ? 'Data absensi siswa berhasil dihapus.' : 'Data absensi pegawai berhasil dihapus.');
    }

    public function create()
    {
        // Redirect to employee create by default
        return redirect()->route('admin.attendances.create-employee');
    }

    public function createEmployee()
    {
        // Get all employees for the dropdown
        $employees = Employee::orderBy('full_name')->get();

        // Get all schedules for employee shifts
        $schedules = Schedule::where('type', 'pegawai')->get();

        // Get the referring URL query parameters
        $previousUrl = url()->previous();
        $query = parse_url($previousUrl, PHP_URL_QUERY);
        $queryParamString = $query ? '?' . $query : '';

        return view('admin.attendances.create-employee', compact('employees', 'schedules', 'queryParamString'));
    }

    public function createStudent()
    {
        // Get all students for the dropdown
        $students = Student::orderBy('full_name')->get();

        // Get all schedules for student shifts
        $schedules = Schedule::where('type', 'siswa')->get();

        // Get the referring URL query parameters
        $previousUrl = url()->previous();
        $query = parse_url($previousUrl, PHP_URL_QUERY);
        $queryParamString = $query ? '?' . $query : '';

        return view('admin.attendances.create-student', compact('students', 'schedules', 'queryParamString'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'attendable_type' => 'required|string|in:App\Models\Employee,App\Models\Student',
            'attendable_id' => [
                'required',
                'integer',
                function ($attribute, $value, $fail) use ($request) {
                    $model = $request->attendable_type;
                    $modelClass = str_replace('App\\Models\\', '', $model);
                    $modelInstance = 'App\\Models\\' . $modelClass;

                    if (!class_exists($modelInstance)) {
                        $fail('Model tidak ditemukan.');
                        return;
                    }

                    $exists = $modelInstance::where('id', $value)->exists();
                    if (!$exists) {
                        $fail('Pilihan ' . strtolower($modelClass) . ' tidak valid.');
                    }
                },
            ],
            'date' => 'required|date',
            'clock_in' => 'nullable|date_format:H:i:s,H:i',
            'clock_out' => 'nullable|date_format:H:i:s,H:i',
            'status_in' => 'nullable|string',
            'status_out' => 'nullable|string',
        ]);

        // Prepare data
        $data = [
            'attendable_type' => $request->attendable_type,
            'attendable_id' => $request->attendable_id,
            'date' => $request->date,
        ];

        if ($request->filled('clock_in')) {
            $data['clock_in'] = Carbon::parse($request->date . ' ' . $request->clock_in);
        }

        if ($request->filled('clock_out')) {
            $data['clock_out'] = Carbon::parse($request->date . ' ' . $request->clock_out);
        }

        if ($request->filled('status_in')) {
            $data['status_in'] = $request->status_in;
        }

        if ($request->filled('status_out')) {
            $data['status_out'] = $request->status_out;
        }

        Attendance::create($data);

        // Determine the route based on attendable type
        $route = $request->attendable_type === 'App\Models\Student' ? 'admin.reports.students' : 'admin.reports.index';

        // Preserve query parameters from the hidden input or referring page
        $redirectUrl = route($route);
        if ($request->filled('redirect_params')) {
            // Use the query parameters that were stored in the hidden input
            $redirectUrl .= $request->redirect_params;
        } else {
            // Fallback: try to get from the referring page
            $previousUrl = url()->previous();
            $query = parse_url($previousUrl, PHP_URL_QUERY);
            if ($query) {
                $redirectUrl .= '?' . $query;
            }
        }

        // Return with appropriate success message based on attendable type
        return redirect($redirectUrl)
            ->with('success', $request->attendable_type === 'App\Models\Student' ? 'Data absensi siswa berhasil ditambahkan.' : 'Data absensi pegawai berhasil ditambahkan.');
    }
}
