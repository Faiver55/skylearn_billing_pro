/**
 * Email Builder JavaScript for Skylearn Billing Pro
 */

(function($) {
    'use strict';

    /**
     * Email Builder Class
     */
    class SkyLearnEmailBuilder {
        constructor() {
            this.currentEmailType = '';
            this.currentLanguage = 'en';
            this.blocks = [];
            this.selectedBlock = null;
            this.isDragging = false;
            
            this.init();
        }

        /**
         * Initialize the email builder
         */
        init() {
            this.bindEvents();
            this.setupDragAndDrop();
            this.loadTemplate();
        }

        /**
         * Bind events
         */
        bindEvents() {
            // Toolbar events
            $('#skylearn-builder-back').on('click', this.handleBack.bind(this));
            $('#skylearn-builder-save').on('click', this.handleSave.bind(this));
            $('#skylearn-builder-preview').on('click', this.handlePreview.bind(this));
            $('#skylearn-builder-test').on('click', this.handleTestEmail.bind(this));

            // Device preview events
            $('.skylearn-device-btn').on('click', this.handleDeviceChange.bind(this));

            // Canvas events
            $(document).on('click', '.skylearn-email-block', this.handleBlockSelect.bind(this));
            $(document).on('input', '.skylearn-editable', this.handleContentEdit.bind(this));
            $(document).on('blur', '.skylearn-editable', this.handleContentBlur.bind(this));

            // Block control events
            $(document).on('click', '.skylearn-move-up', this.handleMoveUp.bind(this));
            $(document).on('click', '.skylearn-move-down', this.handleMoveDown.bind(this));
            $(document).on('click', '.skylearn-duplicate', this.handleDuplicate.bind(this));
            $(document).on('click', '.skylearn-delete', this.handleDelete.bind(this));

            // Property events
            $(document).on('change', '.skylearn-property-input', this.handlePropertyChange.bind(this));
            $(document).on('click', '.skylearn-select-image', this.handleImageSelect.bind(this));

            // Modal events
            $('.skylearn-modal-close, #skylearn-close-preview, #skylearn-cancel-test-email').on('click', this.handleModalClose.bind(this));
            $('#skylearn-send-test-email-btn').on('click', this.handleSendTestEmail.bind(this));

            // Token events
            $(document).on('click', '.skylearn-token-item', this.handleTokenClick.bind(this));

            // Subject events
            $('#email-subject').on('input', this.handleSubjectChange.bind(this));

            // Keyboard events
            $(document).on('keydown', this.handleKeyDown.bind(this));
        }

        /**
         * Setup drag and drop functionality
         */
        setupDragAndDrop() {
            // Make blocks draggable
            $(document).on('dragstart', '.skylearn-block-item', (e) => {
                this.isDragging = true;
                e.originalEvent.dataTransfer.setData('text/plain', $(e.target).closest('.skylearn-block-item').data('block-type'));
                $(e.target).closest('.skylearn-block-item').addClass('skylearn-dragging');
            });

            $(document).on('dragend', '.skylearn-block-item', (e) => {
                this.isDragging = false;
                $(e.target).closest('.skylearn-block-item').removeClass('skylearn-dragging');
            });

            // Canvas drop events
            $('#skylearn-email-canvas').on('dragover', (e) => {
                e.preventDefault();
                e.originalEvent.dataTransfer.dropEffect = 'copy';
                this.showDropZones();
            });

            $('#skylearn-email-canvas').on('dragleave', (e) => {
                if (!$.contains(e.currentTarget, e.relatedTarget)) {
                    this.hideDropZones();
                }
            });

            $('#skylearn-email-canvas').on('drop', (e) => {
                e.preventDefault();
                const blockType = e.originalEvent.dataTransfer.getData('text/plain');
                this.addBlock(blockType);
                this.hideDropZones();
            });

            // Block reordering
            this.setupSortable();
        }

        /**
         * Setup sortable blocks
         */
        setupSortable() {
            $('#skylearn-email-canvas').sortable({
                items: '.skylearn-email-block',
                handle: '.skylearn-email-block',
                placeholder: 'skylearn-drop-zone active',
                tolerance: 'pointer',
                update: (event, ui) => {
                    this.updateBlockOrder();
                }
            });
        }

        /**
         * Load template data
         */
        loadTemplate() {
            const canvas = $('#skylearn-email-canvas');
            this.currentEmailType = canvas.data('email-type');
            this.currentLanguage = canvas.data('language');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'skylearn_email_builder_load',
                    nonce: this.getNonce('skylearn_email_builder'),
                    email_type: this.currentEmailType,
                    language: this.currentLanguage
                },
                success: (response) => {
                    if (response.success) {
                        this.loadTemplateData(response.data);
                    }
                },
                error: () => {
                    this.showError('Failed to load template data.');
                }
            });
        }

        /**
         * Load template data into builder
         */
        loadTemplateData(data) {
            // Set subject
            $('#email-subject').val(data.subject || '');

            // Load blocks
            if (data.blocks && data.blocks.length > 0) {
                this.blocks = data.blocks;
                this.renderBlocks();
            } else {
                this.showPlaceholder();
            }
        }

        /**
         * Render blocks on canvas
         */
        renderBlocks() {
            const canvas = $('#skylearn-email-canvas');
            canvas.empty();

            this.blocks.forEach((block, index) => {
                const blockElement = this.createBlockElement(block, index);
                canvas.append(blockElement);
            });

            this.setupSortable();
        }

        /**
         * Create block element
         */
        createBlockElement(block, index) {
            const template = this.getBlockTemplate(block.type);
            const $block = $(template).clone();
            
            // Set block data
            $block.attr('data-block-index', index);
            $block.find('.skylearn-block-content').attr('data-block-type', block.type);

            // Apply settings
            this.applyBlockSettings($block, block.settings || {});

            return $block;
        }

        /**
         * Get block template
         */
        getBlockTemplate(type) {
            const template = $(`#skylearn-block-templates .skylearn-block-template[data-block="${type}"]`).html();
            return template || this.getDefaultBlockTemplate(type);
        }

        /**
         * Get default block template
         */
        getDefaultBlockTemplate(type) {
            const templates = {
                header: '<div class="skylearn-email-block skylearn-header-block" data-block-type="header"><div class="skylearn-block-content"><h1 contenteditable="true" class="skylearn-editable">Your Heading Here</h1></div><div class="skylearn-block-controls"><button type="button" class="skylearn-block-control skylearn-move-up"><span class="dashicons dashicons-arrow-up-alt"></span></button><button type="button" class="skylearn-block-control skylearn-move-down"><span class="dashicons dashicons-arrow-down-alt"></span></button><button type="button" class="skylearn-block-control skylearn-duplicate"><span class="dashicons dashicons-admin-page"></span></button><button type="button" class="skylearn-block-control skylearn-delete"><span class="dashicons dashicons-trash"></span></button></div></div>',
                text: '<div class="skylearn-email-block skylearn-text-block" data-block-type="text"><div class="skylearn-block-content"><p contenteditable="true" class="skylearn-editable">Enter your text here...</p></div><div class="skylearn-block-controls"><button type="button" class="skylearn-block-control skylearn-move-up"><span class="dashicons dashicons-arrow-up-alt"></span></button><button type="button" class="skylearn-block-control skylearn-move-down"><span class="dashicons dashicons-arrow-down-alt"></span></button><button type="button" class="skylearn-block-control skylearn-duplicate"><span class="dashicons dashicons-admin-page"></span></button><button type="button" class="skylearn-block-control skylearn-delete"><span class="dashicons dashicons-trash"></span></button></div></div>',
                button: '<div class="skylearn-email-block skylearn-button-block" data-block-type="button"><div class="skylearn-block-content"><div class="skylearn-button-wrapper" style="text-align: center; margin: 20px 0;"><a href="#" class="skylearn-email-button skylearn-editable" contenteditable="true" style="display: inline-block; background: #0073aa; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px;">Button Text</a></div></div><div class="skylearn-block-controls"><button type="button" class="skylearn-block-control skylearn-move-up"><span class="dashicons dashicons-arrow-up-alt"></span></button><button type="button" class="skylearn-block-control skylearn-move-down"><span class="dashicons dashicons-arrow-down-alt"></span></button><button type="button" class="skylearn-block-control skylearn-duplicate"><span class="dashicons dashicons-admin-page"></span></button><button type="button" class="skylearn-block-control skylearn-delete"><span class="dashicons dashicons-trash"></span></button></div></div>'
            };
            
            return templates[type] || templates.text;
        }

        /**
         * Apply block settings
         */
        applyBlockSettings($block, settings) {
            const blockType = $block.data('block-type');
            
            switch (blockType) {
                case 'header':
                    if (settings.content) {
                        $block.find('h1').text(settings.content);
                    }
                    if (settings.fontSize) {
                        $block.find('h1').css('font-size', settings.fontSize);
                    }
                    if (settings.color) {
                        $block.find('h1').css('color', settings.color);
                    }
                    if (settings.textAlign) {
                        $block.find('h1').css('text-align', settings.textAlign);
                    }
                    break;
                    
                case 'text':
                    if (settings.content) {
                        $block.find('p').html(settings.content);
                    }
                    if (settings.fontSize) {
                        $block.find('p').css('font-size', settings.fontSize);
                    }
                    if (settings.color) {
                        $block.find('p').css('color', settings.color);
                    }
                    if (settings.lineHeight) {
                        $block.find('p').css('line-height', settings.lineHeight);
                    }
                    break;
                    
                case 'button':
                    if (settings.text) {
                        $block.find('.skylearn-email-button').text(settings.text);
                    }
                    if (settings.url) {
                        $block.find('.skylearn-email-button').attr('href', settings.url);
                    }
                    if (settings.backgroundColor) {
                        $block.find('.skylearn-email-button').css('background', settings.backgroundColor);
                    }
                    if (settings.textColor) {
                        $block.find('.skylearn-email-button').css('color', settings.textColor);
                    }
                    if (settings.borderRadius) {
                        $block.find('.skylearn-email-button').css('border-radius', settings.borderRadius);
                    }
                    break;
            }
        }

        /**
         * Show placeholder
         */
        showPlaceholder() {
            const canvas = $('#skylearn-email-canvas');
            canvas.html(`
                <div class="skylearn-canvas-placeholder">
                    <div class="skylearn-placeholder-content">
                        <span class="dashicons dashicons-email-alt"></span>
                        <h4>Start Building Your Email</h4>
                        <p>Drag blocks from the sidebar to start creating your email template.</p>
                    </div>
                </div>
            `);
        }

        /**
         * Add new block
         */
        addBlock(blockType, insertIndex = null) {
            const newBlock = {
                type: blockType,
                settings: this.getDefaultBlockSettings(blockType)
            };

            if (insertIndex !== null) {
                this.blocks.splice(insertIndex, 0, newBlock);
            } else {
                this.blocks.push(newBlock);
            }

            this.renderBlocks();
            
            // Select the new block
            const newBlockElement = insertIndex !== null ? 
                $(`.skylearn-email-block[data-block-index="${insertIndex}"]`) :
                $(`.skylearn-email-block`).last();
            
            this.selectBlock(newBlockElement);
        }

        /**
         * Get default block settings
         */
        getDefaultBlockSettings(type) {
            const defaults = {
                header: {
                    content: 'Your Heading Here',
                    fontSize: '32px',
                    color: '#333333',
                    textAlign: 'left'
                },
                text: {
                    content: 'Enter your text here...',
                    fontSize: '16px',
                    color: '#333333',
                    lineHeight: '1.6'
                },
                button: {
                    text: 'Button Text',
                    url: '#',
                    backgroundColor: '#0073aa',
                    textColor: '#ffffff',
                    borderRadius: '4px'
                },
                image: {
                    src: '',
                    alt: '',
                    width: '100%',
                    align: 'center'
                },
                spacer: {
                    height: '40px'
                },
                divider: {
                    borderWidth: '1px',
                    borderStyle: 'solid',
                    borderColor: '#dddddd'
                }
            };

            return defaults[type] || {};
        }

        /**
         * Show drop zones
         */
        showDropZones() {
            if ($('.skylearn-drop-zone').length === 0) {
                $('#skylearn-email-canvas .skylearn-email-block').each(function() {
                    $(this).after('<div class="skylearn-drop-zone">Drop block here</div>');
                });
                
                if ($('#skylearn-email-canvas .skylearn-email-block').length === 0) {
                    $('#skylearn-email-canvas').html('<div class="skylearn-drop-zone active">Drop your first block here</div>');
                } else {
                    $('#skylearn-email-canvas').prepend('<div class="skylearn-drop-zone">Drop block here</div>');
                }
            }
            
            $('.skylearn-drop-zone').addClass('active');
        }

        /**
         * Hide drop zones
         */
        hideDropZones() {
            $('.skylearn-drop-zone').remove();
        }

        /**
         * Select block
         */
        selectBlock($block) {
            // Remove previous selection
            $('.skylearn-email-block').removeClass('selected');
            
            // Select new block
            $block.addClass('selected');
            this.selectedBlock = $block;
            
            // Show properties
            this.showBlockProperties($block);
        }

        /**
         * Show block properties
         */
        showBlockProperties($block) {
            const blockType = $block.data('block-type');
            const blockIndex = $block.data('block-index');
            const blockData = this.blocks[blockIndex];
            
            const propertyTemplate = $(`#skylearn-property-templates .skylearn-property-template[data-block="${blockType}"]`).html();
            
            if (propertyTemplate) {
                const $properties = $('.skylearn-properties-content');
                $properties.html(propertyTemplate);
                
                // Populate current values
                this.populatePropertyValues($properties, blockData.settings || {});
            }
        }

        /**
         * Populate property values
         */
        populatePropertyValues($container, settings) {
            $container.find('.skylearn-property-input').each(function() {
                const $input = $(this);
                const property = $input.data('property');
                
                if (settings[property]) {
                    $input.val(settings[property]);
                }
            });
        }

        /**
         * Update block order
         */
        updateBlockOrder() {
            const newOrder = [];
            $('#skylearn-email-canvas .skylearn-email-block').each((index, element) => {
                const blockIndex = $(element).data('block-index');
                newOrder.push(this.blocks[blockIndex]);
            });
            
            this.blocks = newOrder;
            this.renderBlocks();
        }

        /**
         * Event Handlers
         */
        handleBack(e) {
            e.preventDefault();
            if (confirm('Are you sure you want to go back? Any unsaved changes will be lost.')) {
                $('#skylearn-email-builder').hide();
                // Show main email management interface
                $('.skylearn-email-templates-list').show();
            }
        }

        handleSave(e) {
            e.preventDefault();
            this.saveTemplate();
        }

        handlePreview(e) {
            e.preventDefault();
            this.showPreview();
        }

        handleTestEmail(e) {
            e.preventDefault();
            $('#skylearn-test-email-modal').show();
        }

        handleDeviceChange(e) {
            e.preventDefault();
            const device = $(e.currentTarget).data('device');
            
            $('.skylearn-device-btn').removeClass('active');
            $(e.currentTarget).addClass('active');
            
            $('.skylearn-canvas-wrapper').attr('data-device', device);
        }

        handleBlockSelect(e) {
            e.stopPropagation();
            this.selectBlock($(e.currentTarget));
        }

        handleContentEdit(e) {
            const $block = $(e.target).closest('.skylearn-email-block');
            const blockIndex = $block.data('block-index');
            const blockData = this.blocks[blockIndex];
            
            if (blockData) {
                blockData.settings = blockData.settings || {};
                blockData.settings.content = $(e.target).html();
            }
        }

        handleContentBlur(e) {
            // Content editing finished
            this.updateBlockFromElement($(e.target).closest('.skylearn-email-block'));
        }

        handleMoveUp(e) {
            e.stopPropagation();
            const $block = $(e.target).closest('.skylearn-email-block');
            const blockIndex = $block.data('block-index');
            
            if (blockIndex > 0) {
                const temp = this.blocks[blockIndex];
                this.blocks[blockIndex] = this.blocks[blockIndex - 1];
                this.blocks[blockIndex - 1] = temp;
                this.renderBlocks();
            }
        }

        handleMoveDown(e) {
            e.stopPropagation();
            const $block = $(e.target).closest('.skylearn-email-block');
            const blockIndex = $block.data('block-index');
            
            if (blockIndex < this.blocks.length - 1) {
                const temp = this.blocks[blockIndex];
                this.blocks[blockIndex] = this.blocks[blockIndex + 1];
                this.blocks[blockIndex + 1] = temp;
                this.renderBlocks();
            }
        }

        handleDuplicate(e) {
            e.stopPropagation();
            const $block = $(e.target).closest('.skylearn-email-block');
            const blockIndex = $block.data('block-index');
            const blockData = JSON.parse(JSON.stringify(this.blocks[blockIndex]));
            
            this.blocks.splice(blockIndex + 1, 0, blockData);
            this.renderBlocks();
        }

        handleDelete(e) {
            e.stopPropagation();
            if (confirm('Are you sure you want to delete this block?')) {
                const $block = $(e.target).closest('.skylearn-email-block');
                const blockIndex = $block.data('block-index');
                
                this.blocks.splice(blockIndex, 1);
                this.renderBlocks();
                
                // Clear properties panel
                $('.skylearn-properties-content').html(`
                    <div class="skylearn-no-selection">
                        <span class="dashicons dashicons-admin-settings"></span>
                        <p>Select a block to edit its properties</p>
                    </div>
                `);
            }
        }

        handlePropertyChange(e) {
            if (!this.selectedBlock) return;
            
            const blockIndex = this.selectedBlock.data('block-index');
            const blockData = this.blocks[blockIndex];
            const property = $(e.target).data('property');
            const value = $(e.target).val();
            
            if (blockData) {
                blockData.settings = blockData.settings || {};
                blockData.settings[property] = value;
                
                // Update the visual block
                this.applyBlockSettings(this.selectedBlock, blockData.settings);
            }
        }

        handleImageSelect(e) {
            e.preventDefault();
            
            // WordPress media uploader
            if (typeof wp !== 'undefined' && wp.media) {
                const mediaUploader = wp.media({
                    title: 'Select Image',
                    button: {
                        text: 'Use this image'
                    },
                    multiple: false
                });
                
                mediaUploader.on('select', () => {
                    const attachment = mediaUploader.state().get('selection').first().toJSON();
                    const $input = $(e.target).siblings('.skylearn-property-input[data-property="src"]');
                    $input.val(attachment.url).trigger('change');
                });
                
                mediaUploader.open();
            }
        }

        handleModalClose(e) {
            e.preventDefault();
            $(e.target).closest('.skylearn-modal').hide();
        }

        handleSendTestEmail(e) {
            e.preventDefault();
            
            const email = $('#test-email-address').val();
            if (!this.isValidEmail(email)) {
                alert('Please enter a valid email address.');
                return;
            }
            
            const $btn = $(e.target);
            $btn.prop('disabled', true).text('Sending...');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'skylearn_send_test_email',
                    nonce: this.getNonce('skylearn_email_test'),
                    email: email,
                    type: this.currentEmailType
                },
                success: (response) => {
                    if (response.success) {
                        alert('Test email sent successfully!');
                        $('#skylearn-test-email-modal').hide();
                    } else {
                        alert('Error: ' + response.data);
                    }
                },
                error: () => {
                    alert('Failed to send test email. Please try again.');
                },
                complete: () => {
                    $btn.prop('disabled', false).text('Send Test Email');
                }
            });
        }

        handleTokenClick(e) {
            e.preventDefault();
            const token = $(e.target).closest('.skylearn-token-item').data('token');
            
            // Insert token at cursor position if editing
            const activeElement = document.activeElement;
            if ($(activeElement).hasClass('skylearn-editable')) {
                document.execCommand('insertText', false, token);
            }
        }

        handleSubjectChange(e) {
            // Subject changed - no immediate action needed
        }

        handleKeyDown(e) {
            // Handle keyboard shortcuts
            if (e.ctrlKey || e.metaKey) {
                switch (e.keyCode) {
                    case 83: // Ctrl+S
                        e.preventDefault();
                        this.saveTemplate();
                        break;
                }
            }
            
            // Delete selected block
            if (e.keyCode === 46 && this.selectedBlock) { // Delete key
                if (!$(document.activeElement).hasClass('skylearn-editable')) {
                    e.preventDefault();
                    this.handleDelete({ target: this.selectedBlock.find('.skylearn-delete') });
                }
            }
        }

        /**
         * Save template
         */
        saveTemplate() {
            const $saveBtn = $('#skylearn-builder-save');
            $saveBtn.prop('disabled', true).html('<span class="dashicons dashicons-update"></span> Saving...');
            
            const templateData = {
                action: 'skylearn_email_builder_save',
                nonce: this.getNonce('skylearn_email_builder'),
                email_type: this.currentEmailType,
                language: this.currentLanguage,
                subject: $('#email-subject').val(),
                blocks: JSON.stringify(this.blocks)
            };
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: templateData,
                success: (response) => {
                    if (response.success) {
                        this.showSuccess('Template saved successfully!');
                    } else {
                        this.showError('Failed to save template: ' + response.data);
                    }
                },
                error: () => {
                    this.showError('Failed to save template. Please try again.');
                },
                complete: () => {
                    $saveBtn.prop('disabled', false).html('<span class="dashicons dashicons-saved"></span> Save Template');
                }
            });
        }

        /**
         * Show preview
         */
        showPreview() {
            const previewData = {
                action: 'skylearn_email_builder_preview',
                nonce: this.getNonce('skylearn_email_builder'),
                email_type: this.currentEmailType,
                subject: $('#email-subject').val(),
                blocks: JSON.stringify(this.blocks)
            };
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: previewData,
                success: (response) => {
                    if (response.success) {
                        const previewContent = `
                            <!DOCTYPE html>
                            <html>
                            <head>
                                <meta charset="utf-8">
                                <meta name="viewport" content="width=device-width, initial-scale=1">
                                <title>${response.data.subject}</title>
                                <style>
                                    body { font-family: Arial, sans-serif; line-height: 1.6; margin: 0; padding: 20px; background: #f4f4f4; }
                                    .email-container { max-width: 600px; margin: 0 auto; background: #fff; padding: 20px; border-radius: 8px; }
                                </style>
                            </head>
                            <body>
                                <div class="email-container">
                                    <h2 style="margin-top: 0; color: #333; border-bottom: 1px solid #eee; padding-bottom: 10px;">Subject: ${response.data.subject}</h2>
                                    ${response.data.content}
                                </div>
                            </body>
                            </html>
                        `;
                        
                        const $frame = $('#skylearn-preview-frame');
                        $frame.contents().find('html').html(previewContent);
                        $('#skylearn-preview-modal').show();
                    } else {
                        this.showError('Failed to generate preview: ' + response.data);
                    }
                },
                error: () => {
                    this.showError('Failed to generate preview. Please try again.');
                }
            });
        }

        /**
         * Update block from element
         */
        updateBlockFromElement($block) {
            const blockIndex = $block.data('block-index');
            const blockData = this.blocks[blockIndex];
            
            if (!blockData) return;
            
            blockData.settings = blockData.settings || {};
            
            // Extract current values from the block element
            const blockType = blockData.type;
            
            switch (blockType) {
                case 'header':
                    blockData.settings.content = $block.find('h1').text();
                    break;
                case 'text':
                    blockData.settings.content = $block.find('p').html();
                    break;
                case 'button':
                    blockData.settings.text = $block.find('.skylearn-email-button').text();
                    blockData.settings.url = $block.find('.skylearn-email-button').attr('href');
                    break;
            }
        }

        /**
         * Utility methods
         */
        getNonce(action) {
            return window.skylearn_email_builder_nonces ? window.skylearn_email_builder_nonces[action] : '';
        }

        isValidEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }

        showSuccess(message) {
            // Simple success notification
            const $notice = $('<div class="notice notice-success is-dismissible"><p>' + message + '</p></div>');
            $('.skylearn-builder-toolbar').after($notice);
            setTimeout(() => $notice.fadeOut(), 3000);
        }

        showError(message) {
            // Simple error notification
            const $notice = $('<div class="notice notice-error is-dismissible"><p>' + message + '</p></div>');
            $('.skylearn-builder-toolbar').after($notice);
            setTimeout(() => $notice.fadeOut(), 5000);
        }
    }

    /**
     * Initialize email builder when DOM is ready
     */
    $(document).ready(function() {
        if ($('#skylearn-email-builder').length > 0) {
            window.skyLearnEmailBuilder = new SkyLearnEmailBuilder();
        }
    });

})(jQuery);