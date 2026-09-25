@php
    function sortableLink($label, $sortField, $currentSort, $currentDirection) {
        $newDirection = 'asc';
        $icon = '';

        if ($currentSort == $sortField) {
            // If this column is currently sorted
            $newDirection = $currentDirection == 'asc' ? 'desc' : 'asc';
            $icon = $currentDirection == 'asc' ? ' ↑' : ' ↓';
        }

        // Build query string preserving filters
        $params = request()->except(['sort', 'direction', 'page']);
        $params['sort'] = $sortField;
        $params['direction'] = $newDirection;

        $url = request()->url() . '?' . http_build_query($params);

        $class = $currentSort == $sortField ? 'text-blue-600 font-semibold' : 'text-gray-700 hover:text-blue-600';

        return '<a href="' . $url . '" class="' . $class . '">' . $label . $icon . '</a>';
    }

    $currentSort = $sortField ?? request('sort', 'full_name');
    $currentDirection = $sortDirection ?? request('direction', 'asc');
@endphp

@extends('layouts.app')
@section('title', 'Data Siswa')
@section('page-title', 'Data Siswa')
@section('container-class', '')
@section('content')
<div class="w-full py-6">
    <div class="mb-4 flex flex-wrap gap-2">
    <a href="{{ route('admin.students.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">Tambah Siswa</a>
    <a href="{{ route('admin.students.import-form') }}" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">Import Excel</a>
    <a href="{{ route('admin.students.rfid.index') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700">Kartu RFID</a>
    <a href="{{ route('admin.students.promotion.index') }}" class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700">Kenaikan Kelas</a>
</div>

<!-- Filters -->
<div class="mb-4 bg-white p-4 rounded-lg shadow">
    <form method="GET" action="{{ route('admin.students.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Pencarian Nama</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama siswa..." 
                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Jenjang</label>
            <select name="grade_id" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                <option value="">Semua Jenjang</option>
                @foreach($grades as $grade)
                    <option value="{{ $grade->id }}" {{ request('grade_id') == $grade->id ? 'selected' : '' }}>
                        {{ $grade->description }}
                    </option>
                @endforeach
            </select>
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Rombel</label>
            <select name="rombel_id" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                <option value="">Semua Rombel</option>
                @foreach($rombels as $rombel)
                    <option value="{{ $rombel->id }}" {{ request('rombel_id') == $rombel->id ? 'selected' : '' }}>
                        {{ $rombel->name }}
                    </option>
                @endforeach
            </select>
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
            <select name="is_active" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                <option value="">Semua Status</option>
                <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Aktif</option>
                <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Tidak Aktif</option>
                <option value="graduated" {{ request('is_active') === 'graduated' ? 'selected' : '' }}>Lulus</option>
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
            @forelse($students as $student)
            <div class="p-4 border-b">
                <div class="flex justify-between items-start">
                    <div>
                        <div class="text-sm font-medium text-gray-900">
                            {{ $student->full_name }}
                        </div>
                        <div class="mt-1 text-sm text-gray-500">
                            NISN: {{ $student->nisn ?? '-' }}
                        </div>
                        <div class="mt-1 text-sm text-gray-500">
                            ID Absensi: {{ $student->attendance_id ?? '-' }}
                        </div>
                        <div class="mt-1 text-sm text-gray-500">
                            Tingkat Kelas: {{ $student->rombel->grade->description }}
                        </div>
                        <div class="mt-1 text-sm text-gray-500">
                            Rombel: {{ $student->rombel->name }}
                        </div>
                        <div class="mt-1">
                            @include('admin.students.partials.status-badge')
                        </div>
                    </div>
                    <div class="ml-4 flex-shrink-0">
                        <a href="{{ route('admin.students.edit', $student) }}" class="text-blue-600 hover:text-blue-800 text-sm block">Edit</a>
                        <form action="{{ route('admin.students.destroy', $student) }}" method="POST" class="inline mt-1">
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
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{!! sortableLink('Nama', 'full_name', $currentSort, $currentDirection) !!}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{!! sortableLink('NISN', 'nisn', $currentSort, $currentDirection) !!}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{!! sortableLink('ID Absensi', 'attendance_id', $currentSort, $currentDirection) !!}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{!! sortableLink('Tingkat Kelas', 'grade_name', $currentSort, $currentDirection) !!}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{!! sortableLink('Rombel', 'rombel_name', $currentSort, $currentDirection) !!}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{!! sortableLink('Status', 'is_active', $currentSort, $currentDirection) !!}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($students as $student)
                <tr>
                    <td class="px-6 py-4">{{ $student->full_name }}</td>
                    <td class="px-6 py-4">{{ $student->nisn ?? '-' }}</td>
                    <td class="px-6 py-4">{{ $student->attendance_id ?? '-' }}</td>
                    <td class="px-6 py-4">{{ $student->rombel->grade->description }}</td>
                    <td class="px-6 py-4">{{ $student->rombel->name }}</td>
                    <td class="px-6 py-4">
                        @include('admin.students.partials.status-badge')
                    </td>
                    <td class="px-6 py-4">
                        <a href="{{ route('admin.students.edit', $student) }}" class="text-blue-600 hover:text-blue-800">Edit</a>
                        <form action="{{ route('admin.students.destroy', $student) }}" method="POST" class="inline">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-800 ml-2" onclick="return confirm('Yakin hapus?')">Hapus</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-6 py-4 text-center text-gray-500">Tidak ada data</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
</div>

<!-- Custom Pagination with per_page selector -->
@if ($students instanceof \Illuminate\Pagination\LengthAwarePaginator)
    {{ $students->appends(request()->query())->links('vendor.pagination.tailwind-custom') }}
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
                    Menampilkan semua <span class="font-medium">{{ $students->count() }}</span> entri
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