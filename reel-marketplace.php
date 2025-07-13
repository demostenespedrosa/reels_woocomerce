<?php
/**
 * Plugin Name: Reel Marketplace - Explorar Feed
 * Plugin URI: https://github.com/your-repo/reel-marketplace
 * Description: Plugin para marketplace multi-vendedor com feed de vídeos curtos verticais estilo Instagram Reels/TikTok totalmente integrado ao e-commerce.
 * Version: 1.0.0
 * Author: Seu Nome
 * License: GPL v2 or later
 * Text Domain: reel-marketplace
 * Domain Path: /languages
 * 
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * 
 * WC requires at least: 4.0
 * WC tested up to: 8.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('REEL_MARKETPLACE_VERSION', '1.0.0');
define('REEL_MARKETPLACE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('REEL_MARKETPLACE_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('REEL_MARKETPLACE_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main Reel Marketplace Class
 */
class ReelMarketplace {
    
    /**
     * Single instance of the class
     */
    private static $instance = null;
    
    /**
     * Get single instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        
        // Check dependencies
        add_action('admin_init', array($this, 'check_dependencies'));
        
        // Activation/Deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Load required files
        $this->includes();
        
        // Initialize components
        $this->init_components();
    }
    
    /**
     * Load plugin textdomain
     */
    public function load_textdomain() {
        load_plugin_textdomain('reel-marketplace', false, dirname(REEL_MARKETPLACE_PLUGIN_BASENAME) . '/languages');
    }
    
    /**
     * Include required files
     */
    private function includes() {
        // Installer class
        require_once REEL_MARKETPLACE_PLUGIN_PATH . 'includes/class-reel-installer.php';
        
        // Core classes
        require_once REEL_MARKETPLACE_PLUGIN_PATH . 'includes/class-reel-post-type.php';
        require_once REEL_MARKETPLACE_PLUGIN_PATH . 'includes/class-reel-ajax.php';
        require_once REEL_MARKETPLACE_PLUGIN_PATH . 'includes/class-reel-frontend.php';
        require_once REEL_MARKETPLACE_PLUGIN_PATH . 'includes/class-reel-admin.php';
        require_once REEL_MARKETPLACE_PLUGIN_PATH . 'includes/class-reel-wishlist.php';
        require_once REEL_MARKETPLACE_PLUGIN_PATH . 'includes/class-reel-share.php';
        require_once REEL_MARKETPLACE_PLUGIN_PATH . 'includes/class-reel-cart.php';
        require_once REEL_MARKETPLACE_PLUGIN_PATH . 'includes/class-reel-video-handler.php';
        
        // Admin classes
        if (is_admin()) {
            require_once REEL_MARKETPLACE_PLUGIN_PATH . 'admin/class-reel-admin-dashboard.php';
            require_once REEL_MARKETPLACE_PLUGIN_PATH . 'admin/class-reel-admin-metaboxes.php';
        }
        
        // Integration classes
        require_once REEL_MARKETPLACE_PLUGIN_PATH . 'integrations/class-dokan-integration.php';
        require_once REEL_MARKETPLACE_PLUGIN_PATH . 'integrations/class-wcfm-integration.php';
    }
    
    /**
     * Initialize plugin components
     */
    private function init_components() {
        // Core components
        new Reel_Post_Type();
        new Reel_Ajax();
        new Reel_Frontend();
        new Reel_Wishlist();
        new Reel_Share();
        new Reel_Cart();
        new Reel_Video_Handler();
        
        // Admin components
        if (is_admin()) {
            new Reel_Admin();
            new Reel_Admin_Dashboard();
            new Reel_Admin_Metaboxes();
        }
        
        // Integrations
        if (class_exists('WeDevs_Dokan')) {
            new Dokan_Integration();
        }
        
        if (function_exists('wcfm_get_option')) {
            new WCFM_Integration();
        }
    }
    
    /**
     * Check plugin dependencies
     */
    public function check_dependencies() {
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            deactivate_plugins(REEL_MARKETPLACE_PLUGIN_BASENAME);
        }
    }
    
    /**
     * WooCommerce missing notice
     */
    public function woocommerce_missing_notice() {
        echo '<div class="notice notice-error"><p>';
        echo __('Reel Marketplace requer WooCommerce para funcionar. Por favor, instale e ative o WooCommerce.', 'reel-marketplace');
        echo '</p></div>';
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create database tables
        $this->create_tables();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Set default options
        $this->set_default_options();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Create database tables
     */
    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Reel views table
        $table_name = $wpdb->prefix . 'reel_views';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            reel_id bigint(20) NOT NULL,
            user_id bigint(20) DEFAULT NULL,
            ip_address varchar(45) NOT NULL,
            user_agent text,
            viewed_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY reel_id (reel_id),
            KEY user_id (user_id),
            KEY viewed_at (viewed_at)
        ) $charset_collate;";
        
        // Reel interactions table
        $table_interactions = $wpdb->prefix . 'reel_interactions';
        $sql_interactions = "CREATE TABLE $table_interactions (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            reel_id bigint(20) NOT NULL,
            user_id bigint(20) NOT NULL,
            interaction_type varchar(20) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_interaction (reel_id, user_id, interaction_type),
            KEY reel_id (reel_id),
            KEY user_id (user_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        dbDelta($sql_interactions);
    }
    
    /**
     * Set default plugin options
     */
    private function set_default_options() {
        $default_options = array(
            'reel_marketplace_page_id' => 0,
            'reel_autoplay' => 'yes',
            'reel_loop' => 'yes',
            'reel_muted' => 'yes',
            'reel_preload_count' => 3,
            'reel_video_quality' => 'auto',
            'reel_enable_analytics' => 'yes',
            'reel_enable_wishlist' => 'yes',
            'reel_enable_sharing' => 'yes'
        );
        
        foreach ($default_options as $option => $value) {
            if (!get_option($option)) {
                add_option($option, $value);
            }
        }
    }
}

/**
 * Initialize the plugin
 */
function reel_marketplace_init() {
    return ReelMarketplace::get_instance();
}

// Start the plugin
reel_marketplace_init();

/**
 * Helper functions
 */

/**
 * Get reel by ID
 */
function get_reel($reel_id) {
    return get_post($reel_id);
}

/**
 * Get reel products
 */
function get_reel_products($reel_id) {
    $product_ids = get_post_meta($reel_id, '_reel_products', true);
    if (empty($product_ids) || !is_array($product_ids)) {
        return array();
    }
    
    $products = array();
    foreach ($product_ids as $product_id) {
        $product = wc_get_product($product_id);
        if ($product) {
            $products[] = $product;
        }
    }
    
    return $products;
}

/**
 * Check if user can manage reels
 */
function current_user_can_manage_reels() {
    if (current_user_can('manage_options')) {
        return true;
    }
    
    // Check if user is a vendor in Dokan
    if (function_exists('dokan_is_user_seller') && dokan_is_user_seller(get_current_user_id())) {
        return true;
    }
    
    // Check if user is a vendor in WCFM
    if (function_exists('wcfm_is_vendor') && wcfm_is_vendor(get_current_user_id())) {
        return true;
    }
    
    return false;
}
