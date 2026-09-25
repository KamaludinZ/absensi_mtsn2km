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
    }
}
