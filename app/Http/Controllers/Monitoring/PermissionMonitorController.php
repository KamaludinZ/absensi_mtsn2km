<?php

namespace App\Http\Controllers\Monitoring;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Permission;
use App\Models\Employee;
use App\Models\Student;

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

    public function approve(Permission $permission)
    {
        $permission->update(['status' => 'approved']);

        return redirect()->route('monitoring.permissions.index')
            ->with('success', 'Izin berhasil disetujui.');
    }

    public function reject(Permission $permission)
    {
        $permission->update(['status' => 'rejected']);

        return redirect()->route('monitoring.permissions.index')
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
