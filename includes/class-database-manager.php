<?php
/**
 * Database Manager for SkyLearn Billing Pro
 *
 * Handles custom table creation and schema management
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
 * Database Manager class
 */
class SkyLearn_Billing_Pro_Database_Manager {
    
    /**
     * Database version for schema upgrades
     */
    const DB_VERSION = '1.0.0';
    
    /**
     * Initialize database manager
     */
    public function __construct() {
        // Hook into plugin activation for table creation
        add_action('skylearn_billing_pro_activate', array($this, 'create_tables'));
        
        // Check for database updates on admin init
        add_action('admin_init', array($this, 'maybe_upgrade_database'));
    }
    
    /**
     * Create all custom tables
     */
    public function create_tables() {
        global $wpdb;
        
        // Include WordPress upgrade functions
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        try {
            $this->create_course_mappings_table();
            $this->create_enrollment_log_table();
            
            // Update database version
            update_option('skylearn_billing_pro_db_version', self::DB_VERSION);
            
            error_log('SkyLearn Billing Pro: Database tables created successfully');
            
        } catch (Exception $e) {
            error_log('SkyLearn Billing Pro: Failed to create database tables - ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Create course mappings table
     */
    public function create_course_mappings_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'skylearn_course_mappings';
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            product_id varchar(100) NOT NULL,
            course_id bigint(20) unsigned NOT NULL,
            trigger_type varchar(20) NOT NULL DEFAULT 'payment',
            status varchar(20) NOT NULL DEFAULT 'active',
            additional_settings longtext DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY product_id (product_id),
            KEY course_id (course_id),
            KEY trigger_type (trigger_type),
            KEY status (status),
            KEY created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        $result = dbDelta($sql);
        
        if ($wpdb->last_error) {
            throw new Exception('Failed to create course mappings table: ' . $wpdb->last_error);
        }
        
        return $result;
    }
    
    /**
     * Create enrollment log table
     */
    public function create_enrollment_log_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'skylearn_enrollment_log';
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            product_id varchar(100) NOT NULL,
            user_id bigint(20) unsigned NOT NULL,
            course_id bigint(20) unsigned NOT NULL,
            trigger_type varchar(20) NOT NULL,
            status varchar(20) NOT NULL,
            user_email varchar(100) DEFAULT NULL,
            course_title varchar(255) DEFAULT NULL,
            error_message text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY product_id (product_id),
            KEY user_id (user_id),
            KEY course_id (course_id),
            KEY status (status),
            KEY created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        $result = dbDelta($sql);
        
        if ($wpdb->last_error) {
            throw new Exception('Failed to create enrollment log table: ' . $wpdb->last_error);
        }
        
        return $result;
    }
    
    /**
     * Check if database needs upgrade
     */
    public function maybe_upgrade_database() {
        $current_version = get_option('skylearn_billing_pro_db_version', '0.0.0');
        
        if (version_compare($current_version, self::DB_VERSION, '<')) {
            $this->upgrade_database($current_version);
        }
    }
    
    /**
     * Upgrade database from older versions
     *
     * @param string $from_version Current database version
     */
    public function upgrade_database($from_version) {
        try {
            // For now, just recreate tables
            // In future versions, we could add specific upgrade paths
            $this->create_tables();
            
            error_log("SkyLearn Billing Pro: Database upgraded from $from_version to " . self::DB_VERSION);
            
        } catch (Exception $e) {
            error_log('SkyLearn Billing Pro: Database upgrade failed - ' . $e->getMessage());
        }
    }
    
    /**
     * Get course mappings table name
     *
     * @return string Table name with prefix
     */
    public function get_course_mappings_table() {
        global $wpdb;
        return $wpdb->prefix . 'skylearn_course_mappings';
    }
    
    /**
     * Get enrollment log table name
     *
     * @return string Table name with prefix
     */
    public function get_enrollment_log_table() {
        global $wpdb;
        return $wpdb->prefix . 'skylearn_enrollment_log';
    }
    
    /**
     * Check if tables exist
     *
     * @return bool True if all tables exist
     */
    public function tables_exist() {
        global $wpdb;
        
        $tables = array(
            $this->get_course_mappings_table(),
            $this->get_enrollment_log_table()
        );
        
        foreach ($tables as $table) {
            $result = $wpdb->get_var($wpdb->prepare(
                "SHOW TABLES LIKE %s",
                $table
            ));
            
            if ($result !== $table) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Remove all custom tables (for uninstall)
     */
    public function drop_tables() {
        global $wpdb;
        
        $tables = array(
            $this->get_course_mappings_table(),
            $this->get_enrollment_log_table()
        );
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }
        
        // Remove database version option
        delete_option('skylearn_billing_pro_db_version');
    }
}

/**
 * Get the Database Manager instance
 *
 * @return SkyLearn_Billing_Pro_Database_Manager
 */
function skylearn_billing_pro_database_manager() {
    static $instance = null;
    
    if ($instance === null) {
        $instance = new SkyLearn_Billing_Pro_Database_Manager();
    }
    
    return $instance;
}