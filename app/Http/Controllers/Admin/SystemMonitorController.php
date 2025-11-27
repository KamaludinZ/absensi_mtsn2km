<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SystemMonitorController extends Controller
{
    public function index()
    {
        // Get system information
        $systemInfo = $this->getSystemInfo();
        
        // Get database status
        $dbStatus = $this->getDatabaseStatus();
        
        // Get application performance metrics
        $performanceMetrics = $this->getApplicationPerformance();
        
        // Get recent logs (if available)
        $recentLogs = $this->getRecentLogs();
        
        // Get server resource usage
        $serverUsage = $this->getServerResources();
        
        return view('admin.monitoring.index', compact(
            'systemInfo',
            'dbStatus',
            'performanceMetrics',
            'recentLogs',
            'serverUsage'
        ));
    }

    private function getSystemInfo()
    {
        return [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'timezone' => config('app.timezone'),
            'locale' => app()->getLocale(),
            'current_time' => now()->format('Y-m-d H:i:s'),
            'uptime' => $this->getUptime()
        ];
    }

    private function getDatabaseStatus()
    {
        try {
            // Test database connection
            $start = microtime(true);
            DB::connection()->getPdo();
            $end = microtime(true);

            // Get database info
            $dbInfo = [
                'connection' => 'Connected',
                'driver' => DB::getDriverName(),
                'database' => DB::getDatabaseName(),
                'ping' => round(($end - $start) * 1000, 2) . ' ms',
                'tables_count' => count(DB::select('SHOW TABLES')),
                'size' => $this->getDatabaseSize()
            ];

            return $dbInfo;
        } catch (\Exception $e) {
            return [
                'connection' => 'Failed: ' . $e->getMessage(),
                'driver' => 'Unknown',
                'database' => 'Unknown',
                'ping' => 'Error',
                'tables_count' => 0,
                'size' => 0
            ];
        }
    }

    private function getApplicationPerformance()
    {
        // Calculate response time for this specific request since we're already in the request
        // We'll use a more typical approach by measuring a simple operation
        $start = microtime(true);
        // Perform a simple operation to measure response capability
        $test = md5(time());
        $responseTime = round((microtime(true) - $start) * 1000, 2);

        return [
            'response_time' => $responseTime . ' ms',
            'current_requests' => $this->getCurrentRequestCount(),
            'cache_hit_rate' => $this->getCacheHitRate(),
            'session_count' => $this->getSessionCount(),
            'recent_activity' => $this->getRecentActivity()
        ];
    }

    private function getRecentLogs($limit = 10)
    {
        $logPath = storage_path('logs/laravel.log');
        
        if (!file_exists($logPath)) {
            return [];
        }
        
        $logContent = file_get_contents($logPath);
        $lines = explode("\n", $logContent);
        $lines = array_filter($lines, function($line) {
            return !empty(trim($line));
        });
        $lines = array_slice($lines, -$limit);
        
        $logs = [];
        foreach ($lines as $line) {
            $timestamp = null;
            if (preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $line, $matches)) {
                $timestamp = $matches[1];
            }
            
            $logLevel = 'info';
            if (preg_match('/\.(ERROR|WARNING|DEBUG|INFO|CRITICAL|ALERT|EMERGENCY|NOTICE)/i', $line, $matches)) {
                $logLevel = strtolower($matches[1]);
            }
            
            $logs[] = [
                'timestamp' => $timestamp,
                'level' => $logLevel,
                'message' => trim(substr($line, strpos($line, ']') + 1))
            ];
        }
        
        return array_reverse($logs);
    }

    private function getServerResources()
    {
        $cpuUsage = $this->getCpuUsage();
        $memoryUsage = $this->getMemoryUsage();
        $diskUsage = $this->getDiskUsage();
        
        return [
            'cpu_usage' => $cpuUsage,
            'memory_usage' => $memoryUsage,
            'disk_usage' => $diskUsage,
            'load_average' => $this->getLoadAverage()
        ];
    }

    private function getUptime()
    {
        if (PHP_OS_FAMILY === 'Windows') {
            // On Windows, check if COM extension is available
            if (class_exists('COM')) {
                try {
                    $wmi = new \COM('Winmgmts:');
                    $os = $wmi->ExecQuery('SELECT LastBootUpTime FROM Win32_OperatingSystem')->Item();
                    $bootTime = $os->LastBootUpTime;
                    $bootDateTime = new \DateTime(substr($bootTime, 0, 14));
                    $uptime = $bootDateTime->diff(new \DateTime());
                    return $uptime->format('%d days, %h hours, %i minutes');
                } catch (\Exception $e) {
                    // If WMI fails, use PowerShell as fallback
                    $output = shell_exec('powershell -command "(Get-CimInstance -ClassName Win32_OperatingSystem).LastBootUpTime"');
                    if ($output) {
                        $bootTime = trim($output);
                        if ($bootTime && \DateTime::createFromFormat('Y-m-d H:i:s', $bootTime)) {
                            $bootDateTime = new \DateTime($bootTime);
                            $uptime = $bootDateTime->diff(new \DateTime());
                            return $uptime->format('%d days, %h hours, %i minutes');
                        }
                    }
                    return 'Unknown';
                }
            } else {
                // If COM is not available, use PowerShell
                $output = shell_exec('powershell -command "(Get-CimInstance -ClassName Win32_OperatingSystem).LastBootUpTime"');
                if ($output) {
                    $bootTime = trim($output);
                    if ($bootTime && \DateTime::createFromFormat('Y-m-d H:i:s', $bootTime)) {
                        $bootDateTime = new \DateTime($bootTime);
                        $uptime = $bootDateTime->diff(new \DateTime());
                        return $uptime->format('%d days, %h hours, %i minutes');
                    }
                }
                return 'Unknown';
            }
        } else {
            // On Unix-like systems
            if (file_exists('/proc/uptime')) {
                $uptime = file_get_contents('/proc/uptime');
                $uptime = explode(' ', $uptime)[0];
                $uptime = round($uptime);

                $days = floor($uptime / 86400);
                $hours = floor(($uptime % 86400) / 3600);
                $minutes = floor(($uptime % 3600) / 60);

                return "$days days, $hours hours, $minutes minutes";
            }
            return 'Unknown';
        }
    }

    private function getDatabaseSize()
    {
        try {
            $driver = DB::getDriverName();

            if ($driver === 'mysql' || $driver === 'mariadb') {
                // MySQL/MariaDB query
                $result = DB::select("SELECT SUM(data_length + index_length) as size FROM information_schema.tables WHERE table_schema = ?", [DB::getDatabaseName()]);
                $size = $result[0]->size ?? 0;
                return round($size / (1024 * 1024), 2) . ' MB';
            } elseif ($driver === 'pgsql') {
                // PostgreSQL query
                $result = DB::select("SELECT pg_database_size(?) as size", [DB::getDatabaseName()]);
                $size = $result[0]->size ?? 0;
                return round($size / (1024 * 1024), 2) . ' MB';
            } else {
                // For SQLite or other databases
                $size = 0;
                $connection = DB::connection();
                $dbName = $connection->getDatabaseName();

                if ($connection->getDriverName() === 'sqlite' && file_exists($dbName)) {
                    $size = filesize($dbName);
                    return round($size / (1024 * 1024), 2) . ' MB';
                }

                return 'Unknown';
            }
        } catch (\Exception $e) {
            return 'Unknown';
        }
    }

    private function getCurrentRequestCount()
    {
        // Simple implementation - in a real app you might want to track active sessions
        return session()->all() ? count(session()->all()) : 0;
    }

    private function getCacheHitRate()
    {
        try {
            $total = Cache::get('cache_total_requests', 0);
            $hits = Cache::get('cache_hits', 0);
            
            if ($total > 0) {
                return round(($hits / $total) * 100, 2) . '%';
            }
            return '0%';
        } catch (\Exception $e) {
            return 'N/A';
        }
    }

    private function getSessionCount()
    {
        $sessionPath = storage_path('framework/sessions');
        if (is_dir($sessionPath)) {
            $files = glob($sessionPath . '/*');
            return count(array_filter($files, function($file) {
                // Only count non-empty session files (active sessions)
                return is_file($file) && filesize($file) > 0;
            }));
        }
        return 0;
    }

    private function getRecentActivity()
    {
        // This would typically check for recent user activity, active sessions, etc.
        return [
            'active_users' => 0,
            'recent_logins' => 0,
            'recent_requests' => 0
        ];
    }

    private function getCpuUsage()
    {
        if (PHP_OS_FAMILY === 'Windows') {
            try {
                // Try to get CPU usage on Windows using PowerShell
                $output = shell_exec('powershell -command "(Get-WmiObject -Class Win32_Processor | Measure-Object -Property LoadPercentage -Average).Average"');
                if ($output !== null && is_numeric(trim($output))) {
                    return round((float)trim($output), 2) . '%';
                }
            } catch (\Exception $e) {
                // If PowerShell fails, return N/A
            }
            return 'N/A';
        }

        try {
            $load = sys_getloadavg();
            $numCpus = shell_exec('nproc');
            if ($numCpus) {
                $cpuUsage = $load[0] / (int)$numCpus * 100;
                return round($cpuUsage, 2) . '%';
            } else {
                // If nproc is not available, just return the load average
                return round($load[0], 2) . '% (est.)';
            }
        } catch (\Exception $e) {
            return 'N/A';
        }
    }

    private function getMemoryUsage()
    {
        if (PHP_OS_FAMILY === 'Windows') {
            try {
                // On Windows we'll use a different approach
                $output = shell_exec('wmic computersystem get TotalPhysicalMemory /value 2>nul');
                $total = preg_match('/TotalPhysicalMemory=(\d+)/', $output, $matches) ? (int)$matches[1] : 0;

                $output = shell_exec('wmic OS get FreePhysicalMemory /value 2>nul');
                $free = preg_match('/FreePhysicalMemory=(\d+)/', $output, $matches) ? (int)$matches[1] * 1024 : 0; // FreePhysicalMemory is in KB

                if ($total > 0) {
                    $used = $total - $free;
                    return round(($used / $total) * 100, 2) . '%';
                }

                // Fallback for systems where wmic fails
                $output = shell_exec('powershell -command "(Get-WmiObject -Class Win32_OperatingSystem).TotalVisibleMemorySize * 1KB"');
                if ($output) {
                    $total = (int)trim($output);
                    $free = (int)shell_exec('powershell -command "(Get-WmiObject -Class Win32_OperatingSystem).FreePhysicalMemory * 1KB"');
                    if ($total > 0) {
                        $used = $total - $free;
                        return round(($used / $total) * 100, 2) . '%';
                    }
                }
            } catch (\Exception $e) {
                // If all methods fail, return a default value
            }

            return 'N/A';
        }

        $memory = shell_exec('free');
        $memory = explode("\n", $memory);
        $memory = array_filter($memory, function($line) {
            return strpos($line, 'Mem:') !== false;
        });

        if (!empty($memory)) {
            $memory = explode(' ', trim(array_shift($memory)));
            $memory = array_filter($memory, function($value) {
                return $value !== '';
            });

            if (count($memory) >= 3) {
                $total = $memory[1];
                $used = $memory[2];
                return round(($used / $total) * 100, 2) . '%';
            }
        }

        return 'N/A';
    }

    private function getDiskUsage()
    {
        $total = disk_total_space('/');
        $free = disk_free_space('/');
        $used = $total - $free;
        
        if ($total > 0) {
            return round(($used / $total) * 100, 2) . '%';
        }
        
        return 'N/A';
    }

    private function getLoadAverage()
    {
        if (PHP_OS_FAMILY === 'Windows') {
            // On Windows, we'll try to get a similar metric using PowerShell
            try {
                // Get CPU queue length (similar to load average concept)
                $output = shell_exec('powershell -command "(Get-WmiObject -Class Win32_PerfFormattedData_PerfOS_System).ProcessorQueueLength"');
                $queueLength = is_numeric(trim($output)) ? (int)trim($output) : 0;

                return [
                    '1_min' => $queueLength,
                    '5_min' => $queueLength, // Windows doesn't have the same concept of load averages
                    '15_min' => $queueLength
                ];
            } catch (\Exception $e) {
                return [
                    '1_min' => 'N/A',
                    '5_min' => 'N/A',
                    '15_min' => 'N/A'
                ];
            }
        }

        $load = sys_getloadavg();
        return [
            '1_min' => round($load[0], 2),
            '5_min' => round($load[1], 2),
            '15_min' => round($load[2], 2)
        ];
    }
}