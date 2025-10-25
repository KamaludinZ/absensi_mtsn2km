#!/bin/bash

###############################################################################
# Deployment Commands untuk siab.mtsn2kotamalang.sch.id
# Jalankan script ini di server via SSH
###############################################################################

echo "================================================="
echo "Deployment Setup - MTsN 2 Absensi"
echo "Domain: siab.mtsn2kotamalang.sch.id"
echo "================================================="
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Paths
APP_PATH="/home/mtsnkot4/absensi_app"
WEB_PATH="/home/mtsnkot4/siab.mtsn2kotamalang.sch.id"

echo -e "${YELLOW}STEP 1: Creating correct index.php${NC}"
cat > ${WEB_PATH}/index.php << 'INDEXPHP'
<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Path to Laravel application
$appPath = __DIR__.'/../absensi_app';

// Check for maintenance mode
if (file_exists($maintenance = $appPath.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader
require $appPath.'/vendor/autoload.php';

// Bootstrap Laravel and handle the request
$app = require_once $appPath.'/bootstrap/app.php';

$kernel = $app->make(Kernel::class);

$response = $kernel->handle(
    $request = Request::capture()
)->send();

$kernel->terminate($request, $response);
INDEXPHP

echo -e "${GREEN}✓ index.php created${NC}"
echo ""

echo -e "${YELLOW}STEP 2: Checking .env configuration${NC}"
if [ -f "${APP_PATH}/.env" ]; then
    # Check APP_ENV
    APP_ENV=$(grep "^APP_ENV=" ${APP_PATH}/.env | cut -d '=' -f2)
    if [ "$APP_ENV" != "production" ]; then
        echo -e "${YELLOW}⚠️  APP_ENV is not 'production', setting it now...${NC}"
        sed -i 's/^APP_ENV=.*/APP_ENV=production/' ${APP_PATH}/.env
    fi

    # Check APP_DEBUG
    APP_DEBUG=$(grep "^APP_DEBUG=" ${APP_PATH}/.env | cut -d '=' -f2)
    if [ "$APP_DEBUG" != "false" ]; then
        echo -e "${YELLOW}⚠️  APP_DEBUG is not 'false', setting it now...${NC}"
        sed -i 's/^APP_DEBUG=.*/APP_DEBUG=false/' ${APP_PATH}/.env
    fi

    # Check APP_URL
    APP_URL=$(grep "^APP_URL=" ${APP_PATH}/.env | cut -d '=' -f2)
    if [[ "$APP_URL" != *"siab.mtsn2kotamalang.sch.id"* ]]; then
        echo -e "${YELLOW}⚠️  APP_URL not set correctly, updating...${NC}"
        sed -i 's|^APP_URL=.*|APP_URL=https://siab.mtsn2kotamalang.sch.id|' ${APP_PATH}/.env
    fi

    echo -e "${GREEN}✓ .env configuration checked${NC}"
else
    echo -e "${RED}✗ .env file not found!${NC}"
    echo -e "${YELLOW}Please create .env file in ${APP_PATH}/${NC}"
    exit 1
fi
echo ""

echo -e "${YELLOW}STEP 3: Setting permissions${NC}"
cd ${APP_PATH}
chmod -R 755 .
chmod -R 775 storage bootstrap/cache
chown -R mtsnkot4:mtsnkot4 .
echo -e "${GREEN}✓ Permissions set${NC}"
echo ""

echo -e "${YELLOW}STEP 4: Installing/updating dependencies${NC}"
cd ${APP_PATH}
composer install --no-dev --optimize-autoloader 2>&1 | tail -5
composer dump-autoload --optimize
echo -e "${GREEN}✓ Dependencies installed${NC}"
echo ""

echo -e "${YELLOW}STEP 5: Checking database connection${NC}"
cd ${APP_PATH}
php artisan tinker --execute="try { DB::connection()->getPdo(); echo 'Database connected successfully\n'; } catch (Exception \$e) { echo 'Database connection failed: ' . \$e->getMessage() . '\n'; }"
echo ""

echo -e "${YELLOW}STEP 6: Running migrations${NC}"
read -p "Do you want to run fresh migrations? This will DELETE all data! (y/N): " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    php artisan migrate:fresh --force
    echo ""
    read -p "Do you want to seed the database? (Y/n): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Nn]$ ]]; then
        php artisan db:seed --class=DatabaseSeeder --force
    fi
else
    echo "Skipping migrations. If you need to run them manually:"
    echo "  cd ${APP_PATH} && php artisan migrate --force"
fi
echo -e "${GREEN}✓ Database setup complete${NC}"
echo ""

echo -e "${YELLOW}STEP 7: Clearing and caching${NC}"
cd ${APP_PATH}
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
echo -e "${GREEN}✓ Cache cleared and optimized${NC}"
echo ""

echo -e "${YELLOW}STEP 8: Checking build assets${NC}"
if [ -f "${WEB_PATH}/build/manifest.json" ]; then
    echo -e "${GREEN}✓ manifest.json found${NC}"

    if [ -d "${WEB_PATH}/build/assets" ]; then
        CSS_COUNT=$(find ${WEB_PATH}/build/assets -name "*.css" | wc -l)
        JS_COUNT=$(find ${WEB_PATH}/build/assets -name "*.js" | wc -l)
        echo -e "${GREEN}✓ Assets found: ${CSS_COUNT} CSS, ${JS_COUNT} JS files${NC}"
    else
        echo -e "${RED}✗ Assets directory not found!${NC}"
        echo -e "${YELLOW}Please upload 'public/build/assets/' from local to ${WEB_PATH}/build/assets/${NC}"
    fi
else
    echo -e "${RED}✗ manifest.json not found!${NC}"
    echo -e "${YELLOW}Please upload 'public/build/' folder from local to ${WEB_PATH}/build/${NC}"
    echo ""
    echo -e "${YELLOW}On your local machine, run:${NC}"
    echo "  npm run build"
    echo "  Then upload the entire 'public/build/' folder"
fi
echo ""

echo "================================================="
echo -e "${GREEN}Deployment Complete!${NC}"
echo "================================================="
echo ""
echo "Next steps:"
echo "1. Check if build assets are uploaded (manifest.json and assets/)"
echo "2. Test the website: https://siab.mtsn2kotamalang.sch.id"
echo "3. Default login: admin / password"
echo "4. IMPORTANT: Change admin password immediately!"
echo ""
echo "Useful commands:"
echo "  View logs: tail -f ${APP_PATH}/storage/logs/laravel.log"
echo "  Clear cache: cd ${APP_PATH} && php artisan optimize:clear"
echo "  Check environment: cd ${APP_PATH} && php artisan env"
echo ""
echo "If you see stylesheet errors, make sure:"
echo "  - APP_ENV=production in .env"
echo "  - build/manifest.json exists in ${WEB_PATH}/"
echo "  - build/assets/ folder exists with CSS and JS files"
echo ""

# Final verification
echo "=== Quick Verification ==="
echo "APP_ENV: $(grep '^APP_ENV=' ${APP_PATH}/.env)"
echo "Build folder: $(ls -d ${WEB_PATH}/build 2>&1)"
echo "Manifest: $(ls ${WEB_PATH}/build/manifest.json 2>&1)"
echo ""
echo "Done!"
