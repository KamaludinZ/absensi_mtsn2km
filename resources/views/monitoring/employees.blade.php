@php
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

    $currentSort = $sortField ?? request('sort', 'date');
    $currentDirection = $sortDirection ?? request('direction', 'desc');
@endphp

@extends('layouts.app')
@section('title', 'Monitor Pegawai')
@section('page-title', 'Monitor Kehadiran Pegawai')

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="bg-white rounded-lg shadow p-6">
        <!-- Filter Section -->
        <div class="mb-6 p-4 bg-gray-50 rounded-lg">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-6 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Mulai</label>
                    <input type="date" name="start_date" value="{{ request('start_date') }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Selesai</label>
                    <input type="date" name="end_date" value="{{ request('end_date') }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Bulan</label>
                    <select name="month" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Semua Bulan</option>
                        @foreach($months as $value => $name)
                            <option value="{{ $value }}" {{ request('month') == $value ? 'selected' : '' }}>
                                {{ $name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tahun</label>
                    <select name="year" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Semua Tahun</option>
                        @foreach($years as $year)
                            <option value="{{ $year }}" {{ request('year') == $year ? 'selected' : '' }}>
                                {{ $year }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Pegawai</label>
                    <select name="employee_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Semua Pegawai</option>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->id }}" {{ request('employee_id') == $employee->id ? 'selected' : '' }}>
                                {{ $employee->full_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-end">
                    <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 mr-2">
                        Filter
                    </button>
                </div>

                @if(request()->hasAny(['start_date', 'end_date', 'month', 'year', 'employee_id']))
                <div class="md:col-span-6 flex justify-end">
                    <a href="{{ route('monitoring.employees') }}" class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700">
                        Reset
                    </a>
                </div>
                @endif
            </form>
        </div>

        <!-- Summary Statistics -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-blue-50 p-4 rounded-lg">
                <div class="text-2xl font-bold text-blue-700">{{ $totalEmployees }}</div>
                <div class="text-sm text-gray-600">Total Pegawai</div>
            </div>
            <div class="bg-green-50 p-4 rounded-lg">
                <div class="text-2xl font-bold text-green-700">{{ $presentCount }}</div>
                <div class="text-sm text-gray-600">Hadir Hari Ini</div>
            </div>
            <div class="bg-yellow-50 p-4 rounded-lg">
                <div class="text-2xl font-bold text-yellow-700">{{ $totalEmployees - $presentCount }}</div>
                <div class="text-sm text-gray-600">Belum Hadir</div>
            </div>
            <div class="bg-purple-50 p-4 rounded-lg">
                <div class="text-2xl font-bold text-purple-700">{{ now()->translatedFormat('d F Y') }}</div>
                <div class="text-sm text-gray-600">Tanggal Monitoring</div>
            </div>
        </div>

        <!-- Attendance Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white border border-gray-200">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="py-3 px-4 border-b text-left text-xs font-medium text-gray-500 uppercase">{!! sortableLink('Tanggal', 'date', $currentSort, $currentDirection) !!}</th>
                        <th class="py-3 px-4 border-b text-left text-xs font-medium text-gray-500 uppercase">{!! sortableLink('Nama Pegawai', 'full_name', $currentSort, $currentDirection) !!}</th>
                        <th class="py-3 px-4 border-b text-left text-xs font-medium text-gray-500 uppercase">{!! sortableLink('Jabatan', 'position', $currentSort, $currentDirection) !!}</th>
                        <th class="py-3 px-4 border-b text-left text-xs font-medium text-gray-500 uppercase">Nama Shift</th>
                        <th class="py-3 px-4 border-b text-left text-xs font-medium text-gray-500 uppercase">{!! sortableLink('Jam Masuk', 'clock_in', $currentSort, $currentDirection) !!}</th>
                        <th class="py-3 px-4 border-b text-left text-xs font-medium text-gray-500 uppercase">Absensi Masuk</th>
                        <th class="py-3 px-4 border-b text-left text-xs font-medium text-gray-500 uppercase">Koordinat Masuk</th>
                        <th class="py-3 px-4 border-b text-left text-xs font-medium text-gray-500 uppercase">Selisih</th>
                        <th class="py-3 px-4 border-b text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="py-3 px-4 border-b text-left text-xs font-medium text-gray-500 uppercase">Selfie Masuk</th>
                        <th class="py-3 px-4 border-b text-left text-xs font-medium text-gray-500 uppercase">{!! sortableLink('Jam Pulang', 'clock_out', $currentSort, $currentDirection) !!}</th>
                        <th class="py-3 px-4 border-b text-left text-xs font-medium text-gray-500 uppercase">Absensi Pulang</th>
                        <th class="py-3 px-4 border-b text-left text-xs font-medium text-gray-500 uppercase">Koordinat Pulang</th>
                        <th class="py-3 px-4 border-b text-left text-xs font-medium text-gray-500 uppercase">Selisih</th>
                        <th class="py-3 px-4 border-b text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="py-3 px-4 border-b text-left text-xs font-medium text-gray-500 uppercase">Selfie Pulang</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($attendances as $attendance)
                    <tr class="hover:bg-gray-50">
                        <td class="py-3 px-4 border-b whitespace-nowrap">
                            {{ $attendance->date->format('d/m/Y') }}
                        </td>
                        <td class="py-3 px-4 border-b whitespace-nowrap">
                            {{ $attendance->attendable->full_name ?? 'N/A' }}
                        </td>
                        <td class="py-3 px-4 border-b whitespace-nowrap">
                            {{ $attendance->attendable->position ?? 'N/A' }}
                        </td>
                        <td class="py-3 px-4 border-b whitespace-nowrap">
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
                        <td class="py-3 px-4 border-b whitespace-nowrap">
                            {{ $schedule ? \Carbon\Carbon::parse($schedule->start_time)->format('H:i:s') : '-' }}
                        </td>
                        <td class="py-3 px-4 border-b whitespace-nowrap">
                            {{ $attendance->clock_in ? $attendance->clock_in->format('H:i:s') : '-' }}
                        </td>
                        <td class="py-3 px-4 border-b whitespace-nowrap">
                            @if($attendance->clock_in && $schedule)
                                @php
                                    $absensiMasukTime = \Carbon\Carbon::parse($attendance->clock_in)->format('H:i:s');
                                    list($h1, $i1, $s1) = explode(':', $absensiMasukTime);
                                    $absensiMasukDetik = ($h1 * 3600) + ($i1 * 60) + $s1;

                                    $jamMasukTime = \Carbon\Carbon::parse($schedule->start_time)->format('H:i:s');
                                    list($h2, $i2, $s2) = explode(':', $jamMasukTime);
                                    $jamMasukDetik = ($h2 * 3600) + ($i2 * 60) + $s2;

                                    $selisihDetik = $absensiMasukDetik - $jamMasukDetik;
                                    $selisihAbs = abs($selisihDetik);

                                    $jam = floor($selisihAbs / 3600);
                                    $sisaSetelahJam = $selisihAbs - ($jam * 3600);
                                    $menit = floor($sisaSetelahJam / 60);
                                    $detik = $sisaSetelahJam - ($menit * 60);

                                    $formatWaktu = sprintf('%02d:%02d:%02d', $jam, $menit, $detik);

                                    if ($selisihDetik > 0) {
                                        $warna = 'text-red-600 font-semibold';
                                        $label = '+' . $formatWaktu;
                                    } elseif ($selisihDetik < 0) {
                                        $warna = 'text-green-600 font-semibold';
                                        $label = '-' . $formatWaktu;
                                    } else {
                                        $warna = 'text-green-600 font-semibold';
                                        $label = '00:00:00';
                                    }
                                @endphp
                                <span class="{{ $warna }}">{{ $label }}</span>
                            @else
                                -
                            @endif
                        </td>
                        <td class="py-3 px-4 border-b whitespace-nowrap">
                            @if($attendance->clock_in && $schedule)
                                @php
                                    $absensiMasukTime = \Carbon\Carbon::parse($attendance->clock_in)->format('H:i:s');
                                    $jamMasukTime = \Carbon\Carbon::parse($schedule->start_time)->format('H:i:s');

                                    if ($absensiMasukTime > $jamMasukTime) {
                                        $statusText = 'Terlambat';
                                        $statusColor = 'bg-red-100 text-red-800';
                                    } else {
                                        $statusText = 'Tepat Waktu';
                                        $statusColor = 'bg-green-100 text-green-800';
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
                        </td>
                        <td class="py-3 px-4 border-b whitespace-nowrap">
                            @if($attendance->clock_in_latitude && $attendance->clock_in_longitude)
                                <div class="text-sm">
                                    <p>{{ number_format($attendance->clock_in_latitude, 6) }}, {{ number_format($attendance->clock_in_longitude, 6) }}</p>
                                    <p class="text-xs text-gray-500">±{{ number_format($attendance->clock_in_accuracy ?? 0, 0) }}m</p>
                                </div>
                            @else
                                <span>-</span>
                            @endif
                        </td>
                        <td class="py-3 px-4 border-b">
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
                        <td class="py-3 px-4 border-b whitespace-nowrap">
                            {{ $schedule ? \Carbon\Carbon::parse($schedule->end_time)->format('H:i:s') : '-' }}
                        </td>
                        <td class="py-3 px-4 border-b whitespace-nowrap">
                            {{ $attendance->clock_out ? $attendance->clock_out->format('H:i:s') : '-' }}
                        </td>
                        <td class="py-3 px-4 border-b whitespace-nowrap">
                            @if($attendance->clock_out && $schedule)
                                @php
                                    $absensiPulangTime = \Carbon\Carbon::parse($attendance->clock_out)->format('H:i:s');
                                    list($h1, $i1, $s1) = explode(':', $absensiPulangTime);
                                    $absensiPulangDetik = ($h1 * 3600) + ($i1 * 60) + $s1;

                                    $jamPulangTime = \Carbon\Carbon::parse($schedule->end_time)->format('H:i:s');
                                    list($h2, $i2, $s2) = explode(':', $jamPulangTime);
                                    $jamPulangDetik = ($h2 * 3600) + ($i2 * 60) + $s2;

                                    $selisihDetik = $absensiPulangDetik - $jamPulangDetik;
                                    $selisihAbs = abs($selisihDetik);

                                    $jam = floor($selisihAbs / 3600);
                                    $sisaSetelahJam = $selisihAbs - ($jam * 3600);
                                    $menit = floor($sisaSetelahJam / 60);
                                    $detik = $sisaSetelahJam - ($menit * 60);

                                    $formatWaktu = sprintf('%02d:%02d:%02d', $jam, $menit, $detik);

                                    if ($selisihDetik < 0) {
                                        $warna = 'text-red-600 font-semibold';
                                        $label = '-' . $formatWaktu;
                                    } elseif ($selisihDetik > 0) {
                                        $warna = 'text-green-600 font-semibold';
                                        $label = '+' . $formatWaktu;
                                    } else {
                                        $warna = 'text-green-600 font-semibold';
                                        $label = '00:00:00';
                                    }
                                @endphp
                                <span class="{{ $warna }}">{{ $label }}</span>
                            @else
                                -
                            @endif
                        </td>
                        <td class="py-3 px-4 border-b whitespace-nowrap">
                            @if($attendance->clock_out && $schedule)
                                @php
                                    $absensiPulangTime = \Carbon\Carbon::parse($attendance->clock_out)->format('H:i:s');
                                    $jamPulangTime = \Carbon\Carbon::parse($schedule->end_time)->format('H:i:s');

                                    if ($absensiPulangTime < $jamPulangTime) {
                                        $statusText = 'Pulang Awal';
                                        $statusColor = 'bg-yellow-100 text-yellow-800';
                                    } else {
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
                        </td>
                        <td class="py-3 px-4 border-b whitespace-nowrap">
                            @if($attendance->clock_out_latitude && $attendance->clock_out_longitude)
                                <div class="text-sm">
                                    <p>{{ number_format($attendance->clock_out_latitude, 6) }}, {{ number_format($attendance->clock_out_longitude, 6) }}</p>
                                    <p class="text-xs text-gray-500">±{{ number_format($attendance->clock_out_accuracy ?? 0, 0) }}m</p>
                                </div>
                            @else
                                <span>-</span>
                            @endif
                        </td>
                        <td class="py-3 px-4 border-b">
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
                    </tr>
                    @empty
                    <tr>
                        <td colspan="16" class="py-4 px-4 text-center text-gray-500">Belum ada data kehadiran hari ini</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination Controls -->
        @if(isset($paginated) && $paginated && $attendances->hasPages())
        <div class="mt-6 flex flex-col sm:flex-row items-center justify-between gap-4 bg-gray-50 p-4 rounded-lg">
            <div class="text-sm text-gray-600">
                Menampilkan {{ $attendances->firstItem() }} sampai {{ $attendances->lastItem() }} dari {{ $attendances->total() }} data
            </div>
            
            <div class="flex items-center space-x-2">
                <form method="GET" class="flex items-center space-x-2">
                    @if(request()->has('sort'))
                        <input type="hidden" name="sort" value="{{ request('sort') }}">
                    @endif
                    @if(request()->has('direction'))
                        <input type="hidden" name="direction" value="{{ request('direction') }}">
                    @endif
                    @if(request()->has('employee_id'))
                        <input type="hidden" name="employee_id" value="{{ request('employee_id') }}">
                    @endif
                    @if(request()->has('start_date'))
                        <input type="hidden" name="start_date" value="{{ request('start_date') }}">
                    @endif
                    @if(request()->has('end_date'))
                        <input type="hidden" name="end_date" value="{{ request('end_date') }}">
                    @endif
                    
                    <label for="per_page" class="text-sm text-gray-700">Tampilkan:</label>
                    <select name="per_page" id="per_page" onchange="this.form.submit()" 
                            class="border border-gray-300 rounded px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="10" {{ request('per_page') == 10 ? 'selected' : '' }}>10</option>
                        <option value="20" {{ request('per_page') == 20 ? 'selected' : '' }}>20</option>
                        <option value="30" {{ request('per_page') == 30 ? 'selected' : '' }}>30</option>
                        <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                        <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100</option>
                        <option value="all" {{ request('per_page') == 'all' ? 'selected' : '' }}>Semua</option>
                    </select>
                    <span class="text-sm text-gray-700">data per halaman</span>
                </form>
            </div>
            
            <div class="pagination-wrapper">
                {{ $attendances->appends(request()->except('page'))->links() }}
            </div>
        </div>
        @elseif(isset($paginated) && !$paginated && count($attendances) > 0)
        <div class="mt-6 flex flex-col sm:flex-row items-center justify-between gap-4 bg-gray-50 p-4 rounded-lg">
            <div class="text-sm text-gray-600">
                Menampilkan semua {{ count($attendances) }} data (tanpa pagination)
            </div>
            
            <div class="flex items-center space-x-2">
                <form method="GET" class="flex items-center space-x-2">
                    @if(request()->has('sort'))
                        <input type="hidden" name="sort" value="{{ request('sort') }}">
                    @endif
                    @if(request()->has('direction'))
                        <input type="hidden" name="direction" value="{{ request('direction') }}">
                    @endif
                    @if(request()->has('employee_id'))
                        <input type="hidden" name="employee_id" value="{{ request('employee_id') }}">
                    @endif
                    @if(request()->has('start_date'))
                        <input type="hidden" name="start_date" value="{{ request('start_date') }}">
                    @endif
                    @if(request()->has('end_date'))
                        <input type="hidden" name="end_date" value="{{ request('end_date') }}">
                    @endif
                    
                    <label for="per_page" class="text-sm text-gray-700">Tampilkan:</label>
                    <select name="per_page" id="per_page" onchange="this.form.submit()" 
                            class="border border-gray-300 rounded px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="10">10</option>
                        <option value="20">20</option>
                        <option value="30">30</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                        <option value="all" selected>Semua</option>
                    </select>
                    <span class="text-sm text-gray-700">data per halaman</span>
                </form>
            </div>
        </div>
        @endif

        @if($attendances->count() > 0)
        <div class="mt-4 text-sm text-gray-600">
            <p><strong>Keterangan:</strong></p>
            <ul class="list-disc list-inside mt-2 space-y-1">
                <li><span class="text-green-600 font-semibold">Hijau</span> = Tepat waktu / Lebih awal</li>
                <li><span class="text-red-600 font-semibold">Merah</span> = Terlambat / Pulang awal</li>
                <li>Selisih dengan tanda <strong>+</strong> = Terlambat, tanda <strong>-</strong> = Lebih awal</li>
            </ul>
        </div>
        @endif
    </div>
</div>
@endsection
