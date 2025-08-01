<?php
/**
 * Course Mapping UI for Skylearn Billing Pro
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
 * Course Mapping class
 */
class SkyLearn_Billing_Pro_Course_Mapping {
    
    /**
     * LMS Manager instance
     *
     * @var SkyLearn_Billing_Pro_LMS_Manager
     */
    private $lms_manager;
    
    /**
     * Constructor
     */
    public function __construct() {
        // Initialize LMS manager with error handling
        try {
            $this->lms_manager = skylearn_billing_pro_lms_manager();
        } catch (Exception $e) {
            error_log('SkyLearn Billing Pro: Failed to initialize LMS Manager in Course Mapping - ' . $e->getMessage());
            $this->lms_manager = null;
        }
        
        // Only add AJAX handlers if LMS manager is available
        if ($this->lms_manager) {
            add_action('wp_ajax_skylearn_billing_save_course_mapping', array($this, 'ajax_save_course_mapping'));
            add_action('wp_ajax_skylearn_billing_delete_course_mapping', array($this, 'ajax_delete_course_mapping'));
            add_action('wp_ajax_skylearn_billing_search_courses', array($this, 'ajax_search_courses'));
        }
    }
    
    /**
     * Get all course mappings
     *
     * @return array Course mappings
     */
    public function get_course_mappings() {
        $options = get_option('skylearn_billing_pro_options', array());
        return isset($options['course_mappings']) ? $options['course_mappings'] : array();
    }
    
    /**
     * Save course mapping
     *
     * @param string $product_id Product ID
     * @param int $course_id Course ID
     * @param string $trigger_type Trigger type (payment, manual, webhook)
     * @param array $additional_settings Additional mapping settings
     * @return bool Success status
     */
    public function save_course_mapping($product_id, $course_id, $trigger_type = 'payment', $additional_settings = array()) {
        $options = get_option('skylearn_billing_pro_options', array());
        
        if (!isset($options['course_mappings'])) {
            $options['course_mappings'] = array();
        }
        
        // Validate course exists
        if (!$this->validate_course_exists($course_id)) {
            return false;
        }
        
        // Create mapping data
        $mapping_data = array(
            'product_id' => sanitize_text_field($product_id),
            'course_id' => intval($course_id),
            'trigger_type' => sanitize_text_field($trigger_type),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
            'status' => 'active'
        );
        
        // Add additional settings
        if (!empty($additional_settings)) {
            $mapping_data['settings'] = $additional_settings;
        }
        
        // Save mapping (using product_id as key for easy lookup)
        $options['course_mappings'][$product_id] = $mapping_data;
        
        return update_option('skylearn_billing_pro_options', $options);
    }
    
    /**
     * Delete course mapping
     *
     * @param string $product_id Product ID
     * @return bool Success status
     */
    public function delete_course_mapping($product_id) {
        $options = get_option('skylearn_billing_pro_options', array());
        
        if (!isset($options['course_mappings'][$product_id])) {
            return false;
        }
        
        unset($options['course_mappings'][$product_id]);
        
        return update_option('skylearn_billing_pro_options', $options);
    }
    
    /**
     * Get course mapping by product ID
     *
     * @param string $product_id Product ID
     * @return array|false Course mapping or false
     */
    public function get_course_mapping($product_id) {
        $mappings = $this->get_course_mappings();
        return isset($mappings[$product_id]) ? $mappings[$product_id] : false;
    }
    
    /**
     * Get course mapping by course ID
     *
     * @param int $course_id Course ID
     * @return array Course mappings for this course
     */
    public function get_mappings_by_course($course_id) {
        $mappings = $this->get_course_mappings();
        $course_mappings = array();
        
        foreach ($mappings as $product_id => $mapping) {
            if ($mapping['course_id'] == $course_id) {
                $course_mappings[$product_id] = $mapping;
            }
        }
        
        return $course_mappings;
    }
    
    /**
     * Process enrollment for product purchase
     *
     * @param string $product_id Product ID
     * @param int $user_id User ID
     * @param string $trigger Trigger type
     * @return bool Success status
     */
    public function process_enrollment($product_id, $user_id, $trigger = 'payment') {
        if (!$this->lms_manager) {
            error_log('SkyLearn Billing Pro: LMS Manager not available for enrollment processing');
            return false;
        }
        
        try {
            $mapping = $this->get_course_mapping($product_id);
            
            if (!$mapping) {
                return false;
            }
            
            // Check if trigger matches
            if ($mapping['trigger_type'] !== $trigger && $mapping['trigger_type'] !== 'any') {
                return false;
            }
            
            // Check if mapping is active
            if (isset($mapping['status']) && $mapping['status'] !== 'active') {
                return false;
            }
            
            // Enroll user in course
            $result = $this->lms_manager->enroll_user($user_id, $mapping['course_id']);
            
            if ($result) {
                // Log successful enrollment
                $this->log_enrollment($product_id, $user_id, $mapping['course_id'], $trigger, 'success');
                
                // Fire action hook
                do_action('skylearn_billing_pro_course_mapping_enrolled', $user_id, $mapping['course_id'], $product_id, $trigger);
            } else {
                // Log failed enrollment
                $this->log_enrollment($product_id, $user_id, $mapping['course_id'], $trigger, 'failed');
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log('SkyLearn Billing Pro: Error processing enrollment - ' . $e->getMessage());
            
            // Still try to log this failure if we have the required data
            if (isset($mapping) && isset($mapping['course_id'])) {
                $this->log_enrollment($product_id, $user_id, $mapping['course_id'], $trigger, 'failed');
            }
            
            return false;
        }
    }
    
    /**
     * Validate if course exists in active LMS
     *
     * @param int $course_id Course ID
     * @return bool
     */
    private function validate_course_exists($course_id) {
        if (!$this->lms_manager || !$this->lms_manager->has_active_lms()) {
            return false;
        }
        
        try {
            $course_details = $this->lms_manager->get_course_details($course_id);
            return $course_details !== false;
        } catch (Exception $e) {
            error_log('SkyLearn Billing Pro: Error validating course existence - ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Log enrollment activity
     *
     * @param string $product_id Product ID
     * @param int $user_id User ID
     * @param int $course_id Course ID
     * @param string $trigger Trigger type
     * @param string $status Status (success/failed)
     */
    private function log_enrollment($product_id, $user_id, $course_id, $trigger, $status) {
        try {
            $log_entry = array(
                'timestamp' => current_time('mysql'),
                'product_id' => $product_id,
                'user_id' => $user_id,
                'course_id' => $course_id,
                'trigger' => $trigger,
                'status' => $status,
                'user_email' => '',
                'course_title' => ''
            );
            
            // Get user email
            $user = get_user_by('id', $user_id);
            if ($user) {
                $log_entry['user_email'] = $user->user_email;
            }
            
            // Get course title
            if ($this->lms_manager && $this->lms_manager->has_active_lms()) {
                try {
                    $course_details = $this->lms_manager->get_course_details($course_id);
                    if ($course_details) {
                        $log_entry['course_title'] = $course_details['title'];
                    }
                } catch (Exception $e) {
                    error_log('SkyLearn Billing Pro: Error getting course title for log - ' . $e->getMessage());
                }
            }
            
            // Save to enrollment log
            $options = get_option('skylearn_billing_pro_options', array());
            if (!isset($options['enrollment_log'])) {
                $options['enrollment_log'] = array();
            }
            
            $options['enrollment_log'][] = $log_entry;
            
            // Keep only last 1000 entries to prevent database bloat
            if (count($options['enrollment_log']) > 1000) {
                $options['enrollment_log'] = array_slice($options['enrollment_log'], -1000);
            }
            
            update_option('skylearn_billing_pro_options', $options);
            
            // Also log to error log if debug is enabled
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log(sprintf(
                    'Skylearn Billing Pro - Course Mapping: Product %s, User %d, Course %d, Trigger %s, Status %s',
                    $product_id,
                    $user_id,
                    $course_id,
                    $trigger,
                    $status
                ));
            }
            
        } catch (Exception $e) {
            error_log('SkyLearn Billing Pro: Error logging enrollment activity - ' . $e->getMessage());
        }
    }
    
    /**
     * Get enrollment log
     *
     * @param int $limit Number of entries to return
     * @return array Enrollment log entries
     */
    public function get_enrollment_log($limit = 50) {
        $options = get_option('skylearn_billing_pro_options', array());
        $log = isset($options['enrollment_log']) ? $options['enrollment_log'] : array();
        
        // Return latest entries first
        $log = array_reverse($log);
        
        if ($limit > 0) {
            $log = array_slice($log, 0, $limit);
        }
        
        return $log;
    }
    
    /**
     * Render course mapping UI
     */
    public function render_mapping_ui() {
        // Check if LMS manager is available
        if (!$this->lms_manager) {
            $this->render_error_notice(__('LMS Manager could not be initialized. Please check your plugin configuration.', 'skylearn-billing-pro'));
            return;
        }
        
        try {
            $mappings = $this->get_course_mappings();
            $courses = array();
            $lms_status = array();
            
            // Get courses with error handling
            try {
                $courses = $this->lms_manager->get_courses();
            } catch (Exception $e) {
                error_log('SkyLearn Billing Pro: Error getting courses - ' . $e->getMessage());
                $courses = array();
            }
            
            // Get LMS status with error handling
            try {
                $lms_status = $this->lms_manager->get_integration_status();
            } catch (Exception $e) {
                error_log('SkyLearn Billing Pro: Error getting LMS status - ' . $e->getMessage());
                $lms_status = array(
                    'detected_count' => 0,
                    'detected_lms' => array(),
                    'active_lms' => false,
                    'active_lms_name' => false,
                    'has_active_connector' => false,
                    'course_count' => 0
                );
            }
            
            $this->render_mapping_ui_content($mappings, $courses, $lms_status);
            
        } catch (Exception $e) {
            error_log('SkyLearn Billing Pro: Critical error in render_mapping_ui - ' . $e->getMessage());
            $this->render_error_notice(__('An error occurred while loading the course mapping interface. Please check the error logs for more details.', 'skylearn-billing-pro'));
        }
    }
    
    /**
     * Render error notice
     *
     * @param string $message Error message to display
     */
    private function render_error_notice($message) {
        ?>
        <div class="skylearn-billing-course-mapping">
            <div class="skylearn-billing-card">
                <div class="skylearn-billing-card-header">
                    <h3><?php esc_html_e('Course Mapping Error', 'skylearn-billing-pro'); ?></h3>
                </div>
                <div class="skylearn-billing-card-body">
                    <div class="skylearn-billing-notice skylearn-billing-notice-error">
                        <span class="dashicons dashicons-warning"></span>
                        <div>
                            <strong><?php esc_html_e('Error:', 'skylearn-billing-pro'); ?></strong>
                            <?php echo esc_html($message); ?>
                        </div>
                    </div>
                    <p><?php esc_html_e('Please try the following:', 'skylearn-billing-pro'); ?></p>
                    <ul>
                        <li><?php esc_html_e('Ensure you have a supported LMS plugin installed and activated', 'skylearn-billing-pro'); ?></li>
                        <li><?php esc_html_e('Check that your LMS is properly configured in the LMS Settings tab', 'skylearn-billing-pro'); ?></li>
                        <li><?php esc_html_e('Contact support if the problem persists', 'skylearn-billing-pro'); ?></li>
                    </ul>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render the main course mapping UI content
     *
     * @param array $mappings Current course mappings
     * @param array $courses Available courses
     * @param array $lms_status LMS status information
     */
    private function render_mapping_ui_content($mappings, $courses, $lms_status) {
        
        
        ?>
        <div class="skylearn-billing-course-mapping">
            <!-- LMS Status Section -->
            <div class="skylearn-billing-card">
                <div class="skylearn-billing-card-header">
                    <h3><?php esc_html_e('LMS Integration Status', 'skylearn-billing-pro'); ?></h3>
                </div>
                <div class="skylearn-billing-card-body">
                    <?php if ($lms_status['detected_count'] === 0): ?>
                        <div class="skylearn-billing-notice skylearn-billing-notice-warning">
                            <span class="dashicons dashicons-warning"></span>
                            <div>
                                <strong><?php esc_html_e('No LMS plugins detected', 'skylearn-billing-pro'); ?></strong><br>
                                <?php esc_html_e('Please install and activate a supported LMS plugin (LearnDash, TutorLMS, LifterLMS, or LearnPress) to enable course mapping.', 'skylearn-billing-pro'); ?>
                            </div>
                        </div>
                    <?php elseif (!$lms_status['active_lms']): ?>
                        <div class="skylearn-billing-notice skylearn-billing-notice-info">
                            <span class="dashicons dashicons-info"></span>
                            <div>
                                <strong><?php esc_html_e('LMS detected but not configured', 'skylearn-billing-pro'); ?></strong><br>
                                <?php printf(
                                    esc_html__('Found %d LMS plugin(s). Please select an active LMS in the LMS settings tab.', 'skylearn-billing-pro'),
                                    $lms_status['detected_count']
                                ); ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="skylearn-billing-lms-status-grid">
                            <div class="skylearn-billing-status-item">
                                <span class="dashicons dashicons-admin-plugins"></span>
                                <div>
                                    <strong><?php esc_html_e('Active LMS:', 'skylearn-billing-pro'); ?></strong>
                                    <?php echo esc_html($lms_status['active_lms_name']); ?>
                                </div>
                            </div>
                            <div class="skylearn-billing-status-item">
                                <span class="dashicons dashicons-book"></span>
                                <div>
                                    <strong><?php esc_html_e('Available Courses:', 'skylearn-billing-pro'); ?></strong>
                                    <?php echo intval($lms_status['course_count']); ?>
                                </div>
                            </div>
                            <div class="skylearn-billing-status-item">
                                <span class="dashicons dashicons-admin-links"></span>
                                <div>
                                    <strong><?php esc_html_e('Active Mappings:', 'skylearn-billing-pro'); ?></strong>
                                    <?php echo count($mappings); ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ($lms_status['has_active_connector']): ?>
                <!-- Add New Mapping Section -->
                <div class="skylearn-billing-card">
                    <div class="skylearn-billing-card-header">
                        <h3><?php esc_html_e('Add Course Mapping', 'skylearn-billing-pro'); ?></h3>
                        <p><?php esc_html_e('Map payment processor product IDs to LMS courses for automatic enrollment.', 'skylearn-billing-pro'); ?></p>
                    </div>
                    <div class="skylearn-billing-card-body">
                        <form id="skylearn-course-mapping-form" class="skylearn-billing-form">
                            <div class="skylearn-billing-form-row">
                                <div class="skylearn-billing-form-group">
                                    <label for="product_id"><?php esc_html_e('Product ID', 'skylearn-billing-pro'); ?></label>
                                    <input type="text" id="product_id" name="product_id" class="regular-text" placeholder="<?php esc_attr_e('e.g., stripe_prod_123, ls_variant_456', 'skylearn-billing-pro'); ?>" required>
                                    <p class="description"><?php esc_html_e('Enter the product ID from your payment processor (Stripe, Lemon Squeezy, etc.).', 'skylearn-billing-pro'); ?></p>
                                </div>
                                
                                <div class="skylearn-billing-form-group">
                                    <label for="course_id"><?php esc_html_e('Course', 'skylearn-billing-pro'); ?></label>
                                    <select id="course_id" name="course_id" class="skylearn-billing-course-select" required>
                                        <option value=""><?php esc_html_e('Select a course...', 'skylearn-billing-pro'); ?></option>
                                        <?php foreach ($courses as $course): ?>
                                            <option value="<?php echo esc_attr($course['id']); ?>">
                                                <?php echo esc_html($course['title']); ?>
                                                (ID: <?php echo esc_html($course['id']); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="skylearn-billing-form-row">
                                <div class="skylearn-billing-form-group">
                                    <label for="trigger_type"><?php esc_html_e('Enrollment Trigger', 'skylearn-billing-pro'); ?></label>
                                    <select id="trigger_type" name="trigger_type">
                                        <option value="payment"><?php esc_html_e('Payment Completed', 'skylearn-billing-pro'); ?></option>
                                        <option value="webhook"><?php esc_html_e('Webhook Received', 'skylearn-billing-pro'); ?></option>
                                        <option value="manual"><?php esc_html_e('Manual Enrollment', 'skylearn-billing-pro'); ?></option>
                                        <option value="any"><?php esc_html_e('Any Event', 'skylearn-billing-pro'); ?></option>
                                    </select>
                                </div>
                                
                                <div class="skylearn-billing-form-group">
                                    <label>&nbsp;</label>
                                    <button type="submit" class="skylearn-billing-btn skylearn-billing-btn-primary">
                                        <?php esc_html_e('Add Mapping', 'skylearn-billing-pro'); ?>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Existing Mappings Section -->
                <div class="skylearn-billing-card">
                    <div class="skylearn-billing-card-header">
                        <h3><?php esc_html_e('Course Mappings', 'skylearn-billing-pro'); ?></h3>
                    </div>
                    <div class="skylearn-billing-card-body">
                        <?php if (empty($mappings)): ?>
                            <div class="skylearn-billing-empty-state">
                                <span class="dashicons dashicons-admin-links"></span>
                                <h4><?php esc_html_e('No course mappings yet', 'skylearn-billing-pro'); ?></h4>
                                <p><?php esc_html_e('Create your first course mapping to automatically enroll customers in courses after purchase.', 'skylearn-billing-pro'); ?></p>
                            </div>
                        <?php else: ?>
                            <div class="skylearn-billing-mappings-table">
                                <table class="wp-list-table widefat fixed striped">
                                    <thead>
                                        <tr>
                                            <th><?php esc_html_e('Product ID', 'skylearn-billing-pro'); ?></th>
                                            <th><?php esc_html_e('Course', 'skylearn-billing-pro'); ?></th>
                                            <th><?php esc_html_e('Trigger', 'skylearn-billing-pro'); ?></th>
                                            <th><?php esc_html_e('Status', 'skylearn-billing-pro'); ?></th>
                                            <th><?php esc_html_e('Created', 'skylearn-billing-pro'); ?></th>
                                            <th><?php esc_html_e('Actions', 'skylearn-billing-pro'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($mappings as $product_id => $mapping): ?>
                                            <?php
                                            $course_details = null;
                                            $course_title = __('Course not found', 'skylearn-billing-pro');
                                            
                                            // Get course details with error handling
                                            try {
                                                if ($this->lms_manager && $this->lms_manager->has_active_lms()) {
                                                    $course_details = $this->lms_manager->get_course_details($mapping['course_id']);
                                                    if ($course_details) {
                                                        $course_title = $course_details['title'];
                                                    }
                                                }
                                            } catch (Exception $e) {
                                                error_log('SkyLearn Billing Pro: Error getting course details for mapping - ' . $e->getMessage());
                                            }
                                            ?>
                                            <tr data-product-id="<?php echo esc_attr($product_id); ?>">
                                                <td>
                                                    <strong><?php echo esc_html($product_id); ?></strong>
                                                </td>
                                                <td>
                                                    <?php echo esc_html($course_title); ?>
                                                    <br><small>ID: <?php echo esc_html($mapping['course_id']); ?></small>
                                                </td>
                                                <td>
                                                    <span class="skylearn-billing-badge">
                                                        <?php echo esc_html(ucfirst($mapping['trigger_type'])); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if (isset($mapping['status']) && $mapping['status'] === 'active'): ?>
                                                        <span class="skylearn-billing-status-active"><?php esc_html_e('Active', 'skylearn-billing-pro'); ?></span>
                                                    <?php else: ?>
                                                        <span class="skylearn-billing-status-inactive"><?php esc_html_e('Inactive', 'skylearn-billing-pro'); ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php
                                                    if (isset($mapping['created_at'])) {
                                                        echo esc_html(date_i18n(get_option('date_format'), strtotime($mapping['created_at'])));
                                                    } else {
                                                        esc_html_e('Unknown', 'skylearn-billing-pro');
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <button class="button button-small skylearn-billing-delete-mapping" data-product-id="<?php echo esc_attr($product_id); ?>">
                                                        <?php esc_html_e('Delete', 'skylearn-billing-pro'); ?>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Handle course mapping form submission
            $('#skylearn-course-mapping-form').on('submit', function(e) {
                e.preventDefault();
                
                var data = {
                    action: 'skylearn_billing_save_course_mapping',
                    nonce: '<?php echo wp_create_nonce('skylearn_course_mapping_nonce'); ?>',
                    product_id: $('#product_id').val(),
                    course_id: $('#course_id').val(),
                    trigger_type: $('#trigger_type').val()
                };
                
                $.post(ajaxurl, data, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data.message || 'Error saving mapping');
                    }
                });
            });
            
            // Handle mapping deletion
            $('.skylearn-billing-delete-mapping').on('click', function(e) {
                e.preventDefault();
                
                if (!confirm('<?php esc_js_e('Are you sure you want to delete this mapping?', 'skylearn-billing-pro'); ?>')) {
                    return;
                }
                
                var productId = $(this).data('product-id');
                var $row = $(this).closest('tr');
                
                var data = {
                    action: 'skylearn_billing_delete_course_mapping',
                    nonce: '<?php echo wp_create_nonce('skylearn_course_mapping_nonce'); ?>',
                    product_id: productId
                };
                
                $.post(ajaxurl, data, function(response) {
                    if (response.success) {
                        $row.fadeOut();
                    } else {
                        alert(response.data.message || 'Error deleting mapping');
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * AJAX handler for saving course mapping
     */
    public function ajax_save_course_mapping() {
        try {
            check_ajax_referer('skylearn_course_mapping_nonce', 'nonce');
            
            if (!current_user_can('manage_options')) {
                wp_die(__('Unauthorized', 'skylearn-billing-pro'));
            }
            
            $product_id = sanitize_text_field($_POST['product_id']);
            $course_id = intval($_POST['course_id']);
            $trigger_type = sanitize_text_field($_POST['trigger_type']);
            
            if (empty($product_id) || empty($course_id)) {
                wp_send_json_error(array('message' => __('Product ID and Course are required.', 'skylearn-billing-pro')));
            }
            
            $result = $this->save_course_mapping($product_id, $course_id, $trigger_type);
            
            if ($result) {
                wp_send_json_success(array('message' => __('Mapping saved successfully.', 'skylearn-billing-pro')));
            } else {
                wp_send_json_error(array('message' => __('Failed to save mapping.', 'skylearn-billing-pro')));
            }
        } catch (Exception $e) {
            error_log('SkyLearn Billing Pro: Error in ajax_save_course_mapping - ' . $e->getMessage());
            wp_send_json_error(array('message' => __('An error occurred while saving the mapping.', 'skylearn-billing-pro')));
        }
    }
    
    /**
     * AJAX handler for deleting course mapping
     */
    public function ajax_delete_course_mapping() {
        try {
            check_ajax_referer('skylearn_course_mapping_nonce', 'nonce');
            
            if (!current_user_can('manage_options')) {
                wp_die(__('Unauthorized', 'skylearn-billing-pro'));
            }
            
            $product_id = sanitize_text_field($_POST['product_id']);
            
            if (empty($product_id)) {
                wp_send_json_error(array('message' => __('Product ID is required.', 'skylearn-billing-pro')));
            }
            
            $result = $this->delete_course_mapping($product_id);
            
            if ($result) {
                wp_send_json_success(array('message' => __('Mapping deleted successfully.', 'skylearn-billing-pro')));
            } else {
                wp_send_json_error(array('message' => __('Failed to delete mapping.', 'skylearn-billing-pro')));
            }
        } catch (Exception $e) {
            error_log('SkyLearn Billing Pro: Error in ajax_delete_course_mapping - ' . $e->getMessage());
            wp_send_json_error(array('message' => __('An error occurred while deleting the mapping.', 'skylearn-billing-pro')));
        }
    }
    
    /**
     * AJAX handler for searching courses
     */
    public function ajax_search_courses() {
        try {
            check_ajax_referer('skylearn_course_mapping_nonce', 'nonce');
            
            if (!current_user_can('manage_options')) {
                wp_die(__('Unauthorized', 'skylearn-billing-pro'));
            }
            
            $search = sanitize_text_field($_POST['search']);
            
            if (!$this->lms_manager || !$this->lms_manager->has_active_lms()) {
                wp_send_json_error(array('message' => __('No active LMS available.', 'skylearn-billing-pro')));
                return;
            }
            
            $courses = $this->lms_manager->get_courses();
            
            $filtered_courses = array();
            
            foreach ($courses as $course) {
                if (empty($search) || stripos($course['title'], $search) !== false) {
                    $filtered_courses[] = $course;
                }
            }
            
            wp_send_json_success($filtered_courses);
        } catch (Exception $e) {
            error_log('SkyLearn Billing Pro: Error in ajax_search_courses - ' . $e->getMessage());
            wp_send_json_error(array('message' => __('An error occurred while searching courses.', 'skylearn-billing-pro')));
        }
    }
}

/**
 * Get the Course Mapping instance
 *
 * @return SkyLearn_Billing_Pro_Course_Mapping
 */
function skylearn_billing_pro_course_mapping() {
    static $instance = null;
    
    if ($instance === null) {
        $instance = new SkyLearn_Billing_Pro_Course_Mapping();
    }
    
    return $instance;
}