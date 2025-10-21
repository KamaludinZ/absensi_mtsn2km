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

    public function employees()
    {
        $permissions = Permission::where('permissible_type', Employee::class)
            ->with(['permissible' => function($query) {
                $query->select('id', 'full_name', 'position');
            }])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('admin.permissions.index', compact('permissions'));
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

    public function students()
    {
        $permissions = Permission::where('permissible_type', Student::class)
            ->with(['permissible' => function($query) {
                $query->select('id', 'full_name', 'rombel_id');
            }, 'permissible.rombel'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('admin.permissions.index', compact('permissions'));
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