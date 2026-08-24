@extends('layouts.app')

@section('title', 'Update Sistem')
@section('page-title', 'Update Sistem')
@section('container-class', '')

@section('content')
<div class="w-full px-4 sm:px-6 py-6">
    @if(session('success'))
        <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-md flex items-start">
            <svg class="w-5 h-5 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            <div>{{ session('success') }}</div>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-md flex items-start">
            <svg class="w-5 h-5 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
            </svg>
            <div>{{ session('error') }}</div>
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-md">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Upload Section -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="p-4 bg-blue-600 text-white">
                <h2 class="text-lg font-semibold flex items-center">
                    <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                    </svg>
                    Upload Update
                </h2>
            </div>

            <div class="p-6">
                <form action="{{ route('admin.system-update.upload') }}" method="POST" enctype="multipart/form-data" id="uploadForm">
                    @csrf

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            File Update (ZIP)
                        </label>
                        <input type="file"
                               name="update_zip"
                               accept=".zip"
                               required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                               onchange="handleFileSelect(this)">
                        <p class="mt-1 text-sm text-gray-500">Maksimal ukuran file: 100MB</p>
                    </div>

                    <div id="fileInfo" class="hidden mb-4 p-3 bg-gray-50 border border-gray-200 rounded-md">
                        <p class="text-sm"><strong>File:</strong> <span id="fileName"></span></p>
                        <p class="text-sm"><strong>Ukuran:</strong> <span id="fileSize"></span></p>
                    </div>

                    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-yellow-700">
                                    <strong>Peringatan:</strong> Pastikan file ZIP berisi update_manifest.json yang valid.
                                    Sistem akan membuat backup otomatis sebelum menerapkan update.
                                </p>
                            </div>
                        </div>
                    </div>

                    <button type="submit"
                            class="w-full bg-blue-600 text-white px-4 py-3 rounded-md hover:bg-blue-700 font-semibold flex items-center justify-center"
                            id="submitBtn">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                        </svg>
                        Upload dan Terapkan Update
                    </button>
                </form>
            </div>
        </div>

        <!-- Information Section -->
        <div class="space-y-6">
            <!-- Last Update Info -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="p-4 bg-gray-700 text-white">
                    <h2 class="text-lg font-semibold flex items-center">
                        <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Informasi Update Terakhir
                    </h2>
                </div>
                <div class="p-6">
                    @if($lastUpdate)
                        <div class="text-sm">
                            <pre class="bg-gray-50 p-3 rounded border border-gray-200 overflow-x-auto">{{ $lastUpdate }}</pre>
                        </div>
                    @else
                        <p class="text-gray-500 text-sm">Belum ada update yang diterapkan</p>
                    @endif
                </div>
            </div>

            <!-- Backup Management -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="p-4 bg-green-600 text-white">
                    <h2 class="text-lg font-semibold flex items-center">
                        <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                        </svg>
                        Backup Sistem
                    </h2>
                </div>
                <div class="p-6">
                    <p class="text-sm text-gray-600 mb-4">
                        Backup otomatis dibuat sebelum setiap update diterapkan.
                        Anda dapat melihat dan mendownload backup yang tersedia.
                    </p>
                    <a href="{{ route('admin.system-update.backups') }}"
                       class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Lihat Daftar Backup
                    </a>
                </div>
            </div>

            <!-- Instructions -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="p-4 bg-purple-600 text-white">
                    <h2 class="text-lg font-semibold flex items-center">
                        <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Format Update Manifest
                    </h2>
                </div>
                <div class="p-6">
                    <p class="text-sm text-gray-600 mb-3">
                        File ZIP harus berisi <code class="bg-gray-100 px-1 py-0.5 rounded">update_manifest.json</code> dengan format:
                    </p>
                    <pre class="bg-gray-900 text-green-400 p-4 rounded text-xs overflow-x-auto">{
  "version": "1.0.1",
  "description": "Update deskripsi",
  "run_migrations": false,
  "files": [
    "app/Http/Controllers/SomeController.php",
    "resources/views/some-view.blade.php",
    "config/app.php"
  ]
}</pre>
                    <div class="mt-4 space-y-2 text-sm text-gray-600">
                        <p><strong>version:</strong> Versi update</p>
                        <p><strong>description:</strong> Deskripsi update</p>
                        <p><strong>run_migrations:</strong> true/false untuk menjalankan migrasi</p>
                        <p><strong>files:</strong> Array path file yang akan diupdate</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function handleFileSelect(input) {
    const fileInfo = document.getElementById('fileInfo');
    const fileName = document.getElementById('fileName');
    const fileSize = document.getElementById('fileSize');

    if (input.files && input.files[0]) {
        const file = input.files[0];
        fileName.textContent = file.name;
        fileSize.textContent = formatBytes(file.size);
        fileInfo.classList.remove('hidden');
    } else {
        fileInfo.classList.add('hidden');
    }
}

function formatBytes(bytes, decimals = 2) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const dm = decimals < 0 ? 0 : decimals;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
}

// Show loading state on submit
document.getElementById('uploadForm').addEventListener('submit', function(e) {
    const submitBtn = document.getElementById('submitBtn');
    submitBtn.disabled = true;
    submitBtn.innerHTML = `
        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        Memproses Update...
    `;
});
</script>
@endsection
