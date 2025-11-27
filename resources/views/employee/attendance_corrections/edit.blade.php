@extends('layouts.app')

@section('title', 'Edit Koreksi Absensi')
@section('page-title', 'Edit Permohonan Koreksi Absensi')

@section('content')
<div class="max-w-4xl mx-auto bg-white rounded-lg shadow p-6 sm:p-8">
    <h2 class="text-xl font-bold mb-6 text-gray-800">{{ __('Edit Permohonan Koreksi Absensi') }}</h2>
    <form action="{{ route('employee.attendance-corrections.update', $attendanceCorrection) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Date -->
                <div class="mb-4 md:col-span-2">
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

                <!-- Original Clock In/Out (Pre-filled from attendance) -->
                <div class="mb-4">
                    <label class="block text-gray-700 font-bold mb-2" for="original_clock_in_display">
                        Jam Masuk (Terdata)
                    </label>
                    <input type="text" id="original_clock_in_display"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100"
                           value="{{ $attendance ? ($attendance->clock_in ? \Carbon\Carbon::parse($attendance->clock_in)->format('H:i') : 'Belum Absen Masuk') : 'Tidak Tersedia' }}" readonly>
                    <!-- Hidden field to store actual original for controller logic if needed later -->
                    <input type="hidden" name="original_clock_in" id="original_clock_in_hidden" value="{{ $attendance ? ($attendance->clock_in ? \Carbon\Carbon::parse($attendance->clock_in)->format('H:i') : '') : '' }}">
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 font-bold mb-2" for="corrected_clock_in">
                        Jam Masuk (Koreksi)
                    </label>
                    <input type="time" name="corrected_clock_in" id="corrected_clock_in"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                           value="{{ old('corrected_clock_in', $attendanceCorrection->corrected_clock_in ? \Carbon\Carbon::parse($attendanceCorrection->corrected_clock_in)->format('H:i') : '') }}">
                    @error('corrected_clock_in')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 font-bold mb-2" for="original_clock_out_display">
                        Jam Pulang (Terdata)
                    </label>
                    <input type="text" id="original_clock_out_display"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100"
                           value="{{ $attendance ? ($attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : 'Belum Absen Pulang') : 'Tidak Tersedia' }}" readonly>
                    <!-- Hidden field to store actual original for controller logic if needed later -->
                    <input type="hidden" name="original_clock_out" id="original_clock_out_hidden" value="{{ $attendance ? ($attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : '') : '' }}">
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 font-bold mb-2" for="corrected_clock_out">
                        Jam Pulang (Koreksi)
                    </label>
                    <input type="time" name="corrected_clock_out" id="corrected_clock_out"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                           value="{{ old('corrected_clock_out', $attendanceCorrection->corrected_clock_out ? \Carbon\Carbon::parse($attendanceCorrection->corrected_clock_out)->format('H:i') : '') }}">
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
                    <p class="text-gray-500 text-xs mt-1">Format: PDF. Ukuran maksimal: 500KB</p>
                    @error('supporting_document')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror

                    @if($attendanceCorrection->supporting_document)
                        <div class="mt-2">
                            <p class="text-sm text-gray-600">Dokumen saat ini:
                                <a href="{{ Storage::url($attendanceCorrection->supporting_document) }}"
                                   target="_blank"
                                   class="text-blue-600 hover:text-blue-800">
                                    Lihat Dokumen Pendukung
                                </a>
                            </p>
                            <label class="flex items-center mt-2">
                                <input type="checkbox" name="remove_supporting_document" value="1"
                                       class="rounded">
                                <span class="ml-2 text-sm text-gray-700">Hapus dokumen saat ini</span>
                            </label>
                        </div>
                    @endif
                </div>
            </div>

            <div class="flex justify-end mt-6">
                <a href="{{ route('employee.attendance-corrections.index') }}" class="mr-2 px-4 py-2 text-gray-600 bg-gray-200 rounded-md">Batal</a>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const dateInput = document.getElementById('date');
    const originalClockInDisplay = document.getElementById('original_clock_in_display');
    const originalClockOutDisplay = document.getElementById('original_clock_out_display');
    const originalClockInHidden = document.getElementById('original_clock_in_hidden');
    const originalClockOutHidden = document.getElementById('original_clock_out_hidden');
    const correctedClockInInput = document.getElementById('corrected_clock_in');
    const correctedClockOutInput = document.getElementById('corrected_clock_out');

    // Function to fetch attendance data based on selected date
    function fetchAttendanceData(selectedDate) {
        // Show loading state
        originalClockInDisplay.value = 'Memuat...';
        originalClockOutDisplay.value = 'Memuat...';

        // Use fetch to call the AJAX endpoint
        fetch("{{ route('employee.attendance-corrections.get-attendance-data') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                date: selectedDate
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                originalClockInDisplay.value = data.error;
                originalClockOutDisplay.value = data.error;
                originalClockInHidden.value = '';
                originalClockOutHidden.value = '';
            } else {
                // Update the original clock times display
                const clockInValue = data.clock_in || 'Tidak Tersedia';
                const clockOutValue = data.clock_out || 'Tidak Tersedia';
                
                originalClockInDisplay.value = clockInValue;
                originalClockOutDisplay.value = clockOutValue;
                
                // Update hidden fields for form submission
                originalClockInHidden.value = data.clock_in || '';
                originalClockOutHidden.value = data.clock_out || '';
                
                // Don't clear the corrected times when editing - keep existing values
                // correctedClockInInput.value = '';
                // correctedClockOutInput.value = '';
            }
        })
        .catch(error => {
            console.error('Error fetching attendance data:', error);
            originalClockInDisplay.value = 'Error memuat data';
            originalClockOutDisplay.value = 'Error memuat data';
        });
    }

    // Add event listener to date input
    dateInput.addEventListener('change', function() {
        const selectedDate = this.value;
        if (selectedDate) {
            fetchAttendanceData(selectedDate);
        } else {
            // Reset to defaults if no date selected
            originalClockInDisplay.value = 'Tidak Tersedia';
            originalClockOutDisplay.value = 'Tidak Tersedia';
            originalClockInHidden.value = '';
            originalClockOutHidden.value = '';
        }
    });

    // Initialize with the current date if it's already set
    if (dateInput.value) {
        // Delay the initial fetch to ensure DOM is fully loaded
        setTimeout(() => {
            fetchAttendanceData(dateInput.value);
        }, 100);
    }
});
</script>

@endsection