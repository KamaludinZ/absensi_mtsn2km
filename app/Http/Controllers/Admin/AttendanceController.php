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
}
