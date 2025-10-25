<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Schedule;
use Carbon\Carbon;

class ScheduleController extends Controller
{
    public function index()
    {
        $query = Schedule::query();
        
        // Apply filters if provided
        if (request('type')) {
            $query->where('type', request('type'));
        }
        
        if (request('shift_type')) {
            $query->where('shift_type', request('shift_type'));
        }
        
        if (request('day_of_week') !== null) {
            $query->where('day_of_week', request('day_of_week'));
        }
        
        $schedules = $query->orderBy('type')->orderBy('day_of_week')->paginate(20);
        
        return view('admin.schedules.index', compact('schedules'));
    }
    
    public function pegawai()
    {
        $query = Schedule::where('type', 'pegawai');
        
        // Apply filters if provided
        if (request('shift_type')) {
            $query->where('shift_type', request('shift_type'));
        }
        
        if (request('day_of_week') !== null) {
            $query->where('day_of_week', request('day_of_week'));
        }
        
        // Sorting logic
        $sortField = request('sort', 'day_of_week');
        $sortDirection = request('direction', 'asc');

        // Validate sort direction
        if (!in_array($sortDirection, ['asc', 'desc'])) {
            $sortDirection = 'asc';
        }

        // Apply sorting based on field
        switch ($sortField) {
            case 'type':
            case 'shift_type':
            case 'shift_name':
            case 'start_time':
            case 'end_time':
                $query->orderBy($sortField, $sortDirection);
                break;
                
            case 'day_of_week':
            default:
                $query->orderBy('day_of_week', $sortDirection);
                break;
        }
        
        $schedules = $query->paginate(20);
        
        return view('admin.schedules.index', compact('schedules', 'sortField', 'sortDirection'));
    }
    
    public function siswa()
    {
        $query = Schedule::where('type', 'siswa');
        
        // Apply filters if provided
        if (request('shift_type')) {
            $query->where('shift_type', request('shift_type'));
        }
        
        if (request('day_of_week') !== null) {
            $query->where('day_of_week', request('day_of_week'));
        }
        
        // Sorting logic
        $sortField = request('sort', 'day_of_week');
        $sortDirection = request('direction', 'asc');

        // Validate sort direction
        if (!in_array($sortDirection, ['asc', 'desc'])) {
            $sortDirection = 'asc';
        }

        // Apply sorting based on field
        switch ($sortField) {
            case 'type':
            case 'shift_type':
            case 'shift_name':
            case 'start_time':
            case 'end_time':
                $query->orderBy($sortField, $sortDirection);
                break;
                
            case 'day_of_week':
            default:
                $query->orderBy('day_of_week', $sortDirection);
                break;
        }
        
        $schedules = $query->paginate(20);
        
        return view('admin.schedules.index', compact('schedules', 'sortField', 'sortDirection'));
    }
    
    public function create()
    {
        return view('admin.schedules.create');
    }
    
    public function edit(Schedule $schedule)
    {
        return view('admin.schedules.edit', compact('schedule'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|in:pegawai,siswa',
            'shift_type' => 'nullable|in:pagi,siang,sore,malam',
            'shift_name' => 'nullable|string|max:255',
            'day_of_week' => 'nullable|integer|min:0|max:6',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
        ], [
            // Custom error messages
            'end_time.after' => 'Jam selesai harus setelah jam mulai.',
        ]);

        // Special handling for night shifts
        $startTime = $request->start_time;
        $endTime = $request->end_time;
        
        // If shift type is 'malam' and end time is less than start time, it's a valid night shift
        // For other shifts, validate that end time is after start time
        if ($request->shift_type !== 'malam' && $endTime <= $startTime) {
            return redirect()->back()
                ->withErrors(['end_time' => 'Jam selesai harus setelah jam mulai.'])
                ->withInput();
        }

        // Prepare the data to save, setting default day if not provided
        $data = $request->only(['type', 'shift_type', 'shift_name', 'start_time', 'end_time']);
        $data['day_of_week'] = $request->day_of_week ?? 0; // Default to 0 (Sunday) if not provided

        Schedule::create($data);

        return redirect()->route('admin.schedules.custom.index')
            ->with('success', 'Jadwal berhasil ditambahkan.');
    }

    public function update(Request $request, Schedule $schedule)
    {
        $request->validate([
            'type' => 'required|in:pegawai,siswa',
            'shift_type' => 'nullable|in:pagi,siang,sore,malam',
            'shift_name' => 'nullable|string|max:255',
            'day_of_week' => 'nullable|integer|min:0|max:6',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
        ], [
            // Custom error messages
            'end_time.after' => 'Jam selesai harus setelah jam mulai.',
        ]);

        // Special handling for night shifts
        $startTime = $request->start_time;
        $endTime = $request->end_time;
        
        // If shift type is 'malam' and end time is less than start time, it's a valid night shift
        // For other shifts, validate that end time is after start time
        if ($request->shift_type !== 'malam' && $endTime <= $startTime) {
            return redirect()->back()
                ->withErrors(['end_time' => 'Jam selesai harus setelah jam mulai.'])
                ->withInput();
        }

        // Prepare the data to save, setting default day if not provided
        $data = $request->only(['type', 'shift_type', 'shift_name', 'start_time', 'end_time']);
        $data['day_of_week'] = $request->day_of_week ?? $schedule->day_of_week; // Keep existing value if not provided

        $schedule->update($data);

        return redirect()->route('admin.schedules.custom.index')
            ->with('success', 'Jadwal berhasil diperbarui.');
    }

    public function destroy(Schedule $schedule)
    {
        $schedule->delete();

        return redirect()->route('admin.schedules.custom.index')
            ->with('success', 'Jadwal berhasil dihapus.');
    }


}
