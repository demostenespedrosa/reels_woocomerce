<?php
/**
 * Reel Marketplace Installation Helper
 * 
 * Quick setup script for new installations
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Reel_Marketplace_Installer {
    
    public static function run_installation_check() {
        $requirements = array();
        
        // Check WordPress version
        $wp_version = get_bloginfo('version');
        $requirements['wordpress'] = array(
            'required' => '5.0',
            'current' => $wp_version,
            'status' => version_compare($wp_version, '5.0', '>=')
        );
        
        // Check PHP version
        $php_version = PHP_VERSION;
        $requirements['php'] = array(
            'required' => '7.4',
            'current' => $php_version,
            'status' => version_compare($php_version, '7.4', '>=')
        );
        
        // Check WooCommerce
        $wc_active = class_exists('WooCommerce');
        $wc_version = $wc_active ? WC()->version : '0.0';
        $requirements['woocommerce'] = array(
            'required' => '4.0',
            'current' => $wc_version,
            'status' => $wc_active && version_compare($wc_version, '4.0', '>=')
        );
        
        // Check Dokan (optional)
        $dokan_active = class_exists('WeDevs_Dokan');
        $requirements['dokan'] = array(
            'required' => '3.0 (optional)',
            'current' => $dokan_active ? 'Active' : 'Not installed',
            'status' => true, // Optional, so always true
            'optional' => true
        );
        
        // Check WCFM (optional)
        $wcfm_active = class_exists('WCFM');
        $requirements['wcfm'] = array(
            'required' => '6.0 (optional)',
            'current' => $wcfm_active ? 'Active' : 'Not installed',
            'status' => true, // Optional, so always true
            'optional' => true
        );
        
        // Check FFmpeg (optional)
        $ffmpeg_path = self::check_ffmpeg();
        $requirements['ffmpeg'] = array(
            'required' => 'For video processing (optional)',
            'current' => $ffmpeg_path ? 'Available' : 'Not found',
            'status' => true, // Optional, so always true
            'optional' => true
        );
        
        // Check upload directory
        $upload_dir = wp_upload_dir();
        $requirements['uploads'] = array(
            'required' => 'Writable uploads directory',
            'current' => is_writable($upload_dir['basedir']) ? 'Writable' : 'Not writable',
            'status' => is_writable($upload_dir['basedir'])
        );
        
        return $requirements;
    }
    
    public static function check_ffmpeg() {
        // Check if FFmpeg is available
        $paths = array(
            '/usr/bin/ffmpeg',
            '/usr/local/bin/ffmpeg',
            '/opt/local/bin/ffmpeg',
            'ffmpeg' // System PATH
        );
        
        foreach ($paths as $path) {
            if (is_executable($path) || (exec("which $path 2>/dev/null") && is_executable(exec("which $path 2>/dev/null")))) {
                return $path;
            }
        }
        
        return false;
    }
    
    public static function create_sample_page() {
        // Check if sample page already exists
        $existing_page = get_page_by_path('explorar-reels');
        if ($existing_page) {
            return $existing_page->ID;
        }
        
        // Create sample page
        $page_data = array(
            'post_title' => 'Explorar Reels',
            'post_content' => '[reel_feed]',
            'post_status' => 'publish',
            'post_type' => 'page',
            'post_slug' => 'explorar-reels'
        );
        
        $page_id = wp_insert_post($page_data);
        
        if ($page_id && !is_wp_error($page_id)) {
            // Set as front page option
            update_option('reel_marketplace_feed_page', $page_id);
            return $page_id;
        }
        
        return false;
    }
    
    public static function setup_default_settings() {
        $default_settings = array(
            'autoplay_enabled' => 1,
            'reels_per_page' => 10,
            'moderation_mode' => 'auto',
            'max_video_size' => 100,
            'max_video_duration' => 60,
            'video_compression' => 1,
            'generate_thumbnails' => 1,
            'analytics_enabled' => 1,
            'anonymous_analytics' => 0
        );
        
        $existing_settings = get_option('reel_marketplace_settings', array());
        $settings = array_merge($default_settings, $existing_settings);
        
        update_option('reel_marketplace_settings', $settings);
        
        return $settings;
    }
    
    public static function create_reels_directory() {
        $upload_dir = wp_upload_dir();
        $reels_dir = $upload_dir['basedir'] . '/reels/';
        
        if (!file_exists($reels_dir)) {
            wp_mkdir_p($reels_dir);
            
            // Create .htaccess for security
            $htaccess_content = "Options -Indexes\n";
            $htaccess_content .= "<Files *.php>\n";
            $htaccess_content .= "Order Allow,Deny\n";
            $htaccess_content .= "Deny from all\n";
            $htaccess_content .= "</Files>\n";
            
            file_put_contents($reels_dir . '.htaccess', $htaccess_content);
            
            // Create index.php
            file_put_contents($reels_dir . 'index.php', '<?php // Silence is golden');
        }
        
        return is_dir($reels_dir) && is_writable($reels_dir);
    }
    
    public static function get_installation_report() {
        $requirements = self::run_installation_check();
        $all_required_met = true;
        
        foreach ($requirements as $key => $requirement) {
            if (!$requirement['optional'] && !$requirement['status']) {
                $all_required_met = false;
                break;
            }
        }
        
        return array(
            'requirements' => $requirements,
            'all_required_met' => $all_required_met,
            'ready_for_use' => $all_required_met
        );
    }
    
    public static function run_quick_setup() {
        $results = array();
        
        // 1. Check requirements
        $results['requirements_check'] = self::get_installation_report();
        
        // 2. Setup default settings
        $results['settings_setup'] = self::setup_default_settings();
        
        // 3. Create upload directory
        $results['directory_setup'] = self::create_reels_directory();
        
        // 4. Create sample page
        $results['sample_page'] = self::create_sample_page();
        
        // 5. Set setup complete flag
        update_option('reel_marketplace_setup_complete', true);
        update_option('reel_marketplace_setup_date', current_time('mysql'));
        
        return $results;
    }
}

// Auto-run setup check on admin pages
if (is_admin() && !get_option('reel_marketplace_setup_complete')) {
    add_action('admin_notices', function() {
        $report = Reel_Marketplace_Installer::get_installation_report();
        
        if (!$report['ready_for_use']) {
            echo '<div class="notice notice-warning is-dismissible">';
            echo '<h3>🎬 Reel Marketplace - Configuração Necessária</h3>';
            echo '<p>Alguns requisitos precisam ser atendidos antes de usar o plugin:</p>';
            echo '<ul>';
            
            foreach ($report['requirements'] as $key => $req) {
                if (!$req['optional'] && !$req['status']) {
                    echo '<li>❌ <strong>' . ucfirst($key) . '</strong>: Requer ' . $req['required'] . ' (atual: ' . $req['current'] . ')</li>';
                }
            }
            
            echo '</ul>';
            echo '<p><a href="' . admin_url('admin.php?page=reel-marketplace') . '" class="button button-primary">Verificar Configuração</a></p>';
            echo '</div>';
        } else {
            echo '<div class="notice notice-info is-dismissible">';
            echo '<h3>🎬 Reel Marketplace - Pronto para Usar!</h3>';
            echo '<p>Plugin configurado com sucesso! <a href="' . admin_url('admin.php?page=reel-marketplace') . '">Acessar Dashboard</a> ou <a href="#" onclick="reelQuickSetup()">Executar Configuração Rápida</a></p>';
            echo '</div>';
            
            // Quick setup JavaScript
            ?>
            <script>
            function reelQuickSetup() {
                if (confirm('Deseja executar a configuração rápida? Isso criará uma página de exemplo e definirá as configurações padrão.')) {
                    jQuery.post(ajaxurl, {
                        action: 'reel_quick_setup',
                        nonce: '<?php echo wp_create_nonce('reel_setup_nonce'); ?>'
                    }, function(response) {
                        if (response.success) {
                            alert('Configuração concluída! Página de exemplo criada: ' + response.data.page_url);
                            location.reload();
                        } else {
                            alert('Erro na configuração: ' + response.data.message);
                        }
                    });
                }
            }
            </script>
            <?php
        }
    });
}

// AJAX handler for quick setup
add_action('wp_ajax_reel_quick_setup', function() {
    check_ajax_referer('reel_setup_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Permissão negada');
    }
    
    $results = Reel_Marketplace_Installer::run_quick_setup();
    
    if ($results['sample_page']) {
        $page_url = get_permalink($results['sample_page']);
        wp_send_json_success(array(
            'message' => 'Configuração concluída com sucesso!',
            'page_url' => $page_url,
            'results' => $results
        ));
    } else {
        wp_send_json_error('Erro ao criar página de exemplo');
    }
});
