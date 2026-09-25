@extends('layouts.app')

@section('title', 'Jam Absensi Siswa')

@section('content')
<div class="container mx-auto px-4 py-8 space-y-6">
    @if($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white rounded-lg shadow-md p-6">
        <h1 class="text-2xl font-bold text-gray-800 mb-2">Jam Absensi Siswa</h1>
        <p class="text-gray-600 mb-4">
            Halaman absensi siswa tidak memakai login (siswa memasukkan NISN atau menempelkan kartu RFID),
            karena itu halaman hanya dibuka pada rentang jam absensi. Jam buka mengikuti
            <a href="{{ route('admin.schedules.custom.siswa') }}" class="text-blue-600 hover:underline">jadwal absen siswa</a>
            hari itu, dan halaman ditutup pada hari libur serta hari non-aktif.
        </p>

        <div class="rounded-lg border p-4 {{ $window['open'] ? 'bg-green-50 border-green-200' : 'bg-yellow-50 border-yellow-200' }}">
            <p class="text-sm text-gray-600">Status halaman absensi siswa saat ini</p>
            <p class="text-lg font-semibold {{ $window['open'] ? 'text-green-700' : 'text-yellow-700' }}">
                {{ $window['open'] ? 'Dibuka' : 'Ditutup' }}
            </p>
            @if($window['opens_at'])
                <p class="text-sm text-gray-600">Jam buka hari ini: {{ $window['opens_at']->format('H:i') }} – {{ $window['closes_at']->format('H:i') }}</p>
            @else
                <p class="text-sm text-gray-600">{{ $window['reason'] }}</p>
            @endif
            <a href="{{ route('student.attendance.index') }}" target="_blank" class="text-sm text-blue-600 hover:underline">Buka halaman absensi siswa →</a>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-bold text-gray-800 mb-4">Pengaturan Jam</h2>
        <form action="{{ route('admin.student-kiosk.settings') }}" method="POST" class="space-y-4">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="open_minutes_before" class="block text-sm font-medium text-gray-700 mb-1">Dibuka ... menit sebelum jam masuk</label>
                    <input type="number" id="open_minutes_before" name="open_minutes_before" min="0" max="240" required
                           value="{{ old('open_minutes_before', $settings['open_minutes_before']) }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label for="close_minutes_after" class="block text-sm font-medium text-gray-700 mb-1">Ditutup ... menit setelah jam pulang</label>
                    <input type="number" id="close_minutes_after" name="close_minutes_after" min="0" max="240" required
                           value="{{ old('close_minutes_after', $settings['close_minutes_after']) }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 font-semibold">
                Simpan Pengaturan
            </button>
        </form>
    </div>
</div>
@endsection
