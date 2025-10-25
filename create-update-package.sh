#!/bin/bash

# Script untuk membuat update package di Linux/Mac
# Untuk dijalankan di root project Laravel

echo "========================================"
echo " SIAB Update Package Creator"
echo "========================================"
echo ""

# Read version from VERSION file
if [ ! -f "VERSION" ]; then
    echo "❌ ERROR: VERSION file not found!"
    exit 1
fi

VERSION=$(cat VERSION)
echo "Current Version: $VERSION"
echo ""

# Set filename
FILENAME="update-${VERSION}.zip"

# Check if file already exists
if [ -f "$FILENAME" ]; then
    echo "File $FILENAME already exists!"
    read -p "Overwrite? (y/n): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        echo "Cancelled."
        exit 0
    fi
    rm "$FILENAME"
fi

echo ""
echo "Creating package..."
echo ""

# Create temp directory
TEMP_DIR="temp-update-package"
rm -rf "$TEMP_DIR"
mkdir -p "$TEMP_DIR"

# Copy required folders
echo "[1/7] Copying app folder..."
cp -r app "$TEMP_DIR/"

echo "[2/7] Copying bootstrap folder..."
cp -r bootstrap "$TEMP_DIR/"

echo "[3/7] Copying config folder..."
cp -r config "$TEMP_DIR/"

echo "[4/7] Copying database folder..."
cp -r database "$TEMP_DIR/"

echo "[5/7] Copying public folder..."
cp -r public "$TEMP_DIR/"

echo "[6/7] Copying resources folder..."
cp -r resources "$TEMP_DIR/"

echo "[7/7] Copying routes folder..."
cp -r routes "$TEMP_DIR/"

# Copy required files
echo ""
echo "Copying files..."
cp artisan "$TEMP_DIR/"
cp composer.json "$TEMP_DIR/"
cp composer.lock "$TEMP_DIR/"
cp VERSION "$TEMP_DIR/"

# Create ZIP
echo ""
echo "Creating ZIP archive..."
cd "$TEMP_DIR"
zip -r -q "../$FILENAME" .
cd ..

# Cleanup temp directory
echo "Cleaning up..."
rm -rf "$TEMP_DIR"

# Show result
echo ""
echo "========================================"
if [ -f "$FILENAME" ]; then
    echo "✅ SUCCESS!"
    echo "Package created: $FILENAME"

    # Get file size
    SIZE=$(du -h "$FILENAME" | cut -f1)
    echo "Size: $SIZE"

    # Check if size exceeds 100MB
    SIZE_MB=$(du -m "$FILENAME" | cut -f1)
    if [ $SIZE_MB -gt 100 ]; then
        echo ""
        echo "⚠️  WARNING: File size exceeds 100MB limit!"
        echo "Please optimize the package or remove unnecessary files."
    fi
else
    echo "❌ FAILED!"
    echo "Package creation failed. Please check errors above."
fi
echo "========================================"
echo ""
