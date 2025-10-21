@extends('layouts.app')

@section('title', 'Pengaturan Hari Aktif')
@section('page-title', 'Pengaturan Hari Aktif')

@section('content')
<div class="bg-white rounded-lg shadow p-6">
    <div class="mb-6">
        <h2 class="text-xl font-bold text-gray-800">Pengaturan Hari Aktif</h2>
        <p class="text-gray-600">Pilih hari-hari yang merupakan hari kerja/aktif dalam seminggu.</p>
    </div>
    
    <form action="{{ route('admin.holidays.update-active-days') }}" method="POST">
        @csrf
        @method('POST')
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
            <div class="flex items-center p-4 border rounded-lg">
                <input type="checkbox" name="active_days[]" value="1" id="monday" 
                       class="h-4 w-4 text-blue-600 rounded focus:ring-blue-500" 
                       {{ in_array(1, $activeDays) ? 'checked' : '' }}>
                <label for="monday" class="ml-3 block text-gray-700">Senin</label>
            </div>
            
            <div class="flex items-center p-4 border rounded-lg">
                <input type="checkbox" name="active_days[]" value="2" id="tuesday" 
                       class="h-4 w-4 text-blue-600 rounded focus:ring-blue-500" 
                       {{ in_array(2, $activeDays) ? 'checked' : '' }}>
                <label for="tuesday" class="ml-3 block text-gray-700">Selasa</label>
            </div>
            
            <div class="flex items-center p-4 border rounded-lg">
                <input type="checkbox" name="active_days[]" value="3" id="wednesday" 
                       class="h-4 w-4 text-blue-600 rounded focus:ring-blue-500" 
                       {{ in_array(3, $activeDays) ? 'checked' : '' }}>
                <label for="wednesday" class="ml-3 block text-gray-700">Rabu</label>
            </div>
            
            <div class="flex items-center p-4 border rounded-lg">
                <input type="checkbox" name="active_days[]" value="4" id="thursday" 
                       class="h-4 w-4 text-blue-600 rounded focus:ring-blue-500" 
                       {{ in_array(4, $activeDays) ? 'checked' : '' }}>
                <label for="thursday" class="ml-3 block text-gray-700">Kamis</label>
            </div>
            
            <div class="flex items-center p-4 border rounded-lg">
                <input type="checkbox" name="active_days[]" value="5" id="friday" 
                       class="h-4 w-4 text-blue-600 rounded focus:ring-blue-500" 
                       {{ in_array(5, $activeDays) ? 'checked' : '' }}>
                <label for="friday" class="ml-3 block text-gray-700">Jumat</label>
            </div>
            
            <div class="flex items-center p-4 border rounded-lg">
                <input type="checkbox" name="active_days[]" value="6" id="saturday" 
                       class="h-4 w-4 text-blue-600 rounded focus:ring-blue-500" 
                       {{ in_array(6, $activeDays) ? 'checked' : '' }}>
                <label for="saturday" class="ml-3 block text-gray-700">Sabtu</label>
            </div>
            
            <div class="flex items-center p-4 border rounded-lg">
                <input type="checkbox" name="active_days[]" value="7" id="sunday" 
                       class="h-4 w-4 text-blue-600 rounded focus:ring-blue-500" 
                       {{ in_array(7, $activeDays) ? 'checked' : '' }}>
                <label for="sunday" class="ml-3 block text-gray-700">Minggu</label>
            </div>
        </div>
        
        <div class="flex justify-end">
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                Simpan Pengaturan
            </button>
        </div>
    </form>
</div>

<div class="bg-white rounded-lg shadow p-6 mt-6">
    <h3 class="text-lg font-semibold mb-4">Laporan Hari Kerja</h3>
    <p class="text-gray-600 mb-4">Berikut adalah jumlah hari kerja per bulan berdasarkan pengaturan hari aktif dan hari libur:</p>
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        @for($i = 1; $i <= 12; $i++)
            @php
                $currentYear = now()->year;
                $holidaysCount = \App\Models\Holiday::whereYear('date', $currentYear)
                    ->whereMonth('date', $i)
                    ->where('is_active', true)
                    ->count();
                
                $startDate = \Carbon\Carbon::create($currentYear, $i, 1)->startOfMonth();
                $endDate = \Carbon\Carbon::create($currentYear, $i, 1)->endOfMonth();
                
                $workingDays = 0;
                $date = $startDate->copy();
                
                while ($date <= $endDate) {
                    $dayOfWeek = (int) $date->format('N');
                    if (in_array($dayOfWeek, $activeDays) && !\App\Models\Holiday::where('date', $date->format('Y-m-d'))->where('is_active', true)->exists()) {
                        $workingDays++;
                    }
                    $date->addDay();
                }
            @endphp
            <div class="p-4 border rounded-lg">
                <h4 class="font-medium text-gray-800">{{ \Carbon\Carbon::create()->year($currentYear)->month($i)->translatedFormat('F') }}</h4>
                <p class="text-2xl font-bold text-blue-600">{{ $workingDays }} hari</p>
                <p class="text-sm text-gray-500">{{ $holidaysCount }} hari libur</p>
            </div>
        @endfor
    </div>
</div>
@endsection