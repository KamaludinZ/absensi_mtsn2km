@echo off
REM Create Production Deployment Package Script (Windows)
REM This script creates a zip file with all necessary files for production deployment

echo ===============================================
echo Creating Production Deployment Package
echo MTsN 2 Absensi v1.0.1
echo ===============================================
echo.

set PACKAGE_NAME=mtsn2-absensi-v1.0.1-production.zip
set TEMP_DIR=%TEMP%\mtsn2-absensi-deploy

echo 📦 Creating deployment package: %PACKAGE_NAME%
echo.

REM Check if we're in the right directory
if not exist "composer.json" (
    echo ❌ Error: Please run this script from the project root directory
    pause
    exit /b 1
)

REM Clean up any existing temporary directory
if exist "%TEMP_DIR%" (
    echo ⚠️  Cleaning up existing temporary directory
    rmdir /s /q "%TEMP_DIR%"
)

REM Create temporary directory
mkdir "%TEMP_DIR%"

echo 📋 Copying application files...

REM Copy essential files and directories
xcopy /E /I app "%TEMP_DIR%\app\" >nul
xcopy /E /I bootstrap "%TEMP_DIR%\bootstrap\" >nul
xcopy /E /I config "%TEMP_DIR%\config\" >nul
xcopy /E /I database "%TEMP_DIR%\database\" >nul
xcopy /E /I lang "%TEMP_DIR%\lang\" >nul
xcopy /E /I public "%TEMP_DIR%\public\" >nul
xcopy /E /I resources "%TEMP_DIR%\resources\" >nul
xcopy /E /I routes "%TEMP_DIR%\routes\" >nul
xcopy /E /I storage "%TEMP_DIR%\storage\" >nul
xcopy /E /I vendor "%TEMP_DIR%\vendor\" >nul

REM Copy essential files
copy .env.production "%TEMP_DIR%\.env" >nul
copy composer.json "%TEMP_DIR%\" >nul
copy composer.lock "%TEMP_DIR%\" >nul
copy package.json "%TEMP_DIR%\" >nul
copy package-lock.json "%TEMP_DIR%\" >nul
copy artisan "%TEMP_DIR%\" >nul
copy README.md "%TEMP_DIR%\" >nul
copy VERSION "%TEMP_DIR%\" >nul

REM Copy deployment scripts
copy deploy-production.sh "%TEMP_DIR%\" >nul
copy deploy-production.bat "%TEMP_DIR%\" >nul
copy deploy-shared-hosting.sh "%TEMP_DIR%\" >nul
copy deploy-shared-hosting.bat "%TEMP_DIR%\" >nul
copy PRODUCTION_DEPLOYMENT_CHECKLIST.md "%TEMP_DIR%\" >nul
copy RELEASE_NOTES_v1.0.1.md "%TEMP_DIR%\" >nul

echo ✅ Application files copied

REM Remove unnecessary files from the package
echo 🧹 Cleaning up unnecessary files...
cd /d "%TEMP_DIR%"

REM Remove development files
rmdir /s /q .git 2>nul
rmdir /s /q .github 2>nul
rmdir /s /q tests 2>nul
rmdir /s /q .claude 2>nul
del /q .env.example 2>nul
del /q .env.testing 2>nul
del /q phpunit.xml 2>nul
del /q vite.config.js 2>nul
del /q tailwind.config.js 2>nul
del /q test_*.php 2>nul
del /q *_test.php 2>nul
del /q create-*.php 2>nul
del /q *_FIX.md 2>nul
del /q *_DEBUG.md 2>nul
del /q *_TEST.md 2>nul
del /q *_GUIDE.md 2>nul
del /q *_IMPROVEMENTS.md 2>nul
del /q *_IMPLEMENTATION.md 2>nul
del /q *_UPDATES.md 2>nul
del /q *_DOCUMENTATION.md 2>nul
del /q *_SUMMARY.md 2>nul
del /q CHECKLIST_*.md 2>nul
del /q HOW_TO_*.md 2>nul
del /q QUICK_FIX_*.md 2>nul
del /q SOLUSI_*.md 2>nul
del /q VERIFY_*.php 2>nul
del /q SETUP_*.php 2>nul
del /q TEMP_*.php 2>nul
del /q DEBUG_*.php 2>nul
del /q FIX_*.php 2>nul
del /q TEST_*.php 2>nul
del /q UPDATE_*.php 2>nul
del /q deploy-helper.php 2>nul
del /q DEPLOY_COMMANDS.sh 2>nul

REM Remove IDE files
del /s /q *.DS_Store 2>nul
del /s /q Thumbs.db 2>nul
rmdir /s /q .idea 2>nul
rmdir /s /q .vscode 2>nul

REM Remove log files
del /s /q *.log 2>nul
rmdir /s /q storage\logs 2>nul
mkdir storage\logs

REM Remove cache files
rmdir /s /q storage\framework\cache 2>nul
rmdir /s /q storage\framework\sessions 2>nul
rmdir /s /q storage\framework\views 2>nul
rmdir /s /q storage\app\public\selfies 2>nul
rmdir /s /q bootstrap\cache 2>nul
mkdir storage\framework\cache
mkdir storage\framework\sessions
mkdir storage\framework\views
mkdir storage\app\public\selfies
mkdir bootstrap\cache

echo ✅ Unnecessary files removed

REM Set proper permissions
echo 🔐 Setting permissions...
icacls . /grant Users:(OI)(CI)F /T >nul
icacls storage /grant Users:(OI)(CI)F /T >nul
icacls bootstrap\cache /grant Users:(OI)(CI)F /T >nul

echo ✅ Permissions set

REM Create the zip package using PowerShell
echo 📦 Creating zip package...
cd /d "%TEMP%"
powershell -Command "Compress-Archive -Path 'mtsn2-absensi-deploy\*' -DestinationPath '%CD%\%PACKAGE_NAME%' -Force"

if %errorlevel% equ 0 (
    echo ✅ Deployment package created: %PACKAGE_NAME%
    for %%A in ("%PACKAGE_NAME%") do echo 📦 Package size: %%~zA bytes
) else (
    echo ❌ Error creating deployment package
    pause
    exit /b 1
)

REM Clean up temporary directory
cd /d "%CD%"
rmdir /s /q "%TEMP_DIR%"

echo.
echo 🎉 Deployment package creation completed!
echo Package: %PACKAGE_NAME%
echo Ready for production deployment without symlinks and Vite.
echo.
pause