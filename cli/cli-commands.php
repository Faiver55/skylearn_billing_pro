<?php
/**
 * WP-CLI commands for Skylearn Billing Pro automation and testing
 *
 * @package SkyLearnBillingPro
 * @subpackage CLI
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WP_CLI')) {
    return;
}

/**
 * Skylearn Billing Pro CLI Commands
 */
class SkyLearn_Billing_Pro_CLI {
    
    /**
     * Run all unit tests
     *
     * ## OPTIONS
     *
     * [--coverage]
     * : Generate coverage report
     *
     * [--verbose]
     * : Show verbose output
     *
     * ## EXAMPLES
     *
     *     wp skylearn test:unit
     *     wp skylearn test:unit --coverage
     *     wp skylearn test:unit --verbose
     *
     * @param array $args
     * @param array $assoc_args
     */
    public function test_unit($args, $assoc_args) {
        $coverage = WP_CLI\Utils\get_flag_value($assoc_args, 'coverage', false);
        $verbose = WP_CLI\Utils\get_flag_value($assoc_args, 'verbose', false);
        
        WP_CLI::line('Running Skylearn Billing Pro unit tests...');
        
        $plugin_dir = SKYLEARN_BILLING_PRO_PLUGIN_DIR;
        $phpunit_config = $plugin_dir . 'phpunit.xml';
        
        if (!file_exists($phpunit_config)) {
            WP_CLI::error('PHPUnit configuration file not found.');
        }
        
        $command = "cd {$plugin_dir} && phpunit";
        
        if ($coverage) {
            $command .= ' --coverage-html tests/coverage --coverage-text';
        }
        
        if ($verbose) {
            $command .= ' --verbose';
        }
        
        $command .= ' tests/unit/';
        
        $result = shell_exec($command);
        
        if ($result) {
            WP_CLI::line($result);
            WP_CLI::success('Unit tests completed.');
        } else {
            WP_CLI::error('Failed to run unit tests.');
        }
    }
    
    /**
     * Run integration tests
     *
     * ## OPTIONS
     *
     * [--test=<test_name>]
     * : Run specific integration test
     *
     * ## EXAMPLES
     *
     *     wp skylearn test:integration
     *     wp skylearn test:integration --test=payment
     *
     * @param array $args
     * @param array $assoc_args
     */
    public function test_integration($args, $assoc_args) {
        $test_name = WP_CLI\Utils\get_flag_value($assoc_args, 'test', '');
        
        WP_CLI::line('Running Skylearn Billing Pro integration tests...');
        
        $plugin_dir = SKYLEARN_BILLING_PRO_PLUGIN_DIR;
        $command = "cd {$plugin_dir} && phpunit tests/integration/";
        
        if ($test_name) {
            $command .= "test-{$test_name}-integration.php";
        }
        
        $result = shell_exec($command);
        
        if ($result) {
            WP_CLI::line($result);
            WP_CLI::success('Integration tests completed.');
        } else {
            WP_CLI::error('Failed to run integration tests.');
        }
    }
    
    /**
     * Generate test data for development and testing
     *
     * ## OPTIONS
     *
     * [--users=<count>]
     * : Number of test users to create
     * ---
     * default: 10
     * ---
     *
     * [--orders=<count>]
     * : Number of test orders to create
     * ---
     * default: 50
     * ---
     *
     * [--subscriptions=<count>]
     * : Number of test subscriptions to create
     * ---
     * default: 20
     * ---
     *
     * ## EXAMPLES
     *
     *     wp skylearn generate:testdata
     *     wp skylearn generate:testdata --users=50 --orders=100
     *
     * @param array $args
     * @param array $assoc_args
     */
    public function generate_testdata($args, $assoc_args) {
        $user_count = intval(WP_CLI\Utils\get_flag_value($assoc_args, 'users', 10));
        $order_count = intval(WP_CLI\Utils\get_flag_value($assoc_args, 'orders', 50));
        $subscription_count = intval(WP_CLI\Utils\get_flag_value($assoc_args, 'subscriptions', 20));
        
        WP_CLI::line('Generating test data for Skylearn Billing Pro...');
        
        // Generate test users
        WP_CLI::line("Creating {$user_count} test users...");
        $progress = WP_CLI\Utils\make_progress_bar('Creating users', $user_count);
        
        for ($i = 1; $i <= $user_count; $i++) {
            $user_data = array(
                'user_login' => "testuser{$i}",
                'user_email' => "testuser{$i}@example.com",
                'user_pass' => wp_generate_password(),
                'first_name' => "Test",
                'last_name' => "User {$i}",
                'role' => 'subscriber'
            );
            
            $user_id = wp_insert_user($user_data);
            
            if (!is_wp_error($user_id)) {
                // Add some test metadata
                update_user_meta($user_id, 'billing_first_name', $user_data['first_name']);
                update_user_meta($user_id, 'billing_last_name', $user_data['last_name']);
                update_user_meta($user_id, 'billing_email', $user_data['user_email']);
            }
            
            $progress->tick();
        }
        $progress->finish();
        
        // Generate test orders
        WP_CLI::line("Creating {$order_count} test orders...");
        $progress = WP_CLI\Utils\make_progress_bar('Creating orders', $order_count);
        
        for ($i = 1; $i <= $order_count; $i++) {
            $order_data = array(
                'order_id' => 'test_order_' . $i,
                'customer_email' => "testuser" . rand(1, $user_count) . "@example.com",
                'amount' => rand(1000, 50000) / 100, // $10.00 to $500.00
                'currency' => 'USD',
                'status' => rand(0, 10) > 2 ? 'completed' : 'failed', // 80% success rate
                'gateway' => rand(0, 1) ? 'stripe' : 'paddle',
                'product_id' => 'test_product_' . rand(1, 5),
                'created_at' => date('Y-m-d H:i:s', time() - rand(0, 30 * 24 * 3600)) // Random time in last 30 days
            );
            
            // Store order data in options for testing
            $orders = get_option('skylearn_test_orders', array());
            $orders[] = $order_data;
            update_option('skylearn_test_orders', $orders);
            
            $progress->tick();
        }
        $progress->finish();
        
        // Generate test subscriptions
        WP_CLI::line("Creating {$subscription_count} test subscriptions...");
        $progress = WP_CLI\Utils\make_progress_bar('Creating subscriptions', $subscription_count);
        
        $subscription_statuses = array('active', 'canceled', 'paused', 'trialing');
        $subscription_plans = array('monthly_basic', 'monthly_pro', 'yearly_basic', 'yearly_pro');
        
        for ($i = 1; $i <= $subscription_count; $i++) {
            $subscription_data = array(
                'subscription_id' => 'test_sub_' . $i,
                'customer_email' => "testuser" . rand(1, $user_count) . "@example.com",
                'plan_id' => $subscription_plans[array_rand($subscription_plans)],
                'status' => $subscription_statuses[array_rand($subscription_statuses)],
                'amount' => rand(999, 9999) / 100, // $9.99 to $99.99
                'currency' => 'USD',
                'current_period_start' => date('Y-m-d H:i:s', time() - rand(0, 30 * 24 * 3600)),
                'current_period_end' => date('Y-m-d H:i:s', time() + rand(0, 30 * 24 * 3600)),
                'created_at' => date('Y-m-d H:i:s', time() - rand(0, 90 * 24 * 3600)) // Random time in last 90 days
            );
            
            // Store subscription data in options for testing
            $subscriptions = get_option('skylearn_test_subscriptions', array());
            $subscriptions[] = $subscription_data;
            update_option('skylearn_test_subscriptions', $subscriptions);
            
            $progress->tick();
        }
        $progress->finish();
        
        WP_CLI::success("Test data generated successfully!");
        WP_CLI::line("Created:");
        WP_CLI::line("- {$user_count} test users");
        WP_CLI::line("- {$order_count} test orders");
        WP_CLI::line("- {$subscription_count} test subscriptions");
    }
    
    /**
     * Clean up test data
     *
     * ## OPTIONS
     *
     * [--confirm]
     * : Confirm deletion of test data
     *
     * ## EXAMPLES
     *
     *     wp skylearn cleanup:testdata --confirm
     *
     * @param array $args
     * @param array $assoc_args
     */
    public function cleanup_testdata($args, $assoc_args) {
        $confirm = WP_CLI\Utils\get_flag_value($assoc_args, 'confirm', false);
        
        if (!$confirm) {
            WP_CLI::error('Please use --confirm flag to confirm test data cleanup.');
        }
        
        WP_CLI::line('Cleaning up test data...');
        
        // Remove test users
        $users_query = new WP_User_Query(array(
            'search' => 'testuser*',
            'search_columns' => array('user_login'),
            'fields' => 'ID'
        ));
        
        $test_users = $users_query->get_results();
        
        if (!empty($test_users)) {
            WP_CLI::line('Removing ' . count($test_users) . ' test users...');
            
            foreach ($test_users as $user_id) {
                wp_delete_user($user_id);
            }
        }
        
        // Remove test data from options
        delete_option('skylearn_test_orders');
        delete_option('skylearn_test_subscriptions');
        
        WP_CLI::success('Test data cleanup completed.');
    }
    
    /**
     * Run performance tests
     *
     * ## OPTIONS
     *
     * [--iterations=<count>]
     * : Number of test iterations
     * ---
     * default: 100
     * ---
     *
     * ## EXAMPLES
     *
     *     wp skylearn test:performance
     *     wp skylearn test:performance --iterations=500
     *
     * @param array $args
     * @param array $assoc_args
     */
    public function test_performance($args, $assoc_args) {
        $iterations = intval(WP_CLI\Utils\get_flag_value($assoc_args, 'iterations', 100));
        
        WP_CLI::line('Running performance tests...');
        
        $tests = array(
            'LMS Manager' => function() {
                if (function_exists('skylearn_billing_pro_lms_manager')) {
                    $lms_manager = skylearn_billing_pro_lms_manager();
                    $lms_manager->get_supported_lms();
                    $lms_manager->detect_lms_plugins();
                }
            },
            'Payment Manager' => function() {
                if (function_exists('skylearn_billing_pro_payment_manager')) {
                    $payment_manager = skylearn_billing_pro_payment_manager();
                    $payment_manager->get_supported_gateways();
                    $payment_manager->get_gateway_status();
                }
            },
            'Webhook Handler' => function() {
                if (function_exists('skylearn_billing_pro_webhook_handler')) {
                    $webhook_handler = skylearn_billing_pro_webhook_handler();
                    $webhook_handler->get_webhook_status();
                }
            }
        );
        
        $results = array();
        
        foreach ($tests as $test_name => $test_function) {
            WP_CLI::line("Testing {$test_name}...");
            
            $start_time = microtime(true);
            $start_memory = memory_get_usage(true);
            
            for ($i = 0; $i < $iterations; $i++) {
                try {
                    $test_function();
                } catch (Exception $e) {
                    WP_CLI::warning("Error in {$test_name}: " . $e->getMessage());
                }
            }
            
            $end_time = microtime(true);
            $end_memory = memory_get_usage(true);
            
            $execution_time = ($end_time - $start_time) * 1000; // Convert to milliseconds
            $memory_usage = $end_memory - $start_memory;
            $avg_time = $execution_time / $iterations;
            
            $results[$test_name] = array(
                'total_time' => $execution_time,
                'average_time' => $avg_time,
                'memory_usage' => $memory_usage,
                'iterations' => $iterations
            );
        }
        
        // Display results
        WP_CLI::line('');
        WP_CLI::line('Performance Test Results:');
        WP_CLI::line('=========================');
        
        foreach ($results as $test_name => $result) {
            WP_CLI::line('');
            WP_CLI::line("{$test_name}:");
            WP_CLI::line("  Total Time: " . round($result['total_time'], 2) . "ms");
            WP_CLI::line("  Average Time: " . round($result['average_time'], 4) . "ms per iteration");
            WP_CLI::line("  Memory Usage: " . $this->format_bytes($result['memory_usage']));
            WP_CLI::line("  Iterations: {$result['iterations']}");
        }
        
        WP_CLI::success('Performance tests completed.');
    }
    
    /**
     * Check plugin health and configuration
     *
     * ## EXAMPLES
     *
     *     wp skylearn health:check
     *
     * @param array $args
     * @param array $assoc_args
     */
    public function health_check($args, $assoc_args) {
        WP_CLI::line('Checking Skylearn Billing Pro health...');
        
        $issues = array();
        $warnings = array();
        
        // Check WordPress version
        $wp_version = get_bloginfo('version');
        if (version_compare($wp_version, '5.0', '<')) {
            $issues[] = "WordPress version {$wp_version} is below minimum requirement (5.0)";
        }
        
        // Check PHP version
        $php_version = PHP_VERSION;
        if (version_compare($php_version, '7.4', '<')) {
            $issues[] = "PHP version {$php_version} is below minimum requirement (7.4)";
        }
        
        // Check required PHP extensions
        $required_extensions = array('curl', 'json', 'mbstring');
        foreach ($required_extensions as $extension) {
            if (!extension_loaded($extension)) {
                $issues[] = "Required PHP extension '{$extension}' is not loaded";
            }
        }
        
        // Check plugin configuration
        $options = get_option('skylearn_billing_pro_options', array());
        
        if (empty($options['general_settings']['company_email'])) {
            $warnings[] = 'Company email is not configured';
        }
        
        if (empty($options['lms_settings']['active_lms'])) {
            $warnings[] = 'No LMS is configured';
        }
        
        if (empty($options['webhook_settings']['secret'])) {
            $issues[] = 'Webhook secret is not configured';
        }
        
        // Check file permissions
        $upload_dir = wp_upload_dir();
        if (!is_writable($upload_dir['basedir'])) {
            $warnings[] = 'WordPress uploads directory is not writable';
        }
        
        // Check database connectivity
        global $wpdb;
        $db_check = $wpdb->get_var("SELECT 1");
        if ($db_check !== '1') {
            $issues[] = 'Database connectivity issue detected';
        }
        
        // Display results
        if (!empty($issues)) {
            WP_CLI::line('');
            WP_CLI::line('Critical Issues:');
            WP_CLI::line('================');
            foreach ($issues as $issue) {
                WP_CLI::error_multi_line(array("❌ {$issue}"));
            }
        }
        
        if (!empty($warnings)) {
            WP_CLI::line('');
            WP_CLI::line('Warnings:');
            WP_CLI::line('=========');
            foreach ($warnings as $warning) {
                WP_CLI::warning("⚠️  {$warning}");
            }
        }
        
        if (empty($issues) && empty($warnings)) {
            WP_CLI::success('✅ All health checks passed!');
        } elseif (empty($issues)) {
            WP_CLI::success('✅ No critical issues found. Please review warnings.');
        } else {
            WP_CLI::error('❌ Critical issues found. Please resolve them before using the plugin.');
        }
    }
    
    /**
     * Format bytes into human readable format
     *
     * @param int $size
     * @return string
     */
    private function format_bytes($size) {
        $units = array('B', 'KB', 'MB', 'GB');
        
        for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }
        
        return round($size, 2) . ' ' . $units[$i];
    }
}

// Register CLI commands
WP_CLI::add_command('skylearn test:unit', array('SkyLearn_Billing_Pro_CLI', 'test_unit'));
WP_CLI::add_command('skylearn test:integration', array('SkyLearn_Billing_Pro_CLI', 'test_integration'));
WP_CLI::add_command('skylearn test:performance', array('SkyLearn_Billing_Pro_CLI', 'test_performance'));
WP_CLI::add_command('skylearn generate:testdata', array('SkyLearn_Billing_Pro_CLI', 'generate_testdata'));
WP_CLI::add_command('skylearn cleanup:testdata', array('SkyLearn_Billing_Pro_CLI', 'cleanup_testdata'));
WP_CLI::add_command('skylearn health:check', array('SkyLearn_Billing_Pro_CLI', 'health_check'));