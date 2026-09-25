@extends('layouts.guest')

@section('title', 'Absensi Siswa - MTsN 2 Kota Malang')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-blue-500 to-blue-700 py-8">
    <div class="max-w-2xl mx-auto px-4">
        <div class="text-center text-white mb-8">
            <h1 class="text-3xl font-bold mb-2">Absensi Siswa</h1>
            <p>MTsN 2 Kota Malang</p>
        </div>

        <div class="bg-white rounded-lg shadow-xl p-8 text-center">
            <svg class="mx-auto h-16 w-16 text-yellow-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
            <h2 class="text-2xl font-bold text-gray-800 mb-3">{{ $title }}</h2>
            <p class="text-lg text-gray-600 mb-6">{{ $message }}</p>
            <a href="{{ route('welcome') }}" class="inline-block bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition duration-200 font-semibold">
                Kembali ke Halaman Utama
            </a>
        </div>
    </div>
</div>
@endsection
