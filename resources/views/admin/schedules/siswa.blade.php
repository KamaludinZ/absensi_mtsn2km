@extends('layouts.app')
@section('title', 'Pengaturan Absen Siswa')
@section('page-title', 'Pengaturan Absen Siswa')

@section('content')
<div class="w-full py-6 space-y-6">
    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">{{ session('success') }}</div>
    @endif
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
        <h2 class="text-xl font-bold text-gray-800 mb-2">Jadwal Absensi Siswa</h2>
        <p class="text-gray-600 mb-1">
            Centang hari sekolah dan isi jam masuk serta jam pulangnya. Jadwal ini khusus siswa, terpisah dari jadwal/shift pegawai.
        </p>
        <ul class="text-sm text-gray-500 list-disc list-inside mb-6">
            <li>Siswa yang absen masuk setelah jam masuk tercatat <strong>Terlambat</strong>.</li>
            <li>Halaman absensi siswa hanya dibuka pada hari yang dicentang, mulai
                {{ \App\Services\StudentKiosk::openMinutesBefore() }} menit sebelum jam masuk sampai
                {{ \App\Services\StudentKiosk::closeMinutesAfter() }} menit setelah jam pulang
                (<a href="{{ route('admin.student-kiosk.index') }}" class="text-blue-600 hover:underline">ubah di Jam Absensi Siswa</a>).</li>
            <li>Hari yang dicentang menjadi hari sekolah di <a href="{{ route('admin.reports.students') }}" class="text-blue-600 hover:underline">Rekap Absensi Siswa</a>;
                tanggal <a href="{{ route('admin.holidays.index') }}" class="text-blue-600 hover:underline">hari libur</a> tetap tidak dihitung.</li>
        </ul>

        <form method="POST" action="{{ route('admin.schedules.custom.siswa.update') }}">
            @csrf
            @method('PUT')
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Hari Sekolah</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jam Masuk</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jam Pulang</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($days as $d)
                            @php
                                $day = $d['day'];
                                $active = (bool) old("days.$day.active", $d['active']);
                            @endphp
                            <tr x-data="{ active: @js($active) }" :class="active ? '' : 'bg-gray-50'">
                                <td class="px-4 py-3">
                                    <input type="hidden" name="days[{{ $day }}][active]" value="0">
                                    <label class="inline-flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox" name="days[{{ $day }}][active]" value="1" x-model="active" {{ $active ? 'checked' : '' }}
                                               class="h-4 w-4 text-blue-600 border-gray-300 rounded">
                                        <span class="font-medium text-gray-800">{{ $d['name'] }}</span>
                                    </label>
                                </td>
                                <td class="px-4 py-3">
                                    <input type="time" name="days[{{ $day }}][start_time]" value="{{ old("days.$day.start_time", $d['start_time']) }}"
                                           :disabled="!active" class="px-3 py-2 border border-gray-300 rounded-md disabled:bg-gray-100 disabled:text-gray-400">
                                </td>
                                <td class="px-4 py-3">
                                    <input type="time" name="days[{{ $day }}][end_time]" value="{{ old("days.$day.end_time", $d['end_time']) }}"
                                           :disabled="!active" class="px-3 py-2 border border-gray-300 rounded-md disabled:bg-gray-100 disabled:text-gray-400">
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="flex justify-end mt-6">
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700">Simpan Jadwal Siswa</button>
            </div>
        </form>
    </div>
</div>
@endsection
