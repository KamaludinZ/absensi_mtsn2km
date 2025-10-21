@extends('layouts.app')

@section('title', 'Import Hari Libur')
@section('page-title', 'Import Hari Libur')

@section('content')
<div class="bg-white rounded-lg shadow p-6">
    <div class="mb-6">
        <h2 class="text-xl font-bold text-gray-800">Import Hari Libur dari Excel</h2>
        <p class="text-gray-600">Upload file Excel untuk menambahkan data hari libur secara massal.</p>
    </div>
    
    <div class="mb-6 p-4 bg-blue-50 rounded-lg">
        <h3 class="font-semibold text-blue-800 mb-2">Format File:</h3>
        <ul class="list-disc pl-5 text-gray-700 space-y-1">
            <li>Ekstensi file yang didukung: .xlsx, .xls, .csv</li>
            <li>Kolom yang harus ada: <code>name</code> (nama hari libur), <code>date</code> (tanggal dalam format Y-m-d)</li>
            <li>Kolom opsional: <code>description</code> (deskripsi), <code>is_active</code> (1 untuk aktif, 0 untuk tidak aktif)</li>
        </ul>
        
        <div class="mt-4">
            <a href="{{ route('admin.holidays.template') }}" class="inline-flex items-center text-blue-600 hover:text-blue-800">
                <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                </svg>
                Unduh Template Excel
            </a>
        </div>
    </div>
    
    <form action="{{ route('admin.holidays.import') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="mb-6">
            <label class="block text-gray-700 font-bold mb-2" for="file">
                Pilih File Excel
            </label>
            <input type="file" name="file" id="file" 
                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                   accept=".xlsx,.xls,.csv" required>
            @error('file')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>
        
        <div class="flex justify-end">
            <a href="{{ route('admin.holidays.index') }}" class="mr-2 px-4 py-2 text-gray-600 bg-gray-200 rounded-md">Batal</a>
            <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                Import Hari Libur
            </button>
        </div>
    </form>
</div>
@endsection