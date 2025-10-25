<?php
/**
 * Setup Production Storage Script
 *
 * Script ini akan membuat struktur folder untuk penyimpanan selfie di production
 * Jalankan sekali saja setelah deploy ke server production
 *
 * Cara menjalankan:
 * php setup-production-storage.php
 */

echo "========================================\n";
echo "Setup Production Storage Structure\n";
echo "========================================\n\n";

// Get base path (folder tempat script ini berada)
$basePath = __DIR__;

// Define folder structure yang akan dibuat
$folders = [
    'storage/selfies',
    'storage/selfies/employee',
    'storage/selfies/student',
];

echo "Base Path: $basePath\n\n";

// Create folders
foreach ($folders as $folder) {
    $fullPath = $basePath . '/' . $folder;

    if (!file_exists($fullPath)) {
        if (mkdir($fullPath, 0755, true)) {
            echo "✓ Created: $fullPath\n";
        } else {
            echo "✗ Failed to create: $fullPath\n";
            echo "  Please create this folder manually and set permission to 755\n";
        }
    } else {
        echo "→ Already exists: $fullPath\n";
    }

    // Verify permissions
    $perms = substr(sprintf('%o', fileperms($fullPath)), -4);
    echo "  Permissions: $perms\n";

    // Check if writable
    if (is_writable($fullPath)) {
        echo "  Status: ✓ Writable\n";
    } else {
        echo "  Status: ✗ NOT Writable - Please fix permissions!\n";
        echo "  Run: chmod 755 $fullPath\n";
    }

    echo "\n";
}

// Create .htaccess in storage folder for security
$htaccessPath = $basePath . '/storage/.htaccess';
$htaccessContent = '# Deny all direct access to storage folder
# Files should only be accessed via application controller (SelfieController)

<IfModule mod_authz_core.c>
    Require all denied
</IfModule>

<IfModule !mod_authz_core.c>
    Order deny,allow
    Deny from all
</IfModule>

# Additional security headers
<IfModule mod_headers.c>
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "DENY"
    Header set X-XSS-Protection "1; mode=block"
</IfModule>
';

if (!file_exists($htaccessPath)) {
    if (file_put_contents($htaccessPath, $htaccessContent)) {
        echo "✓ Created: $htaccessPath\n";
    } else {
        echo "✗ Failed to create .htaccess file\n";
    }
} else {
    echo "→ Already exists: $htaccessPath\n";
}

echo "\n========================================\n";
echo "Setup Complete!\n";
echo "========================================\n\n";

// Display summary
echo "Summary:\n";
echo "--------\n";
echo "Selfie storage path: " . $basePath . "/storage/selfies/\n";
echo "Employee photos: " . $basePath . "/storage/selfies/employee/\n";
echo "Student photos: " . $basePath . "/storage/selfies/student/\n";
echo "\n";

echo "Next Steps:\n";
echo "-----------\n";
echo "1. Verify all folders are writable (chmod 755)\n";
echo "2. Set APP_ENV=production in .env file\n";
echo "3. Test upload foto absensi\n";
echo "4. Delete this script after setup complete\n";
echo "\n";

// Test write permission with a test file
echo "Testing write permissions...\n";
$testFile = $basePath . '/storage/selfies/employee/.test';
if (file_put_contents($testFile, 'test')) {
    echo "✓ Write test successful!\n";
    @unlink($testFile);
} else {
    echo "✗ Write test failed! Please check folder permissions.\n";
}

echo "\n";
echo "========================================\n";
