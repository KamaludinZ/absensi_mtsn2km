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
                        <option value="">Pilih Pegawai/Siswa</option>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->id }}" data-type="employee">{{ $employee->full_name }} ({{ $employee->position }})</option>
                        @endforeach
                        @foreach($students as $student)
                            <option value="{{ $student->id }}" data-type="student">{{ $student->full_name }} ({{ $student->nisn }})</option>
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
                        $employeeShift = \App\Models\EmployeeShift::where('employee_id', old('attendable_id'))
                            ->where('date', old('date'))
                            ->with('schedule')
                            ->first();

                        if ($employeeShift && $employeeShift->schedule) {
                            $schedule = $employeeShift->schedule;
                        } else {
                            $carbonDate = \Carbon\Carbon::parse(old('date'));
                            $dayOfWeek = $carbonDate->dayOfWeek;
                            if ($dayOfWeek == 0) $dayOfWeek = 7;
                            $schedule = \App\Models\Schedule::where('type', 'pegawai')
                                ->where('day_of_week', $dayOfWeek)
                                ->first();
                        }
                    }
                    elseif (old('attendable_type') === 'App\Models\Student' && old('date')) {
                        $carbonDate = \Carbon\Carbon::parse(old('date'));
                        $dayOfWeek = $carbonDate->dayOfWeek;
                        if ($dayOfWeek == 0) $dayOfWeek = 7;
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

                <!-- Absensi Masuk -->
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

                <!-- Absensi Pulang -->
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
                        <optgroup label="Perizinan">
                            <option value="C" {{ old('status_in') == 'C' ? 'selected' : '' }}>Cuti</option>
                            <option value="S" {{ old('status_in') == 'S' ? 'selected' : '' }}>Sakit</option>
                            <option value="DL" {{ old('status_in') == 'DL' ? 'selected' : '' }}>Dinas Luar</option>
                        </optgroup>
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
                        <optgroup label="Perizinan">
                            <option value="C" {{ old('status_out') == 'C' ? 'selected' : '' }}>Cuti</option>
                            <option value="S" {{ old('status_out') == 'S' ? 'selected' : '' }}>Sakit</option>
                            <option value="DL" {{ old('status_out') == 'DL' ? 'selected' : '' }}>Dinas Luar</option>
                        </optgroup>
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
    var dateInput = document.getElementById('date');
    var clockInInput = document.getElementById('clock_in');
    var clockOutInput = document.getElementById('clock_out');
    var attendableTypeSelect = document.getElementById('attendable_type');
    var attendableIdSelect = document.getElementById('attendable_id');
    var scheduleInfoContainer = document.getElementById('schedule-info-container');

    // Hanya biarkan dropdown tidak berubah - user bisa pilih dari semua pegawai dan siswa
    // Tidak perlu dynamic switching yang kompleks

    function updateScheduleInfo() {
        var selectedDate = dateInput.value;
        var attendableId = attendableIdSelect.value;
        var attendableType = attendableTypeSelect.value;

        if (!selectedDate || !attendableId || !attendableType) {
            return;
        }

        scheduleInfoContainer.innerHTML = '<div class="bg-blue-50 p-4 rounded-lg"><h3 class="text-sm font-medium text-gray-700 mb-2">Informasi Jadwal</h3><p class="text-gray-500">Memuat jadwal...</p></div>';

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
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success && data.data) {
                var schedule = data.data;
                scheduleInfoContainer.innerHTML = '<div class="bg-blue-50 p-4 rounded-lg"><h3 class="text-sm font-medium text-gray-700 mb-2">Informasi Jadwal</h3><div class="grid grid-cols-1 md:grid-cols-3 gap-4"><div><label class="block text-xs font-medium text-gray-500">Nama Shift</label><p class="text-gray-900">' + (schedule.shift_name || 'N/A') + '</p></div><div><label class="block text-xs font-medium text-gray-500">Jam Masuk Jadwal</label><p class="text-gray-900">' + (schedule.start_time || 'N/A') + '</p></div><div><label class="block text-xs font-medium text-gray-500">Jam Pulang Jadwal</label><p class="text-gray-900">' + (schedule.end_time || 'N/A') + '</p></div></div></div>';
            } else {
                scheduleInfoContainer.innerHTML = '<div class="bg-blue-50 p-4 rounded-lg"><h3 class="text-sm font-medium text-gray-700 mb-2">Informasi Jadwal</h3><p class="text-gray-500">Tidak ada jadwal untuk tanggal ini</p></div>';
            }
        })
        .catch(function(error) {
            console.error('Error:', error);
        });
    }

    if (dateInput) {
        dateInput.addEventListener('change', updateScheduleInfo);
    }
    if (attendableIdSelect) {
        attendableIdSelect.addEventListener('change', updateScheduleInfo);
    }
    if (attendableTypeSelect) {
        attendableTypeSelect.addEventListener('change', updateScheduleInfo);
    }
});
</script>
@endsection
