@extends('layouts.app')

@section('title', 'Laporan Tunjangan Kinerja')
@section('page-title', 'Laporan Tunjangan Kinerja Pegawai')
@section('container-class', '')

@php
    // Helper function to generate sortable column header
    function sortableLink($label, $sortField, $currentSort, $currentDirection) {
        $newDirection = 'asc';
        $icon = '';

        if ($currentSort == $sortField) {
            // If this column is currently sorted
            $newDirection = $currentDirection == 'asc' ? 'desc' : 'asc';
            $icon = $currentDirection == 'asc' ? ' ↑' : ' ↓';
        }

        // Build query string preserving filters
        $params = request()->except(['sort', 'direction', 'page']);
        $params['sort'] = $sortField;
        $params['direction'] = $newDirection;

        $url = request()->url() . '?' . http_build_query($params);

        $class = $currentSort == $sortField ? 'text-blue-600 font-semibold' : 'text-gray-700 hover:text-blue-600';

        return '<a href="' . $url . '" class="' . $class . '">' . $label . $icon . '</a>';
    }

    $currentSort = request('sort', 'full_name');
    $currentDirection = request('direction', 'asc');
@endphp

@section('content')
<div class="w-full px-4 sm:px-6 py-6">
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

        <div class="p-4 border-b flex justify-between items-center">
            <h2 class="text-lg font-semibold">Laporan Tunjangan Kinerja Pegawai {{ $months[$selectedMonth] }} {{ $selectedYear }}</h2>
            @php
                $routePrefix = auth()->user()->role->name === 'monitoring' ? 'monitoring' : 'admin';
                $downloadRoute = $routePrefix . '.reports.performance-allowance.download-pdf';
            @endphp
            <a href="{{ route($downloadRoute, ['month' => $selectedMonth, 'year' => $selectedYear, 'employee_id' => request('employee_id')]) }}"
               class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700 inline-flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Download PDF
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{!! sortableLink('Nama Pegawai', 'full_name', $currentSort, $currentDirection) !!}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{!! sortableLink('Jabatan', 'position', $currentSort, $currentDirection) !!}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hari Aktif (Shift)</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hari Hadir</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tidak Hadir</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tepat Waktu Masuk</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tepat Waktu Pulang</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{!! sortableLink('Tepat Waktu Masuk & Pulang', 'on_time_both_days', $currentSort, $currentDirection) !!}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Terlambat Datang</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pulang Awal</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sakit</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cuti</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dinas Luar</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{!! sortableLink('Nilai Tunjangan', 'total_performance_allowance', $currentSort, $currentDirection) !!}</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($performanceData as $data)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <a href="{{ route('admin.reports.performance-allowance.detail', ['employee' => $data['employee']->id, 'year' => $selectedYear, 'month' => $selectedMonth]) }}"
                                   target="_blank"
                                   class="text-blue-600 hover:text-blue-800 hover:underline">
                                    {{ $data['employee']->full_name ?? 'N/A' }}
                                </a>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                {{ $data['employee']->position ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                {{ $data['active_days'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                {{ $data['present_days'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                {{ $data['absent_days'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                {{ $data['on_time_arrival_days'] ?? 0 }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                {{ $data['on_time_departure_days'] ?? 0 }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                {{ $data['on_time_both_days'] ?? 0 }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                {{ $data['late_arrival_days'] ?? 0 }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                {{ $data['early_departure_days'] ?? 0 }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                {{ $data['sick_days'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                {{ $data['leave_days'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                {{ $data['official_duty_days'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                Rp {{ number_format($data['total_performance_allowance'], 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="14" class="px-6 py-4 text-center text-gray-500">
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
                        Total Pembayaran Tunjangan Kinerja: Rp {{ number_format(collect($performanceData)->sum('total_performance_allowance'), 0, ',', '.') }}
                    </p>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection