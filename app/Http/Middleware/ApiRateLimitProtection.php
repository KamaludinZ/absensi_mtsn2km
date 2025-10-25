<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class ApiRateLimitProtection
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Define rate limits based on sensitivity
        $sensitiveEndpoints = [
            'api/location-settings',
            'api/schedules/current-status',
            'admin/location-settings',
            'employee/attendance/clock-in',
            'employee/attendance/clock-out',
            'student/attendance/clock-in',
            'student/attendance/clock-out',
        ];

        $endpoint = $request->path();
        $isSensitive = false;
        
        foreach ($sensitiveEndpoints as $pattern) {
            if (str_contains($endpoint, $pattern)) {
                $isSensitive = true;
                break;
            }
        }

        // Apply stricter rate limiting for sensitive endpoints
        if ($isSensitive) {
            $maxAttempts = 10; // Much stricter for sensitive endpoints
            $decayMinutes = 1; // 10 requests per minute for sensitive endpoints
        } else {
            $maxAttempts = 60; // Standard rate limiting for other endpoints
            $decayMinutes = 1;
        }

        $key = 'api_rate_limit:' . $request->ip() . ':' . md5($endpoint);
        
        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            return response()->json([
                'error' => 'Too Many Requests',
                'message' => 'Rate limit exceeded. Please try again later.'
            ], 429);
        }

        RateLimiter::hit($key, $decayMinutes * 60);

        $response = $next($request);

        // Add rate limit headers to response
        $remaining = RateLimiter::retriesLeft($key, $maxAttempts);
        $response->headers->set('X-RateLimit-Limit', $maxAttempts);
        $response->headers->set('X-RateLimit-Remaining', $remaining);

        return $response;
    }
}