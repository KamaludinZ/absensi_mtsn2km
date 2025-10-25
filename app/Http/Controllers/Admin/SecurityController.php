<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\SecurityService;
use App\Models\SecurityLog;
use App\Models\LoginAttempt;

class SecurityController extends Controller
{
    protected $securityService;

    public function __construct(SecurityService $securityService)
    {
        $this->securityService = $securityService;
    }

    /**
     * Display security dashboard
     */
    public function index()
    {
        $stats = $this->securityService->getDashboardStats();
        
        // Calculate active sessions (sessions active in last 30 minutes)
        // The sessions table stores last_activity as Unix timestamp
        $activeSessions = \DB::table('sessions')
            ->where('last_activity', '>', now()->subMinutes(30)->timestamp)
            ->count();
        
        $stats['active_sessions'] = $activeSessions;
        
        // Get recent security events
        $recentSecurityEvents = \App\Models\SecurityLog::with('user:id,username')
            ->orderBy('created_at', 'desc')
            ->limit(10) // Get 10 for potential use, but display 5 in template
            ->get();
        
        $stats['recent_security_events'] = $recentSecurityEvents;
        
        // Get recent failed logins
        $recentFailedLogins = \App\Models\LoginAttempt::where('successful', false)
            ->orderBy('attempted_at', 'desc')
            ->limit(10) // Get 10 for potential use, but display 5 in template
            ->get();
        
        $stats['recent_failed_logins'] = $recentFailedLogins;
        
        // Get most targeted accounts (failed login attempts in last 7 days)
        $mostTargetedAccounts = \App\Models\LoginAttempt::select('username', \DB::raw('COUNT(*) as attempt_count'))
            ->where('successful', false)
            ->where('attempted_at', '>', now()->subDays(7))
            ->groupBy('username')
            ->orderBy('attempt_count', 'desc')
            ->limit(5)
            ->get();
        
        $stats['most_targeted_accounts'] = $mostTargetedAccounts;
        
        // Get suspicious IPs (failed attempts in last 7 days)
        $suspiciousIPs = \App\Models\LoginAttempt::select('ip_address', \DB::raw('COUNT(*) as attempt_count'))
            ->where('successful', false)
            ->where('attempted_at', '>', now()->subDays(7))
            ->groupBy('ip_address')
            ->orderBy('attempt_count', 'desc')
            ->limit(5)
            ->get();
        
        $stats['suspicious_ips'] = $suspiciousIPs;

        return view('admin.security.index', $stats);
    }

    /**
     * Display security logs
     */
    public function logs(Request $request)
    {
        $query = SecurityLog::with('user:id,username')->orderBy('created_at', 'desc');

        // Filter by event type
        if ($request->filled('event_type')) {
            $query->where('event_type', $request->event_type);
        }

        // Filter by severity
        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->paginate(50);

        $eventTypes = SecurityLog::distinct()->pluck('event_type');
        $severities = ['info', 'warning', 'critical'];

        return view('admin.security.logs', compact('logs', 'eventTypes', 'severities'));
    }

    /**
     * Display login attempts
     */
    public function loginAttempts(Request $request)
    {
        $query = LoginAttempt::orderBy('attempted_at', 'desc');

        // Filter by username
        if ($request->filled('username')) {
            $query->where('username', 'like', '%' . $request->username . '%');
        }

        // Filter by success status
        if ($request->filled('successful')) {
            $query->where('successful', $request->successful === '1');
        }

        // Filter by IP address
        if ($request->filled('ip_address')) {
            $query->where('ip_address', 'like', '%' . $request->ip_address . '%');
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('attempted_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('attempted_at', '<=', $request->date_to);
        }

        $attempts = $query->paginate(50);

        return view('admin.security.login-attempts', compact('attempts'));
    }

    /**
     * Clear old security logs
     */
    public function clearOldLogs(Request $request)
    {
        $request->validate([
            'days' => 'required|integer|min:1|max:365',
        ]);

        $daysToKeep = $request->days;
        $cutoffDate = now()->subDays($daysToKeep);

        $deletedSecurityLogs = SecurityLog::where('created_at', '<', $cutoffDate)->delete();
        $deletedLoginAttempts = LoginAttempt::where('attempted_at', '<', $cutoffDate)->delete();

        // Log this action
        $this->securityService->logEvent(
            'logs_cleared',
            "Log keamanan lama dibersihkan (lebih dari {$daysToKeep} hari)",
            'info',
            [
                'days_to_keep' => $daysToKeep,
                'security_logs_deleted' => $deletedSecurityLogs,
                'login_attempts_deleted' => $deletedLoginAttempts,
            ]
        );

        return redirect()->back()->with('success', "Berhasil menghapus {$deletedSecurityLogs} log keamanan dan {$deletedLoginAttempts} log login yang lebih dari {$daysToKeep} hari.");
    }
}
