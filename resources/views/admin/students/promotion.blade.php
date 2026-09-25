@extends('layouts.app')
@section('title', 'Kenaikan Kelas')
@section('page-title', 'Kenaikan Kelas')

@php
    $rombelOptions = function ($selected = null, $exclude = null) use ($rombelGroups) {
        $html = '';
        foreach ($rombelGroups as $year => $rombels) {
            $html .= '<optgroup label="Tahun Ajaran ' . e($year) . '">';
            foreach ($rombels as $rombel) {
                if ($exclude && $rombel->id === $exclude) {
                    continue;
                }
                $html .= '<option value="' . $rombel->id . '"' . ((string) $selected === (string) $rombel->id ? ' selected' : '') . '>'
                    . e($rombel->name) . ' (' . $rombel->active_students_count . ' siswa aktif)</option>';
            }
            $html .= '</optgroup>';
        }
        return $html;
    };
@endphp

@section('content')
<div class="container mx-auto px-4 py-6 space-y-6">
    @if($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white rounded-lg shadow p-6">
        <h1 class="text-2xl font-bold text-gray-800 mb-2">Kenaikan Kelas & Kelulusan</h1>
        <p class="text-gray-600 mb-4">
            Pindahkan banyak siswa sekaligus ke rombel baru (misalnya saat naik kelas), atau nyatakan siswa lulus.
            Siswa yang lulus dinonaktifkan: tidak bisa absen lagi dan kartu RFID-nya tidak bisa dipakai.
        </p>
        <p class="text-sm text-gray-500 mb-4">
            Tips kenaikan kelas tahun ajaran baru: buat dulu
            <a href="{{ route('admin.academic-years.index') }}" class="text-blue-600 hover:underline">tahun ajaran</a> dan
            <a href="{{ route('admin.rombels.index') }}" class="text-blue-600 hover:underline">rombel</a> baru,
            lalu mulai dari kelas tertinggi: luluskan kelas 9, naikkan kelas 8 ke 9, lalu kelas 7 ke 8.
        </p>

        <form method="GET" action="{{ route('admin.students.promotion.index') }}" class="flex flex-col sm:flex-row gap-3 sm:items-end">
            <div class="flex-1">
                <label for="rombel_id" class="block text-sm font-medium text-gray-700 mb-1">1. Pilih rombel asal</label>
                <select id="rombel_id" name="rombel_id" class="w-full px-3 py-2 border rounded-lg" onchange="this.form.submit()">
                    <option value="">-- Pilih Rombel --</option>
                    {!! $rombelOptions(optional($sourceRombel)->id) !!}
                </select>
            </div>
            <noscript><button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg">Tampilkan</button></noscript>
        </form>
    </div>

    @if($sourceRombel)
        <form method="POST" action="{{ route('admin.students.promotion.process') }}"
              x-data="{
                  action: @js(in_array(old('action'), ['move', 'graduate'], true) ? old('action') : 'move'),
                  source: @js($sourceRombel->name),
                  selected: @js(array_map('intval', old('student_ids', $students->pluck('id')->all()))),
                  all: @js($students->pluck('id')->all()),
                  targetName() { const s = this.$refs.target; return s && s.value ? s.options[s.selectedIndex].text.replace(/ \(.*$/, '') : ''; },
                  confirmText() {
                      return this.action === 'move'
                          ? 'Pindahkan ' + this.selected.length + ' siswa dari ' + this.source + ' ke ' + this.targetName() + '?'
                          : 'Nyatakan ' + this.selected.length + ' siswa dari ' + this.source + ' LULUS? Mereka tidak bisa absen lagi.';
                  }
              }"
              @submit="if (!confirm(confirmText())) $event.preventDefault()"
              class="bg-white rounded-lg shadow p-6 space-y-6">
            @csrf
            <input type="hidden" name="source_rombel_id" value="{{ $sourceRombel->id }}">

            <div>
                <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                    <h2 class="text-lg font-semibold text-gray-800">
                        2. Pilih siswa dari {{ $sourceRombel->name }}
                        <span class="text-sm font-normal text-gray-500">({{ $sourceRombel->academicYear->year_name ?? '-' }})</span>
                    </h2>
                    @if($students->isNotEmpty())
                        <div class="text-sm space-x-3">
                            <span class="text-gray-600"><span x-text="selected.length"></span> dari {{ $students->count() }} dipilih</span>
                            <button type="button" class="text-blue-600 hover:underline" @click="selected = [...all]">Pilih semua</button>
                            <button type="button" class="text-blue-600 hover:underline" @click="selected = []">Kosongkan</button>
                        </div>
                    @endif
                </div>

                @if($students->isEmpty())
                    <p class="text-gray-600">Tidak ada siswa aktif di rombel ini.</p>
                @else
                    <p class="text-sm text-gray-500 mb-2">Hapus centang siswa yang tidak ikut dipindahkan/diluluskan (misalnya tinggal kelas).</p>
                    <div class="overflow-x-auto border rounded-lg">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 w-10"></th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Nama</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">NISN</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Kartu RFID</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach($students as $student)
                                    <tr>
                                        <td class="px-4 py-2">
                                            <input type="checkbox" name="student_ids[]" value="{{ $student->id }}" x-model.number="selected"
                                                   id="student-{{ $student->id }}" class="rounded border-gray-300">
                                        </td>
                                        <td class="px-4 py-2 text-sm text-gray-800"><label for="student-{{ $student->id }}">{{ $student->full_name }}</label></td>
                                        <td class="px-4 py-2 text-sm text-gray-600">{{ $student->nisn }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-600">{{ $student->rfid_uid ? 'Terdaftar' : '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            @if($students->isNotEmpty())
                <div>
                    <h2 class="text-lg font-semibold text-gray-800 mb-3">3. Pilih tindakan</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <label class="border rounded-lg p-4 cursor-pointer" :class="action === 'move' ? 'border-blue-500 bg-blue-50' : 'border-gray-200'">
                            <div class="flex items-center">
                                <input type="radio" name="action" value="move" x-model="action" class="border-gray-300">
                                <span class="ml-2 font-semibold text-gray-800">Pindahkan / Naik Kelas</span>
                            </div>
                            <p class="text-sm text-gray-600 mt-1 ml-6">Siswa tetap aktif, rombelnya diganti.</p>
                            <div class="mt-3 ml-6" x-show="action === 'move'">
                                <label for="target_rombel_id" class="block text-sm text-gray-700 mb-1">Rombel tujuan</label>
                                <select id="target_rombel_id" name="target_rombel_id" x-ref="target" :required="action === 'move'"
                                        class="w-full px-3 py-2 border rounded-lg">
                                    <option value="">-- Pilih Rombel Tujuan --</option>
                                    {!! $rombelOptions(old('target_rombel_id'), $sourceRombel->id) !!}
                                </select>
                            </div>
                        </label>

                        <label class="border rounded-lg p-4 cursor-pointer" :class="action === 'graduate' ? 'border-green-500 bg-green-50' : 'border-gray-200'">
                            <div class="flex items-center">
                                <input type="radio" name="action" value="graduate" x-model="action" class="border-gray-300">
                                <span class="ml-2 font-semibold text-gray-800">Luluskan</span>
                            </div>
                            <p class="text-sm text-gray-600 mt-1 ml-6">Siswa dinonaktifkan dan ditandai lulus. Data absensi lama tetap tersimpan.</p>
                            <div class="mt-3 ml-6" x-show="action === 'graduate'" x-cloak>
                                <input type="hidden" name="release_rfid" value="0">
                                <label class="inline-flex items-start text-sm text-gray-700">
                                    <input type="checkbox" name="release_rfid" value="1" class="rounded border-gray-300 mt-0.5">
                                    <span class="ml-2">Lepas kartu RFID-nya juga, supaya kartu fisiknya bisa dipakai siswa lain</span>
                                </label>
                            </div>
                        </label>
                    </div>
                </div>

                <button type="submit" :disabled="selected.length === 0"
                        class="w-full sm:w-auto px-6 py-3 rounded-lg text-white font-semibold disabled:opacity-40"
                        :class="action === 'move' ? 'bg-blue-600 hover:bg-blue-700' : 'bg-green-600 hover:bg-green-700'"
                        x-text="action === 'move' ? 'Pindahkan ' + selected.length + ' Siswa' : 'Luluskan ' + selected.length + ' Siswa'">
                    Proses
                </button>
            @endif
        </form>
    @endif
</div>
@endsection
