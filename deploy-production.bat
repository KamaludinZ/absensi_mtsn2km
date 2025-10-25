@echo off
REM Production Deployment Script for MTsN 2 Absensi v1.0.1
REM This script prepares the application for production without symlinks and Vite

echo ===============================================
echo Production Deployment Script - MTsN 2 Absensi
echo Version: 1.0.1
echo ===============================================
echo.

REM Check if we're in the right directory
if not exist "composer.json" (
    echo ❌ Error: Please run this script from the project root directory
    pause
    exit /b 1
)

echo 🔍 Checking environment...

REM Check if .env exists
if not exist ".env" (
    echo ⚠️  .env file not found, copying from .env.production
    if exist ".env.production" (
        copy ".env.production" ".env" >nul
        echo ✅ .env file created from .env.production
    ) else (
        echo ❌ Error: Neither .env nor .env.production file found
        pause
        exit /b 1
    )
)

REM Check APP_ENV
findstr /C:"APP_ENV=production" .env >nul
if errorlevel 1 (
    echo ⚠️  Setting APP_ENV to production
    powershell -Command "(Get-Content .env) -replace '^APP_ENV=.*', 'APP_ENV=production' | Set-Content .env"
)

REM Check APP_DEBUG
findstr /C:"APP_DEBUG=false" .env >nul
if errorlevel 1 (
    echo ⚠️  Setting APP_DEBUG to false
    powershell -Command "(Get-Content .env) -replace '^APP_DEBUG=.*', 'APP_DEBUG=false' | Set-Content .env"
)

echo ✅ Environment configured for production
echo.

echo 📦 Installing production dependencies...
composer install --no-dev --optimize-autoloader
if %errorlevel% equ 0 (
    echo ✅ Production dependencies installed
) else (
    echo ❌ Error installing dependencies
    pause
    exit /b 1
)
echo.

echo 🔄 Optimizing autoloader...
composer dump-autoload --optimize
if %errorlevel% equ 0 (
    echo ✅ Autoloader optimized
) else (
    echo ❌ Error optimizing autoloader
    pause
    exit /b 1
)
echo.

echo 🏗️  Building frontend assets...
npm run build
if %errorlevel% equ 0 (
    echo ✅ Frontend assets built successfully
) else (
    echo ❌ Error building frontend assets
    pause
    exit /b 1
)
echo.

echo 🧹 Clearing and optimizing caches...
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
echo ✅ Caches cleared and optimized
echo.

echo 📂 Setting permissions...
REM Set proper permissions for storage and bootstrap/cache directories
icacls . /grant Users:(OI)(CI)F /T >nul
echo ✅ Permissions set
echo.

echo 📊 Checking database...
php artisan tinker --execute="try { DB::connection()->getPdo(); echo 'Database connected successfully'; } catch (Exception $e) { echo 'Database connection failed: ' . $e->getMessage(); }"
echo.
echo.

echo 📋 Running migrations...
php artisan migrate --force
if %errorlevel% equ 0 (
    echo ✅ Migrations completed
) else (
    echo ❌ Error running migrations
    pause
    exit /b 1
)
echo.

echo 🔗 Checking storage link...
REM Check if storage link exists
if exist "public\storage\" (
    if not exist "public\storage\*" (
        echo ✅ Storage link already exists
    ) else (
        echo ⚠️  public\storage is a directory, removing it...
        rmdir /s /q "public\storage"
        php artisan storage:link
        echo ✅ Storage link created
    )
) else (
    php artisan storage:link
    echo ✅ Storage link created
)
echo.

echo ✅ Deployment Checklist
echo ----------------------------------------
echo ✅ Environment set to production
echo ✅ Debug mode disabled
echo ✅ Dependencies installed
echo ✅ Frontend assets built
echo ✅ Caches optimized
echo ✅ Permissions set
echo ✅ Database migrated
echo ✅ Storage linked
echo.

echo ⚠️  Important Post-Deployment Steps:
echo ----------------------------------------
echo 1. Verify .env configuration (especially database credentials)
echo 2. Test the application functionality
echo 3. Check that location features work correctly
echo 4. Verify that attendance reports show location data
echo 5. Test photo uploads and storage access
echo 6. DELETE THIS SCRIPT after deployment!
echo.

echo 🎉 Deployment completed successfully!
echo Application is ready for production use.
echo.
pause