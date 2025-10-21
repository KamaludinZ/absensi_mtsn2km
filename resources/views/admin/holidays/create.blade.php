@extends('layouts.app')

@section('title', 'Tambah Hari Libur')
@section('page-title', 'Tambah Hari Libur')

@section('content')
<div class="bg-white rounded-lg shadow p-6">
    <form action="{{ route('admin.holidays.store') }}" method="POST">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2" for="name">
                    Nama Hari Libur
                </label>
                <input type="text" name="name" id="name" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
                       value="{{ old('name') }}" required>
                @error('name')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2" for="date">
                    Tanggal
                </label>
                <input type="date" name="date" id="date" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
                       value="{{ old('date') }}" required>
                @error('date')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            
            <div class="mb-4 md:col-span-2">
                <label class="block text-gray-700 font-bold mb-2" for="description">
                    Deskripsi
                </label>
                <textarea name="description" id="description" 
                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                          rows="3">{{ old('description') }}</textarea>
                @error('description')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            
            <div class="mb-4 md:col-span-2">
                <label class="flex items-center">
                    <input type="checkbox" name="is_active" id="is_active" 
                           class="form-checkbox h-4 w-4 text-blue-600" {{ old('is_active', true) ? 'checked' : '' }}>
                    <span class="ml-2 text-gray-700">Hari libur aktif</span>
                </label>
            </div>
        </div>
        
        <div class="flex justify-end">
            <a href="{{ route('admin.holidays.index') }}" class="mr-2 px-4 py-2 text-gray-600 bg-gray-200 rounded-md">Batal</a>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md">Simpan</button>
        </div>
    </form>
</div>
@endsection