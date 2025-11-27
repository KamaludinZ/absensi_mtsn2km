@extends('layouts.app')

@section('title', 'Detail Koreksi Absensi')
@section('page-title', 'Detail Permohonan Koreksi Absensi')

@section('content')
<div class="w-full">
    <div class="max-w-4xl mx-auto bg-white rounded-lg shadow p-6 sm:p-8">
        <div class="mb-6">
            <h2 class="text-xl font-bold text-gray-800">Detail Permohonan Koreksi Absensi</h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2">Tanggal Absensi</label>
                <div class="px-3 py-2 border border-gray-300 rounded-md bg-gray-100">
                    {{ $attendanceCorrection->date->format('d/m/Y') }}
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2">Status</label>
                <div class="px-3 py-2 border border-gray-300 rounded-md">
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                        {{ $attendanceCorrection->status === 'approved' ? 'bg-green-100 text-green-800' :
                           ($attendanceCorrection->status === 'rejected' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') }}">
                        {{ ucfirst($attendanceCorrection->status) }}
                    </span>
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2">Jam Masuk (Original)</label>
                <div class="px-3 py-2 border border-gray-300 rounded-md bg-gray-100">
                    {{ $attendanceCorrection->original_clock_in ? \Carbon\Carbon::parse($attendanceCorrection->original_clock_in)->format('H:i') : '-' }}
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2">Jam Masuk (Koreksi)</label>
                <div class="px-3 py-2 border border-gray-300 rounded-md bg-gray-100">
                    {{ $attendanceCorrection->corrected_clock_in ? \Carbon\Carbon::parse($attendanceCorrection->corrected_clock_in)->format('H:i') : '-' }}
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2">Jam Pulang (Original)</label>
                <div class="px-3 py-2 border border-gray-300 rounded-md bg-gray-100">
                    {{ $attendanceCorrection->original_clock_out ? \Carbon\Carbon::parse($attendanceCorrection->original_clock_out)->format('H:i') : '-' }}
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2">Jam Pulang (Koreksi)</label>
                <div class="px-3 py-2 border border-gray-300 rounded-md bg-gray-100">
                    {{ $attendanceCorrection->corrected_clock_out ? \Carbon\Carbon::parse($attendanceCorrection->corrected_clock_out)->format('H:i') : '-' }}
                </div>
            </div>

            <div class="mb-4 md:col-span-2">
                <label class="block text-gray-700 font-bold mb-2">Alasan Permohonan</label>
                <div class="px-3 py-2 border border-gray-300 rounded-md bg-gray-100">
                    {{ $attendanceCorrection->reason }}
                </div>
            </div>

            <div class="mb-4 md:col-span-2">
                <label class="block text-gray-700 font-bold mb-2">Dibuat Tanggal</label>
                <div class="px-3 py-2 border border-gray-300 rounded-md bg-gray-100">
                    {{ $attendanceCorrection->created_at->format('d/m/Y H:i') }}
                </div>
            </div>

            @if($attendanceCorrection->updated_at != $attendanceCorrection->created_at)
                <div class="mb-4 md:col-span-2">
                    <label class="block text-gray-700 font-bold mb-2">Diperbarui Tanggal</label>
                    <div class="px-3 py-2 border border-gray-300 rounded-md bg-gray-100">
                        {{ $attendanceCorrection->updated_at->format('d/m/Y H:i') }}
                    </div>
                </div>
            @endif

            <div class="mb-4 md:col-span-2">
                <label class="block text-gray-700 font-bold mb-2">Dokumen Pendukung</label>
                <div class="px-3 py-2 border border-gray-300 rounded-md bg-gray-100">
                    @if($attendanceCorrection->supporting_document)
                        <a href="{{ Storage::url($attendanceCorrection->supporting_document) }}" 
                           target="_blank" 
                           class="text-blue-600 hover:text-blue-800">
                            Lihat Dokumen Pendukung (PDF)
                        </a>
                    @else
                        <span class="text-gray-500">Tidak ada dokumen pendukung</span>
                    @endif
                </div>
            </div>

            @if($attendanceCorrection->approvedBy)
                <div class="mb-4 md:col-span-2">
                    <label class="block text-gray-700 font-bold mb-2">Disetujui Oleh</label>
                    <div class="px-3 py-2 border border-gray-300 rounded-md bg-gray-100">
                        {{ $attendanceCorrection->approvedBy->full_name }}
                    </div>
                </div>
                
                @if($attendanceCorrection->approved_at)
                    <div class="mb-4">
                        <label class="block text-gray-700 font-bold mb-2">Tanggal Disetujui</label>
                        <div class="px-3 py-2 border border-gray-300 rounded-md bg-gray-100">
                            {{ $attendanceCorrection->approved_at ? $attendanceCorrection->approved_at->format('d/m/Y H:i') : '-' }}
                        </div>
                    </div>
                @endif
            @endif
        </div>

        <div class="flex justify-end">
            <a href="{{ route('employee.attendance-corrections.index') }}" 
               class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700">
                Kembali ke Daftar
            </a>
        </div>
    </div>
</div>
@endsection