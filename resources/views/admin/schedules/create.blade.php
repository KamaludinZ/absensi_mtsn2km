@extends('layouts.app')

@section('title', 'Tambah Jadwal')
@section('page-title', 'Tambah Jadwal')

@section('content')
<div class="bg-white rounded-lg shadow p-6">
    <form action="{{ route('admin.schedules.custom.store') }}" method="POST" id="scheduleForm">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <input type="hidden" name="type" value="pegawai">

            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2" for="shift_type">
                    Jenis Shift
                </label>
                <select name="shift_type" id="shift_type" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Pilih Jenis Shift (Opsional)</option>
                    <option value="pagi" {{ old('shift_type') == 'pagi' ? 'selected' : '' }}>Pagi</option>
                    <option value="siang" {{ old('shift_type') == 'siang' ? 'selected' : '' }}>Siang</option>
                    <option value="sore" {{ old('shift_type') == 'sore' ? 'selected' : '' }}>Sore</option>
                    <option value="malam" {{ old('shift_type') == 'malam' ? 'selected' : '' }}>Malam</option>
                </select>
                <p class="text-gray-500 text-xs mt-1">Pilih jenis shift (opsional)</p>
                @error('shift_type')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2" for="shift_name">
                    Nama Shift
                </label>
                <input type="text" name="shift_name" id="shift_name" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
                       value="{{ old('shift_name') }}" placeholder="Contoh: Shift Pagi A, Shift Siang B">
                <p class="text-gray-500 text-xs mt-1">Nama spesifik untuk shift ini (opsional)</p>
                @error('shift_name')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            

            
            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2" for="start_time">
                    Jam Masuk
                </label>
                <input type="time" name="start_time" id="start_time" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
                       value="{{ old('start_time') }}" required>
                @error('start_time')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2" for="end_time">
                    Jam Pulang
                </label>
                <input type="time" name="end_time" id="end_time" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
                       value="{{ old('end_time') }}" required>
                <p class="text-gray-500 text-xs mt-1" id="nightShiftNote" style="display: none; color: #3b82f6;">
                    Catatan: Untuk shift malam, jam pulang dapat lebih kecil dari jam masuk (berarti shift berlanjut ke hari berikutnya)
                </p>
                @error('end_time')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>
        
        <div class="flex justify-end">
            <a href="{{ route('admin.schedules.custom.index') }}" class="mr-2 px-4 py-2 text-gray-600 bg-gray-200 rounded-md">Batal</a>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md">Simpan</button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const shiftTypeSelect = document.getElementById('shift_type');
    const endTimeInput = document.getElementById('end_time');
    const nightShiftNote = document.getElementById('nightShiftNote');
    const startTimeInput = document.getElementById('start_time');
    const form = document.getElementById('scheduleForm');
    
    // Show/hide night shift note based on shift type
    function toggleNightShiftNote() {
        if (shiftTypeSelect.value === 'malam') {
            nightShiftNote.style.display = 'block';
        } else {
            nightShiftNote.style.display = 'none';
        }
    }
    
    // Add event listener to shift type select
    shiftTypeSelect.addEventListener('change', toggleNightShiftNote);
    
    // Initialize on page load
    toggleNightShiftNote();
    
    // Form submission handler
    form.addEventListener('submit', function(e) {
        const shiftType = shiftTypeSelect.value;
        const startTime = startTimeInput.value;
        const endTime = endTimeInput.value;
        
        // For night shifts, allow end time to be less than start time
        if (shiftType === 'malam' && endTime && startTime && endTime < startTime) {
            // This is a valid night shift, allow submission
            return true;
        }
        
        // For non-night shifts, validate normally
        if (shiftType !== 'malam' && endTime && startTime && endTime <= startTime) {
            e.preventDefault();
            alert('Untuk shift non-malam, jam selesai harus setelah jam mulai.');
            return false;
        }
    });
});
</script>
@endsection