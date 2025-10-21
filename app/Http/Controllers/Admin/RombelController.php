<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Rombel;
use App\Models\AcademicYear;

class RombelController extends Controller
{
    public function index()
    {
        $rombels = Rombel::with('academicYear', 'grade')->orderBy('name')->paginate(20);
        $grades = \App\Models\Grade::orderBy('name')->get();
        $academicYears = AcademicYear::orderBy('year_name')->get();
        return view('admin.rombels.index', compact('rombels', 'grades', 'academicYears'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'grade_id' => 'required|exists:grades,id',
            'name' => 'required|string|max:255',
        ]);

        Rombel::create($request->only(['academic_year_id', 'grade_id', 'name']));

        return redirect()->route('admin.rombels.index')
            ->with('success', 'Rombel berhasil ditambahkan.');
    }

    public function update(Request $request, Rombel $rombel)
    {
        $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'grade_id' => 'required|exists:grades,id',
            'name' => 'required|string|max:255',
        ]);

        $rombel->update($request->only(['academic_year_id', 'grade_id', 'name']));

        return redirect()->route('admin.rombels.index')
            ->with('success', 'Rombel berhasil diperbarui.');
    }
    
    public function edit(Rombel $rombel)
    {
        return response()->json([
            'id' => $rombel->id,
            'academic_year_id' => $rombel->academic_year_id,
            'grade_id' => $rombel->grade_id,
            'name' => $rombel->name,
        ]);
    }

    public function destroy(Rombel $rombel)
    {
        $rombel->delete();

        return redirect()->route('admin.rombels.index')
            ->with('success', 'Rombel berhasil dihapus.');
    }

    /**
     * Show import form
     */
    public function importForm()
    {
        $grades = \App\Models\Grade::orderBy('name')->get();
        $academicYears = AcademicYear::orderBy('year_name')->get();
        return view('admin.rombels.import', compact('grades', 'academicYears'));
    }

    /**
     * Import rombels from Excel
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:2048',
            'academic_year_id' => 'required|exists:academic_years,id',
            'grade_id' => 'required|exists:grades,id',
        ], [
            'file.required' => 'File harus diupload',
            'file.max' => 'Ukuran file maksimal 2MB',
        ]);

        try {
            $file = $request->file('file');

            // Validasi ekstensi file secara manual
            $extension = strtolower($file->getClientOriginalExtension());
            if (!in_array($extension, ['xlsx', 'xls', 'csv'])) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'File harus berformat Excel (.xlsx, .xls) atau CSV (.csv)');
            }

            $rows = [];

            // Read the Excel file
            $fileType = \PhpOffice\PhpSpreadsheet\IOFactory::identify($file->getPathname());
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($fileType);
            $spreadsheet = $reader->load($file->getPathname());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            // Skip header row and process data
            $createdCount = 0;
            $skippedCount = 0;
            
            foreach ($rows as $index => $row) {
                if ($index === 0) continue; // Skip header row

                if (empty($row[0])) continue; // Skip empty rows
                
                // Create rombel
                Rombel::create([
                    'academic_year_id' => $request->academic_year_id,
                    'grade_id' => $request->grade_id,
                    'name' => trim($row[0])
                ]);
                $createdCount++;
            }

            return redirect()->route('admin.rombels.index')
                ->with('success', "Berhasil mengimpor {$createdCount} rombel.");
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal mengimpor data: ' . $e->getMessage());
        }
    }

    /**
     * Download Excel template for rombel import
     */
    public function downloadTemplate()
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename=rombel_template.csv',
        ];

        $content = "Nama Rombel\n7A\n7B\n8A\n8B\n9A\n9B\n";

        return response($content, 200, $headers);
    }

    /**
     * Get rombels by grade level for student attendance selection
     * Grade is now selected by grade ID instead of name
     */
    public function getByGrade($grade)
    {
        $rombels = Rombel::where('grade_id', $grade)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($rombels);
    }
}
