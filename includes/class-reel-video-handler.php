<?php
/**
 * Reel Video Handler Class
 * 
 * Handles video processing and optimization
 */

if (!defined('ABSPATH')) {
    exit;
}

class Reel_Video_Handler {
    
    public function __construct() {
        add_filter('wp_handle_upload_prefilter', array($this, 'handle_video_upload'));
        add_filter('wp_generate_attachment_metadata', array($this, 'generate_video_metadata'), 10, 2);
        add_action('add_attachment', array($this, 'process_video_attachment'));
    }
    
    /**
     * Handle video upload and validation
     */
    public function handle_video_upload($file) {
        // Only process video files
        if (strpos($file['type'], 'video/') !== 0) {
            return $file;
        }
        
        // Check file size (max 100MB)
        $max_size = 100 * 1024 * 1024; // 100MB
        if ($file['size'] > $max_size) {
            $file['error'] = __('O arquivo de vídeo é muito grande. Tamanho máximo permitido: 100MB.', 'reel-marketplace');
            return $file;
        }
        
        // Check video duration (max 3 minutes)
        $duration = $this->get_video_duration($file['tmp_name']);
        if ($duration && $duration > 180) {
            $file['error'] = __('O vídeo é muito longo. Duração máxima permitida: 3 minutos.', 'reel-marketplace');
            return $file;
        }
        
        // Validate video dimensions for mobile optimization
        $dimensions = $this->get_video_dimensions($file['tmp_name']);
        if ($dimensions) {
            $aspect_ratio = $dimensions['width'] / $dimensions['height'];
            
            // Recommend vertical videos (9:16 ratio)
            if ($aspect_ratio > 1) {
                // This is a landscape video - warn but don't block
                add_action('admin_notices', function() {
                    echo '<div class="notice notice-warning"><p>' . 
                         __('Recomendamos vídeos verticais (9:16) para melhor experiência mobile.', 'reel-marketplace') . 
                         '</p></div>';
                });
            }
        }
        
        return $file;
    }
    
    /**
     * Generate video metadata
     */
    public function generate_video_metadata($metadata, $attachment_id) {
        $file_path = get_attached_file($attachment_id);
        $file_type = get_post_mime_type($attachment_id);
        
        if (strpos($file_type, 'video/') !== 0) {
            return $metadata;
        }
        
        // Get video information
        $video_info = $this->get_video_info($file_path);
        
        if ($video_info) {
            $metadata['duration'] = $video_info['duration'];
            $metadata['width'] = $video_info['width'];
            $metadata['height'] = $video_info['height'];
            $metadata['fileformat'] = $video_info['format'];
            $metadata['bitrate'] = $video_info['bitrate'];
            
            // Generate thumbnail
            $thumbnail_path = $this->generate_video_thumbnail($file_path, $attachment_id);
            if ($thumbnail_path) {
                $metadata['thumbnail'] = $thumbnail_path;
            }
        }
        
        return $metadata;
    }
    
    /**
     * Process video attachment after upload
     */
    public function process_video_attachment($attachment_id) {
        $file_type = get_post_mime_type($attachment_id);
        
        if (strpos($file_type, 'video/') !== 0) {
            return;
        }
        
        // Schedule video optimization
        wp_schedule_single_event(time() + 60, 'reel_optimize_video', array($attachment_id));
    }
    
    /**
     * Get video duration using FFmpeg or getID3
     */
    private function get_video_duration($file_path) {
        // Try FFmpeg first
        if ($this->is_ffmpeg_available()) {
            $command = sprintf(
                'ffprobe -v quiet -show_entries format=duration -of csv="p=0" %s',
                escapeshellarg($file_path)
            );
            
            $output = shell_exec($command);
            if ($output) {
                return floatval(trim($output));
            }
        }
        
        // Fallback to getID3 if available
        if (class_exists('getID3')) {
            $getID3 = new getID3();
            $file_info = $getID3->analyze($file_path);
            
            if (isset($file_info['playtime_seconds'])) {
                return $file_info['playtime_seconds'];
            }
        }
        
        return null;
    }
    
    /**
     * Get video dimensions
     */
    private function get_video_dimensions($file_path) {
        // Try FFmpeg first
        if ($this->is_ffmpeg_available()) {
            $command = sprintf(
                'ffprobe -v quiet -select_streams v:0 -show_entries stream=width,height -of csv="s=x:p=0" %s',
                escapeshellarg($file_path)
            );
            
            $output = shell_exec($command);
            if ($output) {
                $dimensions = explode('x', trim($output));
                if (count($dimensions) === 2) {
                    return array(
                        'width' => intval($dimensions[0]),
                        'height' => intval($dimensions[1])
                    );
                }
            }
        }
        
        return null;
    }
    
    /**
     * Get comprehensive video information
     */
    private function get_video_info($file_path) {
        $info = array();
        
        // Try FFmpeg
        if ($this->is_ffmpeg_available()) {
            $command = sprintf(
                'ffprobe -v quiet -print_format json -show_format -show_streams %s',
                escapeshellarg($file_path)
            );
            
            $output = shell_exec($command);
            if ($output) {
                $json = json_decode($output, true);
                
                if (isset($json['format'])) {
                    $info['duration'] = floatval($json['format']['duration']);
                    $info['bitrate'] = intval($json['format']['bit_rate']);
                    $info['format'] = $json['format']['format_name'];
                }
                
                if (isset($json['streams'])) {
                    foreach ($json['streams'] as $stream) {
                        if ($stream['codec_type'] === 'video') {
                            $info['width'] = $stream['width'];
                            $info['height'] = $stream['height'];
                            $info['codec'] = $stream['codec_name'];
                            break;
                        }
                    }
                }
            }
        }
        
        return $info;
    }
    
    /**
     * Generate video thumbnail
     */
    private function generate_video_thumbnail($video_path, $attachment_id) {
        if (!$this->is_ffmpeg_available()) {
            return null;
        }
        
        $upload_dir = wp_upload_dir();
        $thumbnail_name = 'reel-thumb-' . $attachment_id . '.jpg';
        $thumbnail_path = $upload_dir['path'] . '/' . $thumbnail_name;
        $thumbnail_url = $upload_dir['url'] . '/' . $thumbnail_name;
        
        // Generate thumbnail at 2 seconds into the video
        $command = sprintf(
            'ffmpeg -i %s -ss 00:00:02 -vframes 1 -q:v 2 -f image2 %s 2>/dev/null',
            escapeshellarg($video_path),
            escapeshellarg($thumbnail_path)
        );
        
        $result = shell_exec($command);
        
        if (file_exists($thumbnail_path)) {
            // Create attachment for thumbnail
            $thumbnail_attachment = array(
                'guid' => $thumbnail_url,
                'post_mime_type' => 'image/jpeg',
                'post_title' => 'Video Thumbnail',
                'post_content' => '',
                'post_status' => 'inherit'
            );
            
            $thumbnail_id = wp_insert_attachment($thumbnail_attachment, $thumbnail_path);
            
            if ($thumbnail_id) {
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                $thumbnail_metadata = wp_generate_attachment_metadata($thumbnail_id, $thumbnail_path);
                wp_update_attachment_metadata($thumbnail_id, $thumbnail_metadata);
                
                return $thumbnail_url;
            }
        }
        
        return null;
    }
    
    /**
     * Check if FFmpeg is available
     */
    private function is_ffmpeg_available() {
        $output = shell_exec('which ffmpeg 2>/dev/null');
        return !empty($output);
    }
    
    /**
     * Optimize video for web delivery
     */
    public function optimize_video($attachment_id) {
        if (!$this->is_ffmpeg_available()) {
            return false;
        }
        
        $file_path = get_attached_file($attachment_id);
        if (!file_exists($file_path)) {
            return false;
        }
        
        $upload_dir = wp_upload_dir();
        $file_info = pathinfo($file_path);
        $optimized_name = $file_info['filename'] . '-optimized.mp4';
        $optimized_path = $upload_dir['path'] . '/' . $optimized_name;
        
        // FFmpeg command for web optimization
        $command = sprintf(
            'ffmpeg -i %s -c:v libx264 -preset medium -crf 23 -c:a aac -b:a 128k -movflags +faststart -vf "scale=trunc(iw/2)*2:trunc(ih/2)*2" %s 2>/dev/null',
            escapeshellarg($file_path),
            escapeshellarg($optimized_path)
        );
        
        $result = shell_exec($command);
        
        if (file_exists($optimized_path)) {
            // Update attachment with optimized version
            $optimized_url = $upload_dir['url'] . '/' . $optimized_name;
            update_attached_file($attachment_id, $optimized_path);
            
            // Remove original file if optimization was successful
            if (filesize($optimized_path) < filesize($file_path)) {
                unlink($file_path);
            }
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Convert video to multiple formats/qualities
     */
    public function create_video_variants($attachment_id) {
        if (!$this->is_ffmpeg_available()) {
            return array();
        }
        
        $file_path = get_attached_file($attachment_id);
        if (!file_exists($file_path)) {
            return array();
        }
        
        $upload_dir = wp_upload_dir();
        $file_info = pathinfo($file_path);
        $variants = array();
        
        // Quality settings
        $qualities = array(
            'high' => array('scale' => '720:-2', 'crf' => '20', 'suffix' => '-720p'),
            'medium' => array('scale' => '480:-2', 'crf' => '23', 'suffix' => '-480p'),
            'low' => array('scale' => '360:-2', 'crf' => '26', 'suffix' => '-360p')
        );
        
        foreach ($qualities as $quality => $settings) {
            $variant_name = $file_info['filename'] . $settings['suffix'] . '.mp4';
            $variant_path = $upload_dir['path'] . '/' . $variant_name;
            $variant_url = $upload_dir['url'] . '/' . $variant_name;
            
            $command = sprintf(
                'ffmpeg -i %s -c:v libx264 -preset medium -crf %s -c:a aac -b:a 128k -movflags +faststart -vf "scale=%s" %s 2>/dev/null',
                escapeshellarg($file_path),
                $settings['crf'],
                $settings['scale'],
                escapeshellarg($variant_path)
            );
            
            shell_exec($command);
            
            if (file_exists($variant_path)) {
                $variants[$quality] = $variant_url;
            }
        }
        
        return $variants;
    }
    
    /**
     * Get video streaming URL based on device/connection
     */
    public static function get_adaptive_video_url($attachment_id, $quality = 'auto') {
        $file_url = wp_get_attachment_url($attachment_id);
        
        if ($quality === 'auto') {
            // Detect connection speed and device capabilities
            // This is a simplified version - you might want to use JavaScript for better detection
            $quality = 'medium';
        }
        
        $variants = get_post_meta($attachment_id, '_video_variants', true);
        
        if (is_array($variants) && isset($variants[$quality])) {
            return $variants[$quality];
        }
        
        return $file_url;
    }
}
