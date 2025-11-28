@extends('layouts.app')

@section('title', 'Tambah Absensi')
@section('page-title', 'Tambah Absensi')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="mb-4">
        <a href="{{ route('admin.reports.index') . $queryParamString }}" class="text-blue-600 hover:text-blue-800">
            &larr; Kembali ke Laporan Pegawai
        </a>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-2xl font-bold mb-6">Tambah Data Absensi</h2>

        @php
            /** @var \Illuminate\Support\ViewErrorBag|\Illuminate\Support\MessageBag $errors */
        @endphp
        @if($errors->any()) {{-- @phpstan-ignore-line --}}
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error) {{-- @phpstan-ignore-line --}}
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form action="{{ route('admin.attendances.store') }}" method="POST">
            @csrf
            <input type="hidden" name="redirect_params" value="{{ $queryParamString }}">

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
                <div class="md:col-span-2">
                    <label for="date" class="block text-sm font-medium text-gray-700 mb-2">Tanggal <span class="text-red-500">*</span></label>
                    <input type="date"
                           id="date"
                           name="date"
                           value="{{ old('date', date('Y-m-d')) }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           required>
                </div>

                <!-- Get schedule information -->
                @php
                    $schedule = null;
                    if (old('attendable_type') === 'App\Models\Employee' && old('date')) {
                        // Try to get shift from employee_shifts table
                        $employeeShift = \App\Models\EmployeeShift::where('employee_id', old('attendable_id'))
                            ->where('date', old('date'))
                            ->with('schedule')
                            ->first();

                        if ($employeeShift && $employeeShift->schedule) {
                            $schedule = $employeeShift->schedule;
                        } else {
                            // Try to get schedule by day of week
                            $carbonDate = \Carbon\Carbon::parse(old('date'));
                            $dayOfWeek = $carbonDate->dayOfWeek;
                            if ($dayOfWeek == 0) $dayOfWeek = 7; // Convert Sunday (0) to 7
                            $schedule = \App\Models\Schedule::where('type', 'pegawai')
                                ->where('day_of_week', $dayOfWeek)
                                ->first();
                        }
                    }
                    // For students, get schedule similarly
                    elseif (old('attendable_type') === 'App\Models\Student' && old('date')) {
                        $carbonDate = \Carbon\Carbon::parse(old('date'));
                        $dayOfWeek = $carbonDate->dayOfWeek;
                        if ($dayOfWeek == 0) $dayOfWeek = 7; // Convert Sunday (0) to 7
                        $schedule = \App\Models\Schedule::where('type', 'siswa')
                            ->where('day_of_week', $dayOfWeek)
                            ->first();
                    }
                @endphp

                <!-- Schedule Information -->
                <div class="md:col-span-2" id="schedule-info-container">
                    @if($schedule)
                    <div class="bg-blue-50 p-4 rounded-lg">
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
                    @else
                    <div class="bg-blue-50 p-4 rounded-lg">
                        <h3 class="text-sm font-medium text-gray-700 mb-2">Informasi Jadwal</h3>
                        <p class="text-gray-500">Tidak ada jadwal untuk tanggal ini (pilih tanggal dan pegawai/siswa terlebih dahulu)</p>
                    </div>
                    @endif
                </div>

                <!-- Absensi Masuk (Actual Clock In Time) -->
                <div>
                    <label for="clock_in" class="block text-sm font-medium text-gray-700 mb-2">Absensi Masuk</label>
                    <input type="time"
                           id="clock_in"
                           name="clock_in"
                           value="{{ old('clock_in') }}"
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
                           value="{{ old('clock_out') }}"
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
                            <option value="C" {{ old('status_in') == 'C' ? 'selected' : '' }}>Cuti</option>
                            <option value="S" {{ old('status_in') == 'S' ? 'selected' : '' }}>Sakit</option>
                            <option value="DL" {{ old('status_in') == 'DL' ? 'selected' : '' }}>Dinas Luar</option>
                        </optgroup>
                        <!-- Status Kehadiran -->
                        <optgroup label="Kehadiran">
                            <option value="Tepat Waktu" {{ old('status_in') == 'Tepat Waktu' ? 'selected' : '' }}>Tepat Waktu (Otomatis)</option>
                            <option value="Terlambat" {{ old('status_in') == 'Terlambat' ? 'selected' : '' }}>Terlambat (Otomatis)</option>
                            <option value="present" {{ old('status_in') == 'present' ? 'selected' : '' }}>Hadir (Otomatis)</option>
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
                            <option value="C" {{ old('status_out') == 'C' ? 'selected' : '' }}>Cuti</option>
                            <option value="S" {{ old('status_out') == 'S' ? 'selected' : '' }}>Sakit</option>
                            <option value="DL" {{ old('status_out') == 'DL' ? 'selected' : '' }}>Dinas Luar</option>
                        </optgroup>
                        <!-- Status Kehadiran -->
                        <optgroup label="Kehadiran">
                            <option value="Tepat Waktu" {{ old('status_out') == 'Tepat Waktu' ? 'selected' : '' }}>Tepat Waktu (Otomatis)</option>
                            <option value="Pulang Awal" {{ old('status_out') == 'Pulang Awal' ? 'selected' : '' }}>Pulang Awal (Otomatis)</option>
                            <option value="present" {{ old('status_out') == 'present' ? 'selected' : '' }}>Hadir (Otomatis)</option>
                        </optgroup>
                    </select>
                    <div class="text-xs text-gray-500 mt-1">Pilih dari Perizinan untuk mengganti status otomatis</div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-end space-x-3 mt-8">
                <a href="{{ route('admin.reports.index') . $queryParamString }}"
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
    const dateInput = document.getElementById('date');
    const clockInInput = document.getElementById('clock_in');
    const clockOutInput = document.getElementById('clock_out');
    const attendableSelect = document.getElementById('attendable_id');
    const scheduleInfoContainer = document.getElementById('schedule-info-container');

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

        // Update schedule info when attendable type changes
        updateScheduleInfo();
    });

    // Function to update schedule information dynamically
    function updateScheduleInfo() {
        // Get current attendable select element (it may have been replaced)
        const currentAttendableSelect = document.getElementById('attendable_id');

        if (!dateInput || !currentAttendableSelect || !attendableTypeSelect) return;

        const selectedDate = dateInput.value;
        const attendableId = currentAttendableSelect.value;
        const attendableType = attendableTypeSelect.value;

        if (!selectedDate || !attendableId || !attendableType) {
            // Show default message
            scheduleInfoContainer.innerHTML = `
                <div class="bg-blue-50 p-4 rounded-lg">
                    <h3 class="text-sm font-medium text-gray-700 mb-2">Informasi Jadwal</h3>
                    <p class="text-gray-500">Tidak ada jadwal untuk tanggal ini (pilih tanggal dan pegawai/siswa terlebih dahulu)</p>
                </div>
            `;
            return;
        }

        // Show loading state
        scheduleInfoContainer.innerHTML = `
            <div class="bg-blue-50 p-4 rounded-lg">
                <h3 class="text-sm font-medium text-gray-700 mb-2">Informasi Jadwal</h3>
                <p class="text-gray-500">Memuat jadwal...</p>
            </div>
        `;

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
                displayScheduleInfo(data.data);
            } else {
                console.error('API Error:', data.message);
                scheduleInfoContainer.innerHTML = `
                    <div class="bg-blue-50 p-4 rounded-lg">
                        <h3 class="text-sm font-medium text-gray-700 mb-2">Informasi Jadwal</h3>
                        <p class="text-red-500">Gagal memuat jadwal: ${data.message || 'Unknown error'}</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error fetching schedule info:', error);
            scheduleInfoContainer.innerHTML = `
                <div class="bg-blue-50 p-4 rounded-lg">
                    <h3 class="text-sm font-medium text-gray-700 mb-2">Informasi Jadwal</h3>
                    <p class="text-red-500">Gagal memuat jadwal</p>
                </div>
            `;
        });
    }

    // Function to display schedule information
    function displayScheduleInfo(schedule) {
        if (!scheduleInfoContainer) return;

        if (schedule) {
            scheduleInfoContainer.innerHTML = `
                <div class="bg-blue-50 p-4 rounded-lg">
                    <h3 class="text-sm font-medium text-gray-700 mb-2">Informasi Jadwal</h3>
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
                </div>
            `;
        } else {
            scheduleInfoContainer.innerHTML = `
                <div class="bg-blue-50 p-4 rounded-lg">
                    <h3 class="text-sm font-medium text-gray-700 mb-2">Informasi Jadwal</h3>
                    <p class="text-gray-500">Tidak ada jadwal untuk tanggal ini</p>
                </div>
            `;
        }

        // Update status fields based on the new schedule
        updateStatusFields(schedule);
    }

    // Function to get the current schedule data from the displayed schedule info
    function getCurrentScheduleData() {
        const scheduleInfoDiv = document.querySelector('#schedule-info-container .bg-blue-50.p-4.rounded-lg');
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

    // Function to update status fields based on clock times and schedule
    function updateStatusFields(schedule) {
        if (!schedule) return;

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

    // Add event listener to date field to update schedule information dynamically
    if (dateInput) {
        dateInput.addEventListener('change', function(e) {
            updateScheduleInfo();
        });
    }

    // Add event listener to attendable field to update schedule information when attendable changes
    // Use event delegation on the container since the select element may be replaced
    if (attendableSelectContainer) {
        attendableSelectContainer.addEventListener('change', function(e) {
            if (e.target.id === 'attendable_id') {
                updateScheduleInfo();
            }
        });
    }

    // Add event listeners to clock_in and clock_out fields to update status automatically when user finishes editing
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

    // Initialize schedule info if date and attendable are set
    updateScheduleInfo();
});
</script>
@endsection