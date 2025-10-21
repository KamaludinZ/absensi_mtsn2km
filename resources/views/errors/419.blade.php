@extends('layouts.guest')

@section('title', '419 - Sesi Kedaluwarsa')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-yellow-500 to-orange-700 flex items-center justify-center px-4">
    <div class="max-w-md w-full bg-white rounded-lg shadow-xl p-8 text-center">
        <div class="text-6xl font-bold text-orange-600 mb-4">419</div>
        <h1 class="text-2xl font-bold text-gray-800 mb-4">Sesi Kedaluwarsa</h1>
        <p class="text-gray-600 mb-6">
            Sesi Anda telah kedaluwarsa. Silakan refresh halaman dan coba lagi.
        </p>
        <button onclick="window.location.reload()" class="bg-orange-600 text-white px-6 py-3 rounded-lg hover:bg-orange-700 transition duration-200 inline-block mr-2">
            Refresh Halaman
        </button>
        <a href="{{ url('/') }}" class="bg-gray-600 text-white px-6 py-3 rounded-lg hover:bg-gray-700 transition duration-200 inline-block">
            Kembali ke Beranda
        </a>
    </div>
</div>
@endsection
