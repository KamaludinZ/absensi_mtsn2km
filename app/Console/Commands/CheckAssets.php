<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CheckAssets extends Command
{
    protected $signature = 'assets:check';
    protected $description = 'Check asset configuration and availability';

    public function handle()
    {
        $this->info('=== Asset Configuration Check ===');
        $this->newLine();

        // 1. Check environment
        $this->info('1. Environment Check:');
        $env = config('app.env');
        $this->line("   APP_ENV: {$env}");

        if ($env === 'local') {
            $this->warn('   ⚠️  Environment is LOCAL - Vite dev server will be used');
            $this->warn('   ⚠️  Change APP_ENV to "production" in .env file');
        } else {
            $this->info('   ✓ Environment is set to production');
        }
        $this->newLine();

        // 2. Check manifest file
        $this->info('2. Manifest File Check:');
        $manifestPath = public_path('build/manifest.json');

        if (file_exists($manifestPath)) {
            $this->info("   ✓ Manifest found at: {$manifestPath}");

            try {
                $manifest = json_decode(file_get_contents($manifestPath), true);

                if (json_last_error() === JSON_ERROR_NONE) {
                    $this->info('   ✓ Manifest is valid JSON');

                    // Check CSS entry
                    if (isset($manifest['resources/css/app.css'])) {
                        $cssFile = $manifest['resources/css/app.css']['file'];
                        $this->info("   ✓ CSS entry found: {$cssFile}");

                        // Check if CSS file exists
                        $cssPath = public_path('build/' . $cssFile);
                        if (file_exists($cssPath)) {
                            $size = number_format(filesize($cssPath) / 1024, 2);
                            $this->info("   ✓ CSS file exists ({$size} KB)");
                        } else {
                            $this->error("   ✗ CSS file NOT found: {$cssPath}");
                        }
                    } else {
                        $this->error('   ✗ CSS entry not found in manifest');
                    }

                    // Check JS entry
                    if (isset($manifest['resources/js/app.js'])) {
                        $jsFile = $manifest['resources/js/app.js']['file'];
                        $this->info("   ✓ JS entry found: {$jsFile}");

                        // Check if JS file exists
                        $jsPath = public_path('build/' . $jsFile);
                        if (file_exists($jsPath)) {
                            $size = number_format(filesize($jsPath) / 1024, 2);
                            $this->info("   ✓ JS file exists ({$size} KB)");
                        } else {
                            $this->error("   ✗ JS file NOT found: {$jsPath}");
                        }
                    } else {
                        $this->error('   ✗ JS entry not found in manifest');
                    }

                } else {
                    $this->error('   ✗ Manifest is not valid JSON');
                }
            } catch (\Exception $e) {
                $this->error("   ✗ Error reading manifest: {$e->getMessage()}");
            }
        } else {
            $this->error("   ✗ Manifest NOT found at: {$manifestPath}");
            $this->warn('   ⚠️  Run "npm run build" locally and upload build folder');
        }
        $this->newLine();

        // 3. Check build directory
        $this->info('3. Build Directory Check:');
        $buildPath = public_path('build');

        if (is_dir($buildPath)) {
            $this->info("   ✓ Build directory exists: {$buildPath}");

            // List contents
            $files = scandir($buildPath);
            $this->line('   Contents:');
            foreach ($files as $file) {
                if ($file !== '.' && $file !== '..') {
                    $this->line("   - {$file}");
                }
            }

            // Check assets subdirectory
            $assetsPath = $buildPath . '/assets';
            if (is_dir($assetsPath)) {
                $assetFiles = scandir($assetsPath);
                $cssCount = count(array_filter($assetFiles, fn($f) => str_ends_with($f, '.css')));
                $jsCount = count(array_filter($assetFiles, fn($f) => str_ends_with($f, '.js')));

                $this->info("   ✓ Assets directory found ({$cssCount} CSS, {$jsCount} JS files)");
            } else {
                $this->error('   ✗ Assets subdirectory not found');
            }
        } else {
            $this->error("   ✗ Build directory NOT found: {$buildPath}");
            $this->warn('   ⚠️  Upload the entire "public/build" folder from local');
        }
        $this->newLine();

        // 4. Check .env configuration
        $this->info('4. Configuration Check:');
        $this->line('   APP_DEBUG: ' . (config('app.debug') ? 'true' : 'false'));
        $this->line('   APP_URL: ' . config('app.url'));

        // Check for VITE variables (should not exist in production)
        if (env('VITE_APP_NAME')) {
            $this->warn('   ⚠️  VITE_APP_NAME is set in .env (remove it for production)');
        } else {
            $this->info('   ✓ No VITE variables in .env');
        }
        $this->newLine();

        // 5. Summary and recommendations
        $this->info('=== Summary ===');

        $issues = [];

        if ($env === 'local') {
            $issues[] = 'Change APP_ENV to "production" in .env';
        }

        if (!file_exists($manifestPath)) {
            $issues[] = 'Upload build/manifest.json to server';
        }

        if (!is_dir($buildPath)) {
            $issues[] = 'Upload entire build/ folder to public_html/build/';
        }

        if (empty($issues)) {
            $this->info('✓ All checks passed! Assets should load correctly.');
            $this->newLine();
            $this->info('If you still have issues:');
            $this->line('1. Run: php artisan config:clear');
            $this->line('2. Run: php artisan view:clear');
            $this->line('3. Check browser console for errors');
        } else {
            $this->warn('⚠️  Issues found:');
            foreach ($issues as $issue) {
                $this->line("  - {$issue}");
            }
            $this->newLine();
            $this->info('Fix these issues and run this command again.');
        }

        return 0;
    }
}
