<?php
/**
 * Reel Frontend Class
 * 
 * Handles the frontend display and interactions
 */

if (!defined('ABSPATH')) {
    exit;
}

class Reel_Frontend {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('reel_feed', array($this, 'reel_feed_shortcode'));
        add_action('wp_footer', array($this, 'add_reel_modal'));
        
        // Add Explorar page
        add_action('wp_loaded', array($this, 'create_explorar_page'));
        
        // Add menu item
        add_filter('wp_nav_menu_items', array($this, 'add_explorar_menu_item'), 10, 2);
        
        // Handle custom endpoints
        add_action('init', array($this, 'add_rewrite_rules'));
        add_filter('query_vars', array($this, 'add_query_vars'));
        add_action('template_redirect', array($this, 'template_redirect'));
    }
    
    public function init() {
        // Additional initialization
    }
    
    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_scripts() {
        if ($this->is_reel_page()) {
            wp_enqueue_script(
                'reel-marketplace-frontend',
                REEL_MARKETPLACE_PLUGIN_URL . 'assets/js/frontend.js',
                array('jquery'),
                REEL_MARKETPLACE_VERSION,
                true
            );
            
            wp_enqueue_style(
                'reel-marketplace-frontend',
                REEL_MARKETPLACE_PLUGIN_URL . 'assets/css/frontend.css',
                array(),
                REEL_MARKETPLACE_VERSION
            );
            
            // Material Design Icons
            wp_enqueue_style(
                'material-icons',
                'https://fonts.googleapis.com/icon?family=Material+Icons',
                array(),
                null
            );
            
            // Localize script
            wp_localize_script('reel-marketplace-frontend', 'reelAjax', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('reel_ajax'),
                'strings' => array(
                    'loading' => __('Carregando...', 'reel-marketplace'),
                    'error' => __('Erro ao carregar', 'reel-marketplace'),
                    'addToCart' => __('Adicionar ao Carrinho', 'reel-marketplace'),
                    'buyNow' => __('Comprar Agora', 'reel-marketplace'),
                    'favorited' => __('Favoritado!', 'reel-marketplace'),
                    'shared' => __('Compartilhado!', 'reel-marketplace'),
                    'selectVariation' => __('Selecione uma variação', 'reel-marketplace')
                )
            ));
        }
    }
    
    /**
     * Check if current page should load reel assets
     */
    private function is_reel_page() {
        return is_page('explorar') || has_shortcode(get_post()->post_content, 'reel_feed') || get_query_var('reel_view');
    }
    
    /**
     * Create Explorar page
     */
    public function create_explorar_page() {
        $page_id = get_option('reel_marketplace_page_id');
        
        if (!$page_id || !get_post($page_id)) {
            $page_id = wp_insert_post(array(
                'post_title' => __('Explorar', 'reel-marketplace'),
                'post_content' => '[reel_feed]',
                'post_status' => 'publish',
                'post_type' => 'page',
                'post_name' => 'explorar'
            ));
            
            if ($page_id) {
                update_option('reel_marketplace_page_id', $page_id);
            }
        }
    }
    
    /**
     * Add Explorar menu item
     */
    public function add_explorar_menu_item($items, $args) {
        if (isset($args->theme_location) && $args->theme_location === 'primary') {
            $explorar_url = home_url('/explorar/');
            $explorar_item = '<li class="menu-item reel-explorar-menu"><a href="' . $explorar_url . '">' . __('Explorar', 'reel-marketplace') . '</a></li>';
            $items = $explorar_item . $items;
        }
        
        return $items;
    }
    
    /**
     * Add rewrite rules
     */
    public function add_rewrite_rules() {
        add_rewrite_rule('^explorar/?$', 'index.php?page_id=' . get_option('reel_marketplace_page_id'), 'top');
        add_rewrite_rule('^reel/([^/]+)/?$', 'index.php?reel_view=$matches[1]', 'top');
    }
    
    /**
     * Add query vars
     */
    public function add_query_vars($vars) {
        $vars[] = 'reel_view';
        return $vars;
    }
    
    /**
     * Template redirect
     */
    public function template_redirect() {
        $reel_slug = get_query_var('reel_view');
        
        if ($reel_slug) {
            $this->load_single_reel_template($reel_slug);
        }
    }
    
    /**
     * Load single reel template
     */
    private function load_single_reel_template($slug) {
        $reel = get_page_by_path($slug, OBJECT, 'reel');
        
        if (!$reel) {
            global $wp_query;
            $wp_query->set_404();
            status_header(404);
            return;
        }
        
        // Load single reel template
        include REEL_MARKETPLACE_PLUGIN_PATH . 'templates/single-reel.php';
        exit;
    }
    
    /**
     * Reel feed shortcode
     */
    public function reel_feed_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => 20,
            'category' => '',
            'tag' => '',
            'featured' => false,
            'autoplay' => true
        ), $atts);
        
        $args = array(
            'post_type' => 'reel',
            'post_status' => 'publish',
            'posts_per_page' => intval($atts['limit']),
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
        if (!empty($atts['category'])) {
            $args['tax_query'][] = array(
                'taxonomy' => 'reel_category',
                'field' => 'slug',
                'terms' => explode(',', $atts['category'])
            );
        }
        
        // Filter by tag
        if (!empty($atts['tag'])) {
            $args['tax_query'][] = array(
                'taxonomy' => 'reel_tag',
                'field' => 'slug',
                'terms' => explode(',', $atts['tag'])
            );
        }
        
        // Filter featured
        if ($atts['featured']) {
            $args['meta_query'][] = array(
                'key' => '_reel_featured',
                'value' => '1',
                'compare' => '='
            );
        }
        
        $reels = new WP_Query($args);
        
        ob_start();
        
        if ($reels->have_posts()) {
            echo '<div class="reel-feed-container" data-autoplay="' . ($atts['autoplay'] ? 'true' : 'false') . '">';
            echo '<div class="reel-feed">';
            
            while ($reels->have_posts()) {
                $reels->the_post();
                $this->render_reel_item(get_the_ID());
            }
            
            echo '</div>';
            
            // Navigation controls
            echo '<div class="reel-navigation">';
            echo '<button class="reel-nav-up material-icons">keyboard_arrow_up</button>';
            echo '<button class="reel-nav-down material-icons">keyboard_arrow_down</button>';
            echo '</div>';
            
            // Loading indicator
            echo '<div class="reel-loading">';
            echo '<div class="reel-loading-spinner"></div>';
            echo '<p>' . __('Carregando mais reels...', 'reel-marketplace') . '</p>';
            echo '</div>';
            
            echo '</div>';
        } else {
            echo '<div class="reel-empty">';
            echo '<div class="reel-empty-icon material-icons">video_library</div>';
            echo '<h3>' . __('Nenhum reel encontrado', 'reel-marketplace') . '</h3>';
            echo '<p>' . __('Volte mais tarde para ver novos conteúdos!', 'reel-marketplace') . '</p>';
            echo '</div>';
        }
        
        wp_reset_postdata();
        
        return ob_get_clean();
    }
    
    /**
     * Render individual reel item
     */
    private function render_reel_item($reel_id) {
        $video_url = get_post_meta($reel_id, '_reel_video_url', true);
        $video_file = get_post_meta($reel_id, '_reel_video_file', true);
        $video_poster = get_post_meta($reel_id, '_reel_video_poster', true);
        $autoplay = get_post_meta($reel_id, '_reel_autoplay', true);
        $products = get_reel_products($reel_id);
        $reel_post = get_post($reel_id);
        
        $video_src = !empty($video_file) ? $video_file : $video_url;
        
        if (empty($video_src)) {
            return;
        }
        
        ?>
        <div class="reel-item" data-reel-id="<?php echo esc_attr($reel_id); ?>">
            <div class="reel-video-container">
                <?php if (strpos($video_src, 'youtube.com') !== false || strpos($video_src, 'youtu.be') !== false): ?>
                    <iframe 
                        class="reel-video" 
                        src="<?php echo esc_url($this->get_youtube_embed_url($video_src)); ?>" 
                        frameborder="0" 
                        allowfullscreen>
                    </iframe>
                <?php elseif (strpos($video_src, 'vimeo.com') !== false): ?>
                    <iframe 
                        class="reel-video" 
                        src="<?php echo esc_url($this->get_vimeo_embed_url($video_src)); ?>" 
                        frameborder="0" 
                        allowfullscreen>
                    </iframe>
                <?php else: ?>
                    <video 
                        class="reel-video" 
                        <?php echo $autoplay !== 'no' ? 'autoplay' : ''; ?> 
                        muted 
                        loop 
                        playsinline
                        <?php if ($video_poster): ?>poster="<?php echo esc_url($video_poster); ?>"<?php endif; ?>
                    >
                        <source src="<?php echo esc_url($video_src); ?>" type="video/mp4">
                        <?php _e('Seu navegador não suporta vídeos HTML5.', 'reel-marketplace'); ?>
                    </video>
                <?php endif; ?>
                
                <!-- Video overlay controls -->
                <div class="reel-overlay">
                    <div class="reel-actions">
                        <button class="reel-action reel-like" data-reel-id="<?php echo esc_attr($reel_id); ?>">
                            <span class="material-icons">favorite_border</span>
                            <span class="reel-action-count">0</span>
                        </button>
                        
                        <button class="reel-action reel-share" data-reel-id="<?php echo esc_attr($reel_id); ?>">
                            <span class="material-icons">share</span>
                        </button>
                        
                        <?php if (!empty($products)): ?>
                        <button class="reel-action reel-products" data-reel-id="<?php echo esc_attr($reel_id); ?>">
                            <span class="material-icons">shopping_bag</span>
                            <span class="reel-action-count"><?php echo count($products); ?></span>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Reel info -->
                <div class="reel-info">
                    <div class="reel-author">
                        <?php
                        $author = get_user_by('id', $reel_post->post_author);
                        echo get_avatar($author->ID, 32);
                        ?>
                        <span class="reel-author-name"><?php echo esc_html($author->display_name); ?></span>
                    </div>
                    
                    <?php if (!empty($reel_post->post_excerpt)): ?>
                    <div class="reel-description">
                        <?php echo wp_kses_post($reel_post->post_excerpt); ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Products section -->
            <?php if (!empty($products)): ?>
            <div class="reel-products-section">
                <div class="reel-products-slider">
                    <?php foreach ($products as $index => $product): ?>
                    <div class="reel-product-card" data-product-id="<?php echo esc_attr($product->get_id()); ?>">
                        <div class="reel-product-image">
                            <?php echo $product->get_image('thumbnail'); ?>
                        </div>
                        <div class="reel-product-info">
                            <h4 class="reel-product-title"><?php echo esc_html($product->get_name()); ?></h4>
                            <div class="reel-product-price"><?php echo $product->get_price_html(); ?></div>
                            <div class="reel-product-actions">
                                <button class="reel-btn reel-btn-primary reel-add-to-cart" data-product-id="<?php echo esc_attr($product->get_id()); ?>">
                                    <span class="material-icons">shopping_cart</span>
                                    <?php _e('Comprar Agora', 'reel-marketplace'); ?>
                                </button>
                                <button class="reel-btn reel-btn-secondary reel-add-to-wishlist" data-product-id="<?php echo esc_attr($product->get_id()); ?>">
                                    <span class="material-icons">favorite_border</span>
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if (count($products) > 1): ?>
                <div class="reel-products-indicators">
                    <?php for ($i = 0; $i < count($products); $i++): ?>
                    <button class="reel-product-indicator <?php echo $i === 0 ? 'active' : ''; ?>" data-index="<?php echo $i; ?>"></button>
                    <?php endfor; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Get YouTube embed URL
     */
    private function get_youtube_embed_url($url) {
        $video_id = '';
        
        if (preg_match('/youtube\.com\/watch\?v=([^\&\?\/]+)/', $url, $id)) {
            $video_id = $id[1];
        } elseif (preg_match('/youtube\.com\/embed\/([^\&\?\/]+)/', $url, $id)) {
            $video_id = $id[1];
        } elseif (preg_match('/youtu\.be\/([^\&\?\/]+)/', $url, $id)) {
            $video_id = $id[1];
        }
        
        if ($video_id) {
            return "https://www.youtube.com/embed/{$video_id}?autoplay=1&mute=1&loop=1&controls=0&modestbranding=1&rel=0&playlist={$video_id}";
        }
        
        return $url;
    }
    
    /**
     * Get Vimeo embed URL
     */
    private function get_vimeo_embed_url($url) {
        $video_id = '';
        
        if (preg_match('/vimeo\.com\/(\d+)/', $url, $id)) {
            $video_id = $id[1];
        }
        
        if ($video_id) {
            return "https://player.vimeo.com/video/{$video_id}?autoplay=1&muted=1&loop=1&controls=0&title=0&byline=0&portrait=0";
        }
        
        return $url;
    }
    
    /**
     * Add reel modal to footer
     */
    public function add_reel_modal() {
        if (!$this->is_reel_page()) {
            return;
        }
        
        ?>
        <!-- Reel Product Modal -->
        <div id="reel-product-modal" class="reel-modal">
            <div class="reel-modal-content">
                <div class="reel-modal-header">
                    <h3 id="reel-modal-title"><?php _e('Selecionar Variação', 'reel-marketplace'); ?></h3>
                    <button class="reel-modal-close material-icons">close</button>
                </div>
                <div class="reel-modal-body">
                    <div id="reel-modal-product-content">
                        <!-- Product content will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Reel Share Modal -->
        <div id="reel-share-modal" class="reel-modal reel-share-modal">
            <div class="reel-modal-content">
                <div class="reel-modal-header">
                    <h3><?php _e('Compartilhar', 'reel-marketplace'); ?></h3>
                    <button class="reel-modal-close material-icons">close</button>
                </div>
                <div class="reel-modal-body">
                    <div class="reel-share-options">
                        <button class="reel-share-option" data-platform="whatsapp">
                            <img src="<?php echo REEL_MARKETPLACE_PLUGIN_URL; ?>assets/images/whatsapp.svg" alt="WhatsApp">
                            <span>WhatsApp</span>
                        </button>
                        <button class="reel-share-option" data-platform="instagram">
                            <img src="<?php echo REEL_MARKETPLACE_PLUGIN_URL; ?>assets/images/instagram.svg" alt="Instagram">
                            <span>Instagram</span>
                        </button>
                        <button class="reel-share-option" data-platform="twitter">
                            <img src="<?php echo REEL_MARKETPLACE_PLUGIN_URL; ?>assets/images/twitter.svg" alt="Twitter">
                            <span>Twitter</span>
                        </button>
                        <button class="reel-share-option" data-platform="facebook">
                            <img src="<?php echo REEL_MARKETPLACE_PLUGIN_URL; ?>assets/images/facebook.svg" alt="Facebook">
                            <span>Facebook</span>
                        </button>
                        <button class="reel-share-option" data-platform="copy">
                            <span class="material-icons">content_copy</span>
                            <span><?php _e('Copiar Link', 'reel-marketplace'); ?></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Toast notifications -->
        <div id="reel-toast-container"></div>
        <?php
    }
}
