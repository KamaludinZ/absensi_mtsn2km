@extends('layouts.app')
@section('title', 'Laporan Absensi Pegawai')
@section('page-title', 'Laporan Absensi Pegawai')

@php
    // Helper function to generate sortable column header
    function sortableLink($label, $sortField, $currentSort, $currentDirection) {
        $newDirection = 'asc';
        $icon = '';

        if ($currentSort == $sortField) {
            // If this column is currently sorted
            $newDirection = $currentDirection == 'asc' ? 'desc' : 'asc';
            $icon = $currentDirection == 'asc' ? ' ↑' : ' ↓';
        }

        // Build query string preserving filters
        $params = request()->except(['sort', 'direction', 'page']);
        $params['sort'] = $sortField;
        $params['direction'] = $newDirection;

        $url = request()->url() . '?' . http_build_query($params);

        $class = $currentSort == $sortField ? 'text-blue-600 font-semibold' : 'text-gray-700 hover:text-blue-600';

        return '<a href="' . $url . '" class="' . $class . '">' . $label . $icon . '</a>';
    }

    $currentSort = request('sort', 'date');
    $currentDirection = request('direction', 'desc');
@endphp

@section('content')
<div class="max-w-6xl mx-auto">
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-bold">Laporan Absensi Pegawai</h2>
        </div>
        
        <!-- Filter Form -->
        <form method="GET" class="mb-6 p-4 bg-gray-50 rounded-lg">
            <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-2">Tanggal Mulai</label>
                    <input type="date" name="start_date" value="{{ request('start_date') }}" class="w-full px-4 py-2 border rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2">Tanggal Selesai</label>
                    <input type="date" name="end_date" value="{{ request('end_date') }}" class="w-full px-4 py-2 border rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2">Bulan</label>
                    <select name="month" class="w-full px-4 py-2 border rounded-lg">
                        <option value="">Semua Bulan</option>
                        @foreach($months as $value => $name)
                            <option value="{{ $value }}" {{ request('month') == $value ? 'selected' : '' }}>
                                {{ $name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2">Tahun</label>
                    <select name="year" class="w-full px-4 py-2 border rounded-lg">
                        <option value="">Semua Tahun</option>
                        @foreach($years as $year)
                            <option value="{{ $year }}" {{ request('year') == $year ? 'selected' : '' }}>
                                {{ $year }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2">Pegawai</label>
                    <select name="employee_id" class="w-full px-4 py-2 border rounded-lg">
                        <option value="">Semua Pegawai</option>
                        @foreach($employees as $employee)
                        <option value="{{ $employee->id }}" {{ request('employee_id') == $employee->id ? 'selected' : '' }}>
                            {{ $employee->full_name }} ({{ $employee->position }})
                        </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2">&nbsp;</label>
                    <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700">Filter</button>
                </div>
            </div>
        </form>

        <!-- Summary Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-6 gap-4 mb-6">
            <div class="bg-blue-50 p-4 rounded-lg">
                <div class="text-2xl font-bold">{{ $summary['total_attendances'] }}</div>
                <div class="text-sm text-gray-600">Total Absensi</div>
            </div>
            <div class="bg-green-50 p-4 rounded-lg">
                <div class="text-2xl font-bold">{{ $summary['present'] }}</div>
                <div class="text-sm text-gray-600">Hadir</div>
            </div>
            <div class="bg-yellow-50 p-4 rounded-lg">
                <div class="text-2xl font-bold">{{ $summary['absent'] }}</div>
                <div class="text-sm text-gray-600">Tidak Hadir</div>
            </div>
            <div class="bg-red-50 p-4 rounded-lg">
                <div class="text-2xl font-bold">{{ $summary['late'] }}</div>
                <div class="text-sm text-gray-600">Terlambat</div>
            </div>
            <div class="bg-orange-50 p-4 rounded-lg">
                <div class="text-2xl font-bold">{{ $summary['early_departure'] }}</div>
                <div class="text-sm text-gray-600">Pulang Awal</div>
            </div>
            <div class="bg-purple-50 p-4 rounded-lg">
                <div class="text-2xl font-bold">{{ $summary['permissions'] }}</div>
                <div class="text-sm text-gray-600">Perizinan</div>
            </div>
        </div>

        <!-- Attendance Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white border border-gray-200">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="py-3 px-4 border-b text-left">{!! sortableLink('Nama Pegawai', 'full_name', $currentSort, $currentDirection) !!}</th>
                        <th class="py-3 px-4 border-b text-left">{!! sortableLink('Jabatan', 'position', $currentSort, $currentDirection) !!}</th>
                        <th class="py-3 px-4 border-b text-left">{!! sortableLink('Tanggal', 'date', $currentSort, $currentDirection) !!}</th>
                        <th class="py-3 px-4 border-b text-left">Nama Shift</th>
                        <th class="py-3 px-4 border-b text-left">Jam Masuk</th>
                        <th class="py-3 px-4 border-b text-left">Koordinat Masuk</th>
                        <th class="py-3 px-4 border-b text-left">{!! sortableLink('Absensi Masuk', 'clock_in', $currentSort, $currentDirection) !!}</th>
                        <th class="py-3 px-4 border-b text-left">Selisih Masuk</th>
                        <th class="py-3 px-4 border-b text-left">Status Masuk</th>
                        <th class="py-3 px-4 border-b text-left">Selfie Masuk</th>
                        <th class="py-3 px-4 border-b text-left">Jam Pulang</th>
                        <th class="py-3 px-4 border-b text-left">Koordinat Pulang</th>
                        <th class="py-3 px-4 border-b text-left">{!! sortableLink('Absensi Pulang', 'clock_out', $currentSort, $currentDirection) !!}</th>
                        <th class="py-3 px-4 border-b text-left">Selisih Pulang</th>
                        <th class="py-3 px-4 border-b text-left">Status Pulang</th>
                        <th class="py-3 px-4 border-b text-left">Selfie Pulang</th>
                        <th class="py-3 px-4 border-b text-left">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($attendances as $attendance)
                    <tr class="hover:bg-gray-50">
                        <td class="py-3 px-4 border-b">
                            {{ $attendance->attendable->full_name ?? 'N/A' }}
                        </td>
                        <td class="py-3 px-4 border-b">
                            {{ $attendance->attendable->position ?? 'N/A' }}
                        </td>
                        <td class="py-3 px-4 border-b">
                            {{ $attendance->date->format('d/m/Y') }}
                        </td>
                        <td class="py-3 px-4 border-b">
                            @php
                                $shiftName = null;
                                $schedule = null;
                                
                                // Try to get shift from employee_shifts table
                                $employeeShift = \App\Models\EmployeeShift::where('employee_id', $attendance->attendable_id)
                                    ->where('date', $attendance->date)
                                    ->with('schedule')
                                    ->first();
                                    
                                if ($employeeShift && $employeeShift->schedule) {
                                    $schedule = $employeeShift->schedule;
                                    $shiftName = $schedule->shift_name;
                                } else {
                                    // Try to get schedule by day of week
                                    $dayOfWeek = $attendance->date->dayOfWeek;
                                    $schedule = \App\Models\Schedule::where('type', 'pegawai')
                                        ->where('day_of_week', $dayOfWeek)
                                        ->first();
                                    if ($schedule) {
                                        $shiftName = $schedule->shift_name;
                                    }
                                }
                            @endphp
                            {{ $shiftName ?? 'N/A' }}
                        </td>
                        <td class="py-3 px-4 border-b">
                            {{ $schedule ? \Carbon\Carbon::parse($schedule->start_time)->format('H:i:s') : '-' }}
                        </td>
                        <td class="py-3 px-4 border-b">
                            @if($attendance->clock_in_latitude && $attendance->clock_in_longitude)
                                <a href="https://www.google.com/maps?q={{ $attendance->clock_in_latitude }},{{ $attendance->clock_in_longitude }}" 
                                   target="_blank" 
                                   title="Lihat lokasi di peta">
                                    {{ number_format($attendance->clock_in_latitude, 6) }}, {{ number_format($attendance->clock_in_longitude, 6) }}
                                </a>
                            @else
                                -
                            @endif
                        </td>
                        <td class="py-3 px-4 border-b">
                            {{ $attendance->clock_in ? $attendance->clock_in->format('H:i:s') : '-' }}
                        </td>
                                                                                                <td class="py-3 px-4 border-b">
                                                                                                    @if($attendance->clock_in && $schedule)
                                                                                                        @php
                                                                                                            $clockIn = \Carbon\Carbon::parse($attendance->clock_in);
                                                                                                            $startTime = \Carbon\Carbon::parse($schedule->start_time)->setDate($clockIn->year, $clockIn->month, $clockIn->day);
                                                                                                            $diffInSeconds = $clockIn->diffInSeconds($startTime, false);
                                                                        
                                                                                                            $absDiff = abs($diffInSeconds);
                                                                                                            $formattedValue = ($timeDiffFormat === 'minutes')
                                                                                                                ? ceil($absDiff / 60) . ' menit'
                                                                                                                : sprintf('%02d:%02d:%02d', floor($absDiff / 3600), floor(($absDiff % 3600) / 60), $absDiff % 60);
                                                                        
                                                                                                            // Based on user's observation, a late clock-in produces a negative diff.
                                                                                                            // So we check for < 0 to mean LATE and display '+'.
                                                                                                            if ($diffInSeconds > 0) { // This block now represents ON-TIME or EARLY
                                                                                                                $selisihColor = 'text-green-600 font-semibold';
                                                                                                                $selisihLabel = '-' . $formattedValue;
                                                                                                            } else { // This block now represents LATE or EXACTLY ON TIME
                                                                                                                $selisihColor = 'text-red-600 font-semibold';
                                                                                                                $selisihLabel = '+' . $formattedValue;
                                                                                                            }
                                                                                                        @endphp
                                                                                                        <span class="{{ $selisihColor }}">{{ $selisihLabel }}</span>
                                                                                                    @else
                                                                                                        -
                                                                                                    @endif
                                                                                                </td>
                                                                                                <td class="py-3 px-4 border-b">
                                                                                                    @if($attendance->clock_in && $schedule)
                                                                                                        @php
                                                                                                            $clockIn = \Carbon\Carbon::parse($attendance->clock_in);
                                                                                                            $startTime = \Carbon\Carbon::parse($schedule->start_time)->setDate($clockIn->year, $clockIn->month, $clockIn->day);
                                                                                                            $diffInSeconds = $clockIn->diffInSeconds($startTime, false);
                                                                        
                                                                                                            // Based on user's observation, a late clock-in produces a negative diff.
                                                                                                            if ($diffInSeconds > 0) { // This is ON-TIME or EARLY
                                                                                                                $statusText = 'Tepat Waktu';
                                                                                                                $statusColor = 'bg-green-100 text-green-800';
                                                                                                            } else { // This is LATE
                                                                                                                $statusText = 'Terlambat';
                                                                                                                $statusColor = 'bg-red-100 text-red-800';
                                                                                                            }
                                                                                                        @endphp
                                                                                                        <span class="px-2 py-1 rounded text-xs {{ $statusColor }}">
                                                                                                            {{ $statusText }}
                                                                                                        </span>
                                                                                                    @else
                                                                                                        <span class="px-2 py-1 rounded text-xs bg-gray-100 text-gray-800">
                                                                                                            {{ $attendance->clock_in ? 'N/A' : 'Tidak Hadir' }}
                                                                                                        </span>
                                                                                                    @endif
                                                                                                </td>                        <td class="py-3 px-4 border-b">
                            @if($attendance->selfie_path_in)
                                @php
                                    $filename = basename($attendance->selfie_path_in);
                                    $type = 'employee';
                                @endphp
                                <a href="{{ route('selfies.show', ['type' => $type, 'filename' => $filename]) }}" target="_blank">
                                    <img src="{{ route('selfies.show', ['type' => $type, 'filename' => $filename]) }}" alt="Selfie Masuk" class="w-16 h-16 object-cover rounded border cursor-pointer hover:opacity-80">
                                </a>
                            @else
                                -
                            @endif
                        </td>
                        <td class="py-3 px-4 border-b">
                            {{ $schedule ? \Carbon\Carbon::parse($schedule->end_time)->format('H:i:s') : '-' }}
                        </td>
                        <td class="py-3 px-4 border-b">
                            @if($attendance->clock_out_latitude && $attendance->clock_out_longitude)
                                <a href="https://www.google.com/maps?q={{ $attendance->clock_out_latitude }},{{ $attendance->clock_out_longitude }}" 
                                   target="_blank" 
                                   title="Lihat lokasi di peta">
                                    {{ number_format($attendance->clock_out_latitude, 6) }}, {{ number_format($attendance->clock_out_longitude, 6) }}
                                </a>
                            @else
                                -
                            @endif
                        </td>
                        <td class="py-3 px-4 border-b">
                            {{ $attendance->clock_out ? $attendance->clock_out->format('H:i:s') : '-' }}
                        </td>
                                                                                                <td class="py-3 px-4 border-b">
                                                                                                    @if($attendance->clock_out && $schedule)
                                                                                                        @php
                                                                                                            $clockOut = \Carbon\Carbon::parse($attendance->clock_out);
                                                                                                            $endTime = \Carbon\Carbon::parse($schedule->end_time)->setDate($clockOut->year, $clockOut->month, $clockOut->day);
                                                                                                            // Calculation: Actual Time - Scheduled Time
                                                                                                            $diffInSeconds = $clockOut->diffInSeconds($endTime, false);
                                                                        
                                                                                                            $absDiff = abs($diffInSeconds);
                                                                                                            $formattedValue = ($timeDiffFormat === 'minutes')
                                                                                                                ? ceil($absDiff / 60) . ' menit'
                                                                                                                : sprintf('%02d:%02d:%02d', floor($absDiff / 3600), floor(($absDiff % 3600) / 60), $absDiff % 60);
                                                                        
                                                                                                            // Based on debug output, a positive diff means EARLY departure
                                                                                                            if ($diffInSeconds > 0) { // Early Departure
                                                                                                                $selisihColor = 'text-red-600 font-semibold';
                                                                                                                $selisihLabel = '-' . $formattedValue;
                                                                                                            } else { // On Time or Late Departure
                                                                                                                $selisihColor = 'text-green-600 font-semibold';
                                                                                                                $selisihLabel = '+' . $formattedValue;
                                                                                                            }
                                                                                                        @endphp
                                                                                                        <span class="{{ $selisihColor }}">{{ $selisihLabel }}</span>
                                                                                                    @else
                                                                                                        -
                                                                                                    @endif
                                                                                                </td>
                                                                                                <td class="py-3 px-4 border-b">
                                                                                                    @if($attendance->clock_out && $schedule)
                                                                                                        @php
                                                                                                            $clockOut = \Carbon\Carbon::parse($attendance->clock_out);
                                                                                                            $endTime = \Carbon\Carbon::parse($schedule->end_time)->setDate($clockOut->year, $clockOut->month, $clockOut->day);
                                                                                                            // Calculation: Actual Time - Scheduled Time
                                                                                                            $diffInSeconds = $clockOut->diffInSeconds($endTime, false);
                                                                        
                                                                                                            // Based on debug output, a positive diff means EARLY departure
                                                                                                            if ($diffInSeconds > 0) { // Early Departure
                                                                                                                $statusText = 'Pulang Awal';
                                                                                                                $statusColor = 'bg-red-100 text-red-800';
                                                                                                            } else { // On Time or Late Departure
                                                                                                                $statusText = 'Tepat Waktu';
                                                                                                                $statusColor = 'bg-green-100 text-green-800';
                                                                                                            }
                                                                                                        @endphp
                                                                                                        <span class="px-2 py-1 rounded text-xs {{ $statusColor }}">
                                                                                                            {{ $statusText }}
                                                                                                        </span>
                                                                                                    @else
                                                                                                        <span class="px-2 py-1 rounded text-xs bg-gray-100 text-gray-800">
                                                                                                            {{ $attendance->clock_out ? 'N/A' : 'Belum Pulang' }}
                                                                                                        </span>
                                                                                                    @endif
                                                                                                </td>                        <td class="py-3 px-4 border-b">
                            @if($attendance->selfie_path_out)
                                @php
                                    $filename = basename($attendance->selfie_path_out);
                                    $type = 'employee';
                                @endphp
                                <a href="{{ route('selfies.show', ['type' => $type, 'filename' => $filename]) }}" target="_blank">
                                    <img src="{{ route('selfies.show', ['type' => $type, 'filename' => $filename]) }}" alt="Selfie Pulang" class="w-16 h-16 object-cover rounded border cursor-pointer hover:opacity-80">
                                </a>
                            @else
                                -
                            @endif
                        </td>
                        <td class="py-3 px-4 border-b">
                            <div class="flex space-x-2">
                                <a href="{{ route('admin.employees.show', $attendance->attendable_id) }}" class="text-blue-600 hover:underline">Detail</a>
                                <a href="{{ route('admin.attendances.edit', $attendance->id) }}" class="text-yellow-600 hover:underline">Edit</a>
                                <form action="{{ route('admin.attendances.destroy', $attendance->id) }}" method="POST" class="inline" onsubmit="return confirm('Yakin hapus data absensi ini?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:underline">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="13" class="py-4 px-4 text-center text-gray-500">Tidak ada data absensi ditemukan</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Custom Pagination with per_page selector -->
        @if ($attendances instanceof \Illuminate\Pagination\LengthAwarePaginator)
            {{ $attendances->appends(request()->query())->links('vendor.pagination.tailwind-custom') }}
        @else
            <!-- For "Show All" records -->
            <div class="mt-6">
                <div class="flex flex-col md:flex-row items-center justify-between space-y-4 md:space-y-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <label for="per_page" class="text-sm text-gray-700">Tampilkan:</label>
                        <select id="per_page" name="per_page" 
                                class="border border-gray-300 rounded px-2 py-1 text-sm focus:ring-blue-500 focus:border-blue-500 w-auto min-w-[80px]"
                                onchange="updatePerPage(this)">
                            <option value="10" {{ request('per_page') == '10' ? 'selected' : '' }}>10</option>
                            <option value="20" {{ !request('per_page') || request('per_page') == '20' ? 'selected' : '' }}>20</option>
                            <option value="30" {{ request('per_page') == '30' ? 'selected' : '' }}>30</option>
                            <option value="50" {{ request('per_page') == '50' ? 'selected' : '' }}>50</option>
                            <option value="100" {{ request('per_page') == '100' ? 'selected' : '' }}>100</option>
                            <option value="all" selected>Semua</option>
                        </select>
                        <span class="text-sm text-gray-700">entri</span>
                    </div>
                </div>
            </div>
            
            <script>
                function updatePerPage(selectElement) {
                    const perPage = selectElement.value;
                    const url = new URL(window.location);
                    const params = new URLSearchParams(url.search);
                    
                    if (perPage && perPage !== 'all') {
                        params.set('per_page', perPage);
                    } else {
                        params.delete('per_page');
                    }
                    
                    // Reset to first page when changing per_page
                    params.delete('page');
                    
                    url.search = params.toString();
                    window.location.href = url.toString();
                }
            </script>
        @endif
    </div>
</div>
@endsection