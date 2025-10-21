@extends('layouts.app')

@section('title', 'Import Rombel')
@section('page-title', 'Import Rombel')

@section('content')
<div class="max-w-2xl mx-auto bg-white rounded-lg shadow p-6">
    <div class="mb-6">
        <a href="{{ route('admin.rombels.index') }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
            ← Kembali ke Daftar Rombel
        </a>
    </div>

    @if(session('error'))
        <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
            <p class="text-red-800">{{ session('error') }}</p>
        </div>
    @endif

    @if(session('success'))
        <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
            <p class="text-green-800">{{ session('success') }}</p>
        </div>
    @endif

    <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
        <h3 class="font-medium text-blue-800 mb-2">Petunjuk Import Rombel</h3>
        <ul class="text-sm text-blue-700 list-disc pl-5 space-y-1">
            <li>Format file harus berupa Excel (.xlsx, .xls) atau CSV (.csv)</li>
            <li>Ukuran file maksimal 2MB</li>
            <li>Format kolom: Nama Rombel (kolom A)</li>
            <li>Contoh format: 7A, 7B, 8A, dst</li>
            <li>Download <a href="{{ route('admin.rombels.download-template') }}" class="text-blue-600 hover:underline font-semibold">template</a> untuk contoh format</li>
        </ul>
    </div>

    <form action="{{ route('admin.rombels.import') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Tahun Pelajaran</label>
            <select name="academic_year_id" required class="w-full px-4 py-2 border rounded-lg">
                <option value="">Pilih Tahun Pelajaran</option>
                @foreach($academicYears as $academicYear)
                <option value="{{ $academicYear->id }}" {{ old('academic_year_id') == $academicYear->id ? 'selected' : '' }}>
                    {{ $academicYear->year_name }}
                </option>
                @endforeach
            </select>
            @error('academic_year_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>
        
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Tingkat Kelas</label>
            <select name="grade_id" required class="w-full px-4 py-2 border rounded-lg">
                <option value="">Pilih Tingkat Kelas</option>
                @foreach($grades as $grade)
                <option value="{{ $grade->id }}" {{ old('grade_id') == $grade->id ? 'selected' : '' }}>
                    {{ $grade->name }} - {{ $grade->description }}
                </option>
                @endforeach
            </select>
            @error('grade_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">File Rombel <span class="text-red-500">*</span></label>
            <input type="file" name="file" accept=".xlsx,.xls,.csv" required
                   class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('file') border-red-500 @enderror">
            @error('file')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
            <p class="text-gray-500 text-xs mt-1">Format: .xlsx, .xls, atau .csv (Maksimal 2MB)</p>
        </div>

        <div class="flex space-x-4">
            <button type="submit" class="flex-1 bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700">Import</button>
            <a href="{{ route('admin.rombels.index') }}" class="flex-1 bg-gray-500 text-white py-2 rounded-lg hover:bg-gray-600 text-center">Batal</a>
        </div>
    </form>
</div>
@endsection