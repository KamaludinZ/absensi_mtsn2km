@extends('layouts.app')

@section('title', 'Log Keamanan')
@section('page-title', 'Log Keamanan')

@section('content')
<div class="bg-white rounded-lg shadow p-6">
    <div class="mb-6">
        <h2 class="text-xl font-bold text-gray-800 mb-4">Audit Trail Keamanan</h2>

        <form method="GET" action="{{ route('admin.security.logs') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tipe Event</label>
                <select name="event_type" class="w-full border border-gray-300 rounded px-3 py-2">
                    <option value="">Semua</option>
                    @foreach($eventTypes as $type)
                        <option value="{{ $type }}" {{ request('event_type') === $type ? 'selected' : '' }}>
                            {{ $type }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Severity</label>
                <select name="severity" class="w-full border border-gray-300 rounded px-3 py-2">
                    <option value="">Semua</option>
                    @foreach($severities as $severity)
                        <option value="{{ $severity }}" {{ request('severity') === $severity ? 'selected' : '' }}>
                            {{ ucfirst($severity) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Dari Tanggal</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full border border-gray-300 rounded px-3 py-2">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Sampai Tanggal</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="w-full border border-gray-300 rounded px-3 py-2">
            </div>

            <div class="flex items-end gap-2">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                    Filter
                </button>
                <a href="{{ route('admin.security.logs') }}" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
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
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Event</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Severity</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Deskripsi</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">IP Address</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($logs as $log)
                    <tr>
                        <td class="px-4 py-3 text-sm text-gray-700">
                            {{ optional($log->created_at)->format('d/m/Y H:i:s') ?? '-' }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700">
                            {{ optional($log->user)->username ?? 'System' }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700">
                            {{ $log->event_type ?? '-' }}
                        </td>
                        <td class="px-4 py-3 text-sm">
                            @php
                                $severity = $log->severity ?? 'info';
                                $severityClass = $severity === 'critical'
                                    ? 'bg-red-100 text-red-800'
                                    : ($severity === 'warning' ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800');
                            @endphp
                            <span class="px-2 py-1 rounded text-xs font-semibold {{ $severityClass }}">
                                {{ strtoupper($severity) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700">
                            {{ $log->description ?? '-' }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700">
                            {{ $log->ip_address ?? '-' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-6 text-center text-gray-500">
                            Tidak ada data log keamanan
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $logs->appends(request()->query())->links() }}
    </div>
</div>
@endsection
