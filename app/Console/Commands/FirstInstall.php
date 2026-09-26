<?php

namespace App\Console\Commands;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

/**
 * Fill an empty database with the starting data (roles, admin account,
 * classes, schedules) on the very first deploy. Does nothing once any user
 * exists, so it is safe to run on every container start.
 */
class FirstInstall extends Command
{
    protected $signature = 'app:first-install {--admin-password= : Password for the "admin" account (default: INITIAL_ADMIN_PASSWORD)}';
    protected $description = 'Seed an empty database with the starting data and an admin account';

    public function handle(): int
    {
        if (User::exists()) {
            $this->line('Database already has users, first install skipped.');
            return self::SUCCESS;
        }

        $password = (string) ($this->option('admin-password') ?: env('INITIAL_ADMIN_PASSWORD', ''));

        if (strlen($password) < 8) {
            $this->error('The database is empty. Set INITIAL_ADMIN_PASSWORD (at least 8 characters) to create the admin account.');
            return self::FAILURE;
        }

        $this->call('db:seed', ['--class' => DatabaseSeeder::class, '--force' => true]);

        User::where('username', 'admin')->update(['password' => Hash::make($password)]);

        $this->info('First install done. Log in with username "admin" and the INITIAL_ADMIN_PASSWORD password.');

        return self::SUCCESS;
    }
}
