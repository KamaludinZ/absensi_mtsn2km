@extends('layouts.app')

@section('title', 'Restore Database')
@section('page-title', 'Restore Database')

@section('content')
<div class="bg-white rounded-lg shadow p-6">
    <div class="mb-6">
        <h2 class="text-xl font-bold text-gray-800 mb-4">Restore Database dari Backup</h2>
        <p class="text-gray-600">Pilih file backup untuk mengembalikan database. <strong class="text-red-600">Peringatan: Proses ini akan menggantikan data saat ini.</strong></p>
    </div>
    
    <form action="{{ route('admin.backup.restore.execute') }}" method="POST" onsubmit="return confirm('Anda yakin ingin merestore database? Proses ini akan menggantikan data saat ini!')">
        @csrf
        <div class="mb-6">
            <label class="block text-gray-700 font-bold mb-2" for="filename">
                Pilih File Backup
            </label>
            <select name="filename" id="filename" 
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                <option value="">Pilih File Backup</option>
                @forelse($backups as $backup)
                    <option value="{{ $backup['name'] }}">{{ $backup['name'] }} ({{ date('d/m/Y H:i', $backup['date']) }} - {{ number_format($backup['size'] / 1024, 2) }} KB)</option>
                @empty
                    <option value="" disabled>Belum ada file backup</option>
                @endforelse
            </select>
            @error('filename')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>
        
        <div class="flex justify-end">
            <a href="{{ route('admin.backup.index') }}" class="mr-2 px-4 py-2 text-gray-600 bg-gray-200 rounded-md">Kembali</a>
            <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 disabled:opacity-50" 
                    {{ count($backups) == 0 ? 'disabled' : '' }}>
                Restore Database
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