@extends('layouts.app')

@section('title', 'Keamanan Sistem')
@section('page-title', 'Dashboard Keamanan Sistem')

@section('content')
<div class="mb-6">
    <div class="bg-gradient-to-r from-indigo-500 to-purple-600 rounded-lg shadow-lg p-6 text-white">
        <h2 class="text-2xl font-bold mb-2 flex items-center">
            <svg class="w-8 h-8 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
            </svg>
            Status Keamanan Sistem
        </h2>
        <p class="opacity-90">Monitor dan kelola keamanan aplikasi absensi secara real-time</p>
    </div>
</div>

{{-- Security Health Score --}}
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600 mb-1">Skor Keamanan</p>
                <p class="text-4xl font-bold {{ $security_health_score >= 80 ? 'text-green-600' : ($security_health_score >= 60 ? 'text-yellow-600' : 'text-red-600') }}">
                    {{ $security_health_score }}%
                </p>
                <p class="text-xs text-gray-500 mt-1">
                    @if($security_health_score >= 80)
                        Sangat Baik
                    @elseif($security_health_score >= 60)
                        Perlu Perhatian
                    @else
                        Kritis
                    @endif
                </p>
            </div>
            <svg class="w-16 h-16 {{ $security_health_score >= 80 ? 'text-green-600' : ($security_health_score >= 60 ? 'text-yellow-600' : 'text-red-600') }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600 mb-1">Event Hari Ini</p>
                <p class="text-4xl font-bold text-blue-600">{{ $total_security_events_today }}</p>
                <p class="text-xs text-gray-500 mt-1">Total aktivitas</p>
            </div>
            <svg class="w-16 h-16 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600 mb-1">Login Gagal</p>
                <p class="text-4xl font-bold text-orange-600">{{ $failed_logins_today }}</p>
                <p class="text-xs text-gray-500 mt-1">Hari ini</p>
            </div>
            <svg class="w-16 h-16 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600 mb-1">Event Kritis</p>
                <p class="text-4xl font-bold text-red-600">{{ $critical_events_today }}</p>
                <p class="text-xs text-gray-500 mt-1">Perlu tindakan</p>
            </div>
            <svg class="w-16 h-16 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
        </div>
    </div>
</div>

{{-- Quick Actions --}}
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    <a href="{{ route('admin.security.logs') }}" class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition">
        <div class="flex items-center">
            <svg class="w-12 h-12 text-indigo-600 mr-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <div>
                <h3 class="font-bold text-lg text-gray-800">Log Keamanan</h3>
                <p class="text-sm text-gray-600">Lihat semua aktivitas keamanan</p>
            </div>
        </div>
    </a>

    <a href="{{ route('admin.security.login-attempts') }}" class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition">
        <div class="flex items-center">
            <svg class="w-12 h-12 text-purple-600 mr-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
            </svg>
            <div>
                <h3 class="font-bold text-lg text-gray-800">Percobaan Login</h3>
                <p class="text-sm text-gray-600">Monitor aktivitas login</p>
            </div>
        </div>
    </a>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <svg class="w-12 h-12 text-green-600 mr-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
            </svg>
            <div>
                <h3 class="font-bold text-lg text-gray-800">Sesi Aktif</h3>
                <p class="text-sm text-gray-600">{{ $active_sessions }} user aktif</p>
            </div>
        </div>
    </div>
</div>

{{-- Security Recommendations --}}
<div class="bg-white rounded-lg shadow p-6 mb-6">
    <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
        <svg class="w-6 h-6 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        Rekomendasi Keamanan
    </h3>

    <div class="space-y-3">
        @foreach($security_recommendations as $recommendation)
        <div class="border-l-4 p-4 rounded
            @if($recommendation['type'] === 'critical') border-red-500 bg-red-50
            @elseif($recommendation['type'] === 'warning') border-yellow-500 bg-yellow-50
            @else border-green-500 bg-green-50
            @endif">
            <div class="flex items-start">
                <div class="flex-shrink-0 mr-3">
                    @if($recommendation['type'] === 'critical')
                        <svg class="w-6 h-6 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"/>
                        </svg>
                    @elseif($recommendation['type'] === 'warning')
                        <svg class="w-6 h-6 text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"/>
                        </svg>
                    @else
                        <svg class="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/>
                        </svg>
                    @endif
                </div>
                <div class="flex-1">
                    <h4 class="font-semibold
                        @if($recommendation['type'] === 'critical') text-red-800
                        @elseif($recommendation['type'] === 'warning') text-yellow-800
                        @else text-green-800
                        @endif">
                        {{ $recommendation['title'] }}
                    </h4>
                    <p class="text-sm mt-1
                        @if($recommendation['type'] === 'critical') text-red-700
                        @elseif($recommendation['type'] === 'warning') text-yellow-700
                        @else text-green-700
                        @endif">
                        {{ $recommendation['description'] }}
                    </p>
                    @if($recommendation['action'])
                        <a href="{{ $recommendation['action_url'] }}" class="inline-block mt-2 text-sm font-semibold
                            @if($recommendation['type'] === 'critical') text-red-600 hover:text-red-800
                            @elseif($recommendation['type'] === 'warning') text-yellow-600 hover:text-yellow-800
                            @else text-green-600 hover:text-green-800
                            @endif">
                            {{ $recommendation['action'] }} →
                        </a>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>

{{-- Recent Security Events --}}
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Event Keamanan Terakhir</h3>
        <div class="space-y-3">
            @forelse($recent_security_events->take(5) as $event)
            <div class="flex items-start pb-3 border-b border-gray-200 last:border-0">
                <div class="flex-shrink-0 mr-3">
                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full
                        @if($event->severity === 'critical') bg-red-100 text-red-600
                        @elseif($event->severity === 'warning') bg-yellow-100 text-yellow-600
                        @else bg-blue-100 text-blue-600
                        @endif">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"/>
                        </svg>
                    </span>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900">{{ $event->description }}</p>
                    <p class="text-xs text-gray-500 mt-1">
                        {{ $event->user ? $event->user->username : 'System' }} • {{ $event->created_at->diffForHumans() }}
                    </p>
                </div>
            </div>
            @empty
            <p class="text-gray-500 text-center py-4">Belum ada event keamanan</p>
            @endforelse
        </div>
        @if($recent_security_events->count() > 5)
        <div class="mt-4 text-center">
            <a href="{{ route('admin.security.logs') }}" class="text-sm text-indigo-600 hover:text-indigo-800 font-semibold">
                Lihat Semua Event →
            </a>
        </div>
        @endif
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Percobaan Login Gagal Terakhir</h3>
        <div class="space-y-3">
            @forelse($recent_failed_logins->take(5) as $attempt)
            <div class="flex items-start pb-3 border-b border-gray-200 last:border-0">
                <div class="flex-shrink-0 mr-3">
                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-red-100 text-red-600">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z"/>
                        </svg>
                    </span>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900">{{ $attempt->username }}</p>
                    <p class="text-xs text-gray-500 mt-1">
                        IP: {{ $attempt->ip_address }} • {{ $attempt->attempted_at->diffForHumans() }}
                    </p>
                </div>
            </div>
            @empty
            <p class="text-gray-500 text-center py-4">Tidak ada percobaan login gagal</p>
            @endforelse
        </div>
        @if($recent_failed_logins->count() > 5)
        <div class="mt-4 text-center">
            <a href="{{ route('admin.security.login-attempts') }}" class="text-sm text-indigo-600 hover:text-indigo-800 font-semibold">
                Lihat Semua Percobaan →
            </a>
        </div>
        @endif
    </div>
</div>

{{-- Top Targeted Accounts & Suspicious IPs --}}
@if($most_targeted_accounts->count() > 0 || $suspicious_ips->count() > 0)
<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
    @if($most_targeted_accounts->count() > 0)
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Akun Paling Ditargetkan (7 Hari Terakhir)</h3>
        <div class="space-y-2">
            @foreach($most_targeted_accounts as $account)
            <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                <span class="font-medium text-gray-900">{{ $account->username }}</span>
                <span class="px-3 py-1 bg-red-100 text-red-800 rounded-full text-sm font-semibold">
                    {{ $account->attempt_count }} percobaan
                </span>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    @if($suspicious_ips->count() > 0)
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4">IP Mencurigakan (7 Hari Terakhir)</h3>
        <div class="space-y-2">
            @foreach($suspicious_ips as $ip)
            <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                <span class="font-mono text-gray-900">{{ $ip->ip_address }}</span>
                <span class="px-3 py-1 bg-orange-100 text-orange-800 rounded-full text-sm font-semibold">
                    {{ $ip->attempt_count }} percobaan
                </span>
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>
@endif

@endsection
