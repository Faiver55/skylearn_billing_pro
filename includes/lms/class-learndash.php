<?php
/**
 * LearnDash LMS Connector for Skylearn Billing Pro
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
 * LearnDash LMS Connector class
 */
class SkyLearn_Billing_Pro_LearnDash_Connector {
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init();
    }
    
    /**
     * Initialize connector
     */
    private function init() {
        // Verify LearnDash is active
        if (!$this->is_learndash_active()) {
            return;
        }
        
        // Hook into LearnDash if needed
        add_action('learndash_course_completed', array($this, 'on_course_completed'), 10, 1);
    }
    
    /**
     * Check if LearnDash is active
     *
     * @return bool
     */
    private function is_learndash_active() {
        return function_exists('learndash_get_course_id') || class_exists('SFWD_LMS');
    }
    
    /**
     * Get all courses from LearnDash
     *
     * @return array Array of courses with ID and title
     */
    public function get_courses() {
        if (!$this->is_learndash_active()) {
            error_log('SkyLearn Billing Pro: LearnDash not active when get_courses() called');
            return array();
        }
        
        $courses = array();
        
        try {
            // Get LearnDash courses
            $course_query = new WP_Query(array(
                'post_type' => 'sfwd-courses',
                'post_status' => 'publish',
                'posts_per_page' => -1,
                'orderby' => 'title',
                'order' => 'ASC'
            ));
            
            if ($course_query->have_posts()) {
                while ($course_query->have_posts()) {
                    $course_query->the_post();
                    $course_id = get_the_ID();
                    
                    try {
                        $courses[] = array(
                            'id' => $course_id,
                            'title' => get_the_title(),
                            'permalink' => get_permalink(),
                            'status' => get_post_status(),
                            'enrolled_count' => $this->get_enrolled_count($course_id),
                            'price' => $this->get_course_price($course_id)
                        );
                    } catch (Exception $e) {
                        error_log('SkyLearn Billing Pro: Error processing course ' . $course_id . ': ' . $e->getMessage());
                        // Continue processing other courses
                    }
                }
                wp_reset_postdata();
            } else {
                error_log('SkyLearn Billing Pro: No LearnDash courses found in database');
            }
            
            error_log('SkyLearn Billing Pro: Found ' . count($courses) . ' LearnDash courses');
            
        } catch (Exception $e) {
            error_log('SkyLearn Billing Pro: Error in get_courses(): ' . $e->getMessage());
            return array();
        }
        
        return $courses;
    }
    
    /**
     * Enroll user in LearnDash course
     *
     * @param int $user_id User ID
     * @param int $course_id Course ID
     * @return bool Success status
     */
    public function enroll_user($user_id, $course_id) {
        if (!$this->is_learndash_active()) {
            return false;
        }
        
        // Verify course exists and is published
        if (get_post_type($course_id) !== 'sfwd-courses' || get_post_status($course_id) !== 'publish') {
            return false;
        }
        
        // Verify user exists
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return false;
        }
        
        // Check if already enrolled
        if ($this->is_user_enrolled($user_id, $course_id)) {
            return true; // Already enrolled, consider success
        }
        
        // Enroll user using LearnDash function
        if (function_exists('ld_update_course_access')) {
            $result = ld_update_course_access($user_id, $course_id, $remove = false);
            
            // Log enrollment for debugging/tracking
            $this->log_enrollment_action('enroll', $user_id, $course_id, $result);
            
            // Fire action hook for other plugins
            if ($result) {
                do_action('skylearn_billing_pro_user_enrolled', $user_id, $course_id, 'learndash');
            }
            
            return $result;
        }
        
        return false;
    }
    
    /**
     * Unenroll user from LearnDash course
     *
     * @param int $user_id User ID
     * @param int $course_id Course ID
     * @return bool Success status
     */
    public function unenroll_user($user_id, $course_id) {
        if (!$this->is_learndash_active()) {
            return false;
        }
        
        // Verify course exists
        if (get_post_type($course_id) !== 'sfwd-courses') {
            return false;
        }
        
        // Verify user exists
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return false;
        }
        
        // Check if user is enrolled
        if (!$this->is_user_enrolled($user_id, $course_id)) {
            return true; // Not enrolled, consider success
        }
        
        // Unenroll user using LearnDash function
        if (function_exists('ld_update_course_access')) {
            $result = ld_update_course_access($user_id, $course_id, $remove = true);
            
            // Log unenrollment for debugging/tracking
            $this->log_enrollment_action('unenroll', $user_id, $course_id, $result);
            
            // Fire action hook for other plugins
            if ($result) {
                do_action('skylearn_billing_pro_user_unenrolled', $user_id, $course_id, 'learndash');
            }
            
            return $result;
        }
        
        return false;
    }
    
    /**
     * Check if user is enrolled in LearnDash course
     *
     * @param int $user_id User ID
     * @param int $course_id Course ID
     * @return bool Enrollment status
     */
    public function is_user_enrolled($user_id, $course_id) {
        if (!$this->is_learndash_active()) {
            return false;
        }
        
        // Use LearnDash function to check enrollment
        if (function_exists('sfwd_lms_has_access')) {
            return sfwd_lms_has_access($course_id, $user_id);
        }
        
        // Fallback: Check user meta
        $enrolled_courses = get_user_meta($user_id, '_sfwd-course_access_from', true);
        if (is_array($enrolled_courses)) {
            return in_array($course_id, $enrolled_courses);
        }
        
        return false;
    }
    
    /**
     * Get course details
     *
     * @param int $course_id Course ID
     * @return array|false Course details or false
     */
    public function get_course_details($course_id) {
        if (!$this->is_learndash_active()) {
            return false;
        }
        
        if (get_post_type($course_id) !== 'sfwd-courses') {
            return false;
        }
        
        $course = get_post($course_id);
        if (!$course) {
            return false;
        }
        
        $details = array(
            'id' => $course_id,
            'title' => $course->post_title,
            'content' => $course->post_content,
            'excerpt' => $course->post_excerpt,
            'permalink' => get_permalink($course_id),
            'status' => $course->post_status,
            'enrolled_count' => $this->get_enrolled_count($course_id),
            'price' => $this->get_course_price($course_id),
            'lessons_count' => $this->get_lessons_count($course_id),
            'topics_count' => $this->get_topics_count($course_id),
            'quizzes_count' => $this->get_quizzes_count($course_id)
        );
        
        // Add LearnDash specific settings if function exists
        if (function_exists('learndash_get_setting')) {
            $details['course_settings'] = array(
                'price_type' => learndash_get_setting($course_id, 'course_price_type'),
                'price' => learndash_get_setting($course_id, 'course_price'),
                'access_mode' => learndash_get_setting($course_id, 'course_access_mode'),
                'materials' => learndash_get_setting($course_id, 'course_materials')
            );
        }
        
        return $details;
    }
    
    /**
     * Get enrolled user count for course
     *
     * @param int $course_id Course ID
     * @return int Enrolled count
     */
    private function get_enrolled_count($course_id) {
        if (function_exists('learndash_get_users_for_course')) {
            $enrolled_users = learndash_get_users_for_course($course_id, array(), false);
            return is_array($enrolled_users) ? count($enrolled_users) : 0;
        }
        
        // Fallback method - with error handling
        global $wpdb;
        
        // Check if $wpdb is available
        if (!isset($wpdb) || !is_object($wpdb)) {
            error_log('SkyLearn Billing Pro: $wpdb not available for enrolled count query');
            return 0;
        }
        
        try {
            $count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE meta_key = %s AND meta_value LIKE %s",
                '_sfwd-course_access_from',
                '%' . $wpdb->esc_like($course_id) . '%'
            ));
            
            return intval($count);
        } catch (Exception $e) {
            error_log('SkyLearn Billing Pro: Error getting enrolled count for course ' . $course_id . ': ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get course price
     *
     * @param int $course_id Course ID
     * @return string Course price
     */
    private function get_course_price($course_id) {
        if (function_exists('learndash_get_setting')) {
            $price_type = learndash_get_setting($course_id, 'course_price_type');
            $price = learndash_get_setting($course_id, 'course_price');
            
            if ($price_type === 'free' || empty($price)) {
                return function_exists('__') ? __('Free', 'skylearn-billing-pro') : 'Free';
            }
            
            return $price;
        }
        
        return function_exists('__') ? __('N/A', 'skylearn-billing-pro') : 'N/A';
    }
    
    /**
     * Get lessons count for course
     *
     * @param int $course_id Course ID
     * @return int Lessons count
     */
    private function get_lessons_count($course_id) {
        if (function_exists('learndash_get_course_lessons_list')) {
            $lessons = learndash_get_course_lessons_list($course_id);
            return is_array($lessons) ? count($lessons) : 0;
        }
        
        return 0;
    }
    
    /**
     * Get topics count for course
     *
     * @param int $course_id Course ID
     * @return int Topics count
     */
    private function get_topics_count($course_id) {
        if (function_exists('learndash_get_course_topics_list')) {
            $topics = learndash_get_course_topics_list($course_id);
            return is_array($topics) ? count($topics) : 0;
        }
        
        return 0;
    }
    
    /**
     * Get quizzes count for course
     *
     * @param int $course_id Course ID
     * @return int Quizzes count
     */
    private function get_quizzes_count($course_id) {
        if (function_exists('learndash_get_course_quiz_list')) {
            $quizzes = learndash_get_course_quiz_list($course_id);
            return is_array($quizzes) ? count($quizzes) : 0;
        }
        
        return 0;
    }
    
    /**
     * Log enrollment action for debugging
     *
     * @param string $action Action type (enroll/unenroll)
     * @param int $user_id User ID
     * @param int $course_id Course ID
     * @param bool $result Action result
     */
    private function log_enrollment_action($action, $user_id, $course_id, $result) {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }
        
        $message = sprintf(
            'Skylearn Billing Pro - LearnDash %s: User %d, Course %d, Result: %s',
            $action,
            $user_id,
            $course_id,
            $result ? 'success' : 'failed'
        );
        
        error_log($message);
    }
    
    /**
     * Handle course completion event
     *
     * @param array $data Course completion data
     */
    public function on_course_completed($data) {
        $user_id = isset($data['user']) ? $data['user']->ID : 0;
        $course_id = isset($data['course']) ? $data['course']->ID : 0;
        
        if ($user_id && $course_id) {
            do_action('skylearn_billing_pro_course_completed', $user_id, $course_id, 'learndash');
        }
    }
    
    /**
     * Get LMS specific capabilities
     *
     * @return array Capabilities supported by this LMS
     */
    public function get_capabilities() {
        return array(
            'enrollment' => true,
            'unenrollment' => true,
            'progress_tracking' => true,
            'completion_tracking' => true,
            'quiz_support' => true,
            'lesson_support' => true,
            'certificate_support' => function_exists('learndash_get_certificate_details'),
            'group_support' => function_exists('learndash_get_users_group_ids'),
            'prerequisite_support' => true
        );
    }
}