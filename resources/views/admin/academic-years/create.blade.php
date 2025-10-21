@extends('layouts.app')

@section('title', 'Tambah Tahun Pelajaran')
@section('page-title', 'Tambah Tahun Pelajaran')

@section('content')
<div class="bg-white rounded-lg shadow p-6">
    <form action="{{ route('admin.academic-years.store') }}" method="POST">
        @csrf
        <div class="mb-4">
            <label class="block text-gray-700 font-bold mb-2" for="year_name">
                Tahun Pelajaran
            </label>
            <input type="text" name="year_name" id="year_name" 
                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
                   value="{{ old('year_name') }}" required>
            @error('year_name')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>
        
        <div class="mb-4">
            <label class="block text-gray-700 font-bold mb-2" for="status">
                Status
            </label>
            <select name="status" id="status" 
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Aktif</option>
                <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Tidak Aktif</option>
            </select>
            @error('status')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>
        
        <div class="flex justify-end">
            <a href="{{ route('admin.academic-years.index') }}" class="mr-2 px-4 py-2 text-gray-600 bg-gray-200 rounded-md">Batal</a>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md">Simpan</button>
        </div>
    </form>
</div>
@endsection