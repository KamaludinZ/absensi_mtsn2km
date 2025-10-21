@extends('layouts.app')

@section('title', 'Laporan Hari Kerja')
@section('page-title', 'Laporan Hari Kerja')

@section('content')
<div class="bg-white rounded-lg shadow p-6">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-bold text-gray-800">Laporan Hari Kerja</h2>
        
        <form method="GET" class="flex items-center space-x-2">
            <select name="month" class="border border-gray-300 rounded px-3 py-2">
                @for($i = 1; $i <= 12; $i++)
                    <option value="{{ $i }}" {{ $i == $currentMonth ? 'selected' : '' }}>
                        {{ \Carbon\Carbon::create()->month($i)->translatedFormat('F') }}
                    </option>
                @endfor
            </select>
            
            <select name="year" class="border border-gray-300 rounded px-3 py-2">
                @for($year = now()->year - 2; $year <= now()->year + 2; $year++)
                    <option value="{{ $year }}" {{ $year == $currentYear ? 'selected' : '' }}>
                        {{ $year }}
                    </option>
                @endfor
            </select>
            
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md">Tampilkan</button>
        </form>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div class="bg-blue-50 p-6 rounded-lg text-center">
            <p class="text-4xl font-bold text-blue-600">{{ $workingDays }}</p>
            <p class="text-gray-600 mt-2">Hari Kerja</p>
        </div>
        
        <div class="bg-gray-50 p-6 rounded-lg text-center">
            <p class="text-4xl font-bold text-gray-600">
                {{ now()->year($currentYear)->month($currentMonth)->endOfMonth()->day }}
            </p>
            <p class="text-gray-600 mt-2">Total Hari dalam Bulan</p>
        </div>
        
        <div class="bg-red-50 p-6 rounded-lg text-center">
            <p class="text-4xl font-bold text-red-600">{{ count($holidays) }}</p>
            <p class="text-gray-600 mt-2">Hari Libur</p>
        </div>
    </div>
    
    <div class="mb-6">
        <h3 class="text-lg font-semibold mb-4">Hari Libur dalam Bulan {{ \Carbon\Carbon::create()->year($currentYear)->month($currentMonth)->translatedFormat('F Y') }}</h3>
        @if(count($holidays) > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Hari Libur</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hari</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($holidays as $holiday)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">{{ $holiday->date->format('d/m/Y') }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">{{ $holiday->name }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">{{ $holiday->date->translatedFormat('l') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-gray-500">Tidak ada hari libur dalam bulan ini.</p>
        @endif
    </div>
    
    <div>
        <h3 class="text-lg font-semibold mb-4">Keterangan Hari Aktif</h3>
        <div class="flex flex-wrap gap-2">
            @php
                $dayNames = [
                    1 => 'Senin',
                    2 => 'Selasa', 
                    3 => 'Rabu',
                    4 => 'Kamis',
                    5 => 'Jumat',
                    6 => 'Sabtu',
                    7 => 'Minggu'
                ];
            @endphp
            @foreach($activeDays as $day)
                <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm">
                    {{ $dayNames[$day] }}
                </span>
            @endforeach
        </div>
    </div>
</div>
@endsection