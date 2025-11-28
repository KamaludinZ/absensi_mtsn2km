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
        $attendance = Attendance::findOrFail($id);

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

        return view('admin.attendances.edit', compact('attendance', 'employees', 'schedules', 'queryParamString'));
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
            $updateData['status_in'] = $request->status_in;
        }

        if ($request->filled('status_out')) {
            $updateData['status_out'] = $request->status_out;
        }

        $attendance->update($updateData);

        // Determine the route based on attendable type
        $route = $attendance->attendable_type === Student::class ? 'admin.reports.students' : 'admin.reports.index';

        // Get the original request parameters from the referring page
        $previousUrl = url()->previous();
        $query = parse_url($previousUrl, PHP_URL_QUERY);
        $redirectUrl = route($route);
        if ($query) {
            $redirectUrl .= '?' . $query;
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
        // Get all employees for the dropdown
        $employees = Employee::orderBy('full_name')->get();

        // Get all students for the dropdown
        $students = \App\Models\Student::orderBy('full_name')->get();

        // Get all schedules for employee shifts
        $schedules = Schedule::where('type', 'pegawai')->get();

        return view('admin.attendances.create', compact('employees', 'students', 'schedules'));
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

        // Preserve query parameters from the referring page (if coming from reports page)
        $previousUrl = url()->previous();
        $query = parse_url($previousUrl, PHP_URL_QUERY);
        $redirectUrl = route($route);
        if ($query) {
            $redirectUrl .= '?' . $query;
        } else {
            // If coming directly to create page, just go to the main report route
            $redirectUrl = route($route);
        }

        // Return with appropriate success message based on attendable type
        return redirect($redirectUrl)
            ->with('success', $request->attendable_type === 'App\Models\Student' ? 'Data absensi siswa berhasil ditambahkan.' : 'Data absensi pegawai berhasil ditambahkan.');
    }
}
