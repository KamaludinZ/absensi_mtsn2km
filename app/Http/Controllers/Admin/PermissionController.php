<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Permission;
use App\Models\Employee;
use App\Models\Student;

class PermissionController extends Controller
{
    public function index()
    {
        $permissions = Permission::where('permissible_type', Employee::class)
            ->with(['permissible'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('admin.permissions.index', compact('permissions'));
    }

    public function employees(Request $request)
    {
        $query = Permission::where('permissible_type', Employee::class)
            ->with(['permissible' => function($query) {
                $query->select('id', 'full_name', 'position');
            }]);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->where(function($q) use ($request) {
                $q->whereBetween('start_date', [$request->start_date, $request->end_date])
                  ->orWhereBetween('end_date', [$request->start_date, $request->end_date])
                  ->orWhere(function($subQ) use ($request) {
                      $subQ->where('start_date', '<=', $request->start_date)
                           ->where('end_date', '>=', $request->end_date);
                  });
            });
        } elseif ($request->filled('start_date')) {
            $query->where('start_date', '>=', $request->start_date);
        } elseif ($request->filled('end_date')) {
            $query->where('end_date', '<=', $request->end_date);
        }

        // Filter by employee
        if ($request->filled('employee_id')) {
            $query->where('permissible_id', $request->employee_id);
        }

        $permissions = $query->orderBy('created_at', 'desc')->paginate(15);

        // Get employees for filter dropdown
        $employees = Employee::orderBy('full_name')->get();

        return view('admin.permissions.index', compact('permissions', 'employees'));
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
            }, 'permissible.rombel']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->where(function($q) use ($request) {
                $q->whereBetween('start_date', [$request->start_date, $request->end_date])
                  ->orWhereBetween('end_date', [$request->start_date, $request->end_date])
                  ->orWhere(function($subQ) use ($request) {
                      $subQ->where('start_date', '<=', $request->start_date)
                           ->where('end_date', '>=', $request->end_date);
                  });
            });
        } elseif ($request->filled('start_date')) {
            $query->where('start_date', '>=', $request->start_date);
        } elseif ($request->filled('end_date')) {
            $query->where('end_date', '<=', $request->end_date);
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

        $permissions = $query->orderBy('created_at', 'desc')->paginate(15);

        // Get rombels for filter dropdown
        $rombels = \App\Models\Rombel::orderBy('name')->get();

        return view('admin.permissions.index', compact('permissions', 'rombels'));
    }

    public function approve(Permission $permission)
    {
        $permission->update(['status' => 'approved']);

        return redirect()->back()->with('success', 'Izin berhasil disetujui.');
    }

    public function reject(Permission $permission)
    {
        $permission->update(['status' => 'rejected']);

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