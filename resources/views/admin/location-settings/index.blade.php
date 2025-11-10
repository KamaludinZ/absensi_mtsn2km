@extends('layouts.app')

@section('title', 'Pengaturan Lokasi')

@push('styles')
<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
    crossorigin=""/>
<style>
    #map-preview {
        height: 500px;
        z-index: 1;
    }
</style>
@endpush

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

                <!-- Display with Coordinates -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Tampilkan Koordinat
                    </label>
                    <div class="flex items-center">
                        <input
                            type="hidden"
                            name="display_with_coordinates"
                            value="0"
                        >
                        <input
                            type="checkbox"
                            id="display_with_coordinates"
                            name="display_with_coordinates"
                            value="1"
                            {{ old('display_with_coordinates', $settings['display_with_coordinates']) ? 'checked' : '' }}
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                        >
                        <label for="display_with_coordinates" class="ml-2 block text-sm text-gray-700">
                            Tampilkan dengan titik koordinat
                        </label>
                    </div>
                    <p class="mt-1 text-sm text-gray-500">Jika diaktifkan, koordinat lokasi akan ditampilkan</p>
                </div>

                <!-- Display with Map -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Tampilkan Peta
                    </label>
                    <div class="flex items-center">
                        <input
                            type="hidden"
                            name="display_with_map"
                            value="0"
                        >
                        <input
                            type="checkbox"
                            id="display_with_map"
                            name="display_with_map"
                            value="1"
                            {{ old('display_with_map', $settings['display_with_map']) ? 'checked' : '' }}
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                        >
                        <label for="display_with_map" class="ml-2 block text-sm text-gray-700">
                            Tampilkan dengan maps sesuai koordinat
                        </label>
                    </div>
                    <p class="mt-1 text-sm text-gray-500">Jika diaktifkan, lokasi akan ditampilkan dalam bentuk peta</p>
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
            <div class="bg-gray-50 p-4 rounded-md border border-gray-300 mb-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                    <div>
                        <span class="font-medium text-gray-700">Latitude:</span>
                        <span id="current-lat" class="text-gray-900 ml-2">{{ $settings['geofence_center_lat'] }}</span>
                    </div>
                    <div>
                        <span class="font-medium text-gray-700">Longitude:</span>
                        <span id="current-lng" class="text-gray-900 ml-2">{{ $settings['geofence_center_lng'] }}</span>
                    </div>
                    <div>
                        <span class="font-medium text-gray-700">Radius:</span>
                        <span id="current-radius" class="text-gray-900 ml-2">{{ $settings['geofence_radius'] }}</span> meter
                    </div>
                </div>
            </div>
            <div id="map-preview" class="w-full bg-gray-200 rounded-md border border-gray-300"></div>
            <p class="mt-2 text-sm text-gray-500">
                <span class="inline-block w-3 h-3 bg-blue-500 rounded-full mr-1"></span> Marker menunjukkan pusat geofence
                <span class="inline-block w-3 h-3 bg-blue-500 bg-opacity-30 border border-blue-500 rounded-full mr-1 ml-3"></span> Lingkaran biru menunjukkan area geofence
            </p>
        </div>
    </div>
</div>

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
    integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
    crossorigin=""></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Get current settings
    let currentLat = parseFloat('{{ $settings['geofence_center_lat'] }}');
    let currentLng = parseFloat('{{ $settings['geofence_center_lng'] }}');
    let currentRadius = parseFloat('{{ $settings['geofence_radius'] }}');

    // Initialize map
    const map = L.map('map-preview').setView([currentLat, currentLng], 17);

    // Add OpenStreetMap tiles
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 19
    }).addTo(map);

    // Add marker
    let marker = L.marker([currentLat, currentLng]).addTo(map);
    marker.bindPopup(`<b>Pusat Geofence</b><br>Lat: ${currentLat.toFixed(6)}<br>Lng: ${currentLng.toFixed(6)}`);

    // Add circle
    let circle = L.circle([currentLat, currentLng], {
        color: '#3B82F6',
        fillColor: '#3B82F6',
        fillOpacity: 0.2,
        radius: currentRadius
    }).addTo(map);

    // Update map when form inputs change
    const latInput = document.getElementById('geofence_center_lat');
    const lngInput = document.getElementById('geofence_center_lng');
    const radiusInput = document.getElementById('geofence_radius');

    function updateMap() {
        const newLat = parseFloat(latInput.value);
        const newLng = parseFloat(lngInput.value);
        const newRadius = parseFloat(radiusInput.value);

        // Validate inputs
        if (isNaN(newLat) || isNaN(newLng) || isNaN(newRadius)) {
            return;
        }

        // Update current values display
        document.getElementById('current-lat').textContent = newLat.toFixed(6);
        document.getElementById('current-lng').textContent = newLng.toFixed(6);
        document.getElementById('current-radius').textContent = newRadius;

        // Update marker position
        marker.setLatLng([newLat, newLng]);
        marker.setPopupContent(`<b>Pusat Geofence</b><br>Lat: ${newLat.toFixed(6)}<br>Lng: ${newLng.toFixed(6)}`);

        // Update circle
        circle.setLatLng([newLat, newLng]);
        circle.setRadius(newRadius);

        // Center map on new location
        map.setView([newLat, newLng], map.getZoom());
    }

    // Add event listeners
    latInput.addEventListener('input', updateMap);
    lngInput.addEventListener('input', updateMap);
    radiusInput.addEventListener('input', updateMap);

    // Add button to get current location
    const getCurrentLocationBtn = document.createElement('button');
    getCurrentLocationBtn.type = 'button';
    getCurrentLocationBtn.className = 'mt-4 bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-md transition duration-300 ease-in-out inline-flex items-center';
    getCurrentLocationBtn.innerHTML = `
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
        Gunakan Lokasi Saya
    `;

    getCurrentLocationBtn.addEventListener('click', function() {
        if (navigator.geolocation) {
            getCurrentLocationBtn.disabled = true;
            getCurrentLocationBtn.innerHTML = `
                <svg class="animate-spin h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Mendapatkan lokasi...
            `;

            navigator.geolocation.getCurrentPosition(
                function(position) {
                    latInput.value = position.coords.latitude.toFixed(6);
                    lngInput.value = position.coords.longitude.toFixed(6);
                    updateMap();

                    getCurrentLocationBtn.disabled = false;
                    getCurrentLocationBtn.innerHTML = `
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        Gunakan Lokasi Saya
                    `;
                },
                function(error) {
                    alert('Tidak dapat mengambil lokasi: ' + error.message);
                    getCurrentLocationBtn.disabled = false;
                    getCurrentLocationBtn.innerHTML = `
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        Gunakan Lokasi Saya
                    `;
                }
            );
        } else {
            alert('Browser Anda tidak mendukung geolocation');
        }
    });

    // Insert button before the submit button
    const submitButton = document.querySelector('button[type="submit"]');
    submitButton.parentNode.insertBefore(getCurrentLocationBtn, submitButton);
    submitButton.parentNode.insertBefore(document.createTextNode(' '), submitButton);
});
</script>
@endsection