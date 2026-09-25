<?php
/**
 * Create Storage Symlink
 *
 * INSTRUCTIONS:
 * 1. Upload this file to public_html folder
 * 2. Access via browser: https://yourdomain.com/create-symlink.php
 * 3. DELETE this file immediately after symlink created!
 *
 * SECURITY: This file is protected with a password
 */

// CHANGE THIS PASSWORD!
define('SYMLINK_PASSWORD', 'change_this_secure_password');

// Check password
// Refuse to run with the default password: anyone reading this repository knows it.
if (SYMLINK_PASSWORD === 'change_this_secure_password' || strlen(SYMLINK_PASSWORD) < 12) {
    http_response_code(403);
    die('Access denied. Set SYMLINK_PASSWORD in this file to a unique password (min. 12 characters) first.');
}

if (!isset($_GET['password']) || !is_string($_GET['password']) || !hash_equals(SYMLINK_PASSWORD, $_GET['password'])) {
    die('Access denied. Usage: ?password=YOUR_PASSWORD');
}

echo "<html><head><title>Create Storage Symlink</title>";
echo "<style>
    body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
    .success { color: green; padding: 15px; background: #d4edda; border-radius: 5px; margin: 10px 0; }
    .error { color: red; padding: 15px; background: #f8d7da; border-radius: 5px; margin: 10px 0; }
    .warning { color: orange; padding: 15px; background: #fff3cd; border-radius: 5px; margin: 10px 0; }
    .info { color: blue; padding: 15px; background: #d1ecf1; border-radius: 5px; margin: 10px 0; }
    code { background: #f4f4f4; padding: 2px 5px; border-radius: 3px; }
</style></head><body>";

echo "<h1>🔗 Storage Symlink Creator</h1>";

// Configuration
$target = __DIR__ . '/../absensi_app/storage/app/public';
$link = __DIR__ . '/storage';

// Check if target exists
if (!file_exists($target)) {
    echo "<div class='error'>";
    echo "❌ <strong>Error:</strong> Target directory does not exist!<br>";
    echo "Looking for: <code>" . htmlspecialchars($target) . "</code><br><br>";
    echo "Please verify:<br>";
    echo "1. Laravel application is in <code>/home/username/absensi_app</code><br>";
    echo "2. Adjust the path in this script if your structure is different";
    echo "</div>";
} elseif (is_link($link)) {
    // Symlink already exists
    echo "<div class='info'>";
    echo "ℹ️ <strong>Info:</strong> Symlink already exists!<br>";
    echo "Current symlink points to: <code>" . htmlspecialchars(readlink($link)) . "</code>";
    echo "</div>";

    // Verify it points to correct location
    if (readlink($link) === $target) {
        echo "<div class='success'>";
        echo "✅ Symlink is correctly configured!";
        echo "</div>";
    } else {
        echo "<div class='warning'>";
        echo "⚠️ Warning: Symlink exists but points to wrong location!<br>";
        echo "Expected: <code>" . htmlspecialchars($target) . "</code><br>";
        echo "Current: <code>" . htmlspecialchars(readlink($link)) . "</code><br><br>";
        echo "To fix: Delete the existing symlink and run this script again.";
        echo "</div>";
    }
} elseif (file_exists($link)) {
    // File or folder exists with same name
    echo "<div class='error'>";
    echo "❌ <strong>Error:</strong> Cannot create symlink!<br>";
    echo "A file or directory named 'storage' already exists at:<br>";
    echo "<code>" . htmlspecialchars($link) . "</code><br><br>";
    echo "<strong>Solution:</strong><br>";
    echo "1. Rename or delete the existing 'storage' folder<br>";
    echo "2. Run this script again<br><br>";
    echo "<em>Note: The existing folder is NOT the storage symlink!</em>";
    echo "</div>";
} else {
    // Create symlink
    try {
        if (symlink($target, $link)) {
            echo "<div class='success'>";
            echo "✅ <strong>Success!</strong> Storage symlink created!<br><br>";
            echo "<strong>Details:</strong><br>";
            echo "Link: <code>" . htmlspecialchars($link) . "</code><br>";
            echo "Target: <code>" . htmlspecialchars($target) . "</code><br><br>";
            echo "You can now upload files and they will be accessible via web!";
            echo "</div>";

            // Verify symlink
            if (is_link($link)) {
                echo "<div class='info'>";
                echo "✅ Verification: Symlink is working correctly!";
                echo "</div>";
            }
        } else {
            echo "<div class='error'>";
            echo "❌ <strong>Error:</strong> Failed to create symlink!<br><br>";
            echo "Possible reasons:<br>";
            echo "1. PHP doesn't have permission to create symlinks<br>";
            echo "2. Server has disabled symlink() function<br>";
            echo "3. File system doesn't support symlinks<br><br>";
            echo "<strong>Alternative Solution:</strong><br>";
            echo "Contact your hosting provider or use SSH:<br>";
            echo "<code>ln -s " . htmlspecialchars($target) . " " . htmlspecialchars($link) . "</code>";
            echo "</div>";
        }
    } catch (Exception $e) {
        echo "<div class='error'>";
        echo "❌ <strong>Exception:</strong> " . htmlspecialchars($e->getMessage()) . "<br><br>";
        echo "Please contact your hosting provider for assistance.";
        echo "</div>";
    }
}

echo "<hr>";
echo "<div class='warning'>";
echo "<strong>🚨 IMPORTANT SECURITY REMINDER:</strong><br>";
echo "DELETE this file (<code>create-symlink.php</code>) immediately!<br>";
echo "File location: <code>" . htmlspecialchars(__FILE__) . "</code>";
echo "</div>";

echo "<hr>";
echo "<div class='info'>";
echo "<h3>Next Steps:</h3>";
echo "<ol>";
echo "<li>Verify symlink is created correctly</li>";
echo "<li>Test file upload functionality</li>";
echo "<li><strong>DELETE this create-symlink.php file!</strong></li>";
echo "</ol>";
echo "</div>";

echo "</body></html>";
