@extends('layouts.app')

@section('title', 'Dashboard Monitoring')
@section('page-title', 'Dashboard Monitoring')
@section('page-description', 'Monitor kehadiran pegawai dan siswa secara real-time')

@section('content')
<div class="card mb-8">
    <div class="p-6">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center">
            <div>
                <h2 class="text-heading text-gray-800">Dashboard Monitoring</h2>
                <p class="text-gray-600 mt-2">Selamat datang di sistem absensi MTsN 2 Kota Malang. Gunakan menu di sisi kiri untuk mengakses fitur-fitur monitoring.</p>
            </div>
            <div class="mt-4 md:mt-0">
                <a href="{{ route('monitoring.employees') }}" class="btn btn-primary mr-2">Lihat Kehadiran</a>
                <a href="{{ route('monitoring.permissions.employees') }}" class="btn btn-outline">Perizinan</a>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="stat-card bg-gradient-to-br from-blue-50 to-blue-100 border border-blue-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-blue-600 mb-1">Data Pegawai</p>
                <p class="text-3xl font-bold text-blue-800">{{ $totalEmployees ?? 0 }}</p>
                <p class="text-xs text-blue-600 mt-1">{{ $totalEmployees ?? 0 }} pegawai terdaftar</p>
            </div>
            <div class="bg-blue-100 p-3 rounded-full">
                <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
            </div>
        </div>
    </div>

    <div class="stat-card bg-gradient-to-br from-green-50 to-green-100 border border-green-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-green-600 mb-1">Data Siswa</p>
                <p class="text-3xl font-bold text-green-800">{{ $totalStudents ?? 0 }}</p>
                <p class="text-xs text-green-600 mt-1">{{ $totalStudents ?? 0 }} siswa terdaftar</p>
            </div>
            <div class="bg-green-100 p-3 rounded-full">
                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path d="M12 14l9-5-9-5-9 5 9 5z"/>
                    <path d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222" />
                </svg>
            </div>
        </div>
    </div>

    <div class="stat-card bg-gradient-to-br from-purple-50 to-purple-100 border border-purple-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-purple-600 mb-1">Kehadiran Hari Ini</p>
                <p class="text-3xl font-bold text-purple-800">{{ $overallAttendanceRate ?? 0 }}%</p>
                <p class="text-xs text-purple-600 mt-1">Tingkat Kehadiran</p>
            </div>
            <div class="bg-purple-100 p-3 rounded-full">
                <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 13h.01M9 17h.01M13 13h.01M13 17h.01M17 13h.01M17 17h.01" />
                </svg>
            </div>
        </div>
    </div>

    <div class="stat-card bg-gradient-to-br from-indigo-50 to-indigo-100 border border-indigo-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-indigo-600 mb-1">Tahun Pelajaran</p>
                <p class="text-xl font-bold text-indigo-800">{{ $currentAcademicYear->year_name ?? 'Belum ditentukan' }}</p>
                <p class="text-xs text-indigo-600 mt-1">Tahun Pelajaran Aktif</p>
            </div>
            <div class="bg-indigo-100 p-3 rounded-full">
                <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <div class="card border-card">
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-subheading font-semibold text-gray-800">Kehadiran Pegawai Hari Ini</h3>
                <a href="{{ route('monitoring.employees') }}" class="text-sm text-blue-600 hover:text-blue-800">Lihat detail →</a>
            </div>
            <div class="flex items-center justify-center h-48">
                <div class="text-center">
                    <p class="text-5xl font-bold text-blue-600">{{ number_format((($todayEmployeePresent ?? 0) / max($totalEmployees ?? 0, 1)) * 100, 1) }}%</p>
                    <p class="text-gray-600 mt-2">Tingkat Kehadiran</p>
                    <p class="text-sm text-gray-500 mt-1">{{ $todayEmployeePresent ?? 0 }}/{{ $totalEmployees ?? 0 }} hadir</p>
                    
                    <!-- Progress bar -->
                    <div class="w-full bg-gray-200 rounded-full h-2.5 mt-4">
                        <div class="bg-blue-600 h-2.5 rounded-full" style="width: {{ number_format((($todayEmployeePresent ?? 0) / max($totalEmployees ?? 0, 1)) * 100, 0) }}%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-card">
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-subheading font-semibold text-gray-800">Kehadiran Siswa Hari Ini</h3>
                <a href="{{ route('monitoring.students') }}" class="text-sm text-green-600 hover:text-green-800">Lihat detail →</a>
            </div>
            <div class="flex items-center justify-center h-48">
                <div class="text-center">
                    <p class="text-5xl font-bold text-green-600">{{ number_format((($todayStudentPresent ?? 0) / max($totalStudents ?? 0, 1)) * 100, 1) }}%</p>
                    <p class="text-gray-600 mt-2">Tingkat Kehadiran</p>
                    <p class="text-sm text-gray-500 mt-1">{{ $todayStudentPresent ?? 0 }}/{{ $totalStudents ?? 0 }} hadir</p>
                    
                    <!-- Progress bar -->
                    <div class="w-full bg-gray-200 rounded-full h-2.5 mt-4">
                        <div class="bg-green-600 h-2.5 rounded-full" style="width: {{ number_format((($todayStudentPresent ?? 0) / max($totalStudents ?? 0, 1)) * 100, 0) }}%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-8">
    <div class="card border-card">
        <div class="p-6">
            <div class="flex items-center mb-4">
                <div class="bg-yellow-100 p-2 rounded-lg mr-3">
                    <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 13h.01M9 17h.01M13 13h.01M13 17h.01M17 13h.01M17 17h.01" />
                    </svg>
                </div>
                <h3 class="text-subheading font-semibold text-gray-800">Perizinan Pending</h3>
            </div>
            <div class="text-center py-6">
                <p class="text-4xl font-bold text-yellow-600">{{ $pendingPermissions ?? 0 }}</p>
                <p class="text-gray-600 mt-2">Permohonan Tertunda</p>
                <a href="{{ route('monitoring.permissions.employees') }}" class="btn btn-primary mt-4 w-full">Review Izin</a>
            </div>
        </div>
    </div>

    <div class="card border-card">
        <div class="p-6">
            <div class="flex items-center mb-4">
                <div class="bg-orange-100 p-2 rounded-lg mr-3">
                    <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                </div>
                <h3 class="text-subheading font-semibold text-gray-800">Perbaikan Pending</h3>
            </div>
            <div class="text-center py-6">
                <p class="text-4xl font-bold text-orange-600">{{ $pendingCorrections ?? 0 }}</p>
                <p class="text-gray-600 mt-2">Ajuan Perbaikan</p>
                <a href="{{ route('admin.attendance-corrections.index') }}" class="btn btn-primary mt-4 w-full">Review Ajuan</a>
            </div>
        </div>
    </div>

    <div class="card border-card">
        <div class="p-6">
            <div class="flex items-center mb-4">
                <div class="bg-pink-100 p-2 rounded-lg mr-3">
                    <svg class="w-6 h-6 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </div>
                <h3 class="text-subheading font-semibold text-gray-800">User Monitoring</h3>
            </div>
            <div class="text-center py-6">
                <p class="text-4xl font-bold text-pink-600">{{ $totalMonitoringUsers ?? 0 }}</p>
                <p class="text-gray-600 mt-2">User Monitoring</p>
                <a href="{{ route('admin.users.index') }}" class="btn btn-secondary mt-4 w-full">Lihat Pengguna</a>
            </div>
        </div>
    </div>

    <div class="card border-card">
        <div class="p-6">
            <div class="flex items-center mb-4">
                <div class="bg-teal-100 p-2 rounded-lg mr-3">
                    <svg class="w-6 h-6 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
                    </svg>
                </div>
                <h3 class="text-subheading font-semibold text-gray-800">Backup Database</h3>
            </div>
            <div class="text-center py-6">
                <p class="text-4xl font-bold text-teal-600">{{ $backupCount ?? 0 }}</p>
                <p class="text-gray-600 mt-2">Backup Tersedia</p>
                <a href="{{ route('admin.backup.index') }}" class="btn btn-secondary mt-4 w-full">Lihat Backup</a>
            </div>
        </div>
    </div>
</div>

<div class="card border-card mb-8">
    <div class="p-6">
        <h3 class="text-subheading font-semibold text-gray-800 mb-6">Ringkasan Sistem</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg p-4 text-center">
                <p class="text-2xl font-bold text-blue-700">{{ $totalGrades ?? 0 }}</p>
                <p class="text-sm text-blue-600 mt-1">Tingkat Kelas</p>
            </div>
            <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-lg p-4 text-center">
                <p class="text-2xl font-bold text-green-700">{{ $totalRombels ?? 0 }}</p>
                <p class="text-sm text-green-600 mt-1">Rombongan Belajar</p>
            </div>
            <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-lg p-4 text-center">
                <p class="text-2xl font-bold text-purple-700">{{ $totalSchedules ?? 0 }}</p>
                <p class="text-sm text-purple-600 mt-1">Jadwal Absensi</p>
            </div>
            <div class="bg-gradient-to-br from-indigo-50 to-indigo-100 rounded-lg p-4 text-center">
                <p class="text-2xl font-bold text-indigo-700">{{ $backupCount ?? 0 }}</p>
                <p class="text-sm text-indigo-600 mt-1">Backup Database</p>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="card border-card">
        <div class="p-6">
            <h3 class="text-subheading font-semibold text-gray-800 mb-4">Ringkasan Perizinan</h3>
            <div class="space-y-3">
                <div class="flex justify-between items-center p-4 bg-gray-50 rounded-lg">
                    <span>Perizinan Pegawai</span>
                    <span class="font-bold text-lg">{{ $pendingPermissions ?? 0 }}</span>
                </div>
                <div class="flex justify-between items-center p-4 bg-gray-50 rounded-lg">
                    <span>Perizinan Siswa</span>
                    <span class="font-bold text-lg">{{ $pendingStudentPermissions ?? 0 }}</span>
                </div>
                <div class="flex justify-between items-center p-4 bg-gray-50 rounded-lg">
                    <span>Total Hari Ini</span>
                    <span class="font-bold text-lg">{{ $totalPermissionsToday ?? 0 }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Holidays This Month Section -->
    <div class="card border-card">
        <div class="p-6">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center">
                    <div class="bg-amber-100 p-2 rounded-lg mr-3">
                        <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <h3 class="text-subheading font-semibold text-gray-800">Hari Libur Bulan {{ \Carbon\Carbon::now()->translatedFormat('F Y') }}</h3>
                </div>
                <a href="{{ route('admin.holidays.index') }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                    Lihat Semua →
                </a>
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
</div>

<!-- Chart Filter -->
<div class="card mb-6">
    <div class="p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Filter Grafik Kehadiran</h3>
        <form method="GET" class="flex gap-4 items-end">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Bulan</label>
                <select name="month" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @foreach($months as $value => $name)
                        <option value="{{ $value }}" {{ $selectedMonth == $value ? 'selected' : '' }}>
                            {{ $name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tahun</label>
                <select name="year" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @foreach($years as $year)
                        <option value="{{ $year }}" {{ $selectedYear == $year ? 'selected' : '' }}>
                            {{ $year }}
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                Tampilkan
            </button>
        </form>
    </div>
</div>

<!-- Attendance Charts -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- Employee Attendance Chart -->
    <div class="card">
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-lg font-semibold text-gray-800">Grafik Kehadiran Pegawai</h3>
                    <p class="text-sm text-gray-600">{{ $months[$selectedMonth] }} {{ $selectedYear }}</p>
                </div>
                <div class="text-right">
                    <p class="text-2xl font-bold text-blue-600">{{ $employeeAttendanceData['percentage'] }}%</p>
                    <p class="text-xs text-gray-600">Persentase Kehadiran</p>
                </div>
            </div>
            <canvas id="employeeAttendanceChart" height="80"></canvas>
        </div>
    </div>

    <!-- Student Attendance Chart -->
    <div class="card">
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-lg font-semibold text-gray-800">Grafik Kehadiran Siswa</h3>
                    <p class="text-sm text-gray-600">{{ $months[$selectedMonth] }} {{ $selectedYear }}</p>
                </div>
                <div class="text-right">
                    <p class="text-2xl font-bold text-green-600">{{ $studentAttendanceData['percentage'] }}%</p>
                    <p class="text-xs text-gray-600">Persentase Kehadiran</p>
                </div>
            </div>
            <canvas id="studentAttendanceChart" height="80"></canvas>
        </div>
    </div>
</div>

<!-- Recent Activities -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- Recent Login Attempts -->
    <div class="card">
        <div class="p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Aktivitas Login Terbaru</h3>
            <div class="space-y-3 max-h-96 overflow-y-auto">
                @forelse($recentLogins as $login)
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div class="flex items-center space-x-3">
                            <div class="flex-shrink-0">
                                @if($login->successful)
                                    <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    </div>
                                @else
                                    <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
                                        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </div>
                                @endif
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900">
                                    {{ $login->username }}
                                </p>
                                <p class="text-xs text-gray-500">
                                    {{ $login->ip_address }} • {{ $login->created_at->diffForHumans() }}
                                </p>
                            </div>
                        </div>
                        <span class="text-xs font-medium px-2 py-1 rounded {{ $login->successful ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $login->successful ? 'Berhasil' : 'Gagal' }}
                        </span>
                    </div>
                @empty
                    <p class="text-center text-gray-500 py-8">Tidak ada aktivitas login</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Recent Attendances -->
    <div class="card">
        <div class="p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Absensi Terbaru</h3>
            <div class="space-y-3 max-h-96 overflow-y-auto">
                @forelse($recentAttendances as $attendance)
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div class="flex items-center space-x-3">
                            <div class="flex-shrink-0">
                                @if($attendance->attendable_type === 'App\Models\Employee')
                                    <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                    </div>
                                @else
                                    <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                        </svg>
                                    </div>
                                @endif
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900">
                                    {{ $attendance->attendable->full_name ?? 'N/A' }}
                                </p>
                                <p class="text-xs text-gray-500">
                                    {{ $attendance->attendable_type === 'App\Models\Employee' ? 'Pegawai' : 'Siswa' }}
                                    • {{ \Carbon\Carbon::parse($attendance->clock_in)->format('H:i') }}
                                    • {{ $attendance->date->format('d/m/Y') }}
                                </p>
                            </div>
                        </div>
                        <span class="text-xs font-medium px-2 py-1 rounded {{ $attendance->status_in === 'Tepat Waktu' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                            {{ $attendance->status_in ?? 'Hadir' }}
                        </span>
                    </div>
                @empty
                    <p class="text-center text-gray-500 py-8">Tidak ada absensi terbaru</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

<!-- Chart.js Scripts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    // Employee Attendance Chart
    const employeeCtx = document.getElementById('employeeAttendanceChart');
    if (employeeCtx) {
        // Find the maximum value in the datasets to determine proper y-axis scale
        const empData = {!! json_encode($employeeAttendanceData['present']) !!}.concat({!! json_encode($employeeAttendanceData['absent']) !!});
        const maxEmpValue = empData.length > 0 ? Math.max(...empData) : 0;
        const totalEmployees = {!! json_encode($totalEmployees) !!};
        const suggestedMaxEmp = maxEmpValue > 0 ? Math.min(Math.ceil(maxEmpValue * 1.2), totalEmployees) : 10;
        const stepSizeEmp = maxEmpValue > 0 ? Math.max(1, Math.ceil(maxEmpValue / 5)) : 2;

        new Chart(employeeCtx, {
            type: 'line',
            data: {
                labels: {!! json_encode($employeeAttendanceData['labels']) !!},
                datasets: [{
                    label: 'Hadir',
                    data: {!! json_encode($employeeAttendanceData['present']) !!},
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'Tidak Hadir',
                    data: {!! json_encode($employeeAttendanceData['absent']) !!},
                    borderColor: 'rgb(239, 68, 68)',
                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom'
                    }
                },
                scales: {
                    x: {
                        ticks: {
                            maxRotation: 45,
                            minRotation: 45
                        }
                    },
                    y: {
                        beginAtZero: true,
                        suggestedMax: suggestedMaxEmp,
                        ticks: {
                            stepSize: stepSizeEmp,
                            callback: function(value) {
                                if (Number.isInteger(value)) {
                                    return value;
                                }
                            }
                        }
                    }
                },
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                }
            }
        });
    }

    // Student Attendance Chart
    const studentCtx = document.getElementById('studentAttendanceChart');
    if (studentCtx) {
        // Find the maximum value in the datasets to determine proper y-axis scale
        const studData = {!! json_encode($studentAttendanceData['present']) !!}.concat({!! json_encode($studentAttendanceData['absent']) !!});
        const maxStudValue = studData.length > 0 ? Math.max(...studData) : 0;
        const totalStudents = {!! json_encode($totalStudents) !!};
        const suggestedMaxStud = maxStudValue > 0 ? Math.min(Math.ceil(maxStudValue * 1.2), totalStudents) : 10;
        const stepSizeStud = maxStudValue > 0 ? Math.max(1, Math.ceil(maxStudValue / 5)) : 2;

        new Chart(studentCtx, {
            type: 'line',
            data: {
                labels: {!! json_encode($studentAttendanceData['labels']) !!},
                datasets: [{
                    label: 'Hadir',
                    data: {!! json_encode($studentAttendanceData['present']) !!},
                    borderColor: 'rgb(34, 197, 94)',
                    backgroundColor: 'rgba(34, 197, 94, 0.1)',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'Tidak Hadir',
                    data: {!! json_encode($studentAttendanceData['absent']) !!},
                    borderColor: 'rgb(239, 68, 68)',
                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom'
                    }
                },
                scales: {
                    x: {
                        ticks: {
                            maxRotation: 45,
                            minRotation: 45
                        }
                    },
                    y: {
                        beginAtZero: true,
                        suggestedMax: suggestedMaxStud,
                        ticks: {
                            stepSize: stepSizeStud,
                            callback: function(value) {
                                if (Number.isInteger(value)) {
                                    return value;
                                }
                            }
                        }
                    }
                },
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                }
            }
        });
    }
</script>
@endsection
