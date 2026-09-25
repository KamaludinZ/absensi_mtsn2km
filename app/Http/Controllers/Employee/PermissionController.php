<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Permission;
use App\Models\Employee;

class PermissionController extends Controller
{
    public function index()
    {
        $employee = auth()->user()->employee;

        if (!$employee) {
            return redirect()->back()->with('error', 'Data pegawai tidak ditemukan.');
        }

        $permissions = Permission::where('permissible_type', Employee::class)
            ->where('permissible_id', $employee->id)
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('employee.permission.index', compact('permissions'));
    }

    public function create()
    {
        return view('employee.permission.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string|max:1000',
            'supporting_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png,gif|max:500', // max 500KB
        ]);

        $employee = auth()->user()->employee;

        if (!$employee) {
            return redirect()->back()->with('error', 'Data pegawai tidak ditemukan.');
        }

        // Handle supporting document upload
        $supportingDocumentPath = null;
        if ($request->hasFile('supporting_document')) {
            $file = $request->file('supporting_document');
            $supportingDocumentPath = $file->store('permissions', 'public');
        }

        Permission::create([
            'permissible_type' => Employee::class,
            'permissible_id' => $employee->id,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'reason' => $request->reason,
            'supporting_document' => $supportingDocumentPath,
            'status' => 'pending',
        ]);

        return redirect()->route('employee.permission.index')
            ->with('success', 'Pengajuan izin berhasil dikirim. Mohon menunggu persetujuan.');
    }
}
