@php
    $currentSort = $sortField ?? request('sort', 'created_at');
    $currentDirection = $sortDirection ?? request('direction', 'desc');
@endphp

@extends('layouts.app')

@section('title', 'Perizinan')
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
        <h1 class="text-2xl font-bold">
            @if(request()->routeIs('admin.permissions.students') || request()->routeIs('admin.permissions.students.*'))
                Perizinan Siswa
            @else
                Perizinan Pegawai
            @endif
        </h1>
        @if(request()->routeIs('admin.permissions.students') || request()->routeIs('admin.permissions.students.*'))
            <a href="{{ route('admin.permissions.students.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                Tambah Izin Siswa
            </a>
        @else
            <a href="{{ route('admin.permissions.employees.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                Tambah Izin Pegawai
            </a>
        @endif
    </div>
    
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="p-4 border-b">
            <h2 class="text-lg font-semibold">Daftar Permohonan Izin</h2>
        </div>

        <!-- Filter Section -->
        <div class="p-4 bg-gray-50 border-b">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Bulan</label>
                    <select name="month" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Semua Bulan</option>
                        <option value="1" {{ (request('month') ?? $selectedMonth ?? now()->month) == '1' ? 'selected' : '' }}>Januari</option>
                        <option value="2" {{ (request('month') ?? $selectedMonth ?? now()->month) == '2' ? 'selected' : '' }}>Februari</option>
                        <option value="3" {{ (request('month') ?? $selectedMonth ?? now()->month) == '3' ? 'selected' : '' }}>Maret</option>
                        <option value="4" {{ (request('month') ?? $selectedMonth ?? now()->month) == '4' ? 'selected' : '' }}>April</option>
                        <option value="5" {{ (request('month') ?? $selectedMonth ?? now()->month) == '5' ? 'selected' : '' }}>Mei</option>
                        <option value="6" {{ (request('month') ?? $selectedMonth ?? now()->month) == '6' ? 'selected' : '' }}>Juni</option>
                        <option value="7" {{ (request('month') ?? $selectedMonth ?? now()->month) == '7' ? 'selected' : '' }}>Juli</option>
                        <option value="8" {{ (request('month') ?? $selectedMonth ?? now()->month) == '8' ? 'selected' : '' }}>Agustus</option>
                        <option value="9" {{ (request('month') ?? $selectedMonth ?? now()->month) == '9' ? 'selected' : '' }}>September</option>
                        <option value="10" {{ (request('month') ?? $selectedMonth ?? now()->month) == '10' ? 'selected' : '' }}>Oktober</option>
                        <option value="11" {{ (request('month') ?? $selectedMonth ?? now()->month) == '11' ? 'selected' : '' }}>November</option>
                        <option value="12" {{ (request('month') ?? $selectedMonth ?? now()->month) == '12' ? 'selected' : '' }}>Desember</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tahun</label>
                    <select name="year" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Semua Tahun</option>
                        @php
                            $currentYear = date('Y');
                            $startYear = $currentYear - 5; // Show 5 years before current year
                            $endYear = $currentYear + 2;   // Show 2 years after current year
                        @endphp
                        @for($year = $endYear; $year >= $startYear; $year--)
                            <option value="{{ $year }}" {{ (request('year') ?? $selectedYear ?? now()->year) == $year ? 'selected' : '' }}>
                                {{ $year }}
                            </option>
                        @endfor
                    </select>
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

                @if(request()->routeIs('admin.permissions.students') || request()->routeIs('admin.permissions.students.*'))
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Rombel</label>
                        <select name="rombel_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Semua Rombel</option>
                            @if(isset($rombels))
                                @foreach($rombels as $rombel)
                                    <option value="{{ $rombel->id }}" {{ request('rombel_id') == $rombel->id ? 'selected' : '' }}>
                                        {{ $rombel->name }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                @else
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Pegawai</label>
                        <select name="employee_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Semua Pegawai</option>
                            @if(isset($employees))
                                @foreach($employees as $employee)
                                    <option value="{{ $employee->id }}" {{ request('employee_id') == $employee->id ? 'selected' : '' }}>
                                        {{ $employee->full_name }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                @endif

                @if(request()->routeIs('admin.permissions.students') || request()->routeIs('admin.permissions.students.*'))
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Siswa</label>
                        <input type="text" name="student_name" value="{{ request('student_name') }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="Cari nama siswa...">
                    </div>
                @endif

                <div class="md:col-span-4 flex justify-end">
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 mr-2">
                        Filter
                    </button>
                    @if(request()->routeIs('admin.permissions.students') || request()->routeIs('admin.permissions.students.*'))
                        <a href="{{ route('admin.permissions.students') }}" class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700">
                            Reset
                        </a>
                    @else
                        <a href="{{ route('admin.permissions.employees') }}" class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{!! sortableLink('Nama', 'full_name', $currentSort, $currentDirection) !!}</th>
                        @if(request()->routeIs('admin.permissions.students') || request()->routeIs('admin.permissions.students.*'))
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{!! sortableLink('Rombel', 'rombel', $currentSort, $currentDirection) !!}</th>
                        @else
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{!! sortableLink('Jabatan', 'position', $currentSort, $currentDirection) !!}</th>
                        @endif
                        @if(request()->routeIs('admin.permissions.students'))
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jenis</th>
                        @endif
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{!! sortableLink('Tanggal Izin', 'start_date', $currentSort, $currentDirection) !!}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{!! sortableLink('Alasan', 'reason', $currentSort, $currentDirection) !!}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dokumen Pendukung</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{!! sortableLink('Status', 'status', $currentSort, $currentDirection) !!}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Disetujui Oleh</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($permissions as $permission)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($permission->permissible_type === 'App\\Models\\Employee')
                                    {{ $permission->permissible->full_name ?? 'N/A' }}
                                @elseif($permission->permissible_type === 'App\\Models\\Student')
                                    {{ $permission->permissible->full_name ?? 'N/A' }}
                                @else
                                    N/A
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if(request()->routeIs('admin.permissions.students') || request()->routeIs('admin.permissions.students.*'))
                                    {{ $permission->rombel_name ?? 'N/A' }}
                                @else
                                    {{ $permission->permissible->position ?? 'N/A' }}
                                @endif
                            </td>
                            @if(request()->routeIs('admin.permissions.students'))
                                <td class="px-6 py-4 whitespace-nowrap">{{ $permission->student_type_label }}</td>
                            @endif
                            <td class="px-6 py-4 whitespace-nowrap">
                                {{ \Carbon\Carbon::parse($permission->start_date)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($permission->end_date)->format('d/m/Y') }}
                            </td>
                            <td class="px-6 py-4">
                                {{ $permission->reason }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($permission->supporting_document)
                                    <a href="{{ route('documents.permission', $permission) }}" target="_blank" class="text-blue-600 hover:underline">
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
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($permission->status !== 'pending' && $permission->approvedBy)
                                    {{ $permission->approvedBy->full_name }}
                                    <br>
                                    <span class="text-xs text-gray-500">({{ $permission->approvedBy->username ?? $permission->approvedBy->email }})</span>
                                    <br>
                                    <span class="text-xs text-gray-500">({{ $permission->updated_at->format('d/m/Y H:i') }})</span>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <div class="flex items-center space-x-2">
                                    @if($permission->status === 'pending')
                                        <form action="{{ route('admin.permissions.approve', $permission) }}" method="POST" class="inline-block">
                                            @csrf
                                            <button type="submit" class="text-green-600 hover:text-green-900" title="Setujui" onclick="return confirm('Apakah Anda yakin ingin menyetujui izin ini?')">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                            </button>
                                        </form>
                                        <form action="{{ route('admin.permissions.reject', $permission) }}" method="POST" class="inline-block">
                                            @csrf
                                            <button type="submit" class="text-red-600 hover:text-red-900" title="Tolak" onclick="return confirm('Apakah Anda yakin ingin menolak izin ini?')">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                            </button>
                                        </form>
                                    @endif

                                    @if(request()->routeIs('admin.permissions.students') || request()->routeIs('admin.permissions.students.*'))
                                        <a href="{{ route('admin.permissions.students.edit', $permission) }}" class="text-blue-600 hover:text-blue-900" title="Edit">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                        </a>
                                        <form action="{{ route('admin.permissions.students.destroy', $permission) }}" method="POST" class="inline-block">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900" title="Hapus" onclick="return confirm('Apakah Anda yakin ingin menghapus izin ini?')">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                            </button>
                                        </form>
                                    @else
                                        <a href="{{ route('admin.permissions.employees.edit', $permission) }}" class="text-blue-600 hover:text-blue-900" title="Edit">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                        </a>
                                        <form action="{{ route('admin.permissions.employees.destroy', $permission) }}" method="POST" class="inline-block">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900" title="Hapus" onclick="return confirm('Apakah Anda yakin ingin menghapus izin ini?')">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ request()->routeIs('admin.permissions.students') ? 9 : 8 }}" class="px-6 py-4 text-center text-gray-500">
                                Tidak ada permohonan izin.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Custom Pagination with per_page selector -->
        @if ($permissions instanceof \Illuminate\Pagination\LengthAwarePaginator)
            {{ $permissions->appends(request()->query())->links('vendor.pagination.tailwind-custom') }}
        @else
            <!-- For "Show All" records -->
            <div class="p-4 border-t">
                <div class="flex flex-col md:flex-row items-center justify-between space-y-4 md:space-y-0">
                    <div class="flex items-center space-x-2">
                        <label for="per_page" class="text-sm text-gray-700">Tampilkan:</label>
                        <select id="per_page" name="per_page" 
                                class="border border-gray-300 rounded px-2 py-1 text-sm focus:ring-blue-500 focus:border-blue-500"
                                onchange="updatePerPage(this)">
                            <option value="10" {{ request('per_page') == '10' ? 'selected' : '' }}>10</option>
                            <option value="20" {{ !request('per_page') || request('per_page') == '20' ? 'selected' : '' }}>20</option>
                            <option value="30" {{ request('per_page') == '30' ? 'selected' : '' }}>30</option>
                            <option value="50" {{ request('per_page') == '50' ? 'selected' : '' }}>50</option>
                            <option value="100" {{ request('per_page') == '100' ? 'selected' : '' }}>100</option>
                            <option value="all" selected>Semua</option>
                        </select>
                        <span class="text-sm text-gray-700">entri</span>
                        
                        <div class="text-sm text-gray-700 ml-4">
                            Menampilkan semua <span class="font-medium">{{ $permissions->count() }}</span> entri
                        </div>
                    </div>
                </div>
            </div>
            
            <script>
                function updatePerPage(selectElement) {
                    const perPage = selectElement.value;
                    const url = new URL(window.location);
                    const params = new URLSearchParams(url.search);
                    
                    if (perPage && perPage !== 'all') {
                        params.set('per_page', perPage);
                    } else {
                        params.delete('per_page');
                    }
                    
                    // Reset to first page when changing per_page
                    params.delete('page');
                    
                    url.search = params.toString();
                    window.location.href = url.toString();
                }
            </script>
        @endif
    </div>
</div>
@endsection