@extends('layouts.app')

@section('title', 'Dashboard Pegawai')
@section('page-title', 'Dashboard Pegawai')
@section('page-description', 'Kelola kehadiran dan perizinan Anda')

@section('content')
<div class="card mb-8">
    <div class="p-6">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center">
            <div>
                <h2 class="text-heading text-gray-800">Dashboard Pegawai</h2>
                <p class="text-gray-600 mt-2">Halo, {{ auth()->user()->name }}! Gunakan menu di sisi kiri untuk mengakses fitur absensi dan perizinan.</p>
            </div>
            <div class="mt-4 md:mt-0">
                <a href="{{ route('employee.attendance.index') }}" class="btn btn-primary mr-2">Absen Sekarang</a>
                <a href="{{ route('employee.permission.index') }}" class="btn btn-outline">Ajukan Perizinan</a>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="stat-card bg-gradient-to-br from-blue-50 to-blue-100 border border-blue-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-blue-600 mb-1">Total Kehadiran Bulan Ini</p>
                <p class="text-3xl font-bold text-blue-800">{{ $attendanceCount ?? 0 }}</p>
                <p class="text-xs text-blue-600 mt-1">Jumlah hari hadir</p>
            </div>
            <div class="bg-blue-100 p-3 rounded-full">
                <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
            </div>
        </div>
    </div>

    <div class="stat-card bg-gradient-to-br from-green-50 to-green-100 border border-green-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-green-600 mb-1">Status Hari Ini</p>
                <p class="text-2xl font-bold {{ $todayAttendance && $todayAttendance->clock_in ? 'text-green-800' : 'text-red-800' }}">
                    {{ $todayAttendance && $todayAttendance->clock_in ? 'Sudah Absen' : 'Belum Absen' }}
                </p>
                <p class="text-xs text-gray-600 mt-1">Kehadiran hari ini</p>
            </div>
            <div class="bg-green-100 p-3 rounded-full">
                <svg class="w-8 h-8 {{ $todayAttendance && $todayAttendance->clock_in ? 'text-green-600' : 'text-red-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
        </div>
    </div>

    <div class="stat-card bg-gradient-to-br from-indigo-50 to-indigo-100 border border-indigo-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-indigo-600 mb-1">Jabatan</p>
                <p class="text-xl font-bold text-indigo-800">{{ $employee->position ?? 'N/A' }}</p>
                <p class="text-xs text-indigo-600 mt-1">{{ $employee->department ?? 'Umum' }}</p>
            </div>
            <div class="bg-indigo-100 p-3 rounded-full">
                <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
            </div>
        </div>
    </div>
</div>

@if($todayAttendance)
<div class="card border-card mb-8">
    <div class="p-6">
        <h3 class="text-subheading font-semibold text-gray-800 mb-6">Detail Absensi Hari Ini</h3>
        @php
            // Get today's schedule
            $todaySchedule = null;
            $employeeShift = \App\Models\EmployeeShift::where('employee_id', $employee->id)
                ->where('date', \Carbon\Carbon::today())
                ->with('schedule')
                ->first();

            if ($employeeShift && $employeeShift->schedule) {
                $todaySchedule = $employeeShift->schedule;
            } else {
                // Try to get schedule by day of week
                $dayOfWeek = \Carbon\Carbon::today()->dayOfWeek;
                $todaySchedule = \App\Models\Schedule::where('type', 'pegawai')
                    ->where('day_of_week', $dayOfWeek)
                    ->first();
            }

            // Calculate status for clock_in
            $statusInText = '-';
            $statusInColor = 'text-gray-500';
            if ($todayAttendance->clock_in && $todaySchedule) {
                $absensiMasukTime = \Carbon\Carbon::parse($todayAttendance->clock_in)->format('H:i:s');
                $jamMasukTime = \Carbon\Carbon::parse($todaySchedule->start_time)->format('H:i:s');

                if ($absensiMasukTime > $jamMasukTime) {
                    $statusInText = 'Terlambat';
                    $statusInColor = 'text-red-600 font-semibold';
                } else {
                    $statusInText = 'Tepat Waktu';
                    $statusInColor = 'text-green-600 font-semibold';
                }
            } elseif ($todayAttendance->clock_in) {
                $statusInText = 'N/A';
            } else {
                $statusInText = 'Tidak Hadir';
            }

            // Calculate status for clock_out
            $statusOutText = '-';
            $statusOutColor = 'text-gray-500';
            if ($todayAttendance->clock_out && $todaySchedule) {
                $absensiPulangTime = \Carbon\Carbon::parse($todayAttendance->clock_out)->format('H:i:s');
                $jamPulangTime = \Carbon\Carbon::parse($todaySchedule->end_time)->format('H:i:s');

                if ($absensiPulangTime < $jamPulangTime) {
                    $statusOutText = 'Pulang Awal';
                    $statusOutColor = 'text-yellow-600 font-semibold';
                } else {
                    $statusOutText = 'Tepat Waktu';
                    $statusOutColor = 'text-green-600 font-semibold';
                }
            } elseif ($todayAttendance->clock_out) {
                $statusOutText = 'N/A';
            } else {
                $statusOutText = 'Belum Pulang';
            }
        @endphp
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="p-6 bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl border border-blue-100">
                <div class="flex items-center mb-2">
                    <div class="bg-blue-100 p-2 rounded-lg mr-3">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <p class="text-gray-600 font-medium">Jam Masuk</p>
                </div>
                <p class="text-2xl font-bold text-gray-800">{{ $todayAttendance->clock_in ? $todayAttendance->clock_in->format('H:i:s') : '-' }}</p>
                @if($todaySchedule)
                <p class="text-xs text-gray-500 mt-1">Jadwal: {{ \Carbon\Carbon::parse($todaySchedule->start_time)->format('H:i:s') }}</p>
                @endif
                <p class="text-sm mt-2">Status: <span class="{{ $statusInColor }}">{{ $statusInText }}</span></p>
            </div>
            <div class="p-6 bg-gradient-to-br from-green-50 to-green-100 rounded-xl border border-green-100">
                <div class="flex items-center mb-2">
                    <div class="bg-green-100 p-2 rounded-lg mr-3">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <p class="text-gray-600 font-medium">Jam Pulang</p>
                </div>
                <p class="text-2xl font-bold text-gray-800">{{ $todayAttendance->clock_out ? $todayAttendance->clock_out->format('H:i:s') : '-' }}</p>
                @if($todaySchedule)
                <p class="text-xs text-gray-500 mt-1">Jadwal: {{ \Carbon\Carbon::parse($todaySchedule->end_time)->format('H:i:s') }}</p>
                @endif
                <p class="text-sm mt-2">Status: <span class="{{ $statusOutColor }}">{{ $statusOutText }}</span></p>
            </div>
        </div>
    </div>
</div>
@endif

<div class="card border-card mb-8">
    <div class="p-6">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
            <div>
                <h3 class="text-subheading font-semibold text-gray-800">Jadwal Shift Bulan Ini</h3>
                <p class="text-gray-600 mt-1">{{ \Carbon\Carbon::now()->translatedFormat('F Y') }}</p>
            </div>
            <div class="mt-2 md:mt-0">
                <span class="badge badge-info">{{ $employeeShifts->count() }} shift</span>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Hari</th>
                        <th>Nama Shift</th>
                        <th>Jenis Shift</th>
                        <th>Jam Masuk</th>
                        <th>Jam Pulang</th>
                    </tr>
                </thead>
                <tbody>
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
                        <td class="font-medium">{{ $date->format('d') }}</td>
                        <td>{{ $date->locale('id_ID')->dayName }}</td>
                        <td>
                            @if($employeeShift && $employeeShift->schedule)
                                <span class="badge badge-primary">{{ $employeeShift->schedule->shift_name ?: 'N/A' }}</span>
                            @else
                                <span class="text-gray-400">Tidak Ada</span>
                            @endif
                        </td>
                        <td>
                            @if($employeeShift && $employeeShift->schedule)
                                <span class="badge badge-info">{{ $employeeShift->schedule->shift_type ?: 'N/A' }}</span>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td>
                            @if($employeeShift && $employeeShift->schedule)
                                {{ \Carbon\Carbon::parse($employeeShift->schedule->start_time)->format('H:i') }}
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td>
                            @if($employeeShift && $employeeShift->schedule)
                                {{ \Carbon\Carbon::parse($employeeShift->schedule->end_time)->format('H:i') }}
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                    </tr>
                @endfor
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="card border-card">
        <div class="p-6">
            <div class="flex items-center mb-4">
                <div class="bg-purple-100 p-2 rounded-lg mr-3">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 13h.01M9 17h.01M13 13h.01M13 17h.01M17 13h.01M17 17h.01" />
                    </svg>
                </div>
                <h3 class="text-subheading font-semibold text-gray-800">Perizinan Terakhir</h3>
            </div>
            @if($latestPermission)
                <div class="p-4 bg-gray-50 rounded-lg">
                    <div class="flex justify-between items-start mb-2">
                        <p class="font-semibold">Tanggal: {{ \Carbon\Carbon::parse($latestPermission->start_date)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($latestPermission->end_date)->format('d/m/Y') }}</p>
                        <span class="badge 
                            {{ $latestPermission->status === 'approved' ? 'badge-success' : 
                               ($latestPermission->status === 'rejected' ? 'badge-danger' : 'badge-warning') }}">
                            {{ ucfirst($latestPermission->status) }}
                        </span>
                    </div>
                    <p class="text-gray-700">Alasan: {{ $latestPermission->reason }}</p>
                </div>
            @else
                <p class="text-gray-600">Anda belum memiliki permohonan perizinan</p>
            @endif
        </div>
    </div>

    <div class="card border-card">
        <div class="p-6">
            <div class="flex items-center mb-4">
                <div class="bg-indigo-100 p-2 rounded-lg mr-3">
                    <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </div>
                <h3 class="text-subheading font-semibold text-gray-800">Profil Pegawai</h3>
            </div>
            <div class="space-y-3">
                <div class="flex">
                    <span class="font-medium w-24 text-gray-600">Nama:</span>
                    <span class="text-gray-800">{{ $employee->full_name ?? auth()->user()->name }}</span>
                </div>
                <div class="flex">
                    <span class="font-medium w-24 text-gray-600">Jabatan:</span>
                    <span class="text-gray-800">{{ $employee->position ?? 'N/A' }}</span>
                </div>
                <div class="flex">
                    <span class="font-medium w-24 text-gray-600">Departemen:</span>
                    <span class="text-gray-800">{{ $employee->department ?? 'Umum' }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Holidays This Month Section -->
<div class="card border-card mt-6">
    <div class="p-6">
        <div class="flex items-center mb-6">
            <div class="bg-amber-100 p-2 rounded-lg mr-3">
                <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
            </div>
            <h3 class="text-subheading font-semibold text-gray-800">Hari Libur Bulan {{ \Carbon\Carbon::now()->translatedFormat('F Y') }}</h3>
        </div>

        @if($holidaysThisMonth->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($holidaysThisMonth as $holiday)
                <div class="bg-gradient-to-br from-amber-50 to-yellow-50 rounded-lg p-4 border border-amber-100">
                    <div class="flex items-start">
                        <div class="bg-amber-100 rounded-lg p-3 mr-3 text-center min-w-[60px]">
                            <p class="text-2xl font-bold text-amber-700">{{ $holiday->date->format('d') }}</p>
                            <p class="text-xs text-amber-600">{{ $holiday->date->translatedFormat('M') }}</p>
                        </div>
                        <div class="flex-1">
                            <h4 class="font-semibold text-gray-800 mb-1">{{ $holiday->name }}</h4>
                            @if($holiday->description)
                                <p class="text-sm text-gray-600">{{ Str::limit($holiday->description, 80) }}</p>
                            @endif
                            <p class="text-xs text-gray-500 mt-2">{{ $holiday->date->translatedFormat('l, d F Y') }}</p>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-8">
                <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <p class="text-gray-500">Tidak ada hari libur pada bulan ini</p>
            </div>
        @endif
    </div>
</div>
@endsection
