<?php
/**
 * Application Configuration for ClassTrack
 * Contains deployment-specific settings
 */

require_once 'env.php';

// Base URL Configuration
// Set this to your actual domain for production
// For local development, you can leave this as is or modify as needed
define('CLASSTRACK_BASE_URL', envValue('APP_URL', 'http://localhost/classtrack'));

// Alternative: Auto-detect base URL (uncomment to use)
/*
define('CLASSTRACK_BASE_URL', 
    (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') .
    '://' . $_SERVER['HTTP_HOST'] . 
    rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\')
);
*/

// Environment Settings
define('CLASSTRACK_ENV', envValue('APP_ENV', 'development')); // Change to 'production' for live deployment

// Email Settings
define('CLASSTRACK_ADMIN_EMAIL', envValue('ADMIN_EMAIL', 'admin@classtrack.system'));
define('CLASSTRACK_SUPPORT_EMAIL', envValue('SUPPORT_EMAIL', 'support@classtrack.system'));

// Application Settings
define('CLASSTRACK_APP_NAME', 'ClassTrack');
define('CLASSTRACK_APP_VERSION', '1.0.0');

?>
