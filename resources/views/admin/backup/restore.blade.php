@extends('layouts.app')

@section('title', 'Restore Database & Files')
@section('page-title', 'Restore Database & Files')

@section('content')
<!-- Upload Backup File Section -->
<div class="bg-white rounded-lg shadow p-6 mb-6">
    <div class="mb-6">
        <h2 class="text-xl font-bold text-gray-800 mb-2 flex items-center">
            <svg class="w-6 h-6 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
            </svg>
            Upload File Backup
        </h2>
        <p class="text-gray-600 text-sm">Upload file backup (.zip) dari komputer Anda untuk direstore.</p>
    </div>

    <form action="{{ route('admin.backup.upload') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="mb-4">
            <label class="block text-gray-700 font-medium mb-2" for="backup_file">
                Pilih File Backup (ZIP)
            </label>
            <input type="file"
                   name="backup_file"
                   id="backup_file"
                   accept=".zip"
                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                   required>
            <p class="text-gray-500 text-xs mt-1">Maksimal ukuran file: 500MB. Format: ZIP</p>
            @error('backup_file')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex justify-end">
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 inline-flex items-center">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                </svg>
                Upload Backup
            </button>
        </div>
    </form>
</div>

<!-- Restore from Existing Backup Section -->
<div class="bg-white rounded-lg shadow p-6">
    <div class="mb-6">
        <h2 class="text-xl font-bold text-gray-800 mb-2 flex items-center">
            <svg class="w-6 h-6 mr-2 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            Restore dari Backup yang Ada
        </h2>
        <p class="text-gray-600 text-sm">Pilih file backup untuk mengembalikan database dan files. <strong class="text-red-600">Peringatan: Proses ini akan menggantikan data saat ini.</strong></p>
    </div>

    <form action="{{ route('admin.backup.restore.execute') }}" method="POST" onsubmit="return confirm('Anda yakin ingin merestore? Proses ini akan menggantikan semua data saat ini (Database, Foto, Settings)!')">
        @csrf
        <div class="mb-4">
            <label class="block text-gray-700 font-medium mb-2" for="filename">
                Pilih File Backup
            </label>
            <select name="filename" id="filename"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                <option value="">-- Pilih File Backup --</option>
                @forelse($backups as $backup)
                    <option value="{{ $backup['name'] }}">
                        {{ $backup['name'] }}
                        ({{ date('d/m/Y H:i', $backup['date']) }} - {{ number_format($backup['size'] / 1024 / 1024, 2) }} MB)
                    </option>
                @empty
                    <option value="" disabled>Belum ada file backup</option>
                @endforelse
            </select>
            @error('filename')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-6 p-4 bg-yellow-50 border border-yellow-200 rounded-md">
            <div class="flex items-start">
                <svg class="w-5 h-5 text-yellow-600 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                <div>
                    <h4 class="font-medium text-yellow-800 mb-1">Yang akan di-restore:</h4>
                    <ul class="text-sm text-yellow-700 list-disc list-inside space-y-1">
                        <li>Database (semua tabel dan data)</li>
                        <li>Foto selfie absensi pegawai dan siswa</li>
                        <li>File pengaturan aplikasi</li>
                        <li>Konfigurasi shift dan jadwal</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('admin.backup.index') }}" class="px-4 py-2 text-gray-600 bg-gray-200 rounded-md hover:bg-gray-300 inline-flex items-center">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Kembali
            </a>
            <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 disabled:opacity-50 inline-flex items-center"
                    {{ count($backups) == 0 ? 'disabled' : '' }}>
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Restore Sekarang
            </button>
        </div>
    </form>
</div>

<div class="bg-white rounded-lg shadow p-6 mt-6">
    <h3 class="text-lg font-semibold mb-4">Daftar Backup Tersedia</h3>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama File</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ukuran</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($backups as $backup)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">{{ $backup['name'] }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ number_format($backup['size'] / 1024, 2) }} KB</td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ date('d/m/Y H:i', $backup['date']) }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <a href="{{ route('admin.backup.download', $backup['name']) }}" class="text-blue-600 hover:text-blue-900 mr-3">Download</a>
                        <form action="{{ route('admin.backup.delete', $backup['name']) }}" method="POST" class="inline" onsubmit="return confirm('Hapus file backup ini?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-900">Hapus</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="px-6 py-4 text-center text-gray-500">Tidak ada file backup tersedia</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection