@extends('layouts.app')

@section('title', 'Laporan Absensi')
@section('page-title', 'Laporan Absensi')
@section('container-class', '')

@section('content')
<div class="w-full px-4 sm:px-6 py-6">
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="p-4">
    <form action="{{ route('admin.reports.generate') }}" method="POST">
        @csrf
        <div class="mb-4">
            <label class="block text-sm font-medium mb-2">Tipe</label>
            <select name="type" required class="w-full px-4 py-2 border rounded-lg">
                <option value="employee">Pegawai</option>
                <option value="student">Siswa</option>
            </select>
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium mb-2">Tanggal Mulai</label>
            <input type="date" name="start_date" required class="w-full px-4 py-2 border rounded-lg">
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium mb-2">Tanggal Selesai</label>
            <input type="date" name="end_date" required class="w-full px-4 py-2 border rounded-lg">
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium mb-2">Rombel (untuk siswa)</label>
            <select name="rombel_id" class="w-full px-4 py-2 border rounded-lg">
                <option value="">Semua</option>
                @foreach($rombels as $rombel)
                <option value="{{ $rombel->id }}">{{ $rombel->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-6">
            <label class="block text-sm font-medium mb-2">Format</label>
            <select name="format" required class="w-full px-4 py-2 border rounded-lg">
                <option value="excel">Excel</option>
                <option value="pdf">PDF</option>
            </select>
        </div>
        <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700">Generate Laporan</button>
    </form>
        </div>
    </div>
</div>
@endsection
