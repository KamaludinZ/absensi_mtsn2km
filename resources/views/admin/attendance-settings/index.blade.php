@extends('layouts.app')

@section('title', 'Pengaturan Absensi')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="bg-white rounded-lg shadow-md p-6">
        <h1 class="text-2xl font-bold text-gray-800 mb-6">Pengaturan Absensi</h1>

        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

        <form action="{{ route('admin.attendance-settings.store') }}" method="POST">
            @csrf

            <div class="space-y-6">
                <!-- Require Selfie Photo -->
                <div class="border-b border-gray-200 pb-4">
                    <div class="flex items-start">
                        <div class="flex items-center h-5">
                            <input
                                type="hidden"
                                name="require_selfie_photo"
                                value="0"
                            >
                            <input
                                type="checkbox"
                                id="require_selfie_photo"
                                name="require_selfie_photo"
                                value="1"
                                {{ old('require_selfie_photo', $settings['require_selfie_photo']) ? 'checked' : '' }}
                                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                            >
                        </div>
                        <div class="ml-3 text-sm">
                            <label for="require_selfie_photo" class="font-medium text-gray-700">
                                Wajibkan Foto Selfie
                            </label>
                            <p class="text-gray-500 mt-1">
                                Jika diaktifkan, pengguna diwajibkan mengambil foto selfie saat melakukan absensi
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Require Check In -->
                <div class="border-b border-gray-200 pb-4">
                    <div class="flex items-start">
                        <div class="flex items-center h-5">
                            <input
                                type="hidden"
                                name="require_check_in"
                                value="0"
                            >
                            <input
                                type="checkbox"
                                id="require_check_in"
                                name="require_check_in"
                                value="1"
                                {{ old('require_check_in', $settings['require_check_in']) ? 'checked' : '' }}
                                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                            >
                        </div>
                        <div class="ml-3 text-sm">
                            <label for="require_check_in" class="font-medium text-gray-700">
                                Wajibkan Absensi Datang
                            </label>
                            <p class="text-gray-500 mt-1">
                                Jika diaktifkan, pengguna diwajibkan melakukan absensi datang (check-in)
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Require Check Out -->
                <div class="border-b border-gray-200 pb-4">
                    <div class="flex items-start">
                        <div class="flex items-center h-5">
                            <input
                                type="hidden"
                                name="require_check_out"
                                value="0"
                            >
                            <input
                                type="checkbox"
                                id="require_check_out"
                                name="require_check_out"
                                value="1"
                                {{ old('require_check_out', $settings['require_check_out']) ? 'checked' : '' }}
                                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                            >
                        </div>
                        <div class="ml-3 text-sm">
                            <label for="require_check_out" class="font-medium text-gray-700">
                                Wajibkan Absensi Pulang
                            </label>
                            <p class="text-gray-500 mt-1">
                                Jika diaktifkan, pengguna diwajibkan melakukan absensi pulang (check-out)
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Time Difference Format -->
                <div class="pb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-3">
                        Format Perhitungan Selisih Waktu
                    </label>
                    <div class="space-y-3">
                        <div class="flex items-start">
                            <div class="flex items-center h-5">
                                <input
                                    type="radio"
                                    id="format_time"
                                    name="time_difference_format"
                                    value="time"
                                    {{ old('time_difference_format', $settings['time_difference_format']) == 'time' ? 'checked' : '' }}
                                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                                >
                            </div>
                            <div class="ml-3 text-sm">
                                <label for="format_time" class="font-medium text-gray-700">
                                    Format Waktu (HH:mm:ss)
                                </label>
                                <p class="text-gray-500 mt-1">
                                    Menampilkan selisih dalam format jam:menit:detik. Contoh: +00:15:30 atau -00:10:00
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start">
                            <div class="flex items-center h-5">
                                <input
                                    type="radio"
                                    id="format_minutes"
                                    name="time_difference_format"
                                    value="minutes"
                                    {{ old('time_difference_format', $settings['time_difference_format']) == 'minutes' ? 'checked' : '' }}
                                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                                >
                            </div>
                            <div class="ml-3 text-sm">
                                <label for="format_minutes" class="font-medium text-gray-700">
                                    Total Menit
                                </label>
                                <p class="text-gray-500 mt-1">
                                    Menampilkan selisih dalam total menit. Contoh: +15 menit atau -10 menit
                                </p>
                            </div>
                        </div>
                    </div>
                    <p class="mt-2 text-xs text-gray-500">
                        Format ini akan diterapkan pada kolom selisih di report absensi (Admin, Monitoring, dan Pegawai)
                    </p>
                </div>
            </div>

            <div class="mt-6 pt-6 border-t border-gray-200">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-6 rounded-md transition duration-300 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    Simpan Pengaturan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
