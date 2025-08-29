<?php
/**
 * Plugin Name: Posts Grid & Search Widgets
 * Plugin URI: https://example.com
 * Description: Advanced posts grid widget with search functionality, multiple templates, and customizable pagination.
 * Version: 2.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: posts-grid-search
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('PGS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PGS_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('PGS_VERSION', '2.0.0');

class PostsGridSearchPlugin {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
    }
    
    public function init() {
        // Load text domain for translations
        load_plugin_textdomain('posts-grid-search', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Register widgets
        add_action('widgets_init', array($this, 'register_widgets'));
        
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // AJAX handlers
        add_action('wp_ajax_pgs_filter_posts', array($this, 'ajax_filter_posts'));
        add_action('wp_ajax_nopriv_pgs_filter_posts', array($this, 'ajax_filter_posts'));
        
        // Add Elementor templates support
        add_action('init', array($this, 'register_elementor_support'));
    }
    
    public function register_widgets() {
        require_once PGS_PLUGIN_PATH . 'includes/class-posts-grid-widget.php';
        require_once PGS_PLUGIN_PATH . 'includes/class-search-filter-widget.php';
        require_once PGS_PLUGIN_PATH . 'includes/class-template-manager.php';
        
        register_widget('PGS_Posts_Grid_Widget');
        register_widget('PGS_Search_Filter_Widget');
    }
    
    public function enqueue_frontend_assets() {
        wp_enqueue_style('pgs-frontend-style', PGS_PLUGIN_URL . 'assets/css/frontend.css', array(), PGS_VERSION);
        wp_enqueue_script('pgs-frontend-script', PGS_PLUGIN_URL . 'assets/js/frontend.js', array('jquery'), PGS_VERSION, true);
        
        // Localize script for AJAX
        wp_localize_script('pgs-frontend-script', 'pgs_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('pgs_nonce')
        ));
    }
    
    public function enqueue_admin_assets($hook) {
        if ($hook === 'widgets.php') {
            wp_enqueue_style('pgs-admin-style', PGS_PLUGIN_URL . 'assets/css/admin.css', array(), PGS_VERSION);
            wp_enqueue_script('pgs-admin-script', PGS_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), PGS_VERSION, true);
            wp_enqueue_media();
        }
    }
    
    public function register_elementor_support() {
        // Check if Elementor is active
        if (did_action('elementor/loaded')) {
            require_once PGS_PLUGIN_PATH . 'includes/class-elementor-integration.php';
            new PGS_Elementor_Integration();
        }
    }
    
    public function ajax_filter_posts() {
        check_ajax_referer('pgs_nonce', 'nonce');
        
        $search_query = sanitize_text_field($_POST['search_query']);
        $posts_per_page = intval($_POST['posts_per_page']);
        $template = sanitize_text_field($_POST['template']);
        $page = intval($_POST['page']);
        $widget_instance = isset($_POST['widget_instance']) ? $_POST['widget_instance'] : array();
        
        $args = array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => $posts_per_page,
            'paged' => $page,
        );
        
        if (!empty($search_query)) {
            $args['s'] = $search_query;
        }
        
        $query = new WP_Query($args);
        
        ob_start();
        if ($query->have_posts()) {
            $template_manager = new PGS_Template_Manager();
            while ($query->have_posts()) {
                $query->the_post();
                $template_manager->render_post($template, $widget_instance);
            }
        } else {
            echo '<div class="pgs-no-posts">' . __('No posts found.', 'posts-grid-search') . '</div>';
        }
        wp_reset_postdata();
        
        $posts_html = ob_get_clean();
        
        wp_send_json_success(array(
            'posts' => $posts_html,
            'total_pages' => $query->max_num_pages,
            'current_page' => $page,
            'total_posts' => $query->found_posts
        ));
    }
}

// Initialize the plugin
PostsGridSearchPlugin::get_instance();