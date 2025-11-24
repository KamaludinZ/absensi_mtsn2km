@extends('layouts.app')

@section('title', 'Pengaturan Gaji Pegawai')
@section('page-title', 'Pengaturan Gaji Pegawai')

@section('content')
<div class="w-full bg-gray-50 min-h-screen">
    <div class="max-w-full mx-auto px-0 sm:px-0">
        @if(session('success'))
            <div class="w-full p-4 bg-green-100 border-b border-green-400 text-green-700">
                {{ session('success') }}
            </div>
        @endif

        <div class="w-full bg-white min-h-screen shadow-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <div>
                        <h2 class="text-xl font-bold text-gray-800">Pengaturan Gaji Pegawai</h2>
                        <p class="text-sm text-gray-600 mt-1">Atur komponen gaji untuk masing-masing pegawai</p>
                    </div>
                </div>
            </div>

        <!-- Bulk Salary Settings Form -->
        <div class="p-6 bg-gray-50 border-b border-gray-200">
            <h3 class="text-lg font-semibold mb-2 text-gray-800">Pengisian Gaji Masal</h3>
            <p class="text-sm text-gray-600 mb-4">Gunakan formulir di bawah untuk menerapkan pengaturan gaji yang sama ke beberapa atau semua pegawai sekaligus.</p>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Gaji Pokok</label>
                    <input type="number" name="bulk_base_salary" id="bulk_base_salary"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="Rp 0" min="0">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Uang Makan</label>
                    <input type="number" name="bulk_meal_allowance" id="bulk_meal_allowance"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="Rp 0" min="0">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Uang Transport</label>
                    <input type="number" name="bulk_transport_allowance" id="bulk_transport_allowance"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="Rp 0" min="0">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tunjangan Kinerja</label>
                    <input type="number" name="bulk_performance_allowance" id="bulk_performance_allowance"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="Rp 0" min="0">
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-4">
                <button type="button" id="apply-bulk-settings" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    Terapkan ke Semua Pegawai
                </button>
                <button type="button" id="apply-bulk-selected" class="bg-purple-600 text-white px-4 py-2 rounded-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                    Terapkan ke Pegawai Terpilih
                </button>
                <button type="button" id="enable-edit-mode" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                    Edit Gaji Pegawai
                </button>
                <button type="submit" id="save-changes" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 hidden">
                    Simpan Perubahan
                </button>
                <button type="button" id="cancel-edit" class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 hidden">
                    Batal
                </button>
            </div>

            <div id="selected-employees-info" class="mt-4 text-sm text-blue-600 bg-blue-50 p-3 rounded-md hidden">
                <span id="selected-count">0</span> pegawai terpilih
            </div>
        </div>

        <!-- Explanation note -->
        <div class="p-4 bg-blue-50 border-b border-blue-100">
            <div class="flex items-start">
                <svg class="w-5 h-5 text-blue-500 mt-0.5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="text-sm text-blue-700">
                    <strong>Catatan:</strong> Klik tombol "Edit Gaji Pegawai" untuk mengaktifkan mode edit dan mengubah nilai gaji.
                    Gunakan tombol "Simpan Perubahan" untuk menyimpan data setelah selesai mengedit.
                </p>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.employees.update-salary-settings') }}">
            @csrf

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1">
                                <input type="checkbox" id="select-all" class="rounded border-gray-300 focus:ring-blue-500">
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider min-w-[200px]">Nama Pegawai</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider min-w-[150px]">Jabatan</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider min-w-[150px]">Gaji Pokok</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider min-w-[150px]">Uang Makan</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider min-w-[150px]">Uang Transport</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider min-w-[150px]">Tunjangan Kinerja</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($employees as $employee)
                            @php
                                $salary = $employee->salary;
                            @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-3 whitespace-nowrap">
                                    <input type="checkbox" name="selected_employees[]" value="{{ $employee->id }}" class="employee-checkbox rounded border-gray-300 focus:ring-blue-500">
                                </td>
                                <td class="px-6 py-3 whitespace-nowrap">
                                    <div class="font-medium text-gray-900">{{ $employee->full_name }}</div>
                                    <div class="text-sm text-gray-500">{{ $employee->nip ? '(' . $employee->nip . ')' : '' }}</div>
                                </td>
                                <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-900">
                                    {{ $employee->position }}
                                </td>
                                <td class="px-6 py-3 whitespace-nowrap">
                                    <input type="number"
                                           name="salaries[{{ $employee->id }}][base_salary]"
                                           value="{{ old("salaries.{$employee->id}.base_salary", $salary ? intval($salary->base_salary) : 0) }}"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 disabled:cursor-not-allowed disabled:bg-gray-50"
                                           min="0">
                                </td>
                                <td class="px-6 py-3 whitespace-nowrap">
                                    <input type="number"
                                           name="salaries[{{ $employee->id }}][meal_allowance]"
                                           value="{{ old("salaries.{$employee->id}.meal_allowance", $salary ? intval($salary->meal_allowance) : 0) }}"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 disabled:cursor-not-allowed disabled:bg-gray-50"
                                           min="0">
                                </td>
                                <td class="px-6 py-3 whitespace-nowrap">
                                    <input type="number"
                                           name="salaries[{{ $employee->id }}][transport_allowance]"
                                           value="{{ old("salaries.{$employee->id}.transport_allowance", $salary ? intval($salary->transport_allowance) : 0) }}"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 disabled:cursor-not-allowed disabled:bg-gray-50"
                                           min="0">
                                </td>
                                <td class="px-6 py-3 whitespace-nowrap">
                                    <input type="number"
                                           name="salaries[{{ $employee->id }}][performance_allowance]"
                                           value="{{ old("salaries.{$employee->id}.performance_allowance", $salary ? intval($salary->performance_allowance) : 0) }}"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 disabled:cursor-not-allowed disabled:bg-gray-50"
                                           min="0">
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                                    <div class="flex flex-col items-center justify-center">
                                        <svg class="w-12 h-12 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <span>Tidak ada data pegawai.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Save Button at Bottom -->
            <div class="p-4 border-t bg-gray-50 text-right">
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Select all elements
    const selectAllCheckbox = document.getElementById('select-all');
    const employeeCheckboxes = document.querySelectorAll('.employee-checkbox');
    const bulkBaseSalary = document.getElementById('bulk_base_salary');
    const bulkMealAllowance = document.getElementById('bulk_meal_allowance');
    const bulkTransportAllowance = document.getElementById('bulk_transport_allowance');
    const bulkPerformanceAllowance = document.getElementById('bulk_performance_allowance');
    const applyBulkSettingsBtn = document.getElementById('apply-bulk-settings');
    const applyBulkSelectedBtn = document.getElementById('apply-bulk-selected');
    const selectedEmployeesInfo = document.getElementById('selected-employees-info');
    const selectedCountSpan = document.getElementById('selected-count');
    const enableEditModeBtn = document.getElementById('enable-edit-mode');
    const saveChangesBtn = document.getElementById('save-changes');
    const cancelEditBtn = document.getElementById('cancel-edit');
    const allSalaryInputs = document.querySelectorAll('input[name*="[base_salary]"], input[name*="[meal_allowance]"], input[name*="[transport_allowance]"], input[name*="[performance_allowance]"]');

    // Initially disable all salary input fields
    allSalaryInputs.forEach(input => {
        input.disabled = true;
        input.classList.add('bg-gray-100', 'cursor-not-allowed');
    });

    // Toggle all employee checkboxes when select all is clicked
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            employeeCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateSelectedCount();
        });
    }

    // Update selected count when individual checkboxes change
    employeeCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateSelectedCount();
        });
    });

    // Function to update selected count display
    function updateSelectedCount() {
        const selectedCount = document.querySelectorAll('.employee-checkbox:checked').length;
        selectedCountSpan.textContent = selectedCount;

        if (selectedCount > 0) {
            selectedEmployeesInfo.classList.remove('hidden');
            applyBulkSelectedBtn.disabled = false;
            applyBulkSelectedBtn.classList.remove('disabled:opacity-50', 'disabled:cursor-not-allowed');
        } else {
            selectedEmployeesInfo.classList.add('hidden');
            applyBulkSelectedBtn.disabled = true;
            applyBulkSelectedBtn.classList.add('disabled:opacity-50', 'disabled:cursor-not-allowed');
        }
    }

    // Enable edit mode
    if (enableEditModeBtn) {
        enableEditModeBtn.addEventListener('click', function() {
            // Enable all salary input fields
            allSalaryInputs.forEach(input => {
                input.disabled = false;
                input.classList.remove('bg-gray-100', 'cursor-not-allowed');
            });

            // Show/hide appropriate buttons
            enableEditModeBtn.classList.add('hidden');
            saveChangesBtn.classList.remove('hidden');
            cancelEditBtn.classList.remove('hidden');
        });
    }

    // Cancel edit mode (revert to view mode)
    if (cancelEditBtn) {
        cancelEditBtn.addEventListener('click', function() {
            // Disable all salary input fields again
            allSalaryInputs.forEach(input => {
                input.disabled = true;
                input.classList.add('bg-gray-100', 'cursor-not-allowed');
            });

            // Refresh the page to restore original values
            location.reload();
        });
    }

    // Apply bulk settings to all employees
    if (applyBulkSettingsBtn) {
        applyBulkSettingsBtn.addEventListener('click', function() {
            // Ensure edit mode is enabled before applying bulk changes
            if (saveChangesBtn.classList.contains('hidden')) {
                alert('Silakan klik tombol "Edit Gaji Pegawai" terlebih dahulu untuk mengaktifkan mode edit.');
                return;
            }

            if (!confirm('Apakah Anda yakin ingin menerapkan pengaturan gaji yang sama ke SEMUA pegawai?')) {
                return;
            }

            const bulkValues = {
                base_salary: bulkBaseSalary.value || 0,
                meal_allowance: bulkMealAllowance.value || 0,
                transport_allowance: bulkTransportAllowance.value || 0,
                performance_allowance: bulkPerformanceAllowance.value || 0
            };

            // Fill all salary fields with bulk values using employee checkboxes
            employeeCheckboxes.forEach(checkbox => {
                const employeeId = checkbox.value;

                // Find the corresponding salary fields for this employee
                const baseSalaryField = document.querySelector(`input[name="salaries[${employeeId}][base_salary]"]`);
                const mealAllowanceField = document.querySelector(`input[name="salaries[${employeeId}][meal_allowance]"]`);
                const transportAllowanceField = document.querySelector(`input[name="salaries[${employeeId}][transport_allowance]"]`);
                const performanceAllowanceField = document.querySelector(`input[name="salaries[${employeeId}][performance_allowance]"]`);

                if (baseSalaryField) baseSalaryField.value = bulkValues.base_salary;
                if (mealAllowanceField) mealAllowanceField.value = bulkValues.meal_allowance;
                if (transportAllowanceField) transportAllowanceField.value = bulkValues.transport_allowance;
                if (performanceAllowanceField) performanceAllowanceField.value = bulkValues.performance_allowance;
            });
        });
    }

    // Apply bulk settings to selected employees only
    if (applyBulkSelectedBtn) {
        applyBulkSelectedBtn.addEventListener('click', function() {
            // Ensure edit mode is enabled before applying bulk changes
            if (saveChangesBtn.classList.contains('hidden')) {
                alert('Silakan klik tombol "Edit Gaji Pegawai" terlebih dahulu untuk mengaktifkan mode edit.');
                return;
            }

            const selectedCheckboxes = document.querySelectorAll('.employee-checkbox:checked');

            if (selectedCheckboxes.length === 0) {
                alert('Silakan pilih setidaknya satu pegawai terlebih dahulu.');
                return;
            }

            if (!confirm(`Apakah Anda yakin ingin menerapkan pengaturan gaji yang sama ke ${selectedCheckboxes.length} pegawai yang dipilih?`)) {
                return;
            }

            const bulkValues = {
                base_salary: bulkBaseSalary.value || 0,
                meal_allowance: bulkMealAllowance.value || 0,
                transport_allowance: bulkTransportAllowance.value || 0,
                performance_allowance: bulkPerformanceAllowance.value || 0
            };

            // Fill only selected employee salary fields with bulk values
            selectedCheckboxes.forEach(checkbox => {
                const employeeId = checkbox.value;

                // Find the corresponding salary fields for this selected employee
                const baseSalaryField = document.querySelector(`input[name="salaries[${employeeId}][base_salary]"]`);
                const mealAllowanceField = document.querySelector(`input[name="salaries[${employeeId}][meal_allowance]"]`);
                const transportAllowanceField = document.querySelector(`input[name="salaries[${employeeId}][transport_allowance]"]`);
                const performanceAllowanceField = document.querySelector(`input[name="salaries[${employeeId}][performance_allowance]"]`);

                if (baseSalaryField) baseSalaryField.value = bulkValues.base_salary;
                if (mealAllowanceField) mealAllowanceField.value = bulkValues.meal_allowance;
                if (transportAllowanceField) transportAllowanceField.value = bulkValues.transport_allowance;
                if (performanceAllowanceField) performanceAllowanceField.value = bulkValues.performance_allowance;
            });
        });
    }

    // Initialize the selected count
    updateSelectedCount();
});
</script>
@endsection