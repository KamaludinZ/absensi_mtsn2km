@extends('layouts.app')

@section('title', 'Pengaturan Shift Pegawai')
@section('page-title', 'Pengaturan Shift Pegawai')

@section('content')
<div class="bg-white rounded-lg shadow p-6">
    <div class="flex flex-wrap justify-between items-center mb-6 gap-4">
        <h2 class="text-xl font-bold text-gray-800">Pengaturan Shift Pegawai</h2>
        
        <!-- Month/Year Filter -->
        <form method="GET" class="flex flex-wrap items-center gap-2">
            <select name="month" class="border border-gray-300 rounded px-3 py-2">
                @for($i = 1; $i <= 12; $i++)
                    <option value="{{ $i }}" {{ $i == $month ? 'selected' : '' }}>
                        {{ \Carbon\Carbon::create()->month($i)->translatedFormat('F') }}
                    </option>
                @endfor
            </select>
            
            <select name="year" class="border border-gray-300 rounded px-3 py-2">
                @for($y = now()->year - 2; $y <= now()->year + 2; $y++)
                    <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>
                        {{ $y }}
                    </option>
                @endfor
            </select>
            
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                Tampilkan
            </button>
        </form>
    </div>
    
    <form action="{{ route('admin.employee-shifts.store') }}" method="POST" id="employeeShiftForm">
        @csrf
        <input type="hidden" name="month" value="{{ $month }}">
        <input type="hidden" name="year" value="{{ $year }}">
        
        <!-- Action Buttons -->
        <div class="mb-6 flex flex-wrap items-center gap-2">
            <button type="button" id="editShiftBtn" class="bg-yellow-600 text-white px-4 py-2 rounded-md hover:bg-yellow-700">
                Edit Pengaturan Shift
            </button>
            <button type="submit" form="employeeShiftForm" id="saveShiftBtn" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 hidden">
                Simpan Pengaturan Shift
            </button>
            <button type="button" id="cancelEditBtn" class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600 hidden">
                Batal
            </button>
            <a href="{{ route('admin.employee-shifts.export', ['month' => $month, 'year' => $year]) }}"
               id="exportExcelBtn"
               class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 inline-flex items-center gap-2 hidden">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <span>Export Excel</span>
            </a>
            <button type="button" id="importExcelBtn" class="bg-purple-600 hover:bg-purple-700 text-white font-medium px-4 py-2 rounded-md inline-flex items-center gap-2 hidden" style="background-color: #7c3aed; color: white;">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 0115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                </svg>
                <span>Import Excel</span>
            </button>
        </div>

        <!-- Debug Info (Remove in production) -->
        @if(config('app.debug'))
        <div class="mb-4 p-3 bg-gray-100 rounded-lg text-xs text-gray-700">
            <strong>Debug Info:</strong>
            <div>Total Employees: {{ $employees->count() }}</div>
            <div>Total Schedules: {{ $schedules->count() }}</div>
            <div>Employees with Shifts: {{ $employeeShifts->keys()->count() }}</div>
            <div>Total Shift Records: {{ $employeeShifts->flatten()->count() }}</div>
            <div>Month/Year: {{ $month }}/{{ $year }}</div>
        </div>
        @endif

        <!-- Instructions -->
        <div class="mb-4 p-3 bg-blue-50 rounded-lg text-sm text-blue-800">
            <p><strong>Instruksi:</strong></p>
            <ul class="list-disc pl-5 mt-1 space-y-1">
                <li>Klik tombol "Edit Pengaturan Shift" untuk mengaktifkan mode edit</li>
                <li>Setelah klik "Edit Pengaturan Shift", tombol "Export Excel" dan "Import Excel" akan muncul</li>
                <li>Gunakan tombol "Export Excel" untuk mengunduh data shift saat ini dalam format Excel</li>
                <li>Gunakan tombol "Import Excel" untuk mengunggah file Excel dan mengubah shift secara massal</li>
                <li><strong>Import Excel:</strong> Data akan langsung disimpan dan diterapkan ke absensi pegawai. Anda dapat melihat hasilnya di tabel dan melakukan edit manual jika diperlukan</li>
                <li>Centang/lepaskan centang pada tanggal untuk mengaktifkan/menonaktifkan hari tersebut</li>
                <li>Jika tanggal dinonaktifkan, maka tidak akan dihitung sebagai hari aktif dan semua pilihan shift untuk tanggal tersebut akan dinonaktifkan</li>
                <li>Pilih shift untuk setiap pegawai pada tanggal yang aktif (untuk edit manual)</li>
                <li>Klik "Simpan Pengaturan Shift" untuk menyimpan perubahan manual dan menerapkan ke data absensi</li>
            </ul>
        </div>
        
        <!-- Table Container with Horizontal Scroll -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50 sticky top-0">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-gray-100">
                            Nama Pegawai
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-gray-100">
                            Jabatan
                        </th>

                        @foreach($dates as $date)
                            @php
                                $dateString = $date->format('Y-m-d');
                                $isDisabled = $disabledDates->contains($dateString);
                            @endphp
                            <th class="px-2 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider bg-gray-100 min-w-[120px] date-header-{{ $dateString }}"
                                data-date="{{ $dateString }}">
                                <div class="flex flex-col items-center">
                                    <span>{{ $date->translatedFormat('d') }}</span>
                                    <span class="text-xs">{{ $date->translatedFormat('l') }}</span>
                                    <div class="mt-1">
                                        <input type="checkbox" 
                                               id="date_toggle_{{ $dateString }}" 
                                               class="date-toggle h-4 w-4 text-blue-600 rounded focus:ring-blue-500 date-checkbox"
                                               data-date="{{ $dateString }}"
                                               {{ $isDisabled ? '' : 'checked' }}>
                                    </div>
                                </div>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($employees as $employee)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10">
                                        <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                                            <span class="text-blue-800 font-medium">{{ substr($employee->full_name, 0, 1) }}</span>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900">{{ $employee->full_name }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $employee->position ?? '-' }}</div>
                            </td>

                            @foreach($dates as $date)
                                @php
                                    $dateString = $date->format('Y-m-d');
                                    $isDisabled = $disabledDates->contains($dateString);
                                @endphp
                                <td class="px-2 py-2 whitespace-nowrap text-center date-cell-{{ $dateString }}"
                                    data-date="{{ $dateString }}">
                                    <select name="shift_{{ $employee->id }}_{{ $date->day }}" 
                                            class="shift-select border border-gray-300 rounded px-1 py-1 text-xs w-full {{ $isDisabled ? 'bg-gray-100 cursor-not-allowed opacity-50' : '' }} shift-selection"
                                            data-date="{{ $dateString }}"
                                            data-employee-id="{{ $employee->id }}"
                                            {{ $isDisabled ? 'disabled' : '' }}>
                                        <option value="">-</option>
                                        @foreach($schedules as $schedule)
                                            <option value="{{ $schedule->id }}" 
                                                    {{ isset($employeeShifts[$employee->id]) && $employeeShifts[$employee->id]->contains(function($shift) use ($date, $schedule) { 
                                                        return $shift->date->format('Y-m-d') === $date->format('Y-m-d') && $shift->schedule_id === $schedule->id; 
                                                    }) ? 'selected' : '' }}>
                                                @if($schedule->shift_name)
                                                    {{ $schedule->shift_name }}
                                                @elseif($schedule->shift_type)
                                                    {{ ucfirst($schedule->shift_type) }}
                                                @else
                                                    {{ $schedule->start_time }}-{{ $schedule->end_time }}
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                            @endforeach
                        </tr>
                    @empty
                    <tr>
                        <td colspan="{{ count($dates) + 2 }}" class="px-6 py-4 text-center text-gray-500">
                            Tidak ada data pegawai aktif
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </form>

    <!-- Import Modal (Outside main form to avoid nested forms) -->
    <div id="importModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Import File Excel</h3>
                <form id="importForm" action="{{ route('admin.employee-shifts.import') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="month" value="{{ $month }}">
                    <input type="hidden" name="year" value="{{ $year }}">

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Pilih File Excel</label>
                        <input type="file" name="file" accept=".xlsx,.xls" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <p class="mt-1 text-xs text-gray-500">Format: .xlsx atau .xls</p>
                    </div>

                    <div class="flex justify-end gap-2">
                        <button type="button" id="closeModalBtn" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                            Batal
                        </button>
                        <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                            Upload & Import
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // DOM Elements
    const editShiftBtn = document.getElementById('editShiftBtn');
    const saveShiftBtn = document.getElementById('saveShiftBtn');
    const cancelEditBtn = document.getElementById('cancelEditBtn');
    const exportExcelBtn = document.getElementById('exportExcelBtn');
    const importExcelBtn = document.getElementById('importExcelBtn');
    const importModal = document.getElementById('importModal');
    const closeModalBtn = document.getElementById('closeModalBtn');
    const form = document.getElementById('employeeShiftForm');
    const dateCheckboxes = document.querySelectorAll('.date-checkbox');
    const shiftSelects = document.querySelectorAll('.shift-selection');

    // Debug: Check if elements exist
    console.log('=== Employee Shifts Debug ===');
    console.log('editShiftBtn:', editShiftBtn);
    console.log('saveShiftBtn:', saveShiftBtn);
    console.log('cancelEditBtn:', cancelEditBtn);
    console.log('exportExcelBtn:', exportExcelBtn);
    console.log('importExcelBtn:', importExcelBtn);
    console.log('importModal:', importModal);
    console.log('Total shift selects:', shiftSelects.length);
    console.log('Total date checkboxes:', dateCheckboxes.length);
    console.log('Form element:', form);

    // State variables
    let isEditMode = false;
    let originalDisabledDates = [];
    
    // Update hidden input with current disabled dates
    function updateDisabledDatesInput() {
        const disabledDates = [];
        dateCheckboxes.forEach(checkbox => {
            if (!checkbox.checked) {
                disabledDates.push(checkbox.dataset.date);
            }
        });
        
        // Remove existing hidden inputs
        const existingInputs = form.querySelectorAll('input[name="disabled_dates[]"]');
        existingInputs.forEach(input => input.remove());
        
        // Create new hidden inputs for each disabled date
        disabledDates.forEach(function(date, index) {
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'disabled_dates[]';
            hiddenInput.value = date;
            form.appendChild(hiddenInput);
        });
    }
    
    // Set edit mode
    function setEditMode(enabled) {
        isEditMode = enabled;
        console.log('setEditMode called with enabled:', enabled);

        // Toggle button visibility with safety checks
        if (editShiftBtn) {
            editShiftBtn.classList.toggle('hidden', enabled);
            console.log('Toggled editShiftBtn hidden to:', enabled);
        }
        if (saveShiftBtn) {
            saveShiftBtn.classList.toggle('hidden', !enabled);
            console.log('Toggled saveShiftBtn hidden to:', !enabled);
        }
        if (cancelEditBtn) {
            cancelEditBtn.classList.toggle('hidden', !enabled);
            console.log('Toggled cancelEditBtn hidden to:', !enabled);
        }
        if (exportExcelBtn) {
            exportExcelBtn.classList.toggle('hidden', !enabled);
            console.log('Toggled exportExcelBtn hidden to:', !enabled);
        }
        if (importExcelBtn) {
            importExcelBtn.classList.toggle('hidden', !enabled);
            console.log('Toggled importExcelBtn hidden to:', !enabled);
        }
        
        // Toggle form elements
        if (enabled) {
            // Store original values before enabling edit mode
            storeOriginalValues();
            
            // Enable all form elements in edit mode
            shiftSelects.forEach(select => {
                const date = select.dataset.date;
                const isDateDisabled = !document.getElementById(`date_toggle_${date}`).checked;
                
                if (isDateDisabled) {
                    select.disabled = true;
                    select.classList.add('bg-gray-100', 'cursor-not-allowed', 'opacity-50');
                } else {
                    select.disabled = false;
                    select.classList.remove('bg-gray-100', 'cursor-not-allowed', 'opacity-50');
                }
            });
            
            dateCheckboxes.forEach(checkbox => {
                checkbox.disabled = false;
            });
        } else {
            // Disable all form elements in view mode
            shiftSelects.forEach(select => {
                select.disabled = true;
                select.classList.add('bg-gray-100', 'cursor-not-allowed', 'opacity-50');
            });
            
            dateCheckboxes.forEach(checkbox => {
                checkbox.disabled = true;
            });
        }
        
        // Update hidden input
        updateDisabledDatesInput();
    }
    
    // Initialize original values on page load
    storeOriginalValues();
    setEditMode(false);

    // Date toggle functionality
    dateCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            if (!isEditMode) return;
            
            const date = this.dataset.date;
            const isChecked = this.checked;
            
            // Update shift selects for this date
            document.querySelectorAll(`.shift-selection[data-date="${date}"]`).forEach(select => {
                if (isChecked) {
                    // Enable shift selection
                    select.disabled = false;
                    select.classList.remove('bg-gray-100', 'cursor-not-allowed', 'opacity-50');
                } else {
                    // Disable shift selection and clear the value
                    select.disabled = true;
                    select.classList.add('bg-gray-100', 'cursor-not-allowed', 'opacity-50');
                    select.value = ''; // Clear selection
                    // Remove the 'selected' attribute if present
                    select.querySelectorAll('option').forEach(option => {
                        option.removeAttribute('selected');
                    });
                    // Ensure empty option is selected
                    if (select.querySelector('option[value=""]')) {
                        select.querySelector('option[value=""]').selected = true;
                    }
                }
            });
            
            // Update hidden input
            updateDisabledDatesInput();
        });
    });
    
    // Edit button click
    editShiftBtn.addEventListener('click', function() {
        console.log('Edit button clicked');
        console.log('exportExcelBtn exists:', !!exportExcelBtn);
        console.log('importExcelBtn exists:', !!importExcelBtn);
        setEditMode(true);
    });
    
    // Store original values when entering edit mode
    function storeOriginalValues() {
        // Store original disabled dates
        originalDisabledDates = [];
        dateCheckboxes.forEach(checkbox => {
            if (!checkbox.checked) {
                originalDisabledDates.push(checkbox.dataset.date);
            }
        });
        
        // Store original shift values
        shiftSelects.forEach(select => {
            select.dataset.originalValue = select.value;
        });
    }
    
    // Cancel button click
    cancelEditBtn.addEventListener('click', function() {
        // Reset checkboxes to original state
        dateCheckboxes.forEach(checkbox => {
            const date = checkbox.dataset.date;
            const wasOriginallyDisabled = originalDisabledDates.includes(date);
            checkbox.checked = !wasOriginallyDisabled;
            
            // Trigger change event to update UI
            checkbox.dispatchEvent(new Event('change'));
        });
        
        // Restore original shift values
        shiftSelects.forEach(select => {
            select.value = select.dataset.originalValue || '';
            
            // Update selected attribute
            select.querySelectorAll('option').forEach(option => {
                if (option.value === select.dataset.originalValue) {
                    option.selected = true;
                } else {
                    option.selected = false;
                }
            });
        });
        
        setEditMode(false);
    });
    
    // Import Excel button click
    if (importExcelBtn && importModal) {
        importExcelBtn.addEventListener('click', function() {
            console.log('Import button clicked');
            importModal.classList.remove('hidden');
        });
    }

    // Close modal button click
    if (closeModalBtn && importModal) {
        closeModalBtn.addEventListener('click', function() {
            importModal.classList.add('hidden');
        });
    }

    // Close modal when clicking outside
    if (importModal) {
        importModal.addEventListener('click', function(e) {
            if (e.target === importModal) {
                importModal.classList.add('hidden');
            }
        });
    }

    // Form submission
    form.addEventListener('submit', function(e) {
        if (!isEditMode) {
            e.preventDefault();
            alert('Silakan klik tombol "Edit Pengaturan Shift" terlebih dahulu.');
            return false;
        }

        if (!confirm('Apakah Anda yakin ingin menyimpan pengaturan shift ini?\n\nPerubahan akan:\n- Menyimpan data shift pegawai sesuai pilihan Anda\n- Menerapkan ke absensi pegawai untuk bulan ini\n- Menghapus dan membuat ulang data absensi sesuai shift yang dipilih\n\nCatatan: Jika Anda sudah melakukan import Excel sebelumnya, data import akan ditimpa dengan data yang Anda pilih di form ini.')) {
            e.preventDefault();
            return false;
        }

        // IMPORTANT: Enable all selects before submission
        // Disabled form elements are NOT sent to server
        // But we keep checkboxes as is since disabled dates should not be submitted
        shiftSelects.forEach(select => {
            select.disabled = false;
        });

        console.log('Form is being submitted with all selects enabled');

        // Form will submit normally, no need to prevent default
        // The page will reload after server processes the request
    });
});
</script>
@endsection