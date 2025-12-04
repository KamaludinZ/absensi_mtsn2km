<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AttendanceCorrection;
use App\Models\Employee;
use App\Models\Student;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AttendanceCorrectionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = AttendanceCorrection::with(['correctable', 'approvedBy']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by month and year if provided
        if ($request->filled('month') && $request->filled('year')) {
            $query->whereMonth('date', $request->month)
                  ->whereYear('date', $request->year);
        } elseif ($request->filled('month')) {
            $query->whereMonth('date', $request->month)
                  ->whereYear('date', $request->year ?? now()->year);
        } elseif ($request->filled('year')) {
            $query->whereYear('date', $request->year);
        }
        // Set default to current month and year if no filters are provided
        elseif (!$request->filled('start_date') && !$request->filled('end_date')) {
            $query->whereMonth('date', now()->month)
                  ->whereYear('date', now()->year);
        }
        // Legacy date range filter (for backward compatibility)
        elseif ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        } elseif ($request->filled('start_date')) {
            $query->where('date', '>=', $request->start_date);
        } elseif ($request->filled('end_date')) {
            $query->where('date', '<=', $request->end_date);
        }

        $attendanceCorrections = $query->orderBy('created_at', 'desc')->get();

        return view('admin.attendance_corrections.index', compact('attendanceCorrections'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $employees = Employee::orderBy('full_name')->get();
        $students = Student::orderBy('full_name')->get();
        return view('admin.attendance_corrections.create', compact('employees', 'students'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'correctable_type' => ['required', Rule::in([Employee::class, Student::class])],
            'correctable_id' => 'required|integer',
            'date' => 'required|date',
            'original_clock_in' => 'nullable|date_format:H:i',
            'corrected_clock_in' => 'nullable|date_format:H:i',
            'original_clock_out' => 'nullable|date_format:H:i',
            'corrected_clock_out' => 'nullable|date_format:H:i',
            'reason' => 'required|string|max:1000',
            'supporting_document' => 'required|file|mimes:pdf|max:500', // max 500KB
            'status' => ['nullable', Rule::in(['pending', 'approved', 'rejected'])],
        ]);

        // Handle supporting document upload
        $supportingDocumentPath = null;
        if ($request->hasFile('supporting_document')) {
            $file = $request->file('supporting_document');
            $filename = time() . '_' . $file->getClientOriginalName();
            $supportingDocumentPath = $file->storeAs('attendance_corrections', $filename, 'public');
        }

        // Fetch the current attendance record for original times
        $attendance = Attendance::where('attendable_type', $request->correctable_type)
            ->where('attendable_id', $request->correctable_id)
            ->where('date', $request->date)
            ->first();

        AttendanceCorrection::create([
            'correctable_type' => $request->correctable_type,
            'correctable_id' => $request->correctable_id,
            'date' => $request->date,
            'original_clock_in' => $attendance->clock_in ?? null,
            'corrected_clock_in' => $request->corrected_clock_in,
            'original_clock_out' => $attendance->clock_out ?? null,
            'corrected_clock_out' => $request->corrected_clock_out,
            'reason' => $request->reason,
            'supporting_document' => $supportingDocumentPath,
            'status' => $request->status ?? 'pending',
        ]);

        return redirect()->route('admin.attendance-corrections.index')->with('success', 'Permohonan koreksi absensi berhasil dibuat.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(AttendanceCorrection $attendanceCorrection)
    {
        $employees = Employee::orderBy('full_name')->get();
        $students = Student::orderBy('full_name')->get();
        return view('admin.attendance_corrections.edit', compact('attendanceCorrection', 'employees', 'students'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, AttendanceCorrection $attendanceCorrection)
    {
        $request->validate([
            'correctable_type' => ['required', Rule::in([Employee::class, Student::class])],
            'correctable_id' => 'required|integer',
            'date' => 'required|date',
            'original_clock_in' => 'nullable|date_format:H:i',
            'corrected_clock_in' => 'nullable|date_format:H:i',
            'original_clock_out' => 'nullable|date_format:H:i',
            'corrected_clock_out' => 'nullable|date_format:H:i',
            'reason' => 'required|string|max:1000',
            'supporting_document' => 'nullable|file|mimes:pdf|max:500', // max 500KB
            'status' => ['required', Rule::in(['pending', 'approved', 'rejected'])],
        ]);

        $supportingDocumentPath = $attendanceCorrection->supporting_document;
        if ($request->hasFile('supporting_document')) {
            // Delete old document if exists
            if ($attendanceCorrection->supporting_document) {
                Storage::disk('public')->delete($attendanceCorrection->supporting_document);
            }
            $file = $request->file('supporting_document');
            $filename = time() . '_' . $file->getClientOriginalName();
            $supportingDocumentPath = $file->storeAs('attendance_corrections', $filename, 'public');
        }

        $attendanceCorrection->update([
            'correctable_type' => $request->correctable_type,
            'correctable_id' => $request->correctable_id,
            'date' => $request->date,
            'original_clock_in' => $request->original_clock_in, // Original values can be manually updated too
            'corrected_clock_in' => $request->corrected_clock_in,
            'original_clock_out' => $request->original_clock_out, // Original values can be manually updated too
            'corrected_clock_out' => $request->corrected_clock_out,
            'reason' => $request->reason,
            'supporting_document' => $supportingDocumentPath,
            'status' => $request->status,
        ]);

        return redirect()->route('admin.attendance-corrections.index')->with('success', 'Permohonan koreksi absensi berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AttendanceCorrection $attendanceCorrection)
    {
        $attendanceCorrection->delete();
        return redirect()->route('admin.attendance-corrections.index')->with('success', 'Permohonan koreksi absensi berhasil dihapus.');
    }

    public function approve(AttendanceCorrection $attendanceCorrection)
    {
        if ($attendanceCorrection->status !== 'pending') {
            return redirect()->back()->with('error', 'Koreksi absensi sudah tidak dalam status pending.');
        }

        // Apply the correction to the main attendance record
        $attendance = Attendance::firstOrNew([
            'attendable_type' => $attendanceCorrection->correctable_type,
            'attendable_id' => $attendanceCorrection->correctable_id,
            'date' => $attendanceCorrection->date,
        ]);

        if ($attendanceCorrection->corrected_clock_in) {
            // Extract time part regardless if it's a Carbon instance or string
            if ($attendanceCorrection->corrected_clock_in instanceof \Carbon\Carbon) {
                $timePart = $attendanceCorrection->corrected_clock_in->format('H:i:s');
            } else {
                // If it's a datetime string, parse it and extract time
                $parsedTime = Carbon::parse($attendanceCorrection->corrected_clock_in);
                $timePart = $parsedTime->format('H:i:s');
            }
            $attendance->clock_in = Carbon::parse($attendanceCorrection->date->format('Y-m-d') . ' ' . $timePart);
        }

        if ($attendanceCorrection->corrected_clock_out) {
            // Extract time part regardless if it's a Carbon instance or string
            if ($attendanceCorrection->corrected_clock_out instanceof \Carbon\Carbon) {
                $timePart = $attendanceCorrection->corrected_clock_out->format('H:i:s');
            } else {
                // If it's a datetime string, parse it and extract time
                $parsedTime = Carbon::parse($attendanceCorrection->corrected_clock_out);
                $timePart = $parsedTime->format('H:i:s');
            }
            $attendance->clock_out = Carbon::parse($attendanceCorrection->date->format('Y-m-d') . ' ' . $timePart);
        }

        // Only update status_in if corrected_clock_in was provided, and status_out if corrected_clock_out was provided
        if ($attendanceCorrection->corrected_clock_in) {
            $attendance->status_in = 'corrected';
        }

        if ($attendanceCorrection->corrected_clock_out) {
            $attendance->status_out = 'corrected';
        }

        $attendance->save();

        $attendanceCorrection->update([
            'status' => 'approved',
            'approved_by' => Auth::id(),
        ]);

        return redirect()->back()->with('success', 'Koreksi absensi berhasil disetujui dan diterapkan.');
    }

    public function reject(AttendanceCorrection $attendanceCorrection)
    {
        if ($attendanceCorrection->status !== 'pending') {
            return redirect()->back()->with('error', 'Koreksi absensi sudah tidak dalam status pending.');
        }

        $attendanceCorrection->update([
            'status' => 'rejected',
            'approved_by' => Auth::id(),
        ]);

        return redirect()->back()->with('success', 'Koreksi absensi berhasil ditolak.');
    }

    /**
     * Get attendance data for a specific date and employee/student.
     */
    public function getAttendanceData(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'correctable_type' => ['required', Rule::in([Employee::class, Student::class])],
            'correctable_id' => 'required|integer',
        ]);

        $attendance = Attendance::where('attendable_type', $request->correctable_type)
            ->where('attendable_id', $request->correctable_id)
            ->where('date', $request->date)
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
}
