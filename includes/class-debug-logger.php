<?php
/**
 * Debug Logger for SkyLearn Billing Pro
 * 
 * @package SkyLearnBillingPro
 * @author Ferdous Khalifa
 * @copyright 2024 Skyian LLC
 * @license GPLv3
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Debug Logger Class
 */
class SkyLearn_Billing_Pro_Debug_Logger {
    
    /**
     * Log file path
     */
    private static $log_file = null;
    
    /**
     * Initialize the logger
     */
    public static function init() {
        if (defined('WP_CONTENT_DIR')) {
            self::$log_file = WP_CONTENT_DIR . '/skylearn-billing-debug.log';
        } else {
            self::$log_file = '/tmp/skylearn-billing-debug.log';
        }
    }
    
    /**
     * Log a message
     * 
     * @param string $message The message to log
     * @param string $level The log level (INFO, WARNING, ERROR)
     * @param string $context Additional context
     */
    public static function log($message, $level = 'INFO', $context = '') {
        if (!self::$log_file) {
            self::init();
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $memory_usage = round(memory_get_usage() / 1024 / 1024, 2) . 'MB';
        $peak_memory = round(memory_get_peak_usage() / 1024 / 1024, 2) . 'MB';
        
        $log_entry = sprintf(
            "[%s] [%s] [Memory: %s | Peak: %s] %s%s\n",
            $timestamp,
            $level,
            $memory_usage,
            $peak_memory,
            $message,
            $context ? " | Context: $context" : ''
        );
        
        // Write to file
        file_put_contents(self::$log_file, $log_entry, FILE_APPEND | LOCK_EX);
        
        // Also log to WordPress error log if available
        if (function_exists('error_log')) {
            error_log("SkyLearn Billing Pro [$level]: $message" . ($context ? " | Context: $context" : ''));
        }
    }
    
    /**
     * Log an error
     */
    public static function error($message, $context = '') {
        self::log($message, 'ERROR', $context);
    }
    
    /**
     * Log a warning
     */
    public static function warning($message, $context = '') {
        self::log($message, 'WARNING', $context);
    }
    
    /**
     * Log info
     */
    public static function info($message, $context = '') {
        self::log($message, 'INFO', $context);
    }
    
    /**
     * Log function call start
     */
    public static function function_start($function_name) {
        self::log("Starting function: $function_name", 'DEBUG');
    }
    
    /**
     * Log function call end
     */
    public static function function_end($function_name, $success = true) {
        $status = $success ? 'completed successfully' : 'failed';
        self::log("Function $function_name $status", 'DEBUG');
    }
    
    /**
     * Log exception
     */
    public static function exception($exception, $context = '') {
        $message = sprintf(
            "Exception: %s in %s:%d | Stack trace: %s",
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getTraceAsString()
        );
        self::error($message, $context);
    }
    
    /**
     * Clear log file
     */
    public static function clear_log() {
        if (self::$log_file && file_exists(self::$log_file)) {
            file_put_contents(self::$log_file, '');
        }
    }
}

// Initialize the logger
SkyLearn_Billing_Pro_Debug_Logger::init();