@extends('layouts.app')
@section('title', 'Backup & Restore')
@section('page-title', 'Backup & Restore')
@section('content')
<div class="mb-6">
    <div class="flex flex-wrap gap-4">
        <form action="{{ route('admin.backup.create') }}" method="POST" class="inline">
            @csrf
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                Buat Backup Baru
            </button>
        </form>
        
        <a href="{{ route('admin.backup.restore') }}" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700">
            Restore Database
        </a>
    </div>
</div>

<div class="bg-white rounded-lg shadow p-6">
    <h3 class="text-lg font-semibold mb-4">Daftar Backup</h3>
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
                    <td colspan="4" class="px-6 py-4 text-center text-gray-500">Tidak ada file backup</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
