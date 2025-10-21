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
            <!-- Main Section: Select Grade, Rombel, and Student with Current Time Display -->
            <div x-show="step === 1">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">Pilih Data Siswa</h2>
                
                <!-- Clock & Date at the top -->
                <div class="mb-6 bg-blue-50 border border-blue-200 rounded-lg p-4 text-center">
                    <p class="text-2xl font-bold text-gray-800" x-text="currentTime"></p>
                    <p class="text-sm text-gray-600 mt-1" x-text="currentDate"></p>
                </div>
                
                <form @submit.prevent="confirmStudent">
                    <!-- Grade Selection -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tingkat Kelas</label>
                        <div x-show="loadingGrades" class="flex items-center text-gray-600 mb-2">
                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>Memuat data kelas...</span>
                        </div>
                        <select x-model="selectedGrade" @change="loadRombels" required
                            class="w-full px-4 py-3 text-lg border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                            :disabled="loadingGrades">
                            <option value="">-- Pilih Tingkat Kelas --</option>
                            <template x-for="grade in grades" :key="grade.id">
                                <option :value="grade.id" x-text="grade.name + ' - ' + grade.description"></option>
                            </template>
                        </select>
                    </div>

                    <!-- Rombel Selection -->
                    <div class="mb-4" x-show="rombels.length > 0">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Rombel</label>
                        <div x-show="loadingRombels" class="flex items-center text-gray-600 mb-2">
                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>Memuat data rombel...</span>
                        </div>
                        <select x-model="selectedRombel" @change="loadStudents" required
                            class="w-full px-4 py-3 text-lg border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                            :disabled="loadingRombels">
                            <option value="">-- Pilih Rombel --</option>
                            <template x-for="rombel in rombels" :key="rombel.id">
                                <option :value="rombel.id" x-text="rombel.name"></option>
                            </template>
                        </select>
                    </div>

                    <!-- Student Selection -->
                    <div class="mb-4" x-show="students.length > 0">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nama Siswa</label>
                        <div x-show="loadingStudents" class="flex items-center text-gray-600 mb-2">
                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>Memuat data siswa...</span>
                        </div>
                        <select x-model="selectedStudent" required
                            class="w-full px-4 py-3 text-lg border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                            :disabled="loadingStudents">
                            <option value="">-- Pilih Nama --</option>
                            <template x-for="student in students" :key="student.id">
                                <option :value="student.id" x-text="student.full_name"></option>
                            </template>
                        </select>
                    </div>

                    <!-- Showing selected information when student is selected -->
                    <div class="mb-6 p-4 bg-gray-50 rounded-lg" x-show="selectedStudent">
                        <p class="text-lg"><strong>Nama:</strong> <span x-text="getSelectedStudentName()"></span></p>
                        <p class="text-lg"><strong>Rombel:</strong> <span x-text="getSelectedRombelName()"></span></p>
                        <p class="text-lg"><strong>Kelas:</strong> <span x-text="getSelectedGradeName()"></span></p>
                    </div>

                    <button type="submit" :disabled="!selectedStudent"
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
                
                <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                    <p class="text-lg"><strong>Nama:</strong> <span x-text="student.full_name"></span></p>
                    <p class="text-lg"><strong>Rombel:</strong> <span x-text="student.rombel"></span></p>
                    <p class="text-lg"><strong>Kelas:</strong> <span x-text="student.grade_info"></span></p>
                </div>
                <div class="flex space-x-4">
                    <button @click="prepareClockIn" 
                        class="flex-1 bg-green-600 text-white py-3 rounded-lg hover:bg-green-700 transition duration-200 font-semibold">
                        Absen Datang
                    </button>
                    <button @click="prepareClockOut" 
                        class="flex-1 bg-yellow-500 text-white py-3 rounded-lg hover:bg-yellow-600 transition duration-200 font-semibold">
                        Absen Pulang
                    </button>
                    <button @click="reset" class="flex-1 bg-gray-500 text-white py-3 rounded-lg hover:bg-gray-600 transition duration-200 font-semibold">
                        Batal
                    </button>
                </div>
                <div x-show="student.has_clocked_in && student.has_clocked_out" class="text-center">
                    <p class="text-lg text-green-600 font-semibold">Sudah Absen Masuk dan Pulang</p>
                    <button @click="reset" class="mt-4 bg-gray-500 text-white py-3 rounded-lg hover:bg-gray-600 transition duration-200 font-semibold">
                        Pilih Siswa Lain
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
                    <p class="text-gray-600 mt-2">Mengirim absensi...</p>
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
                <p class="text-sm text-gray-500">Mengarahkan ke halaman utama...</p>
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
        selectedGrade: '',
        selectedRombel: '',
        selectedStudent: '',
        grades: [],
        rombels: [],
        students: [],
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
        loadingGrades: false,
        loadingRombels: false,
        loadingStudents: false,

        async loadRombels() {
            if (!this.selectedGrade) {
                this.rombels = [];
                this.selectedRombel = '';
                this.students = [];
                this.selectedStudent = '';
                return;
            }

            this.loadingRombels = true;
            this.error = '';
            this.selectedRombel = '';
            this.selectedStudent = '';
            this.rombels = [];
            this.students = [];

            try {
                const url = `{{ route("student.attendance.get-rombels", ":grade") }}`;
                const response = await fetch(url.replace(':grade', encodeURIComponent(this.selectedGrade)));
                
                if (!response.ok) {
                    throw new Error(`Gagal memuat rombel: ${response.status} ${response.statusText}`);
                }
                
                const data = await response.json();
                this.rombels = data.rombels || [];
                
                // Show appropriate message if no rombels found
                if (this.rombels.length === 0) {
                    this.error = 'Tidak ada rombel yang ditemukan untuk kelas ini.';
                } else {
                    this.error = ''; // Clear any previous error
                }
            } catch (error) {
                console.error('Error loading rombels:', error);
                this.error = `Gagal memuat data rombel: ${error.message}`;
                this.rombels = [];
                this.selectedRombel = '';
            } finally {
                this.loadingRombels = false;
            }
        },

        async loadStudents() {
            if (!this.selectedRombel) {
                this.students = [];
                this.selectedStudent = '';
                return;
            }

            this.loadingStudents = true;
            this.error = '';
            this.selectedStudent = '';
            this.students = [];

            try {
                const url = `{{ route("student.attendance.get-students", ":rombelId") }}`;
                const response = await fetch(url.replace(':rombelId', encodeURIComponent(this.selectedRombel)));
                
                if (!response.ok) {
                    throw new Error(`Gagal memuat siswa: ${response.status} ${response.statusText}`);
                }
                
                const data = await response.json();
                this.students = data.students || [];
                
                // Show appropriate message if no students found
                if (this.students.length === 0) {
                    this.error = 'Tidak ada siswa yang ditemukan di rombel ini.';
                } else {
                    this.error = ''; // Clear any previous error
                }
            } catch (error) {
                console.error('Error loading students:', error);
                this.error = `Gagal memuat data siswa: ${error.message}`;
                this.students = [];
                this.selectedStudent = '';
            } finally {
                this.loadingStudents = false;
            }
        },

        async confirmStudent() {
            if (!this.selectedStudent) {
                this.error = 'Silakan pilih siswa terlebih dahulu.';
                return;
            }

            this.error = '';
            this.submitting = true;

            try {
                // Check attendance status using new endpoint
                const response = await fetch('{{ route("student.attendance.get-student-info") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ 
                        student_id: this.selectedStudent
                    })
                });

                if (!response.ok) {
                    throw new Error(`Gagal memuat info siswa: ${response.status} ${response.statusText}`);
                }

                const data = await response.json();

                if (data.success) {
                    // Add grade information to student object
                    data.student.grade_info = this.getSelectedGradeName();
                    this.student = data.student;
                    this.step = 2;
                } else {
                    this.error = data.message || 'Gagal memuat informasi siswa.';
                }
            } catch (error) {
                console.error('Error confirming student:', error);
                this.error = error.message || 'Terjadi kesalahan. Silakan coba lagi.';
            } finally {
                this.submitting = false;
            }
        },

        getSelectedGradeName() {
            if (!this.selectedGrade || !this.grades.length) return '';
            const grade = this.grades.find(g => g.id == this.selectedGrade);
            return grade ? grade.name + ' - ' + grade.description : '';
        },

        getSelectedStudentName() {
            if (!this.selectedStudent || !this.students.length) return '';
            const student = this.students.find(s => s.id == this.selectedStudent);
            return student ? student.full_name : '';
        },

        getSelectedRombelName() {
            if (!this.selectedStudent || !this.students.length) return '';
            const student = this.students.find(s => s.id == this.selectedStudent);
            return student ? student.rombel_name : '';
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
            this.selectedGrade = '';
            this.selectedRombel = '';
            this.selectedStudent = '';
            this.rombels = [];
            this.students = [];
            this.student = {};
            this.capturedImage = null;
            this.message = '';
            this.error = '';
            if (this.stream) {
                this.stream.getTracks().forEach(track => track.stop());
            }
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
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        student_id: this.student.id,
                        selfie: this.capturedImage
                    })
                });

                if (!response.ok) {
                    throw new Error(`Gagal mengirim absensi: ${response.status} ${response.statusText}`);
                }

                const data = await response.json();

                this.submitting = false;

                if (data.success) {
                    this.message = data.message;
                    this.showToast = true;
                    this.toastMessage = data.message;
                    this.toastType = 'success';
                    this.step = 4;

                    // Auto-redirect to welcome page after 2 seconds
                    setTimeout(() => {
                        window.location.href = '{{ route("welcome") }}';
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
        },

        async init() {
            // Load grades initially
            this.loadingGrades = true;
            try {
                const response = await fetch('{{ route("student.attendance.get-grades") }}');
                
                if (!response.ok) {
                    throw new Error(`Gagal memuat kelas: ${response.status} ${response.statusText}`);
                }
                
                const data = await response.json();
                this.grades = data.grades;
            } catch (error) {
                console.error('Failed to load grades:', error);
                this.error = `Gagal memuat data kelas: ${error.message}`;
            } finally {
                this.loadingGrades = false;
            }

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
        }
    }
}
</script>
@endpush
@endsection
