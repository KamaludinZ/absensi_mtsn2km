<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
            'admin.or.monitoring' => \App\Http\Middleware\CheckAdminOrMonitoringRole::class,
        ]);

        // Add security headers to all requests
        $middleware->web(append: [
            \App\Http\Middleware\SecurityHeaders::class,
            \App\Http\Middleware\EnhancedSecurity::class,
        ]);

        // Configure rate limiting
        $middleware->throttleApi();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
