<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Login rate limit: 5 attempts per minute per username+IP, plus an
        // overall cap per IP. Many teachers share one school IP, so a pure
        // per-IP limit of 5/minute locks people out during the morning rush.
        RateLimiter::for('login', function (Request $request) {
            $username = Str::lower((string) $request->input('username'));

            return [
                Limit::perMinute(5)->by($username . '|' . $request->ip()),
                Limit::perMinute(60)->by('login-ip|' . $request->ip()),
            ];
        });

        // Student attendance page (no login): generous per-IP limit because
        // students on the school WiFi share one IP (this also bounds NISN
        // guessing), plus a per-NISN/card limit against repeated submissions
        // for the same student.
        RateLimiter::for('student-kiosk', function (Request $request) {
            $limits = [Limit::perMinute(300)->by('kiosk-ip|' . $request->ip())];

            $identifier = $request->input('identifier');
            if (is_string($identifier) && $identifier !== '') {
                $limits[] = Limit::perMinute(10)->by('kiosk-id|' . sha1(strtoupper(trim($identifier))));
            }

            return $limits;
        });
    }
}
