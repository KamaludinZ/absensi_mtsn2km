@php
    $currentSort = $sortField ?? request('sort', 'created_at');
    $currentDirection = $sortDirection ?? request('direction', 'desc');
@endphp

@extends('layouts.app')
@section('title', 'Manajemen Perizinan')
@section('page-title', 'Manajemen Perizinan')
@section('content')
<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="min-w-full">
        <thead class="bg-gray-50"><tr><th class="px-6 py-3 text-left">{!! sortableLink('Nama', 'full_name', $currentSort, $currentDirection) !!}</th><th class="px-6 py-3 text-left">{!! sortableLink('Tipe', 'permissible_type', $currentSort, $currentDirection) !!}</th><th class="px-6 py-3 text-left">{!! sortableLink('Tanggal', 'start_date', $currentSort, $currentDirection) !!}</th><th class="px-6 py-3 text-left">{!! sortableLink('Alasan', 'reason', $currentSort, $currentDirection) !!}</th><th class="px-6 py-3 text-left">Dokumen Pendukung</th><th class="px-6 py-3 text-left">{!! sortableLink('Status', 'status', $currentSort, $currentDirection) !!}</th><th class="px-6 py-3 text-left">Aksi</th></tr></thead>
        <tbody>
            @forelse($permissions as $permission)
            <tr><td class="px-6 py-4">{{ $permission->permissible->full_name }}</td>
            <td class="px-6 py-4">{{ class_basename($permission->permissible_type) }}</td>
            <td class="px-6 py-4">{{ $permission->start_date->format('d/m/Y') }} - {{ $permission->end_date->format('d/m/Y') }}</td>
            <td class="px-6 py-4">{{ Str::limit($permission->reason, 30) }}</td>
            <td class="px-6 py-4">
                @if($permission->supporting_document)
                    <a href="{{ route('documents.permission', $permission) }}" target="_blank" class="text-blue-600 hover:underline">
                        Lihat Dokumen
                    </a>
                @else
                    -
                @endif
            </td>
            <td class="px-6 py-4"><span class="px-2 py-1 text-xs rounded {{ $permission->status == 'approved' ? 'bg-green-100 text-green-800' : ($permission->status == 'rejected' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') }}">{{ ucfirst($permission->status) }}</span></td>
            <td class="px-6 py-4">
                @if($permission->status == 'pending')
                <form action="{{ route('monitoring.permissions.approve', $permission) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="text-green-600">Setujui</button>
                </form>
                <form action="{{ route('monitoring.permissions.reject', $permission) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="text-red-600 ml-2">Tolak</button>
                </form>
                @endif
            </td></tr>
            @empty
            <tr><td colspan="7" class="text-center py-4">Tidak ada data</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $permissions->links() }}</div>
@endsection
