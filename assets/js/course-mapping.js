/**
 * Skylearn Billing Pro - Course Mapping JavaScript
 * Enhanced course mapping functionality with better error handling
 *
 * @package SkyLearnBillingPro
 * @author Ferdous Khalifa
 * @copyright 2024 Skyian LLC
 * @license GPLv3
 */

(function($) {
    'use strict';

    var CourseMappingManager = {
        
        /**
         * Initialize course mapping functionality
         */
        init: function() {
            this.bindEvents();
            this.initValidation();
            this.checkCourseAvailability();
        },
        
        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Course mapping form submission
            $(document).on('submit', '#skylearn-course-mapping-form', this.handleFormSubmission.bind(this));
            
            // Delete mapping buttons
            $(document).on('click', '.skylearn-billing-delete-mapping', this.handleMappingDeletion.bind(this));
            
            // Course search functionality
            $(document).on('input', '#course-search', this.handleCourseSearch.bind(this));
            
            // Real-time validation
            $(document).on('blur', '#product_id', this.validateProductId.bind(this));
            $(document).on('change', '#course_id', this.validateCourseSelection.bind(this));
        },
        
        /**
         * Initialize form validation
         */
        initValidation: function() {
            // Add required field indicators
            $('#skylearn-course-mapping-form input[required], #skylearn-course-mapping-form select[required]').each(function() {
                var $label = $('label[for="' + this.id + '"]');
                if ($label.length && $label.find('.required').length === 0) {
                    $label.append(' <span class="required" style="color: #FF3B00;">*</span>');
                }
            });
        },
        
        /**
         * Check course availability and update UI accordingly
         */
        checkCourseAvailability: function() {
            var $courseSelect = $('#course_id');
            var $submitButton = $('#skylearn-course-mapping-form button[type="submit"]');
            var coursesAvailable = $courseSelect.find('option').length > 1;
            
            if (!coursesAvailable) {
                $submitButton.prop('disabled', true).text('No Courses Available');
                $courseSelect.prop('disabled', true);
                this.showNotice('No courses are available. Please check your LMS configuration and ensure you have published courses.', 'warning');
            }
        },
        
        /**
         * Handle form submission
         */
        handleFormSubmission: function(e) {
            e.preventDefault();
            
            var $form = $(e.target);
            var $submitButton = $form.find('button[type="submit"]');
            
            // Validate form
            if (!this.validateForm($form)) {
                return false;
            }
            
            // Show loading state
            this.setLoadingState($submitButton, true, 'Saving...');
            
            var data = {
                action: 'skylearn_billing_save_course_mapping',
                nonce: skylernCourseMappingData.nonce,
                product_id: $('#product_id').val().trim(),
                course_id: $('#course_id').val(),
                trigger_type: $('#trigger_type').val()
            };
            
            $.ajax({
                url: skylernCourseMappingData.ajaxUrl,
                type: 'POST',
                data: data,
                timeout: 30000, // 30 second timeout
                success: this.handleSubmissionSuccess.bind(this),
                error: this.handleSubmissionError.bind(this, $submitButton),
                complete: function() {
                    this.setLoadingState($submitButton, false, 'Add Mapping');
                }.bind(this)
            });
        },
        
        /**
         * Handle successful form submission
         */
        handleSubmissionSuccess: function(response) {
            if (response.success) {
                this.showNotice('Course mapping saved successfully!', 'success');
                
                // Reset form
                $('#skylearn-course-mapping-form')[0].reset();
                
                // Reload mappings table or redirect
                setTimeout(function() {
                    window.location.reload();
                }, 1500);
            } else {
                var errorMessage = response.data && response.data.message ? 
                    response.data.message : 
                    'Error saving mapping. Please try again.';
                this.showNotice(errorMessage, 'error');
            }
        },
        
        /**
         * Handle form submission error
         */
        handleSubmissionError: function($submitButton, xhr, status, error) {
            console.error('Course Mapping AJAX Error:', {
                status: status,
                error: error,
                response: xhr.responseText
            });
            
            var errorMessage = 'Network error occurred. Please check your connection and try again.';
            
            if (xhr.status === 403) {
                errorMessage = 'Permission denied. Please refresh the page and try again.';
            } else if (xhr.status === 500) {
                errorMessage = 'Server error occurred. Please contact support if this persists.';
            } else if (xhr.status === 0) {
                errorMessage = 'Connection lost. Please check your internet connection.';
            }
            
            this.showNotice(errorMessage, 'error');
        },
        
        /**
         * Handle mapping deletion
         */
        handleMappingDeletion: function(e) {
            e.preventDefault();
            
            if (!confirm('Are you sure you want to delete this mapping? This action cannot be undone.')) {
                return;
            }
            
            var $button = $(e.target);
            var productId = $button.data('product-id');
            var $row = $button.closest('tr');
            
            this.setLoadingState($button, true, 'Deleting...');
            
            var data = {
                action: 'skylearn_billing_delete_course_mapping',
                nonce: skylernCourseMappingData.nonce,
                product_id: productId
            };
            
            $.ajax({
                url: skylernCourseMappingData.ajaxUrl,
                type: 'POST',
                data: data,
                timeout: 30000,
                success: function(response) {
                    if (response.success) {
                        this.showNotice('Mapping deleted successfully.', 'success');
                        $row.fadeOut(300, function() {
                            $(this).remove();
                            // Check if table is now empty
                            if ($('.skylearn-billing-mappings-table tbody tr:visible').length === 0) {
                                $('.skylearn-billing-mappings-table').replaceWith(
                                    '<div class="skylearn-billing-empty-state">' +
                                    '<span class="dashicons dashicons-admin-links"></span>' +
                                    '<h4>No course mappings yet</h4>' +
                                    '<p>Create your first course mapping to automatically enroll customers in courses after purchase.</p>' +
                                    '</div>'
                                );
                            }
                        });
                    } else {
                        var errorMessage = response.data && response.data.message ? 
                            response.data.message : 
                            'Error deleting mapping. Please try again.';
                        this.showNotice(errorMessage, 'error');
                        this.setLoadingState($button, false, 'Delete');
                    }
                }.bind(this),
                error: function(xhr, status, error) {
                    console.error('Delete Mapping AJAX Error:', {
                        status: status,
                        error: error,
                        response: xhr.responseText
                    });
                    this.showNotice('Network error occurred while deleting mapping.', 'error');
                    this.setLoadingState($button, false, 'Delete');
                }.bind(this)
            });
        },
        
        /**
         * Validate the entire form
         */
        validateForm: function($form) {
            var isValid = true;
            var errors = [];
            
            // Validate product ID
            var productId = $('#product_id').val().trim();
            if (!productId) {
                errors.push('Product ID is required.');
                this.markFieldAsError('#product_id');
                isValid = false;
            } else if (productId.length < 3) {
                errors.push('Product ID must be at least 3 characters long.');
                this.markFieldAsError('#product_id');
                isValid = false;
            } else {
                this.markFieldAsValid('#product_id');
            }
            
            // Validate course selection
            var courseId = $('#course_id').val();
            if (!courseId) {
                errors.push('Please select a course.');
                this.markFieldAsError('#course_id');
                isValid = false;
            } else {
                this.markFieldAsValid('#course_id');
            }
            
            // Check for duplicate mapping
            if (isValid && this.checkDuplicateMapping(productId)) {
                errors.push('A mapping for this product ID already exists.');
                this.markFieldAsError('#product_id');
                isValid = false;
            }
            
            if (!isValid) {
                this.showNotice('Please fix the following errors: ' + errors.join(' '), 'error');
            }
            
            return isValid;
        },
        
        /**
         * Validate product ID field
         */
        validateProductId: function(e) {
            var $field = $(e.target);
            var value = $field.val().trim();
            
            if (!value) {
                this.markFieldAsError($field);
                return false;
            } else if (value.length < 3) {
                this.markFieldAsError($field);
                this.showFieldError($field, 'Product ID must be at least 3 characters long.');
                return false;
            } else if (this.checkDuplicateMapping(value)) {
                this.markFieldAsError($field);
                this.showFieldError($field, 'A mapping for this product ID already exists.');
                return false;
            } else {
                this.markFieldAsValid($field);
                this.clearFieldError($field);
                return true;
            }
        },
        
        /**
         * Validate course selection
         */
        validateCourseSelection: function(e) {
            var $field = $(e.target);
            var value = $field.val();
            
            if (!value) {
                this.markFieldAsError($field);
                return false;
            } else {
                this.markFieldAsValid($field);
                return true;
            }
        },
        
        /**
         * Check if product ID already has a mapping
         */
        checkDuplicateMapping: function(productId) {
            var exists = false;
            $('.skylearn-billing-mappings-table tbody tr').each(function() {
                var existingProductId = $(this).data('product-id');
                if (existingProductId === productId) {
                    exists = true;
                    return false; // Break the loop
                }
            });
            return exists;
        },
        
        /**
         * Mark field as having an error
         */
        markFieldAsError: function(selector) {
            var $field = $(selector);
            $field.addClass('skylearn-field-error').removeClass('skylearn-field-valid');
            $field.closest('.skylearn-billing-form-group').addClass('has-error');
        },
        
        /**
         * Mark field as valid
         */
        markFieldAsValid: function(selector) {
            var $field = $(selector);
            $field.addClass('skylearn-field-valid').removeClass('skylearn-field-error');
            $field.closest('.skylearn-billing-form-group').removeClass('has-error');
        },
        
        /**
         * Show field-specific error message
         */
        showFieldError: function($field, message) {
            this.clearFieldError($field);
            $field.after('<div class="skylearn-field-error-message">' + message + '</div>');
        },
        
        /**
         * Clear field error message
         */
        clearFieldError: function($field) {
            $field.siblings('.skylearn-field-error-message').remove();
        },
        
        /**
         * Set loading state for button
         */
        setLoadingState: function($button, loading, text) {
            if (loading) {
                $button.prop('disabled', true).addClass('loading');
                if (text) {
                    $button.data('original-text', $button.text()).text(text);
                }
            } else {
                $button.prop('disabled', false).removeClass('loading');
                if ($button.data('original-text')) {
                    $button.text($button.data('original-text'));
                }
            }
        },
        
        /**
         * Show notification to user
         */
        showNotice: function(message, type) {
            type = type || 'info';
            
            // Remove existing notices
            $('.skylearn-mapping-notice').fadeOut(300, function() {
                $(this).remove();
            });
            
            var $notice = $('<div class="notice notice-' + type + ' is-dismissible skylearn-mapping-notice">' +
                '<p>' + message + '</p>' +
                '<button type="button" class="notice-dismiss">' +
                    '<span class="screen-reader-text">Dismiss this notice.</span>' +
                '</button>' +
            '</div>');
            
            // Insert notice at the top of the content area
            $('.skylearn-billing-course-mapping').prepend($notice);
            
            // Auto-dismiss success notices after 5 seconds
            if (type === 'success') {
                setTimeout(function() {
                    $notice.fadeOut(300, function() {
                        $(this).remove();
                    });
                }, 5000);
            }
            
            // Handle dismiss button
            $notice.on('click', '.notice-dismiss', function() {
                $notice.fadeOut(300, function() {
                    $(this).remove();
                });
            });
            
            // Scroll to notice
            $('html, body').animate({
                scrollTop: $notice.offset().top - 100
            }, 300);
        },
        
        /**
         * Handle course search
         */
        handleCourseSearch: function(e) {
            var searchTerm = $(e.target).val().toLowerCase();
            var $options = $('#course_id option');
            
            $options.each(function() {
                var $option = $(this);
                var text = $option.text().toLowerCase();
                
                if (text.includes(searchTerm) || searchTerm === '') {
                    $option.show();
                } else {
                    $option.hide();
                }
            });
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        // Only initialize on LMS pages
        if ($('#skylearn-course-mapping-form').length || $('.skylearn-billing-course-mapping').length) {
            CourseMappingManager.init();
        }
    });
    
    // Make it globally accessible
    window.CourseMappingManager = CourseMappingManager;
    
})(jQuery);