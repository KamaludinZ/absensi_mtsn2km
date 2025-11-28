@extends('layouts.app')

@section('title', 'Tambah Absensi')
@section('page-title', 'Tambah Absensi')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="mb-4">
        <a href="{{ route('admin.reports.index') }}" class="text-blue-600 hover:text-blue-800">
            &larr; Kembali ke Laporan Pegawai
        </a>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-2xl font-bold mb-6">Tambah Data Absensi</h2>

        @if($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form action="{{ route('admin.attendances.store') }}" method="POST">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Attendable Type -->
                <div>
                    <label for="attendable_type" class="block text-sm font-medium text-gray-700 mb-2">Tipe <span class="text-red-500">*</span></label>
                    <select id="attendable_type" name="attendable_type" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                        <option value="">Pilih Tipe</option>
                        <option value="App\Models\Employee">Pegawai</option>
                        <option value="App\Models\Student">Siswa</option>
                    </select>
                </div>

                <!-- Attendable ID (Employee or Student) -->
                <div id="attendable-select-container">
                    <label for="attendable_id" class="block text-sm font-medium text-gray-700 mb-2">Nama <span class="text-red-500">*</span></label>
                    <select id="attendable_id" name="attendable_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                        <option value="">Pilih Pegawai</option>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->id }}">{{ $employee->full_name }} ({{ $employee->position }})</option>
                        @endforeach
                    </select>
                </div>

                <!-- Date -->
                <div>
                    <label for="date" class="block text-sm font-medium text-gray-700 mb-2">Tanggal <span class="text-red-500">*</span></label>
                    <input type="date" 
                           id="date" 
                           name="date" 
                           value="{{ old('date', date('Y-m-d')) }}" 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                           required>
                </div>

                <!-- Clock In Time -->
                <div>
                    <label for="clock_in" class="block text-sm font-medium text-gray-700 mb-2">Jam Masuk</label>
                    <input type="time" 
                           id="clock_in" 
                           name="clock_in" 
                           value="{{ old('clock_in') }}" 
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
                           value="{{ old('clock_out') }}" 
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
                            <option value="C" {{ old('status_in') == 'C' ? 'selected' : '' }}>Cuti</option>
                            <option value="S" {{ old('status_in') == 'S' ? 'selected' : '' }}>Sakit</option>
                            <option value="DL" {{ old('status_in') == 'DL' ? 'selected' : '' }}>Dinas Luar</option>
                        </optgroup>
                        <!-- Status Kehadiran -->
                        <optgroup label="Kehadiran">
                            <option value="Tepat Waktu" {{ old('status_in') == 'Tepat Waktu' ? 'selected' : '' }}>Tepat Waktu</option>
                            <option value="Terlambat" {{ old('status_in') == 'Terlambat' ? 'selected' : '' }}>Terlambat</option>
                            <option value="present" {{ old('status_in') == 'present' ? 'selected' : '' }}>Hadir</option>
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
                            <option value="C" {{ old('status_out') == 'C' ? 'selected' : '' }}>Cuti</option>
                            <option value="S" {{ old('status_out') == 'S' ? 'selected' : '' }}>Sakit</option>
                            <option value="DL" {{ old('status_out') == 'DL' ? 'selected' : '' }}>Dinas Luar</option>
                        </optgroup>
                        <!-- Status Kehadiran -->
                        <optgroup label="Kehadiran">
                            <option value="Tepat Waktu" {{ old('status_out') == 'Tepat Waktu' ? 'selected' : '' }}>Tepat Waktu</option>
                            <option value="Pulang Awal" {{ old('status_out') == 'Pulang Awal' ? 'selected' : '' }}>Pulang Awal</option>
                            <option value="present" {{ old('status_out') == 'present' ? 'selected' : '' }}>Hadir</option>
                        </optgroup>
                    </select>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-end space-x-3 mt-8">
                <a href="{{ route('admin.reports.index') }}" 
                   class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                    Batal
                </a>
                <button type="submit" 
                        class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    Simpan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const attendableTypeSelect = document.getElementById('attendable_type');
    const attendableSelectContainer = document.getElementById('attendable-select-container');

    // Store the original HTML for employee dropdown
    const employeeDropdownHTML = attendableSelectContainer.innerHTML;

    // Create student dropdown options (we'll populate these via JS)
    const studentDropdownHTML = `
        <label for="attendable_id" class="block text-sm font-medium text-gray-700 mb-2">Nama <span class="text-red-500">*</span></label>
        <select id="attendable_id" name="attendable_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
            <option value="">Pilih Siswa</option>
            @foreach($students as $student)
                <option value="{{ $student->id }}">{{ $student->full_name }} ({{ $student->nisn }})</option>
            @endforeach
        </select>
    `;

    attendableTypeSelect.addEventListener('change', function() {
        if (this.value === 'App\\Models\\Employee') {
            attendableSelectContainer.innerHTML = employeeDropdownHTML;
        } else if (this.value === 'App\\Models\\Student') {
            attendableSelectContainer.innerHTML = studentDropdownHTML;
        }
    });
});
</script>
@endsection