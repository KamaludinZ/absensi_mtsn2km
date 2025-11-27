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

        <form action="{{ route('admin.attendance-settings.store') }}" method="POST" enctype="multipart/form-data">
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
                <div class="border-b border-gray-200 pb-4">
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

                <!-- Header Settings for PDF -->
                <div class="bg-blue-50 p-4 rounded-lg">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Pengaturan Header Laporan PDF</h3>
                    <p class="text-xs text-gray-600 mb-4">Informasi berikut akan ditampilkan pada header laporan PDF yang diekspor</p>

                    <div class="space-y-4">
                        <!-- Logo Upload Section -->
                        <div class="border-t pt-4">
                            <h4 class="text-md font-semibold text-gray-700 mb-3">Logo Sekolah</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- Left Logo -->
                                <div>
                                    <label for="left_logo" class="block text-sm font-medium text-gray-700 mb-1">
                                        Logo Kiri
                                    </label>
                                    @if(!empty($settings['left_logo_path']))
                                        <div class="mb-2">
                                            <img src="{{ asset($settings['left_logo_path']) }}" alt="Logo Kiri" class="h-20 border border-gray-300 rounded">
                                        </div>
                                    @endif
                                    <input
                                        type="file"
                                        id="left_logo"
                                        name="left_logo"
                                        accept="image/png,image/jpeg,image/jpg"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    >
                                    <p class="mt-1 text-xs text-gray-500">Format: PNG, JPG, JPEG (Max: 2MB). Recommended: 100x100px</p>
                                    @error('left_logo')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Right Logo -->
                                <div>
                                    <label for="right_logo" class="block text-sm font-medium text-gray-700 mb-1">
                                        Logo Kanan
                                    </label>
                                    @if(!empty($settings['right_logo_path']))
                                        <div class="mb-2">
                                            <img src="{{ asset($settings['right_logo_path']) }}" alt="Logo Kanan" class="h-20 border border-gray-300 rounded">
                                        </div>
                                    @endif
                                    <input
                                        type="file"
                                        id="right_logo"
                                        name="right_logo"
                                        accept="image/png,image/jpeg,image/jpg"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    >
                                    <p class="mt-1 text-xs text-gray-500">Format: PNG, JPG, JPEG (Max: 2MB). Recommended: 100x100px</p>
                                    @error('right_logo')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <!-- School Name -->
                        <div>
                            <label for="school_name" class="block text-sm font-medium text-gray-700 mb-1">
                                Nama Sekolah <span class="text-red-500">*</span>
                            </label>
                            <input
                                type="text"
                                id="school_name"
                                name="school_name"
                                value="{{ old('school_name', $settings['school_name']) }}"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="Contoh: MTsN 2 KOTA MALANG"
                                required
                            >
                            @error('school_name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- School Full Name -->
                        <div>
                            <label for="school_full_name" class="block text-sm font-medium text-gray-700 mb-1">
                                Nama Lengkap Sekolah <span class="text-red-500">*</span>
                            </label>
                            <input
                                type="text"
                                id="school_full_name"
                                name="school_full_name"
                                value="{{ old('school_full_name', $settings['school_full_name']) }}"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="Contoh: MADRASAH TSANAWIYAH NEGERI 2 KOTA MALANG"
                                required
                            >
                            @error('school_full_name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- School Address -->
                        <div>
                            <label for="school_address" class="block text-sm font-medium text-gray-700 mb-1">
                                Alamat Sekolah <span class="text-red-500">*</span>
                            </label>
                            <textarea
                                id="school_address"
                                name="school_address"
                                rows="2"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="Contoh: Jl. KH. Hasyim Asy'ari No. 1, Kota Malang, Jawa Timur 65144"
                                required
                            >{{ old('school_address', $settings['school_address']) }}</textarea>
                            @error('school_address')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- School Phone -->
                        <div>
                            <label for="school_phone" class="block text-sm font-medium text-gray-700 mb-1">
                                Nomor Telepon
                            </label>
                            <input
                                type="text"
                                id="school_phone"
                                name="school_phone"
                                value="{{ old('school_phone', $settings['school_phone']) }}"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="Contoh: (0341) 123456"
                            >
                            @error('school_phone')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- School Email -->
                        <div>
                            <label for="school_email" class="block text-sm font-medium text-gray-700 mb-1">
                                Email Sekolah
                            </label>
                            <input
                                type="email"
                                id="school_email"
                                name="school_email"
                                value="{{ old('school_email', $settings['school_email']) }}"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="Contoh: info@mtsn2kotamalang.sch.id"
                            >
                            @error('school_email')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Headmaster Name -->
                        <div>
                            <label for="headmaster_name" class="block text-sm font-medium text-gray-700 mb-1">
                                Nama Kepala Madrasah
                            </label>
                            <input
                                type="text"
                                id="headmaster_name"
                                name="headmaster_name"
                                value="{{ old('headmaster_name', $settings['headmaster_name']) }}"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="Contoh: Dr. H. Ahmad Fauzi, M.Pd"
                            >
                            @error('headmaster_name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Headmaster NIP -->
                        <div>
                            <label for="headmaster_nip" class="block text-sm font-medium text-gray-700 mb-1">
                                NIP Kepala Madrasah
                            </label>
                            <input
                                type="text"
                                id="headmaster_nip"
                                name="headmaster_nip"
                                value="{{ old('headmaster_nip', $settings['headmaster_nip']) }}"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="Contoh: 196801011990031002"
                            >
                            @error('headmaster_nip')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
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
