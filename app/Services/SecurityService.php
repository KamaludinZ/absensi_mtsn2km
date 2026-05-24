<?php

namespace App\Services;

use App\Models\SecurityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class SecurityService
{
    /**
     * Log suspicious activity
     */
    public static function logSuspiciousActivity(string $activity, array $details = []): void
    {
        $userId = auth()->check() ? auth()->id() : null;
        
        SecurityLog::logEvent(
            'suspicious_activity',
            $activity,
            array_merge($details, [
                'request_uri' => request()->getRequestUri(),
                'method' => request()->getMethod(),
            ]),
            3, // High severity
            $userId
        );
        
        Log::warning('Suspicious activity detected: ' . $activity, [
            'user_id' => $userId,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'details' => $details,
        ]);
    }

    /**
     * Log brute force attempts
     */
    public static function logBruteForceAttempt(string $username): void
    {
        SecurityLog::logEvent(
            'brute_force_attempt',
            'Failed login attempt',
            ['username' => $username],
            4, // Critical severity
            null
        );
        
        Log::critical('Brute force login attempt', [
            'username' => $username,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Log unauthorized access attempts
     */
    public static function logUnauthorizedAccess(string $route, string $attemptedAction): void
    {
        $userId = auth()->check() ? auth()->id() : null;
        
        SecurityLog::logEvent(
            'unauthorized_access',
            'Attempted unauthorized access to ' . $route,
            ['action' => $attemptedAction],
            4, // Critical severity
            $userId
        );
        
        Log::critical('Unauthorized access attempt', [
            'user_id' => $userId,
            'route' => $route,
            'action' => $attemptedAction,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Check if IP is blocked due to repeated violations
     */
    public static function isIpBlocked(): bool
    {
        try {
            if (!Schema::hasTable('security_logs')) {
                return false;
            }

            $ipAddress = request()->ip();

            // Count security violations from the past hour
            $violationCount = SecurityLog::where('ip_address', $ipAddress)
                ->where('severity', '>=', 3)
                ->where('created_at', '>=', now()->subHour())
                ->count();

            // Block IP if it has 5 or more high-severity violations in the past hour
            return $violationCount >= 5;
        } catch (\Throwable $e) {
            Log::warning('SecurityService::isIpBlocked fallback triggered', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Block an IP address temporarily
     */
    public static function blockIp(string $reason = 'Excessive violations'): void
    {
        $ipAddress = request()->ip();
        
        SecurityLog::logEvent(
            'ip_blocked',
            'IP address blocked automatically',
            ['reason' => $reason, 'blocked_ip' => $ipAddress],
            4, // Critical severity
            null
        );
        
        // In a production environment, you might store blocked IPs in Redis or database
        // and check against them in middleware
        Log::alert('IP address blocked', [
            'ip' => $ipAddress,
            'reason' => $reason,
        ]);
    }
    
    /**
     * Check if username is blocked due to too many failed attempts
     */
    public function isUsernameBlocked(string $username): bool
    {
        $cacheKey = 'failed_login_attempts_' . $username;
        $attempts = Cache::get($cacheKey, 0);
        
        // Block if more than 5 failed attempts in the last 15 minutes
        return $attempts >= 5;
    }
    
    /**
     * Get failed attempts count for a username
     */
    public function getFailedAttemptsCount(string $username): int
    {
        $cacheKey = 'failed_login_attempts_' . $username;
        return Cache::get($cacheKey, 0);
    }
    
    /**
     * Record login attempt
     */
    public function recordLoginAttempt(string $username, bool $success, string $reason = null): void
    {
        $cacheKey = 'failed_login_attempts_' . $username;
        
        if ($success) {
            // Clear failed attempts on successful login
            Cache::forget($cacheKey);
        } else {
            // Increment failed attempts count
            $attempts = Cache::get($cacheKey, 0);
            Cache::put($cacheKey, $attempts + 1, now()->addMinutes(15));
            
            // Log the failed attempt
            SecurityLog::logEvent(
                'login_failed',
                'Failed login attempt',
                [
                    'username' => $username,
                    'reason' => $reason,
                    'ip' => request()->ip(),
                ],
                2, // Medium severity
                null
            );
        }
    }
    
    /**
     * Log a security event
     */
    public function logEvent(string $event, string $message, string $level = 'info', array $context = []): void
    {
        SecurityLog::logEvent(
            $event,
            $message,
            $context,
            $this->getSeverityLevel($level),
            auth()->check() ? auth()->id() : null
        );
        
        Log::$level($message, $context);
    }
    
    /**
     * Get security statistics for dashboard display
     */
    public function getDashboardStats()
    {
        try {
            if (!Schema::hasTable('security_logs')) {
                return [
                    'security_health_score' => 100,
                    'total_security_events_today' => 0,
                    'failed_logins_today' => 0,
                    'critical_events_today' => 0,
                    'security_recommendations' => [
                        [
                            'type' => 'info',
                            'title' => 'Keamanan Sistem',
                            'message' => 'Tabel log keamanan belum tersedia. Jalankan migrasi untuk mengaktifkan statistik keamanan.',
                            'action' => null,
                            'action_url' => null,
                        ]
                    ]
                ];
            }

            // Calculate security health score (0-100)
            $securityHealthScore = 100;
            
            // Get today's date for filtering
            $today = \Carbon\Carbon::today();
            
            // Count total security events today
            $totalSecurityEvents = \App\Models\SecurityLog::whereDate('created_at', $today)->count();
            
            // Count failed login attempts today
            $failedLogins = \App\Models\SecurityLog::where('event_type', 'login_failed')
                ->whereDate('created_at', $today)
                ->count();
            
            // Count critical events today
            $criticalEvents = \App\Models\SecurityLog::where('severity', '>=', 4)
                ->whereDate('created_at', $today)
                ->count();
            
            // Security recommendations based on recent events
            $securityRecommendations = [];
            
            // Check for brute force attempts
            if ($failedLogins > 5) {
                $securityRecommendations[] = [
                    'type' => 'warning',
                    'title' => 'Verifikasi Dua Faktor',
                    'message' => 'Terdeteksi banyak percobaan login gagal hari ini. Pertimbangkan untuk mengaktifkan verifikasi dua faktor (2FA).',
                    'action' => 'Pengaturan Keamanan',
                    'action_url' => route('admin.security.index'),
                ];
            }
            
            // Check for critical events
            if ($criticalEvents > 0) {
                $securityRecommendations[] = [
                    'type' => 'critical',
                    'title' => 'Aktivitas Mencurigakan',
                    'message' => 'Terdeteksi aktivitas mencurigakan hari ini. Periksa log keamanan untuk detail lebih lanjut.',
                    'action' => 'Lihat Log Keamanan',
                    'action_url' => route('admin.security.logs'),
                ];
            }
            
            // Add general recommendation if no issues
            if (empty($securityRecommendations)) {
                $securityRecommendations[] = [
                    'type' => 'info',
                    'title' => 'Keamanan Sistem',
                    'message' => 'Sistem keamanan berjalan dengan baik. Pertahankan praktik keamanan yang baik.',
                    'action' => null,
                    'action_url' => null,
                ];
            }
            
            // Adjust health score based on events
            if ($failedLogins > 10) {
                $securityHealthScore -= 20;
            } elseif ($failedLogins > 5) {
                $securityHealthScore -= 10;
            }
            
            if ($criticalEvents > 3) {
                $securityHealthScore -= 30;
            } elseif ($criticalEvents > 0) {
                $securityHealthScore -= 15;
            }
            
            // Ensure health score doesn't go below 0
            $securityHealthScore = max(0, $securityHealthScore);
            
            return [
                'security_health_score' => $securityHealthScore,
                'total_security_events_today' => $totalSecurityEvents,
                'failed_logins_today' => $failedLogins,
                'critical_events_today' => $criticalEvents,
                'security_recommendations' => $securityRecommendations
            ];
        } catch (\Exception $e) {
            \Log::error('Failed to get security dashboard stats: ' . $e->getMessage());
            
            // Return default values if there's an error
            return [
                'security_health_score' => 100,
                'total_security_events_today' => 0,
                'failed_logins_today' => 0,
                'critical_events_today' => 0,
                'security_recommendations' => [
                    [
                        'type' => 'info',
                        'title' => 'Keamanan Sistem',
                        'message' => 'Sistem keamanan berjalan dengan baik. Pertahankan praktik keamanan yang baik.',
                        'action' => null,
                        'action_url' => null,
                    ]
                ]
            ];
        }
    }
    
    /**
     * Convert log level to severity number
     */
    private function getSeverityLevel(string $level): int
    {
        $severityMap = [
            'debug' => 1,
            'info' => 2,
            'warning' => 3,
            'error' => 4,
            'critical' => 5,
        ];
        
        return $severityMap[$level] ?? 2; // Default to info level (2)
    }
    
    /**
     * Get security recommendations based on recent events
     */
    private function getSecurityRecommendations($totalEvents, $failedLogins, $criticalEvents): array
    {
        $recommendations = [];
        
        if ($totalEvents > 10) {
            $recommendations[] = [
                'type' => 'warning',
                'message' => 'Tingkat aktivitas mencurigakan tinggi hari ini. Pertimbangkan untuk memeriksa log keamanan secara berkala.',
                'priority' => 'high'
            ];
        }
        
        if ($failedLogins > 5) {
            $recommendations[] = [
                'type' => 'danger',
                'message' => 'Terjadi banyak upaya login gagal. Pertimbangkan untuk memblokir IP yang mencurigakan.',
                'priority' => 'high'
            ];
        }
        
        if ($criticalEvents > 2) {
            $recommendations[] = [
                'type' => 'danger',
                'message' => 'Ditemukan beberapa peristiwa kritis hari ini. Segera tinjau dan tangani masalah keamanan ini.',
                'priority' => 'urgent'
            ];
        }
        
        // Default recommendation if no issues
        if (empty($recommendations)) {
            $recommendations[] = [
                'type' => 'success',
                'message' => 'Tidak ada aktivitas mencurigakan hari ini. Sistem keamanan berjalan dengan baik.',
                'priority' => 'low'
            ];
        }
        
        return $recommendations;
    }
}
