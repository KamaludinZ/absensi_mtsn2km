@extends('layouts.app')

@section('title', 'Absensi Pegawai')
@section('page-title', 'Absensi')

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

                <!-- Attendance Type Selection -->
                <div class="space-y-4">
                    <button 
                        @click="selectAttendanceType('clock-in')" 
                        :disabled="isDisabled('clock-in')"
                        :class="isDisabled('clock-in') ? 'bg-gray-300 cursor-not-allowed' : 'bg-green-600 hover:bg-green-700'"
                        class="w-full py-4 px-6 rounded-lg text-white font-bold text-lg transition duration-200 disabled:opacity-50">
                        <div class="flex items-center justify-center">
                            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Absen Masuk
                        </div>
                    </button>
                    
                    <button 
                        @click="selectAttendanceType('clock-out')" 
                        :disabled="isDisabled('clock-out')"
                        :class="isDisabled('clock-out') ? 'bg-gray-300 cursor-not-allowed' : 'bg-blue-600 hover:bg-blue-700'"
                        class="w-full py-4 px-6 rounded-lg text-white font-bold text-lg transition duration-200 disabled:opacity-50">
                        <div class="flex items-center justify-center">
                            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Absen Pulang
                        </div>
                    </button>
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

                <div class="mb-6">
                    <video x-ref="video" autoplay playsinline class="w-full rounded-lg border border-gray-300" style="display:none;"></video>
                    <canvas x-ref="canvas" class="w-full rounded-lg border border-gray-300" style="display:none;"></canvas>
                    <img x-show="capturedImage" :src="capturedImage" class="w-full rounded-lg border border-gray-300">
                </div>

                <!-- Loading State --> 
                <div x-show="submitting" class="text-center mb-4">
                    <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                    <p class="text-gray-600 mt-2">Mengirim absensi...</p>
                </div>

                <div class="flex space-x-4" x-show="!submitting">
                    <button @click="captureImage" x-show="!capturedImage" class="flex-1 bg-blue-600 text-white py-3 rounded-lg hover:bg-blue-700 transition duration-200 font-semibold">
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
            this.attendanceType = type;
            this.step = 2;
            // Start camera after moving to step 2
            this.startCamera();
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
                    body: JSON.stringify({ selfie: this.capturedImage })
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
        },

        init() {
            // Initialize clock and schedule
            this.updateClock();
            this.fetchScheduleStatus();
            setInterval(() => this.updateClock(), 1000);
            setInterval(() => this.fetchScheduleStatus(), 60000);
        }
    }
}
</script>
@endpush
@endsection