jQuery(document).ready(function($) {
    'use strict';
    
    // Initialize admin functionality
    initWidgetTabs();
    initColorPickers();
    initConditionalFields();
    initTemplatePreview();
    
    function initWidgetTabs() {
        // Tab switching
        $(document).on('click', '.pgs-tab-button', function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var $widget = $button.closest('.pgs-widget-form');
            var targetTab = $button.data('tab');
            
            // Update active states
            $widget.find('.pgs-tab-button').removeClass('active');
            $widget.find('.pgs-tab-content').removeClass('active');
            
            $button.addClass('active');
            $widget.find('.pgs-tab-content[data-tab="' + targetTab + '"]').addClass('active').addClass('pgs-fade-in');
            
            // Remove animation class after animation completes
            setTimeout(function() {
                $widget.find('.pgs-tab-content').removeClass('pgs-fade-in');
            }, 300);
        });
    }
    
    function initColorPickers() {
        // Initialize color pickers on widget load and update
        $(document).on('widget-added widget-updated', function() {
            setupColorPickers();
        });
        
        // Initialize on page load
        setupColorPickers();
        
        function setupColorPickers() {
            $('.pgs-color-input').each(function() {
                var $input = $(this);
                var $preview = $input.siblings('.pgs-color-preview');
                
                if ($preview.length === 0) {
                    $input.after('<div class="pgs-color-preview" style="background-color: ' + $input.val() + ';"></div>');
                    $preview = $input.siblings('.pgs-color-preview');
                }
                
                // Update preview on change
                $input.off('change.pgs').on('change.pgs', function() {
                    var color = $(this).val();
                    $preview.css('background-color', color);
                    
                    // Trigger live preview update
                    updateLivePreview($(this));
                });
                
                // Click preview to open color picker
                $preview.off('click.pgs').on('click.pgs', function() {
                    $input.trigger('click');
                });
            });
        }
    }
    
    function initConditionalFields() {
        // Template-based field visibility
        $(document).on('change', 'select[id*="template"]', function() {
            var $select = $(this);
            var $widget = $select.closest('.pgs-widget-form');
            var template = $select.val();
            
            // Show/hide template-specific fields
            $widget.find('.pgs-elementor-template-group').toggle(template === 'elementor');
            $widget.find('.pgs-card-template-group').toggle(template === 'card');
            
            updateTemplatePreview($select);
        });
        
        // Pagination settings visibility
        $(document).on('change', 'input[id*="show_pagination"]', function() {
            var $checkbox = $(this);
            var $widget = $checkbox.closest('.pgs-widget-form');
            var isChecked = $checkbox.is(':checked');
            
            $widget.find('.pgs-pagination-settings').toggle(isChecked);
        });
        
        // Search button visibility
        $(document).on('change', 'input[id*="show_button"]', function() {
            var $checkbox = $(this);
            var $widget = $checkbox.closest('.pgs-widget-form');
            var isChecked = $checkbox.is(':checked');
            
            $widget.find('.pgs-button-text-group, .pgs-button-colors').toggle(isChecked);
        });
        
        // Initialize on page load
        $('select[id*="template"]').trigger('change');
        $('input[id*="show_pagination"]').trigger('change');
        $('input[id*="show_button"]').trigger('change');
    }
    
    function initTemplatePreview() {
        $(document).on('change', 'select[id*="template"], select[id*="elementor_template"]', function() {
            updateTemplatePreview($(this));
        });
        
        // Initialize previews
        $('select[id*="template"]').each(function() {
            updateTemplatePreview($(this));
        });
    }
    
    function updateTemplatePreview($select) {
        var $widget = $select.closest('.pgs-widget-form');
        var template = $select.val();
        var $preview = $widget.find('.pgs-template-preview');
        
        // Create preview container if it doesn't exist
        if ($preview.length === 0) {
            $select.closest('.pgs-form-group').append('<div class="pgs-template-preview"></div>');
            $preview = $widget.find('.pgs-template-preview');
        }
        
        var previewHtml = '<h5>Template Preview:</h5>';
        
        switch (template) {
            case 'card':
                previewHtml += '<div style="border: 1px solid #e1e5e9; border-radius: 8px; padding: 12px; background: #fff; max-width: 200px;">' +
                    '<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); height: 80px; margin-bottom: 10px; border-radius: 4px;"></div>' +
                    '<div style="font-weight: 600; margin-bottom: 6px; font-size: 13px; color: #1a202c;">Sample Post Title</div>' +
                    '<div style="font-size: 11px; color: #4a5568; margin-bottom: 8px; line-height: 1.4;">This is a sample excerpt that shows how the post content will appear...</div>' +
                    '<div style="font-size: 10px; color: #718096; display: flex; justify-content: space-between;">' +
                    '<span>By Author</span><span>Jan 15, 2025</span></div>' +
                    '</div>';
                break;
            case 'elementor':
                var elementorTemplate = $widget.find('select[id*="elementor_template"]').val();
                if (elementorTemplate) {
                    previewHtml += '<div style="border: 1px solid #e1e5e9; border-radius: 8px; padding: 15px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-align: center;">' +
                        '<div style="font-size: 12px; opacity: 0.9;">Elementor Template</div>' +
                        '<div style="font-weight: 600; margin-top: 4px;">Custom Design</div>' +
                        '</div>';
                } else {
                    previewHtml += '<div style="border: 2px dashed #ddd; border-radius: 8px; padding: 20px; text-align: center; color: #666; font-size: 12px;">' +
                        'Select an Elementor template to preview' +
                        '</div>';
                }
                break;
        }
        
        $preview.html(previewHtml);
    }
    
    function updateLivePreview($input) {
        var inputId = $input.attr('id');
        var value = $input.val();
        
        // Add visual feedback for changes
        $input.addClass('pgs-field-changed');
        setTimeout(function() {
            $input.removeClass('pgs-field-changed');
        }, 1000);
    }
    
    // Enhanced widget save feedback
    $(document).on('click', '.widget-control-save', function() {
        var $widget = $(this).closest('.widget');
        var $form = $widget.find('.pgs-widget-form');
        
        $form.addClass('pgs-saving');
        
        // Show success feedback
        setTimeout(function() {
            $form.removeClass('pgs-saving');
            
            // Add success indicator
            var $saveBtn = $widget.find('.widget-control-save');
            var originalText = $saveBtn.text();
            $saveBtn.text('Saved!').css('color', '#46b450');
            
            setTimeout(function() {
                $saveBtn.text(originalText).css('color', '');
            }, 2000);
        }, 1000);
    });
    
    // Add field change animations
    $('<style>.pgs-field-changed { background-color: #fff3cd !important; transition: background-color 0.3s ease; }</style>').appendTo('head');
    
    // Enhanced help tooltips
    initEnhancedTooltips();
    
    function initEnhancedTooltips() {
        // Add tooltips to complex fields
        var tooltips = {
            'Widget ID': 'Use this unique identifier to target this specific widget with search filters. Leave empty if you don\'t need targeting.',
            'Target Widget ID': 'Enter the Widget ID from a Posts Grid widget to make this search filter control that specific grid only.',
            'Elementor Template': 'Choose from your saved Elementor templates. Make sure the template is designed for post content.',
            'Posts per Page': 'Number of posts to display per page. Higher numbers may affect page loading speed.'
        };
        
        $.each(tooltips, function(labelText, tooltipText) {
            $('label:contains("' + labelText + '")').each(function() {
                var $label = $(this);
                if ($label.find('.pgs-help-icon').length === 0) {
                    $label.append(' <span class="pgs-help-icon" title="' + tooltipText + '">?</span>');
                }
            });
        });
    }
});