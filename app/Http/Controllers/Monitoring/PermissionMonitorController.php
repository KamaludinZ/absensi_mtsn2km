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

        // Sorting logic for all permissions
        $sortField = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');

        // Validate sort direction
        if (!in_array($sortDirection, ['asc', 'desc'])) {
            $sortDirection = 'desc';
        }

        // Apply sorting based on field
        switch ($sortField) {
            case 'full_name':
                // For relationship fields, we need to sort in collection
                $permissions = $query->with('permissible')->get()->sortBy(function($permission) {
                    return strtolower($permission->permissible->full_name ?? '');
                }, SORT_REGULAR, $sortDirection === 'desc');
                
                // Manually paginate
                $perPage = 20;
                $currentPage = request()->get('page', 1);
                $permissions = new \Illuminate\Pagination\LengthAwarePaginator(
                    $permissions->forPage($currentPage, $perPage),
                    $permissions->count(),
                    $perPage,
                    $currentPage,
                    ['path' => request()->url(), 'query' => request()->query()]
                );
                break;

            case 'permissible_type':
                $permissions = $query->with('permissible')->get()->sortBy(function($permission) {
                    return strtolower($permission->permissible_type ?? '');
                }, SORT_REGULAR, $sortDirection === 'desc');
                
                // Manually paginate
                $perPage = 20;
                $currentPage = request()->get('page', 1);
                $permissions = new \Illuminate\Pagination\LengthAwarePaginator(
                    $permissions->forPage($currentPage, $perPage),
                    $permissions->count(),
                    $perPage,
                    $currentPage,
                    ['path' => request()->url(), 'query' => request()->query()]
                );
                break;

            case 'start_date':
            case 'end_date':
            case 'status':
            case 'reason':
                $query->orderBy($sortField, $sortDirection);
                $permissions = $query->paginate(20);
                break;

            case 'created_at':
            default:
                $query->orderBy('created_at', $sortDirection);
                $permissions = $query->paginate(20);
                break;
        }

        return view('monitoring.permissions.index', compact('permissions', 'sortField', 'sortDirection'));
    }

    public function employees(Request $request)
    {
        // Set default to current month and year
        $selectedMonth = $request->input('month', \Carbon\Carbon::now()->month);
        $selectedYear = $request->input('year', \Carbon\Carbon::now()->year);

        $query = Permission::where('permissible_type', Employee::class)
            ->with(['permissible']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by month and year (always applied with default values)
        $startDate = \Carbon\Carbon::create($selectedYear, $selectedMonth, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $query->where(function($q) use ($startDate, $endDate) {
            $q->whereBetween('start_date', [$startDate, $endDate])
              ->orWhereBetween('end_date', [$startDate, $endDate])
              ->orWhere(function($subQ) use ($startDate, $endDate) {
                  $subQ->where('start_date', '<=', $startDate)
                       ->where('end_date', '>=', $endDate);
              });
        });

        // Filter by employee
        if ($request->filled('employee_id')) {
            $query->where('permissible_id', $request->employee_id);
        }

        // Sorting logic for employee permissions
        $sortField = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');

        // Validate sort direction
        if (!in_array($sortDirection, ['asc', 'desc'])) {
            $sortDirection = 'desc';
        }

        // Apply sorting based on field
        switch ($sortField) {
            case 'full_name':
                // For relationship fields, we need to sort in collection
                $permissions = $query->with('permissible')->get()->sortBy(function($permission) {
                    return strtolower($permission->permissible->full_name ?? '');
                }, SORT_REGULAR, $sortDirection === 'desc');
                
                // Manually paginate
                $perPage = 20;
                $currentPage = request()->get('page', 1);
                $permissions = new \Illuminate\Pagination\LengthAwarePaginator(
                    $permissions->forPage($currentPage, $perPage),
                    $permissions->count(),
                    $perPage,
                    $currentPage,
                    ['path' => request()->url(), 'query' => request()->query()]
                );
                break;

            case 'start_date':
            case 'end_date':
            case 'status':
            case 'reason':
                $query->orderBy($sortField, $sortDirection);
                $permissions = $query->paginate(20);
                break;

            case 'created_at':
            default:
                $query->orderBy('created_at', $sortDirection);
                $permissions = $query->paginate(20);
                break;
        }

        // Get employees for filter dropdown
        $employees = Employee::orderBy('full_name')->get();

        // Prepare months and years for dropdown
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        $years = range(date('Y'), date('Y') - 5);

        return view('monitoring.permissions.employees', compact('permissions', 'employees', 'sortField', 'sortDirection', 'months', 'years', 'selectedMonth', 'selectedYear'));
    }

    public function students(Request $request)
    {
        // Set default to current month and year
        $selectedMonth = $request->input('month', \Carbon\Carbon::now()->month);
        $selectedYear = $request->input('year', \Carbon\Carbon::now()->year);

        $query = Permission::where('permissible_type', Student::class)
            ->with(['permissible.rombel']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by month and year (always applied with default values)
        $startDate = \Carbon\Carbon::create($selectedYear, $selectedMonth, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $query->where(function($q) use ($startDate, $endDate) {
            $q->whereBetween('start_date', [$startDate, $endDate])
              ->orWhereBetween('end_date', [$startDate, $endDate])
              ->orWhere(function($subQ) use ($startDate, $endDate) {
                  $subQ->where('start_date', '<=', $startDate)
                       ->where('end_date', '>=', $endDate);
              });
        });

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

        // Sorting logic for student permissions
        $sortField = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');

        // Validate sort direction
        if (!in_array($sortDirection, ['asc', 'desc'])) {
            $sortDirection = 'desc';
        }

        // Apply sorting based on field
        switch ($sortField) {
            case 'full_name':
                // For relationship fields, we need to sort in collection
                $permissions = $query->with(['permissible.rombel'])->get()->sortBy(function($permission) {
                    return strtolower($permission->permissible->full_name ?? '');
                }, SORT_REGULAR, $sortDirection === 'desc');
                
                // Manually paginate
                $perPage = 20;
                $currentPage = request()->get('page', 1);
                $permissions = new \Illuminate\Pagination\LengthAwarePaginator(
                    $permissions->forPage($currentPage, $perPage),
                    $permissions->count(),
                    $perPage,
                    $currentPage,
                    ['path' => request()->url(), 'query' => request()->query()]
                );
                break;

            case 'rombel':
                // For relationship fields, we need to sort in collection
                $permissions = $query->with(['permissible.rombel'])->get()->sortBy(function($permission) {
                    return strtolower($permission->permissible->rombel->name ?? '');
                }, SORT_REGULAR, $sortDirection === 'desc');
                
                // Manually paginate
                $perPage = 20;
                $currentPage = request()->get('page', 1);
                $permissions = new \Illuminate\Pagination\LengthAwarePaginator(
                    $permissions->forPage($currentPage, $perPage),
                    $permissions->count(),
                    $perPage,
                    $currentPage,
                    ['path' => request()->url(), 'query' => request()->query()]
                );
                break;

            case 'start_date':
            case 'end_date':
            case 'status':
            case 'reason':
                $query->orderBy($sortField, $sortDirection);
                $permissions = $query->paginate(20);
                break;

            case 'created_at':
            default:
                $query->orderBy('created_at', $sortDirection);
                $permissions = $query->paginate(20);
                break;
        }

        // Get rombels for filter dropdown
        $rombels = Rombel::orderBy('name')->get();

        // Prepare months and years for dropdown
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        $years = range(date('Y'), date('Y') - 5);

        return view('monitoring.permissions.students', compact('permissions', 'rombels', 'sortField', 'sortDirection', 'months', 'years', 'selectedMonth', 'selectedYear'));
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
