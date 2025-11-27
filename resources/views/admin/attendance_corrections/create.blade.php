@extends('layouts.app')

@section('title', 'Buat Koreksi Absensi')
@section('page-title', 'Buat Permohonan Koreksi Absensi Baru')

@section('content')
<div class="max-w-4xl mx-auto bg-white rounded-lg shadow p-6 sm:p-8">
    <h2 class="text-xl font-bold mb-6 text-gray-800">{{ __('Buat Permohonan Koreksi Absensi Baru') }}</h2>
    <form action="{{ route('admin.attendance-corrections.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

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
                        <option value="App\Models\Employee" {{ old('correctable_type') == 'App\Models\Employee' ? 'selected' : '' }}>Pegawai</option>
                        <option value="App\Models\Student" {{ old('correctable_type') == 'App\Models\Student' ? 'selected' : '' }}>Siswa</option>
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
                            <option value="{{ $employee->id }}" data-type="App\Models\Employee" {{ old('correctable_id') == $employee->id ? 'selected' : '' }}>
                                {{ $employee->full_name }} (Pegawai)
                            </option>
                        @endforeach
                        @foreach($students as $student)
                            <option value="{{ $student->id }}" data-type="App\Models\Student" {{ old('correctable_id') == $student->id ? 'selected' : '' }}>
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
                           value="{{ old('date') }}" required>
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
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="pending" {{ old('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="approved" {{ old('status') == 'approved' ? 'selected' : '' }}>Disetujui</option>
                        <option value="rejected" {{ old('status') == 'rejected' ? 'selected' : '' }}>Ditolak</option>
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
                           value="{{ old('original_clock_in') }}">
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
                           value="{{ old('corrected_clock_in') }}">
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
                           value="{{ old('original_clock_out') }}">
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
                           value="{{ old('corrected_clock_out') }}">
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
                              required>{{ old('reason') }}</textarea>
                    @error('reason')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4 md:col-span-2">
                    <label class="block text-gray-700 font-bold mb-2" for="supporting_document">
                        Dokumen Pendukung <span class="text-red-500">*</span>
                    </label>
                    <input type="file" name="supporting_document" id="supporting_document"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                           accept=".pdf" required>
                    <p class="text-gray-500 text-xs mt-1">Format: PDF. Ukuran maksimal: 500KB</p>
                    @error('supporting_document')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="flex justify-end mt-6">
                <a href="{{ route('admin.attendance-corrections.index') }}" class="mr-2 px-4 py-2 text-gray-600 bg-gray-200 rounded-md">Batal</a>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md">Simpan</button>
            </div>
        </form>
    </div>
@endsection

<script>
document.addEventListener('DOMContentLoaded', function() {
    const correctableTypeSelect = document.getElementById('correctable_type');
    const correctableIdSelect = document.getElementById('correctable_id');
    const dateInput = document.getElementById('date');
    const originalClockInInput = document.getElementById('original_clock_in');
    const originalClockOutInput = document.getElementById('original_clock_out');
    const allCorrectableOptions = Array.from(correctableIdSelect.options).slice(1); // Exclude "Pilih..."

    function filterCorrectableIds() {
        const selectedType = correctableTypeSelect.value;
        correctableIdSelect.innerHTML = '<option value="">Pilih...</option>'; // Reset

        allCorrectableOptions.forEach(option => {
            if (selectedType === '' || option.dataset.type === selectedType) {
                correctableIdSelect.appendChild(option.cloneNode(true));
            }
        });

        // Try to re-select old value if available
        const oldCorrectableId = "{{ old('correctable_id') }}";
        if (oldCorrectableId) {
            correctableIdSelect.value = oldCorrectableId;
        }
    }

    correctableTypeSelect.addEventListener('change', filterCorrectableIds);

    // Initial filter if old input exists
    if (correctableTypeSelect.value) {
        filterCorrectableIds();
    }

    // Function to fetch attendance data based on selected date and correctable
    function fetchAttendanceData() {
        const dateValue = dateInput.value;
        const correctableType = correctableTypeSelect.value;
        const correctableId = correctableIdSelect.value;

        if (!dateValue || !correctableType || !correctableId) {
            return;
        }

        // Show loading state (don't set invalid time values)
        originalClockInInput.placeholder = 'Memuat...';
        originalClockOutInput.placeholder = 'Memuat...';
        originalClockInInput.value = '';
        originalClockOutInput.value = '';

        // Use fetch to call the AJAX endpoint
        fetch("{{ route('admin.attendance-corrections.get-attendance-data') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                date: dateValue,
                correctable_type: correctableType,
                correctable_id: correctableId
            })
        })
        .then(response => response.json())
        .then(data => {
            // Clear loading state
            originalClockInInput.placeholder = '';
            originalClockOutInput.placeholder = '';

            if (data.error) {
                originalClockInInput.value = '';
                originalClockOutInput.value = '';
            } else {
                // Update the original clock times
                originalClockInInput.value = data.clock_in || '';
                originalClockOutInput.value = data.clock_out || '';
            }
        })
        .catch(error => {
            console.error('Error fetching attendance data:', error);
            // Clear loading state
            originalClockInInput.placeholder = '';
            originalClockOutInput.placeholder = '';
            originalClockInInput.value = '';
            originalClockOutInput.value = '';
        });
    }

    // Add event listeners to date and correctable selection
    dateInput.addEventListener('change', fetchAttendanceData);
    correctableTypeSelect.addEventListener('change', fetchAttendanceData);
    correctableIdSelect.addEventListener('change', fetchAttendanceData);

    // Initialize with current values if they exist
    setTimeout(() => {
        if (dateInput.value && correctableTypeSelect.value && correctableIdSelect.value) {
            fetchAttendanceData();
        }
    }, 100);
});
</script>
