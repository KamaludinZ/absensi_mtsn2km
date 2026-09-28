<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ScheduleController extends Controller
{
    /** Carbon dayOfWeek numbers, Monday first */
    private const DAYS = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 0 => 'Minggu'];

    public function index()
    {
        // Student schedules are managed on their own page (siswa)
        $query = Schedule::where('type', 'pegawai');
        
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
    
    /**
     * Student attendance schedule: per school day the time students must be in
     * (late after this) and the time they go home. Separate from the employee
     * shifts; the student attendance page and the student recap use it.
     */
    public function siswa()
    {
        $schedules = Schedule::where('type', 'siswa')->orderBy('id')->get()->groupBy('day_of_week');

        $days = collect(self::DAYS)->map(function ($name, $day) use ($schedules) {
            $schedule = $schedules->get($day)?->first();

            return [
                'day' => $day,
                'name' => $name,
                'active' => (bool) $schedule,
                'start_time' => $schedule ? Carbon::parse($schedule->start_time)->format('H:i') : '07:00',
                'end_time' => $schedule ? Carbon::parse($schedule->end_time)->format('H:i') : '14:00',
            ];
        });

        return view('admin.schedules.siswa', compact('days'));
    }

    public function updateSiswa(Request $request)
    {
        $request->validate([
            'days' => 'required|array',
            'days.*.active' => 'nullable|boolean',
            'days.*.start_time' => 'nullable|date_format:H:i',
            'days.*.end_time' => 'nullable|date_format:H:i',
        ]);

        $errors = [];
        foreach (self::DAYS as $day => $name) {
            $input = $request->input("days.$day", []);
            if (empty($input['active'])) {
                continue;
            }
            if (empty($input['start_time']) || empty($input['end_time'])) {
                $errors["days.$day.start_time"] = "Jam masuk dan jam pulang hari $name wajib diisi.";
            } elseif ($input['end_time'] <= $input['start_time']) {
                $errors["days.$day.end_time"] = "Jam pulang hari $name harus setelah jam masuk.";
            }
        }
        if ($errors) {
            return back()->withErrors($errors)->withInput();
        }

        DB::transaction(function () use ($request) {
            foreach (self::DAYS as $day => $name) {
                $input = $request->input("days.$day", []);
                $existing = Schedule::where('type', 'siswa')->where('day_of_week', $day)->orderBy('id')->get();

                if (empty($input['active'])) {
                    Schedule::whereIn('id', $existing->pluck('id'))->delete();
                    continue;
                }

                $data = [
                    'start_time' => $input['start_time'],
                    'end_time' => $input['end_time'],
                    'shift_type' => null,
                    'shift_name' => null,
                ];

                if ($schedule = $existing->first()) {
                    $schedule->update($data);
                    // One schedule per day: remove leftovers from the old shared form
                    Schedule::whereIn('id', $existing->skip(1)->pluck('id'))->delete();
                } else {
                    Schedule::create($data + ['type' => 'siswa', 'day_of_week' => $day]);
                }
            }
        });

        return redirect()->route('admin.schedules.custom.siswa')
            ->with('success', 'Jadwal absensi siswa berhasil disimpan.');
    }

    public function create()
    {
        return view('admin.schedules.create');
    }
    
    public function edit(Schedule $schedule)
    {
        if ($schedule->type === 'siswa') {
            return redirect()->route('admin.schedules.custom.siswa');
        }

        return view('admin.schedules.edit', compact('schedule'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|in:pegawai',
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
            'type' => 'required|in:pegawai',
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
