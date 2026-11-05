{{-- Hari Penerapan & Jabatan Penerapan: fills /admin/employee-shifts automatically --}}
@php
    $selectedDays = array_map('intval', old('apply_days', isset($schedule) ? ($schedule->apply_days ?? []) : []));
    $selectedPositions = collect(old('apply_positions', isset($schedule) ? ($schedule->apply_positions ?? []) : []))
        ->map(fn ($p) => \App\Models\Schedule::normalizePosition($p))
        ->all();
@endphp

<div class="md:col-span-2 border border-blue-100 bg-blue-50/50 rounded-lg p-4 mb-4">
    <h3 class="font-bold text-gray-800">Penerapan Otomatis ke Shift Pegawai</h3>
    <p class="text-sm text-gray-600 mt-1">
        Bila hari dan jabatan diisi, shift ini otomatis diisikan di
        <a href="{{ route('admin.employee-shifts.index') }}" class="text-blue-600 hover:underline">Pengaturan Shift Pegawai</a>
        untuk semua pegawai <strong>aktif</strong> dengan jabatan terpilih, mulai hari ini sampai akhir bulan, hanya pada
        <a href="{{ route('admin.holidays.active-days') }}" class="text-blue-600 hover:underline">hari aktif</a> yang bukan hari libur.
        Sel shift yang sudah terisi tidak ditimpa. Kosongkan keduanya bila shift hanya dipilih manual.
    </p>

    <div class="mt-4">
        <span class="block text-gray-700 font-bold mb-2">Hari Penerapan</span>
        <div class="flex flex-wrap gap-2">
            @foreach($isoDays as $value => $name)
                @php $isActiveDay = in_array($value, $activeDays, true); @endphp
                <label class="inline-flex items-center gap-2 px-3 py-2 border rounded-md bg-white cursor-pointer {{ $isActiveDay ? 'border-gray-300' : 'border-dashed border-gray-300 text-gray-400' }}">
                    <input type="checkbox" name="apply_days[]" value="{{ $value }}" class="apply-day rounded border-gray-300"
                           {{ in_array($value, $selectedDays, true) ? 'checked' : '' }}>
                    <span>{{ $name }}</span>
                    @unless($isActiveDay)
                        <span class="text-xs">(bukan hari aktif)</span>
                    @endunless
                </label>
            @endforeach
        </div>
        <p class="text-gray-500 text-xs mt-1">Hari yang bukan hari aktif tetap dilewati saat pengisian otomatis.</p>
        @error('apply_days')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div class="mt-4">
        <div class="flex items-center justify-between mb-2">
            <span class="block text-gray-700 font-bold">Jabatan Penerapan</span>
            @if($positions->isNotEmpty())
                <label class="inline-flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
                    <input type="checkbox" id="apply-positions-all" class="rounded border-gray-300">
                    Pilih semua jabatan
                </label>
            @endif
        </div>
        @if($positions->isEmpty())
            <p class="text-sm text-gray-500">Belum ada pegawai aktif dengan jabatan. Isi jabatan di data pegawai terlebih dahulu.</p>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                @foreach($positions as $position)
                    <label class="inline-flex items-center gap-2 px-3 py-2 border border-gray-300 rounded-md bg-white cursor-pointer">
                        <input type="checkbox" name="apply_positions[]" value="{{ $position }}" class="apply-position rounded border-gray-300"
                               {{ in_array(\App\Models\Schedule::normalizePosition($position), $selectedPositions, true) ? 'checked' : '' }}>
                        <span>{{ $position }}</span>
                    </label>
                @endforeach
            </div>
        @endif
        <p class="text-gray-500 text-xs mt-1">Satu jabatan hanya boleh punya satu shift otomatis pada hari yang sama.</p>
        @error('apply_positions')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const all = document.getElementById('apply-positions-all');
    if (!all) return;
    const boxes = Array.from(document.querySelectorAll('.apply-position'));
    const sync = () => { all.checked = boxes.length > 0 && boxes.every(b => b.checked); };
    all.addEventListener('change', () => boxes.forEach(b => { b.checked = all.checked; }));
    boxes.forEach(b => b.addEventListener('change', sync));
    sync();
});
</script>
