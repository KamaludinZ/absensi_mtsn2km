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

        return view('admin.attendances.edit', compact('attendance', 'employees', 'schedules'));
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

        // Redirect to appropriate report page based on attendable type
        if ($attendance->attendable_type === Student::class) {
            return redirect()->route('admin.reports.students')
                ->with('success', 'Data absensi siswa berhasil diperbarui.');
        }

        return redirect()->route('admin.reports.index')
            ->with('success', 'Data absensi pegawai berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $attendance = Attendance::findOrFail($id);
        $isStudent = $attendance->attendable_type === Student::class;
        $attendance->delete();

        // Redirect to appropriate report page based on attendable type
        if ($isStudent) {
            return redirect()->route('admin.reports.students')
                ->with('success', 'Data absensi siswa berhasil dihapus.');
        }

        return redirect()->route('admin.reports.index')
            ->with('success', 'Data absensi pegawai berhasil dihapus.');
    }
}
