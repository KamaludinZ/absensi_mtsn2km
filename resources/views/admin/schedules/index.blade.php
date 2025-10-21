@extends('layouts.app')

@section('title', 'Pengaturan Jadwal')
@section('page-title', 'Pengaturan Jadwal')

@section('content')
<div class="bg-white rounded-lg shadow p-6">
    <div class="flex flex-wrap justify-between items-center mb-6 gap-4">
        <h2 class="text-xl font-bold text-gray-800">Daftar Jadwal Absensi</h2>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.schedules.custom.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                Tambah Jadwal
            </a>
            <a href="{{ route('admin.schedules.custom.pegawai') }}" class="bg-blue-100 text-blue-800 px-4 py-2 rounded-md hover:bg-blue-200">
                Hanya Pegawai
            </a>
            <a href="{{ route('admin.schedules.custom.siswa') }}" class="bg-green-100 text-green-800 px-4 py-2 rounded-md hover:bg-green-200">
                Hanya Siswa
            </a>
        </div>
    </div>
    
    <!-- Filter Section -->
    <div class="mb-6 p-4 bg-gray-50 rounded-lg">
        <form method="GET" class="flex flex-wrap items-center gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Filter Tipe</label>
                <select name="type" class="border border-gray-300 rounded px-3 py-2">
                    <option value="">Semua Tipe</option>
                    <option value="pegawai" {{ request('type') == 'pegawai' ? 'selected' : '' }}>Pegawai</option>
                    <option value="siswa" {{ request('type') == 'siswa' ? 'selected' : '' }}>Siswa</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Filter Shift</label>
                <select name="shift_type" class="border border-gray-300 rounded px-3 py-2">
                    <option value="">Semua Shift</option>
                    <option value="pagi" {{ request('shift_type') == 'pagi' ? 'selected' : '' }}>Pagi</option>
                    <option value="siang" {{ request('shift_type') == 'siang' ? 'selected' : '' }}>Siang</option>
                    <option value="sore" {{ request('shift_type') == 'sore' ? 'selected' : '' }}>Sore</option>
                    <option value="malam" {{ request('shift_type') == 'malam' ? 'selected' : '' }}>Malam</option>
                </select>
            </div>
            

            
            <div class="pt-6">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                    Filter
                </button>
                <a href="{{ route('admin.schedules.custom.index') }}" class="ml-2 bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600">
                    Reset
                </a>
            </div>
        </form>
    </div>
    
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipe</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Shift</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Shift</th>

                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jam Masuk</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jam Pulang</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($schedules as $schedule)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                            @if($schedule->type === 'pegawai') bg-blue-100 text-blue-800
                            @else bg-green-100 text-green-800 @endif">
                            {{ ucfirst($schedule->type) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($schedule->shift_type)
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                @switch($schedule->shift_type)
                                    @case('pagi')
                                        bg-yellow-100 text-yellow-800
                                        @break
                                    @case('siang')
                                        bg-orange-100 text-orange-800
                                        @break
                                    @case('sore')
                                        bg-red-100 text-red-800
                                        @break
                                    @case('malam')
                                        bg-purple-100 text-purple-800
                                        @break
                                @endswitch">
                                {{ ucfirst($schedule->shift_type) }}
                            </span>
                        @else
                            <span class="text-gray-500 text-xs">-</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($schedule->shift_name)
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                {{ $schedule->shift_name }}
                            </span>
                        @else
                            <span class="text-gray-500 text-xs">-</span>
                        @endif
                    </td>

                    <td class="px-6 py-4 whitespace-nowrap">{{ \Carbon\Carbon::parse($schedule->start_time)->format('H:i') }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ \Carbon\Carbon::parse($schedule->end_time)->format('H:i') }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <a href="{{ route('admin.schedules.custom.edit', $schedule) }}" class="text-blue-600 hover:text-blue-900 mr-3">Edit</a>
                        <form action="{{ route('admin.schedules.custom.destroy', $schedule) }}" method="POST" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus jadwal ini?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-900">Hapus</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-4 text-center text-gray-500">Tidak ada data jadwal</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    @if($schedules instanceof \Illuminate\Pagination\LengthAwarePaginator)
        <div class="mt-6">
            {{ $schedules->links() }}
        </div>
    @endif
</div>
@endsection