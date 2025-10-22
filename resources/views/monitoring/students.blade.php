@extends('layouts.app')
@section('title', 'Monitor Siswa')
@section('page-title', 'Monitor Kehadiran Siswa - Hari Ini')

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="bg-white rounded-lg shadow p-6">
        <!-- Summary Statistics -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-blue-50 p-4 rounded-lg">
                <div class="text-2xl font-bold text-blue-700">{{ $totalStudents }}</div>
                <div class="text-sm text-gray-600">Total Siswa</div>
            </div>
            <div class="bg-green-50 p-4 rounded-lg">
                <div class="text-2xl font-bold text-green-700">{{ $presentCount }}</div>
                <div class="text-sm text-gray-600">Hadir Hari Ini</div>
            </div>
            <div class="bg-yellow-50 p-4 rounded-lg">
                <div class="text-2xl font-bold text-yellow-700">{{ $totalStudents - $presentCount }}</div>
                <div class="text-sm text-gray-600">Belum Hadir</div>
            </div>
            <div class="bg-purple-50 p-4 rounded-lg">
                <div class="text-2xl font-bold text-purple-700">{{ now()->translatedFormat('d F Y') }}</div>
                <div class="text-sm text-gray-600">Tanggal Monitoring</div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="mb-6 p-4 bg-gray-50 rounded-lg">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
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
                    <label class="block text-sm font-medium text-gray-700 mb-1">Rombel</label>
                    <select name="rombel_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Semua Rombel</option>
                        @foreach($rombels as $rombel)
                            <option value="{{ $rombel->id }}" {{ request('rombel_id') == $rombel->id ? 'selected' : '' }}>
                                {{ $rombel->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Siswa</label>
                    <input type="text" name="student_name" value="{{ request('student_name') }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="Cari nama siswa...">
                </div>

                <div class="md:col-span-4 flex justify-end">
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 mr-2">
                        Filter
                    </button>
                    <a href="{{ route('monitoring.students') }}" class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- Attendance Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white border border-gray-200">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="py-3 px-4 border-b text-left text-xs font-medium text-gray-500 uppercase">Nama Siswa</th>
                        <th class="py-3 px-4 border-b text-left text-xs font-medium text-gray-500 uppercase">NISN</th>
                        <th class="py-3 px-4 border-b text-left text-xs font-medium text-gray-500 uppercase">Rombel</th>
                        <th class="py-3 px-4 border-b text-left text-xs font-medium text-gray-500 uppercase">Jam Masuk</th>
                        <th class="py-3 px-4 border-b text-left text-xs font-medium text-gray-500 uppercase">Absensi Masuk</th>
                        <th class="py-3 px-4 border-b text-left text-xs font-medium text-gray-500 uppercase">Selisih</th>
                        <th class="py-3 px-4 border-b text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="py-3 px-4 border-b text-left text-xs font-medium text-gray-500 uppercase">Selfie Masuk</th>
                        <th class="py-3 px-4 border-b text-left text-xs font-medium text-gray-500 uppercase">Jam Pulang</th>
                        <th class="py-3 px-4 border-b text-left text-xs font-medium text-gray-500 uppercase">Absensi Pulang</th>
                        <th class="py-3 px-4 border-b text-left text-xs font-medium text-gray-500 uppercase">Selisih</th>
                        <th class="py-3 px-4 border-b text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="py-3 px-4 border-b text-left text-xs font-medium text-gray-500 uppercase">Selfie Pulang</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($attendances as $attendance)
                    <tr class="hover:bg-gray-50">
                        <td class="py-3 px-4 border-b whitespace-nowrap">
                            {{ $attendance->attendable->full_name ?? 'N/A' }}
                        </td>
                        <td class="py-3 px-4 border-b whitespace-nowrap">
                            {{ $attendance->attendable->nisn ?? '-' }}
                        </td>
                        <td class="py-3 px-4 border-b whitespace-nowrap">
                            {{ $attendance->attendable->rombel->name ?? '-' }}
                        </td>
                        <td class="py-3 px-4 border-b whitespace-nowrap">
                            @php
                                $dayOfWeek = $attendance->date->dayOfWeek;
                                $schedule = \App\Models\Schedule::where('type', 'siswa')
                                    ->where('day_of_week', $dayOfWeek)
                                    ->first();
                            @endphp
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
                        <td class="py-3 px-4 border-b">
                            @if($attendance->selfie_path_in)
                                <a href="{{ Storage::url($attendance->selfie_path_in) }}" target="_blank">
                                    <img src="{{ Storage::url($attendance->selfie_path_in) }}" alt="Selfie Masuk" class="w-12 h-12 object-cover rounded border cursor-pointer hover:opacity-80">
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
                        <td class="py-3 px-4 border-b">
                            @if($attendance->selfie_path_out)
                                <a href="{{ Storage::url($attendance->selfie_path_out) }}" target="_blank">
                                    <img src="{{ Storage::url($attendance->selfie_path_out) }}" alt="Selfie Pulang" class="w-12 h-12 object-cover rounded border cursor-pointer hover:opacity-80">
                                </a>
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="13" class="py-4 px-4 text-center text-gray-500">Belum ada data kehadiran hari ini</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

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
