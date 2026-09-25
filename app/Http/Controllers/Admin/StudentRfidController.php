<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Rombel;
use App\Models\Student;
use App\Services\StudentKiosk;
use Illuminate\Http\Request;

/**
 * Register students' RFID cards: type the card number, or click the field
 * and tap the card on a USB RFID reader (which types the number + Enter).
 */
class StudentRfidController extends Controller
{
    public function index(Request $request)
    {
        $activeYear = AcademicYear::where('status', 'active')->first();
        $rombels = Rombel::when($activeYear, fn ($q) => $q->where('academic_year_id', $activeYear->id))
            ->orderBy('grade_id')
            ->orderBy('name')
            ->get();

        $status = $request->input('status', 'all');

        $students = Student::with('rombel')
            ->where('is_active', true)
            ->when($request->filled('rombel_id'), fn ($q) => $q->where('rombel_id', $request->rombel_id))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%' . $request->q . '%';
                $q->where(fn ($w) => $w->where('full_name', 'like', $term)->orWhere('nisn', 'like', $term));
            })
            ->when($status === 'registered', fn ($q) => $q->whereNotNull('rfid_uid'))
            ->when($status === 'unregistered', fn ($q) => $q->whereNull('rfid_uid'))
            ->orderBy('rombel_id')
            ->orderBy('full_name')
            ->paginate(50)
            ->withQueryString();

        $stats = [
            'total' => Student::where('is_active', true)->count(),
            'registered' => Student::where('is_active', true)->whereNotNull('rfid_uid')->count(),
        ];

        return view('admin.students.rfid', compact('students', 'rombels', 'stats', 'status'));
    }

    public function assign(Request $request, Student $student)
    {
        $request->validate([
            'rfid_uid' => 'required|string|max:50',
        ]);

        $uid = StudentKiosk::normalizeRfid($request->rfid_uid);

        $owner = Student::with('rombel')
            ->where('rfid_uid', $uid)
            ->where('id', '!=', $student->id)
            ->first();

        if ($owner) {
            return response()->json([
                'success' => false,
                'message' => 'Kartu ini sudah terdaftar untuk ' . $owner->full_name
                    . ($owner->rombel ? ' (' . $owner->rombel->name . ')' : '') . '.',
            ], 422);
        }

        $student->update(['rfid_uid' => $uid]);

        return response()->json([
            'success' => true,
            'rfid_uid' => $student->rfid_uid,
            'message' => 'Kartu tersimpan untuk ' . $student->full_name . '.',
        ]);
    }

    public function remove(Student $student)
    {
        $student->update(['rfid_uid' => null]);

        return response()->json([
            'success' => true,
            'message' => 'Kartu ' . $student->full_name . ' dihapus.',
        ]);
    }
}
