<?php
/**
 * Reel Cart Class
 * 
 * Handles cart functionality for reels
 */

if (!defined('ABSPATH')) {
    exit;
}

class Reel_Cart {
    
    public function __construct() {
        add_filter('woocommerce_add_to_cart_fragments', array($this, 'cart_fragments'));
        add_action('wp_ajax_reel_quick_add_to_cart', array($this, 'quick_add_to_cart'));
        add_action('wp_ajax_nopriv_reel_quick_add_to_cart', array($this, 'quick_add_to_cart'));
    }
    
    /**
     * Update cart fragments for AJAX cart updates
     */
    public function cart_fragments($fragments) {
        ob_start();
        ?>
        <span class="reel-cart-count"><?php echo WC()->cart->get_cart_contents_count(); ?></span>
        <?php
        $fragments['.reel-cart-count'] = ob_get_clean();
        
        ob_start();
        ?>
        <span class="reel-cart-total"><?php echo WC()->cart->get_cart_total(); ?></span>
        <?php
        $fragments['.reel-cart-total'] = ob_get_clean();
        
        return $fragments;
    }
    
    /**
     * Quick add to cart functionality
     */
    public function quick_add_to_cart() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'reel_ajax')) {
            wp_send_json_error(array('message' => __('Solicitação inválida.', 'reel-marketplace')));
        }
        
        $product_id = intval($_POST['product_id']);
        $variation_id = isset($_POST['variation_id']) ? intval($_POST['variation_id']) : 0;
        $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
        $variation_data = isset($_POST['variation_data']) ? $_POST['variation_data'] : array();
        
        if (!$product_id) {
            wp_send_json_error(array('message' => __('ID do produto inválido.', 'reel-marketplace')));
        }
        
        $product = wc_get_product($product_id);
        if (!$product) {
            wp_send_json_error(array('message' => __('Produto não encontrado.', 'reel-marketplace')));
        }
        
        // Check stock
        if (!$product->is_in_stock()) {
            wp_send_json_error(array('message' => __('Produto fora de estoque.', 'reel-marketplace')));
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
            
            if (!$variation->is_in_stock()) {
                wp_send_json_error(array('message' => __('Variação fora de estoque.', 'reel-marketplace')));
            }
            
            $cart_item_key = WC()->cart->add_to_cart($product_id, $quantity, $variation_id, $variation_data);
        } else {
            $cart_item_key = WC()->cart->add_to_cart($product_id, $quantity);
        }
        
        if ($cart_item_key) {
            // Get cart details
            $cart_item = WC()->cart->get_cart_item($cart_item_key);
            $product_data = $cart_item['data'];
            
            wp_send_json_success(array(
                'message' => sprintf(__('%s adicionado ao carrinho!', 'reel-marketplace'), $product_data->get_name()),
                'cart_count' => WC()->cart->get_cart_contents_count(),
                'cart_total' => WC()->cart->get_cart_total(),
                'cart_subtotal' => WC()->cart->get_cart_subtotal(),
                'cart_url' => wc_get_cart_url(),
                'checkout_url' => wc_get_checkout_url(),
                'product_name' => $product_data->get_name(),
                'product_image' => $product_data->get_image('thumbnail'),
                'product_price' => $product_data->get_price_html()
            ));
        } else {
            wp_send_json_error(array('message' => __('Erro ao adicionar produto ao carrinho.', 'reel-marketplace')));
        }
    }
    
    /**
     * Get cart mini widget HTML
     */
    public static function get_cart_mini_widget() {
        if (!function_exists('WC')) {
            return '';
        }
        
        $cart_count = WC()->cart->get_cart_contents_count();
        $cart_total = WC()->cart->get_cart_total();
        $cart_items = WC()->cart->get_cart();
        
        ob_start();
        ?>
        <div class="reel-cart-mini-widget">
            <div class="reel-cart-header">
                <h3><?php _e('Carrinho', 'reel-marketplace'); ?></h3>
                <span class="reel-cart-count"><?php echo $cart_count; ?></span>
            </div>
            
            <?php if ($cart_count > 0): ?>
                <div class="reel-cart-items">
                    <?php foreach ($cart_items as $cart_item_key => $cart_item): ?>
                        <?php
                        $product = $cart_item['data'];
                        $product_id = $cart_item['product_id'];
                        $quantity = $cart_item['quantity'];
                        ?>
                        <div class="reel-cart-item" data-cart-key="<?php echo esc_attr($cart_item_key); ?>">
                            <div class="reel-cart-item-image">
                                <?php echo $product->get_image('thumbnail'); ?>
                            </div>
                            <div class="reel-cart-item-details">
                                <h4><?php echo esc_html($product->get_name()); ?></h4>
                                <div class="reel-cart-item-price">
                                    <?php echo $product->get_price_html(); ?>
                                </div>
                                <div class="reel-cart-item-quantity">
                                    <?php _e('Qtd:', 'reel-marketplace'); ?> <?php echo $quantity; ?>
                                </div>
                            </div>
                            <button class="reel-cart-item-remove" data-cart-key="<?php echo esc_attr($cart_item_key); ?>">
                                <span class="material-icons">close</span>
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="reel-cart-footer">
                    <div class="reel-cart-total">
                        <strong><?php _e('Total:', 'reel-marketplace'); ?> <span class="reel-cart-total-amount"><?php echo $cart_total; ?></span></strong>
                    </div>
                    <div class="reel-cart-actions">
                        <a href="<?php echo esc_url(wc_get_cart_url()); ?>" class="reel-btn reel-btn-secondary">
                            <?php _e('Ver Carrinho', 'reel-marketplace'); ?>
                        </a>
                        <a href="<?php echo esc_url(wc_get_checkout_url()); ?>" class="reel-btn reel-btn-primary">
                            <?php _e('Finalizar Compra', 'reel-marketplace'); ?>
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="reel-cart-empty">
                    <div class="reel-cart-empty-icon">
                        <span class="material-icons">shopping_cart</span>
                    </div>
                    <p><?php _e('Seu carrinho está vazio', 'reel-marketplace'); ?></p>
                    <a href="<?php echo home_url('/explorar/'); ?>" class="reel-btn reel-btn-primary">
                        <?php _e('Explorar Produtos', 'reel-marketplace'); ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Add cart notification
     */
    public static function add_cart_notification($product_name, $product_image = '') {
        ob_start();
        ?>
        <div class="reel-cart-notification">
            <div class="reel-cart-notification-content">
                <?php if ($product_image): ?>
                <div class="reel-cart-notification-image">
                    <?php echo $product_image; ?>
                </div>
                <?php endif; ?>
                
                <div class="reel-cart-notification-text">
                    <div class="reel-cart-notification-title">
                        <?php _e('Produto adicionado!', 'reel-marketplace'); ?>
                    </div>
                    <div class="reel-cart-notification-product">
                        <?php echo esc_html($product_name); ?>
                    </div>
                </div>
                
                <div class="reel-cart-notification-actions">
                    <a href="<?php echo esc_url(wc_get_cart_url()); ?>" class="reel-btn reel-btn-sm reel-btn-secondary">
                        <?php _e('Ver Carrinho', 'reel-marketplace'); ?>
                    </a>
                    <a href="<?php echo esc_url(wc_get_checkout_url()); ?>" class="reel-btn reel-btn-sm reel-btn-primary">
                        <?php _e('Finalizar', 'reel-marketplace'); ?>
                    </a>
                </div>
            </div>
            
            <button class="reel-cart-notification-close">
                <span class="material-icons">close</span>
            </button>
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Get quick buy modal HTML
     */
    public static function get_quick_buy_modal($product_id) {
        $product = wc_get_product($product_id);
        if (!$product) {
            return '';
        }
        
        ob_start();
        ?>
        <div class="reel-quick-buy-modal">
            <div class="reel-quick-buy-product">
                <div class="reel-quick-buy-image">
                    <?php echo $product->get_image('medium'); ?>
                </div>
                
                <div class="reel-quick-buy-details">
                    <h3><?php echo esc_html($product->get_name()); ?></h3>
                    
                    <div class="reel-quick-buy-price">
                        <?php echo $product->get_price_html(); ?>
                    </div>
                    
                    <?php if ($product->get_short_description()): ?>
                    <div class="reel-quick-buy-description">
                        <?php echo wp_kses_post($product->get_short_description()); ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($product->is_type('variable')): ?>
                        <div class="reel-quick-buy-variations">
                            <?php
                            $attributes = $product->get_variation_attributes();
                            foreach ($attributes as $attribute_name => $options):
                                $attribute_label = wc_attribute_label($attribute_name);
                            ?>
                                <div class="reel-variation-option">
                                    <label><?php echo esc_html($attribute_label); ?>:</label>
                                    <select name="attribute_<?php echo esc_attr(sanitize_title($attribute_name)); ?>" data-attribute_name="attribute_<?php echo esc_attr(sanitize_title($attribute_name)); ?>">
                                        <option value=""><?php echo esc_html(sprintf(__('Escolher %s', 'reel-marketplace'), $attribute_label)); ?></option>
                                        <?php foreach ($options as $option): ?>
                                            <option value="<?php echo esc_attr($option); ?>"><?php echo esc_html($option); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="reel-quick-buy-quantity">
                        <label><?php _e('Quantidade:', 'reel-marketplace'); ?></label>
                        <div class="reel-quantity-selector">
                            <button type="button" class="reel-quantity-minus">-</button>
                            <input type="number" name="quantity" value="1" min="1" max="<?php echo $product->get_stock_quantity() ?: 999; ?>" />
                            <button type="button" class="reel-quantity-plus">+</button>
                        </div>
                    </div>
                    
                    <div class="reel-quick-buy-actions">
                        <button class="reel-btn reel-btn-primary reel-quick-add-to-cart" data-product-id="<?php echo esc_attr($product_id); ?>">
                            <span class="material-icons">add_shopping_cart</span>
                            <?php _e('Adicionar ao Carrinho', 'reel-marketplace'); ?>
                        </button>
                        
                        <button class="reel-btn reel-btn-secondary reel-quick-buy-now" data-product-id="<?php echo esc_attr($product_id); ?>">
                            <span class="material-icons">flash_on</span>
                            <?php _e('Comprar Agora', 'reel-marketplace'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php
        
        return ob_get_clean();
    }
}
