<?php
/**
 * Template Manager Class
 * Handles all template rendering and Elementor integration
 */

if (!defined('ABSPATH')) {
    exit;
}

class PGS_Template_Manager {
    
    public function get_available_templates() {
        $templates = array(
            'card' => __('Card Layout', 'posts-grid-search')
        );
        
        // Add Elementor templates if available
        $elementor_templates = $this->get_elementor_templates();
        if (!empty($elementor_templates)) {
            $templates['elementor'] = __('Saved Templates', 'posts-grid-search');
        }
        
        return $templates;
    }
    
    public function get_elementor_templates() {
        if (!class_exists('\Elementor\Plugin')) {
            return array();
        }
        
        $templates = get_posts(array(
            'post_type' => 'elementor_library',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_elementor_template_type',
                    'value' => array('page', 'section', 'container'),
                    'compare' => 'IN'
                )
            )
        ));
        
        $template_options = array();
        foreach ($templates as $template) {
            $template_options[$template->ID] = $template->post_title;
        }
        
        return $template_options;
    }
    
    public function render_post($template, $instance = array()) {
        $post_id = get_the_ID();
        $title = get_the_title();
        $excerpt = get_the_excerpt();
        $author = get_the_author();
        $date = get_the_date();
        $thumbnail = get_the_post_thumbnail($post_id, 'medium');
        $permalink = get_permalink();
        
        $show_excerpt = !empty($instance['show_excerpt']);
        $show_author = !empty($instance['show_author']);
        $show_date = !empty($instance['show_date']);
        $show_thumbnail = !empty($instance['show_thumbnail']);
        
        if ($template === 'elementor' && !empty($instance['elementor_template'])) {
            $this->render_elementor_template($instance['elementor_template'], $post_id);
        } else {
            $this->render_card_template($post_id, $title, $excerpt, $author, $date, $thumbnail, $permalink, $instance);
        }
    }
    
    private function render_card_template($post_id, $title, $excerpt, $author, $date, $thumbnail, $permalink, $instance) {
        $show_excerpt = !empty($instance['show_excerpt']);
        $show_author = !empty($instance['show_author']);
        $show_date = !empty($instance['show_date']);
        $show_thumbnail = !empty($instance['show_thumbnail']);
        
        echo '<article class="pgs-post-card">';
        echo '<a href="' . esc_url($permalink) . '" class="pgs-post-link">';
        
        if ($show_thumbnail && $thumbnail) {
            echo '<div class="pgs-post-thumbnail">' . $thumbnail . '</div>';
        }
        
        echo '<div class="pgs-post-content">';
        echo '<h3 class="pgs-post-title">' . esc_html($title) . '</h3>';
        
        if ($show_excerpt && $excerpt) {
            echo '<p class="pgs-post-excerpt">' . esc_html($excerpt) . '</p>';
        }
        
        if ($show_author || $show_date) {
            echo '<div class="pgs-post-meta">';
            if ($show_author) {
                echo '<span class="pgs-post-author">By ' . esc_html($author) . '</span>';
            }
            if ($show_date) {
                echo '<span class="pgs-post-date">' . esc_html($date) . '</span>';
            }
            echo '</div>';
        }
        
        echo '</div>';
        echo '</a>';
        echo '</article>';
    }
    
    private function render_elementor_template($template_id, $post_id) {
        if (!class_exists('\Elementor\Plugin')) {
            return;
        }
        
        // Set up post data for the template
        global $post;
        $original_post = $post;
        $post = get_post($post_id);
        setup_postdata($post);
        
        // Render Elementor template
        echo '<div class="pgs-elementor-template">';
        echo \Elementor\Plugin::instance()->frontend->get_builder_content($template_id);
        echo '</div>';
        
        // Restore original post data
        $post = $original_post;
        wp_reset_postdata();
    }
}