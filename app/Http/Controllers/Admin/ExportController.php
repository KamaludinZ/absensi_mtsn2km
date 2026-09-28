<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Student;
use App\Models\Rombel;
use App\Models\Setting;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\AttendanceExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ExportController extends Controller
{
    public function employees()
    {
        $employees = Employee::where('is_active', true)->orderBy('full_name')->get();
        return view('admin.exports.employees', compact('employees'));
    }

    public function students()
    {
        try {
            $grades = \App\Models\Grade::orderBy('name')->get();
            $rombels = Rombel::orderBy('name')->get(['id', 'name', 'grade_id']);
            $students = Student::where('is_active', true)->orderBy('full_name')->get(['id', 'full_name', 'rombel_id']);
            return view('admin.exports.students', compact('grades', 'rombels', 'students'));
        } catch (\Exception $e) {
            Log::error('Error in ExportController@students: ' . $e->getMessage());
            Log::error($e->getTraceAsString());

            return back()->with('error', 'Terjadi kesalahan saat memuat halaman export siswa. Silakan hubungi administrator.');
        }
    }
}