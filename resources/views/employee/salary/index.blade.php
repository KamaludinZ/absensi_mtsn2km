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
    <div class="space-y-4">
        @foreach($summaries as $summary)
            <div class="bg-white rounded-lg shadow p-5">
                <h3 class="text-lg font-bold mb-4">
                    <a href="{{ route('employee.salary.report.detail', ['year' => $selectedYear, 'month' => $summary['month_number']]) }}" class="text-blue-600 hover:text-blue-800 hover:underline">
                        {{ $summary['month_name'] }} {{ $selectedYear }}
                    </a>
                </h3>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Hari Aktif (Shift):</span>
                        <span class="font-semibold">{{ $summary['active_days'] }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Hari Hadir:</span>
                        <span class="font-semibold text-green-600">{{ $summary['present_days'] }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Tepat Waktu:</span>
                        <span class="font-semibold">{{ $summary['on_time_days'] }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Terlambat Datang:</span>
                        <span class="font-semibold text-red-600">{{ $summary['late_days'] }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Pulang Awal:</span>
                        <span class="font-semibold text-yellow-600">{{ $summary['early_leave_days'] }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Sakit:</span>
                        <span class="font-semibold text-purple-600">{{ $summary['sick_days'] }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Cuti:</span>
                        <span class="font-semibold text-purple-600">{{ $summary['leave_days'] }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Dinas Luar:</span>
                        <span class="font-semibold text-purple-600">{{ $summary['official_duty_days'] }}</span>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
