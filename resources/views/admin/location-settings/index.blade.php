@extends('layouts.app')

@section('title', 'Pengaturan Lokasi')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="bg-white rounded-lg shadow-md p-6">
        <h1 class="text-2xl font-bold text-gray-800 mb-6">Pengaturan Lokasi Absensi</h1>
        
        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

        <form action="{{ route('admin.location-settings.store') }}" method="POST">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Latitude -->
                <div>
                    <label for="geofence_center_lat" class="block text-sm font-medium text-gray-700 mb-1">
                        Latitude Pusat Geofence
                    </label>
                    <input 
                        type="number" 
                        id="geofence_center_lat" 
                        name="geofence_center_lat" 
                        step="any" 
                        value="{{ old('geofence_center_lat', $settings['geofence_center_lat']) }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                        required
                    >
                    <p class="mt-1 text-sm text-gray-500">Contoh: -6.200000</p>
                </div>

                <!-- Longitude -->
                <div>
                    <label for="geofence_center_lng" class="block text-sm font-medium text-gray-700 mb-1">
                        Longitude Pusat Geofence
                    </label>
                    <input 
                        type="number" 
                        id="geofence_center_lng" 
                        name="geofence_center_lng" 
                        step="any" 
                        value="{{ old('geofence_center_lng', $settings['geofence_center_lng']) }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                        required
                    >
                    <p class="mt-1 text-sm text-gray-500">Contoh: 106.816666</p>
                </div>

                <!-- Geofence Radius -->
                <div>
                    <label for="geofence_radius" class="block text-sm font-medium text-gray-700 mb-1">
                        Radius Geofence (meter)
                    </label>
                    <input 
                        type="number" 
                        id="geofence_radius" 
                        name="geofence_radius" 
                        value="{{ old('geofence_radius', $settings['geofence_radius']) }}"
                        min="1"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                        required
                    >
                    <p class="mt-1 text-sm text-gray-500">Jarak maksimum dari pusat geofence (dalam meter)</p>
                </div>

                <!-- Location Required -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Lokasi Diperlukan
                    </label>
                    <div class="flex items-center">
                        <input 
                            type="hidden" 
                            name="location_required" 
                            value="0"
                        >
                        <input 
                            type="checkbox" 
                            id="location_required" 
                            name="location_required" 
                            value="1"
                            {{ old('location_required', $settings['location_required']) ? 'checked' : '' }}
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                        >
                        <label for="location_required" class="ml-2 block text-sm text-gray-700">
                            Wajibkan lokasi saat absensi
                        </label>
                    </div>
                    <p class="mt-1 text-sm text-gray-500">Jika diaktifkan, pengguna harus memberikan izin lokasi untuk absensi</p>
                </div>
            </div>

            <div class="mt-6">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md transition duration-300 ease-in-out">
                    Simpan Pengaturan
                </button>
            </div>
        </form>
        
        <!-- Map Preview -->
        <div class="mt-8">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Pratinjau Geofence</h2>
            <div id="map-preview" class="h-64 w-full bg-gray-200 rounded-md border border-gray-300 flex items-center justify-center">
                <p class="text-gray-600">Peta akan ditampilkan di sini</p>
            </div>
            <p class="mt-2 text-sm text-gray-500">Koordinat: {{ $settings['geofence_center_lat'] }}, {{ $settings['geofence_center_lng'] }} | Radius: {{ $settings['geofence_radius'] }} meter</p>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Get current settings
    const lat = parseFloat('{{ $settings['geofence_center_lat'] }}');
    const lng = parseFloat('{{ $settings['geofence_center_lng'] }}');
    const radius = parseFloat('{{ $settings['geofence_radius'] }}');
    
    // Update map preview - just show coordinates for now
    const mapPreview = document.getElementById('map-preview');
    mapPreview.innerHTML = `
        <div class="text-center">
            <p class="text-lg font-medium">Koordinat Geofence:</p>
            <p class="text-gray-700 mt-2">${lat.toFixed(6)}, ${lng.toFixed(6)}</p>
            <p class="text-gray-600 mt-1">Radius: ${radius} meter</p>
            <p class="text-sm text-gray-500 mt-2">Pratinjau peta akan ditampilkan dalam implementasi selanjutnya</p>
        </div>
    `;
});
</script>
@endsection