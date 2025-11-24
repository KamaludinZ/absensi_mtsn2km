@extends('layouts.app')

@section('title', 'Laporan Tunjangan Kinerja')
@section('page-title', 'Laporan Tunjangan Kinerja Pegawai')

@section('content')
<div class="container mx-auto px-4 py-8">
    @if(session('success'))
        <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-md">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-md">
            {{ session('error') }}
        </div>
    @endif

    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <!-- Filter Section -->
        <div class="p-4 bg-gray-50 border-b">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-6 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Bulan</label>
                    <select name="month" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @foreach($months as $value => $name)
                            <option value="{{ $value }}" {{ $selectedMonth == $value ? 'selected' : '' }}>
                                {{ $name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tahun</label>
                    <select name="year" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @foreach($years as $yearOption)
                            <option value="{{ $yearOption }}" {{ $selectedYear == $yearOption ? 'selected' : '' }}>
                                {{ $yearOption }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Pegawai</label>
                    <select name="employee_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Semua Pegawai</option>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->id }}" {{ request('employee_id') == $employee->id ? 'selected' : '' }}>
                                {{ $employee->full_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-end">
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 w-full">
                        Filter
                    </button>
                    <a href="{{ route('admin.reports.performance-allowance') }}" class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700 ml-2 w-full text-center">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        <div class="p-4 border-b">
            <h2 class="text-lg font-semibold">Laporan Tunjangan Kinerja Pegawai {{ $months[$selectedMonth] }} {{ $selectedYear }}</h2>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Pegawai</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jabatan</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Hari</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hari Hadir</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hari Terlambat</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kepulangan Awal</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Persentase Kehadiran</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Skor Kinerja</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nilai Tunjangan</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($performanceData as $data)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                {{ $data['employee']->full_name ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                {{ $data['employee']->position ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                {{ $data['total_days'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                {{ $data['present_days'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                {{ $data['late_days'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                {{ $data['early_departure_days'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                {{ $data['present_rate'] }}%
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                {{ $data['performance_score'] }}/100
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    // Calculate allowance based on performance score
                                    $baseAllowance = 500000; // Base allowance
                                    $adjustment = $data['performance_score'] / 100;
                                    $finalAllowance = $baseAllowance * $adjustment;
                                @endphp
                                Rp {{ number_format($finalAllowance, 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-4 text-center text-gray-500">
                                Tidak ada data pelaporan tunjangan kinerja.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(count($performanceData) > 0)
        <div class="p-4 bg-gray-50 border-t">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-sm text-gray-700">
                        Total Pegawai: {{ count($performanceData) }}
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-700">
                        Rata-rata Skor Kinerja: {{ number_format(collect($performanceData)->avg('performance_score'), 2) }}/100
                    </p>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection