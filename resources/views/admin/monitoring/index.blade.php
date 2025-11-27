@extends('layouts.app')

@section('title', 'Monitoring Aplikasi')
@section('page-title', 'Monitoring Aplikasi')

@section('content')
<div class="card mb-8">
    <div class="p-6">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
            <div>
                <h2 class="text-heading text-gray-800">Monitoring Aplikasi</h2>
                <p class="text-gray-600 mt-2">Monitor kondisi dan respon aplikasi serta server untuk kontrol perkembangan data dan kualitas respon.</p>
            </div>
        </div>
    </div>
</div>

<!-- System Information -->
<div class="card border-card mb-8">
    <div class="p-6">
        <h3 class="text-subheading font-semibold text-gray-800 mb-6 flex items-center">
            <svg class="w-6 h-6 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
            </svg>
            Informasi Sistem
        </h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg p-4 border border-blue-100">
                <h4 class="text-sm font-medium text-blue-600 mb-1">Versi PHP</h4>
                <p class="text-xl font-bold text-blue-800">{{ $systemInfo['php_version'] }}</p>
            </div>
            
            <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-lg p-4 border border-green-100">
                <h4 class="text-sm font-medium text-green-600 mb-1">Versi Laravel</h4>
                <p class="text-xl font-bold text-green-800">{{ $systemInfo['laravel_version'] }}</p>
            </div>
            
            <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-lg p-4 border border-purple-100">
                <h4 class="text-sm font-medium text-purple-600 mb-1">Software Server</h4>
                <p class="text-xl font-bold text-purple-800">{{ $systemInfo['server_software'] ?? 'N/A' }}</p>
            </div>
            
            <div class="bg-gradient-to-br from-indigo-50 to-indigo-100 rounded-lg p-4 border border-indigo-100">
                <h4 class="text-sm font-medium text-indigo-600 mb-1">Waktu Server</h4>
                <p class="text-xl font-bold text-indigo-800">{{ $systemInfo['current_time'] }}</p>
            </div>
            
            <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 rounded-lg p-4 border border-yellow-100">
                <h4 class="text-sm font-medium text-yellow-600 mb-1">Uptime Server</h4>
                <p class="text-xl font-bold text-yellow-800">{{ $systemInfo['uptime'] }}</p>
            </div>
            
            <div class="bg-gradient-to-br from-red-50 to-red-100 rounded-lg p-4 border border-red-100">
                <h4 class="text-sm font-medium text-red-600 mb-1">Memory Limit</h4>
                <p class="text-xl font-bold text-red-800">{{ $systemInfo['memory_limit'] }}</p>
            </div>
            
            <div class="bg-gradient-to-br from-teal-50 to-teal-100 rounded-lg p-4 border border-teal-100">
                <h4 class="text-sm font-medium text-teal-600 mb-1">Max Execution Time</h4>
                <p class="text-xl font-bold text-teal-800">{{ $systemInfo['max_execution_time'] }}s</p>
            </div>
            
            <div class="bg-gradient-to-br from-pink-50 to-pink-100 rounded-lg p-4 border border-pink-100">
                <h4 class="text-sm font-medium text-pink-600 mb-1">Zona Waktu</h4>
                <p class="text-xl font-bold text-pink-800">{{ $systemInfo['timezone'] }}</p>
            </div>
        </div>
    </div>
</div>

<!-- Database Status -->
<div class="card border-card mb-8">
    <div class="p-6">
        <h3 class="text-subheading font-semibold text-gray-800 mb-6 flex items-center">
            <svg class="w-6 h-6 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
            </svg>
            Status Database
        </h3>
        
        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
            <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-lg p-4 border border-green-100">
                <h4 class="text-sm font-medium text-green-600 mb-1">Koneksi</h4>
                <p class="text-xl font-bold text-green-800">{{ $dbStatus['connection'] === 'Connected' ? 'Terhubung' : 'Tidak Terhubung' }}</p>
            </div>
            
            <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg p-4 border border-blue-100">
                <h4 class="text-sm font-medium text-blue-600 mb-1">Driver</h4>
                <p class="text-xl font-bold text-blue-800">{{ $dbStatus['driver'] }}</p>
            </div>
            
            <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-lg p-4 border border-purple-100">
                <h4 class="text-sm font-medium text-purple-600 mb-1">Database</h4>
                <p class="text-xl font-bold text-purple-800">{{ $dbStatus['database'] }}</p>
            </div>
            
            <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 rounded-lg p-4 border border-yellow-100">
                <h4 class="text-sm font-medium text-yellow-600 mb-1">Respons</h4>
                <p class="text-xl font-bold text-yellow-800">{{ $dbStatus['ping'] }}</p>
            </div>
            
            <div class="bg-gradient-to-br from-indigo-50 to-indigo-100 rounded-lg p-4 border border-indigo-100">
                <h4 class="text-sm font-medium text-indigo-600 mb-1">Jumlah Tabel</h4>
                <p class="text-xl font-bold text-indigo-800">{{ $dbStatus['tables_count'] }}</p>
            </div>
            
            <div class="bg-gradient-to-br from-teal-50 to-teal-100 rounded-lg p-4 border border-teal-100">
                <h4 class="text-sm font-medium text-teal-600 mb-1">Ukuran</h4>
                <p class="text-xl font-bold text-teal-800">{{ $dbStatus['size'] }}</p>
            </div>
        </div>
    </div>
</div>

<!-- Application Performance -->
<div class="card border-card mb-8">
    <div class="p-6">
        <h3 class="text-subheading font-semibold text-gray-800 mb-6 flex items-center">
            <svg class="w-6 h-6 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
            Kinerja Aplikasi
        </h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg p-4 border border-blue-100">
                <h4 class="text-sm font-medium text-blue-600 mb-1">Waktu Respon</h4>
                <p class="text-xl font-bold text-blue-800">{{ $performanceMetrics['response_time'] }}</p>
            </div>
            
            <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-lg p-4 border border-green-100">
                <h4 class="text-sm font-medium text-green-600 mb-1">Request Saat Ini</h4>
                <p class="text-xl font-bold text-green-800">{{ $performanceMetrics['current_requests'] }}</p>
            </div>
            
            <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-lg p-4 border border-purple-100">
                <h4 class="text-sm font-medium text-purple-600 mb-1">Cache Hit Rate</h4>
                <p class="text-xl font-bold text-purple-800">{{ $performanceMetrics['cache_hit_rate'] }}</p>
            </div>
            
            <div class="bg-gradient-to-br from-indigo-50 to-indigo-100 rounded-lg p-4 border border-indigo-100">
                <h4 class="text-sm font-medium text-indigo-600 mb-1">Jumlah Session</h4>
                <p class="text-xl font-bold text-indigo-800">{{ $performanceMetrics['session_count'] }}</p>
            </div>
        </div>
    </div>
</div>

<!-- Server Resources -->
<div class="card border-card mb-8">
    <div class="p-6">
        <h3 class="text-subheading font-semibold text-gray-800 mb-6 flex items-center">
            <svg class="w-6 h-6 mr-2 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
            </svg>
            Penggunaan Sumber Daya Server
        </h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
            <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg p-4 border border-blue-100">
                <h4 class="text-sm font-medium text-blue-600 mb-1">Penggunaan CPU</h4>
                <p class="text-xl font-bold text-blue-800">{{ $serverUsage['cpu_usage'] }}</p>
            </div>
            
            <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-lg p-4 border border-green-100">
                <h4 class="text-sm font-medium text-green-600 mb-1">Penggunaan Memori</h4>
                <p class="text-xl font-bold text-green-800">{{ $serverUsage['memory_usage'] }}</p>
            </div>
            
            <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-lg p-4 border border-purple-100">
                <h4 class="text-sm font-medium text-purple-600 mb-1">Penggunaan Disk</h4>
                <p class="text-xl font-bold text-purple-800">{{ $serverUsage['disk_usage'] }}</p>
            </div>
            
            <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 rounded-lg p-4 border border-yellow-100">
                <h4 class="text-sm font-medium text-yellow-600 mb-1">Load Average</h4>
                <p class="text-xl font-bold text-yellow-800">{{ $serverUsage['load_average']['1_min'] ?? 'N/A' }}</p>
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-gray-50 rounded-lg p-3 border border-gray-200">
                <h5 class="text-sm font-medium text-gray-700">1 Menit Terakhir</h5>
                <p class="text-lg font-semibold text-gray-800">{{ $serverUsage['load_average']['1_min'] ?? 'N/A' }}</p>
            </div>
            <div class="bg-gray-50 rounded-lg p-3 border border-gray-200">
                <h5 class="text-sm font-medium text-gray-700">5 Menit Terakhir</h5>
                <p class="text-lg font-semibold text-gray-800">{{ $serverUsage['load_average']['5_min'] ?? 'N/A' }}</p>
            </div>
            <div class="bg-gray-50 rounded-lg p-3 border border-gray-200">
                <h5 class="text-sm font-medium text-gray-700">15 Menit Terakhir</h5>
                <p class="text-lg font-semibold text-gray-800">{{ $serverUsage['load_average']['15_min'] ?? 'N/A' }}</p>
            </div>
        </div>
    </div>
</div>

<!-- Recent Logs -->
<div class="card border-card mb-8">
    <div class="p-6">
        <h3 class="text-subheading font-semibold text-gray-800 mb-6 flex items-center">
            <svg class="w-6 h-6 mr-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Log Terbaru
        </h3>
        
        @if(!empty($recentLogs))
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Waktu</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Level</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pesan</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($recentLogs as $log)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $log['timestamp'] ?? 'N/A' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    @if($log['level'] === 'error' || $log['level'] === 'critical') bg-red-100 text-red-800
                                    @elseif($log['level'] === 'warning') bg-yellow-100 text-yellow-800
                                    @elseif($log['level'] === 'info') bg-blue-100 text-blue-800
                                    @else bg-gray-100 text-gray-800
                                    @endif">
                                    {{ $log['level'] }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">{{ Str::limit($log['message'], 100) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-8">
                <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <p class="text-gray-500">Tidak ada log terbaru</p>
            </div>
        @endif
    </div>
</div>

{{-- Refresh Button --}}
<div class="flex justify-end mb-8">
    <button onclick="location.reload()" class="btn btn-primary flex items-center">
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
        </svg>
        Refresh
    </button>
</div>
@endsection