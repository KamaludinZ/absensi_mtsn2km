# Release Notes - Version 1.0.3

**Release Date:** 2025-10-25

## Security Update

### Fixed Security Vulnerability
- **Critical Security Fix**: Fixed unauthorized access to selfie photos via direct URL
- All `/selfies/*` routes now require proper authentication
- Enhanced access control with role-based permissions

### Changes Included

#### Security Improvements
- Added authentication middleware to selfie photo access routes
- Removed direct file access exceptions from `.htaccess` files
- Enhanced `SelfieController` with proper permission checks
  - Admin/Monitoring users can view all selfies
  - Employees can only view their own selfies
  - Unauthorized users are blocked with 403 error

#### Files Modified
- `routes/web.php` - Added secure selfie route with auth middleware
- `public/.htaccess` - Removed `/selfies` exception
- `public/.htaccess.FIXED` - Removed `/selfies` exception
- `VERSION` - Updated to 1.0.3
- `CHANGELOG.md` - Added v1.0.3 entry

### Update Instructions

1. **Backup**: Always backup your current installation and database before updating
2. **Upload**: Upload `update-1.0.3.zip` to your server
3. **Extract**: Extract the update package to your application directory
4. **Run Commands**:
   ```bash
   php artisan config:cache
   php artisan route:clear
   php artisan view:clear
   ```
5. **Test**: Verify the application is working correctly
6. **Verify**: Check that direct URL access to selfies now requires login

### Security Verification

To verify the fix is working:
1. Try accessing a selfie URL directly without logging in (e.g., `https://yourdomain.com/selfies/employee/employee_1_in_123.jpg`)
2. You should be redirected to the login page
3. After login, you should be able to access selfies based on your role permissions

### Version Information
- **Previous Version**: 1.0.2
- **Current Version**: 1.0.3
- **Update Type**: Security Patch
- **Compatibility**: Backward compatible, no breaking changes

### Support
If you encounter any issues with this update, please contact:
- Email: dev@mtsn2kotamalang.sch.id
- Issue Tracker: [Your issue tracking URL]