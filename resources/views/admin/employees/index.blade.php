@php
    $currentSort = $sortField ?? request('sort', 'full_name');
    $currentDirection = $sortDirection ?? request('direction', 'asc');
@endphp

@extends('layouts.app')
@section('title', 'Data Pegawai')
@section('page-title', 'Data Pegawai')
@section('content')

<!-- Employee Summary Cards -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 md:gap-6 mb-8">
    <div class="bg-blue-50 border border-blue-100 rounded-lg p-4 md:p-6 shadow-sm">
        <div class="text-2xl md:text-3xl font-bold text-blue-800">{{ $totalEmployees }}</div>
        <div class="text-sm text-blue-600 mt-2">Total GTK</div>
    </div>
    <div class="bg-green-50 border border-green-100 rounded-lg p-4 md:p-6 shadow-sm">
        <div class="text-2xl md:text-3xl font-bold text-green-800">{{ $guruEmployees }}</div>
        <div class="text-sm text-green-600 mt-2">Jumlah Guru</div>
    </div>
    <div class="bg-purple-50 border border-purple-100 rounded-lg p-4 md:p-6 shadow-sm">
        <div class="text-2xl md:text-3xl font-bold text-purple-800">{{ $staffEmployees }}</div>
        <div class="text-sm text-purple-600 mt-2">Jumlah Staf</div>
    </div>
    <div class="bg-yellow-50 border border-yellow-100 rounded-lg p-4 md:p-6 shadow-sm">
        <div class="text-2xl md:text-3xl font-bold text-yellow-800">{{ $activeEmployees }}</div>
        <div class="text-sm text-yellow-600 mt-2">Aktif ({{ $inactiveEmployees }} Tidak Aktif)</div>
    </div>
</div>

<div class="mb-4 flex justify-between">
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('admin.employees.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">Tambah Pegawai</a>
        <a href="{{ route('admin.employees.import-form') }}" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">Import Excel</a>
    </div>
</div>

<!-- Filters -->
<div class="mb-4 bg-white p-4 rounded-lg shadow">
    <form method="GET" action="{{ route('admin.employees.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
            <select name="is_active" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                <option value="">Semua Status</option>
                <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Aktif</option>
                <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Tidak Aktif</option>
            </select>
        </div>

        <div class="flex items-end">
            <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">Filter</button>
        </div>
    </form>
</div>
<div class="bg-white rounded-lg shadow overflow-hidden">
    <!-- Mobile view for smaller screens -->
    <div class="block md:hidden">
        <div class="divide-y divide-gray-200">
            @forelse($employees as $employee)
            <div class="p-4 border-b">
                <div class="flex justify-between items-start">
                    <div>
                        <div class="text-sm font-medium text-gray-900">
                            ID Pegawai: {{ $employee->id }} | {{ $employee->full_name }}
                        </div>
                        <div class="mt-1 text-sm text-gray-500">
                            NIP: {{ $employee->nip ?? 'N/A' }}
                        </div>
                        <div class="mt-1 text-sm text-gray-500">
                            Jabatan: {{ $employee->position }}
                        </div>
                        <div class="mt-1 text-sm text-gray-500">
                            Username: {{ $employee->user ? $employee->user->username : '-' }}
                        </div>
                        <div class="mt-1">
                            <span class="px-2 py-1 text-xs rounded {{ $employee->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $employee->is_active ? 'Aktif' : 'Tidak Aktif' }}
                            </span>
                        </div>
                    </div>
                    <div class="ml-4 flex-shrink-0">
                        <a href="{{ route('admin.employees.edit', $employee) }}" class="text-blue-600 hover:text-blue-800 text-sm block">Edit</a>
                        <form action="{{ route('admin.employees.destroy', $employee) }}" method="POST" class="inline mt-1">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-800 text-sm block" onclick="return confirm('Yakin hapus?')">Hapus</button>
                        </form>
                    </div>
                </div>
            </div>
            @empty
            <div class="p-4 text-center text-gray-500">
                Tidak ada data
            </div>
            @endforelse
        </div>
    </div>
    
    <!-- Desktop view for larger screens -->
    <div class="hidden md:block overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID Pegawai</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{!! sortableLink('Nama', 'full_name', $currentSort, $currentDirection) !!}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{!! sortableLink('NIP', 'nip', $currentSort, $currentDirection) !!}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{!! sortableLink('Jabatan', 'position', $currentSort, $currentDirection) !!}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{!! sortableLink('Username', 'username', $currentSort, $currentDirection) !!}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{!! sortableLink('Status', 'is_active', $currentSort, $currentDirection) !!}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($employees as $employee)
                <tr>
                    <td class="px-6 py-4">{{ $employee->id }}</td>
                    <td class="px-6 py-4">{{ $employee->full_name }}</td>
                    <td class="px-6 py-4">{{ $employee->nip ?? 'N/A' }}</td>
                    <td class="px-6 py-4">{{ $employee->position }}</td>
                    <td class="px-6 py-4">
                        {{ $employee->user ? $employee->user->username : '-' }}
                    </td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 text-xs rounded {{ $employee->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $employee->is_active ? 'Aktif' : 'Tidak Aktif' }}
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <a href="{{ route('admin.employees.edit', $employee) }}" class="text-blue-600 hover:text-blue-800">Edit</a>
                        <form action="{{ route('admin.employees.destroy', $employee) }}" method="POST" class="inline">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-800 ml-2" onclick="return confirm('Yakin hapus?')">Hapus</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="px-6 py-4 text-center text-gray-500">Tidak ada data</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Custom Pagination with per_page selector -->
@if ($employees instanceof \Illuminate\Pagination\LengthAwarePaginator)
    {{ $employees->appends(request()->query())->links('vendor.pagination.tailwind-custom') }}
@else
    <!-- For "Show All" records -->
    <div class="mt-6">
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
                    Menampilkan semua <span class="font-medium">{{ $employees->count() }}</span> entri
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

<script>
function togglePassword(employeeId) {
    const passwordElement = document.getElementById('password-' + employeeId);
    const eyeIcon = document.getElementById('eye-icon-' + employeeId);
    
    if (passwordElement.textContent === '********') {
        // In a real scenario, for security you wouldn't reveal the actual password
        // Here we'll just show a placeholder to indicate the functionality
        passwordElement.textContent = '[Disimpan | Diubah: 2025-01-15]';
        eyeIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-1.563 3.029m-9.344 9.344A10.03 10.03 0 0112 19c-4.478 0-8.268-2.943-9.543-7a10.03 10.03 0 011.563-3.029m9.344-9.344A10.05 10.05 0 0012 5c-4.478 0-8.268 2.943-9.543 7a10.05 10.05 0 001.563 3.029m-9.344 9.344z" />';
    } else {
        // Hide password
        passwordElement.textContent = '********';
    }
}
</script>

@endsection
