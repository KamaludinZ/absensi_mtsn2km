<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Absensi MTsN 2 Kota Malang')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex flex-col bg-gray-100">
        <div class="flex-1">
            @yield('content')
        </div>

        <!-- Footer -->
        <footer class="bg-gray-800 text-white py-4 mt-auto">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <p class="text-sm">&copy; {{ date('Y') }} MTsN 2 Kota Malang. Sistem Absensi.</p>
            </div>
        </footer>
    </div>

    <script>
        // Update clock 
        function updateClock() {
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            const timeString = `${hours}:${minutes}:${seconds}`;
            
            // Format date in Indonesian style
            const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
            const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            
            const dayName = days[now.getDay()];
            const day = now.getDate();
            const monthName = months[now.getMonth()];
            const year = now.getFullYear();
            
            const dateString = `${dayName}, ${day} ${monthName} ${year}`;
            
            // Update any elements with these IDs if they exist
            const timeElement = document.getElementById('current-time');
            const dateElement = document.getElementById('current-date');
            
            if (timeElement) {
                timeElement.textContent = timeString;
            }
            
            if (dateElement) {
                dateElement.textContent = dateString;
            }
        }
        
        // Initialize and update every second
        updateClock();
        setInterval(updateClock, 1000);
    </script>
    
    @stack('scripts')
</body>
</html>
