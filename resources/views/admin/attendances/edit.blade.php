@extends('layouts.app')

@section('title', 'Edit Absensi')
@section('page-title', 'Edit Absensi')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="mb-4">
        @if($attendance->attendable_type === 'App\\Models\\Student')
            <a href="{{ route('admin.reports.students') . $queryParamString }}" class="text-blue-600 hover:text-blue-800">
                &larr; Kembali ke Laporan Siswa
            </a>
        @else
            <a href="{{ route('admin.reports.index') . $queryParamString }}" class="text-blue-600 hover:text-blue-800">
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
            <input type="hidden" name="redirect_params" value="{{ $queryParamString }}">

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

                <!-- Get schedule information -->
                @php
                    $schedule = null;
                    if ($attendance->attendable_type === 'App\Models\Employee') {
                        // Try to get shift from employee_shifts table
                        $employeeShift = \App\Models\EmployeeShift::where('employee_id', $attendance->attendable_id)
                            ->where('date', $attendance->date)
                            ->with('schedule')
                            ->first();

                        if ($employeeShift && $employeeShift->schedule) {
                            $schedule = $employeeShift->schedule;
                        } else {
                            // Try to get schedule by day of week
                            $dayOfWeek = $attendance->date->dayOfWeek;
                            if ($dayOfWeek == 0) $dayOfWeek = 7; // Convert Sunday (0) to 7
                            $schedule = \App\Models\Schedule::where('type', 'pegawai')
                                ->where('day_of_week', $dayOfWeek)
                                ->first();
                        }
                    }
                    // For students, get schedule similarly
                    else {
                        $dayOfWeek = $attendance->date->dayOfWeek;
                        if ($dayOfWeek == 0) $dayOfWeek = 7; // Convert Sunday (0) to 7
                        $schedule = \App\Models\Schedule::where('type', 'siswa')
                            ->where('day_of_week', $dayOfWeek)
                            ->first();
                    }
                @endphp

                <!-- Schedule Information -->
                @if($schedule)
                <div class="md:col-span-2 bg-blue-50 p-4 rounded-lg">
                    <h3 class="text-sm font-medium text-gray-700 mb-2">Informasi Jadwal</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-500">Nama Shift</label>
                            <p class="text-gray-900">{{ $schedule->shift_name ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500">Jam Masuk Jadwal</label>
                            <p class="text-gray-900">{{ $schedule->start_time ? \Carbon\Carbon::parse($schedule->start_time)->format('H:i:s') : 'N/A' }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500">Jam Pulang Jadwal</label>
                            <p class="text-gray-900">{{ $schedule->end_time ? \Carbon\Carbon::parse($schedule->end_time)->format('H:i:s') : 'N/A' }}</p>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Absensi Masuk (Actual Clock In Time) -->
                <div>
                    <label for="clock_in" class="block text-sm font-medium text-gray-700 mb-2">Absensi Masuk</label>
                    <input type="time"
                           id="clock_in"
                           name="clock_in"
                           value="{{ old('clock_in', $attendance->clock_in ? $attendance->clock_in->format('H:i:s') : '') }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           step="1">
                    <p class="text-xs text-gray-500 mt-1">Kosongkan jika tidak ada absensi masuk</p>
                </div>

                <!-- Absensi Pulang (Actual Clock Out Time) -->
                <div>
                    <label for="clock_out" class="block text-sm font-medium text-gray-700 mb-2">Absensi Pulang</label>
                    <input type="time"
                           id="clock_out"
                           name="clock_out"
                           value="{{ old('clock_out', $attendance->clock_out ? $attendance->clock_out->format('H:i:s') : '') }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           step="1">
                    <p class="text-xs text-gray-500 mt-1">Kosongkan jika tidak ada absensi pulang</p>
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
                            <option value="Tepat Waktu" {{ old('status_in', $attendance->status_in) == 'Tepat Waktu' || (empty($attendance->status_in) && $computedStatusIn == 'Tepat Waktu') ? 'selected' : '' }}>Tepat Waktu (Otomatis)</option>
                            <option value="Terlambat" {{ old('status_in', $attendance->status_in) == 'Terlambat' || (empty($attendance->status_in) && $computedStatusIn == 'Terlambat') ? 'selected' : '' }}>Terlambat (Otomatis)</option>
                            <option value="present" {{ old('status_in', $attendance->status_in) == 'present' || (empty($attendance->status_in) && $computedStatusIn == 'Hadir') ? 'selected' : '' }}>Hadir (Otomatis)</option>
                        </optgroup>
                    </select>
                    <div class="text-xs text-gray-500 mt-1">Pilih dari Perizinan untuk mengganti status otomatis</div>
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
                            <option value="Tepat Waktu" {{ old('status_out', $attendance->status_out) == 'Tepat Waktu' || (empty($attendance->status_out) && $computedStatusOut == 'Tepat Waktu') ? 'selected' : '' }}>Tepat Waktu (Otomatis)</option>
                            <option value="Pulang Awal" {{ old('status_out', $attendance->status_out) == 'Pulang Awal' || (empty($attendance->status_out) && $computedStatusOut == 'Pulang Awal') ? 'selected' : '' }}>Pulang Awal (Otomatis)</option>
                            <option value="present" {{ old('status_out', $attendance->status_out) == 'present' || (empty($attendance->status_out) && $computedStatusOut == 'Hadir') ? 'selected' : '' }}>Hadir (Otomatis)</option>
                        </optgroup>
                    </select>
                    <div class="text-xs text-gray-500 mt-1">Pilih dari Perizinan untuk mengganti status otomatis</div>
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
                    <a href="{{ route('admin.reports.students') . $queryParamString }}"
                       class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                        Batal
                    </a>
                @else
                    <a href="{{ route('admin.reports.index') . $queryParamString }}"
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle status selection logic - only Perizinan options should be used for manual override
    const statusInSelect = document.getElementById('status_in');
    const statusOutSelect = document.getElementById('status_out');
    const dateInput = document.getElementById('date');

    // Function to check if a selected option is from the Kehadiran group
    function isKehadiranOption(selectElement) {
        if (!selectElement || selectElement.value === '') return false;

        // Check if the selected value is NOT one of the permission values
        return !['C', 'S', 'DL'].includes(selectElement.value);
    }

    // Add event listener to status dropdowns to inform users about computed vs manual status
    if (statusInSelect) {
        statusInSelect.addEventListener('change', function(e) {
            // Kehadiran options represent computed values and will be recalculated on save
            // if no manual permission is selected
        });
    }

    if (statusOutSelect) {
        statusOutSelect.addEventListener('change', function(e) {
            // Kehadiran options represent computed values and will be recalculated on save
            // if no manual permission is selected
        });
    }

    // Add event listener to date field to update schedule information dynamically
    if (dateInput) {
        dateInput.addEventListener('change', function(e) {
            const selectedDate = e.target.value;
            const attendableId = {{ $attendance->attendable_id }};
            const attendableType = {!! json_encode($attendance->attendable_type) !!};

            // Show loading state
            const scheduleInfoDiv = document.querySelector('.bg-blue-50.p-4.rounded-lg');
            if (scheduleInfoDiv) {
                scheduleInfoDiv.innerHTML = '<h3 class="text-sm font-medium text-gray-700 mb-2">Informasi Jadwal</h3><p class="text-gray-500">Memuat jadwal...</p>';
            }

            // Make AJAX request to get schedule info for the selected date
            fetch('/api/schedule-info', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    date: selectedDate,
                    attendable_id: attendableId,
                    attendable_type: attendableType
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateScheduleInfo(data.data);
                } else {
                    console.error('API Error:', data.message);
                    const scheduleInfoDiv = document.querySelector('.bg-blue-50.p-4.rounded-lg');
                    if (scheduleInfoDiv) {
                        scheduleInfoDiv.innerHTML = '<h3 class="text-sm font-medium text-gray-700 mb-2">Informasi Jadwal</h3><p class="text-red-500">Gagal memuat jadwal: ' + (data.message || 'Unknown error') + '</p>';
                    }
                }
            })
            .catch(error => {
                console.error('Error fetching schedule info:', error);
                // Revert to original schedule info or show error
                const scheduleInfoDiv = document.querySelector('.bg-blue-50.p-4.rounded-lg');
                if (scheduleInfoDiv) {
                    scheduleInfoDiv.innerHTML = '<h3 class="text-sm font-medium text-gray-700 mb-2">Informasi Jadwal</h3><p class="text-red-500">Gagal memuat jadwal</p>';
                }
            });
        });
    }

    // Function to get the current schedule data from the displayed schedule info
    function getCurrentScheduleData() {
        const scheduleInfoDiv = document.querySelector('.bg-blue-50.p-4.rounded-lg');

        if (scheduleInfoDiv) {
            // Get all divs inside the grid container
            const gridDivs = scheduleInfoDiv.querySelectorAll('.grid > div');

            if (gridDivs.length >= 3) {
                // Second div is Jam Masuk Jadwal (index 1)
                // Third div is Jam Pulang Jadwal (index 2)
                const startTimeElement = gridDivs[1].querySelector('p');
                const endTimeElement = gridDivs[2].querySelector('p');

                if (startTimeElement && endTimeElement) {
                    return {
                        start_time: startTimeElement.textContent.trim(),
                        end_time: endTimeElement.textContent.trim()
                    };
                }
            }
        }
        return null;
    }

    // Initialize status fields based on initial schedule info when page loads
    // Use the getCurrentScheduleData function to get initial schedule
    const initialSchedule = getCurrentScheduleData();
    if (initialSchedule && initialSchedule.start_time !== 'N/A') {
        updateStatusFields(initialSchedule);
    }

    // Function to update the schedule information display
    function updateScheduleInfo(schedule) {
        const scheduleInfoDiv = document.querySelector('.bg-blue-50.p-4.rounded-lg');
        if (!scheduleInfoDiv || !schedule) {
            return;
        }

        let scheduleHtml = '<h3 class="text-sm font-medium text-gray-700 mb-2">Informasi Jadwal</h3>';

        if (schedule) {
            scheduleHtml += `
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-500">Nama Shift</label>
                        <p class="text-gray-900">${schedule.shift_name || 'N/A'}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500">Jam Masuk Jadwal</label>
                        <p class="text-gray-900">${schedule.start_time || 'N/A'}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500">Jam Pulang Jadwal</label>
                        <p class="text-gray-900">${schedule.end_time || 'N/A'}</p>
                    </div>
                </div>
            `;
        } else {
            scheduleHtml += '<p class="text-gray-500">Tidak ada jadwal untuk tanggal ini</p>';
        }

        scheduleInfoDiv.innerHTML = scheduleHtml;

        // After updating schedule info, recompute status if clock times exist
        updateStatusFields(schedule);
    }

    // Function to update status fields based on clock times and schedule
    function updateStatusFields(schedule) {
        if (!schedule) return;

        const clockInInput = document.getElementById('clock_in');
        const clockOutInput = document.getElementById('clock_out');
        const statusInSelect = document.getElementById('status_in');
        const statusOutSelect = document.getElementById('status_out');

        // Update Status In based on clock_in and scheduled start time
        if (clockInInput && statusInSelect && schedule.start_time && schedule.start_time !== 'N/A') {
            const clockInValue = clockInInput.value;

            if (clockInValue) {
                // Parse time values (could be HH:MM or HH:MM:SS format)
                const clockInParts = clockInValue.split(':');
                const schedStartParts = schedule.start_time.split(':');

                const clockInHours = parseInt(clockInParts[0], 10);
                const clockInMinutes = parseInt(clockInParts[1], 10);
                const schedStartHours = parseInt(schedStartParts[0], 10);
                const schedStartMinutes = parseInt(schedStartParts[1], 10);

                // Calculate total minutes from midnight for both times
                const clockInTotalMinutes = clockInHours * 60 + clockInMinutes;
                const schedStartTotalMinutes = schedStartHours * 60 + schedStartMinutes;

                // If clock-in is after scheduled start time, it's late (Terlambat), else on time (Tepat Waktu)
                if (clockInTotalMinutes > schedStartTotalMinutes) {
                    // Select "Terlambat" in the status_in select
                    Array.from(statusInSelect.options).forEach(option => {
                        option.selected = (option.value === 'Terlambat');
                    });
                } else {
                    // Select "Tepat Waktu" in the status_in select
                    Array.from(statusInSelect.options).forEach(option => {
                        option.selected = (option.value === 'Tepat Waktu');
                    });
                }
            }
        }

        // Update Status Out based on clock_out and scheduled end time
        if (clockOutInput && statusOutSelect && schedule.end_time && schedule.end_time !== 'N/A') {
            const clockOutValue = clockOutInput.value;

            if (clockOutValue) {
                // Parse time values (could be HH:MM or HH:MM:SS format)
                const clockOutParts = clockOutValue.split(':');
                const schedEndParts = schedule.end_time.split(':');

                const clockOutHours = parseInt(clockOutParts[0], 10);
                const clockOutMinutes = parseInt(clockOutParts[1], 10);
                const schedEndHours = parseInt(schedEndParts[0], 10);
                const schedEndMinutes = parseInt(schedEndParts[1], 10);

                // Calculate total minutes from midnight for both times
                const clockOutTotalMinutes = clockOutHours * 60 + clockOutMinutes;
                const schedEndTotalMinutes = schedEndHours * 60 + schedEndMinutes;

                // If clock-out is before scheduled end time, it's early departure (Pulang Awal), else on time (Tepat Waktu)
                if (clockOutTotalMinutes < schedEndTotalMinutes) {
                    // Select "Pulang Awal" in the status_out select
                    Array.from(statusOutSelect.options).forEach(option => {
                        option.selected = (option.value === 'Pulang Awal');
                    });
                } else {
                    // Select "Tepat Waktu" in the status_out select
                    Array.from(statusOutSelect.options).forEach(option => {
                        option.selected = (option.value === 'Tepat Waktu');
                    });
                }
            }
        }
    }

    // Add event listeners to clock_in and clock_out fields to update status automatically when user finishes editing
    const clockInInput = document.getElementById('clock_in');
    const clockOutInput = document.getElementById('clock_out');

    if (clockInInput) {
        // Use input event to update status immediately as user types or selects time
        clockInInput.addEventListener('input', function(e) {
            const schedule = getCurrentScheduleData();
            if (schedule && schedule.start_time && schedule.start_time !== 'N/A') {
                updateStatusFields(schedule);
            }
        });

        // Use blur event to update status when user finishes editing the field
        clockInInput.addEventListener('blur', function(e) {
            const schedule = getCurrentScheduleData();
            if (schedule && schedule.start_time && schedule.start_time !== 'N/A') {
                updateStatusFields(schedule);
            }
        });

        // Also update on change in case of programmatic changes
        clockInInput.addEventListener('change', function(e) {
            const schedule = getCurrentScheduleData();
            if (schedule && schedule.start_time && schedule.start_time !== 'N/A') {
                updateStatusFields(schedule);
            }
        });
    }

    if (clockOutInput) {
        // Use input event to update status immediately as user types or selects time
        clockOutInput.addEventListener('input', function(e) {
            const schedule = getCurrentScheduleData();
            if (schedule && schedule.end_time && schedule.end_time !== 'N/A') {
                updateStatusFields(schedule);
            }
        });

        // Use blur event to update status when user finishes editing the field
        clockOutInput.addEventListener('blur', function(e) {
            const schedule = getCurrentScheduleData();
            if (schedule && schedule.end_time && schedule.end_time !== 'N/A') {
                updateStatusFields(schedule);
            }
        });

        // Also update on change in case of programmatic changes
        clockOutInput.addEventListener('change', function(e) {
            const schedule = getCurrentScheduleData();
            if (schedule && schedule.end_time && schedule.end_time !== 'N/A') {
                updateStatusFields(schedule);
            }
        });
    }
});
</script>

@endsection
