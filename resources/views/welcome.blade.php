@extends('layouts.guest')

@section('title', 'Selamat Datang - MTsN 2 Kota Malang')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-blue-500 to-blue-700">
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col">
                <div class="flex flex-wrap justify-between items-center h-16 gap-2">
                    <div class="flex items-center">
                        <h1 class="text-xl font-bold text-gray-800">MTsN 2 Kota Malang</h1>
                    </div>
                    <!-- Live Date/Time and Badge Status -->
                    <div x-data="clockApp()" class="flex flex-col items-center">
                        <p class="text-lg font-bold text-gray-800" x-text="currentTime"></p>
                        <div class="flex items-center justify-center space-x-3">
                            <p class="text-sm text-gray-600" x-text="currentDate"></p>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <a href="{{ route('login') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition duration-200">
                            Login Admin
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="text-center text-white mb-12">
            <h2 class="text-4xl font-bold mb-4">Sistem Absensi Digital</h2>
            <p class="text-lg">Selamat datang di sistem absensi digital MTsN 2 Kota Malang. Silakan pilih opsi di bawah untuk melanjutkan.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mt-12">
            <div class="bg-white rounded-lg shadow-xl p-8">
                <div class="text-center">
                    <svg class="mx-auto h-16 w-16 text-blue-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    <h3 class="text-2xl font-bold text-gray-800 mb-4">Absensi Siswa</h3>
                    <p class="text-gray-600 mb-6">Pilih jenjang, rombel dan nama untuk melakukan absensi</p>
                    <a href="{{ route('student.attendance.index') }}" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition duration-200 inline-block">
                        Mulai Absensi
                    </a>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-xl p-8">
                <div class="text-center">
                    <svg class="mx-auto h-16 w-16 text-green-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    <h3 class="text-2xl font-bold text-gray-800 mb-4">Guru & Tenaga Kependidikan</h3>
                    <p class="text-gray-600 mb-6">Login untuk akses sistem absensi pegawai</p>
                    <a href="{{ route('login') }}" class="bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 transition duration-200 inline-block">
                        Login
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function clockApp() {
    return {
        currentTime: '',
        currentDate: '',
        formattedDateTime: '',
        badge: null,

        init() {
            this.updateClock();
            setInterval(() => this.updateClock(), 1000);
            this.fetchScheduleStatus();
            // Update schedule status every minute
            setInterval(() => this.fetchScheduleStatus(), 60000);
        },

        updateClock() {
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            // For consistency with employee attendance, format time as HH:MM only (no seconds)
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
                const data = await response.json();
                // We no longer need to set these from API since we're formatting them locally
                // this.currentDate = data.current_date;
                // this.formattedDateTime = data.formatted_datetime;
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
