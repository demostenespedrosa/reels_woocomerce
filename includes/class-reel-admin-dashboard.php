<?php
/**
 * Admin Dashboard Class
 * 
 * Handles the admin dashboard interface for reel management
 */

if (!defined('ABSPATH')) {
    exit;
}

class Reel_Admin_Dashboard {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menus'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_reel_admin_analytics', array($this, 'get_admin_analytics'));
        add_action('wp_ajax_reel_moderate_content', array($this, 'moderate_content'));
        add_action('wp_ajax_reel_bulk_action', array($this, 'handle_bulk_actions'));
    }
    
    /**
     * Add admin menus
     */
    public function add_admin_menus() {
        // Main menu
        add_menu_page(
            __('Reel Marketplace', 'reel-marketplace'),
            __('Reels', 'reel-marketplace'),
            'manage_options',
            'reel-marketplace',
            array($this, 'dashboard_page'),
            'dashicons-video-alt3',
            30
        );
        
        // Submenu pages
        add_submenu_page(
            'reel-marketplace',
            __('Dashboard', 'reel-marketplace'),
            __('Dashboard', 'reel-marketplace'),
            'manage_options',
            'reel-marketplace',
            array($this, 'dashboard_page')
        );
        
        add_submenu_page(
            'reel-marketplace',
            __('Todos os Reels', 'reel-marketplace'),
            __('Todos os Reels', 'reel-marketplace'),
            'manage_options',
            'reel-all-reels',
            array($this, 'all_reels_page')
        );
        
        add_submenu_page(
            'reel-marketplace',
            __('Analytics', 'reel-marketplace'),
            __('Analytics', 'reel-marketplace'),
            'manage_options',
            'reel-analytics',
            array($this, 'analytics_page')
        );
        
        add_submenu_page(
            'reel-marketplace',
            __('Configurações', 'reel-marketplace'),
            __('Configurações', 'reel-marketplace'),
            'manage_options',
            'reel-settings',
            array($this, 'settings_page')
        );
        
        add_submenu_page(
            'reel-marketplace',
            __('Moderação', 'reel-marketplace'),
            __('Moderação', 'reel-marketplace'),
            'manage_options',
            'reel-moderation',
            array($this, 'moderation_page')
        );
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'reel-') === false) {
            return;
        }
        
        wp_enqueue_script('reel-admin', REEL_MARKETPLACE_URL . 'assets/js/admin.js', array('jquery', 'chart-js'), '1.0.0', true);
        wp_enqueue_style('reel-admin', REEL_MARKETPLACE_URL . 'assets/css/admin.css', array(), '1.0.0');
        
        // Chart.js for analytics
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '3.9.1', true);
        
        wp_localize_script('reel-admin', 'reelAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('reel_admin_nonce'),
            'strings' => array(
                'confirmDelete' => __('Tem certeza que deseja deletar este reel?', 'reel-marketplace'),
                'confirmApprove' => __('Aprovar este reel?', 'reel-marketplace'),
                'confirmReject' => __('Rejeitar este reel?', 'reel-marketplace'),
                'processing' => __('Processando...', 'reel-marketplace'),
                'error' => __('Erro na operação', 'reel-marketplace')
            )
        ));
    }
    
    /**
     * Dashboard page
     */
    public function dashboard_page() {
        $stats = $this->get_dashboard_stats();
        ?>
        <div class="wrap reel-admin-dashboard">
            <h1 class="wp-heading-inline"><?php _e('Reel Marketplace Dashboard', 'reel-marketplace'); ?></h1>
            
            <div class="reel-admin-stats">
                <div class="reel-stat-card">
                    <div class="reel-stat-icon">
                        <span class="dashicons dashicons-video-alt3"></span>
                    </div>
                    <div class="reel-stat-content">
                        <div class="reel-stat-number"><?php echo number_format($stats['total_reels']); ?></div>
                        <div class="reel-stat-label"><?php _e('Total de Reels', 'reel-marketplace'); ?></div>
                    </div>
                </div>
                
                <div class="reel-stat-card">
                    <div class="reel-stat-icon">
                        <span class="dashicons dashicons-visibility"></span>
                    </div>
                    <div class="reel-stat-content">
                        <div class="reel-stat-number"><?php echo number_format($stats['total_views']); ?></div>
                        <div class="reel-stat-label"><?php _e('Visualizações', 'reel-marketplace'); ?></div>
                    </div>
                </div>
                
                <div class="reel-stat-card">
                    <div class="reel-stat-icon">
                        <span class="dashicons dashicons-heart"></span>
                    </div>
                    <div class="reel-stat-content">
                        <div class="reel-stat-number"><?php echo number_format($stats['total_likes']); ?></div>
                        <div class="reel-stat-label"><?php _e('Curtidas', 'reel-marketplace'); ?></div>
                    </div>
                </div>
                
                <div class="reel-stat-card">
                    <div class="reel-stat-icon">
                        <span class="dashicons dashicons-share"></span>
                    </div>
                    <div class="reel-stat-content">
                        <div class="reel-stat-number"><?php echo number_format($stats['total_shares']); ?></div>
                        <div class="reel-stat-label"><?php _e('Compartilhamentos', 'reel-marketplace'); ?></div>
                    </div>
                </div>
                
                <div class="reel-stat-card">
                    <div class="reel-stat-icon">
                        <span class="dashicons dashicons-clock"></span>
                    </div>
                    <div class="reel-stat-content">
                        <div class="reel-stat-number"><?php echo number_format($stats['pending_moderation']); ?></div>
                        <div class="reel-stat-label"><?php _e('Pendentes', 'reel-marketplace'); ?></div>
                    </div>
                </div>
                
                <div class="reel-stat-card">
                    <div class="reel-stat-icon">
                        <span class="dashicons dashicons-cart"></span>
                    </div>
                    <div class="reel-stat-content">
                        <div class="reel-stat-number"><?php echo wc_price($stats['revenue_generated']); ?></div>
                        <div class="reel-stat-label"><?php _e('Receita Gerada', 'reel-marketplace'); ?></div>
                    </div>
                </div>
            </div>
            
            <div class="reel-admin-charts">
                <div class="reel-chart-container">
                    <h3><?php _e('Visualizações nos Últimos 30 Dias', 'reel-marketplace'); ?></h3>
                    <canvas id="reel-views-chart"></canvas>
                </div>
                
                <div class="reel-chart-container">
                    <h3><?php _e('Top 10 Reels por Engajamento', 'reel-marketplace'); ?></h3>
                    <canvas id="reel-engagement-chart"></canvas>
                </div>
            </div>
            
            <div class="reel-admin-tables">
                <div class="reel-table-container">
                    <h3><?php _e('Reels Recentes', 'reel-marketplace'); ?></h3>
                    <?php $this->render_recent_reels_table(); ?>
                </div>
                
                <div class="reel-table-container">
                    <h3><?php _e('Top Vendedores', 'reel-marketplace'); ?></h3>
                    <?php $this->render_top_vendors_table(); ?>
                </div>
            </div>
        </div>
        
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Load charts data
            reelAdminDashboard.loadCharts();
        });
        </script>
        <?php
    }
    
    /**
     * All reels page
     */
    public function all_reels_page() {
        $list_table = new Reel_Admin_List_Table();
        $list_table->prepare_items();
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php _e('Todos os Reels', 'reel-marketplace'); ?></h1>
            
            <form id="reel-filter" method="get">
                <input type="hidden" name="page" value="<?php echo $_REQUEST['page']; ?>" />
                <?php $list_table->search_box(__('Buscar reels', 'reel-marketplace'), 'reel'); ?>
                <?php $list_table->display(); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Analytics page
     */
    public function analytics_page() {
        ?>
        <div class="wrap reel-admin-analytics">
            <h1><?php _e('Analytics dos Reels', 'reel-marketplace'); ?></h1>
            
            <div class="reel-analytics-filters">
                <select id="analytics-period">
                    <option value="7"><?php _e('Últimos 7 dias', 'reel-marketplace'); ?></option>
                    <option value="30" selected><?php _e('Últimos 30 dias', 'reel-marketplace'); ?></option>
                    <option value="90"><?php _e('Últimos 90 dias', 'reel-marketplace'); ?></option>
                    <option value="365"><?php _e('Último ano', 'reel-marketplace'); ?></option>
                </select>
                
                <select id="analytics-metric">
                    <option value="views"><?php _e('Visualizações', 'reel-marketplace'); ?></option>
                    <option value="likes"><?php _e('Curtidas', 'reel-marketplace'); ?></option>
                    <option value="shares"><?php _e('Compartilhamentos', 'reel-marketplace'); ?></option>
                    <option value="revenue"><?php _e('Receita', 'reel-marketplace'); ?></option>
                </select>
                
                <button id="update-analytics" class="button button-primary"><?php _e('Atualizar', 'reel-marketplace'); ?></button>
            </div>
            
            <div class="reel-analytics-content">
                <div class="reel-analytics-chart">
                    <canvas id="main-analytics-chart"></canvas>
                </div>
                
                <div class="reel-analytics-sidebar">
                    <div class="reel-analytics-widget">
                        <h3><?php _e('Métricas Principais', 'reel-marketplace'); ?></h3>
                        <div id="main-metrics"></div>
                    </div>
                    
                    <div class="reel-analytics-widget">
                        <h3><?php _e('Dispositivos', 'reel-marketplace'); ?></h3>
                        <canvas id="device-chart"></canvas>
                    </div>
                    
                    <div class="reel-analytics-widget">
                        <h3><?php _e('Funil de Conversão', 'reel-marketplace'); ?></h3>
                        <div id="conversion-funnel"></div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
        if (isset($_POST['submit'])) {
            $this->save_settings();
            echo '<div class="notice notice-success"><p>' . __('Configurações salvas com sucesso!', 'reel-marketplace') . '</p></div>';
        }
        
        $settings = get_option('reel_marketplace_settings', array());
        ?>
        <div class="wrap">
            <h1><?php _e('Configurações do Reel Marketplace', 'reel-marketplace'); ?></h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('reel_settings_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Autoplay de Vídeos', 'reel-marketplace'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="autoplay_enabled" value="1" <?php checked(isset($settings['autoplay_enabled']) ? $settings['autoplay_enabled'] : 1); ?>>
                                <?php _e('Ativar autoplay nos reels', 'reel-marketplace'); ?>
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Reels por Página', 'reel-marketplace'); ?></th>
                        <td>
                            <input type="number" name="reels_per_page" value="<?php echo esc_attr(isset($settings['reels_per_page']) ? $settings['reels_per_page'] : 10); ?>" min="5" max="50">
                            <p class="description"><?php _e('Número de reels exibidos por página no feed', 'reel-marketplace'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Moderação de Conteúdo', 'reel-marketplace'); ?></th>
                        <td>
                            <select name="moderation_mode">
                                <option value="auto" <?php selected(isset($settings['moderation_mode']) ? $settings['moderation_mode'] : 'auto', 'auto'); ?>><?php _e('Aprovação automática', 'reel-marketplace'); ?></option>
                                <option value="manual" <?php selected(isset($settings['moderation_mode']) ? $settings['moderation_mode'] : 'auto', 'manual'); ?>><?php _e('Aprovação manual', 'reel-marketplace'); ?></option>
                            </select>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Tamanho Máximo do Vídeo', 'reel-marketplace'); ?></th>
                        <td>
                            <input type="number" name="max_video_size" value="<?php echo esc_attr(isset($settings['max_video_size']) ? $settings['max_video_size'] : 100); ?>" min="10" max="500">
                            <span>MB</span>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Duração Máxima do Vídeo', 'reel-marketplace'); ?></th>
                        <td>
                            <input type="number" name="max_video_duration" value="<?php echo esc_attr(isset($settings['max_video_duration']) ? $settings['max_video_duration'] : 60); ?>" min="15" max="300">
                            <span><?php _e('segundos', 'reel-marketplace'); ?></span>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Processamento de Vídeo', 'reel-marketplace'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="video_compression" value="1" <?php checked(isset($settings['video_compression']) ? $settings['video_compression'] : 1); ?>>
                                <?php _e('Ativar compressão automática de vídeos', 'reel-marketplace'); ?>
                            </label>
                            <br>
                            <label>
                                <input type="checkbox" name="generate_thumbnails" value="1" <?php checked(isset($settings['generate_thumbnails']) ? $settings['generate_thumbnails'] : 1); ?>>
                                <?php _e('Gerar thumbnails automaticamente', 'reel-marketplace'); ?>
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Analytics', 'reel-marketplace'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="analytics_enabled" value="1" <?php checked(isset($settings['analytics_enabled']) ? $settings['analytics_enabled'] : 1); ?>>
                                <?php _e('Ativar coleta de analytics', 'reel-marketplace'); ?>
                            </label>
                            <br>
                            <label>
                                <input type="checkbox" name="anonymous_analytics" value="1" <?php checked(isset($settings['anonymous_analytics']) ? $settings['anonymous_analytics'] : 0); ?>>
                                <?php _e('Analytics anônimos (sem rastreamento de usuários)', 'reel-marketplace'); ?>
                            </label>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Moderation page
     */
    public function moderation_page() {
        $pending_reels = get_posts(array(
            'post_type' => 'reel',
            'post_status' => 'pending',
            'numberposts' => -1
        ));
        ?>
        <div class="wrap">
            <h1><?php _e('Moderação de Reels', 'reel-marketplace'); ?></h1>
            
            <?php if (!empty($pending_reels)): ?>
                <div class="reel-moderation-list">
                    <?php foreach ($pending_reels as $reel): ?>
                        <?php $this->render_moderation_item($reel); ?>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="notice notice-info">
                    <p><?php _e('Não há reels pendentes de moderação.', 'reel-marketplace'); ?></p>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Get dashboard stats
     */
    private function get_dashboard_stats() {
        global $wpdb;
        
        $stats = array();
        
        // Total reels
        $stats['total_reels'] = wp_count_posts('reel')->publish;
        
        // Total views
        $stats['total_views'] = $wpdb->get_var("SELECT SUM(view_count) FROM {$wpdb->prefix}reel_views");
        
        // Total likes
        $stats['total_likes'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}reel_interactions WHERE interaction_type = 'like'");
        
        // Total shares
        $stats['total_shares'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}reel_interactions WHERE interaction_type = 'share'");
        
        // Pending moderation
        $stats['pending_moderation'] = wp_count_posts('reel')->pending;
        
        // Revenue generated (simplified calculation)
        $stats['revenue_generated'] = 0; // This would need more complex calculation
        
        return $stats;
    }
    
    /**
     * Render recent reels table
     */
    private function render_recent_reels_table() {
        $recent_reels = get_posts(array(
            'post_type' => 'reel',
            'numberposts' => 10,
            'post_status' => array('publish', 'pending')
        ));
        ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Reel', 'reel-marketplace'); ?></th>
                    <th><?php _e('Autor', 'reel-marketplace'); ?></th>
                    <th><?php _e('Status', 'reel-marketplace'); ?></th>
                    <th><?php _e('Visualizações', 'reel-marketplace'); ?></th>
                    <th><?php _e('Data', 'reel-marketplace'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent_reels as $reel): ?>
                    <tr>
                        <td><strong><?php echo esc_html($reel->post_title); ?></strong></td>
                        <td><?php echo get_the_author_meta('display_name', $reel->post_author); ?></td>
                        <td>
                            <span class="reel-status-<?php echo esc_attr($reel->post_status); ?>">
                                <?php echo ucfirst($reel->post_status); ?>
                            </span>
                        </td>
                        <td><?php echo $this->get_reel_views($reel->ID); ?></td>
                        <td><?php echo date_i18n(get_option('date_format'), strtotime($reel->post_date)); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }
    
    /**
     * Render top vendors table
     */
    private function render_top_vendors_table() {
        global $wpdb;
        
        $top_vendors = $wpdb->get_results("
            SELECT 
                p.post_author,
                COUNT(*) as reel_count,
                SUM(rv.view_count) as total_views
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->prefix}reel_views rv ON p.ID = rv.reel_id
            WHERE p.post_type = 'reel' AND p.post_status = 'publish'
            GROUP BY p.post_author
            ORDER BY total_views DESC
            LIMIT 10
        ");
        ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Vendedor', 'reel-marketplace'); ?></th>
                    <th><?php _e('Reels', 'reel-marketplace'); ?></th>
                    <th><?php _e('Visualizações', 'reel-marketplace'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($top_vendors as $vendor): ?>
                    <tr>
                        <td><?php echo get_the_author_meta('display_name', $vendor->post_author); ?></td>
                        <td><?php echo number_format($vendor->reel_count); ?></td>
                        <td><?php echo number_format($vendor->total_views); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }
    
    /**
     * Render moderation item
     */
    private function render_moderation_item($reel) {
        $video_url = get_post_meta($reel->ID, '_reel_video_url', true);
        $thumbnail = get_post_meta($reel->ID, '_reel_thumbnail', true);
        $author = get_userdata($reel->post_author);
        ?>
        <div class="reel-moderation-item" data-reel-id="<?php echo esc_attr($reel->ID); ?>">
            <div class="reel-mod-video">
                <?php if ($thumbnail): ?>
                    <img src="<?php echo esc_url($thumbnail); ?>" alt="">
                <?php endif; ?>
                <div class="reel-mod-controls">
                    <a href="<?php echo esc_url($video_url); ?>" target="_blank" class="button"><?php _e('Ver Vídeo', 'reel-marketplace'); ?></a>
                </div>
            </div>
            
            <div class="reel-mod-details">
                <h3><?php echo esc_html($reel->post_title); ?></h3>
                <p><strong><?php _e('Autor:', 'reel-marketplace'); ?></strong> <?php echo esc_html($author->display_name); ?></p>
                <p><strong><?php _e('Data:', 'reel-marketplace'); ?></strong> <?php echo date_i18n(get_option('date_format'), strtotime($reel->post_date)); ?></p>
                
                <?php if ($reel->post_content): ?>
                    <p><strong><?php _e('Descrição:', 'reel-marketplace'); ?></strong></p>
                    <div class="reel-mod-description"><?php echo wp_kses_post($reel->post_content); ?></div>
                <?php endif; ?>
            </div>
            
            <div class="reel-mod-actions">
                <button class="button button-primary reel-approve" data-reel-id="<?php echo esc_attr($reel->ID); ?>">
                    <?php _e('Aprovar', 'reel-marketplace'); ?>
                </button>
                <button class="button button-secondary reel-reject" data-reel-id="<?php echo esc_attr($reel->ID); ?>">
                    <?php _e('Rejeitar', 'reel-marketplace'); ?>
                </button>
                <button class="button reel-delete" data-reel-id="<?php echo esc_attr($reel->ID); ?>">
                    <?php _e('Deletar', 'reel-marketplace'); ?>
                </button>
            </div>
        </div>
        <?php
    }
    
    /**
     * Get reel views
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
     * Save settings
     */
    private function save_settings() {
        check_admin_referer('reel_settings_nonce');
        
        $settings = array(
            'autoplay_enabled' => isset($_POST['autoplay_enabled']) ? 1 : 0,
            'reels_per_page' => intval($_POST['reels_per_page']),
            'moderation_mode' => sanitize_text_field($_POST['moderation_mode']),
            'max_video_size' => intval($_POST['max_video_size']),
            'max_video_duration' => intval($_POST['max_video_duration']),
            'video_compression' => isset($_POST['video_compression']) ? 1 : 0,
            'generate_thumbnails' => isset($_POST['generate_thumbnails']) ? 1 : 0,
            'analytics_enabled' => isset($_POST['analytics_enabled']) ? 1 : 0,
            'anonymous_analytics' => isset($_POST['anonymous_analytics']) ? 1 : 0
        );
        
        update_option('reel_marketplace_settings', $settings);
    }
    
    /**
     * Get admin analytics
     */
    public function get_admin_analytics() {
        check_ajax_referer('reel_admin_nonce', 'nonce');
        
        $period = intval($_POST['period']) ?: 30;
        $metric = sanitize_text_field($_POST['metric']) ?: 'views';
        
        $analytics = $this->calculate_analytics($period, $metric);
        
        wp_send_json_success($analytics);
    }
    
    /**
     * Calculate analytics
     */
    private function calculate_analytics($period, $metric) {
        global $wpdb;
        
        $date_from = date('Y-m-d', strtotime("-{$period} days"));
        
        // Implementation would depend on specific metric
        // This is a simplified version
        $data = array(
            'labels' => array(),
            'values' => array(),
            'total' => 0
        );
        
        return $data;
    }
    
    /**
     * Moderate content
     */
    public function moderate_content() {
        check_ajax_referer('reel_admin_nonce', 'nonce');
        
        $reel_id = intval($_POST['reel_id']);
        $action = sanitize_text_field($_POST['mod_action']);
        
        switch ($action) {
            case 'approve':
                wp_update_post(array(
                    'ID' => $reel_id,
                    'post_status' => 'publish'
                ));
                wp_send_json_success(__('Reel aprovado', 'reel-marketplace'));
                break;
                
            case 'reject':
                wp_update_post(array(
                    'ID' => $reel_id,
                    'post_status' => 'draft'
                ));
                wp_send_json_success(__('Reel rejeitado', 'reel-marketplace'));
                break;
                
            case 'delete':
                wp_delete_post($reel_id, true);
                wp_send_json_success(__('Reel deletado', 'reel-marketplace'));
                break;
        }
        
        wp_send_json_error(__('Ação inválida', 'reel-marketplace'));
    }
    
    /**
     * Handle bulk actions
     */
    public function handle_bulk_actions() {
        check_ajax_referer('reel_admin_nonce', 'nonce');
        
        $action = sanitize_text_field($_POST['bulk_action']);
        $reel_ids = array_map('intval', $_POST['reel_ids']);
        
        $processed = 0;
        
        foreach ($reel_ids as $reel_id) {
            switch ($action) {
                case 'approve':
                    wp_update_post(array(
                        'ID' => $reel_id,
                        'post_status' => 'publish'
                    ));
                    $processed++;
                    break;
                    
                case 'reject':
                    wp_update_post(array(
                        'ID' => $reel_id,
                        'post_status' => 'draft'
                    ));
                    $processed++;
                    break;
                    
                case 'delete':
                    wp_delete_post($reel_id, true);
                    $processed++;
                    break;
            }
        }
        
        wp_send_json_success(sprintf(__('%d reels processados', 'reel-marketplace'), $processed));
    }
}

// Initialize
new Reel_Admin_Dashboard();
