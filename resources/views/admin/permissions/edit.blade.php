@extends('layouts.app')

@section('title', 'Edit Izin Siswa')
@section('page-title', 'Edit Izin Siswa')

@section('content')
<div class="bg-white rounded-lg shadow p-6">
    <form action="{{ route('admin.permissions.students.update', $permission) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2" for="grade_id">
                    Tingkat Kelas
                </label>
                <select name="grade_id" id="grade_id" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Pilih Tingkat Kelas</option>
                    @foreach($grades ?? [] as $grade)
                        <option value="{{ $grade->id }}" 
                                {{ (string)old('grade_id', $permission->permissible->rombel->grade_id ?? '') === (string)$grade->id ? 'selected' : '' }}>
                            {{ $grade->name }}
                        </option>
                    @endforeach
                </select>
                @error('grade_id')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2" for="rombel_id">
                    Rombel
                </label>
                <select name="rombel_id" id="rombel_id" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Pilih Rombel</option>
                    @foreach($rombels ?? [] as $rombel)
                        <option value="{{ $rombel->id }}" 
                                data-grade="{{ $rombel->grade_id }}" 
                                {{ (string)old('rombel_id', $permission->permissible->rombel_id ?? '') === (string)$rombel->id ? 'selected' : '' }}>
                            {{ $rombel->name }}
                        </option>
                    @endforeach
                </select>
                @error('rombel_id')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2" for="student_id">
                    Siswa
                </label>
                <select name="student_id" id="student_id" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
                        required>
                    <option value="">Pilih Siswa</option>
                    @foreach($students as $student)
                        <option value="{{ $student->id }}" 
                                data-rombel="{{ $student->rombel_id }}" 
                                {{ (string)old('student_id', $permission->permissible_id) === (string)$student->id ? 'selected' : '' }}>
                            {{ $student->full_name }}
                        </option>
                    @endforeach
                </select>
                @error('student_id')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2" for="status">
                    Status
                </label>
                <select name="status" id="status" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
                        required>
                    <option value="pending" {{ (string)old('status', $permission->status) === 'pending' ? 'selected' : '' }}>
                        Menunggu Persetujuan
                    </option>
                    <option value="approved" {{ (string)old('status', $permission->status) === 'approved' ? 'selected' : '' }}>
                        Disetujui
                    </option>
                    <option value="rejected" {{ (string)old('status', $permission->status) === 'rejected' ? 'selected' : '' }}>
                        Ditolak
                    </option>
                </select>
                @error('status')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2" for="start_date">
                    Tanggal Mulai
                </label>
                <input type="date" name="start_date" id="start_date"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                       value="{{ old('start_date', $permission->start_date ? $permission->start_date->format('Y-m-d') : '') }}" required>
                @error('start_date')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2" for="end_date">
                    Tanggal Selesai
                </label>
                <input type="date" name="end_date" id="end_date"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                       value="{{ old('end_date', $permission->end_date ? $permission->end_date->format('Y-m-d') : '') }}" required>
                @error('end_date')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            
            <div class="mb-4 md:col-span-2">
                <label class="block text-gray-700 font-bold mb-2" for="reason">
                    Alasan Izin
                </label>
                <textarea name="reason" id="reason"
                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                          rows="3" required>{{ old('reason', $permission->reason ?? '') }}</textarea>
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
                       accept=".jpg,.jpeg,.png,.pdf">
                @error('supporting_document')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
                <p class="text-gray-500 text-xs mt-1">Format: JPG, JPEG, PNG, PDF. Maksimal 2MB.</p>
                
                @if($permission->supporting_document)
                    <div class="mt-2">
                        <p class="text-gray-700">Dokumen saat ini:</p>
                        <a href="{{ asset('storage/' . $permission->supporting_document) }}" target="_blank" 
                           class="text-blue-600 hover:underline">
                            Lihat Dokumen
                        </a>
                    </div>
                @endif
            </div>
        </div>
        
        <div class="flex justify-end">
            <a href="{{ route('admin.permissions.students') }}" class="mr-2 px-4 py-2 text-gray-600 bg-gray-200 rounded-md">Batal</a>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md">Update</button>
        </div>
    </form>
    
    <!-- Delete Form -->
    <div class="mt-6">
        <form action="{{ route('admin.permissions.students.destroy', $permission) }}" method="POST" class="inline-block">
            @csrf
            @method('DELETE')
            <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-md"
                    onclick="return confirm('Apakah Anda yakin ingin menghapus izin siswa ini?')">
                Hapus
            </button>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const gradeSelect = document.getElementById('grade_id');
    const rombelSelect = document.getElementById('rombel_id');
    const studentSelect = document.getElementById('student_id');

    // Store original options for filtering
    const rombelOptions = Array.from(rombelSelect.options).slice(1); // Exclude first option
    const studentOptions = Array.from(studentSelect.options).slice(1); // Exclude first option

    // Filter rombels based on selected grade
    gradeSelect.addEventListener('change', function() {
        const selectedGradeId = this.value;

        // Reset and repopulate rombel select
        rombelSelect.innerHTML = '<option value="">Pilih Rombel</option>';
        studentSelect.innerHTML = '<option value="">Pilih Siswa</option>';

        if (selectedGradeId) {
            rombelOptions.forEach(function(option) {
                if (option.getAttribute('data-grade') == selectedGradeId) {
                    rombelSelect.appendChild(option.cloneNode(true));
                }
            });
        } else {
            rombelOptions.forEach(function(option) {
                rombelSelect.appendChild(option.cloneNode(true));
            });
        }

        // Reset student dropdown when grade changes
        if (!selectedGradeId) {
            studentOptions.forEach(function(option) {
                studentSelect.appendChild(option.cloneNode(true));
            });
        }
    });

    // Filter students based on selected rombel
    rombelSelect.addEventListener('change', function() {
        const selectedRombelId = this.value;

        // Reset and repopulate student select
        studentSelect.innerHTML = '<option value="">Pilih Siswa</option>';

        if (selectedRombelId) {
            // Only show students from the selected rombel
            studentOptions.forEach(function(option) {
                if (option.getAttribute('data-rombel') == selectedRombelId) {
                    studentSelect.appendChild(option.cloneNode(true));
                }
            });
        } else {
            // If no rombel is selected but grade is selected, show all students from that grade
            const selectedGradeId = gradeSelect.value;
            if (selectedGradeId) {
                // First populate all matching rombels for that grade
                const gradeRombels = [];
                rombelOptions.forEach(function(rombelOption) {
                    if (rombelOption.getAttribute('data-grade') == selectedGradeId) {
                        gradeRombels.push(rombelOption.value);
                    }
                });

                // Now show students from those rombels
                studentOptions.forEach(function(option) {
                    if (gradeRombels.includes(option.getAttribute('data-rombel'))) {
                        studentSelect.appendChild(option.cloneNode(true));
                    }
                });
            } else {
                // If neither grade nor rombel selected, show all students
                studentOptions.forEach(function(option) {
                    studentSelect.appendChild(option.cloneNode(true));
                });
            }
        }
    });

    // Initialize form with selected values for editing
    function initializeForm() {
        // If grade is selected, trigger grade change to populate rombels
        if (gradeSelect.value) {
            // Clone rombel options based on selected grade
            rombelSelect.innerHTML = '<option value="">Pilih Rombel</option>';
            rombelOptions.forEach(function(option) {
                if (option.getAttribute('data-grade') == gradeSelect.value) {
                    rombelSelect.appendChild(option.cloneNode(true));
                }
            });
        }

        // If rombel is selected, trigger rombel change to populate students
        if (rombelSelect.value) {
            // Clone student options based on selected rombel
            studentSelect.innerHTML = '<option value="">Pilih Siswa</option>';
            studentOptions.forEach(function(option) {
                if (option.getAttribute('data-rombel') == rombelSelect.value) {
                    studentSelect.appendChild(option.cloneNode(true));
                }
            });
        }
    }

    // Run initialization to properly load dependent dropdowns
    setTimeout(initializeForm, 100);
});
</script>
@endsection