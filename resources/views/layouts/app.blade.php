<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#1e40af">
    <meta name="description" content="Sistem Absensi MTsN 2 Kota Malang">
    <title>@yield('title', 'Absensi MTsN 2 Kota Malang')</title>

    <!-- Vite CSS -->
    @vite('resources/css/app.css')
</head>
<body>
    <div class="min-h-screen flex flex-col md:flex-row">
        @include('components.sidebar')

        <div class="flex-1 flex flex-col main-content">
            @include('components.navbar')

            <main class="flex-1 p-4 sm:p-6 bg-gray-50">
                <div class="container-layout">
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
            <footer class="bg-white border-t border-gray-200 py-6 mt-auto">
                <div class="container-layout">
                    <div class="flex flex-col md:flex-row justify-between items-center">
                        <p class="text-sm text-gray-600 mb-2 md:mb-0">
                            &copy; {{ date('Y') }} MTsN 2 Kota Malang. Sistem Absensi.
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
    </div>

    <!-- Vite JS -->
    @vite('resources/js/app.js')

    @stack('scripts')
</body>
</html>
