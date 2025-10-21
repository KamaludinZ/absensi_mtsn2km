@extends('layouts.app')

@section('title', 'Dashboard Pegawai')
@section('page-title', 'Dashboard Pegawai')

@section('content')
<div class="bg-white rounded-lg shadow p-6 mb-6">
    <h2 class="text-xl font-bold text-gray-800 mb-2">Dashboard Pegawai</h2>
    <p class="text-gray-600">Halo, {{ auth()->user()->name }}! Gunakan menu di sisi kiri untuk mengakses fitur absensi dan perizinan.</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-2">Total Kehadiran Bulan Ini</h3>
        <p class="text-gray-600 mb-4">Jumlah hari hadir selama bulan ini</p>
        <div class="h-32 flex items-center justify-center">
            <div class="text-center">
                <p class="text-3xl font-bold text-blue-600">{{ $attendanceCount ?? 0 }}</p>
                <p class="text-gray-600 mt-1">Hari Hadir</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-2">Status Hari Ini</h3>
        <p class="text-gray-600 mb-4">Status kehadiran Anda hari ini</p>
        <div class="h-32 flex items-center justify-center">
            <p class="text-2xl font-bold {{ $todayAttendance && $todayAttendance->clock_in ? 'text-green-600' : 'text-red-600' }}">
                {{ $todayAttendance && $todayAttendance->clock_in ? 'Sudah Absen' : 'Belum Absen' }}
            </p>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-2">Jabatan</h3>
        <p class="text-gray-600 mb-4">Posisi Anda di sekolah</p>
        <div class="h-32 flex items-center justify-center">
            <div class="text-center">
                <p class="text-xl font-bold text-gray-800">{{ $employee->position ?? 'N/A' }}</p>
                <p class="text-sm text-gray-600 mt-2">Departemen</p>
                <p class="text-sm text-gray-600">{{ $employee->department ?? 'Umum' }}</p>
            </div>
        </div>
    </div>
</div>

@if($todayAttendance)
<div class="bg-white rounded-lg shadow p-6 mb-6">
    <h3 class="text-lg font-bold text-gray-800 mb-4">Detail Absensi Hari Ini</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="p-4 bg-blue-50 rounded-lg">
            <p class="text-gray-600 font-medium">Jam Masuk</p>
            <p class="text-xl font-bold text-gray-800">{{ $todayAttendance->clock_in ? $todayAttendance->clock_in->format('H:i:s') : '-' }}</p>
            <p class="text-sm text-gray-500 mt-1">Status: {{ $todayAttendance->status_in ?? '-' }}</p>
        </div>
        <div class="p-4 bg-green-50 rounded-lg">
            <p class="text-gray-600 font-medium">Jam Pulang</p>
            <p class="text-xl font-bold text-gray-800">{{ $todayAttendance->clock_out ? $todayAttendance->clock_out->format('H:i:s') : '-' }}</p>
            <p class="text-sm text-gray-500 mt-1">Status: {{ $todayAttendance->status_out ?? '-' }}</p>
        </div>
    </div>
</div>
@endif

    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold text-gray-800">Jadwal Shift Bulan Ini</h3>
            <span class="text-sm text-gray-600">{{ \Carbon\Carbon::now()->translatedFormat('F Y') }}</span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hari</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Shift</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jenis Shift</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jam Masuk</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jam Pulang</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                @php
                    $currentMonth = \Carbon\Carbon::now();
                    $daysInMonth = $currentMonth->daysInMonth;
                    $startDate = \Carbon\Carbon::create($currentMonth->year, $currentMonth->month, 1);
                @endphp
                @for($day = 1; $day <= $daysInMonth; $day++)
                    @php
                        $date = \Carbon\Carbon::create($currentMonth->year, $currentMonth->month, $day);
                        $dateString = $date->format('Y-m-d');
                        $employeeShift = $employeeShifts[$dateString] ?? null;
                        $isToday = $date->format('Y-m-d') === \Carbon\Carbon::today()->format('Y-m-d');
                    @endphp
                    <tr class="{{ $isToday ? 'bg-blue-50' : ($employeeShift ? '' : 'bg-gray-50') }}">
                        <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">
                            {{ $date->format('d') }}
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                            {{ $date->locale('id_ID')->dayName }}
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                            {{ $employeeShift && $employeeShift->schedule ? ($employeeShift->schedule->shift_name ?: 'N/A') : 'Tidak Ada' }}
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                            {{ $employeeShift && $employeeShift->schedule ? ($employeeShift->schedule->shift_type ?: 'N/A') : 'N/A' }}
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                            {{ $employeeShift && $employeeShift->schedule ? \Carbon\Carbon::parse($employeeShift->schedule->start_time)->format('H:i') : 'N/A' }}
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                            {{ $employeeShift && $employeeShift->schedule ? \Carbon\Carbon::parse($employeeShift->schedule->end_time)->format('H:i') : 'N/A' }}
                        </td>
                    </tr>
                @endfor
                </tbody>
            </table>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Perizinan Terakhir</h3>
            @if($latestPermission)
                <div class="p-4 border rounded-lg">
                    <p class="font-semibold">Tanggal: {{ \Carbon\Carbon::parse($latestPermission->start_date)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($latestPermission->end_date)->format('d/m/Y') }}</p>
                    <p class="text-gray-700 mt-2">Alasan: {{ $latestPermission->reason }}</p>
                    <span class="mt-2 px-2 py-1 text-xs rounded-full inline-block 
                        {{ $latestPermission->status === 'approved' ? 'bg-green-100 text-green-800' : 
                           ($latestPermission->status === 'rejected' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') }}">
                        {{ ucfirst($latestPermission->status) }}
                    </span>
                </div>
            @else
                <p class="text-gray-600">Anda belum memiliki permohonan perizinan</p>
            @endif
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Profil Pegawai</h3>
            <div class="space-y-2">
                <p><span class="font-medium">Nama:</span> {{ $employee->full_name ?? auth()->user()->name }}</p>
                <p><span class="font-medium">Jabatan:</span> {{ $employee->position ?? 'N/A' }}</p>
            </div>
        </div>
    </div>
@endsection
