@echo off
REM Script untuk membuat update package di Windows
REM Untuk dijalankan di root project Laravel

echo ========================================
echo  SIAB Update Package Creator
echo ========================================
echo.

REM Read version from VERSION file
set /p VERSION=<VERSION
echo Current Version: %VERSION%
echo.

REM Set filename
set FILENAME=update-%VERSION%.zip

REM Check if file already exists
if exist "%FILENAME%" (
    echo File %FILENAME% sudah ada!
    set /p OVERWRITE=Overwrite? (Y/N):
    if /i not "%OVERWRITE%"=="Y" (
        echo Dibatalkan.
        pause
        exit /b
    )
    del "%FILENAME%"
)

echo.
echo Membuat package...
echo.

REM Create temp directory
set TEMP_DIR=temp-update-package
if exist "%TEMP_DIR%" rmdir /s /q "%TEMP_DIR%"
mkdir "%TEMP_DIR%"

REM Copy required folders
echo [1/7] Copying app folder...
xcopy /E /I /Q app "%TEMP_DIR%\app" > nul

echo [2/7] Copying bootstrap folder...
xcopy /E /I /Q bootstrap "%TEMP_DIR%\bootstrap" > nul

echo [3/7] Copying config folder...
xcopy /E /I /Q config "%TEMP_DIR%\config" > nul

echo [4/7] Copying database folder...
xcopy /E /I /Q database "%TEMP_DIR%\database" > nul

echo [5/7] Copying public folder...
xcopy /E /I /Q public "%TEMP_DIR%\public" > nul

echo [6/7] Copying resources folder...
xcopy /E /I /Q resources "%TEMP_DIR%\resources" > nul

echo [7/7] Copying routes folder...
xcopy /E /I /Q routes "%TEMP_DIR%\routes" > nul

REM Copy required files
echo.
echo Copying files...
copy /Y artisan "%TEMP_DIR%\" > nul
copy /Y composer.json "%TEMP_DIR%\" > nul
copy /Y composer.lock "%TEMP_DIR%\" > nul
copy /Y VERSION "%TEMP_DIR%\" > nul

REM Create ZIP using PowerShell
echo.
echo Creating ZIP archive...
powershell -Command "Compress-Archive -Path '%TEMP_DIR%\*' -DestinationPath '%FILENAME%' -Force"

REM Cleanup temp directory
echo Cleaning up...
rmdir /s /q "%TEMP_DIR%"

REM Show result
echo.
echo ========================================
if exist "%FILENAME%" (
    echo ^✅ SUCCESS!
    echo Package created: %FILENAME%

    REM Get file size
    for %%I in ("%FILENAME%") do set SIZE=%%~zI
    set /a SIZE_MB=%SIZE% / 1048576
    echo Size: %SIZE_MB% MB

    REM Check if size exceeds 100MB
    if %SIZE_MB% GTR 100 (
        echo.
        echo ⚠️  WARNING: File size exceeds 100MB limit!
        echo Please optimize the package or remove unnecessary files.
    )
) else (
    echo ❌ FAILED!
    echo Package creation failed. Please check errors above.
)
echo ========================================
echo.

pause
