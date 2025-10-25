#!/bin/bash

# Simplified Production Deployment Script for Shared Hosting
# Use this script for cPanel or shared hosting environments

echo "==============================================="
echo "Simplified Deployment Script - MTsN 2 Absensi"
echo "Version: 1.0.1"
echo "==============================================="
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Check if we're in the right directory
if [ ! -f "composer.json" ]; then
    echo -e "${RED}❌ Error: Please run this script from the project root directory${NC}"
    exit 1
fi

echo -e "${BLUE}🔍 Checking environment...${NC}"

# Check if .env exists
if [ ! -f ".env" ]; then
    echo -e "${YELLOW}⚠️  .env file not found, copying from .env.production${NC}"
    if [ -f ".env.production" ]; then
        cp .env.production .env
        echo -e "${GREEN}✅ .env file created from .env.production${NC}"
    else
        echo -e "${RED}❌ Error: Neither .env nor .env.production file found${NC}"
        exit 1
    fi
fi

# Check APP_ENV
APP_ENV=$(grep "^APP_ENV=" .env | cut -d '=' -f2)
if [ "$APP_ENV" != "production" ]; then
    echo -e "${YELLOW}⚠️  Setting APP_ENV to production${NC}"
    sed -i 's/^APP_ENV=.*/APP_ENV=production/' .env
fi

# Check APP_DEBUG
APP_DEBUG=$(grep "^APP_DEBUG=" .env | cut -d '=' -f2)
if [ "$APP_DEBUG" != "false" ]; then
    echo -e "${YELLOW}⚠️  Setting APP_DEBUG to false${NC}"
    sed -i 's/^APP_DEBUG=.*/APP_DEBUG=false/' .env
fi

echo -e "${GREEN}✅ Environment configured for production${NC}"
echo ""

echo -e "${BLUE}📦 Installing production dependencies...${NC}"
composer install --no-dev --optimize-autoloader --no-interaction
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Production dependencies installed${NC}"
else
    echo -e "${RED}❌ Error installing dependencies${NC}"
    exit 1
fi
echo ""

echo -e "${BLUE}🔄 Optimizing autoloader...${NC}"
composer dump-autoload --optimize --no-interaction
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Autoloader optimized${NC}"
else
    echo -e "${RED}❌ Error optimizing autoloader${NC}"
    exit 1
fi
echo ""

echo -e "${BLUE}🏗️  Building frontend assets...${NC}"
# For shared hosting, we'll use a pre-built approach
if [ -d "public/build" ]; then
    echo -e "${GREEN}✅ Frontend assets already built${NC}"
else
    echo -e "${YELLOW}⚠️  Building frontend assets locally${NC}"
    npm install --no-audit --no-fund
    npm run build
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✅ Frontend assets built successfully${NC}"
    else
        echo -e "${RED}❌ Error building frontend assets${NC}"
        exit 1
    fi
fi
echo ""

echo -e "${BLUE}🧹 Clearing and optimizing caches...${NC}"
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
echo -e "${GREEN}✅ Caches cleared and optimized${NC}"
echo ""

echo -e "${BLUE}📂 Setting permissions...${NC}"
# Set proper permissions for storage and bootstrap/cache directories
chmod -R 755 .
chmod -R 775 storage bootstrap/cache

echo -e "${GREEN}✅ Permissions set${NC}"
echo ""

echo -e "${BLUE}📊 Running migrations...${NC}"
php artisan migrate --force
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Migrations completed${NC}"
else
    echo -e "${RED}❌ Error running migrations${NC}"
    exit 1
fi
echo ""

echo -e "${BLUE}🔗 Creating storage link...${NC}"
# For shared hosting, we need to create the link manually
if [ -L "public/storage" ]; then
    echo -e "${GREEN}✅ Storage link already exists${NC}"
elif [ -d "public/storage" ]; then
    echo -e "${YELLOW}⚠️  public/storage is a directory, removing it...${NC}"
    rm -rf public/storage
    php artisan storage:link
    echo -e "${GREEN}✅ Storage link created${NC}"
else
    php artisan storage:link
    echo -e "${GREEN}✅ Storage link created${NC}"
fi
echo ""

echo -e "${BLUE}✅ Deployment Checklist${NC}"
echo "----------------------------------------"
echo -e "${GREEN}✅ Environment set to production${NC}"
echo -e "${GREEN}✅ Debug mode disabled${NC}"
echo -e "${GREEN}✅ Dependencies installed${NC}"
echo -e "${GREEN}✅ Frontend assets built${NC}"
echo -e "${GREEN}✅ Caches optimized${NC}"
echo -e "${GREEN}✅ Permissions set${NC}"
echo -e "${GREEN}✅ Database migrated${NC}"
echo -e "${GREEN}✅ Storage linked${NC}"
echo ""

echo -e "${YELLOW}⚠️  Post-Deployment Verification:${NC}"
echo "----------------------------------------"
echo "1. Check that .env file has correct settings:"
echo "   - APP_ENV=production"
echo "   - APP_DEBUG=false"
echo "   - DB_* settings correct"
echo ""
echo "2. Verify application works by accessing it in browser"
echo "3. Test attendance functionality with location"
echo "4. Check that reports show coordinate information"
echo "5. Verify photo uploads work correctly"
echo ""

echo -e "${GREEN}🎉 Deployment completed successfully!${NC}"
echo "Application is ready for production use."
echo ""