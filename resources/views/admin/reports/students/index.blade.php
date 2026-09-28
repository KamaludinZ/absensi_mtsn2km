@extends('layouts.app')
@section('title', 'Rekap Absensi Siswa')
@section('page-title', 'Rekap Absensi Siswa')

@php
    $badge = [
        'H' => 'bg-green-100 text-green-800',
        'T' => 'bg-yellow-100 text-yellow-800',
        'S' => 'bg-blue-100 text-blue-800',
        'I' => 'bg-indigo-100 text-indigo-800',
        'A' => 'bg-red-100 text-red-800',
        'M' => 'bg-orange-100 text-orange-800',
        'L' => 'bg-gray-200 text-gray-700',
    ];
    $filterQuery = array_filter([
        'grade_id' => $grade?->id,
        'rombel_id' => $rombel?->id,
        'student_id' => $student?->id,
        'start_date' => $start->toDateString(),
        'end_date' => $end->toDateString(),
    ]);
@endphp

@section('content')
<div class="w-full py-6 space-y-6">
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
        <div class="flex flex-wrap justify-between items-start gap-4 mb-4">
            <div>
                <h2 class="text-xl font-bold text-gray-800">Rekap Absensi Siswa</h2>
                <p class="text-sm text-gray-600 mt-1">
                    Hari sekolah mengikuti <a href="{{ route('admin.schedules.custom.siswa') }}" class="text-blue-600 hover:underline">Pengaturan Absen Siswa</a>
                    dan hari libur. Izin/sakit dihitung dari <a href="{{ route('admin.permissions.students') }}" class="text-blue-600 hover:underline">Perizinan Siswa</a> yang sudah disetujui.
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.reports.students.daily') }}" class="bg-gray-100 text-gray-800 px-4 py-2 rounded-lg hover:bg-gray-200">Data Absensi Harian</a>
                <a href="{{ route('admin.permissions.students.create') }}" class="bg-blue-100 text-blue-800 px-4 py-2 rounded-lg hover:bg-blue-200">+ Izin Siswa</a>
            </div>
        </div>

        <form method="GET" action="{{ route('admin.reports.students') }}"
              x-data="{
                  rombels: @js($rombels->map(fn ($r) => ['id' => $r->id, 'name' => $r->name, 'grade' => $r->grade_id])),
                  students: @js($students->map(fn ($s) => ['id' => $s->id, 'name' => $s->full_name, 'rombel' => $s->rombel_id])),
                  grade: @js((string) ($grade?->id ?? '')),
                  rombel: @js((string) ($rombel?->id ?? '')),
                  student: @js((string) ($student?->id ?? '')),
                  get rombelOptions() { return this.rombels.filter(r => !this.grade || String(r.grade) === this.grade); },
                  get studentOptions() {
                      const ids = this.rombelOptions.map(r => r.id);
                      return this.students.filter(s => this.rombel ? String(s.rombel) === this.rombel : (!this.grade || ids.includes(s.rombel)));
                  },
              }"
              class="grid grid-cols-1 md:grid-cols-6 gap-4 items-end">
            <div>
                <label for="grade_id" class="block text-sm font-medium text-gray-700 mb-1">Tingkat Kelas</label>
                <select id="grade_id" name="grade_id" x-model="grade" @change="rombel = ''; student = ''" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    <option value="">Semua Tingkat</option>
                    @foreach($grades as $g)
                        <option value="{{ $g->id }}">Kelas {{ $g->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="rombel_id" class="block text-sm font-medium text-gray-700 mb-1">Rombel</label>
                <select id="rombel_id" name="rombel_id" x-model="rombel" @change="student = ''" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    <option value="">Semua Rombel</option>
                    <template x-for="r in rombelOptions" :key="r.id">
                        <option :value="String(r.id)" x-text="r.name" :selected="String(r.id) === rombel"></option>
                    </template>
                </select>
            </div>
            <div>
                <label for="student_id" class="block text-sm font-medium text-gray-700 mb-1">Nama Siswa</label>
                <select id="student_id" name="student_id" x-model="student" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    <option value="">Semua Siswa</option>
                    <template x-for="s in studentOptions" :key="s.id">
                        <option :value="String(s.id)" x-text="s.name" :selected="String(s.id) === student"></option>
                    </template>
                </select>
            </div>
            <div>
                <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Dari Tanggal</label>
                <input type="date" id="start_date" name="start_date" value="{{ $start->toDateString() }}" class="w-full px-3 py-2 border border-gray-300 rounded-md">
            </div>
            <div>
                <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">Sampai Tanggal</label>
                <input type="date" id="end_date" name="end_date" value="{{ $end->toDateString() }}" class="w-full px-3 py-2 border border-gray-300 rounded-md">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">Tampilkan</button>
                <a href="{{ route('admin.reports.students') }}" class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600">Reset</a>
            </div>
        </form>
    </div>

    @if($pendingPermissions > 0)
        <div class="bg-orange-50 border border-orange-300 text-orange-800 px-4 py-3 rounded">
            Ada <strong>{{ $pendingPermissions }}</strong> izin siswa pada periode ini yang belum disetujui. Selama belum disetujui,
            harinya ditandai <em>Izin belum disetujui</em> (tidak dihitung Sakit/Izin maupun Alpa).
            <a href="{{ route('admin.permissions.students', ['status' => 'pending']) }}" class="underline font-medium">Proses perizinan</a>
        </div>
    @endif

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex flex-wrap justify-between items-center gap-4 mb-4">
            <div>
                <h3 class="text-lg font-semibold text-gray-800">{{ $scopeLabel }}</h3>
                <p class="text-sm text-gray-600">
                    Periode {{ $start->translatedFormat('d F Y') }} – {{ $end->translatedFormat('d F Y') }}
                    · {{ $schoolDays->count() }} hari sekolah s.d. hari ini
                    @if($holidays->isNotEmpty())
                        · {{ $holidays->count() }} hari libur
                    @endif
                </p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('admin.reports.students.export', $filterQuery + ['format' => 'excel']) }}" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">Export Excel</a>
                <a href="{{ route('admin.reports.students.export', $filterQuery + ['format' => 'pdf']) }}" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700">Export PDF</a>
            </div>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mb-6">
            @foreach(['H' => 'Hadir tepat waktu', 'T' => 'Terlambat', 'S' => 'Sakit', 'I' => 'Izin', 'A' => 'Alpa', 'M' => 'Izin belum disetujui'] as $code => $title)
                <div class="rounded-lg p-3 {{ $badge[$code] }}">
                    <div class="text-2xl font-bold">{{ $totals[$code] }}</div>
                    <div class="text-sm">{{ $title }}</div>
                </div>
            @endforeach
        </div>

        @if($studentRow)
            <div class="mb-4 text-sm text-gray-700 flex flex-wrap gap-x-6 gap-y-1">
                <span>NISN: <strong>{{ $student->nisn ?? '-' }}</strong></span>
                <span>Rombel sekarang: <strong>{{ $student->rombel->name ?? '-' }}</strong></span>
                <span>Kehadiran: <strong>{{ $studentRow['percentage'] !== null ? $studentRow['percentage'] . '%' : '-' }}</strong>
                    ({{ $studentRow['present'] }} dari {{ $studentRow['effective_days'] }} hari)</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Absen Masuk</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Absen Pulang</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Keterangan</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($calendar as $day)
                            <tr class="{{ $day['code'] === null || $day['code'] === 'L' ? 'bg-gray-50 text-gray-500' : '' }}">
                                <td class="px-4 py-2 whitespace-nowrap">{{ $day['date']->translatedFormat('l, d/m/Y') }}</td>
                                <td class="px-4 py-2 whitespace-nowrap">
                                    @if($day['code'])
                                        <span class="px-2 py-0.5 text-xs font-semibold rounded-full {{ $badge[$day['code']] }}">{{ $day['label'] }}</span>
                                    @else
                                        <span class="text-xs">{{ $day['label'] }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap">{{ $day['attendance']?->clock_in?->format('H:i') ?? '-' }}</td>
                                <td class="px-4 py-2 whitespace-nowrap">{{ $day['attendance']?->clock_out?->format('H:i') ?? '-' }}</td>
                                <td class="px-4 py-2">{{ $day['note'] ?? '' }}</td>
                                <td class="px-4 py-2 whitespace-nowrap text-sm">
                                    @if($day['attendance'])
                                        <a href="{{ route('admin.attendances.edit', $day['attendance']->id) }}" class="text-blue-600 hover:underline">Edit absensi</a>
                                    @elseif($day['permission'])
                                        <a href="{{ route('admin.permissions.students.edit', $day['permission']) }}" class="text-blue-600 hover:underline">Lihat izin</a>
                                    @elseif($day['code'] === 'A')
                                        <a href="{{ route('admin.permissions.students.create') }}" class="text-blue-600 hover:underline">Catat izin</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">No</th>
                            <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">NISN</th>
                            <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama Siswa</th>
                            <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rombel</th>
                            <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase" title="Hari sekolah yang dihitung">Hari</th>
                            <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase" title="Hadir tepat waktu">H</th>
                            <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase" title="Terlambat">T</th>
                            <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase" title="Sakit">S</th>
                            <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase" title="Izin">I</th>
                            <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase" title="Alpa">A</th>
                            <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase" title="Izin belum disetujui">M</th>
                            <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase">% Hadir</th>
                            <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($rows as $i => $row)
                            <tr>
                                <td class="px-3 py-2">{{ $i + 1 }}</td>
                                <td class="px-3 py-2 whitespace-nowrap">{{ $row['student']->nisn ?? '-' }}</td>
                                <td class="px-3 py-2">{{ $row['student']->full_name }}</td>
                                <td class="px-3 py-2 whitespace-nowrap">{{ $rombel->name ?? $row['student']->rombel->name ?? '-' }}</td>
                                <td class="px-3 py-2 text-center">{{ $row['effective_days'] }}</td>
                                @foreach(['H', 'T', 'S', 'I', 'A', 'M'] as $code)
                                    <td class="px-3 py-2 text-center {{ $row['counts'][$code] > 0 && in_array($code, ['A', 'M']) ? 'font-bold text-red-700' : '' }}">{{ $row['counts'][$code] }}</td>
                                @endforeach
                                <td class="px-3 py-2 text-center">{{ $row['percentage'] !== null ? $row['percentage'] . '%' : '-' }}</td>
                                <td class="px-3 py-2 whitespace-nowrap text-sm">
                                    <a href="{{ route('admin.reports.students', array_merge($filterQuery, ['student_id' => $row['student']->id])) }}" class="text-blue-600 hover:underline">Detail</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="13" class="px-3 py-4 text-center text-gray-500">Tidak ada siswa untuk filter ini</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <p class="text-xs text-gray-500 mt-3">H = hadir tepat waktu, T = terlambat, S = sakit, I = izin, A = alpa (tanpa keterangan), M = izin belum disetujui. % Hadir = (H + T) / hari sekolah.</p>
        @endif
    </div>
</div>
@endsection
