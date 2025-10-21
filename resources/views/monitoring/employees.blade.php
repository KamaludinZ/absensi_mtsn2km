@extends('layouts.app')
@section('title', 'Monitor Pegawai')
@section('page-title', 'Monitor Kehadiran Pegawai')
@section('content')
<div class="mb-4 bg-white rounded-lg shadow p-4">
    <p>Total Pegawai: <strong>{{ $totalEmployees }}</strong> | Hadir Hari Ini: <strong class="text-green-600">{{ $presentCount }}</strong></p>
</div>
<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="min-w-full">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left">Nama</th>
        
                <th class="px-6 py-3 text-left">Jam Masuk</th>
                <th class="px-6 py-3 text-left">Status</th>
                <th class="px-6 py-3 text-left">Selfie Masuk</th>
                <th class="px-6 py-3 text-left">Jam Pulang</th>
                <th class="px-6 py-3 text-left">Selfie Pulang</th>
            </tr>
        </thead>
        <tbody>
            @forelse($attendances as $attendance)
            <tr>
                <td class="px-6 py-4">{{ $attendance->attendable->full_name }}</td>
        
                <td class="px-6 py-4">{{ $attendance->clock_in ? $attendance->clock_in->format('H:i:s') : '-' }}</td>
                <td class="px-6 py-4">
                    <span class="px-2 py-1 text-xs rounded {{ $attendance->status_in == 'Tepat Waktu' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">{{ $attendance->status_in }}</span>
                </td>
                <td class="px-6 py-4">
                    @if($attendance->selfie_path_in)
                        <a href="{{ Storage::url($attendance->selfie_path_in) }}" target="_blank">
                            <img src="{{ Storage::url($attendance->selfie_path_in) }}" alt="Selfie Masuk" class="w-16 h-16 object-cover rounded border cursor-pointer hover:opacity-80">
                        </a>
                    @else
                        -
                    @endif
                </td>
                <td class="px-6 py-4">{{ $attendance->clock_out ? $attendance->clock_out->format('H:i:s') : '-' }}</td>
                <td class="px-6 py-4">
                    @if($attendance->selfie_path_out)
                        <a href="{{ Storage::url($attendance->selfie_path_out) }}" target="_blank">
                            <img src="{{ Storage::url($attendance->selfie_path_out) }}" alt="Selfie Pulang" class="w-16 h-16 object-cover rounded border cursor-pointer hover:opacity-80">
                        </a>
                    @else
                        -
                    @endif
                </td>
            </tr>
            @empty
            <tr><td colspan="7" class="text-center py-4">Belum ada data hari ini</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
