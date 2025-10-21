@extends('layouts.app')

@section('title', 'Edit Tingkat Kelas')
@section('page-title', 'Edit Tingkat Kelas')

@section('content')
<div class="max-w-2xl mx-auto bg-white rounded-lg shadow p-6">
    <form action="{{ route('admin.grades.update', $grade) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Nama</label>
            <input type="text" name="name" value="{{ old('name', $grade->name) }}" required class="w-full px-4 py-2 border rounded-lg">
            @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Deskripsi</label>
            <input type="text" name="description" value="{{ old('description', $grade->description) }}" class="w-full px-4 py-2 border rounded-lg">
            @error('description')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
            <select name="is_active" required class="w-full px-4 py-2 border rounded-lg">
                <option value="1" {{ old('is_active', $grade->is_active) ? 'selected' : '' }}>Aktif</option>
                <option value="0" {{ old('is_active', !$grade->is_active) ? 'selected' : '' }}>Tidak Aktif</option>
            </select>
        </div>
        <div class="flex space-x-4">
            <button type="submit" class="flex-1 bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700">Simpan</button>
            <a href="{{ route('admin.grades.index') }}" class="flex-1 bg-gray-500 text-white py-2 rounded-lg hover:bg-gray-600 text-center">Batal</a>
        </div>
    </form>
</div>
@endsection