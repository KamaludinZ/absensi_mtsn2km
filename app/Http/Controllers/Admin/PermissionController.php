<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Permission;
use App\Models\Employee;
use App\Models\Student;
use App\Traits\Paginable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use App\Mail\PermissionApprovedMail;
use App\Mail\PermissionRejectedMail;

class PermissionController extends Controller
{
    use Paginable;
    public function index(Request $request)
    {
        $query = Permission::where('permissible_type', Employee::class)
            ->with(['permissible', 'approvedBy']);

        // Filter by month and year - default to current month and year if no filters provided
        $selectedMonth = $request->filled('month') ? $request->month : now()->month;
        $selectedYear = $request->filled('year') ? $request->year : now()->year;

        if ($request->filled('month') && $request->filled('year')) {
            $query->whereMonth('start_date', $request->month)
                  ->whereYear('start_date', $request->year);
        } elseif ($request->filled('month')) {
            $query->whereMonth('start_date', $request->month)
                  ->whereYear('start_date', $selectedYear);
        } elseif ($request->filled('year')) {
            $query->whereYear('start_date', $request->year);
        } else {
            // Default to current month and year
            $query->whereMonth('start_date', now()->month)
                  ->whereYear('start_date', now()->year);
        }

        $perPage = $this->getPerPagePreference();
        if ($perPage === 'all') {
            $permissions = collect($query->orderBy('created_at', 'desc')->get());
        } else {
            $permissions = $query->orderBy('created_at', 'desc')->paginate($perPage);
        }

        return view('admin.permissions.index', compact('permissions', 'selectedMonth', 'selectedYear'));
    }

    public function employees(Request $request)
    {
        $query = Permission::where('permissible_type', Employee::class)
            ->with(['permissible' => function($query) {
                $query->select('id', 'full_name', 'position');
            }, 'approvedBy']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by month and year - default to current month and year if no filters provided
        $selectedMonth = $request->filled('month') ? $request->month : now()->month;
        $selectedYear = $request->filled('year') ? $request->year : now()->year;

        if ($request->filled('month') && $request->filled('year')) {
            $query->whereMonth('start_date', $request->month)
                  ->whereYear('start_date', $request->year);
        } elseif ($request->filled('month')) {
            $query->whereMonth('start_date', $request->month)
                  ->whereYear('start_date', $selectedYear);
        } elseif ($request->filled('year')) {
            $query->whereYear('start_date', $request->year);
        } else {
            // Default to current month and year
            $query->whereMonth('start_date', now()->month)
                  ->whereYear('start_date', now()->year);
        }

        // Filter by employee
        if ($request->filled('employee_id')) {
            $query->where('permissible_id', $request->employee_id);
        }

        // Sorting logic for employees
        $sortField = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');

        // Validate sort direction
        if (!in_array($sortDirection, ['asc', 'desc'])) {
            $sortDirection = 'desc';
        }

        // Apply sorting based on field
        switch ($sortField) {
            case 'full_name':
                // For relationship fields, we need to sort the collection
                $permissions = $query->get()->sortBy(function($permission) {
                    return strtolower($permission->permissible->full_name ?? '');
                }, SORT_REGULAR, $sortDirection === 'desc');

                // Manually paginate with customizable pagination
                $perPage = $this->getPerPagePreference();
                $currentPage = request()->get('page', 1);

                if ($perPage === 'all') {
                    // Show all records without pagination
                    $paginatedPermissions = new \Illuminate\Pagination\LengthAwarePaginator(
                        $permissions,
                        $permissions->count(),
                        $permissions->count(),
                        $currentPage,
                        ['path' => request()->url(), 'query' => request()->query()]
                    );
                    $permissions = collect($permissions);
                } else {
                    $paginatedPermissions = new \Illuminate\Pagination\LengthAwarePaginator(
                        $permissions->forPage($currentPage, $perPage),
                        $permissions->count(),
                        $perPage,
                        $currentPage,
                        ['path' => request()->url(), 'query' => request()->query()]
                    );
                    $permissions = $paginatedPermissions;
                }
                break;

            case 'position':
                $permissions = $query->get()->sortBy(function($permission) {
                    return strtolower($permission->permissible->position ?? '');
                }, SORT_REGULAR, $sortDirection === 'desc');

                // Manually paginate with customizable pagination
                $perPage = $this->getPerPagePreference();
                $currentPage = request()->get('page', 1);

                if ($perPage === 'all') {
                    // Show all records without pagination
                    $paginatedPermissions = new \Illuminate\Pagination\LengthAwarePaginator(
                        $permissions,
                        $permissions->count(),
                        $permissions->count(),
                        $currentPage,
                        ['path' => request()->url(), 'query' => request()->query()]
                    );
                    $permissions = collect($permissions);
                } else {
                    $paginatedPermissions = new \Illuminate\Pagination\LengthAwarePaginator(
                        $permissions->forPage($currentPage, $perPage),
                        $permissions->count(),
                        $perPage,
                        $currentPage,
                        ['path' => request()->url(), 'query' => request()->query()]
                    );
                    $permissions = $paginatedPermissions;
                }
                break;

            case 'start_date':
            case 'end_date':
            case 'status':
            case 'reason':
                $query->orderBy($sortField, $sortDirection);
                $perPage = $this->getPerPagePreference();
                if ($perPage === 'all') {
                    $permissions = collect($query->get());
                } else {
                    $permissions = $query->paginate($perPage);
                }
                break;

            case 'created_at':
            default:
                $query->orderBy('created_at', $sortDirection);
                $perPage = $this->getPerPagePreference();
                if ($perPage === 'all') {
                    $permissions = collect($query->get());
                } else {
                    $permissions = $query->paginate($perPage);
                }
                break;
        }

        if (!isset($permissions)) {
            $perPage = $this->getPerPagePreference();
            if ($perPage === 'all') {
                $permissions = collect($query->get());
            } else {
                $permissions = $query->paginate($perPage);
            }
        }

        // Get employees for filter dropdown
        $employees = Employee::orderBy('full_name')->get();

        return view('admin.permissions.index', compact('permissions', 'employees', 'sortField', 'sortDirection', 'selectedMonth', 'selectedYear'));
    }

    public function createEmployee()
    {
        $employees = Employee::where('is_active', true)->orderBy('full_name')->get();
        return view('admin.permissions.create-employee', compact('employees'));
    }

    public function storeEmployee(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string|max:500',
            'supporting_document' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        $documentPath = null;
        if ($request->hasFile('supporting_document')) {
            $documentPath = $request->file('supporting_document')->store('permissions', 'public');
        }

        Permission::create([
            'permissible_type' => Employee::class,
            'permissible_id' => $request->employee_id,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'reason' => $request->reason,
            'supporting_document' => $documentPath,
            'status' => $request->status ?? 'pending',
        ]);

        return redirect()->route('admin.permissions.employees')->with('success', 'Izin pegawai berhasil ditambahkan.');
    }

    public function editEmployee(Permission $permission)
    {
        if ($permission->permissible_type !== Employee::class) {
            abort(404);
        }

        // Load the relation to get the current employee for this permission
        $permission->load('permissible');

        $employees = Employee::where('is_active', true)->orderBy('full_name')->get();
        return view('admin.permissions.edit-employee', compact('permission', 'employees'));
    }

    public function updateEmployee(Request $request, Permission $permission)
    {
        if ($permission->permissible_type !== Employee::class) {
            abort(404);
        }

        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string|max:500',
            'status' => 'required|in:pending,approved,rejected',
            'supporting_document' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        $documentPath = $permission->supporting_document;
        if ($request->hasFile('supporting_document')) {
            // Delete old document if exists
            if ($permission->supporting_document) {
                \Storage::disk('public')->delete($permission->supporting_document);
            }
            $documentPath = $request->file('supporting_document')->store('permissions', 'public');
        }

        $permission->update([
            'permissible_type' => Employee::class,
            'permissible_id' => $request->employee_id,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'reason' => $request->reason,
            'supporting_document' => $documentPath,
            'status' => $request->status,
        ]);

        return redirect()->route('admin.permissions.employees')->with('success', 'Izin pegawai berhasil diperbarui.');
    }

    public function destroyEmployee(Permission $permission)
    {
        if ($permission->permissible_type !== Employee::class) {
            abort(404);
        }

        // Delete document if exists
        if ($permission->supporting_document) {
            \Storage::disk('public')->delete($permission->supporting_document);
        }

        $permission->delete();

        return redirect()->route('admin.permissions.employees')->with('success', 'Izin pegawai berhasil dihapus.');
    }

    public function students(Request $request)
    {
        $query = Permission::where('permissible_type', Student::class)
            ->with(['permissible' => function($query) {
                $query->select('id', 'full_name', 'rombel_id');
            }, 'permissible.rombel', 'approvedBy']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by month and year - default to current month and year if no filters provided
        $selectedMonth = $request->filled('month') ? $request->month : now()->month;
        $selectedYear = $request->filled('year') ? $request->year : now()->year;

        if ($request->filled('month') && $request->filled('year')) {
            $query->whereMonth('start_date', $request->month)
                  ->whereYear('start_date', $request->year);
        } elseif ($request->filled('month')) {
            $query->whereMonth('start_date', $request->month)
                  ->whereYear('start_date', $selectedYear);
        } elseif ($request->filled('year')) {
            $query->whereYear('start_date', $request->year);
        } else {
            // Default to current month and year
            $query->whereMonth('start_date', now()->month)
                  ->whereYear('start_date', now()->year);
        }

        // Filter by rombel
        if ($request->filled('rombel_id')) {
            $studentIds = Student::where('rombel_id', $request->rombel_id)->pluck('id');
            $query->whereIn('permissible_id', $studentIds);
        }

        // Filter by student name
        if ($request->filled('student_name')) {
            $studentIds = Student::where('full_name', 'ILIKE', '%' . $request->student_name . '%')->pluck('id');
            $query->whereIn('permissible_id', $studentIds);
        }

        $perPage = $this->getPerPagePreference();
        if ($perPage === 'all') {
            $permissions = collect($query->orderBy('created_at', 'desc')->get());
        } else {
            $permissions = $query->orderBy('created_at', 'desc')->paginate($perPage);
        }

        // Get rombels for filter dropdown
        $rombels = \App\Models\Rombel::orderBy('name')->get();

        return view('admin.permissions.index', compact('permissions', 'rombels', 'selectedMonth', 'selectedYear'));
    }

    public function approve(Permission $permission)
    {
        $permission->update([
            'status' => 'approved',
            'approved_by' => auth()->id()
        ]);

        return redirect()->back()->with('success', 'Izin berhasil disetujui.');
    }

    public function reject(Permission $permission)
    {
        $permission->update([
            'status' => 'rejected',
            'approved_by' => auth()->id()
        ]);

        return redirect()->back()->with('success', 'Izin berhasil ditolak.');
    }

    public function createStudent()
    {
        $students = Student::with('rombel')->where('is_active', true)->get();
        $grades = \App\Models\Grade::all();
        $rombels = \App\Models\Rombel::with('grade')->get();
        return view('admin.permissions.create', compact('students', 'grades', 'rombels'));
    }

    public function storeStudent(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string|max:500',
            'supporting_document' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        $documentPath = null;
        if ($request->hasFile('supporting_document')) {
            $documentPath = $request->file('supporting_document')->store('permissions', 'public');
        }

        Permission::create([
            'permissible_type' => Student::class,
            'permissible_id' => $request->student_id,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'reason' => $request->reason,
            'supporting_document' => $documentPath,
            'status' => 'pending',
        ]);

        return redirect()->route('admin.permissions.students')->with('success', 'Izin siswa berhasil ditambahkan.');
    }

    public function editStudent(Permission $permission)
    {
        if ($permission->permissible_type !== Student::class) {
            abort(404);
        }

        // Load the relation to get the current student for this permission
        $permission->load('permissible.rombel.grade');

        $students = Student::with('rombel')->where('is_active', true)->get();
        $grades = \App\Models\Grade::all();
        $rombels = \App\Models\Rombel::with('grade')->get();
        return view('admin.permissions.edit', compact('permission', 'students', 'grades', 'rombels'));
    }

    public function updateStudent(Request $request, Permission $permission)
    {
        if ($permission->permissible_type !== Student::class) {
            abort(404);
        }

        $request->validate([
            'student_id' => 'required|exists:students,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string|max:500',
            'status' => 'required|in:pending,approved,rejected',
            'supporting_document' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        $documentPath = $permission->supporting_document;
        if ($request->hasFile('supporting_document')) {
            // Delete old document if exists
            if ($permission->supporting_document) {
                \Storage::disk('public')->delete($permission->supporting_document);
            }
            $documentPath = $request->file('supporting_document')->store('permissions', 'public');
        }

        $permission->update([
            'permissible_type' => Student::class,
            'permissible_id' => $request->student_id,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'reason' => $request->reason,
            'supporting_document' => $documentPath,
            'status' => $request->status,
        ]);

        return redirect()->route('admin.permissions.students')->with('success', 'Izin siswa berhasil diperbarui.');
    }

    public function destroyStudent(Permission $permission)
    {
        if ($permission->permissible_type !== Student::class) {
            abort(404);
        }

        // Delete document if exists
        if ($permission->supporting_document) {
            \Storage::disk('public')->delete($permission->supporting_document);
        }

        $permission->delete();

        return redirect()->route('admin.permissions.students')->with('success', 'Izin siswa berhasil dihapus.');
    }
}