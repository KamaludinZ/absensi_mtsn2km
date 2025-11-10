@extends('layouts.app')

@section('title', 'Laporan Absensi Saya')
@section('page-title', 'Laporan Absensi Saya')

@push('styles')
<style>
    .sortable-header {
        cursor: pointer;
        user-select: none;
    }
    .sortable-header:hover {
        background-color: rgba(0, 0, 0, 0.02);
    }
</style>
@endpush

@php
    // Helper function to generate sort URL
    function getSortUrl($field, $currentSort, $currentDirection) {
        $newDirection = 'asc';
        if ($currentSort === $field && $currentDirection === 'asc') {
            $newDirection = 'desc';
        }

        $params = array_merge(request()->query(), [
            'sort' => $field,
            'direction' => $newDirection
        ]);

        return request()->url() . '?' . http_build_query($params);
    }

    // Helper function to get sort icon
    function getSortIcon($field, $currentSort, $currentDirection) {
        if ($currentSort !== $field) {
            return '<svg class="w-4 h-4 inline-block ml-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
            </svg>';
        }

        if ($currentDirection === 'asc') {
            return '<svg class="w-4 h-4 inline-block ml-1 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
            </svg>';
        }

        return '<svg class="w-4 h-4 inline-block ml-1 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
        </svg>';
    }
@endphp

@section('content')
<div class="mb-6">
    <form method="GET" class="bg-white p-4 rounded-lg shadow">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label for="month" class="block text-sm font-medium text-gray-700 mb-1">Bulan</label>
                <select name="month" id="month" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Semua Bulan</option>
                    @php
                        // Always default to current month if no month is specified in request
                        $currentMonth = now()->month;
                        $selectedMonth = request('month') ? request('month') : $currentMonth;
                    @endphp
                    @foreach($months as $value => $name)
                        <option value="{{ $value }}" {{ $selectedMonth == $value ? 'selected' : '' }}>
                            {{ $name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="academic_year_id" class="block text-sm font-medium text-gray-700 mb-1">Tahun Pelajaran</label>
                <select name="academic_year_id" id="academic_year_id" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Semua Tahun Pelajaran</option>
                    @foreach($academicYears as $academicYear)
                        <option value="{{ $academicYear->id }}" {{ $selectedAcademicYearId == $academicYear->id ? 'selected' : '' }}>
                            {{ $academicYear->year_name }} {{ $academicYear->status == 'active' ? '(Aktif)' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    Filter
                </button>
            </div>
        </div>
    </form>
</div>

<div class="bg-white rounded-lg shadow overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider sortable-header">
                    <a href="{{ getSortUrl('date', $sortField, $sortDirection) }}" class="flex items-center hover:text-gray-700 transition-colors">
                        Tanggal
                        {!! getSortIcon('date', $sortField, $sortDirection) !!}
                    </a>
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Shift</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jam Masuk</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider sortable-header">
                    <a href="{{ getSortUrl('clock_in', $sortField, $sortDirection) }}" class="flex items-center hover:text-gray-700 transition-colors">
                        Absensi Masuk
                        {!! getSortIcon('clock_in', $sortField, $sortDirection) !!}
                    </a>
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Selisih Masuk</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider sortable-header">
                    <a href="{{ getSortUrl('status_in', $sortField, $sortDirection) }}" class="flex items-center hover:text-gray-700 transition-colors">
                        Status Masuk
                        {!! getSortIcon('status_in', $sortField, $sortDirection) !!}
                    </a>
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Koordinat Masuk</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Selfie Masuk</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jam Pulang</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider sortable-header">
                    <a href="{{ getSortUrl('clock_out', $sortField, $sortDirection) }}" class="flex items-center hover:text-gray-700 transition-colors">
                        Absensi Pulang
                        {!! getSortIcon('clock_out', $sortField, $sortDirection) !!}
                    </a>
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Selisih Pulang</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider sortable-header">
                    <a href="{{ getSortUrl('status_out', $sortField, $sortDirection) }}" class="flex items-center hover:text-gray-700 transition-colors">
                        Status Pulang
                        {!! getSortIcon('status_out', $sortField, $sortDirection) !!}
                    </a>
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Koordinat Pulang</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Selfie Pulang</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @forelse($attendances as $attendance)
            <tr>
                <td class="px-6 py-4 whitespace-nowrap">{{ $attendance->date->format('d/m/Y') }}</td>
                <td class="px-6 py-4 whitespace-nowrap">
                    @php
                        $shiftName = null;
                        $schedule = null;

                        // Get shift from pre-loaded collection
                        $dateKey = $attendance->date->format('Y-m-d');
                        $employeeShift = $employeeShifts->get($dateKey);

                        if ($employeeShift && $employeeShift->schedule) {
                            $schedule = $employeeShift->schedule;
                            $shiftName = $schedule->shift_name;
                        } else {
                            // Get schedule from pre-loaded collection by day of week
                            $dayOfWeek = $attendance->date->dayOfWeek;
                            $schedule = $schedules->get($dayOfWeek);
                            if ($schedule) {
                                $shiftName = $schedule->shift_name;
                            }
                        }
                    @endphp
                    {{ $shiftName ?? 'N/A' }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">{{ $schedule ? \Carbon\Carbon::parse($schedule->start_time)->format('H:i:s') : '-' }}</td>
                <td class="px-6 py-4 whitespace-nowrap">{{ $attendance->clock_in ? $attendance->clock_in->format('H:i:s') : '-' }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap">
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
                                                <td class="px-6 py-4 whitespace-nowrap">
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
                                                </td>                <td class="px-6 py-4 whitespace-nowrap">
                    @if($attendance->clock_in && $attendance->clock_in_latitude && $attendance->clock_in_longitude)
                        <div class="text-sm">
                            <p>{{ number_format($attendance->clock_in_latitude, 6) }}, {{ number_format($attendance->clock_in_longitude, 6) }}</p>
                            <p class="text-xs text-gray-500">±{{ number_format($attendance->clock_in_accuracy ?? 0, 0) }}m</p>
                        </div>
                    @else
                        <span>-</span>
                    @endif
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
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
                <td class="px-6 py-4 whitespace-nowrap">{{ $schedule ? \Carbon\Carbon::parse($schedule->end_time)->format('H:i:s') : '-' }}</td>
                <td class="px-6 py-4 whitespace-nowrap">{{ $attendance->clock_out ? $attendance->clock_out->format('H:i:s') : '-' }}</td>
                <td class="px-6 py-4 whitespace-nowrap">
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
                <td class="px-6 py-4 whitespace-nowrap">
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
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    @if($attendance->clock_out && $attendance->clock_out_latitude && $attendance->clock_out_longitude)
                        <div class="text-sm">
                            <p>{{ number_format($attendance->clock_out_latitude, 6) }}, {{ number_format($attendance->clock_out_longitude, 6) }}</p>
                            <p class="text-xs text-gray-500">±{{ number_format($attendance->clock_out_accuracy ?? 0, 0) }}m</p>
                        </div>
                    @else
                        <span>-</span>
                    @endif
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
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
                <td colspan="12" class="px-6 py-4 text-center text-gray-500">Tidak ada data absensi</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-6 flex justify-end space-x-4">
    <a href="{{ route('employee.attendance.report.export', array_merge(request()->query(), ['format' => 'pdf'])) }}" 
       class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
        Export PDF
    </a>
    <a href="{{ route('employee.attendance.report.export', array_merge(request()->query(), ['format' => 'excel'])) }}" 
       class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
        Export Excel
    </a>
</div>

<div class="mt-4">
    {{ $attendances->links() }}
</div>
@endsection
