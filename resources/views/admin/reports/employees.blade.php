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
                        <th class="py-3 px-4 border-b text-left">{!! sortableLink('Absensi Masuk', 'clock_in', $currentSort, $currentDirection) !!}</th>
                        <th class="py-3 px-4 border-b text-left">Selisih Masuk</th>
                        <th class="py-3 px-4 border-b text-left">Status Masuk</th>
                        <th class="py-3 px-4 border-b text-left">Selfie Masuk</th>
                        <th class="py-3 px-4 border-b text-left">Jam Pulang</th>
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
                            {{ $attendance->clock_in ? $attendance->clock_in->format('H:i:s') : '-' }}
                        </td>
                        <td class="py-3 px-4 border-b">
                            @if($attendance->clock_in && $schedule)
                                @php
                                    // Ambil waktu ABSENSI MASUK dalam format H:i:s
                                    $absensiMasukTime = \Carbon\Carbon::parse($attendance->clock_in)->format('H:i:s');
                                    list($h1, $i1, $s1) = explode(':', $absensiMasukTime);
                                    $absensiMasukDetik = ($h1 * 3600) + ($i1 * 60) + $s1;

                                    // Ambil waktu JAM MASUK dalam format H:i:s
                                    $jamMasukTime = \Carbon\Carbon::parse($schedule->start_time)->format('H:i:s');
                                    list($h2, $i2, $s2) = explode(':', $jamMasukTime);
                                    $jamMasukDetik = ($h2 * 3600) + ($i2 * 60) + $s2;

                                    // Hitung selisih: ABSENSI MASUK - JAM MASUK
                                    $selisihDetik = $absensiMasukDetik - $jamMasukDetik;

                                    // Convert ke format H:i:s
                                    $selisihAbs = abs($selisihDetik);

                                    $jam = floor($selisihAbs / 3600);
                                    $sisaSetelahJam = $selisihAbs - ($jam * 3600);
                                    $menit = floor($sisaSetelahJam / 60);
                                    $detik = $sisaSetelahJam - ($menit * 60);

                                    $formatWaktu = sprintf('%02d:%02d:%02d', $jam, $menit, $detik);

                                    if ($selisihDetik > 0) {
                                        // Absensi masuk > jam masuk (terlambat)
                                        $warna = 'text-red-600 font-semibold';
                                        $label = '+' . $formatWaktu;
                                    } elseif ($selisihDetik < 0) {
                                        // Absensi masuk < jam masuk (lebih awal)
                                        $warna = 'text-green-600 font-semibold';
                                        $label = '-' . $formatWaktu;
                                    } else {
                                        // Tepat waktu
                                        $warna = 'text-green-600 font-semibold';
                                        $label = '00:00:00';
                                    }
                                @endphp
                                <span class="{{ $warna }}">{{ $label }}</span>
                            @else
                                -
                            @endif
                        </td>
                        <td class="py-3 px-4 border-b">
                            @if($attendance->clock_in && $schedule)
                                @php
                                    // Ambil waktu saja untuk perbandingan
                                    $absensiMasukTime = \Carbon\Carbon::parse($attendance->clock_in)->format('H:i:s');
                                    $jamMasukTime = \Carbon\Carbon::parse($schedule->start_time)->format('H:i:s');

                                    // Bandingkan string waktu
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
                        <td class="py-3 px-4 border-b">
                            @if($attendance->selfie_path_in)
                                <a href="{{ asset('storage/' . $attendance->selfie_path_in) }}" target="_blank" class="text-blue-600 hover:underline">Foto Masuk</a>
                            @else
                                -
                            @endif
                        </td>
                        <td class="py-3 px-4 border-b">
                            {{ $schedule ? \Carbon\Carbon::parse($schedule->end_time)->format('H:i:s') : '-' }}
                        </td>
                        <td class="py-3 px-4 border-b">
                            {{ $attendance->clock_out ? $attendance->clock_out->format('H:i:s') : '-' }}
                        </td>
                        <td class="py-3 px-4 border-b">
                            @if($attendance->clock_out && $schedule)
                                @php
                                    // Ambil waktu ABSENSI PULANG dalam format H:i:s
                                    $absensiPulangTime = \Carbon\Carbon::parse($attendance->clock_out)->format('H:i:s');
                                    list($h1, $i1, $s1) = explode(':', $absensiPulangTime);
                                    $absensiPulangDetik = ($h1 * 3600) + ($i1 * 60) + $s1;

                                    // Ambil waktu JAM PULANG dalam format H:i:s
                                    $jamPulangTime = \Carbon\Carbon::parse($schedule->end_time)->format('H:i:s');
                                    list($h2, $i2, $s2) = explode(':', $jamPulangTime);
                                    $jamPulangDetik = ($h2 * 3600) + ($i2 * 60) + $s2;

                                    // Hitung selisih: ABSENSI PULANG - JAM PULANG
                                    $selisihDetik = $absensiPulangDetik - $jamPulangDetik;

                                    // Convert ke format H:i:s
                                    $selisihAbs = abs($selisihDetik);

                                    $jam = floor($selisihAbs / 3600);
                                    $sisaSetelahJam = $selisihAbs - ($jam * 3600);
                                    $menit = floor($sisaSetelahJam / 60);
                                    $detik = $sisaSetelahJam - ($menit * 60);

                                    $formatWaktu = sprintf('%02d:%02d:%02d', $jam, $menit, $detik);

                                    if ($selisihDetik < 0) {
                                        // Absensi pulang < Jam pulang = Pulang Awal
                                        $warna = 'text-red-600 font-semibold';
                                        $label = '-' . $formatWaktu;
                                    } elseif ($selisihDetik > 0) {
                                        // Absensi pulang > Jam pulang = Pulang Lebih Lama
                                        $warna = 'text-green-600 font-semibold';
                                        $label = '+' . $formatWaktu;
                                    } else {
                                        // Tepat waktu
                                        $warna = 'text-green-600 font-semibold';
                                        $label = '00:00:00';
                                    }
                                @endphp
                                <span class="{{ $warna }}">{{ $label }}</span>
                            @else
                                -
                            @endif
                        </td>
                        <td class="py-3 px-4 border-b">
                            @if($attendance->clock_out && $schedule)
                                @php
                                    // Ambil waktu saja untuk perbandingan
                                    $absensiPulangTime = \Carbon\Carbon::parse($attendance->clock_out)->format('H:i:s');
                                    $jamPulangTime = \Carbon\Carbon::parse($schedule->end_time)->format('H:i:s');

                                    // Bandingkan string waktu
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
                        <td class="py-3 px-4 border-b">
                            @if($attendance->selfie_path_out)
                                <a href="{{ asset('storage/' . $attendance->selfie_path_out) }}" target="_blank" class="text-blue-600 hover:underline">Foto Pulang</a>
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

        <!-- Pagination -->
        @if($attendances->hasPages())
        <div class="mt-6">
            {{ $attendances->appends(request()->query())->links() }}
        </div>
        @endif
    </div>
</div>
@endsection