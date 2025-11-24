@extends('layouts.app')
@section('title', 'Arsip Laporan Gaji')
@section('page-title', 'Arsip Laporan Gaji')

@section('content')
<div class="container mx-auto">
    <!-- Year Filter -->
    <form method="GET" class="mb-6 bg-white p-4 rounded-lg shadow">
        <div class="flex items-end space-x-4">
            <div class="flex-grow">
                <label for="year" class="block text-sm font-medium text-gray-700 mb-1">Pilih Tahun</label>
                <select name="year" id="year" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    @foreach($years as $yearOption)
                        <option value="{{ $yearOption }}" {{ $selectedYear == $yearOption ? 'selected' : '' }}>
                            {{ $yearOption }}
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                Tampilkan
            </button>
        </div>
    </form>

    <!-- Monthly Summaries -->
    <div class="bg-white rounded-lg shadow overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bulan</th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hari Aktif (Shift)</th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hari Hadir</th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tidak Hadir</th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tepat Waktu</th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Terlambat Datang</th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pulang Awal</th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sakit</th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cuti</th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dinas Luar</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($summaries as $summary)
                    <tr>
                        <td class="px-3 py-2 whitespace-nowrap text-sm font-medium">
                            <a href="{{ route('employee.salary.report.detail', ['year' => $selectedYear, 'month' => $summary['month_number']]) }}" class="text-blue-600 hover:text-blue-800 hover:underline">
                                {{ $summary['month_name'] }}
                            </a>
                        </td>
                        <td class="px-3 py-2 whitespace-nowrap text-sm text-center text-gray-700">{{ $summary['active_days'] }}</td>
                        <td class="px-3 py-2 whitespace-nowrap text-sm text-center text-green-600 font-semibold">{{ $summary['present_days'] }}</td>
                        <td class="px-3 py-2 whitespace-nowrap text-sm text-center text-red-800 font-bold">{{ $summary['absent_days'] }}</td>
                        <td class="px-3 py-2 whitespace-nowrap text-sm text-center text-gray-700">{{ $summary['on_time_days'] }}</td>
                        <td class="px-3 py-2 whitespace-nowrap text-sm text-center text-red-600 font-semibold">{{ $summary['late_days'] }}</td>
                        <td class="px-3 py-2 whitespace-nowrap text-sm text-center text-yellow-600 font-semibold">{{ $summary['early_leave_days'] }}</td>
                        <td class="px-3 py-2 whitespace-nowrap text-sm text-center text-purple-600 font-semibold">{{ $summary['sick_days'] }}</td>
                        <td class="px-3 py-2 whitespace-nowrap text-sm text-center text-purple-600 font-semibold">{{ $summary['leave_days'] }}</td>
                        <td class="px-3 py-2 whitespace-nowrap text-sm text-center text-purple-600 font-semibold">{{ $summary['official_duty_days'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="px-3 py-2 text-center text-gray-500">Tidak ada data arsip gaji untuk tahun ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
