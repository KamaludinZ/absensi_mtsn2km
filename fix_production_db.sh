#!/bin/bash

###############################################################################
# Script untuk Fix Database Production - MTsN 2 Absensi
# Error: Column "email" does not exist in users table
###############################################################################

echo "================================================="
echo "Fix Database Production - MTsN 2 Absensi"
echo "================================================="
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if we're in the right directory
if [ ! -f "artisan" ]; then
    echo -e "${RED}Error: artisan file not found!${NC}"
    echo "Please run this script from your Laravel application root directory."
    echo "Example: cd /home/mtsnkot4/absensi_app/ && bash fix_production_db.sh"
    exit 1
fi

echo -e "${YELLOW}WARNING: This script will RESET your database!${NC}"
echo -e "${YELLOW}All existing data will be DELETED!${NC}"
echo ""
echo "Choose an option:"
echo "1) Fresh Migration (Delete all data + Migrate)"
echo "2) Fresh Migration + Basic Seed (No dummy data)"
echo "3) Fresh Migration + Full Seed (WITH dummy data - for testing)"
echo "4) Check Migration Status Only"
echo "5) Exit"
echo ""
read -p "Enter your choice (1-5): " choice

case $choice in
    1)
        echo -e "${YELLOW}Running fresh migration...${NC}"
        php artisan migrate:fresh

        if [ $? -eq 0 ]; then
            echo -e "${GREEN}✓ Migration completed successfully!${NC}"
            echo ""
            echo "Next step: Run database seeder"
            echo "Command: php artisan db:seed --class=DatabaseSeeder"
        else
            echo -e "${RED}✗ Migration failed!${NC}"
            exit 1
        fi
        ;;

    2)
        echo -e "${YELLOW}Running fresh migration + basic seed...${NC}"
        php artisan migrate:fresh

        if [ $? -eq 0 ]; then
            echo -e "${GREEN}✓ Migration completed successfully!${NC}"
            echo ""
            echo -e "${YELLOW}Running DatabaseSeeder (no dummy data)...${NC}"

            # Temporarily disable DummyDataSeeder by backing up and modifying
            if grep -q "DummyDataSeeder" database/seeders/DatabaseSeeder.php; then
                cp database/seeders/DatabaseSeeder.php database/seeders/DatabaseSeeder.php.backup
                sed -i 's/.*DummyDataSeeder.*/\/\/ &/' database/seeders/DatabaseSeeder.php
                echo "Disabled DummyDataSeeder temporarily"
            fi

            php artisan db:seed --class=DatabaseSeeder

            # Restore backup
            if [ -f "database/seeders/DatabaseSeeder.php.backup" ]; then
                mv database/seeders/DatabaseSeeder.php.backup database/seeders/DatabaseSeeder.php
                echo "Restored DatabaseSeeder.php"
            fi

            if [ $? -eq 0 ]; then
                echo -e "${GREEN}✓ Seeding completed successfully!${NC}"
                echo ""
                echo "Default admin account:"
                echo "  Username: admin"
                echo "  Password: password"
                echo ""
                echo -e "${RED}IMPORTANT: Change the admin password immediately!${NC}"
            else
                echo -e "${RED}✗ Seeding failed!${NC}"
            fi
        else
            echo -e "${RED}✗ Migration failed!${NC}"
            exit 1
        fi
        ;;

    3)
        echo -e "${YELLOW}Running fresh migration + full seed (with dummy data)...${NC}"
        php artisan migrate:fresh --seed

        if [ $? -eq 0 ]; then
            echo -e "${GREEN}✓ Migration and seeding completed successfully!${NC}"
            echo ""
            echo "Default admin account:"
            echo "  Username: admin"
            echo "  Password: password"
            echo ""
            echo -e "${RED}IMPORTANT: This includes dummy data. Not recommended for production!${NC}"
        else
            echo -e "${RED}✗ Migration failed!${NC}"
            exit 1
        fi
        ;;

    4)
        echo -e "${YELLOW}Checking migration status...${NC}"
        php artisan migrate:status
        echo ""
        echo -e "${YELLOW}Checking database connection...${NC}"
        php artisan tinker --execute="echo DB::connection()->getDatabaseName(); echo '\n';"
        ;;

    5)
        echo "Exiting..."
        exit 0
        ;;

    *)
        echo -e "${RED}Invalid choice!${NC}"
        exit 1
        ;;
esac

echo ""
echo "================================================="
echo -e "${YELLOW}Post-Migration Steps:${NC}"
echo "================================================="
echo "1. Clear and cache config:"
echo "   php artisan optimize:clear"
echo "   php artisan config:cache"
echo "   php artisan route:cache"
echo "   php artisan view:cache"
echo ""
echo "2. Change admin password (via tinker):"
echo "   php artisan tinker"
echo "   >>> \$user = App\Models\User::where('username', 'admin')->first();"
echo "   >>> \$user->password = bcrypt('new_secure_password');"
echo "   >>> \$user->save();"
echo "   >>> exit"
echo ""
echo "3. Test login in browser"
echo ""
echo "================================================="
echo -e "${GREEN}Done!${NC}"
echo "================================================="
