@extends('layouts.app')

@section('title', 'Percobaan Login')
@section('page-title', 'Percobaan Login')

@section('content')
<div class="bg-white rounded-lg shadow p-6">
    <div class="mb-6">
        <h2 class="text-xl font-bold text-gray-800 mb-4">Riwayat Percobaan Login</h2>

        <form method="GET" action="{{ route('admin.security.login-attempts') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                <input type="text" name="username" value="{{ request('username') }}"
                       class="w-full border border-gray-300 rounded px-3 py-2"
                       placeholder="Cari username">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="successful" class="w-full border border-gray-300 rounded px-3 py-2">
                    <option value="">Semua</option>
                    <option value="1" {{ request('successful') === '1' ? 'selected' : '' }}>Berhasil</option>
                    <option value="0" {{ request('successful') === '0' ? 'selected' : '' }}>Gagal</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">IP Address</label>
                <input type="text" name="ip_address" value="{{ request('ip_address') }}"
                       class="w-full border border-gray-300 rounded px-3 py-2"
                       placeholder="Contoh: 192.168.1.10">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Dari Tanggal</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}"
                       class="w-full border border-gray-300 rounded px-3 py-2">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Sampai Tanggal</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}"
                       class="w-full border border-gray-300 rounded px-3 py-2">
            </div>

            <div class="md:col-span-5 flex items-end gap-2">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                    Filter
                </button>
                <a href="{{ route('admin.security.login-attempts') }}" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Waktu</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Username</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">IP Address</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">User Agent</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($attempts as $attempt)
                    <tr>
                        <td class="px-4 py-3 text-sm text-gray-700">
                            {{ optional($attempt->attempted_at)->format('d/m/Y H:i:s') ?? '-' }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700">
                            {{ $attempt->username ?? '-' }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700">
                            {{ $attempt->ip_address ?? '-' }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700 max-w-[360px] truncate" title="{{ $attempt->user_agent ?? '-' }}">
                            {{ $attempt->user_agent ?? '-' }}
                        </td>
                        <td class="px-4 py-3 text-sm">
                            @if($attempt->successful)
                                <span class="px-2 py-1 rounded text-xs font-semibold bg-green-100 text-green-800">BERHASIL</span>
                            @else
                                <span class="px-2 py-1 rounded text-xs font-semibold bg-red-100 text-red-800">GAGAL</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-gray-500">
                            Tidak ada data percobaan login
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $attempts->appends(request()->query())->links() }}
    </div>
</div>
@endsection
