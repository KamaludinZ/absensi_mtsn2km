<?php

namespace App\Http\Controllers\Monitoring;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Permission;
use App\Models\Employee;
use App\Models\Student;
use App\Models\Rombel;

class PermissionMonitorController extends Controller
{
    public function index(Request $request)
    {
        $query = Permission::with('permissible');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type')) {
            if ($request->type === 'employee') {
                $query->where('permissible_type', Employee::class);
            } elseif ($request->type === 'student') {
                $query->where('permissible_type', Student::class);
            }
        }

        $permissions = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('monitoring.permissions.index', compact('permissions'));
    }

    public function employees(Request $request)
    {
        $query = Permission::where('permissible_type', Employee::class)
            ->with(['permissible']);

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

        $permissions = $query->orderBy('created_at', 'desc')->paginate(20);

        // Get employees for filter dropdown
        $employees = Employee::orderBy('full_name')->get();

        return view('monitoring.permissions.employees', compact('permissions', 'employees'));
    }

    public function students(Request $request)
    {
        $query = Permission::where('permissible_type', Student::class)
            ->with(['permissible.rombel']);

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

        $permissions = $query->orderBy('created_at', 'desc')->paginate(20);

        // Get rombels for filter dropdown
        $rombels = Rombel::orderBy('name')->get();

        return view('monitoring.permissions.students', compact('permissions', 'rombels'));
    }

    public function approve(Permission $permission)
    {
        $permission->update(['status' => 'approved']);

        // Redirect to appropriate page based on permissible type
        $route = $permission->permissible_type === Employee::class
            ? 'monitoring.permissions.employees'
            : 'monitoring.permissions.students';

        return redirect()->route($route)
            ->with('success', 'Izin berhasil disetujui.');
    }

    public function reject(Permission $permission)
    {
        $permission->update(['status' => 'rejected']);

        // Redirect to appropriate page based on permissible type
        $route = $permission->permissible_type === Employee::class
            ? 'monitoring.permissions.employees'
            : 'monitoring.permissions.students';

        return redirect()->route($route)
            ->with('success', 'Izin berhasil ditolak.');
    }

    public function createManual(Request $request)
    {
        $request->validate([
            'type' => 'required|in:employee,student',
            'id' => 'required|integer',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string|max:1000',
        ]);

        $permissibleType = $request->type === 'employee' ? Employee::class : Student::class;
        $permissible = $permissibleType::findOrFail($request->id);

        Permission::create([
            'permissible_type' => $permissibleType,
            'permissible_id' => $permissible->id,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'reason' => $request->reason,
            'status' => 'approved', // Manual entry is auto-approved
        ]);

        return redirect()->route('monitoring.permissions.index')
            ->with('success', 'Izin manual berhasil ditambahkan.');
    }
}
