@extends('layouts.app')

@section('title', 'Tambah Izin Pegawai')
@section('page-title', 'Tambah Izin Pegawai')

@section('content')
<div class="bg-white rounded-lg shadow p-6">
    <form action="{{ route('admin.permissions.employees.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2" for="employee_id">
                    Pegawai
                </label>
                <select name="employee_id" id="employee_id"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        required>
                    <option value="">Pilih Pegawai</option>
                    @foreach($employees as $employee)
                        <option value="{{ $employee->id }}" {{ old('employee_id') == $employee->id ? 'selected' : '' }}>
                            {{ $employee->full_name }} - {{ $employee->position }}
                        </option>
                    @endforeach
                </select>
                @error('employee_id')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2" for="status">
                    Status
                </label>
                <select name="status" id="status"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="pending" {{ old('status', 'pending') == 'pending' ? 'selected' : '' }}>
                        Menunggu Persetujuan
                    </option>
                    <option value="approved" {{ old('status') == 'approved' ? 'selected' : '' }}>
                        Disetujui
                    </option>
                    <option value="rejected" {{ old('status') == 'rejected' ? 'selected' : '' }}>
                        Ditolak
                    </option>
                </select>
                @error('status')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2" for="start_date">
                    Tanggal Mulai
                </label>
                <input type="date" name="start_date" id="start_date"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                       value="{{ old('start_date') }}" required>
                @error('start_date')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2" for="end_date">
                    Tanggal Selesai
                </label>
                <input type="date" name="end_date" id="end_date"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                       value="{{ old('end_date') }}" required>
                @error('end_date')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4 md:col-span-2">
                <label class="block text-gray-700 font-bold mb-2" for="reason">
                    Alasan Izin
                </label>
                <textarea name="reason" id="reason"
                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                          rows="3" required>{{ old('reason') }}</textarea>
                @error('reason')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4 md:col-span-2">
                <label class="block text-gray-700 font-bold mb-2" for="supporting_document">
                    Dokumen Pendukung
                </label>
                <input type="file" name="supporting_document" id="supporting_document"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                       accept=".jpg,.jpeg,.png,.pdf">
                @error('supporting_document')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
                <p class="text-gray-500 text-xs mt-1">Format: JPG, JPEG, PNG, PDF. Maksimal 2MB.</p>
            </div>
        </div>

        <div class="flex justify-end">
            <a href="{{ route('admin.permissions.employees') }}" class="mr-2 px-4 py-2 text-gray-600 bg-gray-200 rounded-md">Batal</a>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md">Simpan</button>
        </div>
    </form>
</div>
@endsection
