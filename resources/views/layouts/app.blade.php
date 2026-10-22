<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#1e40af">
    <meta name="description" content="Sistem Absensi MTsN 2 Kota Malang">
    <!-- Permissions Policy for geolocation and camera access -->
    <meta http-equiv="Permissions-Policy" content="geolocation=(self), camera=(self), microphone=*, autoplay=*, fullscreen=*">
    <title>@yield('title', 'Absensi MTsN 2 Kota Malang')</title>

    @php
        $manifestPath = public_path('build/manifest.json');
        $cssPath = 'build/assets/app-Cv3Cf-7s.css'; // fallback

        if (file_exists($manifestPath)) {
            $manifest = json_decode(file_get_contents($manifestPath), true);
            if (isset($manifest['resources/css/app.css']['file'])) {
                $cssPath = 'build/' . $manifest['resources/css/app.css']['file'];
            }
        }
    @endphp
    <link rel="stylesheet" href="{{ asset($cssPath) }}">
    @stack('styles')
</head>
<body class="@yield('body-class', '')">
    <div class="min-h-screen flex flex-col md:flex-row">
        @include('components.sidebar')

        <div class="flex-1 flex flex-col main-content">
            @include('components.navbar')

            <div class="flex-1 flex flex-col">
                <main class="p-4 sm:p-6 bg-gray-50 flex-1 overflow-y-auto @yield('body-class', '')">
                    <div class="@yield('container-class', 'container-layout')">
                        @if(session('success'))
                            <div class="alert alert-success fade-in" role="alert">
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                    <span>{{ session('success') }}</span>
                                </div>
                            </div>
                        @endif

                        @if((auth()->user()?->role?->name === 'admin') && !\App\Support\PhpSecurity::isPatched())
                            <div class="alert alert-error" role="alert">
                                <div class="flex items-start">
                                    <svg class="w-5 h-5 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                    <span>
                                        <strong>Peringatan keamanan:</strong> server ini memakai PHP {{ PHP_VERSION }} yang punya celah keamanan kritis (PHP2Shell, CVE-2026-109618 / CVE-2026-109619).
                                        Segera perbarui PHP ke versi {{ \App\Support\PhpSecurity::recommendedVersion() }} atau lebih baru, lalu restart PHP. Di Docker/Coolify: lakukan Redeploy.
                                    </span>
                                </div>
                            </div>
                        @endif

                        @if(session('error'))
                            <div class="alert alert-error fade-in" role="alert">
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                    </svg>
                                    <span>{{ session('error') }}</span>
                                </div>
                            </div>
                        @endif

                        @yield('content')
                    </div>
                </main>

                <!-- Footer -->
                <footer class="bg-white border-t border-gray-200 py-6">
                    <div class="container-layout">
                        <div class="flex flex-col md:flex-row justify-between items-center">
                            <p class="text-sm text-gray-600 mb-2 md:mb-0">
                                Versi: {{ 
                                    file_exists(base_path('VERSION')) 
                                        ? trim(file_get_contents(base_path('VERSION'))) 
                                        : '1.0.2' 
                                }} | &copy; {{ date('Y') }} MTsN 2 Kota Malang. Sistem Absensi.
                            </p>
                            <div class="flex space-x-6">
                                <a href="#" class="text-gray-500 hover:text-gray-700 transition-colors">
                                    <span class="sr-only">Privacy Policy</span>
                                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                </a>
                                <a href="#" class="text-gray-500 hover:text-gray-700 transition-colors">
                                    <span class="sr-only">Support</span>
                                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                                    </svg>
                                </a>
                            </div>
                        </div>
                    </div>
                </footer>
            </div>

    @php
        $manifestPath = public_path('build/manifest.json');
        $jsPath = 'build/assets/app-KNSYXkWY.js'; // fallback

        if (file_exists($manifestPath)) {
            $manifest = json_decode(file_get_contents($manifestPath), true);
            if (isset($manifest['resources/js/app.js']['file'])) {
                $jsPath = 'build/' . $manifest['resources/js/app.js']['file'];
            }
        }
    @endphp
    <script type="module" src="{{ asset($jsPath) }}"></script>

    @stack('scripts')
</body>
</html>