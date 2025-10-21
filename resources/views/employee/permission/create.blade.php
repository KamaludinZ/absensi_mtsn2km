@extends('layouts.app')

@section('title', 'Ajukan Izin')
@section('page-title', 'Ajukan Izin')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-lg shadow p-6">
        <form action="{{ route('employee.permission.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="mb-4">
                <label for="start_date" class="block text-sm font-medium text-gray-700 mb-2">Tanggal Mulai</label>
                <input type="date" name="start_date" id="start_date" value="{{ old('start_date') }}" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                @error('start_date')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label for="end_date" class="block text-sm font-medium text-gray-700 mb-2">Tanggal Selesai</label>
                <input type="date" name="end_date" id="end_date" value="{{ old('end_date') }}" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                @error('end_date')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-6">
                <label for="reason" class="block text-sm font-medium text-gray-700 mb-2">Alasan</label>
                <textarea name="reason" id="reason" rows="4" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">{{ old('reason') }}</textarea>
                @error('reason')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            
            <div class="mb-6">
                <label for="supporting_document" class="block text-sm font-medium text-gray-700 mb-2">Bukti Pendukung (Opsional)</label>
                <input type="file" name="supporting_document" id="supporting_document"
                    accept=".pdf,.jpg,.jpeg,.png,.gif" 
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                <p class="text-xs text-gray-500 mt-1">Format: PDF, JPG, PNG. Ukuran maksimal: 500KB</p>
                @error('supporting_document')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex space-x-4">
                <button type="submit" class="flex-1 bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition duration-200 font-semibold">
                    Kirim Pengajuan
                </button>
                <a href="{{ route('employee.permission.index') }}" class="flex-1 bg-gray-500 text-white py-2 rounded-lg hover:bg-gray-600 transition duration-200 font-semibold text-center">
                    Batal
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
