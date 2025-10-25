<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel application
$app = require_once 'bootstrap/app.php';

// Bind public path for testing
$app->bind('path.public', function() {
    return __DIR__ . '/public';
});

echo "Testing public path configuration:\n";
echo "public_path(): " . public_path() . "\n";
echo "storage_path(): " . storage_path() . "\n";
echo "public_path('storage'): " . public_path('storage') . "\n";

// Test filesystem configuration
$config = config('filesystems.disks.public');
echo "\nFilesystem public disk configuration:\n";
print_r($config);

// Test if we can write to the public storage directory
$testFile = public_path('storage/test.txt');
file_put_contents($testFile, 'Test file for public storage');
echo "\nTest file created at: " . $testFile . "\n";

// Check if file exists
if (file_exists($testFile)) {
    echo "Test file exists and is readable\n";
    unlink($testFile); // Clean up
    echo "Test file deleted\n";
} else {
    echo "Test file does not exist\n";
}