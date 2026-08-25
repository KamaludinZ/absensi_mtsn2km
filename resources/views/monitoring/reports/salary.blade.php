@extends('layouts.app')

@section('title', 'Monitoring Laporan Gaji')
@section('page-title', 'Monitoring Laporan Gaji Pegawai')
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
            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">

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
                        @foreach($years as $year)
                            <option value="{{ $year }}" {{ $selectedYear == $year ? 'selected' : '' }}>
                                {{ $year }}
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
                    <a href="{{ route('monitoring.reports.salary') }}" class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700 ml-2 w-full text-center">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        <div class="p-4 border-b">
            <div class="flex justify-between items-start">
                <div>
                    <h2 class="text-lg font-semibold">Monitoring Laporan Gaji Pegawai</h2>
                    <p class="text-sm text-gray-600 mt-1">
                        Periode: <span class="font-medium">{{ $months[$selectedMonth] }} {{ $selectedYear }}</span>
                    </p>
                </div>
                <div>
                    <a href="{{ route('monitoring.reports.salary.download-pdf', ['month' => $selectedMonth, 'year' => $selectedYear, 'employee_id' => request('employee_id')]) }}"
                       class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700 inline-flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Download PDF
                    </a>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{!! sortableLink('Nama Pegawai', 'full_name', $currentSort, $currentDirection) !!}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{!! sortableLink('Jabatan', 'position', $currentSort, $currentDirection) !!}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{!! sortableLink('Gaji Pokok', 'base_salary', $currentSort, $currentDirection) !!}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{!! sortableLink('Total Uang Makan', 'total_meal_allowance', $currentSort, $currentDirection) !!}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{!! sortableLink('Total Uang Transport', 'total_transport_allowance', $currentSort, $currentDirection) !!}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{!! sortableLink('Total Tunjangan Kinerja', 'total_performance_allowance', $currentSort, $currentDirection) !!}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{!! sortableLink('Total Gaji', 'total_salary', $currentSort, $currentDirection) !!}</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($salaryData as $data)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ $data['employee']->full_name }}</div>
                                <div class="text-sm text-gray-500">{{ $data['employee']->nip ?? '' }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                {{ $data['employee']->position ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                Rp {{ number_format($data['base_salary'], 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                Rp {{ number_format($data['total_meal_allowance'], 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                Rp {{ number_format($data['total_transport_allowance'], 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                Rp {{ number_format($data['total_performance_allowance'], 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">
                                Rp {{ number_format($data['total_salary'], 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                                Tidak ada data gaji untuk ditampilkan. Silakan sesuaikan filter di atas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(count($salaryData) > 0)
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <tfoot class="bg-gray-100 font-semibold">
                    <tr>
                        <td class="px-6 py-3 text-left text-sm text-gray-700">Jumlah {{ count($salaryData) }} Pegawai</td>
                        <td class="px-6 py-3 text-left text-sm text-gray-700"></td>
                        <td class="px-6 py-3 text-right text-sm text-gray-900">Rp {{ number_format(collect($salaryData)->sum('base_salary'), 0, ',', '.') }}</td>
                        <td class="px-6 py-3 text-right text-sm text-gray-900">Rp {{ number_format(collect($salaryData)->sum('total_meal_allowance'), 0, ',', '.') }}</td>
                        <td class="px-6 py-3 text-right text-sm text-gray-900">Rp {{ number_format(collect($salaryData)->sum('total_transport_allowance'), 0, ',', '.') }}</td>
                        <td class="px-6 py-3 text-right text-sm text-gray-900">Rp {{ number_format(collect($salaryData)->sum('total_performance_allowance'), 0, ',', '.') }}</td>
                        <td class="px-6 py-3 text-right text-sm text-gray-900">Rp {{ number_format(collect($salaryData)->sum('total_salary'), 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
        @endif
    </div>
</div>
@endsection
