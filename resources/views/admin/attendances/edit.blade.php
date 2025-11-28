@extends('layouts.app')

@section('title', 'Edit Absensi')
@section('page-title', 'Edit Absensi')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="mb-4">
        @if($attendance->attendable_type === 'App\\Models\\Student')
            <a href="{{ route('admin.reports.students') }}" class="text-blue-600 hover:text-blue-800">
                &larr; Kembali ke Laporan Siswa
            </a>
        @else
            <a href="{{ route('admin.reports.index') }}" class="text-blue-600 hover:text-blue-800">
                &larr; Kembali ke Laporan Pegawai
            </a>
        @endif
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-2xl font-bold mb-6">Edit Data Absensi</h2>

        @if($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form action="{{ route('admin.attendances.update', $attendance->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Attendable Information (Read-only) -->
                <div class="md:col-span-2">
                    @if($attendance->attendable_type === 'App\\Models\\Student')
                        <h3 class="text-lg font-semibold mb-4 text-gray-700">Informasi Siswa</h3>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-600">Nama Lengkap</label>
                                    <p class="text-gray-900 font-semibold">{{ $attendance->attendable->full_name ?? 'N/A' }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-600">Rombel</label>
                                    <p class="text-gray-900">{{ $attendance->attendable->rombel->name ?? 'N/A' }}</p>
                                </div>
                            </div>
                        </div>
                    @else
                        <h3 class="text-lg font-semibold mb-4 text-gray-700">Informasi Pegawai</h3>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-600">Nama Lengkap</label>
                                    <p class="text-gray-900 font-semibold">{{ $attendance->attendable->full_name ?? 'N/A' }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-600">Jabatan</label>
                                    <p class="text-gray-900">{{ $attendance->attendable->position ?? 'N/A' }}</p>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Date -->
                <div class="md:col-span-2">
                    <label for="date" class="block text-sm font-medium text-gray-700 mb-2">Tanggal <span class="text-red-500">*</span></label>
                    <input type="date"
                           id="date"
                           name="date"
                           value="{{ old('date', $attendance->date->format('Y-m-d')) }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           required>
                </div>

                <!-- Clock In Time -->
                <div>
                    <label for="clock_in" class="block text-sm font-medium text-gray-700 mb-2">Jam Masuk</label>
                    <input type="time"
                           id="clock_in"
                           name="clock_in"
                           value="{{ old('clock_in', $attendance->clock_in ? $attendance->clock_in->format('H:i') : '') }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           step="1">
                    <p class="text-xs text-gray-500 mt-1">Kosongkan jika tidak ada jam masuk</p>
                </div>

                <!-- Clock Out Time -->
                <div>
                    <label for="clock_out" class="block text-sm font-medium text-gray-700 mb-2">Jam Keluar</label>
                    <input type="time"
                           id="clock_out"
                           name="clock_out"
                           value="{{ old('clock_out', $attendance->clock_out ? $attendance->clock_out->format('H:i') : '') }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           step="1">
                    <p class="text-xs text-gray-500 mt-1">Kosongkan jika tidak ada jam keluar</p>
                </div>

                <!-- Status In -->
                <div>
                    <label for="status_in" class="block text-sm font-medium text-gray-700 mb-2">Status Masuk</label>
                    <select id="status_in"
                            name="status_in"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">Pilih Status</option>
                        <!-- Perizinan -->
                        <optgroup label="Perizinan">
                            <option value="C" {{ old('status_in', $attendance->status_in) == 'C' ? 'selected' : '' }}>Cuti</option>
                            <option value="S" {{ old('status_in', $attendance->status_in) == 'S' ? 'selected' : '' }}>Sakit</option>
                            <option value="DL" {{ old('status_in', $attendance->status_in) == 'DL' ? 'selected' : '' }}>Dinas Luar</option>
                        </optgroup>
                        <!-- Status Kehadiran -->
                        <optgroup label="Kehadiran">
                            <option value="Tepat Waktu" {{ old('status_in', $attendance->status_in) == 'Tepat Waktu' ? 'selected' : '' }}>Tepat Waktu</option>
                            <option value="Terlambat" {{ old('status_in', $attendance->status_in) == 'Terlambat' ? 'selected' : '' }}>Terlambat</option>
                            <option value="present" {{ old('status_in', $attendance->status_in) == 'present' ? 'selected' : '' }}>Hadir</option>
                        </optgroup>
                    </select>
                </div>

                <!-- Status Out -->
                <div>
                    <label for="status_out" class="block text-sm font-medium text-gray-700 mb-2">Status Keluar</label>
                    <select id="status_out"
                            name="status_out"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">Pilih Status</option>
                        <!-- Perizinan -->
                        <optgroup label="Perizinan">
                            <option value="C" {{ old('status_out', $attendance->status_out) == 'C' ? 'selected' : '' }}>Cuti</option>
                            <option value="S" {{ old('status_out', $attendance->status_out) == 'S' ? 'selected' : '' }}>Sakit</option>
                            <option value="DL" {{ old('status_out', $attendance->status_out) == 'DL' ? 'selected' : '' }}>Dinas Luar</option>
                        </optgroup>
                        <!-- Status Kehadiran -->
                        <optgroup label="Kehadiran">
                            <option value="Tepat Waktu" {{ old('status_out', $attendance->status_out) == 'Tepat Waktu' ? 'selected' : '' }}>Tepat Waktu</option>
                            <option value="Pulang Awal" {{ old('status_out', $attendance->status_out) == 'Pulang Awal' ? 'selected' : '' }}>Pulang Awal</option>
                            <option value="present" {{ old('status_out', $attendance->status_out) == 'present' ? 'selected' : '' }}>Hadir</option>
                        </optgroup>
                    </select>
                </div>

                <!-- Current Selfie Paths (Read-only info) -->
                @if($attendance->selfie_path_in || $attendance->selfie_path_out)
                <div class="md:col-span-2">
                    <h3 class="text-sm font-medium text-gray-700 mb-2">Foto Selfie</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @if($attendance->selfie_path_in)
                        <div class="bg-gray-50 p-3 rounded">
                            <p class="text-xs text-gray-600 mb-2">Foto Masuk:</p>
                            @php
                                $filename = basename($attendance->selfie_path_in);
                                $type = 'employee';
                            @endphp
                            <a href="{{ route('selfies.show', ['type' => $type, 'filename' => $filename]) }}" target="_blank">
                                <img src="{{ route('selfies.show', ['type' => $type, 'filename' => $filename]) }}" alt="Selfie Masuk" class="w-16 h-16 object-cover rounded border cursor-pointer hover:opacity-80">
                            </a>
                        </div>
                        @endif
                        @if($attendance->selfie_path_out)
                        <div class="bg-gray-50 p-3 rounded">
                            <p class="text-xs text-gray-600 mb-2">Foto Keluar:</p>
                            @php
                                $filename = basename($attendance->selfie_path_out);
                                $type = 'employee';
                            @endphp
                            <a href="{{ route('selfies.show', ['type' => $type, 'filename' => $filename]) }}" target="_blank">
                                <img src="{{ route('selfies.show', ['type' => $type, 'filename' => $filename]) }}" alt="Selfie Pulang" class="w-16 h-16 object-cover rounded border cursor-pointer hover:opacity-80">
                            </a>
                        </div>
                        @endif
                    </div>
                </div>
                @endif
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-end space-x-3 mt-8">
                @if($attendance->attendable_type === 'App\\Models\\Student')
                    <a href="{{ route('admin.reports.students') }}"
                       class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                        Batal
                    </a>
                @else
                    <a href="{{ route('admin.reports.index') }}"
                       class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                        Batal
                    </a>
                @endif
                <button type="submit"
                        class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
