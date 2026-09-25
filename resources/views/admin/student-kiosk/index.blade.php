@extends('layouts.app')

@section('title', 'Kiosk Absensi Siswa')

@section('content')
<div class="container mx-auto px-4 py-8 space-y-6">
    @if($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Status hari ini -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h1 class="text-2xl font-bold text-gray-800 mb-2">Kiosk Absensi Siswa</h1>
        <p class="text-gray-600 mb-4">
            Siswa absen tanpa login dengan mengetik NISN (atau tap kartu RFID nanti).
            Karena itu halaman absensi siswa hanya bisa dibuka dari perangkat sekolah yang terdaftar
            dan hanya pada jam absensi.
        </p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="rounded-lg border p-4 {{ $window['open'] ? 'bg-green-50 border-green-200' : 'bg-yellow-50 border-yellow-200' }}">
                <p class="text-sm text-gray-600">Status halaman absensi siswa saat ini</p>
                <p class="text-lg font-semibold {{ $window['open'] ? 'text-green-700' : 'text-yellow-700' }}">
                    {{ $window['open'] ? 'Dibuka' : 'Ditutup' }}
                </p>
                @if($window['opens_at'])
                    <p class="text-sm text-gray-600">Jam buka hari ini: {{ $window['opens_at']->format('H:i') }} – {{ $window['closes_at']->format('H:i') }}</p>
                @else
                    <p class="text-sm text-gray-600">{{ $window['reason'] }}</p>
                @endif
            </div>

            <div class="rounded-lg border p-4 {{ $currentDevice ? 'bg-green-50 border-green-200' : 'bg-gray-50 border-gray-200' }}">
                <p class="text-sm text-gray-600">Browser yang Anda pakai sekarang</p>
                @if($currentDevice)
                    <p class="text-lg font-semibold text-green-700">Terdaftar sebagai "{{ $currentDevice->name }}"</p>
                    <a href="{{ route('student.attendance.index') }}" class="text-sm text-blue-600 hover:underline">Buka halaman absensi siswa →</a>
                @else
                    <p class="text-lg font-semibold text-gray-700">Belum terdaftar</p>
                    <p class="text-sm text-gray-600">Daftarkan di bawah jika ini tablet/PC absensi sekolah.</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Daftarkan perangkat -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-bold text-gray-800 mb-2">Daftarkan Perangkat Ini</h2>
        <p class="text-gray-600 mb-4">
            Buka halaman ini <strong>di tablet/PC absensi sekolah</strong> (login sebagai admin di perangkat itu),
            beri nama, lalu tekan tombol daftar. Setelah itu Anda boleh logout; perangkat tetap terdaftar
            selama data browser (cookie) tidak dihapus.
        </p>
        <form action="{{ route('admin.student-kiosk.devices.register') }}" method="POST" class="flex flex-col sm:flex-row gap-3">
            @csrf
            <input type="text" name="name" required maxlength="100" placeholder="Contoh: Tablet Lobi Depan"
                   value="{{ old('name') }}"
                   class="flex-1 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 font-semibold">
                Daftarkan Perangkat Ini
            </button>
        </form>
    </div>

    <!-- Daftar perangkat -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-bold text-gray-800 mb-4">Perangkat Terdaftar</h2>
        @if($devices->isEmpty())
            <p class="text-gray-600">Belum ada perangkat terdaftar.</p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Terakhir dipakai</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Didaftarkan</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($devices as $device)
                            <tr>
                                <td class="px-4 py-3">
                                    {{ $device->name }}
                                    @if($currentDevice && $currentDevice->id === $device->id)
                                        <span class="ml-1 text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded">perangkat ini</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <span class="text-xs px-2 py-1 rounded {{ $device->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-700' }}">
                                        {{ $device->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ $device->last_used_at ? $device->last_used_at->format('d/m/Y H:i') : '-' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600">
                                    {{ $device->created_at->format('d/m/Y H:i') }}
                                    @if($device->creator) oleh {{ $device->creator->name }} @endif
                                </td>
                                <td class="px-4 py-3 text-sm whitespace-nowrap">
                                    <form action="{{ route('admin.student-kiosk.devices.toggle', $device) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="text-blue-600 hover:underline">
                                            {{ $device->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                        </button>
                                    </form>
                                    <form action="{{ route('admin.student-kiosk.devices.destroy', $device) }}" method="POST" class="inline ml-3"
                                          onsubmit="return confirm('Hapus perangkat ini? Perangkat tidak bisa lagi membuka absensi siswa.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:underline">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <!-- Pengaturan jam & perangkat -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-bold text-gray-800 mb-2">Pengaturan</h2>
        <p class="text-gray-600 mb-4">
            Jam buka mengikuti <a href="{{ route('admin.schedules.custom.siswa') }}" class="text-blue-600 hover:underline">jadwal absen siswa</a>
            hari itu. Halaman juga ditutup pada hari libur dan hari non-aktif.
        </p>
        <form action="{{ route('admin.student-kiosk.settings') }}" method="POST" class="space-y-4">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="open_minutes_before" class="block text-sm font-medium text-gray-700 mb-1">Dibuka ... menit sebelum jam masuk</label>
                    <input type="number" id="open_minutes_before" name="open_minutes_before" min="0" max="240" required
                           value="{{ old('open_minutes_before', $settings['open_minutes_before']) }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label for="close_minutes_after" class="block text-sm font-medium text-gray-700 mb-1">Ditutup ... menit setelah jam pulang</label>
                    <input type="number" id="close_minutes_after" name="close_minutes_after" min="0" max="240" required
                           value="{{ old('close_minutes_after', $settings['close_minutes_after']) }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>
            <div>
                <input type="hidden" name="require_registered_device" value="0">
                <label class="inline-flex items-center">
                    <input type="checkbox" name="require_registered_device" value="1" class="rounded border-gray-300"
                           {{ old('require_registered_device', $settings['require_registered_device']) ? 'checked' : '' }}>
                    <span class="ml-2 text-gray-700">Wajibkan perangkat sekolah yang terdaftar (disarankan)</span>
                </label>
            </div>
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 font-semibold">
                Simpan Pengaturan
            </button>
        </form>
    </div>
</div>
@endsection
