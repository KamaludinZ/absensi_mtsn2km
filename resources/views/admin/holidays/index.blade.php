@extends('layouts.app')

@section('title', 'Hari Libur')
@section('page-title', 'Hari Libur')

@section('content')
<div class="bg-white rounded-lg shadow p-6">
    <div class="mb-6">
        <div class="flex flex-wrap justify-between items-center gap-4 mb-4">
            <h2 class="text-xl font-bold text-gray-800">Daftar Hari Libur</h2>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.holidays.import-form') }}" class="bg-purple-600 text-white px-4 py-2 rounded-md hover:bg-purple-700">
                    Import Excel
                </a>
                <a href="{{ route('admin.holidays.active-days') }}" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">
                    Pengaturan Hari Aktif
                </a>
                <a href="{{ route('admin.holidays.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                    Tambah Hari Libur
                </a>
            </div>
        </div>
        
        <div class="bg-gray-50 p-4 rounded-lg">
            <form method="GET" class="flex flex-wrap items-center gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Bulan</label>
                    <select name="month" class="border border-gray-300 rounded px-3 py-2">
                        @for($i = 1; $i <= 12; $i++)
                            <option value="{{ $i }}" {{ $i == ($month ?? now()->month) ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::create()->month($i)->translatedFormat('F') }}
                            </option>
                        @endfor
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tahun</label>
                    <select name="year" class="border border-gray-300 rounded px-3 py-2">
                        @for($y = now()->year - 2; $y <= now()->year + 2; $y++)
                            <option value="{{ $y }}" {{ (int) $y === (int) ($year ?? now()->year) ? 'selected' : '' }}>
                                {{ $y }}
                            </option>
                        @endfor
                    </select>
                </div>
                
                <div class="pt-6">
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                        Filter
                    </button>
                    <a href="{{ route('admin.holidays.index', ['all' => 1]) }}" class="ml-2 bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600">
                        Reset
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        <input type="checkbox" id="selectAll" class="h-4 w-4 text-blue-600 rounded focus:ring-blue-500">
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deskripsi</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($holidays as $holiday)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <input type="checkbox" name="holiday_ids[]" value="{{ $holiday->id }}" class="holiday-checkbox h-4 w-4 text-blue-600 rounded focus:ring-blue-500">
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ $holiday->name }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ $holiday->formattedDate }}</td>
                    <td class="px-6 py-4">{{ $holiday->description ?? '-' }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                            {{ $holiday->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $holiday->is_active ? 'Aktif' : 'Tidak Aktif' }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <a href="{{ route('admin.holidays.edit', $holiday) }}" class="text-blue-600 hover:text-blue-900 mr-3">Edit</a>
                        <form action="{{ route('admin.holidays.destroy', $holiday) }}" method="POST" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus hari libur ini?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-900">Hapus</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">Tidak ada data hari libur</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <!-- Bulk Action Controls -->
    @if(count($holidays) > 0)
    <div class="mt-4 p-4 bg-gray-50 rounded-lg flex items-center justify-between">
        <div class="flex items-center space-x-4">
            <span id="selectedCount" class="text-sm text-gray-600">0 item dipilih</span>
            <div class="flex items-center space-x-2">
                <select id="bulkAction" class="border border-gray-300 rounded px-2 py-1 text-sm">
                    <option value="">Pilih aksi...</option>
                    <option value="activate">Aktifkan</option>
                    <option value="deactivate">Nonaktifkan</option>
                </select>
                <button id="applyBulkAction" class="bg-blue-600 text-white px-3 py-1 rounded text-sm hover:bg-blue-700 disabled:opacity-50" disabled>
                    Terapkan
                </button>
            </div>
        </div>
    </div>
    @endif
    
    <div class="mt-6">
        {{ $holidays->links() }}
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAllCheckbox = document.getElementById('selectAll');
    const holidayCheckboxes = document.querySelectorAll('.holiday-checkbox');
    const bulkActionSelect = document.getElementById('bulkAction');
    const applyBulkActionBtn = document.getElementById('applyBulkAction');
    const selectedCountSpan = document.getElementById('selectedCount');

    // Function to update selected count and enable/disable bulk action button
    function updateBulkActionControls() {
        const selectedCheckboxes = document.querySelectorAll('.holiday-checkbox:checked');
        const count = selectedCheckboxes.length;
        
        selectedCountSpan.textContent = `${count} item dipilih`;
        
        if (count > 0 && bulkActionSelect.value) {
            applyBulkActionBtn.disabled = false;
            applyBulkActionBtn.classList.remove('disabled:opacity-50');
            applyBulkActionBtn.classList.add('opacity-100');
        } else {
            applyBulkActionBtn.disabled = true;
            applyBulkActionBtn.classList.add('disabled:opacity-50');
            applyBulkActionBtn.classList.remove('opacity-100');
        }
    }

    // Event listener for individual checkboxes
    holidayCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            // Update the select all checkbox state
            const allChecked = Array.from(holidayCheckboxes).every(cb => cb.checked);
            const anyChecked = Array.from(holidayCheckboxes).some(cb => cb.checked);
            
            selectAllCheckbox.checked = allChecked;
            selectAllCheckbox.indeterminate = !allChecked && anyChecked;
            
            updateBulkActionControls();
        });
    });

    // Event listener for select all checkbox
    selectAllCheckbox.addEventListener('change', function() {
        const isChecked = selectAllCheckbox.checked;
        
        holidayCheckboxes.forEach(checkbox => {
            checkbox.checked = isChecked;
        });
        
        updateBulkActionControls();
    });

    // Event listener for bulk action select
    bulkActionSelect.addEventListener('change', updateBulkActionControls);

    // Event listener for apply bulk action button
    applyBulkActionBtn.addEventListener('click', function() {
        const selectedCheckboxes = document.querySelectorAll('.holiday-checkbox:checked');
        const action = bulkActionSelect.value;
        
        if (selectedCheckboxes.length === 0) {
            alert('Silakan pilih setidaknya satu hari libur.');
            return;
        }

        if (!action) {
            alert('Silakan pilih aksi yang akan dilakukan.');
            return;
        }

        if (!confirm(`Apakah Anda yakin ingin ${action === 'activate' ? 'mengaktifkan' : 'menonaktifkan'} ${selectedCheckboxes.length} hari libur yang dipilih?`)) {
            return;
        }

        // Create a form and submit it to the bulk update endpoint
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route('admin.holidays.bulk-update') }}';
        
        // Add CSRF token
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = '{{ csrf_token() }}';
        form.appendChild(csrfInput);

        // Add action
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = action;
        form.appendChild(actionInput);

        // Add selected holiday IDs
        selectedCheckboxes.forEach(checkbox => {
            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'holiday_ids[]';
            idInput.value = checkbox.value;
            form.appendChild(idInput);
        });

        document.body.appendChild(form);
        form.submit();
    });

    // Initialize controls
    updateBulkActionControls();
});
</script>
@endsection