@extends('layouts.app')
@section('title', 'Tambah Siswa')
@section('page-title', 'Tambah Siswa')
@section('content')
<div class="max-w-2xl mx-auto bg-white rounded-lg shadow p-6">
    <form action="{{ route('admin.students.store') }}" method="POST">
        @csrf
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Tingkat Kelas</label>
            <select name="grade_id" id="grade-select" class="w-full px-4 py-2 border rounded-lg">
                <option value="">Pilih Tingkat Kelas</option>
                @foreach($grades as $grade)
                <option value="{{ $grade->id }}">{{ $grade->name }} - {{ $grade->description }}</option>
                @endforeach
            </select>
            @error('grade_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Nama Lengkap</label>
            <input type="text" name="full_name" value="{{ old('full_name') }}" required class="w-full px-4 py-2 border rounded-lg">
            @error('full_name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Rombel</label>
            <select name="rombel_id" id="rombel-select" required class="w-full px-4 py-2 border rounded-lg">
                <option value="">Pilih Rombel</option>
                @foreach($rombels as $rombel)
                <option value="{{ $rombel->id }}">{{ $rombel->name }}</option>
                @endforeach
            </select>
            @error('rombel_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
            <select name="is_active" required class="w-full px-4 py-2 border rounded-lg">
                <option value="1">Aktif</option>
                <option value="0">Tidak Aktif</option>
            </select>
        </div>
        <div class="flex space-x-4">
            <button type="submit" class="flex-1 bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700">Simpan</button>
            <a href="{{ route('admin.students.index') }}" class="flex-1 bg-gray-500 text-white py-2 rounded-lg hover:bg-gray-600 text-center">Batal</a>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const gradeSelect = document.getElementById('grade-select');
    const rombelSelect = document.getElementById('rombel-select');
    
    gradeSelect.addEventListener('change', function() {
        const gradeId = this.value;
        
        if (!gradeId) {
            // Reset rombel dropdown if no grade is selected
            rombelSelect.innerHTML = '<option value="">Pilih Rombel</option>';
            return;
        }
        
        // Show loading state
        rombelSelect.innerHTML = '<option value="">Memuat rombel...</option>';
        rombelSelect.disabled = true;
        
        // Fetch rombels for selected grade with proper headers using named route
        const url = "{{ route('admin.rombels.getByGrade', ':gradeId') }}".replace(':gradeId', gradeId);
        fetch(url, {
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
            }
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                rombelSelect.innerHTML = '<option value="">Pilih Rombel</option>';
                
                // Check if data is correct format
                if (Array.isArray(data)) {
                    data.forEach(rombel => {
                        const option = document.createElement('option');
                        option.value = rombel.id;
                        option.textContent = rombel.name;
                        rombelSelect.appendChild(option);
                    });
                } else {
                    console.error('Unexpected data format:', data);
                    rombelSelect.innerHTML = '<option value="">Tidak ada rombel ditemukan</option>';
                }
                
                rombelSelect.disabled = false;
            })
            .catch(error => {
                console.error('Error fetching rombels:', error);
                rombelSelect.innerHTML = '<option value="">Gagal memuat rombel</option>';
                rombelSelect.disabled = false;
            });
    });
});
</script>
@endpush
@endsection