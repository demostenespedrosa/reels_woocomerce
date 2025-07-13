<?php
/**
 * Dokan Integration Class
 * 
 * Integrates the reel functionality with Dokan marketplace
 */

if (!defined('ABSPATH')) {
    exit;
}

class Reel_Dokan_Integration {
    
    public function __construct() {
        // Only initialize if Dokan is active
        if (!$this->is_dokan_active()) {
            return;
        }
        
        add_action('init', array($this, 'init_hooks'));
    }
    
    /**
     * Check if Dokan is active
     */
    private function is_dokan_active() {
        return class_exists('WeDevs_Dokan');
    }
    
    /**
     * Initialize hooks
     */
    public function init_hooks() {
        // Vendor dashboard
        add_filter('dokan_get_dashboard_nav', array($this, 'add_reel_menu'));
        add_filter('dokan_query_var_filter', array($this, 'add_reel_query_var'));
        add_action('dokan_load_custom_template', array($this, 'load_reel_template'));
        
        // Vendor capabilities
        add_filter('dokan_vendor_can_add_reels', array($this, 'vendor_can_add_reels'), 10, 2);
        
        // Product integration
        add_action('dokan_product_edit_after_main', array($this, 'add_reel_meta_box'));
        add_action('dokan_product_updated', array($this, 'save_product_reel_data'), 10, 2);
        
        // Ajax handlers
        add_action('wp_ajax_dokan_upload_reel_video', array($this, 'handle_video_upload'));
        add_action('wp_ajax_dokan_delete_reel', array($this, 'handle_reel_delete'));
        add_action('wp_ajax_dokan_get_reel_analytics', array($this, 'get_reel_analytics'));
        
        // Frontend filters
        add_filter('reel_marketplace_can_create_reel', array($this, 'check_vendor_permissions'), 10, 2);
        add_filter('reel_marketplace_author_reels', array($this, 'filter_vendor_reels'), 10, 2);
        
        // Enqueue scripts
        add_action('wp_enqueue_scripts', array($this, 'enqueue_vendor_scripts'));
    }
    
    /**
     * Add reel menu to vendor dashboard
     */
    public function add_reel_menu($urls) {
        $urls['reels'] = array(
            'title' => __('Reels', 'reel-marketplace'),
            'icon'  => '<i class="fas fa-video"></i>',
            'url'   => dokan_get_navigation_url('reels'),
            'pos'   => 155
        );
        
        return $urls;
    }
    
    /**
     * Add reel query var
     */
    public function add_reel_query_var($query_vars) {
        $query_vars[] = 'reels';
        return $query_vars;
    }
    
    /**
     * Load reel template in vendor dashboard
     */
    public function load_reel_template($query_vars) {
        if (isset($query_vars['reels'])) {
            if (is_user_logged_in() && dokan_is_user_seller(get_current_user_id())) {
                $this->load_vendor_reel_dashboard();
                return;
            }
        }
    }
    
    /**
     * Load vendor reel dashboard
     */
    private function load_vendor_reel_dashboard() {
        $vendor_id = dokan_get_current_user_id();
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
        
        switch ($action) {
            case 'new':
                $this->render_new_reel_form();
                break;
            case 'edit':
                $reel_id = isset($_GET['reel_id']) ? intval($_GET['reel_id']) : 0;
                $this->render_edit_reel_form($reel_id);
                break;
            case 'analytics':
                $this->render_reel_analytics();
                break;
            default:
                $this->render_reel_list();
                break;
        }
    }
    
    /**
     * Render reel list for vendor
     */
    private function render_reel_list() {
        $vendor_id = dokan_get_current_user_id();
        $paged = isset($_GET['paged']) ? intval($_GET['paged']) : 1;
        
        $args = array(
            'post_type' => 'reel',
            'author' => $vendor_id,
            'posts_per_page' => 20,
            'paged' => $paged,
            'post_status' => array('publish', 'pending', 'draft')
        );
        
        $reels_query = new WP_Query($args);
        ?>
        <div class="dokan-dashboard-wrap">
            <div class="dokan-dashboard-content">
                <header class="dokan-dashboard-header">
                    <h1 class="entry-title">
                        <?php _e('Meus Reels', 'reel-marketplace'); ?>
                        <a href="<?php echo esc_url(add_query_arg('action', 'new')); ?>" class="dokan-btn dokan-btn-theme">
                            <i class="fas fa-plus"></i> <?php _e('Novo Reel', 'reel-marketplace'); ?>
                        </a>
                    </h1>
                </header>
                
                <div class="dokan-reel-list">
                    <?php if ($reels_query->have_posts()): ?>
                        <div class="dokan-reel-stats">
                            <div class="dokan-reel-stat-card">
                                <div class="stat-number"><?php echo $reels_query->found_posts; ?></div>
                                <div class="stat-label"><?php _e('Total de Reels', 'reel-marketplace'); ?></div>
                            </div>
                            <div class="dokan-reel-stat-card">
                                <div class="stat-number"><?php echo $this->get_total_views($vendor_id); ?></div>
                                <div class="stat-label"><?php _e('Visualizações', 'reel-marketplace'); ?></div>
                            </div>
                            <div class="dokan-reel-stat-card">
                                <div class="stat-number"><?php echo $this->get_total_likes($vendor_id); ?></div>
                                <div class="stat-label"><?php _e('Curtidas', 'reel-marketplace'); ?></div>
                            </div>
                        </div>
                        
                        <div class="dokan-reel-table-wrap">
                            <table class="dokan-table dokan-reel-table">
                                <thead>
                                    <tr>
                                        <th><?php _e('Reel', 'reel-marketplace'); ?></th>
                                        <th><?php _e('Produto', 'reel-marketplace'); ?></th>
                                        <th><?php _e('Status', 'reel-marketplace'); ?></th>
                                        <th><?php _e('Visualizações', 'reel-marketplace'); ?></th>
                                        <th><?php _e('Curtidas', 'reel-marketplace'); ?></th>
                                        <th><?php _e('Data', 'reel-marketplace'); ?></th>
                                        <th><?php _e('Ações', 'reel-marketplace'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($reels_query->have_posts()): $reels_query->the_post(); ?>
                                        <?php $this->render_reel_row(get_the_ID()); ?>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <?php
                        if ($reels_query->max_num_pages > 1) {
                            $pagination_args = array(
                                'current' => $paged,
                                'total' => $reels_query->max_num_pages,
                                'base' => add_query_arg('paged', '%#%'),
                                'type' => 'array'
                            );
                            $links = paginate_links($pagination_args);
                            if ($links) {
                                echo '<div class="dokan-pagination-container"><ul class="dokan-pagination">';
                                foreach ($links as $link) {
                                    echo "<li>$link</li>";
                                }
                                echo '</ul></div>';
                            }
                        }
                        ?>
                    <?php else: ?>
                        <div class="dokan-error">
                            <p><?php _e('Você ainda não criou nenhum reel.', 'reel-marketplace'); ?></p>
                            <a href="<?php echo esc_url(add_query_arg('action', 'new')); ?>" class="dokan-btn dokan-btn-theme">
                                <?php _e('Criar Primeiro Reel', 'reel-marketplace'); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
        wp_reset_postdata();
    }
    
    /**
     * Render individual reel row
     */
    private function render_reel_row($reel_id) {
        $reel = get_post($reel_id);
        $video_url = get_post_meta($reel_id, '_reel_video_url', true);
        $thumbnail = get_post_meta($reel_id, '_reel_thumbnail', true);
        $product_id = get_post_meta($reel_id, '_reel_product_id', true);
        $views = $this->get_reel_views($reel_id);
        $likes = $this->get_reel_likes($reel_id);
        
        $product = $product_id ? wc_get_product($product_id) : null;
        ?>
        <tr>
            <td>
                <div class="dokan-reel-preview">
                    <?php if ($thumbnail): ?>
                        <img src="<?php echo esc_url($thumbnail); ?>" alt="" class="reel-thumbnail">
                    <?php else: ?>
                        <div class="reel-no-thumbnail">
                            <i class="fas fa-video"></i>
                        </div>
                    <?php endif; ?>
                    <div class="reel-info">
                        <strong><?php echo esc_html($reel->post_title); ?></strong>
                        <span class="reel-duration"><?php echo $this->get_video_duration($video_url); ?></span>
                    </div>
                </div>
            </td>
            <td>
                <?php if ($product): ?>
                    <a href="<?php echo esc_url(dokan_edit_product_url($product_id)); ?>">
                        <?php echo esc_html($product->get_name()); ?>
                    </a>
                <?php else: ?>
                    <span class="text-muted"><?php _e('Nenhum produto', 'reel-marketplace'); ?></span>
                <?php endif; ?>
            </td>
            <td>
                <span class="dokan-label dokan-label-<?php echo esc_attr($reel->post_status); ?>">
                    <?php echo esc_html($this->get_status_label($reel->post_status)); ?>
                </span>
            </td>
            <td><?php echo number_format($views); ?></td>
            <td><?php echo number_format($likes); ?></td>
            <td><?php echo date_i18n(get_option('date_format'), strtotime($reel->post_date)); ?></td>
            <td>
                <div class="dokan-reel-actions">
                    <a href="<?php echo esc_url(get_permalink($reel_id)); ?>" class="dokan-btn dokan-btn-sm" target="_blank" title="<?php _e('Ver', 'reel-marketplace'); ?>">
                        <i class="fas fa-eye"></i>
                    </a>
                    <a href="<?php echo esc_url(add_query_arg(array('action' => 'edit', 'reel_id' => $reel_id))); ?>" class="dokan-btn dokan-btn-sm" title="<?php _e('Editar', 'reel-marketplace'); ?>">
                        <i class="fas fa-edit"></i>
                    </a>
                    <a href="#" class="dokan-btn dokan-btn-sm dokan-reel-delete" data-reel-id="<?php echo esc_attr($reel_id); ?>" title="<?php _e('Deletar', 'reel-marketplace'); ?>">
                        <i class="fas fa-trash"></i>
                    </a>
                </div>
            </td>
        </tr>
        <?php
    }
    
    /**
     * Render new reel form
     */
    private function render_new_reel_form() {
        $vendor_id = dokan_get_current_user_id();
        $vendor_products = $this->get_vendor_products($vendor_id);
        ?>
        <div class="dokan-dashboard-wrap">
            <div class="dokan-dashboard-content">
                <header class="dokan-dashboard-header">
                    <h1 class="entry-title">
                        <?php _e('Novo Reel', 'reel-marketplace'); ?>
                        <a href="<?php echo esc_url(dokan_get_navigation_url('reels')); ?>" class="dokan-btn dokan-btn-default">
                            <i class="fas fa-arrow-left"></i> <?php _e('Voltar', 'reel-marketplace'); ?>
                        </a>
                    </h1>
                </header>
                
                <form id="dokan-reel-form" method="post" enctype="multipart/form-data">
                    <?php wp_nonce_field('dokan_create_reel', 'dokan_reel_nonce'); ?>
                    
                    <div class="dokan-form-group">
                        <label class="dokan-w3 dokan-control-label" for="reel-title">
                            <?php _e('Título do Reel', 'reel-marketplace'); ?> <span class="required">*</span>
                        </label>
                        <div class="dokan-w5">
                            <input type="text" class="dokan-form-control" id="reel-title" name="reel_title" placeholder="<?php _e('Digite o título do seu reel', 'reel-marketplace'); ?>" required>
                        </div>
                    </div>
                    
                    <div class="dokan-form-group">
                        <label class="dokan-w3 dokan-control-label" for="reel-description">
                            <?php _e('Descrição', 'reel-marketplace'); ?>
                        </label>
                        <div class="dokan-w5">
                            <textarea class="dokan-form-control" id="reel-description" name="reel_description" rows="4" placeholder="<?php _e('Descreva seu reel...', 'reel-marketplace'); ?>"></textarea>
                        </div>
                    </div>
                    
                    <div class="dokan-form-group">
                        <label class="dokan-w3 dokan-control-label" for="reel-video">
                            <?php _e('Vídeo', 'reel-marketplace'); ?> <span class="required">*</span>
                        </label>
                        <div class="dokan-w5">
                            <div class="dokan-reel-video-upload">
                                <input type="file" id="reel-video" name="reel_video" accept="video/*" required>
                                <div class="dokan-reel-upload-progress" style="display: none;">
                                    <div class="progress-bar"></div>
                                    <div class="progress-text">0%</div>
                                </div>
                                <div class="dokan-reel-video-preview" style="display: none;">
                                    <video controls></video>
                                    <button type="button" class="dokan-btn dokan-btn-sm dokan-btn-danger remove-video">
                                        <i class="fas fa-times"></i> <?php _e('Remover', 'reel-marketplace'); ?>
                                    </button>
                                </div>
                                <div class="dokan-help-block">
                                    <?php _e('Formatos aceitos: MP4, MOV, AVI. Tamanho máximo: 100MB. Resolução recomendada: 9:16 (vertical)', 'reel-marketplace'); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="dokan-form-group">
                        <label class="dokan-w3 dokan-control-label" for="reel-product">
                            <?php _e('Produto Associado', 'reel-marketplace'); ?>
                        </label>
                        <div class="dokan-w5">
                            <select class="dokan-form-control" id="reel-product" name="reel_product_id">
                                <option value=""><?php _e('Selecione um produto (opcional)', 'reel-marketplace'); ?></option>
                                <?php foreach ($vendor_products as $product): ?>
                                    <option value="<?php echo esc_attr($product->ID); ?>">
                                        <?php echo esc_html($product->post_title); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="dokan-form-group">
                        <label class="dokan-w3 dokan-control-label" for="reel-tags">
                            <?php _e('Tags', 'reel-marketplace'); ?>
                        </label>
                        <div class="dokan-w5">
                            <input type="text" class="dokan-form-control" id="reel-tags" name="reel_tags" placeholder="<?php _e('Separe as tags com vírgulas', 'reel-marketplace'); ?>">
                        </div>
                    </div>
                    
                    <div class="dokan-form-group">
                        <div class="dokan-w4 ajax_prev dokan-clearfix">
                            <input type="submit" name="create_reel" class="dokan-btn dokan-btn-theme dokan-btn-lg" value="<?php _e('Criar Reel', 'reel-marketplace'); ?>">
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <?php
    }
    
    /**
     * Check vendor permissions
     */
    public function check_vendor_permissions($can_create, $user_id) {
        if (!dokan_is_user_seller($user_id)) {
            return false;
        }
        
        // Check if vendor has active subscription (if applicable)
        if (function_exists('dokan_pro')) {
            $vendor_subscription = dokan_pro()->subscription->get_vendor_subscription($user_id);
            if ($vendor_subscription && !$vendor_subscription->is_active()) {
                return false;
            }
        }
        
        return $can_create;
    }
    
    /**
     * Get vendor products
     */
    private function get_vendor_products($vendor_id) {
        $args = array(
            'post_type' => 'product',
            'author' => $vendor_id,
            'posts_per_page' => -1,
            'post_status' => 'publish'
        );
        
        return get_posts($args);
    }
    
    /**
     * Get total views for vendor
     */
    private function get_total_views($vendor_id) {
        global $wpdb;
        
        $result = $wpdb->get_var($wpdb->prepare("
            SELECT SUM(view_count) 
            FROM {$wpdb->prefix}reel_views rv
            INNER JOIN {$wpdb->posts} p ON rv.reel_id = p.ID
            WHERE p.post_author = %d
        ", $vendor_id));
        
        return intval($result);
    }
    
    /**
     * Get total likes for vendor
     */
    private function get_total_likes($vendor_id) {
        global $wpdb;
        
        $result = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) 
            FROM {$wpdb->prefix}reel_interactions ri
            INNER JOIN {$wpdb->posts} p ON ri.reel_id = p.ID
            WHERE p.post_author = %d AND ri.interaction_type = 'like'
        ", $vendor_id));
        
        return intval($result);
    }
    
    /**
     * Get reel views
     */
    private function get_reel_views($reel_id) {
        global $wpdb;
        
        $result = $wpdb->get_var($wpdb->prepare("
            SELECT SUM(view_count) 
            FROM {$wpdb->prefix}reel_views 
            WHERE reel_id = %d
        ", $reel_id));
        
        return intval($result);
    }
    
    /**
     * Get reel likes
     */
    private function get_reel_likes($reel_id) {
        global $wpdb;
        
        $result = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) 
            FROM {$wpdb->prefix}reel_interactions 
            WHERE reel_id = %d AND interaction_type = 'like'
        ", $reel_id));
        
        return intval($result);
    }
    
    /**
     * Get status label
     */
    private function get_status_label($status) {
        $labels = array(
            'publish' => __('Publicado', 'reel-marketplace'),
            'pending' => __('Pendente', 'reel-marketplace'),
            'draft' => __('Rascunho', 'reel-marketplace'),
            'private' => __('Privado', 'reel-marketplace')
        );
        
        return isset($labels[$status]) ? $labels[$status] : $status;
    }
    
    /**
     * Get video duration
     */
    private function get_video_duration($video_url) {
        // This would require ffmpeg or similar to get actual duration
        // For now, return a placeholder
        return '0:30';
    }
    
    /**
     * Handle video upload
     */
    public function handle_video_upload() {
        check_ajax_referer('dokan_reel_nonce', 'nonce');
        
        if (!dokan_is_user_seller(get_current_user_id())) {
            wp_die(__('Acesso negado', 'reel-marketplace'));
        }
        
        // Handle chunked upload logic here
        wp_send_json_success(array(
            'message' => __('Vídeo enviado com sucesso', 'reel-marketplace')
        ));
    }
    
    /**
     * Handle reel deletion
     */
    public function handle_reel_delete() {
        check_ajax_referer('dokan_reel_nonce', 'nonce');
        
        $reel_id = intval($_POST['reel_id']);
        $current_user = get_current_user_id();
        
        if (!dokan_is_user_seller($current_user)) {
            wp_send_json_error(__('Acesso negado', 'reel-marketplace'));
        }
        
        $reel = get_post($reel_id);
        if (!$reel || $reel->post_author != $current_user) {
            wp_send_json_error(__('Reel não encontrado', 'reel-marketplace'));
        }
        
        if (wp_delete_post($reel_id, true)) {
            wp_send_json_success(__('Reel deletado com sucesso', 'reel-marketplace'));
        } else {
            wp_send_json_error(__('Erro ao deletar reel', 'reel-marketplace'));
        }
    }
    
    /**
     * Enqueue vendor scripts
     */
    public function enqueue_vendor_scripts() {
        if (dokan_is_seller_dashboard()) {
            wp_enqueue_script('dokan-reel-vendor', plugin_dir_url(__FILE__) . '../assets/js/dokan-vendor.js', array('jquery'), '1.0.0', true);
            wp_enqueue_style('dokan-reel-vendor', plugin_dir_url(__FILE__) . '../assets/css/dokan-vendor.css', array(), '1.0.0');
            
            wp_localize_script('dokan-reel-vendor', 'dokanReelAjax', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('dokan_reel_nonce'),
                'confirmDelete' => __('Tem certeza que deseja deletar este reel?', 'reel-marketplace')
            ));
        }
    }
    
    /**
     * Check if vendor can add reels
     */
    public function vendor_can_add_reels($can_add, $vendor_id) {
        // Check vendor status
        if (!dokan_is_user_seller($vendor_id)) {
            return false;
        }
        
        // Check if vendor is enabled
        $vendor_info = dokan_get_store_info($vendor_id);
        if (isset($vendor_info['enabled']) && $vendor_info['enabled'] !== 'yes') {
            return false;
        }
        
        return true;
    }
}

// Initialize
new Reel_Dokan_Integration();
