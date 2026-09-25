<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AttendanceCorrection;
use App\Models\Employee;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class AttendanceCorrectionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $employee = Auth::user()->employee;

        if (!$employee) {
            return redirect()->back()->with('error', 'Data pegawai tidak ditemukan.');
        }

        $attendanceCorrections = AttendanceCorrection::where('correctable_type', Employee::class)
            ->where('correctable_id', $employee->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('employee.attendance_corrections.index', compact('attendanceCorrections'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Fetch current attendance for the employee for today to pre-fill original times
        $employee = Auth::user()->employee;
        $today = Carbon::today();
        $attendance = Attendance::where('attendable_type', Employee::class)
            ->where('attendable_id', $employee->id)
            ->whereDate('date', $today)
            ->first();

        return view('employee.attendance_corrections.create', compact('attendance', 'employee'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'corrected_clock_in' => 'nullable|date_format:H:i',
            'corrected_clock_out' => 'nullable|date_format:H:i',
            'reason' => 'required|string|max:1000',
            'supporting_document' => 'required|file|mimes:pdf|max:500', // max 500KB
        ]);

        $employee = Auth::user()->employee;

        if (!$employee) {
            return redirect()->back()->with('error', 'Data pegawai tidak ditemukan.');
        }

        // Handle supporting document upload
        $supportingDocumentPath = null;
        if ($request->hasFile('supporting_document')) {
            $file = $request->file('supporting_document');
            $supportingDocumentPath = $file->store('attendance_corrections', 'public');
        }

        // Fetch current attendance for the day to get original times
        $attendance = Attendance::where('attendable_type', Employee::class)
            ->where('attendable_id', $employee->id)
            ->where('date', $request->date)
            ->first();

        AttendanceCorrection::create([
            'correctable_type' => Employee::class,
            'correctable_id' => $employee->id,
            'date' => $request->date,
            'original_clock_in' => $attendance->clock_in ?? null,
            'corrected_clock_in' => $request->corrected_clock_in,
            'original_clock_out' => $attendance->clock_out ?? null,
            'corrected_clock_out' => $request->corrected_clock_out,
            'reason' => $request->reason,
            'supporting_document' => $supportingDocumentPath,
            'status' => 'pending',
        ]);

        return redirect()->route('employee.attendance-corrections.index')->with('success', 'Permohonan koreksi absensi berhasil dikirim.');
    }

    /**
     * Get attendance data for a specific date.
     */
    public function getAttendanceData(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
        ]);

        $employee = Auth::user()->employee;

        if (!$employee) {
            return response()->json(['error' => 'Data pegawai tidak ditemukan.'], 404);
        }

        $date = Carbon::parse($request->date);

        $attendance = Attendance::where('attendable_type', Employee::class)
            ->where('attendable_id', $employee->id)
            ->whereDate('date', $date)
            ->first();

        $clockIn = $attendance ? ($attendance->clock_in ? Carbon::parse($attendance->clock_in)->format('H:i') : null) : null;
        $clockOut = $attendance ? ($attendance->clock_out ? Carbon::parse($attendance->clock_out)->format('H:i') : null) : null;

        return response()->json([
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'has_attendance' => $attendance !== null,
            'message' => $attendance ? 'Data absensi ditemukan.' : 'Tidak ada data absensi untuk tanggal tersebut.'
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $employee = Auth::user()->employee;

        if (!$employee) {
            return redirect()->back()->with('error', 'Data pegawai tidak ditemukan.');
        }

        $attendanceCorrection = AttendanceCorrection::where('correctable_type', Employee::class)
            ->where('correctable_id', $employee->id)
            ->findOrFail($id);

        return view('employee.attendance_corrections.show', compact('attendanceCorrection'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $employee = Auth::user()->employee;

        if (!$employee) {
            return redirect()->back()->with('error', 'Data pegawai tidak ditemukan.');
        }

        $attendanceCorrection = AttendanceCorrection::where('correctable_type', Employee::class)
            ->where('correctable_id', $employee->id)
            ->findOrFail($id);

        // If the status is not pending, don't allow editing
        if ($attendanceCorrection->status !== 'pending') {
            return redirect()->back()->with('error', 'Koreksi absensi ini tidak dapat diedit karena sudah diproses.');
        }

        // Fetch attendance data for the selected date
        $attendance = Attendance::where('attendable_type', Employee::class)
            ->where('attendable_id', $employee->id)
            ->whereDate('date', $attendanceCorrection->date)
            ->first();

        return view('employee.attendance_corrections.edit', compact('attendanceCorrection', 'attendance'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'date' => 'required|date',
            'corrected_clock_in' => 'nullable|date_format:H:i',
            'corrected_clock_out' => 'nullable|date_format:H:i',
            'reason' => 'required|string|max:1000',
            'supporting_document' => 'nullable|file|mimes:pdf|max:500',
            'remove_supporting_document' => 'nullable|boolean',
        ]);

        $employee = Auth::user()->employee;

        if (!$employee) {
            return redirect()->back()->with('error', 'Data pegawai tidak ditemukan.');
        }

        $attendanceCorrection = AttendanceCorrection::where('correctable_type', Employee::class)
            ->where('correctable_id', $employee->id)
            ->findOrFail($id);

        // If the status is not pending, don't allow updating
        if ($attendanceCorrection->status !== 'pending') {
            return redirect()->back()->with('error', 'Koreksi absensi ini tidak dapat diupdate karena sudah diproses.');
        }

        // Handle supporting document upload if provided
        $supportingDocumentPath = $attendanceCorrection->supporting_document; // Keep existing document if not updated

        // Check if user wants to remove the existing supporting document
        if ($request->has('remove_supporting_document') && $request->remove_supporting_document) {
            // Delete old file if exists
            if ($attendanceCorrection->supporting_document) {
                Storage::disk('public')->delete($attendanceCorrection->supporting_document);
            }
            $supportingDocumentPath = null;
        } elseif ($request->hasFile('supporting_document')) {
            // Delete old file if exists (and not being removed via checkbox)
            if ($attendanceCorrection->supporting_document) {
                Storage::disk('public')->delete($attendanceCorrection->supporting_document);
            }

            $file = $request->file('supporting_document');
            $supportingDocumentPath = $file->store('attendance_corrections', 'public');
        }

        // Fetch current attendance for the day to get original times
        $attendance = Attendance::where('attendable_type', Employee::class)
            ->where('attendable_id', $employee->id)
            ->where('date', $request->date)
            ->first();

        $attendanceCorrection->update([
            'date' => $request->date,
            'original_clock_in' => $attendance->clock_in ?? null,
            'corrected_clock_in' => $request->corrected_clock_in,
            'original_clock_out' => $attendance->clock_out ?? null,
            'corrected_clock_out' => $request->corrected_clock_out,
            'reason' => $request->reason,
            'supporting_document' => $supportingDocumentPath,
        ]);

        return redirect()->route('employee.attendance-corrections.index')->with('success', 'Permohonan koreksi absensi berhasil diperbarui.');
    }
}
