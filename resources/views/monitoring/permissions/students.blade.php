@extends('layouts.app')
@section('title', 'Perizinan Siswa')
@section('page-title', 'Perizinan Siswa')

@section('content')
<div class="max-w-7xl mx-auto">
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

    <div class="bg-white rounded-lg shadow p-6">
        <!-- Filter Section -->
        <div class="mb-6 p-4 bg-gray-50 rounded-lg">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Mulai</label>
                    <input type="date" name="start_date" value="{{ request('start_date') }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Selesai</label>
                    <input type="date" name="end_date" value="{{ request('end_date') }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Semua Status</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Rombel</label>
                    <select name="rombel_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Semua Rombel</option>
                        @foreach($rombels as $rombel)
                            <option value="{{ $rombel->id }}" {{ request('rombel_id') == $rombel->id ? 'selected' : '' }}>
                                {{ $rombel->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Siswa</label>
                    <input type="text" name="student_name" value="{{ request('student_name') }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="Cari nama siswa...">
                </div>

                <div class="md:col-span-5 flex justify-end">
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 mr-2">
                        Filter
                    </button>
                    <a href="{{ route('monitoring.permissions.students') }}" class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- Permissions Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Siswa</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rombel</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal Izin</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Alasan</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dokumen</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($permissions as $permission)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                {{ $permission->permissible->full_name ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                {{ $permission->permissible->rombel->name ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                {{ \Carbon\Carbon::parse($permission->start_date)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($permission->end_date)->format('d/m/Y') }}
                            </td>
                            <td class="px-6 py-4">
                                {{ Str::limit($permission->reason, 50) }}
                            </td>
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
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                    {{ $permission->status === 'approved' ? 'bg-green-100 text-green-800' :
                                       ($permission->status === 'rejected' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') }}">
                                    {{ ucfirst($permission->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <div class="flex items-center space-x-2">
                                    @if($permission->status === 'pending')
                                        <form action="{{ route('monitoring.permissions.approve', $permission) }}" method="POST" class="inline-block">
                                            @csrf
                                            <button type="submit" class="text-green-600 hover:text-green-900" title="Setujui" onclick="return confirm('Apakah Anda yakin ingin menyetujui izin ini?')">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                            </button>
                                        </form>
                                        <form action="{{ route('monitoring.permissions.reject', $permission) }}" method="POST" class="inline-block">
                                            @csrf
                                            <button type="submit" class="text-red-600 hover:text-red-900" title="Tolak" onclick="return confirm('Apakah Anda yakin ingin menolak izin ini?')">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                            </button>
                                        </form>
                                    @else
                                        -
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                                Tidak ada data perizinan siswa.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($permissions->hasPages())
        <div class="mt-6">
            {{ $permissions->appends(request()->query())->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
