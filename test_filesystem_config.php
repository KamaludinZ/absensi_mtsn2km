<?php

// Simple test script to verify filesystem configuration
require_once 'vendor/autoload.php';

// Bootstrap Laravel application
$app = require_once 'bootstrap/app.php';

// Create a kernel instance
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Config;

// Test the filesystem configuration
echo "=== Filesystem Configuration Test ===\n";

// Check if public disk is configured correctly
$config = Config::get('filesystems.disks.public');
echo "Public disk configuration:\n";
print_r($config);

// Check paths
echo "\nPaths:\n";
echo "public_path(): " . public_path() . "\n";
echo "public_path('storage'): " . public_path('storage') . "\n";
echo "storage_path(): " . storage_path() . "\n";
echo "storage_path('app/public'): " . storage_path('app/public') . "\n";

// Test creating a file in public storage
echo "\nTesting file creation in public storage...\n";
$testContent = "Test file created at " . date('Y-m-d H:i:s');
$testFilePath = 'test_file.txt';

try {
    // Using the public disk (should go to public/storage)
    Storage::disk('public')->put($testFilePath, $testContent);
    echo "✓ File created successfully in public disk\n";
    
    // Check if file exists
    if (Storage::disk('public')->exists($testFilePath)) {
        echo "✓ File exists in public disk\n";
        
        // Get the file content
        $content = Storage::disk('public')->get($testFilePath);
        echo "✓ File content retrieved successfully\n";
        
        // Delete the test file
        Storage::disk('public')->delete($testFilePath);
        echo "✓ Test file deleted\n";
    } else {
        echo "✗ File does not exist in public disk\n";
    }
} catch (Exception $e) {
    echo "✗ Error creating file: " . $e->getMessage() . "\n";
}

echo "\n=== Test Completed ===\n";