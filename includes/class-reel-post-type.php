<?php
/**
 * Reel Post Type Class
 * 
 * Handles the custom post type for reels
 */

if (!defined('ABSPATH')) {
    exit;
}

class Reel_Post_Type {
    
    public function __construct() {
        add_action('init', array($this, 'register_post_type'));
        add_action('init', array($this, 'register_taxonomies'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_boxes'));
        add_filter('manage_reel_posts_columns', array($this, 'add_custom_columns'));
        add_action('manage_reel_posts_custom_column', array($this, 'custom_column_content'), 10, 2);
    }
    
    /**
     * Register the reel post type
     */
    public function register_post_type() {
        $labels = array(
            'name'                  => __('Reels', 'reel-marketplace'),
            'singular_name'         => __('Reel', 'reel-marketplace'),
            'menu_name'            => __('Reels', 'reel-marketplace'),
            'name_admin_bar'       => __('Reel', 'reel-marketplace'),
            'add_new'              => __('Adicionar Novo', 'reel-marketplace'),
            'add_new_item'         => __('Adicionar Novo Reel', 'reel-marketplace'),
            'new_item'             => __('Novo Reel', 'reel-marketplace'),
            'edit_item'            => __('Editar Reel', 'reel-marketplace'),
            'view_item'            => __('Ver Reel', 'reel-marketplace'),
            'all_items'            => __('Todos os Reels', 'reel-marketplace'),
            'search_items'         => __('Buscar Reels', 'reel-marketplace'),
            'parent_item_colon'    => __('Reel Pai:', 'reel-marketplace'),
            'not_found'            => __('Nenhum reel encontrado.', 'reel-marketplace'),
            'not_found_in_trash'   => __('Nenhum reel encontrado na lixeira.', 'reel-marketplace'),
            'featured_image'       => __('Imagem de Capa do Reel', 'reel-marketplace'),
            'set_featured_image'   => __('Definir imagem de capa', 'reel-marketplace'),
            'remove_featured_image' => __('Remover imagem de capa', 'reel-marketplace'),
            'use_featured_image'   => __('Usar como imagem de capa', 'reel-marketplace'),
            'archives'             => __('Arquivo de Reels', 'reel-marketplace'),
            'insert_into_item'     => __('Inserir no reel', 'reel-marketplace'),
            'uploaded_to_this_item' => __('Enviado para este reel', 'reel-marketplace'),
            'filter_items_list'    => __('Filtrar lista de reels', 'reel-marketplace'),
            'items_list_navigation' => __('Navegação da lista de reels', 'reel-marketplace'),
            'items_list'           => __('Lista de reels', 'reel-marketplace'),
        );
        
        $args = array(
            'labels'                => $labels,
            'public'                => true,
            'publicly_queryable'    => true,
            'show_ui'               => true,
            'show_in_menu'          => true,
            'show_in_nav_menus'     => false,
            'show_in_admin_bar'     => true,
            'query_var'             => true,
            'rewrite'               => array('slug' => 'reel'),
            'capability_type'       => 'post',
            'has_archive'           => false,
            'hierarchical'          => false,
            'menu_position'         => 58,
            'menu_icon'             => 'dashicons-video-alt3',
            'supports'              => array('title', 'editor', 'author', 'thumbnail', 'excerpt'),
            'show_in_rest'          => true,
            'rest_base'             => 'reels',
            'rest_controller_class' => 'WP_REST_Posts_Controller',
        );
        
        register_post_type('reel', $args);
    }
    
    /**
     * Register taxonomies for reels
     */
    public function register_taxonomies() {
        // Reel Categories
        $labels = array(
            'name'              => __('Categorias de Reel', 'reel-marketplace'),
            'singular_name'     => __('Categoria de Reel', 'reel-marketplace'),
            'search_items'      => __('Buscar Categorias', 'reel-marketplace'),
            'all_items'         => __('Todas as Categorias', 'reel-marketplace'),
            'edit_item'         => __('Editar Categoria', 'reel-marketplace'),
            'update_item'       => __('Atualizar Categoria', 'reel-marketplace'),
            'add_new_item'      => __('Adicionar Nova Categoria', 'reel-marketplace'),
            'new_item_name'     => __('Nome da Nova Categoria', 'reel-marketplace'),
            'menu_name'         => __('Categorias', 'reel-marketplace'),
        );
        
        $args = array(
            'hierarchical'      => true,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'reel-category'),
            'show_in_rest'      => true,
        );
        
        register_taxonomy('reel_category', array('reel'), $args);
        
        // Reel Tags/Hashtags
        $tag_labels = array(
            'name'                       => __('Tags de Reel', 'reel-marketplace'),
            'singular_name'              => __('Tag de Reel', 'reel-marketplace'),
            'search_items'               => __('Buscar Tags', 'reel-marketplace'),
            'popular_items'              => __('Tags Populares', 'reel-marketplace'),
            'all_items'                  => __('Todas as Tags', 'reel-marketplace'),
            'edit_item'                  => __('Editar Tag', 'reel-marketplace'),
            'update_item'                => __('Atualizar Tag', 'reel-marketplace'),
            'add_new_item'               => __('Adicionar Nova Tag', 'reel-marketplace'),
            'new_item_name'              => __('Nome da Nova Tag', 'reel-marketplace'),
            'separate_items_with_commas' => __('Separe as tags com vírgulas', 'reel-marketplace'),
            'add_or_remove_items'        => __('Adicionar ou remover tags', 'reel-marketplace'),
            'choose_from_most_used'      => __('Escolher das tags mais usadas', 'reel-marketplace'),
            'not_found'                  => __('Nenhuma tag encontrada.', 'reel-marketplace'),
            'menu_name'                  => __('Tags', 'reel-marketplace'),
        );
        
        $tag_args = array(
            'hierarchical'          => false,
            'labels'                => $tag_labels,
            'show_ui'               => true,
            'show_admin_column'     => true,
            'update_count_callback' => '_update_post_term_count',
            'query_var'             => true,
            'rewrite'               => array('slug' => 'reel-tag'),
            'show_in_rest'          => true,
        );
        
        register_taxonomy('reel_tag', array('reel'), $tag_args);
    }
    
    /**
     * Add meta boxes for reel post type
     */
    public function add_meta_boxes() {
        add_meta_box(
            'reel_video_details',
            __('Detalhes do Vídeo', 'reel-marketplace'),
            array($this, 'video_details_meta_box'),
            'reel',
            'normal',
            'high'
        );
        
        add_meta_box(
            'reel_products',
            __('Produtos Vinculados', 'reel-marketplace'),
            array($this, 'products_meta_box'),
            'reel',
            'side',
            'default'
        );
        
        add_meta_box(
            'reel_settings',
            __('Configurações do Reel', 'reel-marketplace'),
            array($this, 'settings_meta_box'),
            'reel',
            'side',
            'default'
        );
        
        add_meta_box(
            'reel_analytics',
            __('Analytics', 'reel-marketplace'),
            array($this, 'analytics_meta_box'),
            'reel',
            'side',
            'low'
        );
    }
    
    /**
     * Video details meta box
     */
    public function video_details_meta_box($post) {
        wp_nonce_field('reel_meta_box', 'reel_meta_box_nonce');
        
        $video_url = get_post_meta($post->ID, '_reel_video_url', true);
        $video_file = get_post_meta($post->ID, '_reel_video_file', true);
        $video_duration = get_post_meta($post->ID, '_reel_video_duration', true);
        $video_poster = get_post_meta($post->ID, '_reel_video_poster', true);
        
        ?>
        <table class="form-table">
            <tr>
                <th><label for="reel_video_url"><?php _e('URL do Vídeo', 'reel-marketplace'); ?></label></th>
                <td>
                    <input type="url" id="reel_video_url" name="reel_video_url" value="<?php echo esc_attr($video_url); ?>" class="large-text" placeholder="https://..." />
                    <p class="description"><?php _e('URL do vídeo (YouTube, Vimeo, etc.) ou deixe em branco para fazer upload', 'reel-marketplace'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="reel_video_file"><?php _e('Arquivo de Vídeo', 'reel-marketplace'); ?></label></th>
                <td>
                    <input type="hidden" id="reel_video_file" name="reel_video_file" value="<?php echo esc_attr($video_file); ?>" />
                    <button type="button" class="button" id="upload_video_button">
                        <?php _e('Fazer Upload do Vídeo', 'reel-marketplace'); ?>
                    </button>
                    <button type="button" class="button" id="remove_video_button" style="<?php echo empty($video_file) ? 'display:none;' : ''; ?>">
                        <?php _e('Remover Vídeo', 'reel-marketplace'); ?>
                    </button>
                    <div id="video_preview" style="margin-top: 10px;">
                        <?php if ($video_file): ?>
                            <video width="200" height="356" controls>
                                <source src="<?php echo esc_url($video_file); ?>" type="video/mp4">
                            </video>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <tr>
                <th><label for="reel_video_duration"><?php _e('Duração (segundos)', 'reel-marketplace'); ?></label></th>
                <td>
                    <input type="number" id="reel_video_duration" name="reel_video_duration" value="<?php echo esc_attr($video_duration); ?>" min="1" max="180" />
                    <p class="description"><?php _e('Duração do vídeo em segundos (máximo 180s)', 'reel-marketplace'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="reel_video_poster"><?php _e('Imagem de Capa', 'reel-marketplace'); ?></label></th>
                <td>
                    <input type="hidden" id="reel_video_poster" name="reel_video_poster" value="<?php echo esc_attr($video_poster); ?>" />
                    <button type="button" class="button" id="upload_poster_button">
                        <?php _e('Selecionar Imagem de Capa', 'reel-marketplace'); ?>
                    </button>
                    <button type="button" class="button" id="remove_poster_button" style="<?php echo empty($video_poster) ? 'display:none;' : ''; ?>">
                        <?php _e('Remover Capa', 'reel-marketplace'); ?>
                    </button>
                    <div id="poster_preview" style="margin-top: 10px;">
                        <?php if ($video_poster): ?>
                            <img src="<?php echo esc_url($video_poster); ?>" style="max-width: 200px; height: auto;" />
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
        </table>
        
        <script>
        jQuery(document).ready(function($) {
            // Video upload
            $('#upload_video_button').click(function(e) {
                e.preventDefault();
                var mediaUploader = wp.media({
                    title: '<?php _e('Selecionar Vídeo', 'reel-marketplace'); ?>',
                    button: {
                        text: '<?php _e('Usar este vídeo', 'reel-marketplace'); ?>'
                    },
                    library: {
                        type: 'video'
                    },
                    multiple: false
                });
                
                mediaUploader.on('select', function() {
                    var attachment = mediaUploader.state().get('selection').first().toJSON();
                    $('#reel_video_file').val(attachment.url);
                    $('#video_preview').html('<video width="200" height="356" controls><source src="' + attachment.url + '" type="video/mp4"></video>');
                    $('#remove_video_button').show();
                });
                
                mediaUploader.open();
            });
            
            $('#remove_video_button').click(function(e) {
                e.preventDefault();
                $('#reel_video_file').val('');
                $('#video_preview').html('');
                $(this).hide();
            });
            
            // Poster upload
            $('#upload_poster_button').click(function(e) {
                e.preventDefault();
                var mediaUploader = wp.media({
                    title: '<?php _e('Selecionar Imagem de Capa', 'reel-marketplace'); ?>',
                    button: {
                        text: '<?php _e('Usar esta imagem', 'reel-marketplace'); ?>'
                    },
                    library: {
                        type: 'image'
                    },
                    multiple: false
                });
                
                mediaUploader.on('select', function() {
                    var attachment = mediaUploader.state().get('selection').first().toJSON();
                    $('#reel_video_poster').val(attachment.url);
                    $('#poster_preview').html('<img src="' + attachment.url + '" style="max-width: 200px; height: auto;" />');
                    $('#remove_poster_button').show();
                });
                
                mediaUploader.open();
            });
            
            $('#remove_poster_button').click(function(e) {
                e.preventDefault();
                $('#reel_video_poster').val('');
                $('#poster_preview').html('');
                $(this).hide();
            });
        });
        </script>
        <?php
    }
    
    /**
     * Products meta box
     */
    public function products_meta_box($post) {
        $selected_products = get_post_meta($post->ID, '_reel_products', true);
        if (!is_array($selected_products)) {
            $selected_products = array();
        }
        
        ?>
        <div id="reel-products-container">
            <p>
                <button type="button" class="button button-secondary" id="add-product-button">
                    <?php _e('Adicionar Produto', 'reel-marketplace'); ?>
                </button>
            </p>
            
            <div id="selected-products">
                <?php foreach ($selected_products as $product_id): ?>
                    <?php $product = wc_get_product($product_id); ?>
                    <?php if ($product): ?>
                        <div class="selected-product" data-product-id="<?php echo esc_attr($product_id); ?>">
                            <div class="product-info">
                                <img src="<?php echo esc_url(wp_get_attachment_image_url($product->get_image_id(), 'thumbnail')); ?>" alt="" width="50" height="50" />
                                <div>
                                    <strong><?php echo esc_html($product->get_name()); ?></strong><br>
                                    <span class="price"><?php echo $product->get_price_html(); ?></span>
                                </div>
                            </div>
                            <button type="button" class="button-link-delete remove-product">×</button>
                            <input type="hidden" name="reel_products[]" value="<?php echo esc_attr($product_id); ?>" />
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
        
        <style>
        .selected-product {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px;
            border: 1px solid #ddd;
            margin-bottom: 5px;
            background: #f9f9f9;
        }
        
        .product-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .product-info img {
            border-radius: 4px;
        }
        
        .remove-product {
            color: #d63638;
            font-size: 18px;
            font-weight: bold;
            text-decoration: none;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            $('#add-product-button').click(function() {
                var productSelector = wp.media({
                    title: '<?php _e('Selecionar Produtos', 'reel-marketplace'); ?>',
                    button: {
                        text: '<?php _e('Adicionar Produtos', 'reel-marketplace'); ?>'
                    },
                    multiple: true
                });
                
                // Custom product selection would need additional implementation
                // For now, we'll use a simple prompt
                var productId = prompt('<?php _e('Digite o ID do produto:', 'reel-marketplace'); ?>');
                if (productId && !isNaN(productId)) {
                    addProductToReel(productId);
                }
            });
            
            $(document).on('click', '.remove-product', function() {
                $(this).closest('.selected-product').remove();
            });
            
            function addProductToReel(productId) {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'get_product_info',
                        product_id: productId,
                        nonce: '<?php echo wp_create_nonce('reel_ajax'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            var productHtml = '<div class="selected-product" data-product-id="' + productId + '">' +
                                '<div class="product-info">' +
                                '<img src="' + response.data.image + '" alt="" width="50" height="50" />' +
                                '<div>' +
                                '<strong>' + response.data.name + '</strong><br>' +
                                '<span class="price">' + response.data.price + '</span>' +
                                '</div>' +
                                '</div>' +
                                '<button type="button" class="button-link-delete remove-product">×</button>' +
                                '<input type="hidden" name="reel_products[]" value="' + productId + '" />' +
                                '</div>';
                            $('#selected-products').append(productHtml);
                        }
                    }
                });
            }
        });
        </script>
        <?php
    }
    
    /**
     * Settings meta box
     */
    public function settings_meta_box($post) {
        $status = get_post_meta($post->ID, '_reel_status', true);
        $featured = get_post_meta($post->ID, '_reel_featured', true);
        $allow_comments = get_post_meta($post->ID, '_reel_allow_comments', true);
        $autoplay = get_post_meta($post->ID, '_reel_autoplay', true);
        
        if (empty($status)) $status = 'active';
        if (empty($autoplay)) $autoplay = 'yes';
        
        ?>
        <table class="form-table">
            <tr>
                <th><label for="reel_status"><?php _e('Status', 'reel-marketplace'); ?></label></th>
                <td>
                    <select id="reel_status" name="reel_status">
                        <option value="active" <?php selected($status, 'active'); ?>><?php _e('Ativo', 'reel-marketplace'); ?></option>
                        <option value="inactive" <?php selected($status, 'inactive'); ?>><?php _e('Inativo', 'reel-marketplace'); ?></option>
                        <option value="scheduled" <?php selected($status, 'scheduled'); ?>><?php _e('Agendado', 'reel-marketplace'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="reel_featured"><?php _e('Destacar', 'reel-marketplace'); ?></label></th>
                <td>
                    <input type="checkbox" id="reel_featured" name="reel_featured" value="1" <?php checked($featured, '1'); ?> />
                    <label for="reel_featured"><?php _e('Destacar este reel no feed', 'reel-marketplace'); ?></label>
                </td>
            </tr>
            <tr>
                <th><label for="reel_autoplay"><?php _e('Reprodução Automática', 'reel-marketplace'); ?></label></th>
                <td>
                    <input type="checkbox" id="reel_autoplay" name="reel_autoplay" value="yes" <?php checked($autoplay, 'yes'); ?> />
                    <label for="reel_autoplay"><?php _e('Reproduzir automaticamente', 'reel-marketplace'); ?></label>
                </td>
            </tr>
            <tr>
                <th><label for="reel_allow_comments"><?php _e('Comentários', 'reel-marketplace'); ?></label></th>
                <td>
                    <input type="checkbox" id="reel_allow_comments" name="reel_allow_comments" value="1" <?php checked($allow_comments, '1'); ?> />
                    <label for="reel_allow_comments"><?php _e('Permitir comentários', 'reel-marketplace'); ?></label>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Analytics meta box
     */
    public function analytics_meta_box($post) {
        global $wpdb;
        
        $views = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}reel_views WHERE reel_id = %d",
            $post->ID
        ));
        
        $likes = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}reel_interactions WHERE reel_id = %d AND interaction_type = 'like'",
            $post->ID
        ));
        
        $shares = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}reel_interactions WHERE reel_id = %d AND interaction_type = 'share'",
            $post->ID
        ));
        
        ?>
        <div class="reel-analytics">
            <div class="analytics-item">
                <span class="analytics-number"><?php echo number_format($views); ?></span>
                <span class="analytics-label"><?php _e('Visualizações', 'reel-marketplace'); ?></span>
            </div>
            <div class="analytics-item">
                <span class="analytics-number"><?php echo number_format($likes); ?></span>
                <span class="analytics-label"><?php _e('Curtidas', 'reel-marketplace'); ?></span>
            </div>
            <div class="analytics-item">
                <span class="analytics-number"><?php echo number_format($shares); ?></span>
                <span class="analytics-label"><?php _e('Compartilhamentos', 'reel-marketplace'); ?></span>
            </div>
        </div>
        
        <style>
        .reel-analytics {
            display: flex;
            gap: 15px;
        }
        
        .analytics-item {
            text-align: center;
            flex: 1;
        }
        
        .analytics-number {
            display: block;
            font-size: 24px;
            font-weight: bold;
            color: #1d4ed8;
        }
        
        .analytics-label {
            display: block;
            font-size: 12px;
            color: #666;
        }
        </style>
        <?php
    }
    
    /**
     * Save meta box data
     */
    public function save_meta_boxes($post_id) {
        // Check nonce
        if (!isset($_POST['reel_meta_box_nonce']) || !wp_verify_nonce($_POST['reel_meta_box_nonce'], 'reel_meta_box')) {
            return;
        }
        
        // Check autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check permissions
        if (isset($_POST['post_type']) && 'reel' == $_POST['post_type']) {
            if (!current_user_can('edit_post', $post_id)) {
                return;
            }
        }
        
        // Save video details
        $fields = array(
            'reel_video_url',
            'reel_video_file',
            'reel_video_duration',
            'reel_video_poster',
            'reel_status',
            'reel_featured',
            'reel_allow_comments',
            'reel_autoplay'
        );
        
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, '_' . $field, sanitize_text_field($_POST[$field]));
            }
        }
        
        // Save products
        if (isset($_POST['reel_products']) && is_array($_POST['reel_products'])) {
            $products = array_map('intval', $_POST['reel_products']);
            update_post_meta($post_id, '_reel_products', $products);
        } else {
            delete_post_meta($post_id, '_reel_products');
        }
    }
    
    /**
     * Add custom columns to reel list
     */
    public function add_custom_columns($columns) {
        $new_columns = array();
        
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            
            if ($key === 'title') {
                $new_columns['video_preview'] = __('Preview', 'reel-marketplace');
                $new_columns['products'] = __('Produtos', 'reel-marketplace');
                $new_columns['analytics'] = __('Analytics', 'reel-marketplace');
            }
        }
        
        return $new_columns;
    }
    
    /**
     * Custom column content
     */
    public function custom_column_content($column, $post_id) {
        switch ($column) {
            case 'video_preview':
                $video_file = get_post_meta($post_id, '_reel_video_file', true);
                $video_poster = get_post_meta($post_id, '_reel_video_poster', true);
                
                if ($video_file) {
                    echo '<video width="60" height="107" poster="' . esc_url($video_poster) . '">';
                    echo '<source src="' . esc_url($video_file) . '" type="video/mp4">';
                    echo '</video>';
                } elseif ($video_poster) {
                    echo '<img src="' . esc_url($video_poster) . '" width="60" height="107" />';
                } else {
                    echo '<span class="dashicons dashicons-video-alt3" style="font-size: 40px; color: #ccc;"></span>';
                }
                break;
                
            case 'products':
                $products = get_post_meta($post_id, '_reel_products', true);
                if (!empty($products) && is_array($products)) {
                    echo count($products) . ' ' . __('produto(s)', 'reel-marketplace');
                } else {
                    echo '—';
                }
                break;
                
            case 'analytics':
                global $wpdb;
                $views = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}reel_views WHERE reel_id = %d",
                    $post_id
                ));
                echo $views . ' ' . __('visualizações', 'reel-marketplace');
                break;
        }
    }
}
