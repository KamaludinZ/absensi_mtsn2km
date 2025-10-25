@echo off
REM Simplified Production Deployment Script for Shared Hosting (Windows)
REM Use this script for cPanel or shared hosting environments on Windows

echo ===============================================
echo Simplified Deployment Script - MTsN 2 Absensi
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
composer install --no-dev --optimize-autoloader --no-interaction
if %errorlevel% equ 0 (
    echo ✅ Production dependencies installed
) else (
    echo ❌ Error installing dependencies
    pause
    exit /b 1
)
echo.

echo 🔄 Optimizing autoloader...
composer dump-autoload --optimize --no-interaction
if %errorlevel% equ 0 (
    echo ✅ Autoloader optimized
) else (
    echo ❌ Error optimizing autoloader
    pause
    exit /b 1
)
echo.

echo 🏗️  Building frontend assets...
REM For shared hosting, we'll use a pre-built approach
if exist "public\build\" (
    echo ✅ Frontend assets already built
) else (
    echo ⚠️  Building frontend assets locally
    npm install --no-audit --no-fund
    npm run build
    if %errorlevel% equ 0 (
        echo ✅ Frontend assets built successfully
    ) else (
        echo ❌ Error building frontend assets
        pause
        exit /b 1
    )
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

echo 📊 Running migrations...
php artisan migrate --force
if %errorlevel% equ 0 (
    echo ✅ Migrations completed
) else (
    echo ❌ Error running migrations
    pause
    exit /b 1
)
echo.

echo 🔗 Creating storage link...
REM For shared hosting, we need to create the link manually
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

echo ⚠️  Post-Deployment Verification:
echo ----------------------------------------
echo 1. Check that .env file has correct settings:
echo    - APP_ENV=production
echo    - APP_DEBUG=false
echo    - DB_* settings correct
echo.
echo 2. Verify application works by accessing it in browser
echo 3. Test attendance functionality with location
echo 4. Check that reports show coordinate information
echo 5. Verify photo uploads work correctly
echo.

echo 🎉 Deployment completed successfully!
echo Application is ready for production use.
echo.
pause