<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Daily database backup (shown in Admin > Backup). Runs inside the Docker
// container via `php artisan schedule:work` (see docker/supervisord.conf).
if (config('app.auto_backup')) {
    Schedule::command('app:backup-database')
        ->dailyAt(config('app.auto_backup_time', '01:00'))
        ->withoutOverlapping(120)
        ->onOneServer();
}
