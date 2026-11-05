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
    
    {{-- Separate form so it is not nested in the shift form; its button sits in the action bar --}}
    <form action="{{ route('admin.employee-shifts.auto-fill') }}" method="POST" id="autoFillForm"
          onsubmit="return confirm('Isi otomatis sel shift yang masih kosong mulai hari ini sampai akhir bulan, sesuai Hari & Jabatan Penerapan di Jadwal Pegawai? Sel yang sudah terisi tidak diubah.');">
        @csrf
        <input type="hidden" name="month" value="{{ $month }}">
        <input type="hidden" name="year" value="{{ $year }}">
    </form>

    <form action="{{ route('admin.employee-shifts.store') }}" method="POST" id="employeeShiftForm">
        @csrf
        <input type="hidden" name="month" value="{{ $month }}">
        <input type="hidden" name="year" value="{{ $year }}">
        
        <!-- Action Buttons -->
        <div class="mb-6 flex flex-wrap items-center gap-2">
            <button type="button" id="editShiftBtn" class="bg-yellow-600 text-white px-4 py-2 rounded-md hover:bg-yellow-700">
                Edit Pengaturan Shift
            </button>
            @if($hasAutoRules ?? false)
                <button type="submit" form="autoFillForm" id="autoFillBtn" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700"
                        title="Isi sel kosong sesuai Hari & Jabatan Penerapan di Jadwal Pegawai">
                    Terapkan Otomatis
                </button>
            @endif
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
                <li>Centang tanggal mengikuti <a href="{{ route('admin.holidays.active-days') }}" class="underline">Hari Aktif</a> dan <a href="{{ route('admin.holidays.index') }}" class="underline">Hari Libur</a> secara otomatis. Tanggal tanpa centang (merah) tertutup: shift tidak bisa diisi dan tidak diubah saat menyimpan</li>
                <li>Bila hari libur ditambahkan atau hari aktif dikurangi, shift di tanggal itu dilepas otomatis, kecuali pegawai yang sudah absen pada hari itu</li>
                <li>Hanya shift yang bernama yang bisa dipilih</li>
                <li>Pilih shift untuk setiap pegawai pada tanggal yang aktif (untuk edit manual)</li>
                <li>Klik "Simpan Pengaturan Shift" untuk menyimpan perubahan manual dan menerapkan ke data absensi</li>
                <li><strong>Otomatis:</strong> shift yang punya Hari &amp; Jabatan Penerapan di <a href="{{ route('admin.schedules.custom.pegawai') }}" class="underline">Jadwal Pegawai</a> diisikan sendiri setiap hari untuk pegawai aktif, mulai hari ini sampai akhir bulan, hanya pada hari aktif yang bukan hari libur. Sel yang sudah terisi tidak ditimpa, dan sel yang Anda kosongkan tidak diisi ulang.</li>
                <li>Gunakan "Terapkan Otomatis" setelah menambah pegawai atau mengubah hari/jabatan penerapan untuk mengisi sel yang masih kosong.</li>
                <li>Menghapus shift dari seorang pegawai juga menghapus baris absensi kosongnya di hari itu; absensi yang sudah berisi jam masuk/pulang tidak pernah dihapus.</li>
            </ul>
        </div>
        
        @if(($namelessCount ?? 0) > 0)
            <div class="mb-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg text-sm text-yellow-800">
                Ada {{ $namelessCount }} shift bulan ini yang memakai jadwal <strong>tanpa nama</strong>. Jadwal tanpa nama tidak dihitung sebagai hari aktif dan tidak bisa dipilih;
                beri nama di <a href="{{ route('admin.schedules.custom.pegawai') }}" class="underline">Jadwal Pegawai</a>. Bila halaman ini disimpan sekarang, sel tersebut dikosongkan.
            </div>
        @endif

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
                                $offReason = $offDays[$dateString] ?? null;
                            @endphp
                            <th class="px-2 py-3 text-center text-xs font-medium uppercase tracking-wider min-w-[120px] date-header-{{ $dateString }} {{ $offReason ? 'bg-red-50 text-red-700' : 'bg-gray-100 text-gray-500' }}"
                                data-date="{{ $dateString }}"
                                title="{{ $offReason ?? 'Hari aktif' }}">
                                <div class="flex flex-col items-center">
                                    <span>{{ $date->translatedFormat('d') }}</span>
                                    <span class="text-xs">{{ $date->translatedFormat('l') }}</span>
                                    <div class="mt-1">
                                        {{-- Read-only: follows Hari Aktif and Hari Libur --}}
                                        <input type="checkbox"
                                               id="date_toggle_{{ $dateString }}"
                                               class="h-4 w-4 text-blue-600 rounded date-checkbox"
                                               data-date="{{ $dateString }}"
                                               {{ $offReason ? '' : 'checked' }} disabled>
                                    </div>
                                    @if($offReason)
                                        <span class="mt-1 normal-case font-normal text-[10px] leading-tight max-w-[110px] whitespace-normal">{{ $offReason }}</span>
                                    @endif
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
                                    $isOff = isset($offDays[$dateString]);
                                @endphp
                                <td class="px-2 py-2 whitespace-nowrap text-center date-cell-{{ $dateString }} {{ $isOff ? 'bg-red-50' : '' }}"
                                    data-date="{{ $dateString }}">
                                    <select name="shift_{{ $employee->id }}_{{ $date->day }}"
                                            class="shift-select border border-gray-300 rounded px-1 py-1 text-xs w-full bg-gray-100 cursor-not-allowed opacity-50 shift-selection"
                                            data-date="{{ $dateString }}"
                                            data-employee-id="{{ $employee->id }}"
                                            data-off="{{ $isOff ? '1' : '0' }}"
                                            disabled>
                                        <option value="">-</option>
                                        @foreach($schedules as $schedule)
                                            <option value="{{ $schedule->id }}" 
                                                    {{ isset($employeeShifts[$employee->id]) && $employeeShifts[$employee->id]->contains(function($shift) use ($date, $schedule) { 
                                                        return $shift->date->format('Y-m-d') === $date->format('Y-m-d') && $shift->schedule_id === $schedule->id; 
                                                    }) ? 'selected' : '' }}>
                                                {{ $schedule->shift_name }}
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
    const shiftSelects = document.querySelectorAll('.shift-selection');


    // State variables
    let isEditMode = false;

    // Closed dates (holiday / not an active day) stay read-only
    function setEditMode(enabled) {
        isEditMode = enabled;

        [[editShiftBtn, enabled], [saveShiftBtn, !enabled], [cancelEditBtn, !enabled], [exportExcelBtn, !enabled], [importExcelBtn, !enabled]]
            .forEach(([button, hidden]) => { if (button) button.classList.toggle('hidden', hidden); });

        shiftSelects.forEach(select => {
            const editable = enabled && select.dataset.off !== '1';
            select.disabled = !editable;
            select.classList.toggle('bg-gray-100', !editable);
            select.classList.toggle('cursor-not-allowed', !editable);
            select.classList.toggle('opacity-50', !editable);
        });
    }

    // Remember the loaded values so "Batal" can restore them
    shiftSelects.forEach(select => { select.dataset.originalValue = select.value; });
    setEditMode(false);

    editShiftBtn.addEventListener('click', function() {
        setEditMode(true);
    });

    cancelEditBtn.addEventListener('click', function() {
        shiftSelects.forEach(select => { select.value = select.dataset.originalValue || ''; });
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

        if (!confirm('Apakah Anda yakin ingin menyimpan pengaturan shift ini?\n\nPerubahan akan:\n- Menyimpan shift pegawai sesuai pilihan Anda (tanggal libur / bukan hari aktif tidak diubah)\n- Menerapkan jadwal ke absensi pegawai bulan ini\n- Menghapus baris absensi kosong pada shift yang dikosongkan (absensi yang sudah ada jam masuk/pulang tetap aman)\n\nCatatan: hasil import Excel sebelumnya akan ditimpa dengan isian di form ini.')) {
            e.preventDefault();
            return false;
        }
        // Closed dates are not submitted: the server leaves them as they are
    });
});
</script>
@endsection