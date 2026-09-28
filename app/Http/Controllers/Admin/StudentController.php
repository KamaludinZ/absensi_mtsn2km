<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\StudentKiosk;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Rombel;
use App\Models\AcademicYear;
use App\Traits\Paginable;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\StudentsImport;

class StudentController extends Controller
{
    use Paginable;
    public function index(Request $request)
    {
        $query = Student::with(['rombel.grade', 'rombel.academicYear']);

        // Search filter for student name
        if ($request->filled('search')) {
            $query->where('full_name', 'like', '%' . $request->search . '%');
        }

        // Filter by grade (jenjang)
        if ($request->filled('grade_id')) {
            $query->whereHas('rombel', function ($q) use ($request) {
                $q->where('grade_id', $request->grade_id);
            });
        }

        // Filter by academic year of the student's rombel
        if ($request->filled('academic_year_id')) {
            $query->whereHas('rombel', function ($q) use ($request) {
                $q->where('academic_year_id', $request->academic_year_id);
            });
        }

        // Filter by rombel
        if ($request->filled('rombel_id')) {
            $query->where('rombel_id', $request->rombel_id);
        }

        // Filter by status
        if ($request->input('is_active') === 'graduated') {
            $query->whereNotNull('graduated_at');
        } elseif ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        // Sorting logic
        $sortField = $request->get('sort', 'full_name');
        $sortDirection = $request->get('direction', 'asc');

        // Validate sort direction
        if (!in_array($sortDirection, ['asc', 'desc'])) {
            $sortDirection = 'asc';
        }

        // Apply sorting based on field
        switch ($sortField) {
            case 'full_name':
                $query->orderBy('full_name', $sortDirection);
                break;

            case 'grade_name':
                // For relationship fields, we need to sort the collection
                $students = $query->get()->sortBy(function($student) {
                    return strtolower($student->rombel->grade->name ?? '');
                }, SORT_REGULAR, $sortDirection === 'desc');
                
                // Manually paginate with customizable pagination
                $perPage = $this->getPerPagePreference();
                $currentPage = request()->get('page', 1);
                
                if ($perPage === 'all') {
                    // Show all records without pagination
                    $students = new \Illuminate\Pagination\LengthAwarePaginator(
                        $students,
                        $students->count(),
                        $students->count(),
                        $currentPage,
                        ['path' => request()->url(), 'query' => request()->query()]
                    );
                } else {
                    $students = new \Illuminate\Pagination\LengthAwarePaginator(
                        $students->forPage($currentPage, $perPage),
                        $students->count(),
                        $perPage,
                        $currentPage,
                        ['path' => request()->url(), 'query' => request()->query()]
                    );
                }
                break;

            case 'rombel_name':
            case 'academic_year':
                $students = $query->get()->sortBy(function($student) use ($sortField) {
                    return $sortField === 'academic_year'
                        ? [$student->rombel->academicYear->year_name ?? '', strtolower($student->rombel->name ?? ''), strtolower($student->full_name)]
                        : strtolower($student->rombel->name ?? '');
                }, SORT_REGULAR, $sortDirection === 'desc');
                
                // Manually paginate with customizable pagination
                $perPage = $this->getPerPagePreference();
                $currentPage = request()->get('page', 1);
                
                if ($perPage === 'all') {
                    // Show all records without pagination
                    $paginatedStudents = new \Illuminate\Pagination\LengthAwarePaginator(
                        $students,
                        $students->count(),
                        $students->count(),
                        $currentPage,
                        ['path' => request()->url(), 'query' => request()->query()]
                    );
                    $students = collect($students);
                } else {
                    $paginatedStudents = new \Illuminate\Pagination\LengthAwarePaginator(
                        $students->forPage($currentPage, $perPage),
                        $students->count(),
                        $perPage,
                        $currentPage,
                        ['path' => request()->url(), 'query' => request()->query()]
                    );
                    $students = $paginatedStudents;
                }
                break;

            case 'is_active':
                $query->orderBy('is_active', $sortDirection);
                break;

            default:
                $query->orderBy('full_name', $sortDirection);
                $perPage = $this->getPerPagePreference();
                if ($perPage === 'all') {
                    $students = collect($query->get());
                } else {
                    $students = $query->paginate($perPage);
                }
                break;
        }
        
        if (!isset($students)) {
            $perPage = $this->getPerPagePreference();
            if ($perPage === 'all') {
                $students = collect($query->get());
            } else {
                $students = $query->paginate($perPage);
            }
        }
        
        // Get all grades for filter dropdown
        $grades = \App\Models\Grade::orderBy('name')->get();
        
        // Get rombels filtered by grade if grade is selected, otherwise all rombels
        $rombelsQuery = Rombel::with('academicYear')->orderBy('name');
        if ($request->filled('grade_id')) {
            $rombelsQuery->where('grade_id', $request->grade_id);
        }
        if ($request->filled('academic_year_id')) {
            $rombelsQuery->where('academic_year_id', $request->academic_year_id);
        }
        $rombels = $rombelsQuery->get();

        $academicYears = AcademicYear::orderByDesc('year_name')->get();

        return view('admin.students.index', compact('students', 'grades', 'rombels', 'academicYears', 'sortField', 'sortDirection'));
    }

    public function create()
    {
        $activeAcademicYear = AcademicYear::where('status', 'active')->first();
        $grades = \App\Models\Grade::orderBy('name')->get();
        
        // Get rombels for active academic year
        $rombels = Rombel::where('academic_year_id', $activeAcademicYear?->id)->orderBy('name')->get();

        return view('admin.students.create', compact('rombels', 'grades'));
    }

    public function store(Request $request)
    {
        $request->merge(['rfid_uid' => StudentKiosk::normalizeRfid($request->input('rfid_uid'))]);

        $request->validate([
            'nisn' => 'required|string|max:255|unique:students,nisn', // Add NISN validation
            'rfid_uid' => 'nullable|string|max:50|unique:students,rfid_uid',
            'full_name' => 'required|string|max:255',
            'rombel_id' => 'required|exists:rombels,id',
            'is_active' => 'required|boolean',
        ], $this->rfidMessages());

        Student::create($request->only(['nisn', 'rfid_uid', 'full_name', 'rombel_id', 'is_active'])); // Include NISN in creation

        return redirect()->route('admin.students.index')
            ->with('success', 'Data siswa berhasil ditambahkan.');
    }

    public function edit(Student $student)
    {
        $activeAcademicYear = AcademicYear::where('status', 'active')->first();
        $rombels = Rombel::where('academic_year_id', $activeAcademicYear?->id)->orderBy('name')->get();

        return view('admin.students.edit', compact('student', 'rombels'));
    }

    public function update(Request $request, Student $student)
    {
        $request->merge(['rfid_uid' => StudentKiosk::normalizeRfid($request->input('rfid_uid'))]);

        $request->validate([
            'nisn' => 'required|string|max:255|unique:students,nisn,' . $student->id, // Add NISN validation with unique rule excluding current student
            'rfid_uid' => 'nullable|string|max:50|unique:students,rfid_uid,' . $student->id,
            'full_name' => 'required|string|max:255',
            'rombel_id' => 'required|exists:rombels,id',
            'is_active' => 'required|boolean',
        ], $this->rfidMessages());

        $data = $request->only(['nisn', 'rfid_uid', 'full_name', 'rombel_id', 'is_active']);
        if ($request->boolean('is_active')) {
            $data['graduated_at'] = null; // re-activating a student undoes the graduation
        }

        $student->update($data); // Include NISN in update

        return redirect()->route('admin.students.index')
            ->with('success', 'Data siswa berhasil diperbarui.');
    }

    public function destroy(Student $student)
    {
        $student->delete();

        return redirect()->route('admin.students.index')
            ->with('success', 'Data siswa berhasil dihapus.');
    }

    public function importForm()
    {
        $activeYear = AcademicYear::where('status', 'active')->first();

        return view('admin.students.import', compact('activeYear'));
    }

    public function downloadTemplate(Request $request)
    {
        $withStudents = $request->boolean('with_students');
        $spreadsheet = \App\Exports\StudentImportTemplate::build($withStudents);
        $fileName = $withStudents ? 'data_siswa_' . now()->format('Ymd') . '.xlsx' : 'template_import_siswa.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save('php://output');
        }, $fileName, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv,txt|max:5120',
        ], [
            'file.required' => 'Pilih file Excel yang akan diimpor.',
            'file.mimes' => 'File harus berformat .xlsx, .xls atau .csv.',
            'file.max' => 'Ukuran file maksimal 5 MB.',
        ]);

        try {
            $sheet = Excel::toCollection(new StudentsImport, $request->file('file'))->first() ?? collect();
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'File tidak bisa dibaca. Pastikan file berasal dari template dan tidak rusak.');
        }

        $result = StudentsImport::process($sheet);

        if ($result['errors']) {
            return back()->with('import_errors', $result['errors']);
        }

        return redirect()->route('admin.students.index')
            ->with('success', "Import selesai: {$result['created']} siswa ditambahkan, {$result['updated']} siswa diperbarui.");
    }

    private function rfidMessages(): array
    {
        return [
            'rfid_uid.unique' => 'Kartu RFID ini sudah terdaftar untuk siswa lain.',
        ];
    }
}
