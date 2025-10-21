<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Holiday;
use Carbon\Carbon;

class HolidayController extends Controller
{
    public function index()
    {
        $month = request('month');
        $year = request('year');
        $showAll = request('all');
        
        $query = Holiday::query();
        
        // If 'all' parameter is present, show all holidays; otherwise filter by current month/year if no specific month/year provided
        if (!$showAll) {
            if (!$month) $month = now()->month;
            if (!$year) $year = now()->year;
            
            if ($month && $year) {
                $query->whereMonth('date', $month)
                      ->whereYear('date', $year);
            }
        }
        
        $holidays = $query->orderBy('date', 'desc')->paginate(20);
        
        return view('admin.holidays.index', compact('holidays', 'month', 'year'));
    }
    
    public function bulkUpdate(Request $request)
    {
        $request->validate([
            'holiday_ids' => 'required|array',
            'holiday_ids.*' => 'exists:holidays,id',
            'action' => 'required|in:activate,deactivate'
        ]);
        
        $holidays = Holiday::whereIn('id', $request->holiday_ids)->get();
        $status = $request->action === 'activate' ? true : false;
        
        foreach ($holidays as $holiday) {
            $holiday->update(['is_active' => $status]);
        }
        
        $actionText = $request->action === 'activate' ? 'diaktifkan' : 'dinonaktifkan';
        $count = count($request->holiday_ids);
        
        return redirect()->back()
            ->with('success', "{$count} hari libur berhasil {$actionText}.");
    }

    public function create()
    {
        return view('admin.holidays.create');
    }

    public function store(Request $request)
    {
        \Log::info('Holiday store request received', [
            'request_data' => $request->all(),
            'user' => auth()->user()->name
        ]);
        
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'date' => 'required|date|unique:holidays,date',
                'description' => 'nullable|string',
            ]);
            
            \Log::info('Holiday validation passed', ['validated_data' => $validatedData]);

            Holiday::create([
                'name' => $validatedData['name'],
                'date' => $validatedData['date'],
                'description' => $validatedData['description'] ?? null,
                'is_active' => $request->has('is_active') ? true : false,
            ]);
            
            \Log::info('Holiday created successfully');

            return redirect()->route('admin.holidays.index')
                ->with('success', 'Hari libur berhasil ditambahkan.');
        } catch (\Exception $e) {
            \Log::error('Error creating holiday', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()
                ->with('error', 'Terjadi kesalahan saat menyimpan hari libur: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function edit(Holiday $holiday)
    {
        return view('admin.holidays.edit', compact('holiday'));
    }

    public function update(Request $request, Holiday $holiday)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'date' => 'required|date|unique:holidays,date,' . $holiday->id,
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $holiday->update([
            'name' => $request->name,
            'date' => $request->date,
            'description' => $request->description,
            'is_active' => $request->has('is_active') ? true : false,
        ]);

        return redirect()->route('admin.holidays.index')
            ->with('success', 'Hari libur berhasil diperbarui.');
    }

    public function destroy(Holiday $holiday)
    {
        $holiday->delete();

        return redirect()->route('admin.holidays.index')
            ->with('success', 'Hari libur berhasil dihapus.');
    }
    
    public function activeDays()
    {
        // Get the saved active days setting (stored as a JSON string of day numbers: 1=Monday, 7=Sunday)
        $activeDays = json_decode(\App\Models\Setting::get('active_days', '[1,2,3,4,5]'), true); // Default to Monday-Friday
        
        $currentYear = now()->year;
        
        return view('admin.holidays.active-days', compact('activeDays', 'currentYear'));
    }
    
    public function updateActiveDays(Request $request)
    {
        $request->validate([
            'active_days' => 'array',
            'active_days.*' => 'integer|between:1,7' // 1=Monday, 7=Sunday
        ]);
        
        // Default to all days if none selected
        $activeDays = $request->input('active_days', [1,2,3,4,5]);
        
        \App\Models\Setting::set('active_days', json_encode($activeDays));
        
        return redirect()->route('admin.holidays.active-days')
            ->with('success', 'Pengaturan hari aktif berhasil disimpan.');
    }
    
    public function workingDaysReport()
    {
        // Get active days setting
        $activeDays = json_decode(\App\Models\Setting::get('active_days', '[1,2,3,4,5]'), true) ?? [1,2,3,4,5];
        
        // Get current month and year
        $currentMonth = request('month', now()->month);
        $currentYear = request('year', now()->year);
        
        // Calculate working days for the selected month
        $startDate = now()->year($currentYear)->month($currentMonth)->startOfMonth();
        $endDate = now()->year($currentYear)->month($currentMonth)->endOfMonth();
        
        // Get holidays for this month
        $holidays = Holiday::whereBetween('date', [$startDate, $endDate])
            ->where('is_active', true)
            ->get();
        
        // Calculate working days
        $workingDays = 0;
        $date = $startDate->copy();
        
        while ($date <= $endDate) {
            $dayOfWeek = (int) $date->format('N'); // 1=Monday, 7=Sunday
            
            // Check if it's an active day and not a holiday
            $isHoliday = $holidays->contains(function($holiday) use ($date) {
                return $holiday->date->format('Y-m-d') === $date->format('Y-m-d');
            });
            
            if (in_array($dayOfWeek, $activeDays) && !$isHoliday) {
                $workingDays++;
            }
            
            $date->addDay();
        }
        
        return view('admin.holidays.working-days-report', compact('workingDays', 'holidays', 'currentMonth', 'currentYear', 'activeDays'));
    }
    
    public function showImportForm()
    {
        return view('admin.holidays.import');
    }
    
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv'
        ]);
        
        try {
            \Maatwebsite\Excel\Facades\Excel::import(new \App\Imports\HolidayImport, $request->file('file'));
            
            return redirect()->route('admin.holidays.index')
                ->with('success', 'Data hari libur berhasil diimpor.');
        } catch (\Exception $e) {
            return redirect()->route('admin.holidays.import-form')
                ->with('error', 'Gagal mengimpor data: ' . $e->getMessage());
        }
    }
    
    public function downloadTemplate()
    {
        // Create a sample template file
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename=holiday_template.csv',
        ];
        
        $content = "name,date,description,is_active\n";
        $content .= "Hari Raya Idul Fitri,2025-04-10,Libur Hari Raya,1\n";
        $content .= "Hari Kemerdekaan RI,2025-08-17,Libur Nasional,1\n";
        
        return response($content, 200, $headers);
    }
}