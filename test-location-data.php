<?php
/**
 * Test Location Data Storage
 * 
 * This script verifies that location data is being saved correctly in the database
 */

require_once 'vendor/autoload.php';

// Bootstrap Laravel application
$app = require_once 'bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Attendance;
use Illuminate\Support\Facades\DB;

echo "=====================================\n";
echo "Testing Location Data Storage\n";
echo "=====================================\n\n";

// Get a sample attendance record
$attendance = Attendance::first();

if (!$attendance) {
    echo "❌ No attendance records found in database\n";
    exit(1);
}

echo "Sample Attendance Record:\n";
echo "ID: " . $attendance->id . "\n";
echo "Date: " . $attendance->date . "\n";
echo "Clock In: " . ($attendance->clock_in ? $attendance->clock_in->format('Y-m-d H:i:s') : 'N/A') . "\n";
echo "Clock Out: " . ($attendance->clock_out ? $attendance->clock_out->format('Y-m-d H:i:s') : 'N/A') . "\n";

echo "\nClock In Location Data:\n";
echo "Latitude: " . ($attendance->clock_in_latitude ?? 'N/A') . "\n";
echo "Longitude: " . ($attendance->clock_in_longitude ?? 'N/A') . "\n";
echo "Accuracy: " . ($attendance->clock_in_accuracy ?? 'N/A') . " meters\n";

echo "\nClock Out Location Data:\n";
echo "Latitude: " . ($attendance->clock_out_latitude ?? 'N/A') . "\n";
echo "Longitude: " . ($attendance->clock_out_longitude ?? 'N/A') . "\n";
echo "Accuracy: " . ($attendance->clock_out_accuracy ?? 'N/A') . " meters\n";

// Update with test location data if not already present
if (is_null($attendance->clock_in_latitude) || is_null($attendance->clock_in_longitude)) {
    echo "\n🔧 Adding test location data to attendance record...\n";
    
    $attendance->update([
        'clock_in_latitude' => -7.99478,
        'clock_in_longitude' => 112.67383,
        'clock_in_accuracy' => 10,
        'clock_out_latitude' => -7.99478,
        'clock_out_longitude' => 112.67383,
        'clock_out_accuracy' => 15
    ]);
    
    echo "✅ Test location data added\n";
    
    // Verify the update
    $attendance->refresh();
    
    echo "\nUpdated Location Data:\n";
    echo "Clock In Latitude: " . ($attendance->clock_in_latitude ?? 'N/A') . "\n";
    echo "Clock In Longitude: " . ($attendance->clock_in_longitude ?? 'N/A') . "\n";
    echo "Clock In Accuracy: " . ($attendance->clock_in_accuracy ?? 'N/A') . " meters\n";
    echo "Clock Out Latitude: " . ($attendance->clock_out_latitude ?? 'N/A') . "\n";
    echo "Clock Out Longitude: " . ($attendance->clock_out_longitude ?? 'N/A') . "\n";
    echo "Clock Out Accuracy: " . ($attendance->clock_out_accuracy ?? 'N/A') . " meters\n";
} else {
    echo "\n✅ Location data already present in database\n";
}

echo "\n🎉 Location data storage test completed!\n";