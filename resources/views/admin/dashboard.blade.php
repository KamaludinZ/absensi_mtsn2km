@extends('layouts.app')

@section('title', 'Dashboard Admin')
@section('page-title', 'Dashboard Admin')

@section('content')
<div class="bg-white rounded-lg shadow p-6 mb-6">
    <h2 class="text-xl font-bold text-gray-800 mb-4">Dashboard Administrator</h2>
    <p class="text-gray-600">Selamat datang di sistem absensi MTsN 2 Kota Malang. Gunakan menu di sisi kiri untuk mengakses fitur-fitur administrasi.</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-2">Data Pegawai</h3>
        <p class="text-gray-600 mb-4">{{ $totalEmployees ?? 0 }} pegawai terdaftar</p>
        <div class="h-32 flex items-center justify-center">
            <div class="text-center">
                <p class="text-3xl font-bold text-blue-600">{{ $totalEmployees ?? 0 }}</p>
                <p class="text-gray-600 mt-1">Total Pegawai</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-2">Data Siswa</h3>
        <p class="text-gray-600 mb-4">{{ $totalStudents ?? 0 }} siswa terdaftar</p>
        <div class="h-32 flex items-center justify-center">
            <div class="text-center">
                <p class="text-3xl font-bold text-green-600">{{ $totalStudents ?? 0 }}</p>
                <p class="text-gray-600 mt-1">Total Siswa</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-2">Kehadiran Hari Ini</h3>
        <p class="text-gray-600 mb-4">Statistik kehadiran keseluruhan</p>
        <div class="h-32 flex items-center justify-center">
            <div class="text-center">
                <p class="text-2xl font-bold text-purple-600">
                    {{ number_format((($todayEmployeePresent ?? 0 + $todayStudentPresent ?? 0) / max(($totalEmployees ?? 0 + $totalStudents ?? 0), 1)) * 100, 1) }}%
                </p>
                <p class="text-gray-600 mt-1">Tingkat Kehadiran</p>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Kehadiran Pegawai Hari Ini</h3>
        <div class="h-40 flex items-center justify-center">
            <div class="text-center">
                <p class="text-4xl font-bold text-blue-600">{{ number_format((($todayEmployeePresent ?? 0) / max($totalEmployees ?? 0, 1)) * 100, 1) }}%</p>
                <p class="text-gray-600 mt-2">Tingkat Kehadiran</p>
                <p class="text-sm text-gray-500 mt-1">{{ $todayEmployeePresent ?? 0 }}/{{ $totalEmployees ?? 0 }} hadir</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Kehadiran Siswa Hari Ini</h3>
        <div class="h-40 flex items-center justify-center">
            <div class="text-center">
                <p class="text-4xl font-bold text-green-600">{{ number_format((($todayStudentPresent ?? 0) / max($totalStudents ?? 0, 1)) * 100, 1) }}%</p>
                <p class="text-gray-600 mt-2">Tingkat Kehadiran</p>
                <p class="text-sm text-gray-500 mt-1">{{ $todayStudentPresent ?? 0 }}/{{ $totalStudents ?? 0 }} hadir</p>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-2">Perizinan Pending</h3>
        <p class="text-gray-600 mb-4">Permohonan izin yang menunggu persetujuan</p>
        <div class="h-32 flex items-center justify-center">
            <div class="text-center">
                <p class="text-3xl font-bold text-yellow-600">{{ $pendingPermissions ?? 0 }}</p>
                <p class="text-gray-600 mt-1">Permohonan Tertunda</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-2">Tahun Ajaran Aktif</h3>
        <p class="text-gray-600 mb-4">Tahun ajaran saat ini</p>
        <div class="h-32 flex items-center justify-center">
            <div class="text-center">
                <p class="text-xl font-bold text-indigo-600">{{ $currentAcademicYear->year_name ?? 'Belum ditentukan' }}</p>
                <p class="text-gray-600 mt-1">Tahun Ajaran</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-2">User Monitoring</h3>
        <p class="text-gray-600 mb-4">Akun monitoring yang terdaftar</p>
        <div class="h-32 flex items-center justify-center">
            <div class="text-center">
                <p class="text-3xl font-bold text-pink-600">{{ $totalMonitoringUsers ?? 0 }}</p>
                <p class="text-gray-600 mt-1">User Monitoring</p>
            </div>
        </div>
    </div>
</div>

<div class="bg-white rounded-lg shadow p-6">
    <h3 class="text-lg font-bold text-gray-800 mb-4">Ringkasan Sistem</h3>
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="text-center p-4 bg-gray-50 rounded-lg">
            <p class="text-2xl font-bold text-gray-800">{{ $totalGrades ?? 0 }}</p>
            <p class="text-sm text-gray-600">Tingkat Kelas</p>
        </div>
        <div class="text-center p-4 bg-gray-50 rounded-lg">
            <p class="text-2xl font-bold text-gray-800">{{ $totalRombels ?? 0 }}</p>
            <p class="text-sm text-gray-600">Rombongan Belajar</p>
        </div>
        <div class="text-center p-4 bg-gray-50 rounded-lg">
            <p class="text-2xl font-bold text-gray-800">{{ $totalSchedules ?? 0 }}</p>
            <p class="text-sm text-gray-600">Jadwal Absensi</p>
        </div>
        <div class="text-center p-4 bg-gray-50 rounded-lg">
            <p class="text-2xl font-bold text-gray-800">{{ $backupCount ?? 0 }}</p>
            <p class="text-sm text-gray-600">Backup Database</p>
        </div>
    </div>
</div>
@endsection