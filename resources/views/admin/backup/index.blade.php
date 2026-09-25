@extends('layouts.app')
@section('title', 'Backup & Restore')
@section('page-title', 'Backup & Restore')
@section('content')
<div class="mb-6">
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
        <div class="flex items-start">
            <svg class="w-5 h-5 text-blue-600 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
            </svg>
            <div>
                <h4 class="font-medium text-blue-800 mb-1">Backup Lengkap Mencakup:</h4>
                <ul class="text-sm text-blue-700 list-disc list-inside space-y-1">
                    <li>Database (semua tabel dan data)</li>
                    <li>Foto selfie absensi pegawai dan siswa</li>
                    <li>File pengaturan aplikasi</li>
                    <li>Metadata backup (tanggal, versi app, dll)</li>
                </ul>
                <p class="text-sm text-blue-700 mt-2">
                    File <code>.env</code> (password database &amp; kunci aplikasi) sengaja tidak ikut dibackup demi keamanan.
                    Simpan salinan <code>.env</code> di tempat aman secara terpisah.
                </p>
            </div>
        </div>
    </div>

    <div class="flex flex-wrap gap-4">
        <form action="{{ route('admin.backup.create') }}" method="POST" class="inline" onsubmit="return confirm('Buat backup lengkap (database + files)?')">
            @csrf
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 inline-flex items-center">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
                </svg>
                Buat Backup Lengkap
            </button>
        </form>

        <a href="{{ route('admin.backup.restore') }}" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 inline-flex items-center">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            Restore Database & Files
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
