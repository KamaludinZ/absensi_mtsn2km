<?php
/**
 * Test Geofence Validation Logic
 * 
 * This script tests the improved geofence validation logic
 */

require_once 'vendor/autoload.php';

// Bootstrap Laravel application
$app = require_once 'bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\Employee\AttendanceController;
use App\Models\LocationSetting;

echo "=====================================\n";
echo "Testing Geofence Validation Logic\n";
echo "=====================================\n\n";

// Set up test geofence settings
echo "🔧 Setting up test geofence settings...\n";

// Create or update geofence settings
LocationSetting::updateOrCreate(
    ['setting_key' => 'geofence_center_lat'],
    [
        'setting_value' => '-7.99478',
        'description' => 'Latitude coordinate of geofence center'
    ]
);

LocationSetting::updateOrCreate(
    ['setting_key' => 'geofence_center_lng'],
    [
        'setting_value' => '112.67383',
        'description' => 'Longitude coordinate of geofence center'
    ]
);

LocationSetting::updateOrCreate(
    ['setting_key' => 'geofence_radius'],
    [
        'setting_value' => '100',
        'description' => 'Geofence radius in meters'
    ]
);

echo "✅ Test geofence settings created\n\n";

// Test the validation logic
$controller = app()->make(AttendanceController::class);

echo "🧪 Testing geofence validation...\n\n";

// Test case 1: Within strict radius
echo "Test 1: Within strict radius\n";
$result1 = $controller->validateGeofence(-7.99478, 112.67384, 10); // Very close
echo "Result: " . ($result1 ? "✅ ACCEPTED" : "❌ REJECTED") . "\n\n";

// Test case 2: Slightly outside strict radius with low accuracy
echo "Test 2: Slightly outside strict radius with low accuracy\n";
$result2 = $controller->validateGeofence(-7.99578, 112.67383, 10); // ~111m away, low accuracy
echo "Result: " . ($result2 ? "✅ ACCEPTED" : "❌ REJECTED") . "\n\n";

// Test case 3: Slightly outside strict radius with high accuracy
echo "Test 3: Slightly outside strict radius with high accuracy\n";
$result3 = $controller->validateGeofence(-7.99578, 112.67383, 50); // ~111m away, higher accuracy
echo "Result: " . ($result3 ? "✅ ACCEPTED" : "❌ REJECTED") . "\n\n";

// Test case 4: Significantly outside radius
echo "Test 4: Significantly outside radius\n";
$result4 = $controller->validateGeofence(-7.99678, 112.67383, 10); // ~222m away
echo "Result: " . ($result4 ? "✅ ACCEPTED" : "❌ REJECTED") . "\n\n";

echo "🎉 Geofence validation tests completed!\n";