@extends('layouts.app')

@section('title', 'Edit Koreksi Absensi')
@section('page-title', 'Edit Permohonan Koreksi Absensi')

@section('content')
<div class="max-w-4xl mx-auto bg-white rounded-lg shadow p-6 sm:p-8">
    <h2 class="text-xl font-bold mb-6 text-gray-800">{{ __('Edit Permohonan Koreksi Absensi') }}</h2>
    <form action="{{ route('admin.attendance-corrections.update', $attendanceCorrection) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Correctable Type and ID -->
                <div class="mb-4">
                    <label class="block text-gray-700 font-bold mb-2" for="correctable_type">
                        Tipe (Pegawai/Siswa)
                    </label>
                    <select name="correctable_type" id="correctable_type"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            required>
                        <option value="">Pilih Tipe</option>
                        <option value="App\Models\Employee" {{ old('correctable_type', $attendanceCorrection->correctable_type) == 'App\Models\Employee' ? 'selected' : '' }}>Pegawai</option>
                        <option value="App\Models\Student" {{ old('correctable_type', $attendanceCorrection->correctable_type) == 'App\Models\Student' ? 'selected' : '' }}>Siswa</option>
                    </select>
                    @error('correctable_type')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 font-bold mb-2" for="correctable_id">
                        Pilih Pegawai/Siswa
                    </label>
                    <select name="correctable_id" id="correctable_id"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            required>
                        <option value="">Pilih...</option>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->id }}" data-type="App\Models\Employee" {{ old('correctable_id', $attendanceCorrection->correctable_id) == $employee->id && old('correctable_type', $attendanceCorrection->correctable_type) == 'App\Models\Employee' ? 'selected' : '' }}>
                                {{ $employee->full_name }} (Pegawai)
                            </option>
                        @endforeach
                        @foreach($students as $student)
                            <option value="{{ $student->id }}" data-type="App\Models\Student" {{ old('correctable_id', $attendanceCorrection->correctable_id) == $student->id && old('correctable_type', $attendanceCorrection->correctable_type) == 'App\Models\Student' ? 'selected' : '' }}>
                                {{ $student->full_name }} (Siswa)
                            </option>
                        @endforeach
                    </select>
                    @error('correctable_id')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Date -->
                <div class="mb-4">
                    <label class="block text-gray-700 font-bold mb-2" for="date">
                        Tanggal Absensi
                    </label>
                    <input type="date" name="date" id="date"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                           value="{{ old('date', $attendanceCorrection->date->format('Y-m-d')) }}" required>
                    @error('date')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Status -->
                <div class="mb-4">
                    <label class="block text-gray-700 font-bold mb-2" for="status">
                        Status Permohonan
                    </label>
                    <select name="status" id="status"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            required>
                        <option value="pending" {{ old('status', $attendanceCorrection->status) == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="approved" {{ old('status', $attendanceCorrection->status) == 'approved' ? 'selected' : '' }}>Disetujui</option>
                        <option value="rejected" {{ old('status', $attendanceCorrection->status) == 'rejected' ? 'selected' : '' }}>Ditolak</option>
                    </select>
                    @error('status')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Original and Corrected Clock In/Out -->
                <div class="mb-4">
                    <label class="block text-gray-700 font-bold mb-2" for="original_clock_in">
                        Jam Masuk (Original)
                    </label>
                    <input type="time" name="original_clock_in" id="original_clock_in"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                           value="{{ old('original_clock_in', $attendanceCorrection->original_clock_in ? $attendanceCorrection->original_clock_in->format('H:i') : '') }}">
                    @error('original_clock_in')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 font-bold mb-2" for="corrected_clock_in">
                        Jam Masuk (Koreksi)
                    </label>
                    <input type="time" name="corrected_clock_in" id="corrected_clock_in"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                           value="{{ old('corrected_clock_in', $attendanceCorrection->corrected_clock_in ? $attendanceCorrection->corrected_clock_in->format('H:i') : '') }}">
                    @error('corrected_clock_in')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 font-bold mb-2" for="original_clock_out">
                        Jam Pulang (Original)
                    </label>
                    <input type="time" name="original_clock_out" id="original_clock_out"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                           value="{{ old('original_clock_out', $attendanceCorrection->original_clock_out ? $attendanceCorrection->original_clock_out->format('H:i') : '') }}">
                    @error('original_clock_out')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 font-bold mb-2" for="corrected_clock_out">
                        Jam Pulang (Koreksi)
                    </label>
                    <input type="time" name="corrected_clock_out" id="corrected_clock_out"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                           value="{{ old('corrected_clock_out', $attendanceCorrection->corrected_clock_out ? $attendanceCorrection->corrected_clock_out->format('H:i') : '') }}">
                    @error('corrected_clock_out')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                
                <!-- Reason -->
                <div class="mb-4 md:col-span-2">
                    <label class="block text-gray-700 font-bold mb-2" for="reason">
                        Alasan Permohonan
                    </label>
                    <textarea name="reason" id="reason" rows="3"
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                              required>{{ old('reason', $attendanceCorrection->reason) }}</textarea>
                    @error('reason')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4 md:col-span-2">
                    <label class="block text-gray-700 font-bold mb-2" for="supporting_document">
                        Dokumen Pendukung
                    </label>
                    <input type="file" name="supporting_document" id="supporting_document"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                           accept=".pdf">
                    @error('supporting_document')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-gray-500 text-xs mt-1">Format: PDF. Ukuran maksimal: 500KB</p>
                    <p class="text-gray-500 text-xs mt-1">Kosongkan jika tidak ingin mengganti dokumen</p>

                    @if($attendanceCorrection->supporting_document)
                        <div class="mt-2">
                            <p class="text-gray-700">Dokumen saat ini:</p>
                            <a href="{{ asset('storage/' . $attendanceCorrection->supporting_document) }}" target="_blank"
                               class="text-blue-600 hover:underline">
                                Lihat Dokumen
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            <div class="flex justify-end mt-6">
                <a href="{{ route('admin.attendance-corrections.index') }}" class="mr-2 px-4 py-2 text-gray-600 bg-gray-200 rounded-md">Batal</a>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md">Update</button>
            </div>
        </form>

        <!-- Delete Form -->
        <div class="mt-6">
            <form action="{{ route('admin.attendance-corrections.destroy', $attendanceCorrection) }}" method="POST" class="inline-block">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-md"
                        onclick="return confirm('Apakah Anda yakin ingin menghapus permohonan koreksi ini?')">
                    Hapus
                </button>
            </form>
        </div>
    </div>
@endsection

<script>
document.addEventListener('DOMContentLoaded', function() {
    const correctableTypeSelect = document.getElementById('correctable_type');
    const correctableIdSelect = document.getElementById('correctable_id');
    const allCorrectableOptions = Array.from(correctableIdSelect.options).slice(1); // Exclude "Pilih..."

    function filterCorrectableIds() {
        const selectedType = correctableTypeSelect.value;
        correctableIdSelect.innerHTML = '<option value="">Pilih...</option>'; // Reset

        allCorrectableOptions.forEach(option => {
            if (selectedType === '' || option.dataset.type === selectedType) {
                correctableIdSelect.appendChild(option.cloneNode(true));
            }
        });

        // Re-select the correctable_id if it matches the current attendanceCorrection
        const currentCorrectableId = "{{ $attendanceCorrection->correctable_id }}";
        const currentCorrectableType = "{{ $attendanceCorrection->correctable_type }}";
        
        const optionToSelect = Array.from(correctableIdSelect.options).find(option => 
            option.value === currentCorrectableId && option.dataset.type === currentCorrectableType
        );
        if (optionToSelect) {
            optionToSelect.selected = true;
        }

        // Try to re-select old value if available (e.g., after validation error)
        const oldCorrectableId = "{{ old('correctable_id') }}";
        const oldCorrectableType = "{{ old('correctable_type') }}";
        if (oldCorrectableId && oldCorrectableType && 
            (oldCorrectableId !== currentCorrectableId || oldCorrectableType !== currentCorrectableType)) { // Only if old input differs from current saved
            const oldOptionToSelect = Array.from(correctableIdSelect.options).find(option => 
                option.value === oldCorrectableId && option.dataset.type === oldCorrectableType
            );
            if (oldOptionToSelect) {
                oldOptionToSelect.selected = true;
            }
        }
    }

    correctableTypeSelect.addEventListener('change', filterCorrectableIds);

    // Initial filter on page load
    filterCorrectableIds();
});
</script>