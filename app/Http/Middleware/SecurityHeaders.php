<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request and add security headers.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Add comprehensive security headers
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(self), microphone=(), camera=(self)');
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');
        
        // Define base CSP policies
        $basePolicies = [
            "default-src" => "'self'",
            "script-src" => "'self' 'unsafe-inline' 'unsafe-eval' https://www.google.com/maps/ https://unpkg.com https://static.cloudflareinsights.com https://cdn.jsdelivr.net",
            "style-src" => "'self' 'unsafe-inline' https://unpkg.com https://cdn.jsdelivr.net",
            "img-src" => "'self' data: https:",
            "font-src" => "'self'",
            "connect-src" => "'self' https://unpkg.com https://*.tile.openstreetmap.org",
            "frame-src" => "https://www.google.com/maps/",
            "object-src" => "'none'",
            "base-uri" => "'self'",
            "form-action" => "'self'",
        ];

        // Allow Vite dev server in local environment
        if (app()->isLocal()) {
            $viteUrl = 'http://localhost:5173';
            $basePolicies['script-src'] .= " " . $viteUrl;
            $basePolicies['style-src'] .= " " . $viteUrl;
            $basePolicies['connect-src'] .= " " . $viteUrl . " ws://localhost:5173";
        }

        // Build the CSP string
        $cspString = '';
        foreach ($basePolicies as $directive => $value) {
            $cspString .= "{$directive} {$value}; ";
        }

        $response->headers->set('Content-Security-Policy', $cspString);
        
        // Add Strict Transport Security for HTTPS
        if (config('app.secure', false) || $request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }
        
        // Remove server information
        $response->headers->remove('X-Powered-By');
        $response->headers->remove('Server');

        return $response;
    }
}
