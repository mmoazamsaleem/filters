<?php
/**
 * Posts Grid Widget Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class PGS_Posts_Grid_Widget extends WP_Widget {
    
    private $template_manager;
    
    public function __construct() {
        parent::__construct(
            'pgs_posts_grid',
            __('Posts Grid', 'posts-grid-search'),
            array(
                'description' => __('Display posts in customizable grid layout with pagination.', 'posts-grid-search'),
                'classname' => 'pgs-posts-grid-widget'
            )
        );
        
        $this->template_manager = new PGS_Template_Manager();
    }
    
    public function widget($args, $instance) {
        echo $args['before_widget'];
        
        if (!empty($instance['title'])) {
            echo $args['before_title'] . apply_filters('widget_title', $instance['title']) . $args['after_title'];
        }
        
        $this->render_posts_grid($instance);
        
        echo $args['after_widget'];
    }
    
    private function render_posts_grid($instance) {
        $template = !empty($instance['template']) ? $instance['template'] : 'card';
        $columns = !empty($instance['columns']) ? intval($instance['columns']) : 3;
        $posts_per_page = !empty($instance['posts_per_page']) ? intval($instance['posts_per_page']) : 6;
        $show_pagination = !empty($instance['show_pagination']);
        
        // Get current page
        $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
        
        $query_args = array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => $posts_per_page,
            'paged' => $paged
        );
        
        $posts_query = new WP_Query($query_args);
        
        echo '<div class="pgs-posts-grid" data-template="' . esc_attr($template) . '" data-columns="' . esc_attr($columns) . '" data-posts-per-page="' . esc_attr($posts_per_page) . '" data-widget-instance="' . esc_attr(json_encode($instance)) . '">';
        echo '<div class="pgs-posts-container pgs-template-' . esc_attr($template) . ' pgs-columns-' . esc_attr($columns) . '">';
        
        if ($posts_query->have_posts()) {
            while ($posts_query->have_posts()) {
                $posts_query->the_post();
                $this->template_manager->render_post($template, $instance);
            }
        } else {
            echo '<div class="pgs-no-posts">' . __('No posts found.', 'posts-grid-search') . '</div>';
        }
        
        echo '</div>'; // .pgs-posts-container
        
        // Pagination
        if ($show_pagination && $posts_query->max_num_pages > 1) {
            $this->render_pagination($posts_query, $instance);
        }
        
        echo '</div>'; // .pgs-posts-grid
        
        wp_reset_postdata();
    }
    
    private function render_pagination($query, $instance) {
        $current_page = max(1, get_query_var('paged'));
        $total_pages = $query->max_num_pages;
        $pagination_style = !empty($instance['pagination_style']) ? $instance['pagination_style'] : 'numbers';
        $prev_icon = !empty($instance['prev_icon']) ? $instance['prev_icon'] : '←';
        $next_icon = !empty($instance['next_icon']) ? $instance['next_icon'] : '→';
        
        $pagination_bg = !empty($instance['pagination_bg']) ? $instance['pagination_bg'] : '#1a202c';
        $pagination_active_color = !empty($instance['pagination_active_color']) ? $instance['pagination_active_color'] : '#14b8a6';
        $pagination_text_color = !empty($instance['pagination_text_color']) ? $instance['pagination_text_color'] : '#ffffff';

        echo '<div class="pgs-pagination" style="--pagination-bg: ' . esc_attr($pagination_bg) . '; --pagination-active: ' . esc_attr($pagination_active_color) . '; --pagination-text: ' . esc_attr($pagination_text_color) . ';">';

        if ($pagination_style === 'numbers') {
            $links = paginate_links(array(
                'base' => get_pagenum_link(1) . '%_%',
                'format' => 'page/%#%/',
                'current' => $current_page,
                'total' => $total_pages,
                'mid_size' => 1,
                'end_size' => 1,
                'prev_text' => $prev_icon,
                'next_text' => $next_icon,
                'type' => 'array',
            ));

            if (!empty($links)) {
                foreach ($links as $link) {
                    $link = str_replace('page-numbers', 'pgs-pagination-btn', $link);
                    $link = str_replace('current', 'pgs-pagination-current', $link);
                    echo $link;
                }
            }
        } elseif ($pagination_style === 'simple') {
            echo '<div class="pgs-pagination-simple">';
            if ($current_page > 1) {
                echo '<a href="' . esc_url(get_pagenum_link($current_page - 1)) . '" class="pgs-pagination-btn">' . __('Previous', 'posts-grid-search') . '</a>';
            }
            echo '<span class="pgs-pagination-info">' . sprintf(__('Page %d of %d', 'posts-grid-search'), $current_page, $total_pages) . '</span>';
            if ($current_page < $total_pages) {
                echo '<a href="' . esc_url(get_pagenum_link($current_page + 1)) . '" class="pgs-pagination-btn">' . __('Next', 'posts-grid-search') . '</a>';
            }
            echo '</div>';
        } elseif ($pagination_style === 'arrows') {
            if ($current_page > 1) {
                echo '<a href="' . esc_url(get_pagenum_link($current_page - 1)) . '" class="pgs-pagination-btn pgs-pagination-arrow">' . esc_html($prev_icon) . '</a>';
            }
            if ($current_page < $total_pages) {
                echo '<a href="' . esc_url(get_pagenum_link($current_page + 1)) . '" class="pgs-pagination-btn pgs-pagination-arrow">' . esc_html($next_icon) . '</a>';
            }
        }

        echo '</div>';
    }
    
    public function form($instance) {
        // Set default values
        $defaults = array(
            'title' => '',
            'template' => 'card',
            'elementor_template' => '',
            'columns' => '3',
            'posts_per_page' => '6',
            'show_pagination' => false,
            'pagination_style' => 'numbers',
            'prev_icon' => '←',
            'next_icon' => '→',
            'pagination_bg' => '#1a202c',
            'pagination_active_color' => '#14b8a6',
            'pagination_text_color' => '#ffffff',
            'show_thumbnail' => true,
            'show_excerpt' => true,
            'show_author' => true,
            'show_date' => true,
        );
        
        $instance = wp_parse_args((array) $instance, $defaults);
        $available_templates = $this->template_manager->get_available_templates();
        $elementor_templates = $this->template_manager->get_elementor_templates();
        ?>
        <div class="pgs-widget-form">
            <!-- Content Tab -->
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
                        <label for="<?php echo $this->get_field_id('template'); ?>"><?php _e('Template', 'posts-grid-search'); ?></label>
                        <select class="widefat" id="<?php echo $this->get_field_id('template'); ?>" name="<?php echo $this->get_field_name('template'); ?>">
                            <?php foreach ($available_templates as $value => $label): ?>
                                <option value="<?php echo esc_attr($value); ?>" <?php selected($instance['template'], $value); ?>>
                                    <?php echo esc_html($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <?php if (!empty($elementor_templates)): ?>
                    <div class="pgs-form-group pgs-elementor-template-group" style="<?php echo $instance['template'] !== 'elementor' ? 'display: none;' : ''; ?>">
                        <label for="<?php echo $this->get_field_id('elementor_template'); ?>"><?php _e('Select Elementor Template', 'posts-grid-search'); ?></label>
                        <select class="widefat" id="<?php echo $this->get_field_id('elementor_template'); ?>" name="<?php echo $this->get_field_name('elementor_template'); ?>">
                            <option value=""><?php _e('Select a template...', 'posts-grid-search'); ?></option>
                            <?php foreach ($elementor_templates as $template_id => $template_name): ?>
                                <option value="<?php echo esc_attr($template_id); ?>" <?php selected($instance['elementor_template'], $template_id); ?>>
                                    <?php echo esc_html($template_name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    
                    <div class="pgs-form-group">
                        <label for="<?php echo $this->get_field_id('posts_per_page'); ?>"><?php _e('Posts per Page', 'posts-grid-search'); ?></label>
                        <input class="widefat" id="<?php echo $this->get_field_id('posts_per_page'); ?>" name="<?php echo $this->get_field_name('posts_per_page'); ?>" type="number" value="<?php echo esc_attr($instance['posts_per_page']); ?>" min="1" max="50">
                    </div>
                    
                    <div class="pgs-form-group pgs-card-template-group" style="<?php echo $instance['template'] !== 'card' ? 'display: none;' : ''; ?>">
                        <label><?php _e('Show Elements', 'posts-grid-search'); ?></label>
                        <div class="pgs-checkbox-group">
                            <label class="pgs-checkbox-label">
                                <input type="checkbox" <?php checked($instance['show_thumbnail'], true); ?> id="<?php echo $this->get_field_id('show_thumbnail'); ?>" name="<?php echo $this->get_field_name('show_thumbnail'); ?>">
                                <?php _e('Featured Image', 'posts-grid-search'); ?>
                            </label>
                            <label class="pgs-checkbox-label">
                                <input type="checkbox" <?php checked($instance['show_excerpt'], true); ?> id="<?php echo $this->get_field_id('show_excerpt'); ?>" name="<?php echo $this->get_field_name('show_excerpt'); ?>">
                                <?php _e('Excerpt', 'posts-grid-search'); ?>
                            </label>
                            <label class="pgs-checkbox-label">
                                <input type="checkbox" <?php checked($instance['show_author'], true); ?> id="<?php echo $this->get_field_id('show_author'); ?>" name="<?php echo $this->get_field_name('show_author'); ?>">
                                <?php _e('Author', 'posts-grid-search'); ?>
                            </label>
                            <label class="pgs-checkbox-label">
                                <input type="checkbox" <?php checked($instance['show_date'], true); ?> id="<?php echo $this->get_field_id('show_date'); ?>" name="<?php echo $this->get_field_name('show_date'); ?>">
                                <?php _e('Date', 'posts-grid-search'); ?>
                            </label>
                        </div>
                    </div>
                </div>
                
                <!-- Style Tab -->
                <div class="pgs-tab-content" data-tab="style">
                    <div class="pgs-form-group">
                        <label for="<?php echo $this->get_field_id('columns'); ?>"><?php _e('Columns', 'posts-grid-search'); ?></label>
                        <select class="widefat" id="<?php echo $this->get_field_id('columns'); ?>" name="<?php echo $this->get_field_name('columns'); ?>">
                            <option value="1" <?php selected($instance['columns'], '1'); ?>>1</option>
                            <option value="2" <?php selected($instance['columns'], '2'); ?>>2</option>
                            <option value="3" <?php selected($instance['columns'], '3'); ?>>3</option>
                            <option value="4" <?php selected($instance['columns'], '4'); ?>>4</option>
                        </select>
                    </div>
                    
                    <div class="pgs-form-group">
                        <label class="pgs-checkbox-label">
                            <input type="checkbox" <?php checked($instance['show_pagination'], true); ?> id="<?php echo $this->get_field_id('show_pagination'); ?>" name="<?php echo $this->get_field_name('show_pagination'); ?>">
                            <?php _e('Enable Pagination', 'posts-grid-search'); ?>
                        </label>
                    </div>
                    
                    <div class="pgs-pagination-settings" style="<?php echo !$instance['show_pagination'] ? 'display: none;' : ''; ?>">
                        <div class="pgs-form-group">
                            <label for="<?php echo $this->get_field_id('pagination_style'); ?>"><?php _e('Pagination Style', 'posts-grid-search'); ?></label>
                            <select class="widefat" id="<?php echo $this->get_field_id('pagination_style'); ?>" name="<?php echo $this->get_field_name('pagination_style'); ?>">
                                <option value="numbers" <?php selected($instance['pagination_style'], 'numbers'); ?>><?php _e('Numbers', 'posts-grid-search'); ?></option>
                                <option value="simple" <?php selected($instance['pagination_style'], 'simple'); ?>><?php _e('Simple', 'posts-grid-search'); ?></option>
                                <option value="arrows" <?php selected($instance['pagination_style'], 'arrows'); ?>><?php _e('Arrows Only', 'posts-grid-search'); ?></option>
                            </select>
                        </div>
                        
                        <div class="pgs-form-row">
                            <div class="pgs-form-group pgs-form-half">
                                <label for="<?php echo $this->get_field_id('prev_icon'); ?>"><?php _e('Previous Icon', 'posts-grid-search'); ?></label>
                                <input class="widefat" id="<?php echo $this->get_field_id('prev_icon'); ?>" name="<?php echo $this->get_field_name('prev_icon'); ?>" type="text" value="<?php echo esc_attr($instance['prev_icon']); ?>">
                            </div>
                            <div class="pgs-form-group pgs-form-half">
                                <label for="<?php echo $this->get_field_id('next_icon'); ?>"><?php _e('Next Icon', 'posts-grid-search'); ?></label>
                                <input class="widefat" id="<?php echo $this->get_field_id('next_icon'); ?>" name="<?php echo $this->get_field_name('next_icon'); ?>" type="text" value="<?php echo esc_attr($instance['next_icon']); ?>">
                            </div>
                        </div>
                        
                        <div class="pgs-color-section">
                            <h4><?php _e('Pagination Colors', 'posts-grid-search'); ?></h4>
                            <div class="pgs-form-row">
                                <div class="pgs-form-group pgs-form-third">
                                    <label for="<?php echo $this->get_field_id('pagination_bg'); ?>"><?php _e('Background', 'posts-grid-search'); ?></label>
                                    <div class="pgs-color-picker-wrapper">
                                        <input class="pgs-color-input" id="<?php echo $this->get_field_id('pagination_bg'); ?>" name="<?php echo $this->get_field_name('pagination_bg'); ?>" type="color" value="<?php echo esc_attr($instance['pagination_bg']); ?>">
                                        <div class="pgs-color-preview" style="background-color: <?php echo esc_attr($instance['pagination_bg']); ?>;"></div>
                                    </div>
                                </div>
                                <div class="pgs-form-group pgs-form-third">
                                    <label for="<?php echo $this->get_field_id('pagination_active_color'); ?>"><?php _e('Active', 'posts-grid-search'); ?></label>
                                    <div class="pgs-color-picker-wrapper">
                                        <input class="pgs-color-input" id="<?php echo $this->get_field_id('pagination_active_color'); ?>" name="<?php echo $this->get_field_name('pagination_active_color'); ?>" type="color" value="<?php echo esc_attr($instance['pagination_active_color']); ?>">
                                        <div class="pgs-color-preview" style="background-color: <?php echo esc_attr($instance['pagination_active_color']); ?>;"></div>
                                    </div>
                                </div>
                                <div class="pgs-form-group pgs-form-third">
                                    <label for="<?php echo $this->get_field_id('pagination_text_color'); ?>"><?php _e('Text', 'posts-grid-search'); ?></label>
                                    <div class="pgs-color-picker-wrapper">
                                        <input class="pgs-color-input" id="<?php echo $this->get_field_id('pagination_text_color'); ?>" name="<?php echo $this->get_field_name('pagination_text_color'); ?>" type="color" value="<?php echo esc_attr($instance['pagination_text_color']); ?>">
                                        <div class="pgs-color-preview" style="background-color: <?php echo esc_attr($instance['pagination_text_color']); ?>;"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Advanced Tab -->
                <div class="pgs-tab-content" data-tab="advanced">
                    <div class="pgs-form-group">
                        <label><?php _e('Custom CSS Classes', 'posts-grid-search'); ?></label>
                        <input class="widefat" id="<?php echo $this->get_field_id('custom_css_class'); ?>" name="<?php echo $this->get_field_name('custom_css_class'); ?>" type="text" value="<?php echo esc_attr(!empty($instance['custom_css_class']) ? $instance['custom_css_class'] : ''); ?>" placeholder="<?php _e('Enter custom CSS classes', 'posts-grid-search'); ?>">
                    </div>
                    
                    <div class="pgs-form-group">
                        <label><?php _e('Widget ID', 'posts-grid-search'); ?></label>
                        <input class="widefat" id="<?php echo $this->get_field_id('widget_id'); ?>" name="<?php echo $this->get_field_name('widget_id'); ?>" type="text" value="<?php echo esc_attr(!empty($instance['widget_id']) ? $instance['widget_id'] : ''); ?>" placeholder="<?php _e('Unique identifier for targeting', 'posts-grid-search'); ?>">
                        <small><?php _e('Use this ID to target this specific widget with search filters.', 'posts-grid-search'); ?></small>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    public function update($new_instance, $old_instance) {
        $instance = array();
        
        // Sanitize all inputs
        $instance['title'] = sanitize_text_field($new_instance['title']);
        $instance['template'] = sanitize_text_field($new_instance['template']);
        $instance['elementor_template'] = sanitize_text_field($new_instance['elementor_template']);
        $instance['columns'] = intval($new_instance['columns']);
        $instance['posts_per_page'] = intval($new_instance['posts_per_page']);
        $instance['show_pagination'] = !empty($new_instance['show_pagination']);
        $instance['pagination_style'] = sanitize_text_field($new_instance['pagination_style']);
        $instance['prev_icon'] = sanitize_text_field($new_instance['prev_icon']);
        $instance['next_icon'] = sanitize_text_field($new_instance['next_icon']);
        $instance['pagination_bg'] = sanitize_hex_color($new_instance['pagination_bg']);
        $instance['pagination_active_color'] = sanitize_hex_color($new_instance['pagination_active_color']);
        $instance['pagination_text_color'] = sanitize_hex_color($new_instance['pagination_text_color']);
        $instance['show_thumbnail'] = !empty($new_instance['show_thumbnail']);
        $instance['show_excerpt'] = !empty($new_instance['show_excerpt']);
        $instance['show_author'] = !empty($new_instance['show_author']);
        $instance['show_date'] = !empty($new_instance['show_date']);
        $instance['custom_css_class'] = sanitize_text_field($new_instance['custom_css_class']);
        $instance['widget_id'] = sanitize_text_field($new_instance['widget_id']);
        
        return $instance;
    }
}