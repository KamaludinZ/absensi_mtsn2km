@extends('layouts.app')

@section('title', 'Export Laporan Siswa')
@section('page-title', 'Export Laporan Siswa')

@section('content')
<div class="max-w-4xl mx-auto py-6">
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex flex-wrap justify-between items-center gap-4 mb-6">
            <h2 class="text-xl font-bold">Export Rekap Absensi Siswa</h2>
            <a href="{{ route('admin.reports.students') }}" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700">
                Lihat Rekap
            </a>
        </div>

        @if(session('error'))
            <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">{{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('admin.reports.students.export') }}" method="GET" class="space-y-6"
              x-data="{
                  rombels: @js($rombels->map(fn ($r) => ['id' => $r->id, 'name' => $r->name, 'grade' => $r->grade_id])),
                  students: @js($students->map(fn ($s) => ['id' => $s->id, 'name' => $s->full_name, 'rombel' => $s->rombel_id])),
                  grade: '', rombel: '', student: '',
                  get rombelOptions() { return this.rombels.filter(r => !this.grade || String(r.grade) === this.grade); },
                  get studentOptions() {
                      const ids = this.rombelOptions.map(r => r.id);
                      return this.students.filter(s => this.rombel ? String(s.rombel) === this.rombel : (!this.grade || ids.includes(s.rombel)));
                  },
              }">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label for="grade_id" class="block text-sm font-medium text-gray-700 mb-2">Tingkat Kelas</label>
                    <select id="grade_id" name="grade_id" x-model="grade" @change="rombel = ''; student = ''" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        <option value="">Semua Tingkat</option>
                        @foreach($grades as $g)
                            <option value="{{ $g->id }}">Kelas {{ $g->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="rombel_id" class="block text-sm font-medium text-gray-700 mb-2">Rombel</label>
                    <select id="rombel_id" name="rombel_id" x-model="rombel" @change="student = ''" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        <option value="">Semua Rombel</option>
                        <template x-for="r in rombelOptions" :key="r.id">
                            <option :value="String(r.id)" x-text="r.name"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label for="student_id" class="block text-sm font-medium text-gray-700 mb-2">Nama Siswa</label>
                    <select id="student_id" name="student_id" x-model="student" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        <option value="">Semua Siswa</option>
                        <template x-for="s in studentOptions" :key="s.id">
                            <option :value="String(s.id)" x-text="s.name"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label for="start_date" class="block text-sm font-medium text-gray-700 mb-2">Dari Tanggal <span class="text-red-600">*</span></label>
                    <input type="date" id="start_date" name="start_date" value="{{ now()->startOfMonth()->toDateString() }}" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label for="end_date" class="block text-sm font-medium text-gray-700 mb-2">Sampai Tanggal <span class="text-red-600">*</span></label>
                    <input type="date" id="end_date" name="end_date" value="{{ now()->endOfMonth()->toDateString() }}" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label for="format" class="block text-sm font-medium text-gray-700 mb-2">Format <span class="text-red-600">*</span></label>
                    <select id="format" name="format" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        <option value="excel">Excel (.xlsx)</option>
                        <option value="pdf">PDF (.pdf)</option>
                    </select>
                </div>
            </div>

            <div class="flex justify-end pt-4 border-t">
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">Download Laporan</button>
            </div>
        </form>

        <div class="mt-8 p-4 bg-blue-50 rounded-lg border border-blue-200 text-sm text-blue-800">
            <h3 class="font-semibold text-blue-900 mb-2">Isi laporan</h3>
            <ul class="list-disc list-inside space-y-1">
                <li>Per siswa: jumlah hari sekolah, Hadir, Terlambat, Sakit, Izin, Alpa, izin yang belum disetujui, dan persentase kehadiran.</li>
                <li>Excel untuk kelas/rombel berisi sheet <strong>Harian</strong> (kode status per tanggal). Jika memilih satu siswa, isinya rincian per tanggal.</li>
                <li>Hari sekolah mengikuti Pengaturan Absen Siswa dan hari libur. Sakit/Izin diambil dari perizinan siswa yang sudah disetujui.</li>
            </ul>
        </div>
    </div>
</div>
@endsection
