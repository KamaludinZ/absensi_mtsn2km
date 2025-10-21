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