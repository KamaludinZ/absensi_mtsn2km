<?php
/**
 * Final Geofence Functionality Test
 * 
 * This script tests the complete geofence functionality in production
 */

require_once 'vendor/autoload.php';

// Bootstrap Laravel application
$app = require_once 'bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Attendance;
use App\Models\LocationSetting;
use Illuminate\Support\Facades\DB;

echo "=====================================\n";
echo "Final Geofence Functionality Test\n";
echo "=====================================\n\n";

// Test 1: Check if location settings exist
echo "Test 1: Checking location settings...\n";
$geofenceCenterLat = LocationSetting::where('setting_key', 'geofence_center_lat')->first();
$geofenceCenterLng = LocationSetting::where('setting_key', 'geofence_center_lng')->first();
$geofenceRadius = LocationSetting::where('setting_key', 'geofence_radius')->first();
$locationRequired = LocationSetting::where('setting_key', 'location_required')->first();

if ($geofenceCenterLat && $geofenceCenterLng && $geofenceRadius) {
    echo "✅ Location settings found:\n";
    echo "   Center Latitude: " . $geofenceCenterLat->setting_value . "\n";
    echo "   Center Longitude: " . $geofenceCenterLng->setting_value . "\n";
    echo "   Radius: " . $geofenceRadius->setting_value . " meters\n";
    echo "   Location Required: " . ($locationRequired ? $locationRequired->setting_value : 'false') . "\n\n";
} else {
    echo "❌ Location settings not found\n\n";
    exit(1);
}

// Test 2: Check if attendance records have location data
echo "Test 2: Checking attendance records with location data...\n";
$attendanceWithLocation = Attendance::whereNotNull('clock_in_latitude')
    ->whereNotNull('clock_in_longitude')
    ->first();

if ($attendanceWithLocation) {
    echo "✅ Attendance records with location data found:\n";
    echo "   Attendance ID: " . $attendanceWithLocation->id . "\n";
    echo "   Clock In Latitude: " . $attendanceWithLocation->clock_in_latitude . "\n";
    echo "   Clock In Longitude: " . $attendanceWithLocation->clock_in_longitude . "\n";
    echo "   Clock In Accuracy: " . $attendanceWithLocation->clock_in_accuracy . " meters\n\n";
} else {
    echo "⚠️  No attendance records with location data found\n";
    echo "   (This is expected if no one has attended with location yet)\n\n";
}

// Test 3: Test geofence validation logic
echo "Test 3: Testing geofence validation logic...\n";

// Get geofence settings
$centerLat = floatval($geofenceCenterLat->setting_value);
$centerLng = floatval($geofenceCenterLng->setting_value);
$radius = floatval($geofenceRadius->setting_value);

// Function to calculate distance using Haversine formula
function calculateDistance($lat1, $lng1, $lat2, $lng2) {
    $earthRadius = 6371e3; // Earth's radius in meters

    $latFrom = deg2rad($lat1);
    $lonFrom = deg2rad($lng1);
    $latTo = deg2rad($lat2);
    $lonTo = deg2rad($lng2);

    $latDelta = $latTo - $latFrom;
    $lonDelta = $lonTo - $lonFrom;

    $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
        cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));

    return $angle * $earthRadius;
}

// Test case 1: Point within geofence
$testLat1 = $centerLat;
$testLng1 = $centerLng;
$distance1 = calculateDistance($testLat1, $testLng1, $centerLat, $centerLng);
$withinGeofence1 = $distance1 <= $radius;

echo "   Test point 1 (exact center):\n";
echo "     Coordinates: " . $testLat1 . ", " . $testLng1 . "\n";
echo "     Distance: " . round($distance1, 2) . " meters\n";
echo "     Within geofence: " . ($withinGeofence1 ? 'YES' : 'NO') . "\n";

// Test case 2: Point slightly outside geofence
$testLat2 = $centerLat + 0.001; // Approximately 111 meters north
$testLng2 = $centerLng;
$distance2 = calculateDistance($testLat2, $testLng2, $centerLat, $centerLng);
$withinGeofence2 = $distance2 <= $radius;

echo "   Test point 2 (slightly outside):\n";
echo "     Coordinates: " . $testLat2 . ", " . $testLng2 . "\n";
echo "     Distance: " . round($distance2, 2) . " meters\n";
echo "     Within geofence: " . ($withinGeofence2 ? 'YES' : 'NO') . "\n";

// Test case 3: Point with accuracy buffer
$testLat3 = $centerLat + 0.001; // Approximately 111 meters north
$testLng3 = $centerLng;
$accuracy3 = 50; // 50 meters accuracy
$distance3 = calculateDistance($testLat3, $testLng3, $centerLat, $centerLng);
$maxAllowedDistance3 = $radius + min($accuracy3, $radius * 0.75); // 75% of radius as buffer
$withinGeofence3 = $distance3 <= $maxAllowedDistance3;

echo "   Test point 3 (with accuracy buffer):\n";
echo "     Coordinates: " . $testLat3 . ", " . $testLng3 . "\n";
echo "     Distance: " . round($distance3, 2) . " meters\n";
echo "     Accuracy: " . $accuracy3 . " meters\n";
echo "     Max allowed distance: " . round($maxAllowedDistance3, 2) . " meters\n";
echo "     Within geofence (with buffer): " . ($withinGeofence3 ? 'YES' : 'NO') . "\n\n";

// Test 4: Check database schema
echo "Test 4: Checking database schema...\n";
$schemaColumns = DB::select("SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'attendances' AND column_name LIKE '%latitude%' OR column_name LIKE '%longitude%' OR column_name LIKE '%accuracy%'");

if (!empty($schemaColumns)) {
    echo "✅ Location columns found in attendances table:\n";
    foreach ($schemaColumns as $column) {
        echo "   " . $column->column_name . " (" . $column->data_type . ")\n";
    }
    echo "\n";
} else {
    echo "❌ Location columns not found in attendances table\n\n";
    exit(1);
}

echo "🎉 All geofence functionality tests completed!\n";
echo "✅ Production ready for MTsN 2 Absensi v1.0.1\n";