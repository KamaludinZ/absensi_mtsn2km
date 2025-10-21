@extends('layouts.app')

@section('title', 'Detail Pegawai')
@section('page-title', 'Detail Pegawai')

@section('content')
<div class="mb-4">
    <a href="{{ route('admin.employees.index') }}" class="text-blue-600 hover:text-blue-800">
        &larr; Kembali ke Daftar Pegawai
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Employee Information Card -->
    <div class="lg:col-span-1">
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Informasi Pegawai</h2>

            <div class="space-y-3">
                <div>
                    <label class="text-sm font-medium text-gray-500">Nama Lengkap</label>
                    <p class="text-gray-900">{{ $employee->full_name }}</p>
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-500">Jabatan</label>
                    <p class="text-gray-900">{{ $employee->position }}</p>
                </div>

                @if($employee->phone)
                <div>
                    <label class="text-sm font-medium text-gray-500">Telepon</label>
                    <p class="text-gray-900">{{ $employee->phone }}</p>
                </div>
                @endif

                @if($employee->email)
                <div>
                    <label class="text-sm font-medium text-gray-500">Email</label>
                    <p class="text-gray-900">{{ $employee->email }}</p>
                </div>
                @endif

                <div>
                    <label class="text-sm font-medium text-gray-500">Status</label>
                    <p>
                        <span class="px-2 py-1 text-xs rounded {{ $employee->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $employee->is_active ? 'Aktif' : 'Tidak Aktif' }}
                        </span>
                    </p>
                </div>

                @if($employee->user)
                <div>
                    <label class="text-sm font-medium text-gray-500">Username</label>
                    <p class="text-gray-900">{{ $employee->user->username }}</p>
                </div>
                @else
                <div>
                    <label class="text-sm font-medium text-gray-500">Akun User</label>
                    <p class="text-red-600 text-sm">Belum memiliki akun</p>
                </div>
                @endif
            </div>

            <div class="mt-6 flex space-x-2">
                <a href="{{ route('admin.employees.edit', $employee) }}" class="flex-1 bg-blue-600 text-white text-center px-4 py-2 rounded-lg hover:bg-blue-700">
                    Edit
                </a>
                <form action="{{ route('admin.employees.destroy', $employee) }}" method="POST" class="flex-1">
                    @csrf @method('DELETE')
                    <button type="submit" class="w-full bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700" onclick="return confirm('Yakin hapus pegawai ini?')">
                        Hapus
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Statistics and Attendance -->
    <div class="lg:col-span-2">
        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500">Hadir Bulan Ini</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $totalPresent }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-red-100 text-red-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500">Terlambat</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $totalLate }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500">Pulang Awal</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $totalEarlyLeave }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Attendance -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-800">Riwayat Absensi Bulan Ini</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jam Masuk</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jam Keluar</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status Masuk</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status Keluar</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($attendances as $attendance)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                {{ $attendance->date->format('d/m/Y') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                {{ $attendance->clock_in ? $attendance->clock_in->format('H:i:s') : '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                {{ $attendance->clock_out ? $attendance->clock_out->format('H:i:s') : '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($attendance->status_in)
                                <span class="px-2 py-1 text-xs rounded {{ $attendance->status_in === 'Tepat Waktu' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $attendance->status_in }}
                                </span>
                                @else
                                <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($attendance->status_out)
                                <span class="px-2 py-1 text-xs rounded {{ $attendance->status_out === 'Tepat Waktu' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                    {{ $attendance->status_out }}
                                </span>
                                @else
                                <span class="text-gray-400">-</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                Belum ada data absensi bulan ini
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
