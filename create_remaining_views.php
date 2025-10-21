<?php
// Script to create remaining admin and monitoring views

$views = [
    // Admin Students
    'resources/views/admin/students/index.blade.php' => <<<'BLADE'
@extends('layouts.app')
@section('title', 'Data Siswa')
@section('page-title', 'Data Siswa')
@section('content')
<div class="mb-4 flex space-x-2">
    <a href="{{ route('admin.students.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">Tambah Siswa</a>
    <a href="{{ route('admin.students.import-form') }}" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">Import Excel</a>
</div>
<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rombel</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @forelse($students as $student)
            <tr>
                
                <td class="px-6 py-4">{{ $student->full_name }}</td>
                <td class="px-6 py-4">{{ $student->rombel->name }}</td>
                <td class="px-6 py-4">
                    <span class="px-2 py-1 text-xs rounded {{ $student->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                        {{ $student->is_active ? 'Aktif' : 'Tidak Aktif' }}
                    </span>
                </td>
                <td class="px-6 py-4">
                    <a href="{{ route('admin.students.edit', $student) }}" class="text-blue-600 hover:text-blue-800">Edit</a>
                    <form action="{{ route('admin.students.destroy', $student) }}" method="POST" class="inline">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-red-600 hover:text-red-800 ml-2" onclick="return confirm('Yakin hapus?')">Hapus</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr><td colspan="5" class="px-6 py-4 text-center text-gray-500">Tidak ada data</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $students->links() }}</div>
@endsection
BLADE,

    'resources/views/admin/students/create.blade.php' => <<<'BLADE'
@extends('layouts.app')
@section('title', 'Tambah Siswa')
@section('page-title', 'Tambah Siswa')
@section('content')
<div class="max-w-2xl mx-auto bg-white rounded-lg shadow p-6">
    <form action="{{ route('admin.students.store') }}" method="POST">
        @csrf
        <div class="mb-4">

        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Nama Lengkap</label>
            <input type="text" name="full_name" value="{{ old('full_name') }}" required class="w-full px-4 py-2 border rounded-lg">
            @error('full_name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Rombel</label>
            <select name="rombel_id" required class="w-full px-4 py-2 border rounded-lg">
                <option value="">Pilih Rombel</option>
                @foreach($rombels as $rombel)
                <option value="{{ $rombel->id }}">{{ $rombel->name }}</option>
                @endforeach
            </select>
            @error('rombel_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
            <select name="is_active" required class="w-full px-4 py-2 border rounded-lg">
                <option value="1">Aktif</option>
                <option value="0">Tidak Aktif</option>
            </select>
        </div>
        <div class="flex space-x-4">
            <button type="submit" class="flex-1 bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700">Simpan</button>
            <a href="{{ route('admin.students.index') }}" class="flex-1 bg-gray-500 text-white py-2 rounded-lg hover:bg-gray-600 text-center">Batal</a>
        </div>
    </form>
</div>
@endsection
BLADE,

    'resources/views/admin/students/edit.blade.php' => <<<'BLADE'
@extends('layouts.app')
@section('title', 'Edit Siswa')
@section('page-title', 'Edit Siswa')
@section('content')
<div class="max-w-2xl mx-auto bg-white rounded-lg shadow p-6">
    <form action="{{ route('admin.students.update', $student) }}" method="POST">
        @csrf @method('PUT')
        <div class="mb-4">

        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Nama Lengkap</label>
            <input type="text" name="full_name" value="{{ old('full_name', $student->full_name) }}" required class="w-full px-4 py-2 border rounded-lg">
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Rombel</label>
            <select name="rombel_id" required class="w-full px-4 py-2 border rounded-lg">
                @foreach($rombels as $rombel)
                <option value="{{ $rombel->id }}" {{ $student->rombel_id == $rombel->id ? 'selected' : '' }}>{{ $rombel->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
            <select name="is_active" required class="w-full px-4 py-2 border rounded-lg">
                <option value="1" {{ $student->is_active ? 'selected' : '' }}>Aktif</option>
                <option value="0" {{ !$student->is_active ? 'selected' : '' }}>Tidak Aktif</option>
            </select>
        </div>
        <div class="flex space-x-4">
            <button type="submit" class="flex-1 bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700">Update</button>
            <a href="{{ route('admin.students.index') }}" class="flex-1 bg-gray-500 text-white py-2 rounded-lg hover:bg-gray-600 text-center">Batal</a>
        </div>
    </form>
</div>
@endsection
BLADE,

    'resources/views/admin/students/import.blade.php' => <<<'BLADE'
@extends('layouts.app')
@section('title', 'Import Siswa')
@section('page-title', 'Import Siswa dari Excel')
@section('content')
<div class="max-w-2xl mx-auto bg-white rounded-lg shadow p-6">
    <div class="mb-6">
        <h3 class="font-semibold text-lg mb-2">Format File Excel:</h3>
        <p class="text-sm text-gray-600">File harus memiliki kolom: <code class="bg-gray-100 px-2 py-1">full_name, rombel, is_active</code></p>
    </div>
    <form action="{{ route('admin.students.import') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">File Excel</label>
            <input type="file" name="file" required accept=".xlsx,.xls,.csv" class="w-full px-4 py-2 border rounded-lg">
            @error('file')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>
        <div class="flex space-x-4">
            <button type="submit" class="flex-1 bg-green-600 text-white py-2 rounded-lg hover:bg-green-700">Import</button>
            <a href="{{ route('admin.students.index') }}" class="flex-1 bg-gray-500 text-white py-2 rounded-lg hover:bg-gray-600 text-center">Batal</a>
        </div>
    </form>
</div>
@endsection
BLADE,

];

// Create directories and files
foreach ($views as $path => $content) {
    $fullPath = __DIR__ . '/' . $path;
    $directory = dirname($fullPath);

    if (!is_dir($directory)) {
        mkdir($directory, 0755, true);
    }

    file_put_contents($fullPath, $content);
    echo "Created: $path\n";
}

echo "\nAll remaining views created successfully!\n";
