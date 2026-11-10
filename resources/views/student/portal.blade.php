@extends('layouts.guest')

@section('title', 'Absensi Saya - MTsN 2 Kota Malang')

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
    $counts = $row['counts'];
@endphp

@section('content')
<div class="max-w-3xl mx-auto px-4 py-6 space-y-5">
    {{-- Identity --}}
    <div class="bg-white rounded-lg shadow p-5 flex items-start justify-between gap-4">
        <div>
            <p class="text-sm text-gray-500">Absensi Saya</p>
            <h1 class="text-xl font-bold text-gray-800">{{ $student->full_name }}</h1>
            <p class="text-sm text-gray-600">NISN {{ $student->nisn }} &middot; Kelas {{ $student->rombel?->name ?? '-' }}</p>
        </div>
        <form method="POST" action="{{ route('student.portal.logout') }}">
            @csrf
            <button type="submit" class="text-sm px-3 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">Keluar</button>
        </form>
    </div>

    {{-- Today --}}
    <div class="bg-white rounded-lg shadow p-5">
        <h2 class="font-semibold text-gray-800 mb-3">Hari ini, {{ now()->translatedFormat('l, d F Y') }}</h2>
        @if($today)
            <div class="grid grid-cols-2 gap-3 text-center">
                <div class="rounded-lg bg-green-50 p-3">
                    <p class="text-xs text-gray-500">Masuk</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $today->clock_in?->format('H:i') ?? '-' }}</p>
                    <p class="text-xs text-gray-600">{{ $today->status_in ?? '' }}</p>
                </div>
                <div class="rounded-lg bg-blue-50 p-3">
                    <p class="text-xs text-gray-500">Pulang</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $today->clock_out?->format('H:i') ?? '-' }}</p>
                    <p class="text-xs text-gray-600">{{ $today->status_out ?? '' }}</p>
                </div>
            </div>
        @elseif($todayPermission)
            <p class="text-gray-700">
                {{ $todayPermission->type === 'S' ? 'Sakit' : 'Izin' }}
                @if($todayPermission->status !== 'approved')
                    <span class="text-orange-600">(belum disetujui)</span>
                @endif
                : {{ $todayPermission->reason }}
            </p>
        @else
            <p class="text-gray-600">Belum ada absensi hari ini. Absen dilakukan di sekolah lewat halaman absen siswa atau kartu RFID.</p>
        @endif
    </div>

    {{-- Month summary --}}
    <div class="bg-white rounded-lg shadow p-5">
        <div class="flex items-center justify-between mb-4">
            <a href="{{ route('student.portal.index', ['bulan' => $previousMonth->format('Y-m')]) }}" class="text-sm text-blue-600 hover:underline">&larr; {{ $previousMonth->translatedFormat('M Y') }}</a>
            <h2 class="font-semibold text-gray-800">{{ $month->translatedFormat('F Y') }}</h2>
            @if($nextMonth)
                <a href="{{ route('student.portal.index', ['bulan' => $nextMonth->format('Y-m')]) }}" class="text-sm text-blue-600 hover:underline">{{ $nextMonth->translatedFormat('M Y') }} &rarr;</a>
            @else
                <span class="text-sm text-gray-300">&rarr;</span>
            @endif
        </div>

        <div class="grid grid-cols-3 sm:grid-cols-6 gap-2 text-center mb-2">
            @foreach(['H', 'T', 'S', 'I', 'A', 'M'] as $code)
                <div class="rounded-lg p-2 {{ $badge[$code] }}">
                    <p class="text-lg font-bold">{{ $counts[$code] }}</p>
                    <p class="text-xs">{{ \App\Services\StudentAttendanceRecap::LABELS[$code] }}</p>
                </div>
            @endforeach
        </div>
        <p class="text-xs text-gray-500">
            Hari sekolah: {{ $row['effective_days'] }}
            @if($row['percentage'] !== null) &middot; Kehadiran {{ $row['percentage'] }}% @endif
        </p>
    </div>

    {{-- Daily history --}}
    <div class="bg-white rounded-lg shadow divide-y divide-gray-100">
        @forelse($calendar as $day)
            <div class="px-5 py-3 flex items-center justify-between gap-3 {{ $day['code'] === null || $day['code'] === 'L' ? 'text-gray-400' : '' }}">
                <div>
                    <p class="text-sm font-medium">{{ $day['date']->translatedFormat('l, d M') }}</p>
                    @if($day['note'])
                        <p class="text-xs text-gray-500">{{ $day['note'] }}</p>
                    @endif
                </div>
                <div class="text-right">
                    @if($day['code'])
                        <span class="px-2 py-0.5 text-xs font-semibold rounded-full {{ $badge[$day['code']] }}">{{ $day['label'] }}</span>
                    @else
                        <span class="text-xs">{{ $day['label'] }}</span>
                    @endif
                    @if($day['attendance'])
                        <p class="text-xs text-gray-600 mt-1">
                            @if($day['attendance']->clock_out)
                                {{ $day['attendance']->clock_in?->format('H:i') ?? '-' }} &ndash; {{ $day['attendance']->clock_out->format('H:i') }}
                            @else
                                Masuk {{ $day['attendance']->clock_in?->format('H:i') ?? '-' }}
                            @endif
                        </p>
                    @endif
                </div>
            </div>
        @empty
            <p class="px-5 py-4 text-sm text-gray-500">Belum ada data di bulan ini.</p>
        @endforelse
    </div>

    <p class="text-xs text-gray-500 text-center">Ada data yang tidak sesuai? Hubungi wali kelas atau admin Absensi.</p>
</div>
@endsection
