<?php
/**
 * Verify Production Setup Script
 *
 * Script ini memverifikasi bahwa setup production sudah benar
 * Jalankan setelah deploy dan setup untuk memastikan semua berjalan baik
 *
 * Cara menjalankan:
 * php verify-production.php
 */

echo "========================================\n";
echo "Production Setup Verification\n";
echo "========================================\n\n";

$basePath = __DIR__;
$errors = [];
$warnings = [];
$success = [];

// 1. Check .env file
echo "1. Checking .env configuration...\n";
$envPath = $basePath . '/.env';
if (file_exists($envPath)) {
    $envContent = file_get_contents($envPath);

    // Check APP_ENV
    if (preg_match('/APP_ENV=production/i', $envContent)) {
        $success[] = "✓ APP_ENV is set to production";
    } else {
        $warnings[] = "⚠ APP_ENV is not set to production";
    }

    // Check APP_DEBUG
    if (preg_match('/APP_DEBUG=false/i', $envContent)) {
        $success[] = "✓ APP_DEBUG is set to false";
    } else {
        $errors[] = "✗ APP_DEBUG should be false in production!";
    }

    // Check APP_URL
    if (preg_match('/APP_URL=.*siab\.mtsn2kotamalang\.sch\.id/i', $envContent)) {
        $success[] = "✓ APP_URL is configured correctly";
    } else {
        $warnings[] = "⚠ APP_URL might not be configured correctly";
    }
} else {
    $errors[] = "✗ .env file not found!";
}
echo "\n";

// 2. Check folder structure
echo "2. Checking folder structure...\n";
$requiredFolders = [
    'storage/selfies',
    'storage/selfies/employee',
    'storage/selfies/student',
    'storage/framework/cache',
    'storage/framework/sessions',
    'storage/framework/views',
    'storage/logs',
    'bootstrap/cache',
];

foreach ($requiredFolders as $folder) {
    $fullPath = $basePath . '/' . $folder;
    if (file_exists($fullPath)) {
        if (is_writable($fullPath)) {
            $success[] = "✓ $folder exists and is writable";
        } else {
            $errors[] = "✗ $folder exists but is NOT writable!";
        }
    } else {
        $errors[] = "✗ $folder does NOT exist!";
    }
}
echo "\n";

// 3. Check .htaccess in storage
echo "3. Checking security files...\n";
$htaccessPath = $basePath . '/storage/.htaccess';
if (file_exists($htaccessPath)) {
    $success[] = "✓ storage/.htaccess exists (access protection)";
} else {
    $warnings[] = "⚠ storage/.htaccess not found - storage folder might be accessible!";
}

// Check public/.htaccess
$publicHtaccess = $basePath . '/public/.htaccess';
if (file_exists($publicHtaccess)) {
    $success[] = "✓ public/.htaccess exists";
} else {
    $warnings[] = "⚠ public/.htaccess not found";
}
echo "\n";

// 4. Check key files exist
echo "4. Checking important files...\n";
$requiredFiles = [
    'app/Http/Controllers/SelfieController.php',
    'app/Http/Controllers/Employee/AttendanceController.php',
    'app/Http/Controllers/Student/AttendanceController.php',
];

foreach ($requiredFiles as $file) {
    if (file_exists($basePath . '/' . $file)) {
        $success[] = "✓ $file exists";
    } else {
        $errors[] = "✗ $file NOT found!";
    }
}
echo "\n";

// 5. Check permissions
echo "5. Checking critical permissions...\n";
$criticalFolders = [
    'storage',
    'bootstrap/cache',
];

foreach ($criticalFolders as $folder) {
    $fullPath = $basePath . '/' . $folder;
    $perms = substr(sprintf('%o', fileperms($fullPath)), -4);

    if ($perms >= '0755') {
        $success[] = "✓ $folder has correct permissions ($perms)";
    } else {
        $errors[] = "✗ $folder has insufficient permissions ($perms) - should be 755";
    }
}
echo "\n";

// 6. Test write to selfies folder
echo "6. Testing file upload capability...\n";
$testFile = $basePath . '/storage/selfies/employee/.test_' . time() . '.txt';
if (@file_put_contents($testFile, 'test')) {
    $success[] = "✓ Can write to storage/selfies/employee/";
    @unlink($testFile);
} else {
    $errors[] = "✗ Cannot write to storage/selfies/employee/ - check permissions!";
}

$testFile2 = $basePath . '/storage/selfies/student/.test_' . time() . '.txt';
if (@file_put_contents($testFile2, 'test')) {
    $success[] = "✓ Can write to storage/selfies/student/";
    @unlink($testFile2);
} else {
    $errors[] = "✗ Cannot write to storage/selfies/student/ - check permissions!";
}
echo "\n";

// Display results
echo "========================================\n";
echo "VERIFICATION RESULTS\n";
echo "========================================\n\n";

if (!empty($success)) {
    echo "SUCCESS (" . count($success) . "):\n";
    foreach ($success as $msg) {
        echo "  $msg\n";
    }
    echo "\n";
}

if (!empty($warnings)) {
    echo "WARNINGS (" . count($warnings) . "):\n";
    foreach ($warnings as $msg) {
        echo "  $msg\n";
    }
    echo "\n";
}

if (!empty($errors)) {
    echo "ERRORS (" . count($errors) . "):\n";
    foreach ($errors as $msg) {
        echo "  $msg\n";
    }
    echo "\n";
}

// Final verdict
echo "========================================\n";
if (empty($errors)) {
    echo "STATUS: ✓ READY FOR PRODUCTION\n";
    echo "========================================\n";
    echo "\nYour application is ready to use!\n";
    echo "You can now test foto upload via the application.\n\n";
} else {
    echo "STATUS: ✗ NOT READY - PLEASE FIX ERRORS\n";
    echo "========================================\n";
    echo "\nPlease fix the errors above before using in production.\n\n";
}

// Show paths
echo "Important Paths:\n";
echo "----------------\n";
echo "Base Path: $basePath\n";
echo "Selfie Storage: $basePath/storage/selfies/\n";
echo "  - Employee: $basePath/storage/selfies/employee/\n";
echo "  - Student: $basePath/storage/selfies/student/\n";
echo "\n";

echo "Routes:\n";
echo "-------\n";
echo "Selfie URL Pattern: /selfies/{type}/{filename}\n";
echo "Example: /selfies/employee/employee_123_in_1234567890.jpg\n";
echo "\n";
