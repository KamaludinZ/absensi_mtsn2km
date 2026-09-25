@extends('layouts.guest')

@section('title', 'Absensi Siswa - MTsN 2 Kota Malang')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-blue-500 to-blue-700 py-8">
    <div class="max-w-2xl mx-auto px-4">
        <div class="text-center text-white mb-8">
            <h1 class="text-3xl font-bold mb-2">Absensi Siswa</h1>
            <p>MTsN 2 Kota Malang</p>
            <a href="{{ route('welcome') }}" class="text-sm mt-2 inline-block hover:underline">Kembali ke Halaman Utama</a>
        </div>

        <div x-data="attendanceApp()" class="bg-white rounded-lg shadow-xl p-8">
    <!-- Toast notifications -->
    <div x-show="showToast" 
         :class="toastType === 'success' ? 'bg-green-500' : 'bg-red-500'" 
         class="fixed top-4 right-4 z-50 px-6 py-4 rounded-lg text-white shadow-lg transition-opacity duration-300"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform translate-x-full"
         x-transition:enter-end="opacity-100 transform translate-x-0"
         x-transition:leave="transition ease-in duration-300"
         x-transition:leave-start="opacity-100 transform translate-x-0"
         x-transition:leave-end="opacity-0 transform translate-x-full">
        <p x-text="toastMessage"></p>
    </div>
            <!-- Step 1: Enter NISN or tap the RFID student card -->
            <div x-show="step === 1">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">Masukkan NISN atau Tempel Kartu</h2>
                
                <!-- Clock & Date at the top -->
                <div class="mb-6 bg-blue-50 border border-blue-200 rounded-lg p-4 text-center">
                    <p class="text-2xl font-bold text-gray-800" x-text="currentTime"></p>
                    <p class="text-sm text-gray-600 mt-1" x-text="currentDate"></p>
                    @if(!empty($kioskWindow['opens_at']))
                        <p class="text-xs text-gray-500 mt-1">Absensi dibuka {{ $kioskWindow['opens_at']->format('H:i') }} – {{ $kioskWindow['closes_at']->format('H:i') }}</p>
                    @endif
                </div>
                
                <!-- Location Information -->
                <div class="mb-4 bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">Lokasi Anda</h3>
                    <div x-show="locationEnabled" class="flex items-center">
                        <svg class="w-5 h-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 01-6 0 3 3 0 016 0z"/>
                        </svg>
                        <div>
                            <p class="text-sm text-gray-700">Koordinat: 
                                <span x-text="currentLocation ? currentLocation.latitude + ', ' + currentLocation.longitude : 'Mengambil lokasi...'"></span>
                            </p>
                            <p class="text-sm text-gray-600" x-text="currentLocation ? 'Akurasi: ' + Math.round(currentLocation.accuracy) + ' meter' : 'Akurasi: -'"></p>
                            <template x-if="lastLocationCheck">
                                <div>
                                    <p class="text-sm" x-bind:class="locationInGeofence ? 'text-green-600' : 'text-red-600'" 
                                       x-text="locationInGeofence ? 
                                            ('Lokasi Anda berada di dalam area geofence (' + Math.round(lastLocationCheck.distance) + 'm dari pusat)') : 
                                            ('Peringatan: Lokasi Anda di luar area geofence (' + Math.round(lastLocationCheck.distance) + 'm dari pusat)')">
                                    </p>
                                    <p class="text-xs text-gray-500" x-show="lastLocationCheck && !lastLocationCheck.withinStrictRadius && lastLocationCheck.withinAllowedRadius">
                                        * Diperbolehkan karena toleransi akurasi GPS (radius: <span x-text="geofenceRadius"></span>m + akurasi: <span x-text="Math.round(currentLocation?.accuracy || 0)"></span>m)
                                    </p>
                                </div>
                            </template>
                            <template x-if="!lastLocationCheck">
                                <p class="text-sm" x-bind:class="locationInGeofence ? 'text-green-600' : 'text-red-600'" 
                                   x-text="locationInGeofence ? 'Lokasi Anda berada di dalam area geofence' : 'Peringatan: Lokasi Anda di luar area geofence'"></p>
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
                
                <form @submit.prevent="confirmStudent">
                    <!-- NISN / RFID input. USB RFID readers act like a keyboard: they
                         "type" the card number followed by Enter into the focused field. -->
                    <div class="mb-6">
                        <label for="identifier" class="block text-sm font-medium text-gray-700 mb-2">NISN atau Kartu Pelajar</label>
                        <input id="identifier" x-ref="identifierInput" x-model="identifier" type="text" inputmode="numeric"
                            autocomplete="off" autofocus maxlength="50" required
                            placeholder="NISN / kartu"
                            class="w-full px-4 py-3 text-2xl tracking-widest text-center border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <p class="text-sm text-gray-500 mt-2">Ketik NISN lalu tekan Enter, atau tempelkan kartu pelajar ke alat pembaca kartu.</p>
                    </div>

                    <button type="submit" :disabled="!identifier.trim() || submitting || (locationRequired && !locationInGeofence)"
                        class="w-full bg-blue-600 text-white py-3 rounded-lg hover:bg-blue-700 transition duration-200 font-semibold disabled:bg-gray-400 disabled:cursor-not-allowed">
                        Lanjutkan
                    </button>
                </form>
            </div>

            <!-- Step 2: Confirm Student Data -->
            <div x-show="step === 2">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">Konfirmasi Data Anda</h2>
                
                <!-- Clock & Date -->
                <div class="mb-4 bg-blue-50 border border-blue-200 rounded-lg p-4 text-center">
                    <p class="text-2xl font-bold text-gray-800" x-text="currentTime"></p>
                    <p class="text-sm text-gray-600 mt-1" x-text="currentDate"></p>
                    <div class="mt-2 text-sm text-gray-600" x-show="studentSchedule">
                        <span x-text="'Jam Masuk: ' + studentSchedule.start"></span> |
                        <span x-text="'Jam Pulang: ' + studentSchedule.end"></span>
                    </div>
                </div>
                
                <!-- Location Information -->
                <div class="mb-4 bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <h3 class="text-lg font-semibold text-gray-800 mb-1">Lokasi Anda</h3>
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 01-6 0 3 3 0 016 0z"/>
                        </svg>
                        <div>
                            <p class="text-sm text-gray-700">Koordinat: 
                                <span x-text="currentLocation ? currentLocation.latitude + ', ' + currentLocation.longitude : 'Mengambil lokasi...'"></span>
                            </p>
                            <p class="text-sm text-gray-600" x-text="currentLocation ? 'Akurasi: ' + Math.round(currentLocation.accuracy) + ' meter' : 'Akurasi: -'"></p>
                            <template x-if="lastLocationCheck">
                                <div>
                                    <p class="text-sm" x-bind:class="locationInGeofence ? 'text-green-600' : 'text-red-600'" 
                                       x-text="locationInGeofence ? 
                                            ('Sesuai area geofence (' + Math.round(lastLocationCheck.distance) + 'm dari pusat)') : 
                                            ('Peringatan: Di luar area geofence (' + Math.round(lastLocationCheck.distance) + 'm dari pusat)')">
                                    </p>
                                    <p class="text-xs text-gray-500" x-show="lastLocationCheck && !lastLocationCheck.withinStrictRadius && lastLocationCheck.withinAllowedRadius">
                                        * Diperbolehkan karena toleransi akurasi GPS (radius: <span x-text="geofenceRadius"></span>m + akurasi: <span x-text="Math.round(currentLocation?.accuracy || 0)"></span>m)
                                    </p>
                                </div>
                            </template>
                            <template x-if="!lastLocationCheck">
                                <p class="text-sm" x-bind:class="locationInGeofence ? 'text-green-600' : 'text-red-600'" 
                                   x-text="locationInGeofence ? 'Sesuai area geofence' : 'Peringatan: Di luar area geofence'"></p>
                            </template>
                        </div>
                    </div>
                </div>
                
                <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                    <p class="text-lg"><strong>Nama:</strong> <span x-text="student.full_name"></span></p>
                    <p class="text-lg"><strong>Rombel:</strong> <span x-text="student.rombel"></span></p>
                    <p class="text-lg"><strong>Kelas:</strong> <span x-text="student.grade_info"></span></p>
                </div>
                <div class="flex space-x-4">
                    <button @click="prepareClockIn" 
                        :disabled="(locationRequired && !locationInGeofence)"
                        :class="(locationRequired && !locationInGeofence) ? 'bg-gray-400 cursor-not-allowed' : 'bg-green-600 hover:bg-green-700'"
                        class="flex-1 text-white py-3 rounded-lg transition duration-200 font-semibold">
                        Absen Datang
                    </button>
                    <button @click="prepareClockOut" 
                        :disabled="(locationRequired && !locationInGeofence)"
                        :class="(locationRequired && !locationInGeofence) ? 'bg-gray-400 cursor-not-allowed' : 'bg-yellow-500 hover:bg-yellow-600'"
                        class="flex-1 text-white py-3 rounded-lg transition duration-200 font-semibold">
                        Absen Pulang
                    </button>
                    <button @click="reset" class="flex-1 bg-gray-500 text-white py-3 rounded-lg hover:bg-gray-600 transition duration-200 font-semibold">
                        Batal
                    </button>
                </div>
                <div x-show="student.has_clocked_in && student.has_clocked_out" class="text-center">
                    <p class="text-lg text-green-600 font-semibold">Sudah Absen Masuk dan Pulang</p>
                    <button @click="reset" class="mt-4 bg-gray-500 text-white py-3 rounded-lg hover:bg-gray-600 transition duration-200 font-semibold">
                        Kembali
                    </button>
                </div>
            </div>

            <!-- Step 3: Camera Selfie -->
            <div x-show="step === 3">
                <h2 class="text-2xl font-bold text-gray-800 mb-6" x-text="clockInAction ? 'Ambil Foto Selfie - Absen Datang' : 'Ambil Foto Selfie - Absen Pulang'"></h2>

                <!-- Clock & Date -->
                <div class="mb-4 bg-blue-50 border border-blue-200 rounded-lg p-4 text-center">
                    <p class="text-2xl font-bold text-gray-800" x-text="currentTime"></p>
                    <p class="text-sm text-gray-600 mt-1" x-text="currentDate"></p>
                    <div class="mt-2 text-sm text-gray-600" x-show="studentSchedule">
                        <span x-text="'Jam Masuk: ' + studentSchedule.start"></span> |
                        <span x-text="'Jam Pulang: ' + studentSchedule.end"></span>
                    </div>
                </div>

                <!-- Location Information -->
                <div class="mb-4 bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <h3 class="text-lg font-semibold text-gray-800 mb-1">Lokasi Absensi</h3>
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 01-6 0 3 3 0 016 0z"/>
                        </svg>
                        <div>
                            <p class="text-sm text-gray-700">Koordinat: 
                                <span x-text="currentLocation ? currentLocation.latitude + ', ' + currentLocation.longitude : 'Mengambil lokasi...'"></span>
                            </p>
                            <p class="text-sm text-gray-600" x-text="currentLocation ? 'Akurasi: ' + Math.round(currentLocation.accuracy) + ' meter' : 'Akurasi: -'"></p>
                            <p class="text-sm" x-bind:class="locationInGeofence ? 'text-green-600' : 'text-red-600'" 
                               x-text="locationInGeofence ? 'Sesuai area geofence' : 'Peringatan: Di luar area geofence'"></p>
                        </div>
                    </div>
                </div>

                <!-- Camera permission request area -->
                <div x-show="!stream && !error" class="text-center mb-6">
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
                        <svg class="mx-auto h-12 w-12 text-blue-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                        <p class="text-gray-700 mb-4">Izinkan akses kamera untuk mengambil foto selfie</p>
                        <button @click="startCamera" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition duration-200 font-semibold">
                            Izinkan Kamera
                        </button>
                    </div>
                </div>

                <div class="mb-6">
                    <video x-ref="video" autoplay playsinline class="w-full rounded-lg border border-gray-300" style="display:none;"></video>
                    <canvas x-ref="canvas" class="w-full rounded-lg border border-gray-300" style="display:none;"></canvas>
                    <img x-show="capturedImage" :src="capturedImage" class="w-full rounded-lg border border-gray-300">
                </div>
                
                <!-- Loading State -->
                <div x-show="submitting" class="text-center mb-4">
                    <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                    <p class="text-gray-600 mt-2">Mengirim absensi beserta lokasi...</p>
                </div>

                <div class="flex space-x-4" x-show="!submitting">
                    <button @click="captureImage" x-show="!capturedImage && stream" class="flex-1 bg-blue-600 text-white py-3 rounded-lg hover:bg-blue-700 transition duration-200 font-semibold">
                        Ambil Foto
                    </button>
                    
                    <div x-show="capturedImage" class="w-full space-y-4">
                        <button @click="submitAttendance" class="w-full bg-green-600 text-white py-3 rounded-lg hover:bg-green-700 transition duration-200 font-semibold">
                            Kirim Absensi
                        </button>
                        <button @click="retakeImage" class="w-full bg-yellow-600 text-white py-3 rounded-lg hover:bg-yellow-700 transition duration-200 font-semibold">
                            Foto Ulang
                        </button>
                    </div>
                </div>
            </div>

            <!-- Step 4: Success - Will auto-redirect -->
            <div x-show="step === 4" class="text-center">
                <svg class="mx-auto h-16 w-16 text-green-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <h2 class="text-2xl font-bold text-gray-800 mb-4" x-text="clockInAction ? 'Absen Datang Berhasil!' : 'Absen Pulang Berhasil!'"></h2>
                <p class="text-lg text-gray-600 mb-6" x-text="message"></p>
                <p class="text-sm text-gray-500">Kembali ke halaman absensi untuk siswa berikutnya...</p>
            </div>

            <!-- Error State with Retry -->
            <div x-show="step === 5" class="text-center">
                <svg class="mx-auto h-16 w-16 text-red-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <h2 class="text-2xl font-bold text-gray-800 mb-4" x-text="clockInAction ? 'Absen Datang Gagal' : 'Absen Pulang Gagal'"></h2>
                <p class="text-lg text-red-600 mb-6" x-text="error"></p>
                <button @click="step = 3; error = ''" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition duration-200 font-semibold">
                    Ulangi Foto
                </button>
            </div>

            <div x-show="error" class="mt-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                <span x-text="error"></span>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function attendanceApp() {
    return {
        step: 1,
        identifier: '',
        student: {},
        studentSchedule: {},
        clockInAction: true, // true for clock-in, false for clock-out
        capturedImage: null,
        message: '',
        error: '',
        stream: null,
        currentTime: '',
        currentDate: '',
        formattedDateTime: '',
        badge: 'Jam Datang',
        submitting: false,
        showToast: false,
        toastMessage: '',
        toastType: 'success', // 'success' or 'error'
        locationEnabled: false,
        locationRequired: false, // Default to false for public access
        currentLocation: null,
        locationInGeofence: false,
        lastLocationCheck: null, // Detailed location check information
        geofenceCenter: null,
        geofenceRadius: null,

        async init() {
            // Load location settings first
            await this.fetchLocationSettings();
            
            // Initialize clock and schedule
            this.updateClock();
            this.fetchScheduleStatus();
            setInterval(() => this.updateClock(), 1000);
            setInterval(() => this.fetchScheduleStatus(), 60000);

            this.$watch('step', value => {
                if (value === 3) {
                    this.startCamera();
                }
            });

            this.$nextTick(() => this.$refs.identifierInput.focus());
        },

        // Fetch location settings from the server using a public endpoint with improved error handling
        async fetchLocationSettings() {
            try {
                // Use AbortController for timeout handling
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 10000); // 10 second timeout

                // Use Promise.all to fetch all settings simultaneously for better performance
                const [geofenceCenterLat, geofenceCenterLng, geofenceRadius, locationRequired] = await Promise.all([
                    fetch('/api/location-settings/geofence-center-lat', { signal: controller.signal }).catch(() => null),
                    fetch('/api/location-settings/geofence-center-lng', { signal: controller.signal }).catch(() => null),
                    fetch('/api/location-settings/geofence-radius', { signal: controller.signal }).catch(() => null),
                    fetch('/api/location-settings/location-required', { signal: controller.signal }).catch(() => null)
                ]);

                clearTimeout(timeoutId);

                // Process geofence center coordinates
                // Handle each request individually to be more robust
                let latValue = null;
                let lngValue = null;
                
                if (geofenceCenterLat && geofenceCenterLat.ok) {
                    try {
                        const latData = await geofenceCenterLat.json();
                        latValue = latData.value ? parseFloat(latData.value) : null;
                    } catch (e) {
                        console.error('Failed to parse geofence center latitude:', e);
                    }
                }
                
                if (geofenceCenterLng && geofenceCenterLng.ok) {
                    try {
                        const lngData = await geofenceCenterLng.json();
                        lngValue = lngData.value ? parseFloat(lngData.value) : null;
                    } catch (e) {
                        console.error('Failed to parse geofence center longitude:', e);
                    }
                }
                
                // Only set geofence center if both values are valid
                if (latValue !== null && lngValue !== null) {
                    this.geofenceCenter = {
                        lat: latValue,
                        lng: lngValue
                    };
                    console.log('Geofence center set:', this.geofenceCenter);
                } else {
                    // Use default geofence if API values are invalid
                    console.warn('Using default geofence center due to invalid values');
                    this.geofenceCenter = {
                        lat: -7.99478, // Default to current school location
                        lng: 112.67383
                    };
                }

                // Process geofence radius
                if (geofenceRadius && geofenceRadius.ok) {
                    try {
                        const radiusData = await geofenceRadius.json();
                        const radiusValue = parseFloat(radiusData.value);
                        this.geofenceRadius = isNaN(radiusValue) ? 100 : radiusValue; // Default radius of 100m if invalid
                        console.log('Geofence radius set:', this.geofenceRadius);
                    } catch (e) {
                        console.error('Failed to parse geofence radius, using default:', e);
                        this.geofenceRadius = 100; // Default radius
                    }
                } else {
                    console.warn('Using default geofence radius due to API failure');
                    this.geofenceRadius = 100; // Default radius
                }

                // Process location required setting
                if (locationRequired && locationRequired.ok) {
                    try {
                        const requiredData = await locationRequired.json();
                        this.locationRequired = requiredData.value === 'true' || requiredData.value === true;
                        console.log('Location required set:', this.locationRequired);
                    } catch (e) {
                        console.error('Failed to parse location required setting, using default:', e);
                        this.locationRequired = false; // Default to not required
                    }
                } else {
                    console.warn('Using default location required setting due to API failure');
                    this.locationRequired = false; // Default to not required
                }

                // Request location if required
                if (this.locationRequired) {
                    this.requestLocation();
                }
            } catch (error) {
                console.error('Failed to fetch location settings:', error);
                
                // Handle different types of errors
                if (error.name === 'AbortError') {
                    console.warn('Location settings request timed out');
                } else if (error.name === 'TypeError' && error.message.includes('fetch')) {
                    console.warn('Network error while fetching location settings');
                }
                
                // Set default values and continue
                this.geofenceCenter = {
                    lat: -7.99478, // Default to current school location
                    lng: 112.67383
                };
                this.geofenceRadius = 100;
                this.locationRequired = false; // Default to not required
                
                // Request location if required
                if (this.locationRequired) {
                    this.requestLocation();
                }
            }
        },

        async confirmStudent() {
            if (!this.identifier.trim()) {
                this.error = 'Silakan masukkan NISN atau tempelkan kartu terlebih dahulu.';
                return;
            }

            // Check location requirements
            if (this.locationRequired && !this.locationInGeofence) {
                this.error = 'Lokasi Anda tidak berada di area geofence yang ditentukan. Silakan pindah ke area yang sesuai.';
                return;
            }

            this.error = '';
            this.submitting = true;

            try {
                const response = await fetch('{{ route("student.attendance.get-student-info") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ 
                        identifier: this.identifier.trim()
                    })
                });

                const data = await response.json().catch(() => ({}));

                if (!response.ok && !data.message) {
                    throw new Error(`Gagal memuat info siswa: ${response.status} ${response.statusText}`);
                }

                if (data.success) {
                    this.student = data.student;
                    this.step = 2;
                } else {
                    this.error = data.message || 'Gagal memuat informasi siswa.';
                    this.identifier = '';
                    this.$nextTick(() => this.$refs.identifierInput.focus());
                }
            } catch (error) {
                console.error('Error confirming student:', error);
                this.error = error.message || 'Terjadi kesalahan. Silakan coba lagi.';
            } finally {
                this.submitting = false;
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
                if (this.geofenceCenter && this.geofenceRadius) {
                    this.locationInGeofence = this.isLocationInGeofence(
                        this.currentLocation.latitude, 
                        this.currentLocation.longitude,
                        this.currentLocation.accuracy
                    );
                } else {
                    this.locationInGeofence = true; // If no geofence is set, consider it OK
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
                            if (this.geofenceCenter && this.geofenceRadius) {
                                this.locationInGeofence = this.isLocationInGeofence(
                                    this.currentLocation.latitude, 
                                    this.currentLocation.longitude,
                                    this.currentLocation.accuracy
                                );
                            } else {
                                this.locationInGeofence = true;
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
            
            const distance = this.calculateDistance(
                lat, 
                lng, 
                this.geofenceCenter.lat, 
                this.geofenceCenter.lng
            );
            
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
            if (!this.stream) {
                this.error = 'Kamera tidak aktif. Mohon izinkan akses kamera terlebih dahulu.';
                return;
            }
            
            const video = this.$refs.video;
            const canvas = this.$refs.canvas;
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            canvas.getContext('2d').drawImage(video, 0, 0);
            this.capturedImage = canvas.toDataURL('image/png');
            video.style.display = 'none';
            if (this.stream) {
                this.stream.getTracks().forEach(track => track.stop());
                this.stream = null; // Clear the stream reference
            }
        },

        retakeImage() {
            this.capturedImage = null;
            // Stop any existing stream before starting again
            if (this.stream) {
                this.stream.getTracks().forEach(track => track.stop());
                this.stream = null;
            }
            // Show the permission request again instead of immediately starting camera
            // The user will click the "Izinkan Kamera" button in the UI
        },

        reset() {
            this.step = 1;
            this.identifier = '';
            this.student = {};
            this.capturedImage = null;
            this.message = '';
            this.error = '';
            if (this.stream) {
                this.stream.getTracks().forEach(track => track.stop());
                this.stream = null;
            }
            this.$nextTick(() => this.$refs.identifierInput.focus());
        },

        updateClock() {
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            // For student attendance, format time as HH:MM only (no seconds)
            this.currentTime = `${hours}:${minutes}`;
            
            // Format date in Indonesian style (similar to employee attendance)
            const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
            const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            
            const dayName = days[now.getDay()];
            const day = now.getDate();
            const monthName = months[now.getMonth()];
            const year = now.getFullYear();
            
            this.currentDate = `${dayName}, ${day} ${monthName} ${year}`;
        },

        async fetchScheduleStatus() {
            try {
                const response = await fetch('/api/schedules/current-status');
                
                if (!response.ok) {
                    throw new Error(`Gagal memuat status jadwal: ${response.status} ${response.statusText}`);
                }
                
                const data = await response.json();
                // We no longer need to set these from API since we're formatting them locally
                this.badge = data.badge;
                this.studentSchedule = data.student_schedule; // Add student schedule data
            } catch (error) {
                console.error('Failed to fetch schedule status:', error);
                // Just log the error and continue with local time
            }
        },

        prepareClockIn() {
            this.clockInAction = true; // Set flag for clock-in
            this.step = 3;
        },

        prepareClockOut() {
            this.clockInAction = false; // Set flag for clock-out
            this.step = 3;
        },

        async submitAttendance() {
            this.error = '';
            this.submitting = true;
            const route = this.clockInAction ? '{{ route("student.attendance.clock-in") }}' : '{{ route("student.attendance.clock-out") }}';

            try {
                const response = await fetch(route, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        identifier: this.identifier.trim(),
                        selfie: this.capturedImage,
                        location: this.currentLocation
                    })
                });

                const data = await response.json().catch(() => ({}));

                if (!response.ok && !data.message) {
                    throw new Error(`Gagal mengirim absensi: ${response.status} ${response.statusText}`);
                }

                this.submitting = false;

                if (data.success) {
                    this.message = data.message;
                    this.showToast = true;
                    this.toastMessage = data.message;
                    this.toastType = 'success';
                    this.step = 4;

                    // Go back to an empty NISN/card field for the next student
                    setTimeout(() => {
                        this.reset();
                    }, 2000);
                    
                    // Hide toast after 3 seconds
                    setTimeout(() => {
                        this.showToast = false;
                    }, 3000);
                } else {
                    this.error = data.message || 'Gagal mengirim absensi. Silakan coba lagi.';
                    this.showToast = true;
                    this.toastMessage = data.message || 'Gagal mengirim absensi. Silakan coba lagi.';
                    this.toastType = 'error';
                    // Stay on step 3 but allow retaking the photo
                    this.step = 3;
                    
                    // Hide toast after 3 seconds
                    setTimeout(() => {
                        this.showToast = false;
                    }, 3000);
                }
            } catch (error) {
                this.submitting = false;
                this.error = error.message || 'Terjadi kesalahan jaringan. Silakan coba lagi.';
                this.showToast = true;
                this.toastMessage = error.message || 'Terjadi kesalahan jaringan. Silakan coba lagi.';
                this.toastType = 'error';
                // Stay on step 3 but allow retaking the photo
                this.step = 3;
                
                // Hide toast after 3 seconds
                setTimeout(() => {
                    this.showToast = false;
                }, 3000);
            }
        }
    }
}
</script>
@endpush
@endsection