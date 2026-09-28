@extends('layouts.app')

@section('title', 'Tambah Absensi Siswa')
@section('page-title', 'Tambah Absensi Siswa')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="mb-4">
        <a href="{{ route('admin.reports.students.daily') . $queryParamString }}" class="text-blue-600 hover:text-blue-800">
            &larr; Kembali ke Laporan Siswa
        </a>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-2xl font-bold mb-6">Tambah Data Absensi Siswa</h2>

        @php
            /** @var \Illuminate\Support\ViewErrorBag|\Illuminate\Support\MessageBag $errors */
        @endphp
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
            <input type="hidden" name="attendable_type" value="App\Models\Student">
            <input type="hidden" name="redirect_params" value="{{ $queryParamString }}">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Student Select -->
                <div class="md:col-span-2">
                    <label for="attendable_id" class="block text-sm font-medium text-gray-700 mb-2">Siswa <span class="text-red-500">*</span></label>
                    <select id="attendable_id" name="attendable_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                        <option value="">Pilih Siswa</option>
                        @foreach($students as $student)
                            <option value="{{ $student->id }}">{{ $student->full_name }} ({{ $student->nisn }})</option>
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

                <!-- Schedule Information -->
                <div class="md:col-span-2" id="schedule-info-container">
                    <div class="bg-blue-50 p-4 rounded-lg">
                        <h3 class="text-sm font-medium text-gray-700 mb-2">Informasi Jadwal</h3>
                        <p class="text-gray-500">Pilih siswa dan tanggal untuk melihat jadwal</p>
                    </div>
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
                            <option value="C">Cuti</option>
                            <option value="S">Sakit</option>
                            <option value="I">Izin</option>
                        </optgroup>
                        <optgroup label="Kehadiran">
                            <option value="Tepat Waktu">Tepat Waktu</option>
                            <option value="Terlambat">Terlambat</option>
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
                        <optgroup label="Perizinan">
                            <option value="C">Cuti</option>
                            <option value="S">Sakit</option>
                            <option value="I">Izin</option>
                        </optgroup>
                        <optgroup label="Kehadiran">
                            <option value="Tepat Waktu">Tepat Waktu</option>
                            <option value="Pulang Awal">Pulang Awal</option>
                        </optgroup>
                    </select>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-end space-x-3 mt-8">
                <a href="{{ route('admin.reports.students.daily') . $queryParamString }}"
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
    var attendableIdSelect = document.getElementById('attendable_id');
    var scheduleInfoContainer = document.getElementById('schedule-info-container');

    function updateScheduleInfo() {
        var selectedDate = dateInput.value;
        var attendableId = attendableIdSelect.value;

        if (!selectedDate || !attendableId) {
            scheduleInfoContainer.innerHTML = '<div class="bg-blue-50 p-4 rounded-lg"><h3 class="text-sm font-medium text-gray-700 mb-2">Informasi Jadwal</h3><p class="text-gray-500">Pilih siswa dan tanggal untuk melihat jadwal</p></div>';
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
                attendable_type: 'App\\Models\\Student'
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
});
</script>
@endsection
