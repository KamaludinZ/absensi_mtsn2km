@extends('layouts.app')

@section('title', 'Perizinan')
@section('page-title', 'Perizinan')

@section('content')
<div class="mb-6">
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-xl font-semibold mb-4">Status Izin Saat Ini</h2>
        @php
            $employee = auth()->user()->employee;
            $currentPermissions = \App\Models\Permission::where('permissible_type', \App\Models\Employee::class)
                ->where('permissible_id', $employee->id)
                ->where('status', 'approved')
                ->where('start_date', '<=', now())
                ->where('end_date', '>=', now())
                ->get();
        @endphp
        
        @if($currentPermissions->count() > 0)
            <div class="mb-4">
                <p class="text-red-600 font-medium">Anda saat ini sedang dalam masa izin:</p>
                <ul class="list-disc pl-5 mt-2">
                    @foreach($currentPermissions as $permission)
                        <li class="mt-1">
                            {{ $permission->start_date->format('d/m/Y') }} - {{ $permission->end_date->format('d/m/Y') }}: 
                            {{ Str::limit($permission->reason, 100) }}
                        </li>
                    @endforeach
                </ul>
            </div>
        @else
            <p class="text-green-600 font-medium">Anda tidak sedang dalam masa izin.</p>
        @endif
    </div>
    
    <a href="{{ route('employee.permission.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition duration-200">
        Ajukan Izin Baru
    </a>
</div>

<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
        <h3 class="text-lg font-medium text-gray-900">Riwayat Pengajuan Izin</h3>
    </div>
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal Mulai</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal Selesai</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Alasan</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dokumen Pendukung</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @forelse($permissions as $permission)
            <tr>
                <td class="px-6 py-4 whitespace-nowrap">{{ $permission->start_date->format('d/m/Y') }}</td>
                <td class="px-6 py-4 whitespace-nowrap">{{ $permission->end_date->format('d/m/Y') }}</td>
                <td class="px-6 py-4">{{ Str::limit($permission->reason, 50) }}</td>
                <td class="px-6 py-4 whitespace-nowrap">
                    @if($permission->supporting_document)
                        <a href="{{ asset('storage/' . $permission->supporting_document) }}" target="_blank" class="text-blue-600 hover:underline">
                            Lihat Dokumen
                        </a>
                    @else
                        -
                    @endif
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    @if($permission->status === 'approved')
                        <span class="px-2 py-1 text-xs rounded bg-green-100 text-green-800">
                            Disetujui
                        </span>
                    @elseif($permission->status === 'rejected')
                        <span class="px-2 py-1 text-xs rounded bg-red-100 text-red-800">
                            Ditolak
                        </span>
                    @else
                        <span class="px-2 py-1 text-xs rounded bg-yellow-100 text-yellow-800">
                            Menunggu Persetujuan
                        </span>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="px-6 py-4 text-center text-gray-500">Tidak ada data perizinan</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">
    {{ $permissions->links() }}
</div>
@endsection
