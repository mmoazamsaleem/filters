<?php
/**
 * Search Filter Widget Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class PGS_Search_Filter_Widget extends WP_Widget {
    
    public function __construct() {
        parent::__construct(
            'pgs_search_filter',
            __('Posts Search Filter', 'posts-grid-search'),
            array(
                'description' => __('Search filter for Posts Grid widget.', 'posts-grid-search'),
                'classname' => 'pgs-search-filter-widget'
            )
        );
    }
    
    public function widget($args, $instance) {
        echo $args['before_widget'];
        
        if (!empty($instance['title'])) {
            echo $args['before_title'] . apply_filters('widget_title', $instance['title']) . $args['after_title'];
        }
        
        $this->render_search_form($instance);
        
        echo $args['after_widget'];
    }
    
    private function render_search_form($instance) {
        $defaults = array(
            'placeholder' => __('Search posts...', 'posts-grid-search'),
            'search_bg' => '#ffffff',
            'search_text_color' => '#333333',
            'search_border_color' => '#dddddd',
            'button_bg' => '#0073aa',
            'button_text_color' => '#ffffff',
            'target_widget' => '',
            'show_button' => true,
            'button_text' => __('Search', 'posts-grid-search')
        );
        
        $instance = wp_parse_args($instance, $defaults);
        
        ?>
        <div class="pgs-search-filter" style="--search-bg: <?php echo esc_attr($instance['search_bg']); ?>; --search-text: <?php echo esc_attr($instance['search_text_color']); ?>; --search-border: <?php echo esc_attr($instance['search_border_color']); ?>; --button-bg: <?php echo esc_attr($instance['button_bg']); ?>; --button-text: <?php echo esc_attr($instance['button_text_color']); ?>;">
            <div class="pgs-search-container">
                <div class="pgs-search-input-wrapper">
                    <svg class="pgs-search-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <path d="m21 21-4.35-4.35"></path>
                    </svg>
                    <input 
                        type="text" 
                        id="pgs-search-input" 
                        class="pgs-search-input" 
                        placeholder="<?php echo esc_attr($instance['placeholder']); ?>"
                        data-target-widget="<?php echo esc_attr($instance['target_widget']); ?>"
                    >
                    <button type="button" class="pgs-search-clear" id="pgs-search-clear" style="display: none;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </div>
                <?php if ($instance['show_button']): ?>
                <button type="button" class="pgs-search-button" id="pgs-search-button">
                    <?php echo esc_html($instance['button_text']); ?>
                </button>
                <?php endif; ?>
            </div>
            
            <div class="pgs-search-results-info" id="pgs-search-results-info" style="display: none;">
                <span id="pgs-results-count"></span>
                <button type="button" class="pgs-clear-search" id="pgs-clear-search">
                    <?php _e('Clear search', 'posts-grid-search'); ?>
                </button>
            </div>
        </div>
        <?php
    }
    
    public function form($instance) {
        $defaults = array(
            'title' => '',
            'placeholder' => __('Search posts...', 'posts-grid-search'),
            'target_widget' => '',
            'search_bg' => '#ffffff',
            'search_text_color' => '#333333',
            'search_border_color' => '#dddddd',
            'button_bg' => '#0073aa',
            'button_text_color' => '#ffffff',
            'show_button' => true,
            'button_text' => __('Search', 'posts-grid-search')
        );
        
        $instance = wp_parse_args((array) $instance, $defaults);
        ?>
        <div class="pgs-widget-form">
            <div class="pgs-widget-tabs">
                <div class="pgs-tab-nav">
                    <button type="button" class="pgs-tab-button active" data-tab="content">
                        <span class="dashicons dashicons-edit"></span>
                        <?php _e('Content', 'posts-grid-search'); ?>
                    </button>
                    <button type="button" class="pgs-tab-button" data-tab="style">
                        <span class="dashicons dashicons-admin-appearance"></span>
                        <?php _e('Style', 'posts-grid-search'); ?>
                    </button>
                    <button type="button" class="pgs-tab-button" data-tab="advanced">
                        <span class="dashicons dashicons-admin-settings"></span>
                        <?php _e('Advanced', 'posts-grid-search'); ?>
                    </button>
                </div>
                
                <!-- Content Tab -->
                <div class="pgs-tab-content active" data-tab="content">
                    <div class="pgs-form-group">
                        <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Widget Title', 'posts-grid-search'); ?></label>
                        <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($instance['title']); ?>">
                    </div>
                    
                    <div class="pgs-form-group">
                        <label for="<?php echo $this->get_field_id('placeholder'); ?>"><?php _e('Placeholder Text', 'posts-grid-search'); ?></label>
                        <input class="widefat" id="<?php echo $this->get_field_id('placeholder'); ?>" name="<?php echo $this->get_field_name('placeholder'); ?>" type="text" value="<?php echo esc_attr($instance['placeholder']); ?>">
                    </div>
                    
                    <div class="pgs-form-group">
                        <label class="pgs-checkbox-label">
                            <input type="checkbox" <?php checked($instance['show_button'], true); ?> id="<?php echo $this->get_field_id('show_button'); ?>" name="<?php echo $this->get_field_name('show_button'); ?>">
                            <?php _e('Show Search Button', 'posts-grid-search'); ?>
                        </label>
                    </div>
                    
                    <div class="pgs-form-group pgs-button-text-group" style="<?php echo !$instance['show_button'] ? 'display: none;' : ''; ?>">
                        <label for="<?php echo $this->get_field_id('button_text'); ?>"><?php _e('Button Text', 'posts-grid-search'); ?></label>
                        <input class="widefat" id="<?php echo $this->get_field_id('button_text'); ?>" name="<?php echo $this->get_field_name('button_text'); ?>" type="text" value="<?php echo esc_attr($instance['button_text']); ?>">
                    </div>
                </div>
                
                <!-- Style Tab -->
                <div class="pgs-tab-content" data-tab="style">
                    <div class="pgs-color-section">
                        <h4><?php _e('Search Input Colors', 'posts-grid-search'); ?></h4>
                        <div class="pgs-form-row">
                            <div class="pgs-form-group pgs-form-third">
                                <label for="<?php echo $this->get_field_id('search_bg'); ?>"><?php _e('Background', 'posts-grid-search'); ?></label>
                                <div class="pgs-color-picker-wrapper">
                                    <input class="pgs-color-input" id="<?php echo $this->get_field_id('search_bg'); ?>" name="<?php echo $this->get_field_name('search_bg'); ?>" type="color" value="<?php echo esc_attr($instance['search_bg']); ?>">
                                    <div class="pgs-color-preview" style="background-color: <?php echo esc_attr($instance['search_bg']); ?>;"></div>
                                </div>
                            </div>
                            <div class="pgs-form-group pgs-form-third">
                                <label for="<?php echo $this->get_field_id('search_text_color'); ?>"><?php _e('Text', 'posts-grid-search'); ?></label>
                                <div class="pgs-color-picker-wrapper">
                                    <input class="pgs-color-input" id="<?php echo $this->get_field_id('search_text_color'); ?>" name="<?php echo $this->get_field_name('search_text_color'); ?>" type="color" value="<?php echo esc_attr($instance['search_text_color']); ?>">
                                    <div class="pgs-color-preview" style="background-color: <?php echo esc_attr($instance['search_text_color']); ?>;"></div>
                                </div>
                            </div>
                            <div class="pgs-form-group pgs-form-third">
                                <label for="<?php echo $this->get_field_id('search_border_color'); ?>"><?php _e('Border', 'posts-grid-search'); ?></label>
                                <div class="pgs-color-picker-wrapper">
                                    <input class="pgs-color-input" id="<?php echo $this->get_field_id('search_border_color'); ?>" name="<?php echo $this->get_field_name('search_border_color'); ?>" type="color" value="<?php echo esc_attr($instance['search_border_color']); ?>">
                                    <div class="pgs-color-preview" style="background-color: <?php echo esc_attr($instance['search_border_color']); ?>;"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="pgs-color-section pgs-button-colors" style="<?php echo !$instance['show_button'] ? 'display: none;' : ''; ?>">
                        <h4><?php _e('Button Colors', 'posts-grid-search'); ?></h4>
                        <div class="pgs-form-row">
                            <div class="pgs-form-group pgs-form-half">
                                <label for="<?php echo $this->get_field_id('button_bg'); ?>"><?php _e('Background', 'posts-grid-search'); ?></label>
                                <div class="pgs-color-picker-wrapper">
                                    <input class="pgs-color-input" id="<?php echo $this->get_field_id('button_bg'); ?>" name="<?php echo $this->get_field_name('button_bg'); ?>" type="color" value="<?php echo esc_attr($instance['button_bg']); ?>">
                                    <div class="pgs-color-preview" style="background-color: <?php echo esc_attr($instance['button_bg']); ?>;"></div>
                                </div>
                            </div>
                            <div class="pgs-form-group pgs-form-half">
                                <label for="<?php echo $this->get_field_id('button_text_color'); ?>"><?php _e('Text', 'posts-grid-search'); ?></label>
                                <div class="pgs-color-picker-wrapper">
                                    <input class="pgs-color-input" id="<?php echo $this->get_field_id('button_text_color'); ?>" name="<?php echo $this->get_field_name('button_text_color'); ?>" type="color" value="<?php echo esc_attr($instance['button_text_color']); ?>">
                                    <div class="pgs-color-preview" style="background-color: <?php echo esc_attr($instance['button_text_color']); ?>;"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Advanced Tab -->
                <div class="pgs-tab-content" data-tab="advanced">
                    <div class="pgs-form-group">
                        <label for="<?php echo $this->get_field_id('target_widget'); ?>"><?php _e('Target Widget ID', 'posts-grid-search'); ?></label>
                        <input class="widefat" id="<?php echo $this->get_field_id('target_widget'); ?>" name="<?php echo $this->get_field_name('target_widget'); ?>" type="text" value="<?php echo esc_attr($instance['target_widget']); ?>" placeholder="<?php _e('Leave empty to target all grids', 'posts-grid-search'); ?>">
                        <small><?php _e('Enter the Widget ID from the Posts Grid widget to target a specific grid.', 'posts-grid-search'); ?></small>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    public function update($new_instance, $old_instance) {
        $instance = array();
        
        $instance['title'] = sanitize_text_field($new_instance['title']);
        $instance['placeholder'] = sanitize_text_field($new_instance['placeholder']);
        $instance['target_widget'] = sanitize_text_field($new_instance['target_widget']);
        $instance['search_bg'] = sanitize_hex_color($new_instance['search_bg']);
        $instance['search_text_color'] = sanitize_hex_color($new_instance['search_text_color']);
        $instance['search_border_color'] = sanitize_hex_color($new_instance['search_border_color']);
        $instance['button_bg'] = sanitize_hex_color($new_instance['button_bg']);
        $instance['button_text_color'] = sanitize_hex_color($new_instance['button_text_color']);
        $instance['show_button'] = !empty($new_instance['show_button']);
        $instance['button_text'] = sanitize_text_field($new_instance['button_text']);
        
        return $instance;
    }
}