<?php
/**
 * WordPress Debug Configuration
 * Temporary file to enable debugging for error identification
 * This should be added to wp-config.php temporarily
 */

// Enable WordPress debugging
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
define('SCRIPT_DEBUG', true);

// Enable error logging to a specific file
ini_set('log_errors', 1);
ini_set('error_log', WP_CONTENT_DIR . '/debug.log');

// Show all errors for debugging
error_reporting(E_ALL);

// Memory limit increase for debugging
ini_set('memory_limit', '256M');

/**
 * Enable WordPress debug bar (if plugin is available)
 */
define('SAVEQUERIES', true);

/**
 * SkyLearn Billing Pro specific debugging
 */
define('SKYLEARN_BILLING_PRO_DEBUG', true);