<?php
/**
 * Reel Ajax Class
 * 
 * Handles all AJAX requests for the reel functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

class Reel_Ajax {
    
    public function __construct() {
        // AJAX actions for logged in users
        add_action('wp_ajax_reel_like', array($this, 'handle_like'));
        add_action('wp_ajax_reel_share', array($this, 'handle_share'));
        add_action('wp_ajax_reel_add_to_cart', array($this, 'handle_add_to_cart'));
        add_action('wp_ajax_reel_add_to_wishlist', array($this, 'handle_add_to_wishlist'));
        add_action('wp_ajax_reel_load_more', array($this, 'handle_load_more'));
        add_action('wp_ajax_reel_view', array($this, 'handle_view'));
        add_action('wp_ajax_get_product_variations', array($this, 'get_product_variations'));
        add_action('wp_ajax_get_product_info', array($this, 'get_product_info'));
        
        // AJAX actions for non-logged in users
        add_action('wp_ajax_nopriv_reel_share', array($this, 'handle_share'));
        add_action('wp_ajax_nopriv_reel_load_more', array($this, 'handle_load_more'));
        add_action('wp_ajax_nopriv_reel_view', array($this, 'handle_view'));
        add_action('wp_ajax_nopriv_get_product_variations', array($this, 'get_product_variations'));
        add_action('wp_ajax_nopriv_get_product_info', array($this, 'get_product_info'));
    }
    
    /**
     * Handle like action
     */
    public function handle_like() {
        $this->verify_nonce();
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('Você precisa estar logado para curtir.', 'reel-marketplace')));
        }
        
        $reel_id = intval($_POST['reel_id']);
        $user_id = get_current_user_id();
        
        if (!$reel_id) {
            wp_send_json_error(array('message' => __('ID do reel inválido.', 'reel-marketplace')));
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'reel_interactions';
        
        // Check if already liked
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE reel_id = %d AND user_id = %d AND interaction_type = 'like'",
            $reel_id,
            $user_id
        ));
        
        if ($existing) {
            // Unlike
            $wpdb->delete(
                $table,
                array(
                    'reel_id' => $reel_id,
                    'user_id' => $user_id,
                    'interaction_type' => 'like'
                ),
                array('%d', '%d', '%s')
            );
            
            $liked = false;
        } else {
            // Like
            $wpdb->insert(
                $table,
                array(
                    'reel_id' => $reel_id,
                    'user_id' => $user_id,
                    'interaction_type' => 'like',
                    'created_at' => current_time('mysql')
                ),
                array('%d', '%d', '%s', '%s')
            );
            
            $liked = true;
        }
        
        // Get total likes count
        $likes_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE reel_id = %d AND interaction_type = 'like'",
            $reel_id
        ));
        
        wp_send_json_success(array(
            'liked' => $liked,
            'count' => intval($likes_count),
            'message' => $liked ? __('Curtido!', 'reel-marketplace') : __('Descurtido!', 'reel-marketplace')
        ));
    }
    
    /**
     * Handle share action
     */
    public function handle_share() {
        $this->verify_nonce();
        
        $reel_id = intval($_POST['reel_id']);
        $platform = sanitize_text_field($_POST['platform']);
        
        if (!$reel_id) {
            wp_send_json_error(array('message' => __('ID do reel inválido.', 'reel-marketplace')));
        }
        
        // Record share interaction
        if (is_user_logged_in()) {
            global $wpdb;
            $table = $wpdb->prefix . 'reel_interactions';
            
            $wpdb->insert(
                $table,
                array(
                    'reel_id' => $reel_id,
                    'user_id' => get_current_user_id(),
                    'interaction_type' => 'share',
                    'created_at' => current_time('mysql')
                ),
                array('%d', '%d', '%s', '%s')
            );
        }
        
        $reel_url = home_url("/reel/" . get_post_field('post_name', $reel_id));
        $reel_title = get_the_title($reel_id);
        $share_text = sprintf(__('Confira este reel incrível: %s', 'reel-marketplace'), $reel_title);
        
        $share_urls = array(
            'whatsapp' => 'https://wa.me/?text=' . urlencode($share_text . ' ' . $reel_url),
            'twitter' => 'https://twitter.com/intent/tweet?text=' . urlencode($share_text) . '&url=' . urlencode($reel_url),
            'facebook' => 'https://www.facebook.com/sharer/sharer.php?u=' . urlencode($reel_url),
            'instagram' => $reel_url, // Instagram doesn't support direct sharing
            'copy' => $reel_url
        );
        
        wp_send_json_success(array(
            'share_url' => isset($share_urls[$platform]) ? $share_urls[$platform] : $reel_url,
            'message' => __('Link de compartilhamento gerado!', 'reel-marketplace')
        ));
    }
    
    /**
     * Handle add to cart action
     */
    public function handle_add_to_cart() {
        $this->verify_nonce();
        
        $product_id = intval($_POST['product_id']);
        $variation_id = isset($_POST['variation_id']) ? intval($_POST['variation_id']) : 0;
        $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
        $variation_data = isset($_POST['variation_data']) ? $_POST['variation_data'] : array();
        
        if (!$product_id) {
            wp_send_json_error(array('message' => __('ID do produto inválido.', 'reel-marketplace')));
        }
        
        // Check if WooCommerce is active
        if (!function_exists('WC')) {
            wp_send_json_error(array('message' => __('WooCommerce não está ativo.', 'reel-marketplace')));
        }
        
        $product = wc_get_product($product_id);
        
        if (!$product) {
            wp_send_json_error(array('message' => __('Produto não encontrado.', 'reel-marketplace')));
        }
        
        // Handle variable products
        if ($product->is_type('variable')) {
            if (!$variation_id) {
                wp_send_json_error(array('message' => __('Selecione uma variação do produto.', 'reel-marketplace')));
            }
            
            $variation = wc_get_product($variation_id);
            
            if (!$variation || $variation->get_parent_id() !== $product_id) {
                wp_send_json_error(array('message' => __('Variação inválida.', 'reel-marketplace')));
            }
            
            $cart_item_data = array();
            $result = WC()->cart->add_to_cart($product_id, $quantity, $variation_id, $variation_data, $cart_item_data);
        } else {
            $result = WC()->cart->add_to_cart($product_id, $quantity);
        }
        
        if ($result) {
            wp_send_json_success(array(
                'message' => __('Produto adicionado ao carrinho!', 'reel-marketplace'),
                'cart_count' => WC()->cart->get_cart_contents_count(),
                'cart_total' => WC()->cart->get_cart_total()
            ));
        } else {
            wp_send_json_error(array('message' => __('Erro ao adicionar produto ao carrinho.', 'reel-marketplace')));
        }
    }
    
    /**
     * Handle add to wishlist action
     */
    public function handle_add_to_wishlist() {
        $this->verify_nonce();
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('Você precisa estar logado para favoritar.', 'reel-marketplace')));
        }
        
        $product_id = intval($_POST['product_id']);
        $user_id = get_current_user_id();
        
        if (!$product_id) {
            wp_send_json_error(array('message' => __('ID do produto inválido.', 'reel-marketplace')));
        }
        
        $wishlist = get_user_meta($user_id, '_reel_wishlist', true);
        if (!is_array($wishlist)) {
            $wishlist = array();
        }
        
        if (in_array($product_id, $wishlist)) {
            // Remove from wishlist
            $wishlist = array_diff($wishlist, array($product_id));
            $favorited = false;
            $message = __('Removido dos favoritos!', 'reel-marketplace');
        } else {
            // Add to wishlist
            $wishlist[] = $product_id;
            $favorited = true;
            $message = __('Adicionado aos favoritos!', 'reel-marketplace');
        }
        
        update_user_meta($user_id, '_reel_wishlist', $wishlist);
        
        wp_send_json_success(array(
            'favorited' => $favorited,
            'message' => $message,
            'wishlist_count' => count($wishlist)
        ));
    }
    
    /**
     * Handle load more reels
     */
    public function handle_load_more() {
        $this->verify_nonce();
        
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 10;
        $category = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : '';
        $tag = isset($_POST['tag']) ? sanitize_text_field($_POST['tag']) : '';
        
        $args = array(
            'post_type' => 'reel',
            'post_status' => 'publish',
            'posts_per_page' => $per_page,
            'paged' => $page,
            'meta_query' => array(
                array(
                    'key' => '_reel_status',
                    'value' => 'active',
                    'compare' => '='
                )
            ),
            'orderby' => 'date',
            'order' => 'DESC'
        );
        
        // Filter by category
        if (!empty($category)) {
            $args['tax_query'][] = array(
                'taxonomy' => 'reel_category',
                'field' => 'slug',
                'terms' => explode(',', $category)
            );
        }
        
        // Filter by tag
        if (!empty($tag)) {
            $args['tax_query'][] = array(
                'taxonomy' => 'reel_tag',
                'field' => 'slug',
                'terms' => explode(',', $tag)
            );
        }
        
        $reels = new WP_Query($args);
        $reels_html = '';
        
        if ($reels->have_posts()) {
            ob_start();
            
            while ($reels->have_posts()) {
                $reels->the_post();
                $frontend = new Reel_Frontend();
                $frontend->render_reel_item(get_the_ID());
            }
            
            $reels_html = ob_get_clean();
            wp_reset_postdata();
        }
        
        wp_send_json_success(array(
            'html' => $reels_html,
            'has_more' => $reels->max_num_pages > $page,
            'next_page' => $page + 1
        ));
    }
    
    /**
     * Handle view tracking
     */
    public function handle_view() {
        $this->verify_nonce();
        
        $reel_id = intval($_POST['reel_id']);
        
        if (!$reel_id) {
            wp_send_json_error(array('message' => __('ID do reel inválido.', 'reel-marketplace')));
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'reel_views';
        
        $user_id = is_user_logged_in() ? get_current_user_id() : null;
        $ip_address = $this->get_client_ip();
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        
        // Check if this IP has already viewed this reel in the last hour (prevent spam)
        $recent_view = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE reel_id = %d AND ip_address = %s AND viewed_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)",
            $reel_id,
            $ip_address
        ));
        
        if (!$recent_view) {
            $wpdb->insert(
                $table,
                array(
                    'reel_id' => $reel_id,
                    'user_id' => $user_id,
                    'ip_address' => $ip_address,
                    'user_agent' => $user_agent,
                    'viewed_at' => current_time('mysql')
                ),
                array('%d', '%d', '%s', '%s', '%s')
            );
        }
        
        wp_send_json_success(array('message' => __('Visualização registrada.', 'reel-marketplace')));
    }
    
    /**
     * Get product variations for variable products
     */
    public function get_product_variations() {
        $this->verify_nonce();
        
        $product_id = intval($_POST['product_id']);
        
        if (!$product_id) {
            wp_send_json_error(array('message' => __('ID do produto inválido.', 'reel-marketplace')));
        }
        
        $product = wc_get_product($product_id);
        
        if (!$product || !$product->is_type('variable')) {
            wp_send_json_error(array('message' => __('Produto não é variável.', 'reel-marketplace')));
        }
        
        $variations = array();
        $variation_ids = $product->get_children();
        
        foreach ($variation_ids as $variation_id) {
            $variation = wc_get_product($variation_id);
            
            if ($variation && $variation->is_purchasable()) {
                $variations[] = array(
                    'id' => $variation_id,
                    'attributes' => $variation->get_variation_attributes(),
                    'price' => $variation->get_price_html(),
                    'stock_status' => $variation->get_stock_status(),
                    'image' => wp_get_attachment_image_url($variation->get_image_id(), 'thumbnail')
                );
            }
        }
        
        // Get product attributes for selection
        $attributes = array();
        foreach ($product->get_variation_attributes() as $attribute_name => $options) {
            $attribute_label = wc_attribute_label($attribute_name);
            $attributes[$attribute_name] = array(
                'label' => $attribute_label,
                'options' => $options
            );
        }
        
        ob_start();
        include REEL_MARKETPLACE_PLUGIN_PATH . 'templates/product-variations.php';
        $html = ob_get_clean();
        
        wp_send_json_success(array(
            'variations' => $variations,
            'attributes' => $attributes,
            'html' => $html
        ));
    }
    
    /**
     * Get product info for admin
     */
    public function get_product_info() {
        $this->verify_nonce();
        
        $product_id = intval($_POST['product_id']);
        
        if (!$product_id) {
            wp_send_json_error(array('message' => __('ID do produto inválido.', 'reel-marketplace')));
        }
        
        $product = wc_get_product($product_id);
        
        if (!$product) {
            wp_send_json_error(array('message' => __('Produto não encontrado.', 'reel-marketplace')));
        }
        
        wp_send_json_success(array(
            'name' => $product->get_name(),
            'price' => $product->get_price_html(),
            'image' => wp_get_attachment_image_url($product->get_image_id(), 'thumbnail')
        ));
    }
    
    /**
     * Verify AJAX nonce
     */
    private function verify_nonce() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'reel_ajax')) {
            wp_send_json_error(array('message' => __('Solicitação inválida.', 'reel-marketplace')));
        }
    }
    
    /**
     * Get client IP address
     */
    private function get_client_ip() {
        $ip_keys = array('HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
    }
}
