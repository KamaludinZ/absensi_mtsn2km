@extends('layouts.app')

@section('title', 'Absensi Pegawai')
@section('page-title', 'Absensi')

@if(isset($locationSettings['display_with_map']) && $locationSettings['display_with_map'])
@push('styles')
<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
    crossorigin=""/>
<style>
    #attendance-map {
        height: 400px;
        z-index: 1;
    }
    @media (max-width: 640px) {
        #attendance-map {
            height: 220px;
        }
    }
</style>
@endpush
@endif

@section('content')
<div class="max-w-2xl mx-auto">
    <div x-data="attendanceApp()" class="bg-white rounded-lg shadow p-8">
        @if($attendance && $attendance->clock_in && $attendance->clock_out)
            <div class="text-center">
                <svg class="mx-auto h-16 w-16 text-green-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <h2 class="text-2xl font-bold text-gray-800 mb-4">Absensi Hari Ini Sudah Lengkap</h2>
                <p class="text-gray-600">Anda sudah melakukan absen masuk dan pulang hari ini.</p>
            </div>
        @else
            <!-- Step 1: Choose attendance type -->
            <div x-show="step === 1">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">Pilih Jenis Absensi</h2>

                <!-- Clock & Date -->
                <div class="mb-6 bg-blue-50 border border-blue-200 rounded-lg p-4 text-center">
                    <p class="text-2xl font-bold text-gray-800" x-text="currentTime"></p>
                    <p class="text-sm text-gray-600 mt-1" x-text="currentDate"></p>
                </div>

                <!-- Location Information -->
                @if(isset($locationSettings['display_with_coordinates']) && $locationSettings['display_with_coordinates'])
                <div class="mb-4 bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">Lokasi Anda</h3>
                    <div x-show="locationEnabled" class="flex items-center">
                        <svg class="w-5 h-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <div>
                            <p class="text-sm text-gray-700">Koordinat:
                                <span x-text="currentLocation ? currentLocation.latitude + ', ' + currentLocation.longitude : 'Mengambil lokasi...'"></span>
                            </p>
                            <p class="text-sm text-gray-600" x-text="currentLocation ? 'Akurasi: ' + Math.round(currentLocation.accuracy) + ' meter' : 'Akurasi: -'"></p>
                            <template x-if="lastLocationCheck">
                                <div>
                                    <p class="text-sm" x-bind:class="locationInGeofence ? 'text-green-600' : 'text-red-600'"
                                       x-text="locationInGeofence ? ('Lokasi Anda berada di dalam area geofence (' + Math.round(lastLocationCheck.distance) + 'm dari pusat)') : ('Peringatan: Lokasi Anda di luar area geofence (' + Math.round(lastLocationCheck.distance) + 'm dari pusat)')">
                                    </p>
                                    <p class="text-xs text-gray-500" x-show="lastLocationCheck && !lastLocationCheck.withinStrictRadius && lastLocationCheck.withinAllowedRadius">
                                        * Diperbolehkan karena toleransi akurasi GPS (radius: <span x-text="geofenceRadius"></span>m + akurasi: <span x-text="Math.round(currentLocation?.accuracy || 0)"></span>m)
                                    </p>
                                </div>
                            </template>
                            <template x-if="!lastLocationCheck">
                                <p class="text-sm text-gray-600" x-text="currentLocation ? 'Lokasi telah diverifikasi' : ''"></p>
                            </template>
                        </div>
                    </div>
                    <div x-show="!locationEnabled" class="flex items-center">
                        <svg class="w-5 h-5 text-yellow-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.464 0L4.31 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                        </svg>
                        <div>
                            <p class="text-sm text-gray-700">Izin lokasi belum diberikan</p>
                            <button @click="requestLocation()" class="text-sm text-blue-600 hover:text-blue-800 underline">Izinkan akses lokasi</button>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Attendance Type Selection -->
                <div class="space-y-4">
                    <button
                        @click="selectAttendanceType('clock-in')"
                        :disabled="isDisabled('clock-in') || (locationRequired && !locationInGeofence) || cooldownActive"
                        :class="(isDisabled('clock-in') || (locationRequired && !locationInGeofence) || cooldownActive) ? 'bg-gray-300 cursor-not-allowed' : 'bg-green-600 hover:bg-green-700'"
                        class="w-full py-4 px-6 rounded-lg text-white font-bold text-lg transition duration-200 disabled:opacity-50">
                        <div class="flex items-center justify-center">
                            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span x-show="!cooldownActive">Absen Masuk</span>
                            <span x-show="cooldownActive" x-text="'Tunggu ' + getCooldownDisplay()"></span>
                        </div>
                    </button>

                    <button
                        @click="selectAttendanceType('clock-out')"
                        :disabled="isDisabled('clock-out') || (locationRequired && !locationInGeofence) || cooldownActive"
                        :class="(isDisabled('clock-out') || (locationRequired && !locationInGeofence) || cooldownActive) ? 'bg-gray-300 cursor-not-allowed' : 'bg-blue-600 hover:bg-blue-700'"
                        class="w-full py-4 px-6 rounded-lg text-white font-bold text-lg transition duration-200 disabled:opacity-50">
                        <div class="flex items-center justify-center">
                            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span x-show="!cooldownActive">Absen Pulang</span>
                            <span x-show="cooldownActive" x-text="'Tunggu ' + getCooldownDisplay()"></span>
                        </div>
                    </button>
                </div>

                <!-- Map Display -->
                @if(isset($locationSettings['display_with_map']) && $locationSettings['display_with_map'])
                <div class="mt-6 mb-4" x-show="locationEnabled && geofenceCenter">
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">Peta Lokasi</h3>
                    <div id="attendance-map" class="w-full bg-gray-200 rounded-md border border-gray-300"></div>
                    <p class="mt-2 text-xs text-gray-500">
                        <span class="inline-block w-3 h-3 bg-red-500 rounded-full mr-1"></span> Pusat Geofence
                        <span class="inline-block w-3 h-3 bg-blue-500 rounded-full mr-1 ml-3"></span> Lokasi Anda
                        <span class="inline-block w-3 h-3 bg-red-500 bg-opacity-30 border border-red-500 rounded-full mr-1 ml-3"></span> Area Geofence
                    </p>
                </div>
                @endif

                <!-- Cooldown info message -->
                <div x-show="cooldownActive" class="mt-4 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-yellow-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-sm text-yellow-800">
                            Mohon tunggu <span class="font-semibold" x-text="getCooldownDisplay()"></span> sebelum dapat melakukan absen.
                            Fitur ini mencegah kesalahan klik tombol yang tidak disengaja.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Step 2: Capture photo -->
            <div x-show="step === 2">
                <h2 class="text-2xl font-bold text-gray-800 mb-6" x-text="attendanceType === 'clock-in' ? 'Ambil Foto Selfie - Absen Masuk' : 'Ambil Foto Selfie - Absen Pulang'"></h2>

                <!-- Clock & Date -->
                <div class="mb-4 bg-blue-50 border border-blue-200 rounded-lg p-4 text-center">
                    <p class="text-2xl font-bold text-gray-800" x-text="currentTime"></p>
                    <p class="text-sm text-gray-600 mt-1" x-text="currentDate"></p>
                </div>
                
                <!-- Location Information -->
                @if(isset($locationSettings['display_with_coordinates']) && $locationSettings['display_with_coordinates'])
                <div class="mb-4 bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <h3 class="text-lg font-semibold text-gray-800 mb-1">Lokasi Absensi</h3>
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <div>
                            <p class="text-sm text-gray-700">Koordinat:
                                <span x-text="currentLocation ? currentLocation.latitude + ', ' + currentLocation.longitude : 'Mengambil lokasi...'"></span>
                            </p>
                            <p class="text-sm text-gray-600" x-text="currentLocation ? 'Akurasi: ' + Math.round(currentLocation.accuracy) + ' meter' : 'Akurasi: -'"></p>
                            <template x-if="lastLocationCheck">
                                <div>
                                    <p class="text-sm" x-bind:class="locationInGeofence ? 'text-green-600' : 'text-red-600'"
                                       x-text="locationInGeofence ? ('Sesuai area geofence (' + Math.round(lastLocationCheck.distance) + 'm dari pusat)') : ('Peringatan: Di luar area geofence (' + Math.round(lastLocationCheck.distance) + 'm dari pusat)')">
                                    </p>
                                    <p class="text-xs text-gray-500" x-show="lastLocationCheck && !lastLocationCheck.withinStrictRadius && lastLocationCheck.withinAllowedRadius">
                                        * Diperbolehkan karena toleransi akurasi GPS (radius: <span x-text="geofenceRadius"></span>m + akurasi: <span x-text="Math.round(currentLocation?.accuracy || 0)"></span>m)
                                    </p>
                                </div>
                            </template>
                            <template x-if="!lastLocationCheck">
                                <p class="text-sm text-gray-600" x-text="currentLocation ? 'Lokasi telah diverifikasi' : ''"></p>
                            </template>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Camera section (only show if selfie is required) -->
                <div class="mb-6" x-show="requireSelfie">
                    <video x-ref="video" autoplay playsinline class="w-full rounded-lg border border-gray-300" style="display:none;"></video>
                    <canvas x-ref="canvas" class="w-full rounded-lg border border-gray-300" style="display:none;"></canvas>
                    <img x-show="capturedImage" :src="capturedImage" class="w-full rounded-lg border border-gray-300">
                </div>

                <!-- Info message when selfie is not required -->
                <div x-show="!requireSelfie" class="mb-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <p class="text-center text-gray-700">Foto selfie tidak diperlukan untuk absensi ini. Klik tombol di bawah untuk mengirim absensi.</p>
                </div>

                <!-- Loading State -->
                <div x-show="submitting" class="text-center mb-4">
                    <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                    <p class="text-gray-600 mt-2">Mengirim absensi beserta lokasi...</p>
                </div>

                <div class="flex space-x-4" x-show="!submitting">
                    <!-- Show "Ambil Foto" button only if selfie is required -->
                    <button @click="captureImage" x-show="requireSelfie && !capturedImage" class="flex-1 bg-blue-600 text-white py-3 rounded-lg hover:bg-blue-700 transition duration-200 font-semibold">
                        Ambil Foto
                    </button>

                    <!-- Show submit and retake buttons when selfie is captured -->
                    <div x-show="requireSelfie && capturedImage" class="w-full space-y-4">
                        <button @click="submitAttendance" class="w-full bg-green-600 text-white py-3 rounded-lg hover:bg-green-700 transition duration-200 font-semibold">
                            Kirim Absensi
                        </button>
                        <button @click="retakeImage" class="w-full bg-yellow-600 text-white py-3 rounded-lg hover:bg-yellow-700 transition duration-200 font-semibold">
                            Foto Ulang
                        </button>
                    </div>

                    <!-- Show submit button directly when selfie is not required -->
                    <button @click="submitAttendance" x-show="!requireSelfie" class="w-full bg-green-600 text-white py-3 rounded-lg hover:bg-green-700 transition duration-200 font-semibold">
                        Kirim Absensi
                    </button>
                </div>
                
                <div class="mt-4">
                    <button @click="step = 1; resetCamera()" class="w-full bg-gray-500 text-white py-2 rounded-lg hover:bg-gray-600 transition duration-200">
                        Kembali
                    </button>
                </div>
            </div>

            <!-- Success State - Will auto-redirect -->
            <div x-show="step === 3" class="text-center">
                <svg class="mx-auto h-16 w-16 text-green-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <h2 class="text-2xl font-bold text-gray-800 mb-4">Absensi Berhasil!</h2>
                <p class="text-lg text-gray-600 mb-6" x-text="message"></p>
                <p class="text-sm text-gray-500">Mengarahkan ke riwayat absensi...</p>
            </div>

            <!-- Error State with Retry -->
            <div x-show="step === 4" class="text-center">
                <svg class="mx-auto h-16 w-16 text-red-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <h2 class="text-2xl font-bold text-gray-800 mb-4">Absensi Gagal</h2>
                <p class="text-lg text-red-600 mb-6" x-text="error"></p>
                <button @click="step = 2; error = ''; capturedImage = null; startCamera()" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition duration-200 font-semibold">
                    Coba Lagi
                </button>
            </div>

            <div x-show="error" class="mt-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                <span x-text="error"></span>
            </div>
        @endif
    </div>
</div>

@push('scripts')
@if(isset($locationSettings['display_with_map']) && $locationSettings['display_with_map'])
<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
    integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
    crossorigin=""></script>
@endif

<script>
function attendanceApp() {
    return {
        step: 1, // 1 = choose type, 2 = capture photo
        attendanceType: null, // 'clock-in' or 'clock-out'
        capturedImage: null,
        message: '',
        error: '',
        stream: null,
        hasClockIn: {{ $attendance && $attendance->clock_in ? 'true' : 'false' }},
        hasClockOut: {{ $attendance && $attendance->clock_out ? 'true' : 'false' }},
        currentTime: '',
        currentDate: '',
        formattedDateTime: '',
        badge: 'Jam Datang',
        submitting: false,
        locationEnabled: false,
        locationRequired: false,
        currentLocation: null,
        locationInGeofence: false,
        lastLocationCheck: null, // Detailed location check information
        geofenceCenter: null,
        geofenceRadius: null,
        map: null,
        geofenceCircle: null,
        geofenceMarker: null,
        userMarker: null,
        displayWithMap: {{ isset($locationSettings['display_with_map']) && $locationSettings['display_with_map'] ? 'true' : 'false' }},
        displayWithCoordinates: {{ isset($locationSettings['display_with_coordinates']) && $locationSettings['display_with_coordinates'] ? 'true' : 'false' }},
        requireSelfie: {{ isset($attendanceSettings['require_selfie_photo']) && $attendanceSettings['require_selfie_photo'] ? 'true' : 'false' }},
        clockInTimestamp: null, // Timestamp when user clicked "Absen Masuk"
        cooldownActive: false, // Whether cooldown is active
        cooldownRemainingSeconds: 0, // Remaining seconds for cooldown
        COOLDOWN_DURATION: 600, // 10 minutes in seconds

        init() {
            // Initialize clock and schedule
            this.updateClock();
            this.fetchScheduleStatus();
            setInterval(() => this.updateClock(), 1000);
            setInterval(() => this.fetchScheduleStatus(), 60000);

            // Initialize location settings
            this.fetchLocationSettings();

            // Check if there's an active cooldown from previous session
            this.checkCooldownStatus();
            setInterval(() => this.checkCooldownStatus(), 1000);
        },

        // Check if cooldown is still active
        checkCooldownStatus() {
            const storedTimestamp = localStorage.getItem('clockInTimestamp');
            if (storedTimestamp) {
                const timePassed = Math.floor((Date.now() - parseInt(storedTimestamp)) / 1000);
                if (timePassed < this.COOLDOWN_DURATION) {
                    this.cooldownActive = true;
                    this.cooldownRemainingSeconds = this.COOLDOWN_DURATION - timePassed;
                } else {
                    // Cooldown expired, clear it
                    localStorage.removeItem('clockInTimestamp');
                    this.cooldownActive = false;
                    this.cooldownRemainingSeconds = 0;
                }
            } else {
                this.cooldownActive = false;
                this.cooldownRemainingSeconds = 0;
            }
        },

        // Format remaining cooldown time as MM:SS
        getCooldownDisplay() {
            const minutes = Math.floor(this.cooldownRemainingSeconds / 60);
            const seconds = this.cooldownRemainingSeconds % 60;
            return `${minutes}:${seconds.toString().padStart(2, '0')}`;
        },

        // Fetch location settings from the server using public API endpoints
        async fetchLocationSettings() {
            try {
                // Fetch each setting individually with proper error handling
                // This is more robust than using Promise.all which fails if any request fails

                // Add cache busting and no-cache headers to always get fresh data
                const fetchOptions = {
                    signal: AbortSignal.timeout(15000), // 15 second timeout per request (increased for slow servers)
                    cache: 'no-store', // Don't use cached response
                    headers: {
                        'Cache-Control': 'no-cache, no-store, must-revalidate',
                        'Pragma': 'no-cache',
                        'Expires': '0'
                    }
                };

                // Process geofence center coordinates
                let latValue = null;
                let lngValue = null;

                try {
                    const latResponse = await fetch('/api/location-settings/geofence-center-lat', fetchOptions);
                    if (latResponse.ok) {
                        const latData = await latResponse.json();
                        latValue = latData.value ? parseFloat(latData.value) : null;
                    }
                } catch (e) {
                    if (e.name !== 'TimeoutError' && e.name !== 'AbortError') {
                        console.warn('Failed to fetch geofence center latitude:', e.message);
                    }
                }

                try {
                    const lngResponse = await fetch('/api/location-settings/geofence-center-lng', fetchOptions);
                    if (lngResponse.ok) {
                        const lngData = await lngResponse.json();
                        lngValue = lngData.value ? parseFloat(lngData.value) : null;
                    }
                } catch (e) {
                    if (e.name !== 'TimeoutError' && e.name !== 'AbortError') {
                        console.warn('Failed to fetch geofence center longitude:', e.message);
                    }
                }

                // Only set geofence center if both values are valid
                if (latValue !== null && lngValue !== null && !isNaN(latValue) && !isNaN(lngValue)) {
                    this.geofenceCenter = {
                        lat: latValue,
                        lng: lngValue
                    };
                    console.log('Geofence center loaded from API:', this.geofenceCenter);
                } else {
                    // Use default geofence if API values are invalid
                    console.warn('Using default geofence center - API values invalid:', { latValue, lngValue });
                    this.geofenceCenter = {
                        lat: -8.170544, // Default coordinates (updated to match current settings)
                        lng: 112.707610
                    };
                }

                // Process geofence radius
                try {
                    const radiusResponse = await fetch('/api/location-settings/geofence-radius', fetchOptions);
                    if (radiusResponse.ok) {
                        const radiusData = await radiusResponse.json();
                        const radiusValue = parseFloat(radiusData.value);
                        this.geofenceRadius = isNaN(radiusValue) ? 100 : radiusValue;
                    } else {
                        this.geofenceRadius = 100; // Default radius
                    }
                } catch (e) {
                    if (e.name !== 'TimeoutError' && e.name !== 'AbortError') {
                        console.warn('Failed to fetch geofence radius:', e.message);
                    }
                    this.geofenceRadius = 100; // Default radius
                }

                // Process location required setting
                try {
                    const requiredResponse = await fetch('/api/location-settings/location-required', fetchOptions);
                    if (requiredResponse.ok) {
                        const requiredData = await requiredResponse.json();
                        this.locationRequired = requiredData.value === 'true' || requiredData.value === true;
                    } else {
                        this.locationRequired = true; // Default to required
                    }
                } catch (e) {
                    if (e.name !== 'TimeoutError' && e.name !== 'AbortError') {
                        console.warn('Failed to fetch location required setting:', e.message);
                    }
                    this.locationRequired = true; // Default to required
                }

                // Request location if required
                if (this.locationRequired) {
                    this.requestLocation();
                }

                // Initialize map after settings are loaded (only if display_with_map is enabled)
                this.$nextTick(() => {
                    if (this.geofenceCenter && this.displayWithMap) {
                        this.initializeMap();
                    }
                });
            } catch (error) {
                // Only log non-timeout/abort errors
                if (error.name !== 'TimeoutError' && error.name !== 'AbortError') {
                    console.warn('Unexpected error while fetching location settings:', error.message);
                }

                // Set default values and continue - app should work even if settings fetch fails
                console.warn('Using fallback default geofence settings due to error');
                this.geofenceCenter = {
                    lat: -8.170544, // Default coordinates (updated to match current settings)
                    lng: 112.707610
                };
                this.geofenceRadius = 200;
                this.locationRequired = true; // Default to required
                
                // Request location if required
                if (this.locationRequired) {
                    this.requestLocation();
                }
            }
        },

        // Check if the attendance type is disabled based on current status
        isDisabled(type) {
            if (type === 'clock-in') {
                // Can't clock in if already clocked in today and haven't clocked out yet
                return this.hasClockIn && !this.hasClockOut;
            } else if (type === 'clock-out') {
                // Can't clock out if hasn't clocked in yet
                return !this.hasClockIn || this.hasClockOut;
            }
            return false;
        },

        // Select attendance type and move to photo capture step
        selectAttendanceType(type) {
            // Check if location is required and valid
            if (this.locationRequired && !this.locationInGeofence) {
                this.error = 'Lokasi Anda tidak berada di area geofence yang ditentukan. Silakan pindah ke area yang sesuai.';
                return;
            }

            // If this is clock-in, start the cooldown
            if (type === 'clock-in') {
                const timestamp = Date.now();
                localStorage.setItem('clockInTimestamp', timestamp.toString());
                this.clockInTimestamp = timestamp;
                this.cooldownActive = true;
                this.cooldownRemainingSeconds = this.COOLDOWN_DURATION;
            }

            this.attendanceType = type;
            this.step = 2;
            // Start camera after moving to step 2 (only if selfie is required)
            if (this.requireSelfie) {
                this.startCamera();
            }
        },

        // Request location access with improved cross-browser support
        async requestLocation() {
            this.error = 'Mengambil lokasi...';
            
            // Check if geolocation is supported
            if (!navigator.geolocation) {
                this.error = 'Browser Anda tidak mendukung geolokasi.';
                return;
            }

            try {
                // Try to get location with different fallback methods
                let position;
                
                // First attempt with high accuracy
                try {
                    position = await new Promise((resolve, reject) => {
                        navigator.geolocation.getCurrentPosition(resolve, reject, {
                            enableHighAccuracy: true,
                            timeout: 10000,
                            maximumAge: 60000
                        });
                    });
                } catch (highAccError) {
                    // If high accuracy fails, try with less strict settings
                    try {
                        position = await new Promise((resolve, reject) => {
                            navigator.geolocation.getCurrentPosition(resolve, reject, {
                                enableHighAccuracy: false,
                                timeout: 15000,
                                maximumAge: 60000
                            });
                        });
                    } catch (lowAccError) {
                        // If all attempts fail, throw the original error
                        throw highAccError;
                    }
                }

                this.currentLocation = {
                    latitude: position.coords.latitude,
                    longitude: position.coords.longitude,
                    accuracy: position.coords.accuracy || 100 // Default accuracy if not provided
                };
                
                this.locationEnabled = true;
                this.error = '';

                // Check if location is within geofence
                if (this.geofenceCenter && this.geofenceRadius && this.currentLocation) {
                    this.locationInGeofence = this.isLocationInGeofence(
                        this.currentLocation.latitude,
                        this.currentLocation.longitude,
                        this.currentLocation.accuracy
                    );
                } else {
                    this.locationInGeofence = true; // If no geofence is set or location not available, consider it OK
                }

                // Update map with user location (only if map is enabled)
                if (this.displayWithMap) {
                    this.$nextTick(() => {
                        this.updateUserLocationOnMap();
                    });
                }
            } catch (error) {
                this.locationEnabled = false;
                this.currentLocation = null;
                this.locationInGeofence = false;
                
                // More specific error handling with better browser compatibility
                switch(error.code) {
                    case error.PERMISSION_DENIED:
                        this.error = 'Izin lokasi ditolak. Mohon izinkan akses lokasi di pengaturan browser Anda.';
                        break;
                    case error.POSITION_UNAVAILABLE:
                        this.error = 'Informasi lokasi tidak tersedia. Pastikan layanan lokasi aktif.';
                        break;
                    case error.TIMEOUT:
                        // Try one more time with extended timeout
                        try {
                            const position = await new Promise((resolve, reject) => {
                                navigator.geolocation.getCurrentPosition(resolve, reject, {
                                    enableHighAccuracy: false,
                                    timeout: 20000,
                                    maximumAge: 60000
                                });
                            });
                            
                            this.currentLocation = {
                                latitude: position.coords.latitude,
                                longitude: position.coords.longitude,
                                accuracy: position.coords.accuracy || 100
                            };
                            
                            this.locationEnabled = true;
                            this.error = '';

                            // Check if location is within geofence
                            if (this.geofenceCenter && this.geofenceRadius && this.currentLocation) {
                                this.locationInGeofence = this.isLocationInGeofence(
                                    this.currentLocation.latitude,
                                    this.currentLocation.longitude,
                                    this.currentLocation.accuracy
                                );
                            } else {
                                this.locationInGeofence = true; // If no geofence is set or location not available, consider it OK
                            }

                            // Update map with user location (only if map is enabled)
                            if (this.displayWithMap) {
                                this.$nextTick(() => {
                                    this.updateUserLocationOnMap();
                                });
                            }
                        } catch {
                            this.error = 'Waktu permintaan lokasi habis. Coba periksa koneksi internet Anda.';
                        }
                        break;
                    default:
                        // Check if error message contains specific browser-related issues
                        if (error.message && error.message.toLowerCase().includes('insecure')) {
                            this.error = 'Layanan lokasi hanya tersedia di koneksi aman (HTTPS).';
                        } else if (error.message && error.message.toLowerCase().includes('cross')) {
                            this.error = 'Akses lokasi diblokir oleh kebijakan keamanan browser.';
                        } else {
                            this.error = 'Terjadi kesalahan saat mendapatkan lokasi: ' + error.message;
                        }
                        break;
                }
            }
        },

        // Check if a location is within the geofence
        isLocationInGeofence(lat, lng, accuracy = 0) {
            if (!this.geofenceCenter) return false;

            // Debug: Log geofence center and user location
            console.log('Checking geofence:', {
                userLat: lat,
                userLng: lng,
                centerLat: this.geofenceCenter.lat,
                centerLng: this.geofenceCenter.lng,
                radius: this.geofenceRadius
            });

            const distance = this.calculateDistance(
                lat,
                lng,
                this.geofenceCenter.lat,
                this.geofenceCenter.lng
            );

            console.log('Calculated distance:', distance, 'meters');
            
            // Enhanced validation with accuracy consideration
            // Check if user is within strict radius first
            const withinStrictRadius = distance <= this.geofenceRadius;
            
            if (withinStrictRadius) {
                // If within strict radius, always allow regardless of accuracy
                this.lastLocationCheck = {
                    distance: distance,
                    accuracy: accuracy,
                    maxAllowedDistance: this.geofenceRadius,
                    withinStrictRadius: true,
                    withinAllowedRadius: true
                };
                return true;
            }
            
            // For users outside strict radius, apply more lenient accuracy handling
            // Cap accuracy at reasonable levels to prevent false negatives but be more forgiving
            const safeAccuracy = Math.min(parseFloat(accuracy) || 0, 200);
            
            // Calculate max allowed distance with more generous accuracy buffer
            // Allow up to 75% of radius as accuracy buffer for better tolerance
            const maxAllowedDistance = this.geofenceRadius + Math.min(safeAccuracy, this.geofenceRadius * 0.75);
            
            // Store detailed information for UI feedback
            this.lastLocationCheck = {
                distance: distance,
                accuracy: safeAccuracy,
                maxAllowedDistance: maxAllowedDistance,
                withinStrictRadius: withinStrictRadius,
                withinAllowedRadius: distance <= maxAllowedDistance
            };
            
            return distance <= maxAllowedDistance;
        },

        // Calculate distance between two coordinates using Haversine formula
        calculateDistance(lat1, lon1, lat2, lon2) {
            const R = 6371e3; // Earth's radius in meters
            const φ1 = lat1 * Math.PI/180;
            const φ2 = lat2 * Math.PI/180;
            const Δφ = (lat2-lat1) * Math.PI/180;
            const Δλ = (lon2-lon1) * Math.PI/180;

            const a = Math.sin(Δφ/2) * Math.sin(Δφ/2) +
                    Math.cos(φ1) * Math.cos(φ2) *
                    Math.sin(Δλ/2) * Math.sin(Δλ/2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));

            return R * c; // Distance in meters
        },

        // Initialize Leaflet map
        initializeMap() {
            if (!this.geofenceCenter || this.map || !this.displayWithMap) return;

            // Check if Leaflet is loaded
            if (typeof L === 'undefined') {
                console.warn('Leaflet is not loaded. Map will not be displayed.');
                return;
            }

            try {
                // Create map centered on geofence center
                this.map = L.map('attendance-map').setView(
                    [this.geofenceCenter.lat, this.geofenceCenter.lng],
                    17
                );

                // Add OpenStreetMap tiles
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                    maxZoom: 19
                }).addTo(this.map);

                // Add geofence center marker (red)
                this.geofenceMarker = L.marker([this.geofenceCenter.lat, this.geofenceCenter.lng], {
                    icon: L.icon({
                        iconUrl: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjQiIGhlaWdodD0iMjQiIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0iI0VGNDQ0NCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cGF0aCBkPSJNMTIgMkM4LjEzIDIgNSA1LjEzIDUgOWMwIDUuMjUgNyAxMyA3IDEzczctNy43NSA3LTEzYzAtMy44Ny0zLjEzLTctNy03em0wIDkuNWMtMS4zOCAwLTIuNS0xLjEyLTIuNS0yLjVzMS4xMi0yLjUgMi41LTIuNSAyLjUgMS4xMiAyLjUgMi41LTEuMTIgMi41LTIuNSAyLjV6Ii8+PC9zdmc+',
                        iconSize: [32, 32],
                        iconAnchor: [16, 32],
                        popupAnchor: [0, -32]
                    })
                }).addTo(this.map);

                // Build popup content based on display_with_coordinates setting
                let geofencePopupContent = '<b>Pusat Geofence</b>';

                if (this.displayWithCoordinates) {
                    geofencePopupContent += `<br>Lat: ${this.geofenceCenter.lat.toFixed(6)}<br>Lng: ${this.geofenceCenter.lng.toFixed(6)}`;
                }

                this.geofenceMarker.bindPopup(geofencePopupContent);

                // Add geofence circle (red transparent)
                this.geofenceCircle = L.circle([this.geofenceCenter.lat, this.geofenceCenter.lng], {
                    color: '#EF4444',
                    fillColor: '#EF4444',
                    fillOpacity: 0.2,
                    radius: this.geofenceRadius
                }).addTo(this.map);

                console.log('Map initialized successfully');
            } catch (error) {
                console.error('Failed to initialize map:', error);
            }
        },

        // Update user location marker on map
        updateUserLocationOnMap() {
            if (!this.map || !this.currentLocation || !this.displayWithMap) return;

            // Check if Leaflet is loaded
            if (typeof L === 'undefined') {
                console.warn('Leaflet is not loaded. Cannot update user location on map.');
                return;
            }

            try {
                const userLat = this.currentLocation.latitude;
                const userLng = this.currentLocation.longitude;

                // Remove existing user marker if any
                if (this.userMarker) {
                    this.map.removeLayer(this.userMarker);
                }

                // Add user location marker (blue)
                this.userMarker = L.marker([userLat, userLng], {
                    icon: L.icon({
                        iconUrl: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjQiIGhlaWdodD0iMjQiIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0iIzM4NzRGRiIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cGF0aCBkPSJNMTIgMkM4LjEzIDIgNSA1LjEzIDUgOWMwIDUuMjUgNyAxMyA3IDEzczctNy43NSA3LTEzYzAtMy44Ny0zLjEzLTctNy03em0wIDkuNWMtMS4zOCAwLTIuNS0xLjEyLTIuNS0yLjVzMS4xMi0yLjUgMi41LTIuNSAyLjUgMS4xMiAyLjUgMi41LTEuMTIgMi41LTIuNSAyLjV6Ii8+PC9zdmc+',
                        iconSize: [32, 32],
                        iconAnchor: [16, 32],
                        popupAnchor: [0, -32]
                    })
                }).addTo(this.map);

                const statusText = this.locationInGeofence ? 'Di dalam area' : 'Di luar area';
                const statusColor = this.locationInGeofence ? 'green' : 'red';

                // Build popup content based on display_with_coordinates setting
                let popupContent = `<b style="color: ${statusColor}">Lokasi Anda</b><br>${statusText}`;

                if (this.displayWithCoordinates) {
                    popupContent += `<br>Lat: ${userLat.toFixed(6)}<br>Lng: ${userLng.toFixed(6)}<br>Akurasi: ${Math.round(this.currentLocation.accuracy)}m`;
                }

                this.userMarker.bindPopup(popupContent);

                // Pan to show both markers
                const bounds = L.latLngBounds([
                    [this.geofenceCenter.lat, this.geofenceCenter.lng],
                    [userLat, userLng]
                ]);
                this.map.fitBounds(bounds, { padding: [50, 50] });

                console.log('User location updated on map');
            } catch (error) {
                console.error('Failed to update user location on map:', error);
            }
        },

        async startCamera() {
            try {
                // Check if we have permission to access camera
                const permissionStatus = await navigator.permissions.query({name: 'camera'});
                
                if (permissionStatus.state === 'denied') {
                    this.error = 'Izin kamera ditolak. Mohon izinkan akses kamera di pengaturan browser Anda.';
                    return;
                }
                
                this.stream = await navigator.mediaDevices.getUserMedia({ 
                    video: { 
                        facingMode: 'user' // Front camera for selfie
                    } 
                });
                this.$refs.video.srcObject = this.stream;
                this.$refs.video.style.display = 'block';
                this.$refs.video.setAttribute('playsinline', true);
                this.error = ''; // Clear any previous error
            } catch (error) {
                if (error.name === 'NotAllowedError') {
                    this.error = 'Akses kamera ditolak. Mohon izinkan akses kamera untuk mengambil foto selfie.';
                } else if (error.name === 'NotFoundError') {
                    this.error = 'Kamera tidak ditemukan. Pastikan perangkat Anda memiliki kamera.';
                } else if (error.name === 'NotReadableError') {
                    this.error = 'Kamera sedang digunakan oleh aplikasi lain. Tutup aplikasi lain yang menggunakan kamera.';
                } else {
                    this.error = 'Tidak dapat mengakses kamera: ' + error.message || 'Terjadi kesalahan tidak diketahui.';
                }
                console.error('Camera error:', error);
            }
        },

        captureImage() {
            const video = this.$refs.video;
            const canvas = this.$refs.canvas;
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            canvas.getContext('2d').drawImage(video, 0, 0);
            this.capturedImage = canvas.toDataURL('image/png');
            video.style.display = 'none';
            if (this.stream) {
                this.stream.getTracks().forEach(track => track.stop());
            }
        },

        retakeImage() {
            this.capturedImage = null;
            this.startCamera();
        },

        resetCamera() {
            this.capturedImage = null;
            if (this.stream) {
                this.stream.getTracks().forEach(track => track.stop());
                this.stream = null;
            }
        },

        async submitAttendance() {
            this.error = '';
            this.submitting = true;

            // Determine the correct route based on attendance type
            let route;
            if (this.attendanceType === 'clock-in') {
                route = '{{ route("employee.attendance.clock-in") }}';
            } else {
                route = '{{ route("employee.attendance.clock-out") }}';
            }

            try {
                const response = await fetch(route, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ 
                        selfie: this.capturedImage,
                        location: this.currentLocation  // Include location data
                    })
                });

                // Check if response is ok before parsing
                if (!response.ok) {
                    this.submitting = false;

                    // Try to parse as JSON first
                    let errorMessage = 'Terjadi kesalahan pada server. Silakan coba lagi.';
                    try {
                        const errorData = await response.json();
                        errorMessage = errorData.message || errorMessage;
                    } catch (e) {
                        // If not JSON, check status code
                        if (response.status === 419) {
                            errorMessage = 'Sesi Anda telah berakhir. Silakan refresh halaman dan login kembali.';
                        } else if (response.status === 500) {
                            errorMessage = 'Terjadi kesalahan server. Silakan hubungi administrator.';
                        } else if (response.status === 422) {
                            errorMessage = 'Data yang dikirim tidak valid. Silakan ambil foto ulang.';
                        } else {
                            errorMessage = `Error ${response.status}: ${response.statusText}`;
                        }
                    }

                    this.error = errorMessage;
                    this.step = 4;
                    return;
                }

                // Parse JSON response
                const data = await response.json();

                this.submitting = false;

                if (data.success) {
                    this.message = data.message;
                    this.step = 3; // Success step

                    // Update the status flags based on what was submitted
                    if (this.attendanceType === 'clock-in') {
                        this.hasClockIn = true;
                    } else if (this.attendanceType === 'clock-out') {
                        this.hasClockOut = true;
                    }

                    // Auto-redirect to attendance report after 2 seconds
                    setTimeout(() => {
                        window.location.href = '{{ route("employee.attendance.report") }}';
                    }, 2000);
                } else {
                    this.error = data.message || 'Gagal mengirim absensi. Silakan coba lagi.';
                    // Go to error step
                    this.step = 4;
                }
            } catch (error) {
                console.error('Attendance submission error:', error);
                this.submitting = false;
                this.error = 'Terjadi kesalahan jaringan. Periksa koneksi internet Anda dan coba lagi.';
                // Go to error step
                this.step = 4;
            }
        },

        updateClock() {
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            // For attendance display, show only hours and minutes (HH:MM format)
            this.currentTime = `${hours}:${minutes}`;
        },

        async fetchScheduleStatus() {
            try {
                const response = await fetch('/api/schedules/current-status');
                const data = await response.json();
                this.currentDate = data.current_date;
                this.formattedDateTime = data.formatted_datetime;
                this.badge = data.badge;
            } catch (error) {
                console.error('Failed to fetch schedule status:', error);
            }
        }
    }
}
</script>
@endpush
@endsection