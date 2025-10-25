<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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

        // Filter by rombel
        if ($request->filled('rombel_id')) {
            $query->where('rombel_id', $request->rombel_id);
        }

        // Filter by status
        if ($request->filled('is_active')) {
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
                $students = $query->get()->sortBy(function($student) {
                    return strtolower($student->rombel->name ?? '');
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
        $rombels = $rombelsQuery->get();

        return view('admin.students.index', compact('students', 'grades', 'rombels', 'sortField', 'sortDirection'));
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
        $request->validate([
            'full_name' => 'required|string|max:255',
            'rombel_id' => 'required|exists:rombels,id',
            'is_active' => 'required|boolean',
        ]);

        Student::create($request->only(['full_name', 'rombel_id', 'is_active']));

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
        $request->validate([
            'full_name' => 'required|string|max:255',
            'rombel_id' => 'required|exists:rombels,id',
            'is_active' => 'required|boolean',
        ]);

        $student->update($request->only(['full_name', 'rombel_id', 'is_active']));

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
        return view('admin.students.import');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
        ]);

        try {
            Excel::import(new StudentsImport, $request->file('file'));

            return redirect()->route('admin.students.index')
                ->with('success', 'Data siswa berhasil diimpor.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal mengimpor data: ' . $e->getMessage());
        }
    }

    /**
     * Get students by rombel for student attendance selection
     * Only returns active students
     */
    public function getByRombel($rombelId)
    {
        $students = Student::where('rombel_id', $rombelId)
            ->where('is_active', true)
            ->orderBy('full_name')
            ->get(['id', 'full_name']);

        return response()->json($students);
    }
}
