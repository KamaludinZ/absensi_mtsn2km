#!/bin/bash

# Create Production Deployment Package Script
# This script creates a zip file with all necessary files for production deployment

echo "==============================================="
echo "Creating Production Deployment Package"
echo "MTsN 2 Absensi v1.0.1"
echo "==============================================="
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

PACKAGE_NAME="mtsn2-absensi-v1.0.1-production.zip"
TEMP_DIR="/tmp/mtsn2-absensi-deploy"

echo -e "${BLUE}📦 Creating deployment package: ${PACKAGE_NAME}${NC}"
echo ""

# Check if we're in the right directory
if [ ! -f "composer.json" ]; then
    echo -e "${RED}❌ Error: Please run this script from the project root directory${NC}"
    exit 1
fi

# Clean up any existing temporary directory
if [ -d "$TEMP_DIR" ]; then
    echo -e "${YELLOW}⚠️  Cleaning up existing temporary directory${NC}"
    rm -rf "$TEMP_DIR"
fi

# Create temporary directory
mkdir -p "$TEMP_DIR"

echo -e "${BLUE}📋 Copying application files...${NC}"

# Copy essential files and directories
cp -r app "$TEMP_DIR/"
cp -r bootstrap "$TEMP_DIR/"
cp -r config "$TEMP_DIR/"
cp -r database "$TEMP_DIR/"
cp -r lang "$TEMP_DIR/"
cp -r public "$TEMP_DIR/"
cp -r resources "$TEMP_DIR/"
cp -r routes "$TEMP_DIR/"
cp -r storage "$TEMP_DIR/"
cp -r vendor "$TEMP_DIR/"

# Copy essential files
cp .env.production "$TEMP_DIR/.env"
cp composer.json "$TEMP_DIR/"
cp composer.lock "$TEMP_DIR/"
cp package.json "$TEMP_DIR/"
cp package-lock.json "$TEMP_DIR/"
cp artisan "$TEMP_DIR/"
cp README.md "$TEMP_DIR/"
cp VERSION "$TEMP_DIR/"

# Copy deployment scripts
cp deploy-production.sh "$TEMP_DIR/"
cp deploy-production.bat "$TEMP_DIR/"
cp deploy-shared-hosting.sh "$TEMP_DIR/"
cp deploy-shared-hosting.bat "$TEMP_DIR/"
cp PRODUCTION_DEPLOYMENT_CHECKLIST.md "$TEMP_DIR/"
cp RELEASE_NOTES_v1.0.1.md "$TEMP_DIR/"

echo -e "${GREEN}✅ Application files copied${NC}"

# Remove unnecessary files from the package
echo -e "${BLUE}🧹 Cleaning up unnecessary files...${NC}"
cd "$TEMP_DIR"

# Remove development files
rm -rf .git
rm -rf .github
rm -rf tests
rm -rf .claude
rm -f .env.example
rm -f .env.testing
rm -f phpunit.xml
rm -f vite.config.js
rm -f tailwind.config.js
rm -f test_*.php
rm -f *_test.php
rm -f create-*.php
rm -f *_FIX.md
rm -f *_DEBUG.md
rm -f *_TEST.md
rm -f *_GUIDE.md
rm -f *_IMPROVEMENTS.md
rm -f *_IMPLEMENTATION.md
rm -f *_UPDATES.md
rm -f *_DOCUMENTATION.md
rm -f *_SUMMARY.md
rm -f CHECKLIST_*.md
rm -f HOW_TO_*.md
rm -f QUICK_FIX_*.md
rm -f SOLUSI_*.md
rm -f VERIFY_*.php
rm -f SETUP_*.php
rm -f TEMP_*.php
rm -f DEBUG_*.php
rm -f FIX_*.php
rm -f TEST_*.php
rm -f UPDATE_*.php
rm -f deploy-helper.php
rm -f DEPLOY_COMMANDS.sh

# Remove IDE files
find . -name "*.DS_Store" -type f -delete
find . -name ".DS_Store" -type f -delete
find . -name "Thumbs.db" -type f -delete
find . -name ".idea" -type d -exec rm -rf {} +
find . -name ".vscode" -type d -exec rm -rf {} +

# Remove log files
find . -name "*.log" -type f -delete
rm -rf storage/logs/*

# Remove cache files
rm -rf storage/framework/cache/*
rm -rf storage/framework/sessions/*
rm -rf storage/framework/views/*
rm -rf storage/app/public/selfies/*
rm -rf bootstrap/cache/*.php

echo -e "${GREEN}✅ Unnecessary files removed${NC}"

# Create required directories if they don't exist
echo -e "${BLUE}📂 Creating required directories...${NC}"
mkdir -p storage/framework/cache
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p storage/app/public/selfies
mkdir -p bootstrap/cache

echo -e "${GREEN}✅ Required directories created${NC}"

# Set proper permissions
echo -e "${BLUE}🔐 Setting permissions...${NC}"
chmod -R 755 .
chmod -R 775 storage bootstrap/cache

echo -e "${GREEN}✅ Permissions set${NC}"

# Create the zip package
echo -e "${BLUE}📦 Creating zip package...${NC}"
cd ..
zip -r "$PACKAGE_NAME" mtsn2-absensi-deploy/* > /dev/null

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Deployment package created: ${PACKAGE_NAME}${NC}"
    echo -e "${BLUE}📦 Package size: $(du -h "$PACKAGE_NAME" | cut -f1)${NC}"
else
    echo -e "${RED}❌ Error creating deployment package${NC}"
    exit 1
fi

# Clean up temporary directory
rm -rf "$TEMP_DIR"

echo ""
echo -e "${GREEN}🎉 Deployment package creation completed!${NC}"
echo "Package: $PACKAGE_NAME"
echo "Ready for production deployment without symlinks and Vite."
echo ""