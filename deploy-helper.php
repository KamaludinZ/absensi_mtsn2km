<?php
/**
 * Deployment Helper Script
 *
 * IMPORTANT:
 * 1. Upload this file to public_html folder
 * 2. Access via browser: https://yourdomain.com/deploy-helper.php
 * 3. DELETE this file immediately after use!
 *
 * Security: Add password protection
 */

// CHANGE THIS PASSWORD!
define('DEPLOY_PASSWORD', 'change_this_to_secure_password');

// Refuse to run with the default password: anyone reading this repository knows it.
if (DEPLOY_PASSWORD === 'change_this_to_secure_password' || strlen(DEPLOY_PASSWORD) < 12) {
    http_response_code(403);
    die('Access denied. Set DEPLOY_PASSWORD in this file to a unique password (min. 12 characters) first.');
}

// Check password
if (!isset($_GET['password']) || !is_string($_GET['password']) || !hash_equals(DEPLOY_PASSWORD, $_GET['password'])) {
    die('Access denied. Use: ?password=YOUR_PASSWORD');
}

// Check if Laravel exists
$laravelPath = __DIR__ . '/../absensi_app';
if (!file_exists($laravelPath)) {
    die('❌ Laravel application not found at: ' . $laravelPath);
}

require $laravelPath . '/vendor/autoload.php';
$app = require_once $laravelPath . '/bootstrap/app.php';

echo "<html><head><title>Deployment Helper</title>";
echo "<style>
    body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
    .success { color: green; padding: 10px; background: #d4edda; border-radius: 5px; margin: 10px 0; }
    .error { color: red; padding: 10px; background: #f8d7da; border-radius: 5px; margin: 10px 0; }
    .info { color: blue; padding: 10px; background: #d1ecf1; border-radius: 5px; margin: 10px 0; }
    .warning { color: orange; padding: 10px; background: #fff3cd; border-radius: 5px; margin: 10px 0; }
    button { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin: 5px; }
    button:hover { background: #0056b3; }
    pre { background: #f4f4f4; padding: 10px; border-radius: 5px; overflow-x: auto; }
</style></head><body>";

echo "<h1>🚀 Deployment Helper</h1>";
echo "<div class='warning'><strong>⚠️ WARNING:</strong> DELETE this file after deployment!</div>";

$action = $_GET['action'] ?? 'menu';

// Navigation
echo "<div style='margin: 20px 0;'>";
echo "<a href='?password=" . DEPLOY_PASSWORD . "'><button>🏠 Menu</button></a> ";
echo "<a href='?password=" . DEPLOY_PASSWORD . "&action=check'><button>✅ Check System</button></a> ";
echo "<a href='?password=" . DEPLOY_PASSWORD . "&action=migrate'><button>🗄️ Run Migration</button></a> ";
echo "<a href='?password=" . DEPLOY_PASSWORD . "&action=cache'><button>🔄 Clear Cache</button></a> ";
echo "<a href='?password=" . DEPLOY_PASSWORD . "&action=symlink'><button>🔗 Create Symlink</button></a> ";
echo "<a href='?password=" . DEPLOY_PASSWORD . "&action=optimize'><button>⚡ Optimize</button></a> ";
echo "</div><hr>";

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

switch ($action) {
    case 'check':
        echo "<h2>System Check</h2>";

        // PHP Version
        echo "<div class='" . (PHP_VERSION_ID >= 80200 ? "success" : "error") . "'>";
        echo "PHP Version: " . PHP_VERSION;
        echo "</div>";

        // Required Extensions
        $required = ['pdo', 'mbstring', 'openssl', 'tokenizer', 'json', 'bcmath', 'ctype', 'fileinfo', 'xml'];
        foreach ($required as $ext) {
            $loaded = extension_loaded($ext);
            echo "<div class='" . ($loaded ? "success" : "error") . "'>";
            echo ($loaded ? "✅" : "❌") . " Extension: $ext";
            echo "</div>";
        }

        // Writable Directories
        $writables = [
            $laravelPath . '/storage' => 'storage/',
            $laravelPath . '/bootstrap/cache' => 'bootstrap/cache/',
        ];

        foreach ($writables as $path => $name) {
            $writable = is_writable($path);
            echo "<div class='" . ($writable ? "success" : "error") . "'>";
            echo ($writable ? "✅" : "❌") . " Writable: $name";
            if (!$writable) {
                echo " - chmod 775 needed";
            }
            echo "</div>";
        }

        // .env exists
        $envExists = file_exists($laravelPath . '/.env');
        echo "<div class='" . ($envExists ? "success" : "error") . "'>";
        echo ($envExists ? "✅" : "❌") . " .env file";
        echo "</div>";

        // Database connection
        try {
            DB::connection()->getPdo();
            echo "<div class='success'>✅ Database Connected</div>";
        } catch (\Exception $e) {
            echo "<div class='error'>❌ Database Connection Failed: " . $e->getMessage() . "</div>";
        }

        // Storage symlink
        $symlinkExists = is_link(__DIR__ . '/storage');
        echo "<div class='" . ($symlinkExists ? "success" : "warning") . "'>";
        echo ($symlinkExists ? "✅" : "⚠️") . " Storage Symlink";
        if (!$symlinkExists) {
            echo " - <a href='?password=" . DEPLOY_PASSWORD . "&action=symlink'>Create Now</a>";
        }
        echo "</div>";

        break;

    case 'migrate':
        echo "<h2>Database Migration</h2>";
        echo "<div class='warning'>Running migrations...</div>";

        try {
            ob_start();
            $status = $kernel->call('migrate', ['--force' => true]);
            $output = ob_get_clean();

            if ($status === 0) {
                echo "<div class='success'>✅ Migration completed successfully!</div>";
            } else {
                echo "<div class='error'>❌ Migration failed!</div>";
            }

            echo "<pre>" . htmlspecialchars($output) . "</pre>";
        } catch (\Exception $e) {
            echo "<div class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
        }

        break;

    case 'cache':
        echo "<h2>Clear Cache</h2>";

        $cacheCommands = [
            'config:clear' => 'Config Cache',
            'route:clear' => 'Route Cache',
            'view:clear' => 'View Cache',
            'cache:clear' => 'Application Cache',
        ];

        foreach ($cacheCommands as $command => $label) {
            try {
                ob_start();
                $kernel->call($command);
                $output = ob_get_clean();
                echo "<div class='success'>✅ Cleared: $label</div>";
            } catch (\Exception $e) {
                echo "<div class='error'>❌ Failed to clear $label: " . $e->getMessage() . "</div>";
            }
        }

        break;

    case 'symlink':
        echo "<h2>Create Storage Symlink</h2>";

        try {
            $target = $laravelPath . '/storage/app/public';
            $link = __DIR__ . '/storage';

            if (is_link($link)) {
                echo "<div class='info'>ℹ️ Symlink already exists</div>";
            } elseif (file_exists($link)) {
                echo "<div class='error'>❌ 'storage' folder already exists. Please remove it first.</div>";
            } else {
                if (symlink($target, $link)) {
                    echo "<div class='success'>✅ Storage symlink created successfully!</div>";
                } else {
                    echo "<div class='error'>❌ Failed to create symlink. Check permissions.</div>";
                }
            }

            // Verify
            if (is_link($link)) {
                echo "<div class='info'>Symlink points to: " . readlink($link) . "</div>";
            }
        } catch (\Exception $e) {
            echo "<div class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
        }

        break;

    case 'optimize':
        echo "<h2>Optimize Application</h2>";

        $optimizeCommands = [
            'config:cache' => 'Config',
            'route:cache' => 'Routes',
            'view:cache' => 'Views',
        ];

        foreach ($optimizeCommands as $command => $label) {
            try {
                ob_start();
                $kernel->call($command);
                $output = ob_get_clean();
                echo "<div class='success'>✅ Optimized: $label</div>";
            } catch (\Exception $e) {
                echo "<div class='error'>❌ Failed to optimize $label: " . $e->getMessage() . "</div>";
            }
        }

        echo "<div class='info'>ℹ️ Application optimized for production</div>";

        break;

    default:
        echo "<h2>Main Menu</h2>";
        echo "<div class='info'>";
        echo "<h3>Available Actions:</h3>";
        echo "<ul>";
        echo "<li><strong>Check System:</strong> Verify server requirements and permissions</li>";
        echo "<li><strong>Run Migration:</strong> Create database tables</li>";
        echo "<li><strong>Clear Cache:</strong> Clear all cached files</li>";
        echo "<li><strong>Create Symlink:</strong> Link storage folder for public access</li>";
        echo "<li><strong>Optimize:</strong> Cache config, routes, and views for production</li>";
        echo "</ul>";
        echo "</div>";

        echo "<div class='warning'>";
        echo "<h3>⚠️ Important Security Steps:</h3>";
        echo "<ol>";
        echo "<li>Complete all necessary deployment actions above</li>";
        echo "<li>Verify everything works correctly</li>";
        echo "<li><strong>DELETE this deploy-helper.php file!</strong></li>";
        echo "<li>Set APP_DEBUG=false in .env</li>";
        echo "<li>Set APP_ENV=production in .env</li>";
        echo "</ol>";
        echo "</div>";
}

echo "<hr>";
echo "<div class='error'><strong>🚨 SECURITY REMINDER:</strong> DELETE this file immediately after deployment!</div>";
echo "<pre>File location: " . __FILE__ . "</pre>";

echo "</body></html>";
