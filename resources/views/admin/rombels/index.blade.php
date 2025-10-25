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

    $currentSort = $sortField ?? request('sort', 'name');
    $currentDirection = $sortDirection ?? request('direction', 'asc');
@endphp

@extends('layouts.app')

@section('title', 'Rombel')
@section('page-title', 'Rombel')

@section('content')
<div class="bg-white rounded-lg shadow">
    <div class="px-6 py-4 border-b flex justify-between items-center">
        <h2 class="text-lg font-semibold text-gray-800">Daftar Rombel</h2>
        <div class="flex space-x-3">
            <a href="{{ route('admin.rombels.import-form') }}" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 text-sm">
                Import Excel
            </a>
            <button onclick="openCreateModal()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 text-sm">
                Tambah Rombel
            </button>
        </div>
    </div>

    <div class="p-6">
        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                {{ session('error') }}
            </div>
        @endif

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{!! sortableLink('Nama', 'name', $currentSort, $currentDirection) !!}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{!! sortableLink('Tingkat Kelas', 'grade_name', $currentSort, $currentDirection) !!}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{!! sortableLink('Tahun Pelajaran', 'year_name', $currentSort, $currentDirection) !!}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($rombels as $rombel)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">{{ $rombel->name }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-500">{{ $rombel->grade ? $rombel->grade->name . ' - ' . $rombel->grade->description : '-' }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-500">{{ $rombel->academicYear->year_name }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button onclick="openEditModal({{ $rombel->id }})" class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>
                            <form action="{{ route('admin.rombels.destroy', $rombel) }}" method="POST" class="inline-block" 
                                onsubmit="return confirm('Apakah Anda yakin ingin menghapus rombel ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900">Hapus</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-6 py-4 text-center text-gray-500">Tidak ada data rombel.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $rombels->links() }}
        </div>
    </div>
</div>

<!-- Create/Edit Modal -->
<div id="rombelModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg w-full max-w-md mx-4">
        <div class="p-6">
            <h3 id="modalTitle" class="text-lg font-medium text-gray-900 mb-4">Tambah Rombel</h3>
            
            <form id="rombelForm" method="POST">
                @csrf
                @method('POST')
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tahun Pelajaran</label>
                    <select name="academic_year_id" required class="w-full px-4 py-2 border rounded-lg">
                        <option value="">Pilih Tahun Pelajaran</option>
                        @foreach($academicYears as $year)
                        <option value="{{ $year->id }}">{{ $year->year_name }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tingkat Kelas</label>
                    <select name="grade_id" required class="w-full px-4 py-2 border rounded-lg">
                        <option value="">Pilih Tingkat Kelas</option>
                        @foreach($grades as $grade)
                        <option value="{{ $grade->id }}">{{ $grade->name }} - {{ $grade->description }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nama Rombel</label>
                    <input type="text" name="name" required class="w-full px-4 py-2 border rounded-lg">
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function openCreateModal() {
        document.getElementById('modalTitle').textContent = 'Tambah Rombel';
        document.getElementById('rombelForm').action = '{{ route('admin.rombels.store') }}';
        document.getElementById('rombelForm').method = 'POST';
        document.getElementById('rombelForm').reset();
        document.getElementById('rombelForm').querySelector('input[name="_method"]').value = 'POST';
        document.getElementById('rombelModal').classList.remove('hidden');
    }
    
    function openEditModal(rombelId) {
        document.getElementById('modalTitle').textContent = 'Edit Rombel';
        document.getElementById('rombelForm').action = '/admin/rombels/' + rombelId;
        document.getElementById('rombelForm').method = 'POST';
        document.getElementById('rombelForm').querySelector('input[name="_method"]').value = 'PUT';
        
        // Fetch existing data
        fetch('/admin/rombels/' + rombelId + '/edit')
            .then(response => response.json())
            .then(data => {
                document.querySelector('select[name="academic_year_id"]').value = data.academic_year_id;
                document.querySelector('select[name="grade_id"]').value = data.grade_id;
                document.querySelector('input[name="name"]').value = data.name;
            })
            .catch(error => console.error('Error:', error));
        
        document.getElementById('rombelModal').classList.remove('hidden');
    }
    
    function closeModal() {
        document.getElementById('rombelModal').classList.add('hidden');
    }
    
    // Close modal when clicking outside
    document.getElementById('rombelModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeModal();
        }
    });
</script>
@endsection