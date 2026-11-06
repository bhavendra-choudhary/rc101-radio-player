================================================================================
                          LARAVEL PROJECT DEPLOYMENT GUIDE
================================================================================

This guide provides step-by-step instructions for deploying this Laravel application
to your web hosting server using the traditional file upload method.

================================================================================
PROJECT OVERVIEW
================================================================================

This is a Laravel 11.x PHP application with the following features:
- Built on Laravel framework (PHP 8.2+ required)
- Uses SQLite database (database/database.sqlite - 512KB)
- Includes frontend assets (CSS, JS, images, HTML files)
- Has proper .htaccess configuration for Apache servers
- Ready for production deployment

Key files included:
- SQLite database: database/database.sqlite (512KB)
- Frontend assets: public/css/app.css, public/js/app.js, public/js/contact-form.js
- HTML files: admin.html, admin-access-setup.html, test-concerts-simple.html, test-js-simple.html
- Images: Multiple PNG files and hero.jpg
- Configuration: .env, .htaccess files
- Dependencies: Complete vendor directory (8,394 items)

================================================================================
PREREQUISITES
================================================================================

Before deploying, ensure your hosting meets these requirements:
- PHP 8.2 or higher
- Apache web server (with mod_rewrite enabled)
- MySQL support (if using MySQL instead of SQLite)
- File upload capabilities (FTP, SFTP, or cPanel File Manager)
- Ability to set file permissions (chmod)

================================================================================
STEP 1: PREPARE FOR DEPLOYMENT
================================================================================

1.1. Backup Your Current Site (if applicable)
   - If replacing an existing site, backup all files and database
   - Download current files to your local computer
   - Export existing database (if applicable)

1.2. Gather Required Information
   - Your domain name (e.g., example.com)
   - Database credentials (if using MySQL):
     * Database name
     * Database username
     * Database password
     * Database host (usually localhost)
   - FTP/SFTP credentials or cPanel access

================================================================================
STEP 2: UPLOAD FILES TO SERVER
================================================================================

2.1. Upload All Project Files
   - Connect to your server via FTP, SFTP, or cPanel File Manager
   - Navigate to your website's root directory (usually public_html or www)
   - Upload ALL files and folders from the project directory
   - Important: Make sure to upload the following key items:
     * .env file
     * .htaccess files (both root and public/)
     * vendor/ directory (contains all PHP dependencies)
     * storage/ directory
     * database/ directory (including database.sqlite if using SQLite)

2.2. File Structure After Upload
   Your server should have this exact structure:
   ```
   client_ready/
   ├── .env
   ├── .htaccess
   ├── README.txt
   ├── app/ (42 items)
   ├── artisan
   ├── bootstrap/ (3 items)
   ├── cache/ (empty)
   ├── cgi-bin/ (empty)
   ├── composer.json
   ├── config/ (10 items)
   ├── database/
   │   ├── .gitignore
   │   ├── database.sqlite (512KB)
   │   ├── factories/ (1 item)
   │   ├── migrations/ (10 items)
   │   └── seeders/ (3 items)
   ├── debug-response.php
   ├── package.json
   ├── postcss.config.js
   ├── public/
   │   ├── .htaccess
   │   ├── 1 (1).png
   │   ├── 1 (2).png
   │   ├── 1 (3).png
   │   ├── Landscape_Logo.png
   │   ├── admin-access-setup.html
   │   ├── admin.html
   │   ├── build/ (3 items)
   │   ├── css/
   │   │   └── app.css
   │   ├── default-thumbnail-template.html
   │   ├── default-thumbnail.svg
   │   ├── favicon.ico
   │   ├── hero.jpg
   │   ├── images/ (empty)
   │   ├── index.php
   │   ├── js/
   │   │   ├── app.js
   │   │   └── contact-form.js
   │   ├── mix-manifest.json
   │   ├── robots.txt
   │   └── test-metadata.php
   ├── resources/ (67 items)
   ├── routes/ (5 items)
   ├── storage/ (10 items)
   ├── tailwind.config.js
   ├── test-api-simple.php
   ├── test-compact.php
   ├── test-concerts-simple.html
   ├── test-coordinates.php
   ├── test-debug-route.php
   ├── test-geocoding.php
   ├── test-integrated.php
   ├── test-js-simple.html
   ├── test-separate.php
   ├── test-ticketmaster.php
   ├── test-trending.php
   ├── vendor/ (8,394 items)
   └── vite.config.js
   ```

================================================================================
STEP 3: CONFIGURE ENVIRONMENT FILE (.env)
================================================================================

3.1. Locate and Edit the .env File
   - Find the .env file in your website root directory
   - Right-click and select "Edit" or download it to edit locally
   - Update the following settings:

3.2. Basic Application Settings
   ```
   APP_NAME=YourAppName
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://your-domain.com
   ```

3.3. Database Configuration (SQLite Only)

   This project uses SQLite exclusively. The database is already configured:
   ```
   DB_CONNECTION=sqlite
   DB_DATABASE=database/database.sqlite
   ```
   - SQLite is pre-configured and ready to use
   - The database file (database.sqlite) is already included (512KB)
   - No additional database setup required
   - All tables and data are already present

   Note: This project is designed to work with SQLite only. MySQL configuration is not recommended.

3.4. Other Important Settings
   ```
   LOG_CHANNEL=stack
   LOG_STACK=single
   LOG_LEVEL=error
   
   SESSION_DRIVER=database
   SESSION_LIFETIME=120
   
   CACHE_STORE=database
   ```

3.5. Save and Upload
   - Save the changes to .env file
   - If edited locally, re-upload to server
   - Ensure the file is in the root directory

================================================================================
STEP 4: DATABASE SETUP
================================================================================

4.1. Database Setup (SQLite)
   - No additional setup required!
   - The SQLite database file (database/database.sqlite) is already included
   - File size: 512KB
   - Contains all necessary tables and data
   - Ready to use immediately
   - No need to create databases or import SQL files
   - Skip directly to Step 5

   Note: The SQLite database includes:
   - All Laravel system tables
   - User authentication tables
   - Application-specific data
   - Cache and session data
   - Everything needed for the application to run

================================================================================
STEP 5: SET FILE PERMISSIONS
================================================================================

5.1. Required Permissions
   Set the following permissions using your FTP client or cPanel File Manager:

   5.1.1. Storage Directory
     - storage/ directory: 755 (drwxr-xr-x)
     - storage/logs/: 755
     - storage/framework/: 755
     - storage/framework/cache/: 755
     - storage/framework/sessions/: 755
     - storage/framework/views/: 755
     - storage/app/: 755
     - storage/app/public/: 755

   5.1.2. Bootstrap Cache Directory
     - bootstrap/cache/: 755

   5.1.3. Vendor Directory
     - vendor/: 755

5.2. How to Set Permissions
   - In cPanel File Manager: Right-click > Change Permissions
   - In FTP client: Right-click > Properties/Permissions
   - If using SSH: chmod -R 755 storage bootstrap/cache

================================================================================
STEP 6: POST-DEPLOYMENT TASKS
================================================================================

6.1. Clear Application Cache (Recommended)
   - If you have SSH access, run these commands from project root:
     ```
     php artisan config:clear
     php artisan cache:clear
     php artisan view:clear
     php artisan route:clear
     ```
   - If no SSH access, you can usually skip this step or contact your host

6.2. Test the Website
   - Open your browser and navigate to your domain
   - Check if the site loads properly
   - Test all major features and pages

6.3. Check Error Logs (if issues occur)
   - Check storage/logs/laravel.log for any errors
   - Check your hosting error logs
   - Common issues and solutions:
     * 500 Error: Check file permissions and .env settings
     * 404 Error: Check .htaccess and mod_rewrite
     * Database Error: Verify database credentials and connection

================================================================================
STEP 7: VERIFICATION CHECKLIST
================================================================================

Verify the following items are working correctly:

[ ] Website loads without errors
[ ] Home page displays correctly
[ ] Admin pages work (admin.html, admin-access-setup.html)
[ ] Test pages load (test-concerts-simple.html, test-js-simple.html)
[ ] Contact forms work (contact-form.js)
[ ] Images load properly (hero.jpg, PNG files)
[ ] CSS and JS files load (app.css, app.js)
[ ] SQLite database connects successfully
[ ] No PHP errors in logs
[ ] No database connection errors
[ ] All test files are accessible (test-api-simple.php, etc.)

================================================================================
TROUBLESHOOTING COMMON ISSUES
================================================================================

7.1. 500 Internal Server Error
   - Check file permissions (should be 755 for directories, 644 for files)
   - Verify .env file settings
   - Check if PHP version is 8.2+
   - Look at storage/logs/laravel.log for specific errors

7.2. 404 Not Found Errors
   - Ensure mod_rewrite is enabled on your server
   - Check .htaccess files are present and properly configured
   - Verify public/.htaccess exists

7.3. Database Connection Errors
   - Verify database credentials in .env file
   - Check if database server is running
   - Ensure database user has proper privileges
   - Verify database name exists

7.4. White Screen or Blank Page
   - Check PHP error reporting
   - Verify file permissions
   - Check if vendor directory is complete
   - Look at server error logs

7.5. Asset Loading Issues (CSS, JS, Images)
   - Check if files are in public/ directory
   - Verify file paths in HTML
   - Check file permissions

================================================================================
MAINTENANCE TIPS
================================================================================

- Regularly backup your database and files
- Keep Laravel and dependencies updated (check composer.json)
- Monitor error logs for issues
- Clear cache periodically if you make configuration changes
- Update .env file if you change hosting or domain

================================================================================
CONTACT INFORMATION
================================================================================

If you encounter issues during deployment:
1. Check this guide for troubleshooting steps
2. Review Laravel documentation at https://laravel.com/docs
3. Contact your hosting provider for server-specific issues
4. For application-specific issues, contact the developer

================================================================================
DEPLOYMENT COMPLETE!
================================================================================

Once you've completed all steps and verified everything works, your Laravel
application should be successfully deployed and running on your server.

For future updates, simply upload the changed files and clear the cache if needed.

================================================================================
