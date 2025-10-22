<?php

namespace App\Services;

use App\Models\SecurityLog;
use App\Models\LoginAttempt;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class SecurityService
{
    /**
     * Get security dashboard statistics with caching
     */
    public function getDashboardStats(): array
    {
        // Cache the entire dashboard stats for 5 minutes to avoid excessive queries
        return Cache::remember('security_dashboard_stats', 300, function () {
            try {
                return $this->calculateDashboardStats();
            } catch (\Exception $e) {
                return $this->getDefaultStats();
            }
        });
    }

    /**
     * Calculate dashboard stats (called from cache)
     */
    private function calculateDashboardStats(): array
    {
        // Get all stats in optimized way - fetch each query result once
        $failedLoginsToday = $this->getTodayFailedLogins();
        $criticalEventsToday = $this->getTodayCriticalEvents();
        $backupCount = $this->getBackupCount();

        return [
            'total_security_events_today' => $this->getTodaySecurityEvents(),
            'failed_logins_today' => $failedLoginsToday,
            'critical_events_today' => $criticalEventsToday,
            'active_sessions' => $this->getActiveSessions(),
            'recent_security_events' => $this->getRecentSecurityEvents(10),
            'recent_failed_logins' => $this->getRecentFailedLogins(10),
            'security_health_score' => $this->calculateSecurityHealthScoreOptimized($failedLoginsToday, $criticalEventsToday, $backupCount),
            'security_recommendations' => $this->getSecurityRecommendationsOptimized($failedLoginsToday, $criticalEventsToday, $backupCount),
            'most_targeted_accounts' => $this->getMostTargetedAccounts(5),
            'suspicious_ips' => $this->getSuspiciousIPs(5),
        ];
    }

    /**
     * Get default stats when error occurs
     */
    private function getDefaultStats(): array
    {
        // Return default values if tables don't exist yet
        return [
            'total_security_events_today' => 0,
            'failed_logins_today' => 0,
            'critical_events_today' => 0,
            'active_sessions' => 0,
            'recent_security_events' => collect(),
            'recent_failed_logins' => collect(),
            'security_health_score' => 100,
            'security_recommendations' => [[
                'type' => 'info',
                'title' => 'Setup Security Monitoring',
                'description' => 'Jalankan migration untuk mengaktifkan fitur monitoring keamanan.',
                'action' => null,
                'action_url' => null,
            ]],
            'most_targeted_accounts' => collect(),
            'suspicious_ips' => collect(),
        ];
    }

    /**
     * Get today's security events count
     */
    private function getTodaySecurityEvents(): int
    {
        return SecurityLog::whereDate('created_at', Carbon::today())->count();
    }

    /**
     * Get today's failed login attempts
     */
    private function getTodayFailedLogins(): int
    {
        return LoginAttempt::where('successful', false)
            ->whereDate('attempted_at', Carbon::today())
            ->count();
    }

    /**
     * Get today's critical security events
     */
    private function getTodayCriticalEvents(): int
    {
        return SecurityLog::where('severity', 'critical')
            ->whereDate('created_at', Carbon::today())
            ->count();
    }

    /**
     * Get active sessions count (approximation based on recent activity)
     */
    private function getActiveSessions(): int
    {
        // Count users with activity in last 30 minutes
        return SecurityLog::where('created_at', '>=', Carbon::now()->subMinutes(30))
            ->distinct('user_id')
            ->count('user_id');
    }

    /**
     * Get recent security events
     */
    private function getRecentSecurityEvents(int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return SecurityLog::with('user:id,username')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get recent failed login attempts
     */
    private function getRecentFailedLogins(int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return LoginAttempt::where('successful', false)
            ->orderBy('attempted_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get backup count (cached helper method)
     */
    private function getBackupCount(): int
    {
        return \Illuminate\Support\Facades\Storage::disk('local')->exists('backups')
            ? count(\Illuminate\Support\Facades\Storage::disk('local')->files('backups'))
            : 0;
    }

    /**
     * Calculate security health score (0-100) - OPTIMIZED VERSION
     * Accepts pre-fetched values to avoid redundant queries
     */
    private function calculateSecurityHealthScoreOptimized(int $failedLoginsToday, int $criticalEventsToday, int $backupCount): int
    {
        $score = 100;

        // Deduct points for failed logins
        if ($failedLoginsToday > 10) {
            $score -= min(20, $failedLoginsToday);
        }

        // Deduct points for critical events
        if ($criticalEventsToday > 0) {
            $score -= min(30, $criticalEventsToday * 10);
        }

        // Deduct points for no recent backups
        if ($backupCount === 0) {
            $score -= 15;
        }

        // Check if HTTPS is enabled
        if (!request()->secure() && app()->environment('production')) {
            $score -= 20;
        }

        // Check if debug mode is disabled in production
        if (config('app.debug') && app()->environment('production')) {
            $score -= 25;
        }

        return max(0, $score);
    }

    /**
     * Calculate security health score (0-100) - LEGACY METHOD (kept for backward compatibility)
     */
    private function calculateSecurityHealthScore(): int
    {
        $failedLoginsToday = $this->getTodayFailedLogins();
        $criticalEventsToday = $this->getTodayCriticalEvents();
        $backupCount = $this->getBackupCount();

        return $this->calculateSecurityHealthScoreOptimized($failedLoginsToday, $criticalEventsToday, $backupCount);
    }

    /**
     * Get security recommendations - OPTIMIZED VERSION
     * Accepts pre-fetched values to avoid redundant queries
     */
    private function getSecurityRecommendationsOptimized(int $failedLoginsToday, int $criticalEventsToday, int $backupCount): array
    {
        $recommendations = [];

        // Check for high failed login attempts
        if ($failedLoginsToday > 10) {
            $recommendations[] = [
                'type' => 'warning',
                'title' => 'Login Gagal Tinggi',
                'description' => 'Terdeteksi banyak percobaan login gagal hari ini. Pertimbangkan untuk meninjau log keamanan.',
                'action' => 'Lihat Log Login',
                'action_url' => route('admin.security.login-attempts'),
            ];
        }

        // Check for critical events
        if ($criticalEventsToday > 0) {
            $recommendations[] = [
                'type' => 'critical',
                'title' => 'Event Kritis Terdeteksi',
                'description' => 'Terdapat event keamanan kritis yang memerlukan perhatian segera.',
                'action' => 'Lihat Event Kritis',
                'action_url' => route('admin.security.logs', ['severity' => 'critical']),
            ];
        }

        // Check HTTPS
        if (!request()->secure() && app()->environment('production')) {
            $recommendations[] = [
                'type' => 'warning',
                'title' => 'HTTPS Tidak Aktif',
                'description' => 'Aplikasi tidak menggunakan HTTPS. Aktifkan HTTPS untuk keamanan yang lebih baik.',
                'action' => null,
                'action_url' => null,
            ];
        }

        // Check debug mode
        if (config('app.debug') && app()->environment('production')) {
            $recommendations[] = [
                'type' => 'critical',
                'title' => 'Debug Mode Aktif di Production',
                'description' => 'Debug mode masih aktif di environment production. Segera nonaktifkan!',
                'action' => null,
                'action_url' => null,
            ];
        }

        // Check for backup
        if ($backupCount === 0) {
            $recommendations[] = [
                'type' => 'warning',
                'title' => 'Tidak Ada Backup',
                'description' => 'Belum ada backup database. Lakukan backup secara berkala untuk keamanan data.',
                'action' => 'Buat Backup',
                'action_url' => route('admin.backup.index'),
            ];
        }

        // If no issues found
        if (empty($recommendations)) {
            $recommendations[] = [
                'type' => 'success',
                'title' => 'Keamanan Baik',
                'description' => 'Tidak ada masalah keamanan yang terdeteksi saat ini.',
                'action' => null,
                'action_url' => null,
            ];
        }

        return $recommendations;
    }

    /**
     * Get security recommendations - LEGACY METHOD (kept for backward compatibility)
     */
    private function getSecurityRecommendations(): array
    {
        $failedLoginsToday = $this->getTodayFailedLogins();
        $criticalEventsToday = $this->getTodayCriticalEvents();
        $backupCount = $this->getBackupCount();

        return $this->getSecurityRecommendationsOptimized($failedLoginsToday, $criticalEventsToday, $backupCount);
    }

    /**
     * Get most targeted accounts (accounts with most failed login attempts)
     */
    private function getMostTargetedAccounts(int $limit = 5): \Illuminate\Support\Collection
    {
        return LoginAttempt::select('username', DB::raw('COUNT(*) as attempt_count'))
            ->where('successful', false)
            ->where('attempted_at', '>=', Carbon::now()->subDays(7))
            ->groupBy('username')
            ->orderBy('attempt_count', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get suspicious IP addresses (IPs with most failed attempts)
     */
    private function getSuspiciousIPs(int $limit = 5): \Illuminate\Support\Collection
    {
        return LoginAttempt::select('ip_address', DB::raw('COUNT(*) as attempt_count'))
            ->where('successful', false)
            ->where('attempted_at', '>=', Carbon::now()->subDays(7))
            ->groupBy('ip_address')
            ->orderBy('attempt_count', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Log security event
     */
    public function logEvent(string $eventType, string $description, string $severity = 'info', array $metadata = []): void
    {
        SecurityLog::log($eventType, $description, $severity, $metadata);
    }

    /**
     * Record login attempt
     */
    public function recordLoginAttempt(string $username, bool $successful, ?string $failureReason = null): void
    {
        LoginAttempt::recordAttempt($username, $successful, $failureReason);

        // Also log to security log if failed
        if (!$successful) {
            $this->logEvent(
                'login_failed',
                "Percobaan login gagal untuk username: {$username}",
                'warning',
                ['username' => $username, 'reason' => $failureReason]
            );
        }
    }

    /**
     * Check if username is blocked due to too many failed attempts
     */
    public function isUsernameBlocked(string $username): bool
    {
        return LoginAttempt::isBlocked($username);
    }

    /**
     * Get failed attempts count for username
     */
    public function getFailedAttemptsCount(string $username): int
    {
        return LoginAttempt::getFailedAttemptsCount($username);
    }
}
