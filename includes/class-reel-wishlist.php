<?php
/**
 * Reel Wishlist Class
 * 
 * Handles wishlist functionality for reels
 */

if (!defined('ABSPATH')) {
    exit;
}

class Reel_Wishlist {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_shortcode('reel_wishlist', array($this, 'wishlist_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
    
    public function init() {
        // Create wishlist page if it doesn't exist
        $this->create_wishlist_page();
    }
    
    /**
     * Create wishlist page
     */
    private function create_wishlist_page() {
        $page_id = get_option('reel_wishlist_page_id');
        
        if (!$page_id || !get_post($page_id)) {
            $page_id = wp_insert_post(array(
                'post_title' => __('Minha Lista de Desejos', 'reel-marketplace'),
                'post_content' => '[reel_wishlist]',
                'post_status' => 'publish',
                'post_type' => 'page',
                'post_name' => 'minha-lista-desejos'
            ));
            
            if ($page_id) {
                update_option('reel_wishlist_page_id', $page_id);
            }
        }
    }
    
    /**
     * Enqueue scripts
     */
    public function enqueue_scripts() {
        if (is_page('minha-lista-desejos') || has_shortcode(get_post()->post_content, 'reel_wishlist')) {
            wp_enqueue_style(
                'reel-wishlist',
                REEL_MARKETPLACE_PLUGIN_URL . 'assets/css/wishlist.css',
                array(),
                REEL_MARKETPLACE_VERSION
            );
            
            wp_enqueue_script(
                'reel-wishlist',
                REEL_MARKETPLACE_PLUGIN_URL . 'assets/js/wishlist.js',
                array('jquery'),
                REEL_MARKETPLACE_VERSION,
                true
            );
        }
    }
    
    /**
     * Wishlist shortcode
     */
    public function wishlist_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<div class="reel-wishlist-login">' . 
                   '<p>' . __('Você precisa estar logado para ver sua lista de desejos.', 'reel-marketplace') . '</p>' .
                   '<a href="' . wp_login_url(get_permalink()) . '" class="reel-btn reel-btn-primary">' . __('Fazer Login', 'reel-marketplace') . '</a>' .
                   '</div>';
        }
        
        $user_id = get_current_user_id();
        $wishlist = get_user_meta($user_id, '_reel_wishlist', true);
        
        if (!is_array($wishlist) || empty($wishlist)) {
            return $this->empty_wishlist_html();
        }
        
        $products = array();
        foreach ($wishlist as $product_id) {
            $product = wc_get_product($product_id);
            if ($product) {
                $products[] = $product;
            }
        }
        
        if (empty($products)) {
            return $this->empty_wishlist_html();
        }
        
        ob_start();
        ?>
        <div class="reel-wishlist-container">
            <div class="reel-wishlist-header">
                <h2><?php _e('Minha Lista de Desejos', 'reel-marketplace'); ?></h2>
                <span class="reel-wishlist-count"><?php echo count($products) . ' ' . _n('item', 'itens', count($products), 'reel-marketplace'); ?></span>
            </div>
            
            <div class="reel-wishlist-grid">
                <?php foreach ($products as $product): ?>
                    <div class="reel-wishlist-item" data-product-id="<?php echo esc_attr($product->get_id()); ?>">
                        <div class="reel-wishlist-image">
                            <?php echo $product->get_image('medium'); ?>
                            <button class="reel-wishlist-remove" data-product-id="<?php echo esc_attr($product->get_id()); ?>">
                                <span class="material-icons">close</span>
                            </button>
                        </div>
                        
                        <div class="reel-wishlist-content">
                            <h3 class="reel-wishlist-title">
                                <a href="<?php echo esc_url($product->get_permalink()); ?>">
                                    <?php echo esc_html($product->get_name()); ?>
                                </a>
                            </h3>
                            
                            <div class="reel-wishlist-price">
                                <?php echo $product->get_price_html(); ?>
                            </div>
                            
                            <?php if ($product->is_in_stock()): ?>
                                <div class="reel-wishlist-stock in-stock">
                                    <span class="material-icons">check_circle</span>
                                    <?php _e('Em estoque', 'reel-marketplace'); ?>
                                </div>
                            <?php else: ?>
                                <div class="reel-wishlist-stock out-of-stock">
                                    <span class="material-icons">cancel</span>
                                    <?php _e('Fora de estoque', 'reel-marketplace'); ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="reel-wishlist-actions">
                                <?php if ($product->is_purchasable() && $product->is_in_stock()): ?>
                                    <button class="reel-btn reel-btn-primary reel-add-to-cart" data-product-id="<?php echo esc_attr($product->get_id()); ?>">
                                        <span class="material-icons">shopping_cart</span>
                                        <?php _e('Adicionar ao Carrinho', 'reel-marketplace'); ?>
                                    </button>
                                <?php endif; ?>
                                
                                <a href="<?php echo esc_url($product->get_permalink()); ?>" class="reel-btn reel-btn-secondary">
                                    <span class="material-icons">visibility</span>
                                    <?php _e('Ver Produto', 'reel-marketplace'); ?>
                                </a>
                            </div>
                            
                            <?php
                            // Show related reels
                            $related_reels = $this->get_product_reels($product->get_id());
                            if (!empty($related_reels)):
                            ?>
                                <div class="reel-wishlist-reels">
                                    <h4><?php _e('Visto em:', 'reel-marketplace'); ?></h4>
                                    <div class="reel-wishlist-reel-thumbs">
                                        <?php foreach ($related_reels as $reel): ?>
                                            <a href="<?php echo home_url('/reel/' . $reel->post_name); ?>" class="reel-wishlist-reel-thumb">
                                                <?php
                                                $poster = get_post_meta($reel->ID, '_reel_video_poster', true);
                                                if ($poster):
                                                ?>
                                                    <img src="<?php echo esc_url($poster); ?>" alt="<?php echo esc_attr($reel->post_title); ?>" />
                                                <?php else: ?>
                                                    <div class="reel-thumb-placeholder">
                                                        <span class="material-icons">play_arrow</span>
                                                    </div>
                                                <?php endif; ?>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="reel-wishlist-actions-bulk">
                <button class="reel-btn reel-btn-primary reel-add-all-to-cart">
                    <span class="material-icons">add_shopping_cart</span>
                    <?php _e('Adicionar Todos ao Carrinho', 'reel-marketplace'); ?>
                </button>
                
                <button class="reel-btn reel-btn-secondary reel-clear-wishlist">
                    <span class="material-icons">clear_all</span>
                    <?php _e('Limpar Lista', 'reel-marketplace'); ?>
                </button>
            </div>
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Get empty wishlist HTML
     */
    private function empty_wishlist_html() {
        ob_start();
        ?>
        <div class="reel-wishlist-empty">
            <div class="reel-wishlist-empty-icon">
                <span class="material-icons">favorite_border</span>
            </div>
            <h3><?php _e('Sua lista de desejos está vazia', 'reel-marketplace'); ?></h3>
            <p><?php _e('Explore nossos reels e adicione produtos que você gosta aos seus favoritos!', 'reel-marketplace'); ?></p>
            <a href="<?php echo home_url('/explorar/'); ?>" class="reel-btn reel-btn-primary">
                <span class="material-icons">explore</span>
                <?php _e('Explorar Reels', 'reel-marketplace'); ?>
            </a>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get reels that contain a specific product
     */
    private function get_product_reels($product_id) {
        global $wpdb;
        
        $reel_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} 
             WHERE meta_key = '_reel_products' 
             AND meta_value LIKE %s",
            '%' . serialize(strval($product_id)) . '%'
        ));
        
        if (empty($reel_ids)) {
            return array();
        }
        
        $reels = get_posts(array(
            'post_type' => 'reel',
            'post__in' => $reel_ids,
            'posts_per_page' => 3,
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => '_reel_status',
                    'value' => 'active',
                    'compare' => '='
                )
            )
        ));
        
        return $reels;
    }
    
    /**
     * Add product to wishlist
     */
    public static function add_to_wishlist($user_id, $product_id) {
        $wishlist = get_user_meta($user_id, '_reel_wishlist', true);
        if (!is_array($wishlist)) {
            $wishlist = array();
        }
        
        if (!in_array($product_id, $wishlist)) {
            $wishlist[] = $product_id;
            update_user_meta($user_id, '_reel_wishlist', $wishlist);
            return true;
        }
        
        return false;
    }
    
    /**
     * Remove product from wishlist
     */
    public static function remove_from_wishlist($user_id, $product_id) {
        $wishlist = get_user_meta($user_id, '_reel_wishlist', true);
        if (!is_array($wishlist)) {
            return false;
        }
        
        $key = array_search($product_id, $wishlist);
        if ($key !== false) {
            unset($wishlist[$key]);
            update_user_meta($user_id, '_reel_wishlist', array_values($wishlist));
            return true;
        }
        
        return false;
    }
    
    /**
     * Check if product is in wishlist
     */
    public static function is_in_wishlist($user_id, $product_id) {
        $wishlist = get_user_meta($user_id, '_reel_wishlist', true);
        if (!is_array($wishlist)) {
            return false;
        }
        
        return in_array($product_id, $wishlist);
    }
    
    /**
     * Get wishlist count
     */
    public static function get_wishlist_count($user_id) {
        $wishlist = get_user_meta($user_id, '_reel_wishlist', true);
        if (!is_array($wishlist)) {
            return 0;
        }
        
        return count($wishlist);
    }
    
    /**
     * Clear wishlist
     */
    public static function clear_wishlist($user_id) {
        delete_user_meta($user_id, '_reel_wishlist');
    }
}
