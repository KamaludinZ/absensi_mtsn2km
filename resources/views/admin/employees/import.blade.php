@extends('layouts.app')

@section('title', 'Import Pegawai')
@section('page-title', 'Import Pegawai')

@section('content')
<div class="max-w-2xl mx-auto bg-white rounded-lg shadow p-6">
    <div class="mb-6">
        <a href="{{ route('admin.employees.index') }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
            ← Kembali ke Daftar Pegawai
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
        <h3 class="font-medium text-blue-800 mb-2">Petunjuk Import Pegawai</h3>
        <ul class="text-sm text-blue-700 list-disc pl-5 space-y-1">
            <li>Format file harus berupa Excel (.xlsx, .xls) atau CSV (.csv)</li>
            <li>Ukuran file maksimal 2MB</li>
            <li><strong>Format kolom (urutan harus sesuai):</strong>
                <ol class="list-decimal pl-5 mt-1">
                    <li>Nama Lengkap (wajib)</li>
                    <li>Jabatan</li>
                    <li>Telepon</li>
                    <li>Email</li>
                    <li>Status Aktif (1=Aktif, 0=Tidak Aktif)</li>
                    <li>Username (untuk akun login)</li>
                    <li>Password (untuk akun login)</li>
                </ol>
            </li>
            <li><strong>Username dan Password:</strong> Jika diisi, sistem akan otomatis membuatkan akun login untuk pegawai tersebut dengan role "pegawai"</li>
            <li>Download <a href="{{ route('admin.employees.download-template') }}" class="text-blue-600 hover:underline font-semibold">Template Excel</a> untuk contoh format yang benar</li>
        </ul>
    </div>

    <form action="{{ route('admin.employees.import') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">File Pegawai <span class="text-red-500">*</span></label>
            <input type="file" name="file" accept=".xlsx,.xls,.csv" required
                   class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('file') border-red-500 @enderror">
            @error('file')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
            <p class="text-gray-500 text-xs mt-1">Format: .xlsx, .xls, atau .csv (Maksimal 2MB)</p>
        </div>

        <div class="flex space-x-4">
            <button type="submit" class="flex-1 bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700">Import</button>
            <a href="{{ route('admin.employees.index') }}" class="flex-1 bg-gray-500 text-white py-2 rounded-lg hover:bg-gray-600 text-center">Batal</a>
        </div>
    </form>
</div>
@endsection