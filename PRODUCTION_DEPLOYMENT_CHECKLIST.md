# Production Deployment Checklist - MTsN 2 Absensi v1.0.1

## Pre-deployment Checks
- [ ] Verify server meets Laravel 12 requirements
- [ ] Check PHP version >= 8.2
- [ ] Confirm all required PHP extensions are installed
- [ ] Verify database connectivity
- [ ] Check available disk space
- [ ] Ensure proper file permissions (755 for dirs, 644 for files)
- [ ] Backup existing database and files

## Environment Configuration
- [ ] Set APP_ENV=production in .env
- [ ] Set APP_DEBUG=false in .env
- [ ] Configure correct database credentials
- [ ] Set proper APP_URL
- [ ] Configure mail settings if needed
- [ ] Set QUEUE_CONNECTION=database
- [ ] Configure CACHE_STORE=database
- [ ] Set SESSION_DRIVER=database

## Asset Compilation
- [ ] Run `npm run build` to compile frontend assets
- [ ] Verify build/assets directory contains compiled files
- [ ] Check that build/manifest.json is generated correctly
- [ ] Confirm CSS and JS files are minified

## Dependency Management
- [ ] Run `composer install --no-dev --optimize-autoloader`
- [ ] Run `composer dump-autoload --optimize`
- [ ] Remove unused packages with `composer remove`

## Database Operations
- [ ] Run `php artisan migrate --force`
- [ ] Seed initial data if needed
- [ ] Clear and rebuild caches

## Caching Optimization
- [ ] Run `php artisan config:cache`
- [ ] Run `php artisan route:cache`
- [ ] Run `php artisan view:cache`
- [ ] Run `php artisan event:cache`

## Storage Configuration
- [ ] Create storage symlink with `php artisan storage:link`
- [ ] Check that public/storage points to storage/app/public
- [ ] Verify uploaded files are accessible

## Security Measures
- [ ] Generate new APP_KEY if needed
- [ ] Remove debug information from production
- [ ] Disable development tools
- [ ] Set proper file permissions
- [ ] Secure .env file (not accessible via web)

## Testing
- [ ] Test login functionality
- [ ] Verify attendance recording works
- [ ] Check location validation and geofence
- [ ] Test photo upload and storage
- [ ] Verify reporting functionality
- [ ] Check admin panel access
- [ ] Test employee dashboard
- [ ] Test student attendance

## Post-deployment
- [ ] Monitor application logs
- [ ] Verify no errors in laravel.log
- [ ] Check server error logs
- [ ] Monitor performance
- [ ] Update documentation
- [ ] Notify stakeholders of deployment
- [ ] Clean up deployment files

## Rollback Plan
- [ ] Have database backup available
- [ ] Keep previous version files
- [ ] Document rollback procedure
- [ ] Test rollback process

## Notes
- Do not use symlinks in production environment
- Do not run Vite development server in production
- Ensure all assets are pre-compiled before deployment
- Test thoroughly before making live to users