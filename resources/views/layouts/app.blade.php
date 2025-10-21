<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Absensi MTsN 2 Kota Malang')</title>
    
    <!-- Vite CSS -->
    @vite('resources/css/app.css')
    
    <style>
        /* Custom responsive styles */
        .sidebar {
            transition: all 0.3s ease;
        }
        
        .sidebar.collapsed {
            width: 64px !important; /* 16 * 4 = 64px - width of icons + padding */
        }
        
        .sidebar.collapsed .sidebar-text {
            display: none;
        }
        
        .sidebar.collapsed .sidebar-full {
            display: none;
        }
        
        .sidebar.collapsed .sidebar-icon-only {
            display: block;
        }
        
        .sidebar-icon-only {
            display: none;
        }
        
        @media (max-width: 767px) {
            .sidebar {
                position: fixed;
                z-index: 50;
                height: 100vh;
                transform: translateX(-100%);
                width: 16rem !important; /* Maintain width on mobile */
            }
            
            .sidebar.open {
                transform: translateX(0);
            }
            
            .sidebar.collapsed {
                width: 16rem !important; /* Don't collapse on mobile */
                transform: translateX(-100%);
            }
            
            .main-content {
                margin-left: 0 !important;
            }
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex flex-col md:flex-row">
        @include('components.sidebar')

        <div class="flex-1 flex flex-col main-content">
            @include('components.navbar')

            <main class="flex-1 p-4 sm:p-6">
                @if(session('success'))
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4" role="alert">
                        {{ session('success') }}
                    </div>
                @endif

                @if(session('error'))
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4" role="alert">
                        {{ session('error') }}
                    </div>
                @endif

                @yield('content')
            </main>

            <!-- Footer -->
            <footer class="bg-white border-t border-gray-200 py-4 mt-auto">
                <div class="px-4 sm:px-6 text-center">
                    <p class="text-sm text-gray-600">&copy; {{ date('Y') }} MTsN 2 Kota Malang. Sistem Absensi.</p>
                </div>
            </footer>
        </div>
    </div>

    <!-- Vite JS -->
    @vite('resources/js/app.js')
    
    <script>
        // Global sidebar toggle function
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('overlay');
            
            if (window.innerWidth < 768) {
                // Mobile behavior - show/hide
                if (sidebar.classList.contains('translate-x-0')) {
                    // Close sidebar
                    sidebar.classList.remove('translate-x-0');
                    sidebar.classList.add('-translate-x-full');
                    overlay.classList.add('hidden');
                } else {
                    // Open sidebar
                    sidebar.classList.add('translate-x-0');
                    sidebar.classList.remove('-translate-x-full');
                    overlay.classList.remove('hidden');
                }
            } else {
                // Desktop behavior - collapse/expand
                sidebar.classList.toggle('collapsed');
            }
        }
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('overlay');
            const menuButton = document.querySelector('button[onclick="toggleSidebar()"]');
            
            if (window.innerWidth < 768 && 
                sidebar && 
                overlay && 
                menuButton &&
                !sidebar.contains(event.target) && 
                !menuButton.contains(event.target) &&
                sidebar.classList.contains('translate-x-0')) {
                // Close sidebar
                sidebar.classList.remove('translate-x-0');
                sidebar.classList.add('-translate-x-full');
                overlay.classList.add('hidden');
            }
        });
        
        // Update sidebar on window resize
        window.addEventListener('resize', function() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('overlay');
            
            if (window.innerWidth >= 768) {
                // On desktop, maintain collapsed state or show expanded
                if (sidebar) {
                    // Only remove mobile classes, preserve collapsed state
                    sidebar.classList.remove('-translate-x-full');
                    sidebar.classList.add('translate-x-0');
                }
                if (overlay) {
                    overlay.classList.add('hidden');
                }
            } else {
                // On mobile, hide sidebar if it was open and remove collapsed class
                if (sidebar) {
                    sidebar.classList.add('-translate-x-full');
                    sidebar.classList.remove('translate-x-0', 'collapsed');
                }
            }
        });
        
        // Ensure proper state when page loads
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            if (window.innerWidth >= 768 && sidebar) {
                // Desktop: ensure sidebar is not collapsed initially (unless user preference)
                sidebar.classList.remove('-translate-x-full');
                sidebar.classList.add('translate-x-0');
            } else if (sidebar) {
                // Mobile: ensure sidebar is hidden initially
                sidebar.classList.add('-translate-x-full');
                sidebar.classList.remove('translate-x-0');
            }
        });
    </script>
    
    <script>
        // Update clock in navbar
        function updateNavbarClock() {
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
            
            // Update the elements in navbar
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
        updateNavbarClock();
        setInterval(updateNavbarClock, 1000);
    </script>
    
    @stack('scripts')
</body>
</html>
