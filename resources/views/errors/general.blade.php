<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error - MTsN 2 Kota Malang</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="text-center max-w-md mx-auto p-6">
        <div class="bg-white p-8 rounded-lg shadow-lg">
            <div class="text-6xl mb-4">⚠️</div>
            <h1 class="text-2xl font-bold text-gray-800 mb-4">@yield('title', 'Terjadi Kesalahan')</h1>
            <p class="text-gray-600 mb-6">
                @yield('message', 'Maaf, terjadi kesalahan pada sistem kami.')
            </p>
            <a href="{{ url('/') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition duration-200 inline-block">
                Kembali ke Beranda
            </a>
        </div>
    </div>
</body>
</html>