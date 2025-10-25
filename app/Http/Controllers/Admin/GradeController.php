<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Grade;

class GradeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $query = Grade::query();
        
        // Sorting logic
        $sortField = request('sort', 'name');
        $sortDirection = request('direction', 'asc');

        // Validate sort direction
        if (!in_array($sortDirection, ['asc', 'desc'])) {
            $sortDirection = 'asc';
        }

        // Apply sorting based on field
        switch ($sortField) {
            case 'name':
            case 'description':
            case 'is_active':
                $query->orderBy($sortField, $sortDirection);
                break;
                
            default:
                $query->orderBy('name', $sortDirection);
                break;
        }
        
        $grades = $query->paginate(20);
        
        return view('admin.grades.index', compact('grades', 'sortField', 'sortDirection'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.grades.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:grades,name',
            'description' => 'nullable|string|max:255',
            'is_active' => 'required|boolean',
        ]);

        Grade::create($request->only(['name', 'description', 'is_active']));

        return redirect()->route('admin.grades.index')
            ->with('success', 'Tingkat kelas berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Grade $grade)
    {
        return view('admin.grades.edit', compact('grade'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Grade $grade)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:grades,name,' . $grade->id,
            'description' => 'nullable|string|max:255',
            'is_active' => 'required|boolean',
        ]);

        $grade->update($request->only(['name', 'description', 'is_active']));

        return redirect()->route('admin.grades.index')
            ->with('success', 'Tingkat kelas berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Grade $grade)
    {
        // Check if grade is associated with any rombels
        if ($grade->rombels()->count() > 0) {
            return redirect()->route('admin.grades.index')
                ->with('error', 'Tingkat kelas tidak dapat dihapus karena masih digunakan oleh rombels.');
        }

        $grade->delete();

        return redirect()->route('admin.grades.index')
            ->with('success', 'Tingkat kelas berhasil dihapus.');
    }
}
