@extends('layouts.app')
@section('title', 'Import Siswa')
@section('page-title', 'Import Siswa dari Excel')
@section('content')
<div class="max-w-3xl mx-auto py-6 space-y-6">
    @if(session('import_errors'))
        @php $importErrors = session('import_errors'); @endphp
        <div class="bg-red-50 border border-red-300 text-red-800 px-4 py-3 rounded">
            <p class="font-semibold mb-2">Import dibatalkan, tidak ada data yang disimpan. Perbaiki {{ count($importErrors) }} kesalahan berikut lalu upload ulang:</p>
            <ul class="list-disc list-inside text-sm space-y-1 max-h-72 overflow-y-auto">
                @foreach(array_slice($importErrors, 0, 200) as $error)
                    <li>{{ $error }}</li>
                @endforeach
                @if(count($importErrors) > 200)
                    <li>… dan {{ count($importErrors) - 200 }} kesalahan lainnya.</li>
                @endif
            </ul>
        </div>
    @endif

    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="font-semibold text-lg mb-2">1. Download template</h3>
        <p class="text-sm text-gray-600 mb-4">
            Template berisi kolom <code class="bg-gray-100 px-1">NISN, Nama Lengkap, Tingkat Kelas, Rombel, Tahun Pelajaran, Status Aktif, Kartu RFID</code>,
            daftar rombel yang valid, dan petunjuk pengisian.
        </p>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('admin.students.download-template') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">Download Template Kosong</a>
            <a href="{{ route('admin.students.download-template', ['with_students' => 1]) }}" class="bg-blue-100 text-blue-800 px-4 py-2 rounded-lg hover:bg-blue-200">Download Data Siswa Aktif</a>
        </div>
        <p class="text-xs text-gray-500 mt-2">"Data Siswa Aktif" berguna untuk memperbarui banyak siswa sekaligus: ubah isinya lalu import kembali.</p>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="font-semibold text-lg mb-2">2. Upload file</h3>
        <ul class="text-sm text-gray-600 list-disc list-inside mb-4 space-y-1">
            <li>Siswa dicocokkan berdasarkan <strong>NISN</strong>: NISN yang sudah ada akan diperbarui, NISN baru akan ditambahkan.</li>
            <li>Tahun Pelajaran kosong = tahun pelajaran aktif{{ $activeYear ? ' (' . $activeYear->year_name . ')' : '' }}.</li>
            <li>Semua baris diperiksa dulu; jika ada kesalahan tidak ada data yang disimpan.</li>
        </ul>
        @if(!$activeYear)
            <div class="mb-4 bg-yellow-50 border border-yellow-300 text-yellow-800 px-3 py-2 rounded text-sm">
                Belum ada tahun pelajaran aktif, jadi kolom Tahun Pelajaran wajib diisi di setiap baris.
            </div>
        @endif
        <form action="{{ route('admin.students.import') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="mb-6">
                <label for="file" class="block text-sm font-medium text-gray-700 mb-2">File Excel (.xlsx, .xls, .csv, maks. 5 MB)</label>
                <input type="file" id="file" name="file" required accept=".xlsx,.xls,.csv" class="w-full px-4 py-2 border rounded-lg">
                @error('file')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div class="flex space-x-4">
                <button type="submit" class="flex-1 bg-green-600 text-white py-2 rounded-lg hover:bg-green-700">Import</button>
                <a href="{{ route('admin.students.index') }}" class="flex-1 bg-gray-500 text-white py-2 rounded-lg hover:bg-gray-600 text-center">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection
