<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Eveent_Elementor_Integration {

	private $plugin;

	public function __construct( $plugin ) {
		$this->plugin = $plugin;
	}

	public function register_widgets( $widgets_manager ) {
		
		
		$active_modules = get_option( 'eveent_active_modules', [] );
		$is_default_active = empty( $active_modules );

	
		if ( $is_default_active || ! empty( $active_modules['barcode'] ) ) {
			require_once( plugin_dir_path( __DIR__ ) . 'widgets/ewf-barcode-widget.php' );
			$widgets_manager->register( new \Elementor_Barcode_Widget() );
		}
		
	
		if ( $is_default_active || ! empty( $active_modules['rsvp'] ) ) {
			require_once( plugin_dir_path( __DIR__ ) . 'widgets/ev-rsvp-widget.php' );
			$widgets_manager->register( new \Elementor_evRSVP_Widget() );
		}
		
		if ( $is_default_active || ! empty( $active_modules['comment'] ) ) {
        require_once( plugin_dir_path( __DIR__ ) . 'widgets/ev-comment-widget.php' );
        $widgets_manager->register( new \Elementor_EV_Comment_Widget() );
    }
		
		
		if ( $is_default_active || ! empty( $active_modules['card_atm'] ) ) {
			require_once( plugin_dir_path( __DIR__ ) . 'widgets/ev-card-atm.php' );
			$widgets_manager->register( new \Eveent_EV_Card_ATM_Widget() );
		}
		
	
		if ( $is_default_active || ! empty( $active_modules['card_gift'] ) ) {
			require_once( plugin_dir_path( __DIR__ ) . 'widgets/ev-card-gift.php' );
			$widgets_manager->register( new \Eveent_EV_Card_Gift_Widget() );
		}
		
	
		if ( $is_default_active || ! empty( $active_modules['privacy'] ) ) {
			require_once( plugin_dir_path( __DIR__ ) . 'widgets/ev-privacy-widget.php' );
			$widgets_manager->register( new \Eveent\Widgets\EV_Privacy_Widget() );
		}
		
	
		if ( $is_default_active || ! empty( $active_modules['audio'] ) ) {
			require_once dirname( __DIR__ ) . '/widgets/ev-audio-widget.php';
			$widgets_manager->register( new \Eveent_Audio_Player() );
		}
		
		if ( $is_default_active || ! empty( $active_modules['video'] ) ) {
			require_once dirname( __DIR__ ) . '/widgets/ev-video-widget.php';
			$widgets_manager->register( new \Eveent_Video_Player() );
		}

		if ( ! empty( $active_modules['guestbook_lite'] ) ) {
			require_once plugin_dir_path( __DIR__ ) . 'widgets/ev-guestbook-lite-widget.php';
			$widgets_manager->register( new \Elementor_Guestbook_Lite_Widget() );
		}
		
	}
	
	public function add_elementor_widget_category( $elements_manager ) {
		$elements_manager->add_category(
			'eveent-widgets',
			[
				'title' => esc_html__( 'Eveent Widgets', 'eveent-widgets' ),
				'icon' => 'eicon-barcode'
			]
		);
	}
}