# MTsN 2 Absensi - Production Release v1.0.1

## Release Date
October 24, 2025

## Features
- Enhanced geofence validation with improved accuracy handling
- Better location tolerance for GPS inaccuracies
- Improved attendance reporting with coordinate display
- Production-ready deployment without symlinks
- Optimized asset compilation without Vite in production

## Improvements
- More flexible geofence validation with 75% radius buffer
- Enhanced location accuracy handling with 200m cap
- Better error handling and user feedback
- Improved database performance with proper indexing
- Optimized frontend asset loading

## Bug Fixes
- Fixed coordinate display in attendance reports
- Resolved location validation issues for edge cases
- Fixed timezone handling in attendance timestamps
- Corrected database migration conflicts

## Deployment Instructions

### Prerequisites
- PHP 8.2+
- PostgreSQL 13+
- Node.js 18+
- Composer
- NPM

### Steps
1. Clone repository or download release package
2. Run `composer install --no-dev --optimize-autoloader`
3. Run `npm install`
4. Run `npm run build`
5. Configure .env file with production settings
6. Run `php artisan migrate --force`
7. Run `php artisan storage:link`
8. Run optimization commands:
   - `php artisan config:cache`
   - `php artisan route:cache`
   - `php artisan view:cache`
9. Set proper file permissions
10. Test application functionality

### Important Notes
- Do not use symlinks in production environment
- Do not run Vite development server in production
- Ensure all assets are pre-compiled before deployment
- Set APP_ENV=production and APP_DEBUG=false

## Changelog
- v1.0.1 (Current) - Production release with geofence improvements
- v1.0.0 - Initial stable release