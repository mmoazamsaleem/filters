<?php
/**
 * Elementor Integration Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class PGS_Elementor_Integration {
    
    public function __construct() {
        add_action('elementor/widgets/widgets_registered', array($this, 'register_elementor_widgets'));
    }
    
    public function register_elementor_widgets() {
        // Register custom Elementor widgets if needed
        // This can be expanded for future Elementor-specific functionality
    }
}