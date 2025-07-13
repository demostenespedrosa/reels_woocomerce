<?php
/**
 * Reel Share Class
 * 
 * Handles sharing functionality for reels
 */

if (!defined('ABSPATH')) {
    exit;
}

class Reel_Share {
    
    public function __construct() {
        add_action('wp_head', array($this, 'add_og_meta_tags'));
        add_action('wp_head', array($this, 'add_twitter_meta_tags'));
        add_action('wp_head', array($this, 'add_structured_data'));
    }
    
    /**
     * Add Open Graph meta tags for better sharing
     */
    public function add_og_meta_tags() {
        if (!$this->is_reel_page()) {
            return;
        }
        
        $reel = $this->get_current_reel();
        if (!$reel) {
            return;
        }
        
        $title = get_the_title($reel->ID);
        $description = get_post_field('post_excerpt', $reel->ID);
        if (empty($description)) {
            $description = wp_trim_words(get_post_field('post_content', $reel->ID), 20);
        }
        
        $video_poster = get_post_meta($reel->ID, '_reel_video_poster', true);
        $video_url = get_post_meta($reel->ID, '_reel_video_url', true);
        $video_file = get_post_meta($reel->ID, '_reel_video_file', true);
        
        $image_url = $video_poster ? $video_poster : get_the_post_thumbnail_url($reel->ID, 'large');
        $video_src = $video_file ? $video_file : $video_url;
        
        $site_name = get_bloginfo('name');
        $url = home_url('/reel/' . $reel->post_name);
        
        ?>
        <!-- Open Graph Meta Tags -->
        <meta property="og:type" content="video.other" />
        <meta property="og:title" content="<?php echo esc_attr($title); ?>" />
        <meta property="og:description" content="<?php echo esc_attr($description); ?>" />
        <meta property="og:url" content="<?php echo esc_url($url); ?>" />
        <meta property="og:site_name" content="<?php echo esc_attr($site_name); ?>" />
        
        <?php if ($image_url): ?>
        <meta property="og:image" content="<?php echo esc_url($image_url); ?>" />
        <meta property="og:image:width" content="720" />
        <meta property="og:image:height" content="1280" />
        <meta property="og:image:type" content="image/jpeg" />
        <?php endif; ?>
        
        <?php if ($video_src): ?>
        <meta property="og:video" content="<?php echo esc_url($video_src); ?>" />
        <meta property="og:video:type" content="video/mp4" />
        <meta property="og:video:width" content="720" />
        <meta property="og:video:height" content="1280" />
        <?php endif; ?>
        
        <!-- Additional meta tags -->
        <meta name="description" content="<?php echo esc_attr($description); ?>" />
        <meta name="robots" content="index, follow" />
        <link rel="canonical" href="<?php echo esc_url($url); ?>" />
        <?php
    }
    
    /**
     * Add Twitter Card meta tags
     */
    public function add_twitter_meta_tags() {
        if (!$this->is_reel_page()) {
            return;
        }
        
        $reel = $this->get_current_reel();
        if (!$reel) {
            return;
        }
        
        $title = get_the_title($reel->ID);
        $description = get_post_field('post_excerpt', $reel->ID);
        if (empty($description)) {
            $description = wp_trim_words(get_post_field('post_content', $reel->ID), 20);
        }
        
        $video_poster = get_post_meta($reel->ID, '_reel_video_poster', true);
        $image_url = $video_poster ? $video_poster : get_the_post_thumbnail_url($reel->ID, 'large');
        
        ?>
        <!-- Twitter Card Meta Tags -->
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" content="<?php echo esc_attr($title); ?>" />
        <meta name="twitter:description" content="<?php echo esc_attr($description); ?>" />
        
        <?php if ($image_url): ?>
        <meta name="twitter:image" content="<?php echo esc_url($image_url); ?>" />
        <?php endif; ?>
        
        <?php
        // Add Twitter site handle if available
        $twitter_handle = get_option('reel_marketplace_twitter_handle');
        if ($twitter_handle):
        ?>
        <meta name="twitter:site" content="<?php echo esc_attr($twitter_handle); ?>" />
        <?php endif; ?>
        <?php
    }
    
    /**
     * Add structured data (JSON-LD)
     */
    public function add_structured_data() {
        if (!$this->is_reel_page()) {
            return;
        }
        
        $reel = $this->get_current_reel();
        if (!$reel) {
            return;
        }
        
        $title = get_the_title($reel->ID);
        $description = get_post_field('post_excerpt', $reel->ID);
        if (empty($description)) {
            $description = wp_trim_words(get_post_field('post_content', $reel->ID), 20);
        }
        
        $video_poster = get_post_meta($reel->ID, '_reel_video_poster', true);
        $video_url = get_post_meta($reel->ID, '_reel_video_url', true);
        $video_file = get_post_meta($reel->ID, '_reel_video_file', true);
        $video_duration = get_post_meta($reel->ID, '_reel_video_duration', true);
        
        $video_src = $video_file ? $video_file : $video_url;
        $url = home_url('/reel/' . $reel->post_name);
        $author = get_user_by('id', $reel->post_author);
        
        // Get associated products
        $products = get_reel_products($reel->ID);
        
        $structured_data = array(
            '@context' => 'https://schema.org',
            '@type' => 'VideoObject',
            'name' => $title,
            'description' => $description,
            'contentUrl' => $video_src,
            'embedUrl' => $url,
            'uploadDate' => get_post_time('c', true, $reel->ID),
            'duration' => $video_duration ? 'PT' . $video_duration . 'S' : null,
            'publisher' => array(
                '@type' => 'Organization',
                'name' => get_bloginfo('name'),
                'url' => home_url()
            )
        );
        
        if ($video_poster) {
            $structured_data['thumbnailUrl'] = $video_poster;
        }
        
        if ($author) {
            $structured_data['author'] = array(
                '@type' => 'Person',
                'name' => $author->display_name
            );
        }
        
        // Add products as mentions
        if (!empty($products)) {
            $mentions = array();
            foreach ($products as $product) {
                $mentions[] = array(
                    '@type' => 'Product',
                    'name' => $product->get_name(),
                    'url' => $product->get_permalink(),
                    'offers' => array(
                        '@type' => 'Offer',
                        'price' => $product->get_price(),
                        'priceCurrency' => get_woocommerce_currency(),
                        'availability' => $product->is_in_stock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock'
                    )
                );
            }
            $structured_data['mentions'] = $mentions;
        }
        
        // Remove null values
        $structured_data = array_filter($structured_data, function($value) {
            return $value !== null;
        });
        
        ?>
        <script type="application/ld+json">
        <?php echo json_encode($structured_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES); ?>
        </script>
        <?php
    }
    
    /**
     * Check if current page is a reel page
     */
    private function is_reel_page() {
        return get_query_var('reel_view') || is_singular('reel');
    }
    
    /**
     * Get current reel
     */
    private function get_current_reel() {
        $reel_slug = get_query_var('reel_view');
        
        if ($reel_slug) {
            return get_page_by_path($reel_slug, OBJECT, 'reel');
        }
        
        if (is_singular('reel')) {
            return get_queried_object();
        }
        
        return null;
    }
    
    /**
     * Generate share URLs
     */
    public static function get_share_urls($reel_id, $custom_text = '') {
        $reel = get_post($reel_id);
        if (!$reel) {
            return array();
        }
        
        $url = home_url('/reel/' . $reel->post_name);
        $title = get_the_title($reel_id);
        $text = $custom_text ? $custom_text : sprintf(__('Confira este reel incrível: %s', 'reel-marketplace'), $title);
        
        return array(
            'whatsapp' => 'https://wa.me/?text=' . urlencode($text . ' ' . $url),
            'twitter' => 'https://twitter.com/intent/tweet?text=' . urlencode($text) . '&url=' . urlencode($url),
            'facebook' => 'https://www.facebook.com/sharer/sharer.php?u=' . urlencode($url),
            'linkedin' => 'https://www.linkedin.com/sharing/share-offsite/?url=' . urlencode($url),
            'telegram' => 'https://t.me/share/url?url=' . urlencode($url) . '&text=' . urlencode($text),
            'email' => 'mailto:?subject=' . urlencode($title) . '&body=' . urlencode($text . ' ' . $url),
            'copy' => $url
        );
    }
    
    /**
     * Get share buttons HTML
     */
    public static function get_share_buttons($reel_id, $include_text = true) {
        $share_urls = self::get_share_urls($reel_id);
        
        ob_start();
        ?>
        <div class="reel-share-buttons">
            <?php if ($include_text): ?>
            <span class="reel-share-label"><?php _e('Compartilhar:', 'reel-marketplace'); ?></span>
            <?php endif; ?>
            
            <?php foreach ($share_urls as $platform => $url): ?>
                <?php if ($platform === 'copy') continue; ?>
                
                <a href="<?php echo esc_url($url); ?>" 
                   class="reel-share-btn reel-share-<?php echo esc_attr($platform); ?>" 
                   target="_blank" 
                   rel="noopener noreferrer"
                   title="<?php echo esc_attr(sprintf(__('Compartilhar no %s', 'reel-marketplace'), ucfirst($platform))); ?>">
                    
                    <?php
                    switch ($platform) {
                        case 'whatsapp':
                            echo '<svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893A11.821 11.821 0 0020.885 3.488"/></svg>';
                            break;
                        case 'twitter':
                            echo '<svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/></svg>';
                            break;
                        case 'facebook':
                            echo '<svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>';
                            break;
                        case 'linkedin':
                            echo '<svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>';
                            break;
                        case 'telegram':
                            echo '<svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>';
                            break;
                        case 'email':
                            echo '<svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>';
                            break;
                        default:
                            echo '<span class="material-icons">share</span>';
                    }
                    ?>
                </a>
            <?php endforeach; ?>
            
            <button class="reel-share-btn reel-share-copy" 
                    data-url="<?php echo esc_attr($share_urls['copy']); ?>"
                    title="<?php _e('Copiar link', 'reel-marketplace'); ?>">
                <svg viewBox="0 0 24 24" width="20" height="20">
                    <path fill="currentColor" d="M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z"/>
                </svg>
            </button>
        </div>
        <?php
        
        return ob_get_clean();
    }
}
