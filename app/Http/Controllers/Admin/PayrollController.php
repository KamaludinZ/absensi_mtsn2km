<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Payroll;
use App\Models\Employee;

class PayrollController extends Controller
{
    public function index(Request $request)
    {
        $query = Payroll::with('employee');

        if ($request->filled('month')) {
            $query->where('month', $request->month);
        }

        if ($request->filled('year')) {
            $query->where('year', $request->year);
        }

        $payrolls = $query->orderBy('year', 'desc')->orderBy('month', 'desc')->paginate(20);

        return view('admin.payrolls.index', compact('payrolls'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020',
            'base_salary' => 'required|numeric|min:0',
            'deductions' => 'nullable|numeric|min:0',
            'bonuses' => 'nullable|numeric|min:0',
        ]);

        Payroll::create([
            'employee_id' => $request->employee_id,
            'month' => $request->month,
            'year' => $request->year,
            'base_salary' => $request->base_salary,
            'deductions' => $request->deductions ?? 0,
            'bonuses' => $request->bonuses ?? 0,
        ]);

        return redirect()->route('admin.payrolls.index')
            ->with('success', 'Data gaji berhasil ditambahkan.');
    }

    public function update(Request $request, Payroll $payroll)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020',
            'base_salary' => 'required|numeric|min:0',
            'deductions' => 'nullable|numeric|min:0',
            'bonuses' => 'nullable|numeric|min:0',
        ]);

        $payroll->update([
            'employee_id' => $request->employee_id,
            'month' => $request->month,
            'year' => $request->year,
            'base_salary' => $request->base_salary,
            'deductions' => $request->deductions ?? 0,
            'bonuses' => $request->bonuses ?? 0,
        ]);

        return redirect()->route('admin.payrolls.index')
            ->with('success', 'Data gaji berhasil diperbarui.');
    }

    public function destroy(Payroll $payroll)
    {
        $payroll->delete();

        return redirect()->route('admin.payrolls.index')
            ->with('success', 'Data gaji berhasil dihapus.');
    }
}
