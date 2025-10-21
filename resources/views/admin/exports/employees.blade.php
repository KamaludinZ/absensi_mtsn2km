@extends('layouts.app')

@section('title', 'Export Laporan Pegawai')
@section('page-title', 'Export Laporan Pegawai')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-bold">Export Laporan Absensi Pegawai</h2>
            <a href="{{ route('admin.reports.index') }}" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 inline-flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
                Lihat Laporan
            </a>
        </div>

        @if(session('error'))
            <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                {{ session('error') }}
            </div>
        @endif

        @if(session('success'))
            <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                {{ session('success') }}
            </div>
        @endif

        <form action="{{ route('admin.reports.generate') }}" method="POST" class="space-y-6">
            @csrf
            <input type="hidden" name="type" value="employee">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Format Export <span class="text-red-600">*</span>
                    </label>
                    <select name="format" id="format" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('format') border-red-500 @enderror" required>
                        <option value="">-- Pilih Format --</option>
                        <option value="excel">Excel (.xlsx)</option>
                        <option value="pdf">PDF (.pdf)</option>
                    </select>
                    @error('format')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Pegawai
                    </label>
                    <select name="employee_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('employee_id') border-red-500 @enderror">
                        <option value="">-- Semua Pegawai --</option>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->id }}">
                                {{ $employee->full_name }} ({{ $employee->position }})
                            </option>
                        @endforeach
                    </select>
                    @error('employee_id')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-sm text-gray-500 mt-1">Kosongkan untuk export semua pegawai</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Tanggal Awal <span class="text-red-600">*</span>
                    </label>
                    <input type="date" name="start_date" value="{{ old('start_date') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('start_date') border-red-500 @enderror" required>
                    @error('start_date')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Tanggal Akhir <span class="text-red-600">*</span>
                    </label>
                    <input type="date" name="end_date" value="{{ old('end_date') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('end_date') border-red-500 @enderror" required>
                    @error('end_date')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="flex justify-between items-center pt-4 border-t">
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 inline-flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Download Laporan
                </button>

                <button type="reset" class="bg-gray-300 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500">
                    Reset Form
                </button>
            </div>
        </form>

        <div class="mt-8 p-4 bg-blue-50 rounded-lg border border-blue-200">
            <div class="flex items-start">
                <svg class="w-6 h-6 text-blue-600 mr-3 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div>
                    <h3 class="font-semibold text-blue-900 mb-2">Informasi Export Laporan Pegawai</h3>
                    <ul class="list-disc list-inside text-sm text-blue-800 space-y-1">
                        <li><strong>Format Excel:</strong> Menghasilkan file .xlsx dengan 14 kolom detail absensi (nama pegawai, jabatan, tanggal, nama shift, jam masuk/pulang, selisih waktu, status, selfie)</li>
                        <li><strong>Format PDF:</strong> Menghasilkan file .pdf siap cetak dengan layout profesional dan kop sekolah</li>
                        <li><strong>Urutan Data:</strong> Diurutkan berdasarkan nama pegawai (A-Z) kemudian tanggal (kronologis)</li>
                        <li><strong>Filter Pegawai:</strong> Pilih pegawai tertentu atau kosongkan untuk export semua pegawai</li>
                        <li><strong>Periode:</strong> Maksimal 1 tahun untuk performa optimal</li>
                        <li><strong>Preview:</strong> Klik "Lihat Laporan" untuk preview data sebelum export</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="mt-6 p-4 bg-yellow-50 rounded-lg border border-yellow-200">
            <div class="flex items-start">
                <svg class="w-6 h-6 text-yellow-600 mr-3 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <div>
                    <h3 class="font-semibold text-yellow-900 mb-1">Catatan Penting</h3>
                    <p class="text-sm text-yellow-800">File export akan langsung terdownload ke komputer Anda. Pastikan tanggal awal tidak lebih besar dari tanggal akhir.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
