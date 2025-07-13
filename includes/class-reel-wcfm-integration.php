<?php
/**
 * WCFM Integration Class
 * 
 * Integrates the reel functionality with WCFM marketplace
 */

if (!defined('ABSPATH')) {
    exit;
}

class Reel_WCFM_Integration {
    
    public function __construct() {
        // Only initialize if WCFM is active
        if (!$this->is_wcfm_active()) {
            return;
        }
        
        add_action('init', array($this, 'init_hooks'));
    }
    
    /**
     * Check if WCFM is active
     */
    private function is_wcfm_active() {
        return class_exists('WCFM');
    }
    
    /**
     * Initialize hooks
     */
    public function init_hooks() {
        // Add menu to WCFM dashboard
        add_filter('wcfm_menus', array($this, 'add_reel_menu'), 20);
        add_action('wcfm_load_views', array($this, 'load_reel_views'), 20);
        add_action('wcfm_load_controllers', array($this, 'load_reel_controllers'), 20);
        
        // Product integration
        add_action('after_wcfm_products_manage_form_product_types', array($this, 'add_reel_product_options'));
        add_action('wcfm_product_manage_form_submit', array($this, 'save_product_reel_data'), 10, 2);
        
        // Ajax handlers
        add_action('wp_ajax_wcfm_reel_upload', array($this, 'handle_reel_upload'));
        add_action('wp_ajax_wcfm_reel_delete', array($this, 'handle_reel_delete'));
        add_action('wp_ajax_wcfm_reel_update', array($this, 'handle_reel_update'));
        add_action('wp_ajax_wcfm_reel_analytics', array($this, 'get_reel_analytics'));
        
        // Frontend filters
        add_filter('reel_marketplace_can_create_reel', array($this, 'check_vendor_permissions'), 10, 2);
        
        // Enqueue scripts
        add_action('wcfm_load_scripts', array($this, 'enqueue_wcfm_scripts'));
        add_action('wcfm_load_styles', array($this, 'enqueue_wcfm_styles'));
        
        // Capabilities
        add_filter('wcfm_capability', array($this, 'add_reel_capabilities'));
    }
    
    /**
     * Add reel menu to WCFM
     */
    public function add_reel_menu($menus) {
        $reel_menus = array(
            'wcfm-reels' => array(
                'label'      => __('Reels', 'reel-marketplace'),
                'url'        => get_wcfm_page() . '#wcfm-reels',
                'icon'       => 'video-camera',
                'priority'   => 155,
                'capability' => 'wcfm_reel_manage',
                'submenu' => array(
                    'wcfm-reels' => array(
                        'label'      => __('Gerenciar Reels', 'reel-marketplace'),
                        'url'        => get_wcfm_page() . '#wcfm-reels',
                        'capability' => 'wcfm_reel_manage'
                    ),
                    'wcfm-reels-new' => array(
                        'label'      => __('Adicionar Reel', 'reel-marketplace'),
                        'url'        => get_wcfm_page() . '#wcfm-reels-new',
                        'capability' => 'wcfm_reel_manage'
                    ),
                    'wcfm-reels-analytics' => array(
                        'label'      => __('Analytics', 'reel-marketplace'),
                        'url'        => get_wcfm_page() . '#wcfm-reels-analytics',
                        'capability' => 'wcfm_reel_analytics'
                    )
                )
            )
        );
        
        $menus = array_merge($menus, $reel_menus);
        return $menus;
    }
    
    /**
     * Load reel views
     */
    public function load_reel_views() {
        global $WCFM, $WCFMu;
        
        $WCFM->wcfm_views_path['wcfm-reels'] = REEL_MARKETPLACE_PATH . 'includes/views/wcfm-view-reels.php';
        $WCFM->wcfm_views_path['wcfm-reels-new'] = REEL_MARKETPLACE_PATH . 'includes/views/wcfm-view-reel-manage.php';
        $WCFM->wcfm_views_path['wcfm-reels-analytics'] = REEL_MARKETPLACE_PATH . 'includes/views/wcfm-view-reel-analytics.php';
    }
    
    /**
     * Load reel controllers
     */
    public function load_reel_controllers() {
        global $WCFM;
        
        $WCFM->wcfm_controllers_path['wcfm-reels'] = REEL_MARKETPLACE_PATH . 'includes/controllers/wcfm-controller-reels.php';
        $WCFM->wcfm_controllers_path['wcfm-reels-new'] = REEL_MARKETPLACE_PATH . 'includes/controllers/wcfm-controller-reel-manage.php';
        $WCFM->wcfm_controllers_path['wcfm-reels-analytics'] = REEL_MARKETPLACE_PATH . 'includes/controllers/wcfm-controller-reel-analytics.php';
    }
    
    /**
     * Add reel capabilities
     */
    public function add_reel_capabilities($capability) {
        $capability['wcfm_reel_manage'] = 'wcfm_reel_manage';
        $capability['wcfm_reel_analytics'] = 'wcfm_reel_analytics';
        return $capability;
    }
    
    /**
     * Handle reel upload
     */
    public function handle_reel_upload() {
        global $WCFM, $WCFMu;
        
        if (!wcfm_is_vendor()) {
            wp_send_json_error(__('Acesso negado', 'reel-marketplace'));
        }
        
        check_ajax_referer('wcfm_ajax_nonce', 'wcfm_ajax_nonce');
        
        $vendor_id = apply_filters('wcfm_current_vendor_id', get_current_user_id());
        
        // Handle file upload
        if (!empty($_FILES['reel_video'])) {
            $upload_dir = wp_upload_dir();
            $reel_dir = $upload_dir['basedir'] . '/reels/';
            
            if (!file_exists($reel_dir)) {
                wp_mkdir_p($reel_dir);
            }
            
            $file = $_FILES['reel_video'];
            $filename = sanitize_file_name($file['name']);
            $filename = time() . '_' . $vendor_id . '_' . $filename;
            $file_path = $reel_dir . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $file_path)) {
                $file_url = $upload_dir['baseurl'] . '/reels/' . $filename;
                
                // Process video if needed
                do_action('reel_marketplace_video_uploaded', $file_path, $vendor_id);
                
                wp_send_json_success(array(
                    'file_url' => $file_url,
                    'file_path' => $file_path,
                    'message' => __('Vídeo enviado com sucesso', 'reel-marketplace')
                ));
            }
        }
        
        wp_send_json_error(__('Erro no upload do vídeo', 'reel-marketplace'));
    }
    
    /**
     * Handle reel creation/update
     */
    public function handle_reel_update() {
        global $WCFM, $WCFMu;
        
        if (!wcfm_is_vendor()) {
            wp_send_json_error(__('Acesso negado', 'reel-marketplace'));
        }
        
        check_ajax_referer('wcfm_ajax_nonce', 'wcfm_ajax_nonce');
        
        $vendor_id = apply_filters('wcfm_current_vendor_id', get_current_user_id());
        $reel_id = isset($_POST['reel_id']) ? intval($_POST['reel_id']) : 0;
        
        // Validate data
        $title = sanitize_text_field($_POST['reel_title']);
        $description = sanitize_textarea_field($_POST['reel_description']);
        $video_url = esc_url($_POST['reel_video_url']);
        $product_id = intval($_POST['reel_product_id']);
        $tags = sanitize_text_field($_POST['reel_tags']);
        
        if (empty($title) || empty($video_url)) {
            wp_send_json_error(__('Título e vídeo são obrigatórios', 'reel-marketplace'));
        }
        
        // Create or update reel
        $reel_data = array(
            'post_title' => $title,
            'post_content' => $description,
            'post_status' => 'publish',
            'post_type' => 'reel',
            'post_author' => $vendor_id
        );
        
        if ($reel_id > 0) {
            // Update existing reel
            $reel_data['ID'] = $reel_id;
            $result = wp_update_post($reel_data);
        } else {
            // Create new reel
            $result = wp_insert_post($reel_data);
            $reel_id = $result;
        }
        
        if (!is_wp_error($result) && $result > 0) {
            // Save meta data
            update_post_meta($reel_id, '_reel_video_url', $video_url);
            update_post_meta($reel_id, '_reel_product_id', $product_id);
            
            // Save tags
            if (!empty($tags)) {
                $tag_array = array_map('trim', explode(',', $tags));
                wp_set_object_terms($reel_id, $tag_array, 'reel_tag');
            }
            
            // Generate thumbnail
            do_action('reel_marketplace_generate_thumbnail', $reel_id, $video_url);
            
            wp_send_json_success(array(
                'reel_id' => $reel_id,
                'message' => __('Reel salvo com sucesso', 'reel-marketplace')
            ));
        } else {
            wp_send_json_error(__('Erro ao salvar reel', 'reel-marketplace'));
        }
    }
    
    /**
     * Handle reel deletion
     */
    public function handle_reel_delete() {
        global $WCFM;
        
        if (!wcfm_is_vendor()) {
            wp_send_json_error(__('Acesso negado', 'reel-marketplace'));
        }
        
        check_ajax_referer('wcfm_ajax_nonce', 'wcfm_ajax_nonce');
        
        $vendor_id = apply_filters('wcfm_current_vendor_id', get_current_user_id());
        $reel_id = intval($_POST['reel_id']);
        
        $reel = get_post($reel_id);
        if (!$reel || $reel->post_author != $vendor_id) {
            wp_send_json_error(__('Reel não encontrado', 'reel-marketplace'));
        }
        
        if (wp_delete_post($reel_id, true)) {
            wp_send_json_success(__('Reel deletado com sucesso', 'reel-marketplace'));
        } else {
            wp_send_json_error(__('Erro ao deletar reel', 'reel-marketplace'));
        }
    }
    
    /**
     * Get reel analytics
     */
    public function get_reel_analytics() {
        global $WCFM;
        
        if (!wcfm_is_vendor()) {
            wp_send_json_error(__('Acesso negado', 'reel-marketplace'));
        }
        
        check_ajax_referer('wcfm_ajax_nonce', 'wcfm_ajax_nonce');
        
        $vendor_id = apply_filters('wcfm_current_vendor_id', get_current_user_id());
        $period = sanitize_text_field($_POST['period']) ?: '30';
        
        $analytics = $this->get_vendor_analytics($vendor_id, $period);
        
        wp_send_json_success($analytics);
    }
    
    /**
     * Get vendor analytics data
     */
    private function get_vendor_analytics($vendor_id, $period = '30') {
        global $wpdb;
        
        $date_from = date('Y-m-d', strtotime("-{$period} days"));
        
        // Get total reels
        $total_reels = get_posts(array(
            'post_type' => 'reel',
            'author' => $vendor_id,
            'post_status' => 'publish',
            'numberposts' => -1,
            'fields' => 'ids'
        ));
        
        // Get views
        $views = $wpdb->get_var($wpdb->prepare("
            SELECT SUM(rv.view_count) 
            FROM {$wpdb->prefix}reel_views rv
            INNER JOIN {$wpdb->posts} p ON rv.reel_id = p.ID
            WHERE p.post_author = %d 
            AND rv.view_date >= %s
        ", $vendor_id, $date_from));
        
        // Get likes
        $likes = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) 
            FROM {$wpdb->prefix}reel_interactions ri
            INNER JOIN {$wpdb->posts} p ON ri.reel_id = p.ID
            WHERE p.post_author = %d 
            AND ri.interaction_type = 'like'
            AND ri.created_at >= %s
        ", $vendor_id, $date_from));
        
        // Get shares
        $shares = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) 
            FROM {$wpdb->prefix}reel_interactions ri
            INNER JOIN {$wpdb->posts} p ON ri.reel_id = p.ID
            WHERE p.post_author = %d 
            AND ri.interaction_type = 'share'
            AND ri.created_at >= %s
        ", $vendor_id, $date_from));
        
        // Get daily stats
        $daily_stats = $wpdb->get_results($wpdb->prepare("
            SELECT 
                DATE(rv.view_date) as date,
                SUM(rv.view_count) as views,
                COUNT(DISTINCT rv.reel_id) as active_reels
            FROM {$wpdb->prefix}reel_views rv
            INNER JOIN {$wpdb->posts} p ON rv.reel_id = p.ID
            WHERE p.post_author = %d 
            AND rv.view_date >= %s
            GROUP BY DATE(rv.view_date)
            ORDER BY date ASC
        ", $vendor_id, $date_from));
        
        // Get top performing reels
        $top_reels = $wpdb->get_results($wpdb->prepare("
            SELECT 
                p.ID,
                p.post_title,
                SUM(rv.view_count) as total_views,
                COUNT(DISTINCT ri.id) as total_likes
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->prefix}reel_views rv ON p.ID = rv.reel_id
            LEFT JOIN {$wpdb->prefix}reel_interactions ri ON p.ID = ri.reel_id AND ri.interaction_type = 'like'
            WHERE p.post_type = 'reel' 
            AND p.post_author = %d
            AND p.post_status = 'publish'
            GROUP BY p.ID
            ORDER BY total_views DESC
            LIMIT 10
        ", $vendor_id));
        
        return array(
            'summary' => array(
                'total_reels' => count($total_reels),
                'total_views' => intval($views),
                'total_likes' => intval($likes),
                'total_shares' => intval($shares),
                'engagement_rate' => $views > 0 ? round((($likes + $shares) / $views) * 100, 2) : 0
            ),
            'daily_stats' => $daily_stats,
            'top_reels' => $top_reels,
            'period' => $period
        );
    }
    
    /**
     * Check vendor permissions
     */
    public function check_vendor_permissions($can_create, $user_id) {
        if (!wcfm_is_vendor($user_id)) {
            return false;
        }
        
        // Check if vendor has reel capability
        if (!current_user_can('wcfm_reel_manage')) {
            return false;
        }
        
        return $can_create;
    }
    
    /**
     * Add reel options to product form
     */
    public function add_reel_product_options($product_id = 0) {
        global $WCFM, $WCFMu;
        
        $vendor_id = apply_filters('wcfm_current_vendor_id', get_current_user_id());
        
        // Get vendor's reels
        $vendor_reels = get_posts(array(
            'post_type' => 'reel',
            'author' => $vendor_id,
            'post_status' => 'publish',
            'numberposts' => -1
        ));
        
        $associated_reels = $product_id ? get_post_meta($product_id, '_associated_reels', true) : array();
        if (!is_array($associated_reels)) {
            $associated_reels = array();
        }
        ?>
        <div class="wcfm_clearfix"></div>
        <div class="wcfm-container">
            <div id="wcfm_products_manage_form_reel_head" class="wcfm-collapse-head">
                <label class="wcfm_title">
                    <span class="wcfmfa fa-video-camera text_tip" data-tip="<?php _e('Associar Reels', 'reel-marketplace'); ?>"></span>
                    <?php _e('Reels Associados', 'reel-marketplace'); ?>
                </label>
            </div>
            <div class="wcfm-container">
                <div id="wcfm_products_manage_form_reel_expander" class="wcfm-content">
                    <div class="wcfm_clearfix"></div>
                    
                    <?php if (!empty($vendor_reels)): ?>
                        <div class="wcfm-tablewrap">
                            <table class="wcfm-table" cellpadding="2" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th><?php _e('Selecionar', 'reel-marketplace'); ?></th>
                                        <th><?php _e('Reel', 'reel-marketplace'); ?></th>
                                        <th><?php _e('Título', 'reel-marketplace'); ?></th>
                                        <th><?php _e('Visualizações', 'reel-marketplace'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($vendor_reels as $reel): ?>
                                        <tr>
                                            <td>
                                                <input type="checkbox" 
                                                       name="associated_reels[]" 
                                                       value="<?php echo esc_attr($reel->ID); ?>"
                                                       <?php checked(in_array($reel->ID, $associated_reels)); ?>>
                                            </td>
                                            <td>
                                                <?php
                                                $thumbnail = get_post_meta($reel->ID, '_reel_thumbnail', true);
                                                if ($thumbnail): ?>
                                                    <img src="<?php echo esc_url($thumbnail); ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                                                <?php else: ?>
                                                    <div style="width: 50px; height: 50px; background: #f0f0f0; display: flex; align-items: center; justify-content: center; border-radius: 4px;">
                                                        <span class="wcfmfa fa-video-camera"></span>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo esc_html($reel->post_title); ?></td>
                                            <td><?php echo $this->get_reel_views($reel->ID); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="wcfm-message">
                            <p><?php _e('Você não tem nenhum reel criado ainda.', 'reel-marketplace'); ?></p>
                            <a href="<?php echo get_wcfm_page() . '#wcfm-reels-new'; ?>" class="wcfm_submit_button">
                                <?php _e('Criar Primeiro Reel', 'reel-marketplace'); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                    
                    <div class="wcfm_clearfix"></div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Save product reel data
     */
    public function save_product_reel_data($product_id, $wcfm_products_manage_form_data) {
        if (isset($wcfm_products_manage_form_data['associated_reels'])) {
            $associated_reels = array_map('intval', $wcfm_products_manage_form_data['associated_reels']);
            update_post_meta($product_id, '_associated_reels', $associated_reels);
            
            // Update reverse relationship
            foreach ($associated_reels as $reel_id) {
                $current_products = get_post_meta($reel_id, '_reel_product_id', true);
                if (!$current_products) {
                    update_post_meta($reel_id, '_reel_product_id', $product_id);
                }
            }
        }
    }
    
    /**
     * Get reel views count
     */
    private function get_reel_views($reel_id) {
        global $wpdb;
        
        $views = $wpdb->get_var($wpdb->prepare("
            SELECT SUM(view_count) 
            FROM {$wpdb->prefix}reel_views 
            WHERE reel_id = %d
        ", $reel_id));
        
        return number_format(intval($views));
    }
    
    /**
     * Enqueue WCFM scripts
     */
    public function enqueue_wcfm_scripts() {
        if (wcfm_is_vendor()) {
            wp_enqueue_script('wcfm-reel-vendor', REEL_MARKETPLACE_URL . 'assets/js/wcfm-vendor.js', array('jquery'), '1.0.0', true);
            
            wp_localize_script('wcfm-reel-vendor', 'wcfmReelAjax', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wcfm_ajax_nonce'),
                'messages' => array(
                    'confirmDelete' => __('Tem certeza que deseja deletar este reel?', 'reel-marketplace'),
                    'uploading' => __('Enviando vídeo...', 'reel-marketplace'),
                    'processing' => __('Processando...', 'reel-marketplace')
                )
            ));
        }
    }
    
    /**
     * Enqueue WCFM styles
     */
    public function enqueue_wcfm_styles() {
        if (wcfm_is_vendor()) {
            wp_enqueue_style('wcfm-reel-vendor', REEL_MARKETPLACE_URL . 'assets/css/wcfm-vendor.css', array(), '1.0.0');
        }
    }
}

// Initialize
new Reel_WCFM_Integration();
