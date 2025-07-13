<?php
/**
 * Reel Admin Class
 * 
 * Handles admin functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

class Reel_Admin {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('admin_init', array($this, 'admin_init'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Reel Marketplace', 'reel-marketplace'),
            __('Reel Marketplace', 'reel-marketplace'),
            'manage_options',
            'reel-marketplace',
            array($this, 'admin_page'),
            'dashicons-video-alt3',
            58
        );
        
        add_submenu_page(
            'reel-marketplace',
            __('Configurações', 'reel-marketplace'),
            __('Configurações', 'reel-marketplace'),
            'manage_options',
            'reel-marketplace-settings',
            array($this, 'settings_page')
        );
        
        add_submenu_page(
            'reel-marketplace',
            __('Analytics', 'reel-marketplace'),
            __('Analytics', 'reel-marketplace'),
            'manage_options',
            'reel-marketplace-analytics',
            array($this, 'analytics_page')
        );
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'reel-marketplace') !== false || get_post_type() === 'reel') {
            wp_enqueue_media();
            
            wp_enqueue_script(
                'reel-marketplace-admin',
                REEL_MARKETPLACE_PLUGIN_URL . 'assets/js/admin.js',
                array('jquery', 'wp-util'),
                REEL_MARKETPLACE_VERSION,
                true
            );
            
            wp_enqueue_style(
                'reel-marketplace-admin',
                REEL_MARKETPLACE_PLUGIN_URL . 'assets/css/admin.css',
                array(),
                REEL_MARKETPLACE_VERSION
            );
            
            wp_localize_script('reel-marketplace-admin', 'reelAdmin', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('reel_ajax'),
                'strings' => array(
                    'selectVideo' => __('Selecionar Vídeo', 'reel-marketplace'),
                    'selectProducts' => __('Selecionar Produtos', 'reel-marketplace'),
                    'loading' => __('Carregando...', 'reel-marketplace'),
                    'error' => __('Erro ao processar solicitação', 'reel-marketplace')
                )
            ));
        }
    }
    
    /**
     * Admin init
     */
    public function admin_init() {
        register_setting('reel_marketplace_settings', 'reel_marketplace_options');
        
        // Add settings sections
        add_settings_section(
            'reel_general_settings',
            __('Configurações Gerais', 'reel-marketplace'),
            array($this, 'general_settings_callback'),
            'reel_marketplace_settings'
        );
        
        add_settings_section(
            'reel_video_settings',
            __('Configurações de Vídeo', 'reel-marketplace'),
            array($this, 'video_settings_callback'),
            'reel_marketplace_settings'
        );
        
        add_settings_section(
            'reel_integration_settings',
            __('Configurações de Integração', 'reel-marketplace'),
            array($this, 'integration_settings_callback'),
            'reel_marketplace_settings'
        );
        
        // Add settings fields
        $this->add_settings_fields();
    }
    
    /**
     * Add settings fields
     */
    private function add_settings_fields() {
        $options = get_option('reel_marketplace_options', array());
        
        // General settings
        add_settings_field(
            'reel_autoplay',
            __('Reprodução Automática', 'reel-marketplace'),
            array($this, 'checkbox_field_callback'),
            'reel_marketplace_settings',
            'reel_general_settings',
            array(
                'name' => 'reel_autoplay',
                'value' => isset($options['reel_autoplay']) ? $options['reel_autoplay'] : 'yes',
                'description' => __('Reproduzir vídeos automaticamente quando visíveis', 'reel-marketplace')
            )
        );
        
        add_settings_field(
            'reel_loop',
            __('Loop de Vídeo', 'reel-marketplace'),
            array($this, 'checkbox_field_callback'),
            'reel_marketplace_settings',
            'reel_general_settings',
            array(
                'name' => 'reel_loop',
                'value' => isset($options['reel_loop']) ? $options['reel_loop'] : 'yes',
                'description' => __('Reproduzir vídeos em loop', 'reel-marketplace')
            )
        );
        
        add_settings_field(
            'reel_muted',
            __('Áudio Silenciado', 'reel-marketplace'),
            array($this, 'checkbox_field_callback'),
            'reel_marketplace_settings',
            'reel_general_settings',
            array(
                'name' => 'reel_muted',
                'value' => isset($options['reel_muted']) ? $options['reel_muted'] : 'yes',
                'description' => __('Iniciar vídeos sem som', 'reel-marketplace')
            )
        );
        
        // Video settings
        add_settings_field(
            'reel_preload_count',
            __('Quantidade de Pré-carregamento', 'reel-marketplace'),
            array($this, 'number_field_callback'),
            'reel_marketplace_settings',
            'reel_video_settings',
            array(
                'name' => 'reel_preload_count',
                'value' => isset($options['reel_preload_count']) ? $options['reel_preload_count'] : 3,
                'min' => 1,
                'max' => 10,
                'description' => __('Número de vídeos para pré-carregar', 'reel-marketplace')
            )
        );
        
        add_settings_field(
            'reel_video_quality',
            __('Qualidade de Vídeo', 'reel-marketplace'),
            array($this, 'select_field_callback'),
            'reel_marketplace_settings',
            'reel_video_settings',
            array(
                'name' => 'reel_video_quality',
                'value' => isset($options['reel_video_quality']) ? $options['reel_video_quality'] : 'auto',
                'options' => array(
                    'auto' => __('Automática', 'reel-marketplace'),
                    'high' => __('Alta', 'reel-marketplace'),
                    'medium' => __('Média', 'reel-marketplace'),
                    'low' => __('Baixa', 'reel-marketplace')
                ),
                'description' => __('Qualidade padrão dos vídeos', 'reel-marketplace')
            )
        );
        
        // Integration settings
        add_settings_field(
            'reel_enable_analytics',
            __('Habilitar Analytics', 'reel-marketplace'),
            array($this, 'checkbox_field_callback'),
            'reel_marketplace_settings',
            'reel_integration_settings',
            array(
                'name' => 'reel_enable_analytics',
                'value' => isset($options['reel_enable_analytics']) ? $options['reel_enable_analytics'] : 'yes',
                'description' => __('Rastrear visualizações e interações', 'reel-marketplace')
            )
        );
        
        add_settings_field(
            'reel_enable_wishlist',
            __('Habilitar Lista de Desejos', 'reel-marketplace'),
            array($this, 'checkbox_field_callback'),
            'reel_marketplace_settings',
            'reel_integration_settings',
            array(
                'name' => 'reel_enable_wishlist',
                'value' => isset($options['reel_enable_wishlist']) ? $options['reel_enable_wishlist'] : 'yes',
                'description' => __('Permitir adicionar produtos aos favoritos', 'reel-marketplace')
            )
        );
        
        add_settings_field(
            'reel_enable_sharing',
            __('Habilitar Compartilhamento', 'reel-marketplace'),
            array($this, 'checkbox_field_callback'),
            'reel_marketplace_settings',
            'reel_integration_settings',
            array(
                'name' => 'reel_enable_sharing',
                'value' => isset($options['reel_enable_sharing']) ? $options['reel_enable_sharing'] : 'yes',
                'description' => __('Permitir compartilhar reels', 'reel-marketplace')
            )
        );
    }
    
    /**
     * Settings field callbacks
     */
    public function checkbox_field_callback($args) {
        $checked = ($args['value'] === 'yes') ? 'checked' : '';
        echo '<input type="checkbox" name="reel_marketplace_options[' . $args['name'] . ']" value="yes" ' . $checked . ' />';
        if (isset($args['description'])) {
            echo '<p class="description">' . $args['description'] . '</p>';
        }
    }
    
    public function number_field_callback($args) {
        $min = isset($args['min']) ? 'min="' . $args['min'] . '"' : '';
        $max = isset($args['max']) ? 'max="' . $args['max'] . '"' : '';
        echo '<input type="number" name="reel_marketplace_options[' . $args['name'] . ']" value="' . esc_attr($args['value']) . '" ' . $min . ' ' . $max . ' />';
        if (isset($args['description'])) {
            echo '<p class="description">' . $args['description'] . '</p>';
        }
    }
    
    public function select_field_callback($args) {
        echo '<select name="reel_marketplace_options[' . $args['name'] . ']">';
        foreach ($args['options'] as $value => $label) {
            $selected = selected($args['value'], $value, false);
            echo '<option value="' . esc_attr($value) . '" ' . $selected . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';
        if (isset($args['description'])) {
            echo '<p class="description">' . $args['description'] . '</p>';
        }
    }
    
    /**
     * Settings section callbacks
     */
    public function general_settings_callback() {
        echo '<p>' . __('Configurações gerais para o funcionamento dos reels.', 'reel-marketplace') . '</p>';
    }
    
    public function video_settings_callback() {
        echo '<p>' . __('Configurações relacionadas à reprodução e qualidade dos vídeos.', 'reel-marketplace') . '</p>';
    }
    
    public function integration_settings_callback() {
        echo '<p>' . __('Configurações de integração com outros plugins e funcionalidades.', 'reel-marketplace') . '</p>';
    }
    
    /**
     * Main admin page
     */
    public function admin_page() {
        global $wpdb;
        
        // Get statistics
        $total_reels = wp_count_posts('reel')->publish;
        $total_views = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}reel_views");
        $total_likes = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}reel_interactions WHERE interaction_type = 'like'");
        $total_shares = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}reel_interactions WHERE interaction_type = 'share'");
        
        ?>
        <div class="wrap">
            <h1><?php _e('Reel Marketplace', 'reel-marketplace'); ?></h1>
            
            <div class="reel-admin-dashboard">
                <div class="reel-stats-grid">
                    <div class="reel-stat-card">
                        <div class="reel-stat-icon">📹</div>
                        <div class="reel-stat-content">
                            <h3><?php echo number_format($total_reels); ?></h3>
                            <p><?php _e('Total de Reels', 'reel-marketplace'); ?></p>
                        </div>
                    </div>
                    
                    <div class="reel-stat-card">
                        <div class="reel-stat-icon">👁️</div>
                        <div class="reel-stat-content">
                            <h3><?php echo number_format($total_views); ?></h3>
                            <p><?php _e('Visualizações', 'reel-marketplace'); ?></p>
                        </div>
                    </div>
                    
                    <div class="reel-stat-card">
                        <div class="reel-stat-icon">❤️</div>
                        <div class="reel-stat-content">
                            <h3><?php echo number_format($total_likes); ?></h3>
                            <p><?php _e('Curtidas', 'reel-marketplace'); ?></p>
                        </div>
                    </div>
                    
                    <div class="reel-stat-card">
                        <div class="reel-stat-icon">📤</div>
                        <div class="reel-stat-content">
                            <h3><?php echo number_format($total_shares); ?></h3>
                            <p><?php _e('Compartilhamentos', 'reel-marketplace'); ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="reel-quick-actions">
                    <h2><?php _e('Ações Rápidas', 'reel-marketplace'); ?></h2>
                    <div class="reel-actions-grid">
                        <a href="<?php echo admin_url('post-new.php?post_type=reel'); ?>" class="reel-action-btn reel-btn-primary">
                            <span class="dashicons dashicons-plus-alt"></span>
                            <?php _e('Criar Novo Reel', 'reel-marketplace'); ?>
                        </a>
                        
                        <a href="<?php echo admin_url('edit.php?post_type=reel'); ?>" class="reel-action-btn reel-btn-secondary">
                            <span class="dashicons dashicons-video-alt3"></span>
                            <?php _e('Gerenciar Reels', 'reel-marketplace'); ?>
                        </a>
                        
                        <a href="<?php echo admin_url('admin.php?page=reel-marketplace-settings'); ?>" class="reel-action-btn reel-btn-secondary">
                            <span class="dashicons dashicons-admin-settings"></span>
                            <?php _e('Configurações', 'reel-marketplace'); ?>
                        </a>
                        
                        <a href="<?php echo admin_url('admin.php?page=reel-marketplace-analytics'); ?>" class="reel-action-btn reel-btn-secondary">
                            <span class="dashicons dashicons-chart-bar"></span>
                            <?php _e('Analytics', 'reel-marketplace'); ?>
                        </a>
                    </div>
                </div>
                
                <div class="reel-recent-activity">
                    <h2><?php _e('Atividade Recente', 'reel-marketplace'); ?></h2>
                    <?php $this->display_recent_activity(); ?>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Configurações do Reel Marketplace', 'reel-marketplace'); ?></h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('reel_marketplace_settings');
                do_settings_sections('reel_marketplace_settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Analytics page
     */
    public function analytics_page() {
        global $wpdb;
        
        // Get analytics data
        $views_by_day = $wpdb->get_results("
            SELECT DATE(viewed_at) as date, COUNT(*) as views 
            FROM {$wpdb->prefix}reel_views 
            WHERE viewed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY DATE(viewed_at)
            ORDER BY date ASC
        ");
        
        $top_reels = $wpdb->get_results("
            SELECT r.reel_id, p.post_title, COUNT(*) as views
            FROM {$wpdb->prefix}reel_views r
            JOIN {$wpdb->posts} p ON r.reel_id = p.ID
            WHERE r.viewed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY r.reel_id
            ORDER BY views DESC
            LIMIT 10
        ");
        
        ?>
        <div class="wrap">
            <h1><?php _e('Analytics do Reel Marketplace', 'reel-marketplace'); ?></h1>
            
            <div class="reel-analytics-dashboard">
                <div class="reel-chart-container">
                    <h2><?php _e('Visualizações nos Últimos 30 Dias', 'reel-marketplace'); ?></h2>
                    <canvas id="reel-views-chart" width="400" height="200"></canvas>
                </div>
                
                <div class="reel-top-content">
                    <h2><?php _e('Top 10 Reels (30 dias)', 'reel-marketplace'); ?></h2>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Reel', 'reel-marketplace'); ?></th>
                                <th><?php _e('Visualizações', 'reel-marketplace'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($top_reels as $reel): ?>
                            <tr>
                                <td>
                                    <a href="<?php echo admin_url('post.php?post=' . $reel->reel_id . '&action=edit'); ?>">
                                        <?php echo esc_html($reel->post_title); ?>
                                    </a>
                                </td>
                                <td><?php echo number_format($reel->views); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <script>
        // Simple chart implementation (you can replace with Chart.js if needed)
        document.addEventListener('DOMContentLoaded', function() {
            const canvas = document.getElementById('reel-views-chart');
            const ctx = canvas.getContext('2d');
            
            const data = <?php echo json_encode($views_by_day); ?>;
            
            // Basic line chart implementation
            if (data.length > 0) {
                // Chart implementation would go here
                ctx.fillStyle = '#6750A4';
                ctx.fillRect(10, 10, 100, 50);
                ctx.fillStyle = '#fff';
                ctx.font = '14px Arial';
                ctx.fillText('Analytics Chart', 20, 35);
                ctx.fillText('(Implementation needed)', 20, 55);
            } else {
                ctx.fillStyle = '#ccc';
                ctx.font = '16px Arial';
                ctx.fillText('Nenhum dado disponível', 150, 100);
            }
        });
        </script>
        <?php
    }
    
    /**
     * Display recent activity
     */
    private function display_recent_activity() {
        global $wpdb;
        
        $recent_reels = get_posts(array(
            'post_type' => 'reel',
            'posts_per_page' => 5,
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC'
        ));
        
        if ($recent_reels) {
            echo '<ul class="reel-activity-list">';
            foreach ($recent_reels as $reel) {
                $views = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}reel_views WHERE reel_id = %d",
                    $reel->ID
                ));
                
                echo '<li>';
                echo '<a href="' . admin_url('post.php?post=' . $reel->ID . '&action=edit') . '">';
                echo esc_html($reel->post_title);
                echo '</a>';
                echo ' - ' . $views . ' ' . __('visualizações', 'reel-marketplace');
                echo ' - ' . human_time_diff(strtotime($reel->post_date), current_time('timestamp')) . ' ' . __('atrás', 'reel-marketplace');
                echo '</li>';
            }
            echo '</ul>';
        } else {
            echo '<p>' . __('Nenhuma atividade recente.', 'reel-marketplace') . '</p>';
        }
    }
}
