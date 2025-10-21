<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AcademicYear;

class AcademicYearController extends Controller
{
    public function index()
    {
        $academicYears = AcademicYear::orderBy('year_name', 'desc')->paginate(20);
        return view('admin.academic-years.index', compact('academicYears'));
    }
    
    public function create()
    {
        return view('admin.academic-years.create');
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'year_name' => 'required|string|max:255',
            'status' => 'required|in:active,inactive',
        ]);

        // If setting as active, deactivate all others
        if ($request->status === 'active') {
            AcademicYear::where('status', 'active')->update(['status' => 'inactive']);
        }

        AcademicYear::create($request->only(['year_name', 'status']));

        return redirect()->route('admin.academic-years.index')
            ->with('success', 'Tahun pelajaran berhasil ditambahkan.');
    }
    
    public function edit(AcademicYear $academicYear)
    {
        return view('admin.academic-years.edit', compact('academicYear'));
    }

    public function update(Request $request, AcademicYear $academicYear)
    {
        $request->validate([
            'year_name' => 'required|string|max:255',
            'status' => 'required|in:active,inactive',
        ]);

        // If setting as active, deactivate all others
        if ($request->status === 'active') {
            AcademicYear::where('id', '!=', $academicYear->id)
                ->where('status', 'active')
                ->update(['status' => 'inactive']);
        }

        $academicYear->update($request->only(['year_name', 'status']));

        return redirect()->route('admin.academic-years.index')
            ->with('success', 'Tahun pelajaran berhasil diperbarui.');
    }

    public function destroy(AcademicYear $academicYear)
    {
        $academicYear->delete();

        return redirect()->route('admin.academic-years.index')
            ->with('success', 'Tahun pelajaran berhasil dihapus.');
    }
}
