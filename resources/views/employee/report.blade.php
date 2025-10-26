@extends('layouts.app')

@section('title', 'Laporan Absensi Saya')
@section('page-title', 'Laporan Absensi Saya')

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
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Shift</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jam Masuk</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Absensi Masuk</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Selisih Masuk</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status Masuk</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Koordinat Masuk</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Selfie Masuk</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jam Pulang</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Absensi Pulang</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Selisih Pulang</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status Pulang</th>
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
                                $color = 'text-red-600 font-semibold';
                                $label = '+' . $formatWaktu;
                            } elseif ($selisihDetik < 0) {
                                // Absensi masuk < jam masuk (lebih awal)
                                $color = 'text-green-600 font-semibold';
                                $label = '-' . $formatWaktu;
                            } else {
                                // Tepat waktu
                                $color = 'text-green-600 font-semibold';
                                $label = '00:00:00';
                            }
                        @endphp
                        <span class="{{ $color }}">{{ $label }}</span>
                    @else
                        -
                    @endif
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
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
                <td class="px-6 py-4 whitespace-nowrap">
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
                                $color = 'text-red-600 font-semibold';
                                $label = '-' . $formatWaktu;
                            } elseif ($selisihDetik > 0) {
                                // Absensi pulang > Jam pulang = Pulang Lebih Lama
                                $color = 'text-green-600 font-semibold';
                                $label = '+' . $formatWaktu;
                            } else {
                                // Tepat waktu
                                $color = 'text-green-600 font-semibold';
                                $label = '00:00:00';
                            }
                        @endphp
                        <span class="{{ $color }}">{{ $label }}</span>
                    @else
                        -
                    @endif
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
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
