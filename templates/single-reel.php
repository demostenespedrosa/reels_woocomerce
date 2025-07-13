<?php
/**
 * Single Reel Template
 * 
 * Displays individual reel page
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

$reel_slug = get_query_var('reel_view');
$reel = get_page_by_path($reel_slug, OBJECT, 'reel');

if (!$reel) {
    get_template_part('404');
    get_footer();
    return;
}

$video_url = get_post_meta($reel->ID, '_reel_video_url', true);
$video_file = get_post_meta($reel->ID, '_reel_video_file', true);
$video_poster = get_post_meta($reel->ID, '_reel_video_poster', true);
$products = get_reel_products($reel->ID);
$author = get_user_by('id', $reel->post_author);

$video_src = !empty($video_file) ? $video_file : $video_url;
?>

<div class="reel-single-container">
    <div class="reel-single-content">
        <!-- Video Section -->
        <div class="reel-single-video">
            <?php if (strpos($video_src, 'youtube.com') !== false || strpos($video_src, 'youtu.be') !== false): ?>
                <iframe 
                    class="reel-single-video-player" 
                    src="<?php echo esc_url($this->get_youtube_embed_url($video_src)); ?>" 
                    frameborder="0" 
                    allowfullscreen>
                </iframe>
            <?php elseif (strpos($video_src, 'vimeo.com') !== false): ?>
                <iframe 
                    class="reel-single-video-player" 
                    src="<?php echo esc_url($this->get_vimeo_embed_url($video_src)); ?>" 
                    frameborder="0" 
                    allowfullscreen>
                </iframe>
            <?php else: ?>
                <video 
                    class="reel-single-video-player" 
                    controls 
                    autoplay 
                    muted 
                    loop 
                    playsinline
                    <?php if ($video_poster): ?>poster="<?php echo esc_url($video_poster); ?>"<?php endif; ?>
                >
                    <source src="<?php echo esc_url($video_src); ?>" type="video/mp4">
                    <?php _e('Seu navegador não suporta vídeos HTML5.', 'reel-marketplace'); ?>
                </video>
            <?php endif; ?>
            
            <!-- Video overlay with actions -->
            <div class="reel-single-overlay">
                <div class="reel-single-actions">
                    <button class="reel-action reel-like" data-reel-id="<?php echo esc_attr($reel->ID); ?>">
                        <span class="material-icons">favorite_border</span>
                        <span class="reel-action-label"><?php _e('Curtir', 'reel-marketplace'); ?></span>
                    </button>
                    
                    <button class="reel-action reel-share" data-reel-id="<?php echo esc_attr($reel->ID); ?>">
                        <span class="material-icons">share</span>
                        <span class="reel-action-label"><?php _e('Compartilhar', 'reel-marketplace'); ?></span>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Info Section -->
        <div class="reel-single-info">
            <div class="reel-single-header">
                <div class="reel-single-author">
                    <?php echo get_avatar($author->ID, 48); ?>
                    <div class="reel-single-author-info">
                        <h3><?php echo esc_html($author->display_name); ?></h3>
                        <span class="reel-single-date">
                            <?php echo human_time_diff(get_post_time('U', false, $reel->ID), current_time('timestamp')) . ' ' . __('atrás', 'reel-marketplace'); ?>
                        </span>
                    </div>
                </div>
                
                <div class="reel-single-share-buttons">
                    <?php echo Reel_Share::get_share_buttons($reel->ID, false); ?>
                </div>
            </div>
            
            <div class="reel-single-title">
                <h1><?php echo esc_html($reel->post_title); ?></h1>
            </div>
            
            <?php if ($reel->post_excerpt): ?>
            <div class="reel-single-description">
                <?php echo wp_kses_post($reel->post_excerpt); ?>
            </div>
            <?php endif; ?>
            
            <?php if ($reel->post_content): ?>
            <div class="reel-single-content-text">
                <?php echo wp_kses_post($reel->post_content); ?>
            </div>
            <?php endif; ?>
            
            <!-- Tags -->
            <?php
            $tags = get_the_terms($reel->ID, 'reel_tag');
            if ($tags && !is_wp_error($tags)):
            ?>
            <div class="reel-single-tags">
                <?php foreach ($tags as $tag): ?>
                    <a href="<?php echo get_term_link($tag); ?>" class="reel-tag">
                        #<?php echo esc_html($tag->name); ?>
                    </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Products Section -->
    <?php if (!empty($products)): ?>
    <div class="reel-single-products">
        <h2><?php _e('Produtos em Destaque', 'reel-marketplace'); ?></h2>
        
        <div class="reel-single-products-grid">
            <?php foreach ($products as $product): ?>
            <div class="reel-single-product-card">
                <div class="reel-single-product-image">
                    <a href="<?php echo esc_url($product->get_permalink()); ?>">
                        <?php echo $product->get_image('medium'); ?>
                    </a>
                    
                    <button class="reel-single-product-wishlist" data-product-id="<?php echo esc_attr($product->get_id()); ?>">
                        <span class="material-icons">favorite_border</span>
                    </button>
                </div>
                
                <div class="reel-single-product-info">
                    <h3 class="reel-single-product-title">
                        <a href="<?php echo esc_url($product->get_permalink()); ?>">
                            <?php echo esc_html($product->get_name()); ?>
                        </a>
                    </h3>
                    
                    <div class="reel-single-product-price">
                        <?php echo $product->get_price_html(); ?>
                    </div>
                    
                    <?php if ($product->get_short_description()): ?>
                    <div class="reel-single-product-description">
                        <?php echo wp_trim_words($product->get_short_description(), 15); ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="reel-single-product-actions">
                        <?php if ($product->is_purchasable() && $product->is_in_stock()): ?>
                            <button class="reel-btn reel-btn-primary reel-add-to-cart" data-product-id="<?php echo esc_attr($product->get_id()); ?>">
                                <span class="material-icons">shopping_cart</span>
                                <?php _e('Adicionar ao Carrinho', 'reel-marketplace'); ?>
                            </button>
                        <?php else: ?>
                            <button class="reel-btn reel-btn-disabled" disabled>
                                <?php _e('Indisponível', 'reel-marketplace'); ?>
                            </button>
                        <?php endif; ?>
                        
                        <a href="<?php echo esc_url($product->get_permalink()); ?>" class="reel-btn reel-btn-secondary">
                            <?php _e('Ver Detalhes', 'reel-marketplace'); ?>
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Related Reels -->
    <?php
    $related_reels = get_posts(array(
        'post_type' => 'reel',
        'posts_per_page' => 6,
        'post__not_in' => array($reel->ID),
        'meta_query' => array(
            array(
                'key' => '_reel_status',
                'value' => 'active',
                'compare' => '='
            )
        ),
        'orderby' => 'rand'
    ));
    
    if ($related_reels):
    ?>
    <div class="reel-single-related">
        <h2><?php _e('Mais Reels', 'reel-marketplace'); ?></h2>
        
        <div class="reel-single-related-grid">
            <?php foreach ($related_reels as $related_reel): ?>
                <?php
                $related_poster = get_post_meta($related_reel->ID, '_reel_video_poster', true);
                $related_author = get_user_by('id', $related_reel->post_author);
                ?>
                <div class="reel-single-related-item">
                    <a href="<?php echo home_url('/reel/' . $related_reel->post_name); ?>" class="reel-single-related-link">
                        <div class="reel-single-related-thumbnail">
                            <?php if ($related_poster): ?>
                                <img src="<?php echo esc_url($related_poster); ?>" alt="<?php echo esc_attr($related_reel->post_title); ?>" />
                            <?php else: ?>
                                <div class="reel-single-related-placeholder">
                                    <span class="material-icons">play_arrow</span>
                                </div>
                            <?php endif; ?>
                            
                            <div class="reel-single-related-overlay">
                                <span class="material-icons">play_arrow</span>
                            </div>
                        </div>
                        
                        <div class="reel-single-related-info">
                            <h4><?php echo esc_html($related_reel->post_title); ?></h4>
                            <span class="reel-single-related-author"><?php echo esc_html($related_author->display_name); ?></span>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="reel-single-related-actions">
            <a href="<?php echo home_url('/explorar/'); ?>" class="reel-btn reel-btn-primary">
                <?php _e('Ver Todos os Reels', 'reel-marketplace'); ?>
            </a>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
.reel-single-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: var(--reel-spacing-lg);
}

.reel-single-content {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--reel-spacing-xl);
    margin-bottom: var(--reel-spacing-xl);
}

.reel-single-video {
    position: relative;
    background: #000;
    border-radius: var(--reel-radius-lg);
    overflow: hidden;
    aspect-ratio: 9/16;
}

.reel-single-video-player {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border: none;
}

.reel-single-overlay {
    position: absolute;
    bottom: var(--reel-spacing-lg);
    right: var(--reel-spacing-lg);
}

.reel-single-actions {
    display: flex;
    flex-direction: column;
    gap: var(--reel-spacing-md);
}

.reel-single-actions .reel-action {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--reel-spacing-xs);
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    border: none;
    border-radius: var(--reel-radius-lg);
    padding: var(--reel-spacing-md);
    color: white;
    cursor: pointer;
    transition: all var(--reel-transition-fast);
}

.reel-action-label {
    font-size: 12px;
    font-weight: 500;
}

.reel-single-info {
    padding: var(--reel-spacing-lg);
}

.reel-single-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: var(--reel-spacing-lg);
}

.reel-single-author {
    display: flex;
    gap: var(--reel-spacing-md);
}

.reel-single-author img {
    border-radius: 50%;
}

.reel-single-author-info h3 {
    margin: 0 0 var(--reel-spacing-xs) 0;
    font-size: 18px;
    color: var(--reel-on-surface);
}

.reel-single-date {
    color: var(--reel-on-surface-variant);
    font-size: 14px;
}

.reel-single-title h1 {
    margin: 0 0 var(--reel-spacing-md) 0;
    font-size: 28px;
    line-height: 1.2;
    color: var(--reel-on-surface);
}

.reel-single-description,
.reel-single-content-text {
    margin-bottom: var(--reel-spacing-md);
    line-height: 1.6;
    color: var(--reel-on-surface);
}

.reel-single-tags {
    display: flex;
    flex-wrap: wrap;
    gap: var(--reel-spacing-sm);
    margin-top: var(--reel-spacing-lg);
}

.reel-tag {
    color: var(--reel-primary);
    text-decoration: none;
    font-weight: 500;
    font-size: 14px;
}

.reel-tag:hover {
    text-decoration: underline;
}

/* Products Section */
.reel-single-products {
    margin-bottom: var(--reel-spacing-xl);
}

.reel-single-products h2 {
    margin-bottom: var(--reel-spacing-lg);
    font-size: 24px;
    color: var(--reel-on-surface);
}

.reel-single-products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: var(--reel-spacing-lg);
}

.reel-single-product-card {
    background: var(--reel-surface-variant);
    border-radius: var(--reel-radius-lg);
    overflow: hidden;
    transition: all var(--reel-transition-normal);
}

.reel-single-product-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--reel-shadow-lg);
}

.reel-single-product-image {
    position: relative;
}

.reel-single-product-image img {
    width: 100%;
    height: 200px;
    object-fit: cover;
}

.reel-single-product-wishlist {
    position: absolute;
    top: var(--reel-spacing-sm);
    right: var(--reel-spacing-sm);
    background: rgba(255, 255, 255, 0.9);
    border: none;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all var(--reel-transition-fast);
}

.reel-single-product-info {
    padding: var(--reel-spacing-md);
}

.reel-single-product-title {
    margin: 0 0 var(--reel-spacing-sm) 0;
    font-size: 16px;
    line-height: 1.3;
}

.reel-single-product-title a {
    color: var(--reel-on-surface);
    text-decoration: none;
}

.reel-single-product-title a:hover {
    color: var(--reel-primary);
}

.reel-single-product-price {
    font-size: 18px;
    font-weight: 700;
    color: var(--reel-primary);
    margin-bottom: var(--reel-spacing-sm);
}

.reel-single-product-description {
    color: var(--reel-on-surface-variant);
    font-size: 14px;
    line-height: 1.4;
    margin-bottom: var(--reel-spacing-md);
}

.reel-single-product-actions {
    display: flex;
    gap: var(--reel-spacing-sm);
}

/* Related Reels */
.reel-single-related h2 {
    margin-bottom: var(--reel-spacing-lg);
    font-size: 24px;
    color: var(--reel-on-surface);
}

.reel-single-related-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: var(--reel-spacing-md);
    margin-bottom: var(--reel-spacing-lg);
}

.reel-single-related-item {
    background: var(--reel-surface-variant);
    border-radius: var(--reel-radius-md);
    overflow: hidden;
    transition: all var(--reel-transition-normal);
}

.reel-single-related-item:hover {
    transform: translateY(-2px);
    box-shadow: var(--reel-shadow-md);
}

.reel-single-related-link {
    display: block;
    text-decoration: none;
    color: inherit;
}

.reel-single-related-thumbnail {
    position: relative;
    aspect-ratio: 9/16;
    background: #000;
}

.reel-single-related-thumbnail img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.reel-single-related-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #333;
    color: #666;
}

.reel-single-related-overlay {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: rgba(0, 0, 0, 0.7);
    border-radius: 50%;
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    opacity: 0;
    transition: opacity var(--reel-transition-fast);
}

.reel-single-related-item:hover .reel-single-related-overlay {
    opacity: 1;
}

.reel-single-related-info {
    padding: var(--reel-spacing-md);
}

.reel-single-related-info h4 {
    margin: 0 0 var(--reel-spacing-xs) 0;
    font-size: 14px;
    line-height: 1.3;
    color: var(--reel-on-surface);
}

.reel-single-related-author {
    font-size: 12px;
    color: var(--reel-on-surface-variant);
}

.reel-single-related-actions {
    text-align: center;
}

/* Mobile responsiveness */
@media (max-width: 768px) {
    .reel-single-content {
        grid-template-columns: 1fr;
        gap: var(--reel-spacing-lg);
    }
    
    .reel-single-video {
        max-height: 70vh;
    }
    
    .reel-single-products-grid {
        grid-template-columns: 1fr;
    }
    
    .reel-single-related-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>

<?php
get_footer();
?>
