@extends('layouts.app')

@section('title', 'Koreksi Absensi Saya')
@section('page-title', 'Daftar Permohonan Koreksi Absensi Saya')
@section('container-class', '')

@section('content')
<div class="w-full py-8">
    @if(session('success'))
        <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-md">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-md">
            {{ session('error') }}
        </div>
    @endif

    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Permohonan Koreksi Absensi Saya</h1>
        <a href="{{ route('employee.attendance-corrections.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
            Ajukan Koreksi Baru
        </a>
    </div>

    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="p-4 border-b">
            <h2 class="text-lg font-semibold">Riwayat Permohonan Koreksi Absensi</h2>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jam Masuk (Original)</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jam Masuk (Koreksi)</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jam Pulang (Original)</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jam Pulang (Koreksi)</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Alasan</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dokumen Bukti</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Disetujui Oleh</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($attendanceCorrections as $correction)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">{{ $correction->date->format('d/m/Y') }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">{{ $correction->original_clock_in ? \Carbon\Carbon::parse($correction->original_clock_in)->format('H:i') : '-' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">{{ $correction->corrected_clock_in ? \Carbon\Carbon::parse($correction->corrected_clock_in)->format('H:i') : '-' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">{{ $correction->original_clock_out ? \Carbon\Carbon::parse($correction->original_clock_out)->format('H:i') : '-' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">{{ $correction->corrected_clock_out ? \Carbon\Carbon::parse($correction->corrected_clock_out)->format('H:i') : '-' }}</td>
                            <td class="px-6 py-4">{{ Str::limit($correction->reason, 100) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($correction->supporting_document)
                                    <a href="{{ asset('storage/' . $correction->supporting_document) }}"
                                       target="_blank"
                                       class="text-blue-600 hover:text-blue-900 inline-flex items-center"
                                       title="Lihat Dokumen">
                                        <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                        </svg>
                                        PDF
                                    </a>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                    {{ $correction->status === 'approved' ? 'bg-green-100 text-green-800' :
                                       ($correction->status === 'rejected' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') }}">
                                    {{ ucfirst($correction->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($correction->status !== 'pending' && $correction->approvedBy)
                                    {{ $correction->approvedBy->full_name }}
                                    <br>
                                    <span class="text-xs text-gray-500">({{ $correction->approvedBy->username ?? $correction->approvedBy->email }})</span>
                                    <br>
                                    <span class="text-xs text-gray-500">({{ $correction->updated_at->format('d/m/Y H:i') }})</span>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($correction->status === 'pending')
                                    <a href="{{ route('employee.attendance-corrections.edit', $correction) }}" class="text-blue-600 hover:text-blue-900 mr-2">Edit</a>
                                @endif
                                <a href="{{ route('employee.attendance-corrections.show', $correction) }}" class="text-green-600 hover:text-green-900">Lihat</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-6 py-4 text-center text-gray-500">
                                Belum ada permohonan koreksi absensi.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
