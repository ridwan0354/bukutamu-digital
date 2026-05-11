<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'EWF_URL' ) ) {
    define( 'EWF_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'EWF_VERSION' ) ) {
	define( 'EWF_VERSION', function_exists('eveent_get_plugin_version') ? eveent_get_plugin_version() : '1.0.0' );	
}

final class Eveent_Custom_Widgets_Plugin {

	public $plugin_version;
	const MINIMUM_ELEMENTOR_VERSION = '3.0.0';
	const EVEENT_URL = 'https://eveent.web.id/api/check-license';
	const EVEENT_DEACTIVATE_URL = 'https://eveent.web.id/api/deactivate';
	private static $_instance = null;
	public $is_licensed = false;
	public $license_message = '';
	public $api_handler;
	public $admin_menu;
	public $settings;
	public $ajax_handler;
	public $elementor_integration;
	public $assets_manager;

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	public function __construct() {
	    $this->plugin_version = EWF_VERSION;
		$this->load_dependencies();
        $this->init();
	}

	private function load_dependencies() {
      
		require_once plugin_dir_path( __FILE__ ) . 'class-api-handler.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-admin-menu.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-settings.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-ajax-handler.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-elementor-integration.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-assets-manager.php';
	}

	public function init() {
       
		$this->api_handler = new Eveent_API_Handler( $this );
		$this->admin_menu = new Eveent_Admin_Menu( $this );
		$this->settings = new Eveent_Settings( $this );
		$this->ajax_handler = new Eveent_Ajax_Handler( $this );
		$this->elementor_integration = new Eveent_Elementor_Integration( $this );
		$this->assets_manager = new Eveent_Assets_Manager( $this );
		
		$this->is_licensed = $this->api_handler->check_license();

		$this->register_hooks();
	}
	
	private function register_hooks() {
    
    add_action( 'admin_menu', [ $this->admin_menu, 'add_admin_menu' ] );
    add_action( 'wp_head', [ $this, 'apply_event_visibility_css' ], 1 );
    add_filter( 'eveent_get_guest_data', [ $this, 'guest_data_filter_proxy' ], 10, 2 );
    add_action( 'admin_init', [ $this->admin_menu, 'register_plugin_settings' ] );
    add_action( 'admin_enqueue_scripts', [ $this->assets_manager, 'admin_scripts_and_styles' ] );
    add_action( 'add_meta_boxes_comment', [ $this->admin_menu, 'admin_add_meta_box' ] );
    add_action( 'edit_comment', [ $this->admin_menu, 'admin_save_meta_fields' ] );
    add_filter( 'comment_author', [ $this->admin_menu, 'admin_add_meta_in_author_comment' ], 10001, 2 );
    
    add_action( 'wp_ajax_ev_widget_deactivate_license', [ $this->api_handler, 'deactivate_license_ajax_handler' ] );
    add_action( 'wp_ajax_ev_widget_refresh_license', [ $this->api_handler, 'refresh_license_status_ajax_handler' ] );

   
    if ( $this->is_licensed ) {
       
        add_action( 'wp_enqueue_scripts', [ $this->assets_manager, 'register_frontend_assets' ] );
        add_action( 'elementor/frontend/after_register_scripts', [ $this->assets_manager, 'register_frontend_assets' ] );
        
       
        add_action( 'elementor/widgets/register', [ $this->elementor_integration, 'register_widgets' ] );
        add_action( 'elementor/elements/categories_registered', [ $this->elementor_integration, 'add_elementor_widget_category' ] );
        
        add_action( 'wp_ajax_ev_get_comments', [ $this->ajax_handler, 'ajax_get_comments' ] );
        add_action( 'wp_ajax_nopriv_ev_get_comments', [ $this->ajax_handler, 'ajax_get_comments' ] );
        add_action( 'wp_ajax_ev_insert_comment', [ $this->ajax_handler, 'ajax_insert_comment' ] );
        add_action( 'wp_ajax_nopriv_ev_insert_comment', [ $this->ajax_handler, 'ajax_insert_comment' ] );
        add_action( 'wp_ajax_ev_insert_public_reply', [ $this->ajax_handler, 'ajax_insert_public_reply' ] );
        add_action( 'wp_ajax_nopriv_ev_insert_public_reply', [ $this->ajax_handler, 'ajax_insert_public_reply' ] );
        add_action( 'wp_ajax_nopriv_ev_rsvp_toggle_like', [ $this->ajax_handler, 'ajax_toggle_like' ] );
        add_action( 'wp_ajax_ev_rsvp_toggle_like', [ $this->ajax_handler, 'ajax_toggle_like' ] );
        
        register_meta('comment', '_selected_sticker', ['type' => 'string', 'description' => 'Data stiker.', 'single' => true, 'show_in_rest' => true]);
        
        add_shortcode('eveent_rsvp_count', [$this, 'shortcode_rsvp_count']);
        add_shortcode('eveent_table_number', [$this, 'shortcode_table_number']);
        
    } else {
        add_action( 'admin_notices', [ $this->admin_menu, 'admin_notice_invalid_license' ] );
    }
}
	
	public function get_wp_timezone() {
        return [ 'timezone' => wp_timezone_string() ];
    }

	public function is_licensed() { return $this->is_licensed; }

	public function get_license_message() { return $this->license_message; }
	
	public function guest_data_filter_proxy( $guest_data, $barcode_uid ) {
        return $this->api_handler->fetch_guest_events_data_from_backend( $barcode_uid );
    }

    public function apply_event_visibility_css() {
        $barcode_uid = isset($_GET['id']) ? sanitize_text_field($_GET['id']) : null;
        if ( $barcode_uid && !is_admin() && !is_preview() ) {
            $guest_data = apply_filters('eveent_get_guest_data', [], $barcode_uid);
            $allowed_events_keys = $guest_data['allowed_events_keys'] ?? [];
            $master_events = $guest_data['master_events'] ?? []; 
            if ( !empty($allowed_events_keys) && !empty($master_events) ) {
                $css_to_hide = '';
                $all_event_keys = array_keys($master_events);
                foreach ($all_event_keys as $key) {
                    // Sanitasi key untuk mencegah CSS injection dari API response
                    $safe_key = sanitize_html_class( $key );
                    if ( empty( $safe_key ) ) continue;
                    if (!in_array($key, $allowed_events_keys)) {
                        $css_to_hide .= ".eveent-{$safe_key} { display: none !important; visibility: hidden !important; height: 0 !important; } ";
                    }
                }
                if (!empty($css_to_hide)) {
                    echo '<style id="eveent-dynamic-event-visibility">' . $css_to_hide . '</style>';
                }
            }
        }
    }

    public function shortcode_rsvp_count($atts) {
        $barcode_uid = isset($_GET['id']) ? sanitize_text_field($_GET['id']) : (isset($_GET['to']) ? sanitize_text_field($_GET['to']) : null);
        if ( $barcode_uid && !is_admin() ) {
            $guest_data = apply_filters('eveent_get_guest_data', [], $barcode_uid);
            return isset($guest_data['rsvp_count']) ? esc_html($guest_data['rsvp_count']) : '';
        }
        return '';
    }

    public function shortcode_table_number($atts) {
        $barcode_uid = isset($_GET['id']) ? sanitize_text_field($_GET['id']) : (isset($_GET['to']) ? sanitize_text_field($_GET['to']) : null);
        if ( $barcode_uid && !is_admin() ) {
            $guest_data = apply_filters('eveent_get_guest_data', [], $barcode_uid);
            return isset($guest_data['table_number']) && $guest_data['table_number'] !== null ? esc_html($guest_data['table_number']) : '';
        }
        return '';
    }
}