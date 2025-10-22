@extends('layouts.guest')

@section('title', 'Selamat Datang - MTsN 2 Kota Malang')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-blue-600 via-indigo-700 to-purple-800 relative overflow-hidden">
    <!-- Animated Background Elements -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-40 -right-40 w-80 h-80 bg-purple-500 rounded-full mix-blend-multiply filter blur-xl opacity-20 animate-blob"></div>
        <div class="absolute -bottom-40 -left-40 w-80 h-80 bg-blue-500 rounded-full mix-blend-multiply filter blur-xl opacity-20 animate-blob animation-delay-2000"></div>
        <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-80 h-80 bg-indigo-500 rounded-full mix-blend-multiply filter blur-xl opacity-20 animate-blob animation-delay-4000"></div>
    </div>

    <!-- Navbar -->
    <nav class="relative bg-white/10 backdrop-blur-md border-b border-white/20 shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16 sm:h-20">
                <!-- Logo & Title -->
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 sm:w-12 sm:h-12 bg-white rounded-xl flex items-center justify-center shadow-lg">
                        <svg class="w-6 h-6 sm:w-7 sm:h-7 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                    </div>
                    <div class="hidden sm:block">
                        <h1 class="text-lg sm:text-xl font-bold text-white">MTsN 2 Kota Malang</h1>
                        <p class="text-xs text-blue-100">Sistem Absensi Digital</p>
                    </div>
                </div>

                <!-- Clock & Login -->
                <div class="flex items-center space-x-3 sm:space-x-6">
                    <div x-data="clockApp()" class="hidden md:block text-right">
                        <p class="text-lg font-bold text-white" x-text="currentTime">--:--:--</p>
                        <p class="text-xs text-blue-100" x-text="currentDate">Loading...</p>
                    </div>
                    <a href="{{ route('login') }}" class="bg-white text-blue-600 px-4 sm:px-6 py-2 sm:py-2.5 rounded-lg hover:bg-blue-50 transition-all duration-200 font-semibold text-sm sm:text-base shadow-lg hover:shadow-xl transform hover:scale-105">
                        <span class="hidden sm:inline">Login Admin</span>
                        <span class="sm:hidden">Login</span>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-20">
        <div class="text-center text-white mb-12 sm:mb-16 fade-in">
            <h2 class="text-3xl sm:text-4xl md:text-5xl lg:text-6xl font-bold mb-4 sm:mb-6">
                Sistem Absensi <span class="text-blue-200">Digital</span>
            </h2>
            <p class="text-base sm:text-lg md:text-xl text-blue-100 max-w-3xl mx-auto px-4">
                Selamat datang di sistem absensi digital MTsN 2 Kota Malang.
                Sistem modern untuk memudahkan pencatatan kehadiran siswa dan pegawai.
            </p>

            <!-- Live Stats -->
            <div class="grid grid-cols-3 gap-3 sm:gap-6 max-w-3xl mx-auto mt-8 sm:mt-12">
                <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-xl p-3 sm:p-6">
                    <p class="text-2xl sm:text-4xl font-bold text-white">500+</p>
                    <p class="text-xs sm:text-sm text-blue-100 mt-1 sm:mt-2">Siswa Aktif</p>
                </div>
                <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-xl p-3 sm:p-6">
                    <p class="text-2xl sm:text-4xl font-bold text-white">50+</p>
                    <p class="text-xs sm:text-sm text-blue-100 mt-1 sm:mt-2">Pegawai</p>
                </div>
                <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-xl p-3 sm:p-6">
                    <p class="text-2xl sm:text-4xl font-bold text-white">99%</p>
                    <p class="text-xs sm:text-sm text-blue-100 mt-1 sm:mt-2">Kehadiran</p>
                </div>
            </div>
        </div>

        <!-- Main Action Cards -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 sm:gap-8 mt-12">
            <!-- Student Attendance Card -->
            <div class="group bg-white rounded-2xl shadow-2xl p-6 sm:p-8 hover:shadow-3xl transition-all duration-300 transform hover:-translate-y-2">
                <div class="text-center">
                    <!-- Icon -->
                    <div class="mx-auto w-16 h-16 sm:w-20 sm:h-20 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl flex items-center justify-center mb-4 sm:mb-6 shadow-lg group-hover:scale-110 transition-transform duration-300">
                        <svg class="h-8 w-8 sm:h-10 sm:w-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M12 14l9-5-9-5-9 5 9 5z"/>
                            <path d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222"/>
                        </svg>
                    </div>

                    <!-- Content -->
                    <h3 class="text-2xl sm:text-3xl font-bold text-gray-800 mb-3 sm:mb-4">Absensi Siswa</h3>
                    <p class="text-sm sm:text-base text-gray-600 mb-6 sm:mb-8 px-2">
                        Pilih jenjang, rombel dan nama untuk melakukan absensi dengan mudah dan cepat
                    </p>

                    <!-- Features -->
                    <div class="space-y-2 sm:space-y-3 mb-6 sm:mb-8 text-left">
                        <div class="flex items-center text-gray-700">
                            <svg class="w-4 h-4 sm:w-5 sm:h-5 text-green-500 mr-2 sm:mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span class="text-xs sm:text-sm">Proses cepat dan mudah</span>
                        </div>
                        <div class="flex items-center text-gray-700">
                            <svg class="w-4 h-4 sm:w-5 sm:h-5 text-green-500 mr-2 sm:mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span class="text-xs sm:text-sm">Tanpa perlu login</span>
                        </div>
                        <div class="flex items-center text-gray-700">
                            <svg class="w-4 h-4 sm:w-5 sm:h-5 text-green-500 mr-2 sm:mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span class="text-xs sm:text-sm">Real-time monitoring</span>
                        </div>
                    </div>

                    <!-- CTA Button -->
                    <a href="{{ route('student.attendance.index') }}" class="group inline-flex items-center justify-center w-full bg-gradient-to-r from-blue-600 to-indigo-600 text-white px-6 sm:px-8 py-3 sm:py-4 rounded-xl hover:from-blue-700 hover:to-indigo-700 transition-all duration-200 font-semibold text-sm sm:text-base shadow-lg hover:shadow-xl transform hover:scale-105">
                        Mulai Absensi Siswa
                        <svg class="ml-2 w-4 h-4 sm:w-5 sm:h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                        </svg>
                    </a>
                </div>
            </div>

            <!-- Staff Attendance Card -->
            <div class="group bg-white rounded-2xl shadow-2xl p-6 sm:p-8 hover:shadow-3xl transition-all duration-300 transform hover:-translate-y-2">
                <div class="text-center">
                    <!-- Icon -->
                    <div class="mx-auto w-16 h-16 sm:w-20 sm:h-20 bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl flex items-center justify-center mb-4 sm:mb-6 shadow-lg group-hover:scale-110 transition-transform duration-300">
                        <svg class="h-8 w-8 sm:h-10 sm:w-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </div>

                    <!-- Content -->
                    <h3 class="text-2xl sm:text-3xl font-bold text-gray-800 mb-3 sm:mb-4">Guru & Tenaga Kependidikan</h3>
                    <p class="text-sm sm:text-base text-gray-600 mb-6 sm:mb-8 px-2">
                        Login untuk mengakses sistem absensi pegawai, perizinan, dan laporan kehadiran
                    </p>

                    <!-- Features -->
                    <div class="space-y-2 sm:space-y-3 mb-6 sm:mb-8 text-left">
                        <div class="flex items-center text-gray-700">
                            <svg class="w-4 h-4 sm:w-5 sm:h-5 text-green-500 mr-2 sm:mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span class="text-xs sm:text-sm">Absensi dengan foto selfie</span>
                        </div>
                        <div class="flex items-center text-gray-700">
                            <svg class="w-4 h-4 sm:w-5 sm:h-5 text-green-500 mr-2 sm:mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span class="text-xs sm:text-sm">Pengajuan perizinan online</span>
                        </div>
                        <div class="flex items-center text-gray-700">
                            <svg class="w-4 h-4 sm:w-5 sm:h-5 text-green-500 mr-2 sm:mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span class="text-xs sm:text-sm">Laporan kehadiran lengkap</span>
                        </div>
                    </div>

                    <!-- CTA Button -->
                    <a href="{{ route('login') }}" class="group inline-flex items-center justify-center w-full bg-gradient-to-r from-green-600 to-emerald-600 text-white px-6 sm:px-8 py-3 sm:py-4 rounded-xl hover:from-green-700 hover:to-emerald-700 transition-all duration-200 font-semibold text-sm sm:text-base shadow-lg hover:shadow-xl transform hover:scale-105">
                        Login Pegawai
                        <svg class="ml-2 w-4 h-4 sm:w-5 sm:h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                        </svg>
                    </a>
                </div>
            </div>
        </div>

        <!-- Footer Info -->
        <div class="mt-12 sm:mt-16 text-center">
            <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-4 sm:p-6 max-w-4xl mx-auto">
                <p class="text-white text-xs sm:text-sm mb-3 sm:mb-4">
                    <span class="font-semibold">MTsN 2 Kota Malang</span> - Sistem Absensi Digital
                </p>
                <p class="text-blue-100 text-xs sm:text-sm">
                    Memudahkan pencatatan dan monitoring kehadiran dengan teknologi modern
                </p>
            </div>
        </div>
    </div>
</div>

<style>
@keyframes blob {
    0%, 100% {
        transform: translate(0, 0) scale(1);
    }
    33% {
        transform: translate(30px, -50px) scale(1.1);
    }
    66% {
        transform: translate(-20px, 20px) scale(0.9);
    }
}

.animate-blob {
    animation: blob 7s infinite;
}

.animation-delay-2000 {
    animation-delay: 2s;
}

.animation-delay-4000 {
    animation-delay: 4s;
}

.shadow-3xl {
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
}
</style>

@push('scripts')
<script>
function clockApp() {
    return {
        currentTime: '',
        currentDate: '',

        init() {
            this.updateClock();
            setInterval(() => this.updateClock(), 1000);
        },

        updateClock() {
            const now = new Date();
            this.currentTime = now.toLocaleTimeString('id-ID', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });

            this.currentDate = now.toLocaleDateString('id-ID', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        }
    }
}
</script>
@endpush
@endsection
