@extends('layouts.app')

@section('title', 'Update Aplikasi')
@section('page-title', 'Update Aplikasi')

@section('content')
<div class="max-w-6xl mx-auto">
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-bold">Manajemen Update Aplikasi</h2>
        </div>

        @if(session('error'))
            <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                <strong class="font-bold">Error!</strong>
                <span class="block sm:inline">{{ session('error') }}</span>
            </div>
        @endif

        @if(session('success'))
            <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                <strong class="font-bold">Berhasil!</strong>
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
        @endif

        <!-- Current Version Info -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div class="bg-blue-50 p-6 rounded-lg border border-blue-200">
                <div class="flex items-start">
                    <svg class="w-12 h-12 text-blue-600 mr-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <div>
                        <h3 class="text-lg font-semibold text-blue-900 mb-2">Versi Aplikasi</h3>
                        <p class="text-3xl font-bold text-blue-600">{{ $currentVersion }}</p>
                        <p class="text-sm text-blue-700 mt-2">Versi saat ini yang terinstall</p>
                    </div>
                </div>
            </div>

            <div class="bg-green-50 p-6 rounded-lg border border-green-200">
                <div class="flex items-start">
                    <svg class="w-12 h-12 text-green-600 mr-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                    </svg>
                    <div>
                        <h3 class="text-lg font-semibold text-green-900 mb-2">Status Git</h3>
                        @if($gitStatus['available'])
                            <p class="text-sm text-green-700">
                                <span class="font-semibold">Branch:</span> {{ $gitStatus['branch'] }}
                            </p>
                            <p class="text-sm text-green-700">
                                <span class="font-semibold">Commit:</span> {{ $gitStatus['lastCommit'] }}
                            </p>
                            <p class="text-sm text-green-700">
                                <span class="font-semibold">Update:</span> {{ $gitStatus['lastCommitDate'] }}
                            </p>
                        @else
                            <p class="text-red-600 font-semibold">Git tidak tersedia</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Update Actions -->
        <div class="bg-gray-50 p-6 rounded-lg border border-gray-200 mb-6">
            <h3 class="text-lg font-semibold mb-4">Aksi Update</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <button onclick="checkUpdate()" id="checkBtn" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 inline-flex items-center justify-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    <span>Cek Update</span>
                </button>

                <form action="{{ route('admin.update.update') }}" method="POST" onsubmit="return confirmUpdate()">
                    @csrf
                    <button type="submit" id="updateBtn" class="w-full bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 inline-flex items-center justify-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        <span>Jalankan Update</span>
                    </button>
                </form>
            </div>

            <div id="updateStatus" class="mt-4 hidden">
                <!-- Update status will be shown here -->
            </div>
        </div>

        <!-- Update History -->
        @if(count($updateHistory) > 0)
        <div class="bg-white p-6 rounded-lg border border-gray-200">
            <h3 class="text-lg font-semibold mb-4">Riwayat Update</h3>
            <div class="space-y-2">
                @foreach($updateHistory as $history)
                <div class="flex items-start p-3 bg-gray-50 rounded">
                    <svg class="w-5 h-5 text-gray-500 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-sm text-gray-700 font-mono">{{ $history }}</p>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Information Box -->
        <div class="mt-6 p-4 bg-yellow-50 rounded-lg border border-yellow-200">
            <div class="flex items-start">
                <svg class="w-6 h-6 text-yellow-600 mr-3 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <div>
                    <h3 class="font-semibold text-yellow-900 mb-2">Catatan Penting</h3>
                    <ul class="list-disc list-inside text-sm text-yellow-800 space-y-1">
                        <li>Backup database akan dibuat secara otomatis sebelum update</li>
                        <li>Proses update akan menjalankan migration database jika ada</li>
                        <li>Semua cache akan dibersihkan setelah update</li>
                        <li>Pastikan koneksi internet stabil saat melakukan update</li>
                        <li>Update menggunakan Git, pastikan Git sudah terinstall di server</li>
                        <li>Jangan tutup browser selama proses update berlangsung</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function checkUpdate() {
    const btn = document.getElementById('checkBtn');
    const statusDiv = document.getElementById('updateStatus');

    // Disable button and show loading
    btn.disabled = true;
    btn.innerHTML = '<svg class="animate-spin w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg><span>Mengecek...</span>';

    fetch('{{ route('admin.update.check') }}')
        .then(response => response.json())
        .then(data => {
            statusDiv.classList.remove('hidden');

            if (data.success) {
                if (data.hasUpdate) {
                    statusDiv.innerHTML = `
                        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                            <strong class="font-bold">Update Tersedia!</strong>
                            <p class="text-sm">${data.message}</p>
                            <p class="text-sm mt-1">Versi saat ini: ${data.currentVersion}</p>
                        </div>
                    `;
                } else {
                    statusDiv.innerHTML = `
                        <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded">
                            <strong class="font-bold">Sudah Terbaru</strong>
                            <p class="text-sm">${data.message}</p>
                        </div>
                    `;
                }
            } else {
                statusDiv.innerHTML = `
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                        <strong class="font-bold">Error!</strong>
                        <p class="text-sm">${data.message}</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            statusDiv.classList.remove('hidden');
            statusDiv.innerHTML = `
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    <strong class="font-bold">Error!</strong>
                    <p class="text-sm">Terjadi kesalahan saat mengecek update.</p>
                </div>
            `;
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg><span>Cek Update</span>';
        });
}

function confirmUpdate() {
    return confirm('Apakah Anda yakin ingin melakukan update?\n\n' +
                   'Proses ini akan:\n' +
                   '- Membuat backup database\n' +
                   '- Mengupdate kode aplikasi\n' +
                   '- Menjalankan migration\n' +
                   '- Membersihkan cache\n\n' +
                   'Pastikan tidak ada yang menggunakan aplikasi saat update berlangsung.');
}
</script>
@endpush
@endsection
