@extends('layouts.app')

@section('title', 'Laporan Absensi Siswa')
@section('page-title', 'Laporan Absensi Siswa')
@section('container-class', '')

@php
    // Helper function to generate sortable column header
    $currentSort = request('sort', 'date');
    $currentDirection = request('direction', 'asc');
@endphp

@section('content')
<div class="w-full px-4 sm:px-6 py-6">
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="p-4">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-bold text-gray-800">Rekap Absensi Siswa</h2>
        <a href="{{ route('admin.attendances.create-student') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
            + Tambah Absensi Siswa
        </a>
    </div>
    
    <!-- Filter Section -->
    <div class="mb-6 p-4 bg-gray-50 rounded-lg">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Bulan</label>
                <select name="month" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Pilih Bulan</option>
                    @foreach($months as $value => $name)
                        <option value="{{ $value }}" {{ request('month', now()->month) == $value ? 'selected' : '' }}>
                            {{ $name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tahun</label>
                <select name="year" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Pilih Tahun</option>
                    @foreach($years as $year)
                        <option value="{{ $year }}" {{ request('year', now()->year) == $year ? 'selected' : '' }}>
                            {{ $year }}
                        </option>
                    @endforeach
                </select>
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
                <a href="{{ route('admin.reports.students') }}" class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700">
                    Reset
                </a>
            </div>
        </form>
    </div>
    
    <!-- Attendance Records Table -->
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{!! sortableLink('Tanggal', 'date', $currentSort, $currentDirection) !!}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{!! sortableLink('Nama Siswa', 'full_name', $currentSort, $currentDirection) !!}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{!! sortableLink('Rombel', 'rombel', $currentSort, $currentDirection) !!}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jam Masuk</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{!! sortableLink('Absensi Masuk', 'clock_in', $currentSort, $currentDirection) !!}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Selisih Masuk</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status Masuk</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jam Pulang</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{!! sortableLink('Absensi Pulang', 'clock_out', $currentSort, $currentDirection) !!}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Selisih Pulang</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status Pulang</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Koordinat Masuk</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Koordinat Pulang</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Foto Masuk</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Foto Pulang</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($attendances as $attendance)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">
                        {{ \Carbon\Carbon::parse($attendance->date)->format('d/m/Y') }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        {{ $attendance->attendable->full_name ?? 'N/A' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        {{ $attendance->rombel_name ?? '-' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @php
                            $dayOfWeek = \Carbon\Carbon::parse($attendance->date)->dayOfWeek;
                            $schedule = \App\Models\Schedule::where('type', 'siswa')
                                ->where('day_of_week', $dayOfWeek)
                                ->first();
                        @endphp
                        {{ $schedule ? \Carbon\Carbon::parse($schedule->start_time)->format('H:i:s') : '-' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        {{ $attendance->clock_in ? \Carbon\Carbon::parse($attendance->clock_in)->format('H:i:s') : '-' }}
                    </td>
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
                        {{ $schedule ? \Carbon\Carbon::parse($schedule->end_time)->format('H:i:s') : '-' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        {{ $attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i:s') : '-' }}
                    </td>
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
                        @if($attendance->clock_in_latitude && $attendance->clock_in_longitude)
                            <div class="text-xs">
                                <div>Lat: {{ $attendance->clock_in_latitude }}</div>
                                <div>Lng: {{ $attendance->clock_in_longitude }}</div>
                                @if($attendance->clock_in_accuracy)
                                    <div>Ak: {{ $attendance->clock_in_accuracy }}m</div>
                                @endif
                            </div>
                        @else
                            -
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($attendance->clock_out_latitude && $attendance->clock_out_longitude)
                            <div class="text-xs">
                                <div>Lat: {{ $attendance->clock_out_latitude }}</div>
                                <div>Lng: {{ $attendance->clock_out_longitude }}</div>
                                @if($attendance->clock_out_accuracy)
                                    <div>Ak: {{ $attendance->clock_out_accuracy }}m</div>
                                @endif
                            </div>
                        @else
                            -
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($attendance->selfie_path_in)
                            @php
                                $filename = basename($attendance->selfie_path_in);
                                $type = 'student';
                            @endphp
                            <a href="{{ route('selfies.show', ['type' => $type, 'filename' => $filename]) }}" target="_blank">
                                <img src="{{ route('selfies.show', ['type' => $type, 'filename' => $filename]) }}" alt="Selfie Masuk" class="w-16 h-16 object-cover rounded border cursor-pointer hover:opacity-80">
                            </a>
                        @else
                            -
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($attendance->selfie_path_out)
                            @php
                                $filename = basename($attendance->selfie_path_out);
                                $type = 'student';
                            @endphp
                            <a href="{{ route('selfies.show', ['type' => $type, 'filename' => $filename]) }}" target="_blank">
                                <img src="{{ route('selfies.show', ['type' => $type, 'filename' => $filename]) }}" alt="Selfie Pulang" class="w-16 h-16 object-cover rounded border cursor-pointer hover:opacity-80">
                            </a>
                        @else
                            -
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($attendance->clock_in && $attendance->clock_out)
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                Hadir
                            </span>
                        @elseif($attendance->clock_in)
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                Masuk Tidak Pulang
                            </span>
                        @else
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                Tidak Hadir
                            </span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <div class="flex flex-wrap items-center gap-1">
                            <a href="{{ route('admin.students.show', $attendance->attendable_id) }}"
                               class="inline-flex items-center justify-center px-2 py-1 text-xs font-medium text-blue-600 bg-blue-50 rounded hover:bg-blue-100 transition-colors min-h-[24px]">
                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                                Detail
                            </a>
                            <a href="{{ route('admin.attendances.edit', $attendance->id) }}"
                               class="inline-flex items-center justify-center px-2 py-1 text-xs font-medium text-yellow-600 bg-yellow-50 rounded hover:bg-yellow-100 transition-colors min-h-[24px]">
                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                                Edit
                            </a>
                            <form action="{{ route('admin.attendances.destroy', $attendance->id) }}" method="POST" class="inline" onsubmit="return confirm('Yakin hapus data absensi ini?');">
                                @csrf @method('DELETE')
                                <button type="submit"
                                        class="inline-flex items-center justify-center px-2 py-1 text-xs font-medium text-red-600 bg-red-50 rounded hover:bg-red-100 transition-colors min-h-[24px]">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                    Hapus
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="17" class="px-6 py-4 text-center text-gray-500">
                        Tidak ada data absensi
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <!-- Custom Pagination with per_page selector -->
    @if (isset($paginated) && $paginated)
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
</div>
@endsection