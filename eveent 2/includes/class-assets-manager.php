<?php

if (!defined('ABSPATH')) {
    exit;
}

class Eveent_Assets_Manager
{
    private $plugin;
    private $plugin_version;

    public function __construct($plugin)
    {
        $this->plugin = $plugin;
        $this->plugin_version = $plugin->plugin_version;
        add_action('admin_enqueue_scripts', [$this, 'admin_scripts_and_styles']);
        
        
        add_action('wp_enqueue_scripts', [$this, 'register_frontend_assets']);
        add_action('elementor/editor/before_enqueue_scripts', [$this, 'register_frontend_assets']);
    }

    public function admin_scripts_and_styles($hook)
    {
        $page_id = isset($_GET['page']) ? sanitize_key($_GET['page']) : '';
        $version = $this->plugin_version;

        if ($page_id !== 'eveent-widgets-settings' && $page_id !== 'eveent-vendor-register') {
            return;
        }

        wp_enqueue_style('ev-admin-style', plugins_url('assets/css/admin-style.css', dirname(__FILE__)), [], $version);
        wp_enqueue_style('sweetalert2-css', 'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css', [], '11.0');
        wp_enqueue_script('sweetalert2-js', 'https://cdn.jsdelivr.net/npm/sweetalert2@11', [], '11.0', true);
        wp_enqueue_script('ev-card-atm-handler', plugins_url('assets/js/ev-card-atm-handler.js', dirname(__FILE__)), ['jquery', 'image-compress-lib'], $version, true);

        if ($page_id === 'eveent-widgets-settings') {
            wp_enqueue_script('ev-deactivate-script', plugins_url('assets/js/ev-admin-script.js', dirname(__FILE__)), ['jquery', 'sweetalert2-js'], $version, true);
            wp_localize_script('ev-deactivate-script', 'evAdminAjax', [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce'   => wp_create_nonce('ev_deactivate_nonce')
            ]);
        }
    }

    public function register_frontend_assets()
    {
        $version = $this->plugin_version;

        
        if (!wp_script_is('sweetalert2-js', 'registered')) {
             wp_register_script('sweetalert2-js', 'https://cdn.jsdelivr.net/npm/sweetalert2@11', [], '11.0', true);
        }

       
        
       
        wp_register_script('ev-rsvp-handler', plugins_url('assets/js/ev-rsvp-handler.js', dirname(__FILE__)), ['jquery', 'sweetalert2-js'], $version, true);
        
      
        wp_register_script('ev-comment-handler', plugins_url('assets/js/ev-comment-handler.js', dirname(__FILE__)), ['jquery', 'sweetalert2-js'], $version, true);

        wp_register_script('ev-guestbook-lite-handler', plugins_url('assets/js/ev-guestbook-lite-handler.js', dirname(__FILE__)), ['jquery', 'elementor-frontend'], $version, true);

        wp_register_script('image-compress-lib', 'https://cdn.jsdelivr.net/npm/browser-image-compression@2.0.2/dist/browser-image-compression.js', [], '2.0.2', true);
        wp_register_script('ev-card-atm-handler', plugins_url('assets/js/ev-card-atm-handler.js', dirname(__FILE__)), ['jquery', 'sweetalert2-js', 'image-compress-lib'], $version, true);

     
        wp_register_script('ev-privacy-handler', plugins_url('assets/js/ev-privacy-handler.js', dirname(__FILE__)), ['jquery'], $version, true);
        wp_register_script('ev-card-dl', plugins_url('assets/js/ewf-download-handler.js', dirname(__FILE__)), ['jquery', 'ewf-html2canvas'], $version, true);
        $barcode_js_ver = file_exists(dirname(__FILE__) . '/../assets/js/ewf-barcode-handler.js') ? filemtime(dirname(__FILE__) . '/../assets/js/ewf-barcode-handler.js') : $version;
        wp_register_script('ev-barcode-handler', plugins_url('assets/js/ewf-barcode-handler.js', dirname(__FILE__)), ['jquery', 'elementor-frontend', 'ewf-qrcode-js'], $barcode_js_ver, true);
        
     
        $audio_js_ver = file_exists(dirname(__FILE__) . '/../assets/js/ev-audio-handler.js') ? filemtime(dirname(__FILE__) . '/../assets/js/ev-audio-handler.js') : $version;
        wp_register_script(
            'ev-audio-handler', 
            plugins_url('assets/js/ev-audio-handler.js', dirname(__FILE__)), 
            ['jquery'], 
            $audio_js_ver, 
            true
        );
        
        wp_register_script(
            'ev-video-js', 
            plugins_url('assets/js/ev-video.js', dirname(__FILE__)), 
            ['jquery', 'elementor-frontend'], 
            $version, 
            true
        );

       
        wp_register_script('ewf-html2canvas', 'https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js', [], '1.4.1', true);
        wp_register_script('ewf-qrcode-js', 'https://cdn.jsdelivr.net/gh/davidshimjs/qrcodejs/qrcode.min.js', [], '1.0.0', true);

        
        $barcode_css_ver = file_exists(dirname(__FILE__) . '/../assets/css/ewf-barcode-style.css') ? filemtime(dirname(__FILE__) . '/../assets/css/ewf-barcode-style.css') : $version;
        wp_register_style('ev-barcode-style', plugins_url('assets/css/ewf-barcode-style.css', dirname(__FILE__)), [], $barcode_css_ver);
        wp_register_style('ewf-card-atm-style', plugins_url('assets/css/ewf-card-atm-style.css', dirname(__FILE__)), [], $version);
        wp_register_style('ewf-card-gift-style', plugins_url('assets/css/ewf-card-gift-style.css', dirname(__FILE__)), [], $version);
        wp_register_style('ewf-privacy-widget', plugins_url('assets/css/ewf-privacy-widget.css', dirname(__FILE__)), [], $version);

        $audio_css_ver = file_exists(dirname(__FILE__) . '/../assets/css/ev-audio-style.css') ? filemtime(dirname(__FILE__) . '/../assets/css/ev-audio-style.css') : $version;
        wp_register_style(
            'ev-audio-style', 
            plugins_url('assets/css/ev-audio-style.css', dirname(__FILE__)), 
            [], 
            $audio_css_ver
        );
       
        wp_register_style('ev-rsvp-style', plugins_url('assets/css/ev-rsvp-style.css', dirname(__FILE__)), [], $version);
        wp_register_style('ev-guestbook-lite-style', plugins_url('assets/css/ev-guestbook-lite-style.css', dirname(__FILE__)), [], $version);
        
         wp_register_style(
            'ev-video-css', 
            plugins_url('assets/css/ev-video.css', dirname(__FILE__)), 
            [], 
            $version
        );
        
        
       
        wp_localize_script('jquery', 'EvGlobal', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'isAdmin' => current_user_can('moderate_comments')
        ]);

       
        wp_localize_script('ev-card-atm-handler', 'EvGift', [
            'nonce' => wp_create_nonce('ewf_send_gift_notification')
        ]);

       
        wp_localize_script('ev-rsvp-handler', 'EvRSVP', [
            'nonce'           => wp_create_nonce('ewf_rsvp'),
            'thanksComment' => 'Terima kasih atas ucapan Anda!',
            'thanksReply'   => 'Balasan Anda telah terkirim.',
        ]);

       
        wp_localize_script('ev-comment-handler', 'EvRSVP', [
            'nonce' => wp_create_nonce('ewf_rsvp') 
        ]);
        
        wp_localize_script('ev-guestbook-lite-handler', 'evGuestbookLiteConfig', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('ev_lite_guestbook_nonce')
        ]);
    }
}