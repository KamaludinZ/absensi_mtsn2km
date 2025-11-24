@extends('layouts.app')

@section('title', 'Laporan Uang Makan')
@section('page-title', 'Laporan Uang Makan Pegawai')

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
                    <a href="{{ route('admin.reports.meal-allowance') }}" class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700 ml-2 w-full text-center">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        <div class="p-4 border-b">
            <h2 class="text-lg font-semibold">Laporan Uang Makan Pegawai {{ $months[$selectedMonth] }} {{ $selectedYear }}</h2>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Pegawai</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jabatan</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jumlah Hari Hadir</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jumlah Hari Terlambat</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Uang Makan</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($mealAllowanceData as $data)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                {{ $data['employee']->full_name ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                {{ $data['employee']->position ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                {{ $data['present_days'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                {{ $data['late_days'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                Rp {{ number_format($data['present_days'] * 20000, 0, ',', '.') }} <!-- Assuming 20,000 per day -->
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                Tidak ada data pelaporan uang makan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(count($mealAllowanceData) > 0)
        <div class="p-4 bg-gray-50 border-t">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-sm text-gray-700">
                        Total Pegawai: {{ count($mealAllowanceData) }}
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-700">
                        Total Pembayaran Uang Makan: Rp {{ number_format(collect($mealAllowanceData)->sum('present_days') * 20000, 0, ',', '.') }}
                    </p>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection