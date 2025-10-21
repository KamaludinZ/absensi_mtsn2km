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