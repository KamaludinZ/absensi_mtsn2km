@extends('layouts.app')

@section('title', 'Monitoring Laporan Gaji')
@section('page-title', 'Monitoring Laporan Gaji Pegawai')
@section('container-class', '')

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
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Pegawai</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jabatan</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Gaji Pokok</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Uang Makan</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Uang Transport</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Tunjangan Kinerja</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Gaji</th>
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
