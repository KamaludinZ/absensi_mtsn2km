@extends('layouts.app')

@section('title', 'Detail Laporan Gaji')
@section('page-title', 'Detail Laporan Gaji')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-6">
        <a href="{{ route('employee.salary.report', ['year' => $year]) }}" class="bg-gray-200 text-gray-800 px-4 py-2 rounded-md hover:bg-gray-300 transition-colors flex items-center inline-flex">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Kembali
        </a>
    </div>
        
            <!-- Employee Info -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                <h2 class="text-2xl font-bold text-gray-800 mb-4">Detail Gaji Pegawai</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-600">Nama Pegawai</p>
                        <p class="text-lg font-semibold text-gray-900">{{ $employee->full_name }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Jabatan</p>
                        <p class="text-lg font-semibold text-gray-900">{{ $employee->position ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">NIP</p>
                        <p class="text-lg font-semibold text-gray-900">{{ $employee->nip ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Periode</p>
                        <p class="text-lg font-semibold text-gray-900">{{ \Carbon\Carbon::create($year, $month)->isoFormat('MMMM YYYY') }}</p>
                    </div>
                </div>
            </div>
        
            <!-- Daily Breakdown Table -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden mb-8">
                <div class="p-4 border-b">
                    <h3 class="text-lg font-semibold">Rincian Kehadiran Harian</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hari</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jam Masuk</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Absensi Masuk</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jam Pulang</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Absensi Pulang</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Uang Makan</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Uang Transport</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tunj. Kinerja</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($detailData as $data)
                                @php $permissionType = $permissionMap[$data['date']->format('Y-m-d')] ?? null; @endphp
                                <tr class="{{ $permissionType ? 'bg-blue-50' : ($data['attendance'] ? 'hover:bg-gray-50' : 'bg-gray-100') }}">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ $data['date']->isoFormat('dddd') }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ $data['date']->isoFormat('D MMMM YYYY') }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                        {{ $data['schedule'] ? \Carbon\Carbon::parse($data['schedule']->start_time)->format('H:i:s') : '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                        @if($permissionType)
                                            <span class="font-semibold text-blue-600">{{ $permissionType }}</span>
                                        @else
                                            <div>{{ $data['attendance'] && $data['attendance']->clock_in ? \Carbon\Carbon::parse($data['attendance']->clock_in)->format('H:i:s') : '-' }}</div>
                                            <div class="text-xs text-gray-500">{{ $data['status_in'] }}</div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                        {{ $data['schedule'] ? \Carbon\Carbon::parse($data['schedule']->end_time)->format('H:i:s') : '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                        @if($permissionType)
                                            <span class="font-semibold text-blue-600">{{ $permissionType }}</span>
                                        @else
                                            <div>{{ $data['attendance'] && $data['attendance']->clock_out ? \Carbon\Carbon::parse($data['attendance']->clock_out)->format('H:i:s') : '-' }}</div>
                                            <div class="text-xs text-gray-500">{{ $data['status_out'] }}</div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-700">Rp {{ number_format($data['daily_meal_allowance'], 0, ',', '.') }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-700">Rp {{ number_format($data['daily_transport_allowance'], 0, ',', '.') }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-700">Rp {{ number_format($data['daily_performance_allowance'], 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-6 py-8 text-center text-gray-500">
                                        Tidak ada data kehadiran untuk periode ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        
            <!-- Summary Section -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold mb-4">Ringkasan Gaji</h3>
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Gaji Pokok</span>
                        <span class="font-semibold text-gray-900">Rp {{ number_format($summary['base_salary'], 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Total Uang Makan</span>
                        <span class="font-semibold text-gray-900">Rp {{ number_format($summary['total_meal_allowance'], 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Total Uang Transport</span>
                        <span class="font-semibold text-gray-900">Rp {{ number_format($summary['total_transport_allowance'], 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Total Tunjangan Kinerja</span>
                        <span class="font-semibold text-gray-900">Rp {{ number_format($summary['total_performance_allowance'], 0, ',', '.') }}</span>
                    </div>
                    <div class="border-t pt-3 mt-3 flex justify-between items-center">
                        <span class="text-lg font-bold text-gray-900">Total Gaji</span>
                        <span class="text-lg font-bold text-green-600">Rp {{ number_format($summary['total_salary'], 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>
</div>
@endsection
        