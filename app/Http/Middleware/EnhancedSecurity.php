<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnhancedSecurity
{
    /**
     * Handle an incoming request and add enhanced security measures.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Block common attack vectors
        if ($this->detectSuspiciousActivity($request)) {
            abort(403, 'Access Denied - Suspicious Activity Detected');
        }

        // Protect against Clickjacking
        $response->headers->set('X-Frame-Options', 'DENY');

        // Additional security headers
        $response->headers->set('X-Download-Options', 'noopen');
        $response->headers->set('X-DNS-Prefetch-Control', 'off');
        $response->headers->set('X-Robots-Tag', 'none');

        return $response;
    }

    /**
     * Detect suspicious activity that might indicate an attack
     */
    private function detectSuspiciousActivity(Request $request): bool
    {
        // Use SecurityService to check if IP is blocked
        if (\App\Services\SecurityService::isIpBlocked()) {
            \App\Services\SecurityService::logSuspiciousActivity('Blocked IP attempted access', [
                'blocked_reason' => 'Previously detected violations'
            ]);
            return true;
        }

        // Check for SQL injection attempts in request parameters
        $suspiciousPatterns = [
            '/union\s+select/i',
            '/insert\s+into/i',
            '/drop\s+table/i',
            '/delete\s+from/i',
            '/\'\s*(or|and)\s*\'/i',
            '/--/',
            '/\/\*/',
            '/benchmark\(.*?,/i',
            '/sleep\(.*?\)/i',
            '/waitfor\s+delay/i',
        ];

        // Check all request inputs
        $inputs = array_merge(
            $request->query(),
            $request->request->all(),
            $request->route()?->parameters() ?? []
        );

        foreach ($inputs as $value) {
            if (is_string($value)) {
                foreach ($suspiciousPatterns as $pattern) {
                    if (preg_match($pattern, $value)) {
                        \App\Services\SecurityService::logSuspiciousActivity('Potential SQL injection attempt detected', [
                            'pattern' => $pattern,
                            'value' => $value,
                            'url' => $request->fullUrl(),
                        ]);
                        return true;
                    }
                }
            }
        }

        // Check for XSS attempts in user input
        $xssPatterns = [
            '/<script/i',
            '/javascript:/i',
            '/on(load|error|click|mouseover|focus|blur)=/i',
            '/<iframe/i',
            '/<object/i',
            '/eval\(/i',
        ];

        foreach ($inputs as $value) {
            if (is_string($value)) {
                foreach ($xssPatterns as $pattern) {
                    if (preg_match($pattern, $value)) {
                        \App\Services\SecurityService::logSuspiciousActivity('Potential XSS attempt detected', [
                            'pattern' => $pattern,
                            'value' => $value,
                            'url' => $request->fullUrl(),
                        ]);
                        return true;
                    }
                }
            }
        }

        // Check for path traversal attempts
        $pathTraversalPatterns = [
            '/\.\.\//',
            '/\.\.\\\/',
            '/%2e%2e%2f/i',
            '/%2e%2e%2f/i',
        ];

        $fullUrl = $request->fullUrl();
        foreach ($pathTraversalPatterns as $pattern) {
            if (preg_match($pattern, $fullUrl)) {
                \App\Services\SecurityService::logSuspiciousActivity('Potential path traversal attempt detected', [
                    'pattern' => $pattern,
                    'url' => $fullUrl,
                ]);
                return true;
            }
        }

        return false;
    }
}