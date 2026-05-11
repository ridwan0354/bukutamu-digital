<?php
/**
 * Plugin Name:       Eveent Widgets
 * Plugin URI:        https://eveent.web.id
 * Description:       Official widget add-on for the Eveent smart guest book.
 * Version:           2.2.3
 * Author:            Eveent
 * Author URI:        https://eveent.web.id
 * Text Domain:       eveent-widgets
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * License:           Proprietary
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function eveent_widgets_load_textdomain() {
	load_plugin_textdomain( 
		'eveent-widgets', 
		false, 
		dirname( plugin_basename( __FILE__ ) ) . '/languages/' 
	);
}
add_action( 'init', 'eveent_widgets_load_textdomain', 0 );

/**
 * Get the configurable API base domain.
 * Defaults to 'eveent.web.id' if not set in admin.
 */
function eveent_get_api_base_domain() {
	return get_option( 'ev_api_base_domain', 'qr.galipatstory.com' );
}

/**
 * Get the full API base URL (with https://).
 */
function eveent_get_api_base_url() {
	return 'https://' . eveent_get_api_base_domain();
}

function eveent_get_plugin_version() {
	if ( ! function_exists( 'get_plugin_data' ) ) {
		require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	}
	$plugin_data = get_plugin_data( __FILE__ );
	return $plugin_data['Version'];
}


add_filter( 'plugins_api', 'eveent_plugin_update_info', 10, 3 );
/**
 * Shared helper: fetch remote plugin info with caching.
 */
function eveent_get_remote_plugin_info() {
	return null;
}

function eveent_plugin_update_info( $res, $action, $args ) {
	$plugin_slug = 'eveent-widgets';
	
	if ( 'plugin_information' !== $action || $plugin_slug !== $args->slug ) {
		return $res;
	}

	$remote_info = eveent_get_remote_plugin_info();
	if ( ! $remote_info ) {
		return $res;
	}

	
	if ( is_object( $remote_info ) ) {
		$res = new stdClass();
		$res->name = $remote_info->name ?? '';
		$res->slug = $remote_info->slug ?? '';
		$res->author = $remote_info->author ?? '';
		$res->version = $remote_info->version ?? '';
		$res->tested = $remote_info->tested ?? '';
		$res->requires = $remote_info->requires_wp ?? '';
		$res->download_link = $remote_info->download_link ?? '';
		$res->sections = [
			'changelog'   => $remote_info->changelog ?? '',
			'description' => 'Official widget add-on for the Eveent smart guest book.',
		];
		$res->upgrade_notice = $remote_info->upgrade_notice ?? '';
	}

	return $res;
}


add_filter( 'pre_set_site_transient_update_plugins', 'eveent_check_for_updates' );
function eveent_check_for_updates( $transient ) {
	if ( empty( $transient->checked ) ) {
		return $transient;
	}

	$plugin_slug = 'eveent-widgets';
	$plugin_base = plugin_basename( __FILE__ );
	$remote_info = eveent_get_remote_plugin_info();

	if ( ! $remote_info ) {
		return $transient;
	}

	$current_version = eveent_get_plugin_version();

	
	if ( is_object( $remote_info ) && ! empty( $remote_info->version ) && version_compare( $current_version, $remote_info->version, '<' ) ) {
		$update              = new stdClass();
		$update->id          = $plugin_base; 
		$update->slug        = $plugin_slug;
		$update->plugin      = $plugin_base;
		$update->new_version = $remote_info->version;
		$update->url         = 'https://eveent.web.id';
		$update->package     = $remote_info->download_link;

		if ( isset( $remote_info->tested ) ) {
			$update->tested = $remote_info->tested;
		}
		if ( isset( $remote_info->requires_wp ) ) {
			$update->requires = $remote_info->requires_wp;
		}
		if ( isset( $remote_info->requires_php ) ) {
			$update->requires_php = $remote_info->requires_php;
		}

		$transient->response[ $plugin_base ] = $update;
	} else {
		
		$no_update = new stdClass();
		$no_update->id     = $plugin_base;
		$no_update->slug   = $plugin_slug;
		$no_update->plugin = $plugin_base;
		$no_update->new_version = $current_version;
		$no_update->url         = 'https://eveent.web.id';
		$transient->no_update[ $plugin_base ] = $no_update;
	}

	return $transient;
}


function eveent_load_plugin_instance() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-settings.php';
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-plugin.php';
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-whatsapp-sender.php';
	
	Eveent_Custom_Widgets_Plugin::instance();
}
add_action( 'elementor/init', 'eveent_load_plugin_instance' );

// Load iframe frontend handler (works without Elementor)
require_once plugin_dir_path( __FILE__ ) . 'includes/class-iframe-frontend.php';
new Eveent_Iframe_Frontend();

// Flush rewrite rules on plugin activation (needed for /buku-tamu/ route)
register_activation_hook( __FILE__, function() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-iframe-frontend.php';
	$frontend = new Eveent_Iframe_Frontend();
	$frontend->register_rewrite_rules();
	flush_rewrite_rules();
});

function eveent_prepare_comment_for_api( $response, $comment, $request ) {
	if ( empty( $comment->comment_ID ) ) {
		return $response;
	}

	if ( ! isset( $response->data['meta_data'] ) ) {
		$response->data['meta_data'] = [];
	}

	$attendance_status = get_comment_meta( $comment->comment_ID, 'attendance', true );
	if ( $attendance_status ) {
		$response->data['meta_data']['attendance'] = [ $attendance_status ];
	}

	$guest_count = get_comment_meta( $comment->comment_ID, 'guest', true );
	if ( $guest_count ) {
		$response->data['meta_data']['guest'] = [ $guest_count ];
	}

	$detailed_attendance = get_comment_meta( $comment->comment_ID, 'detailed_attendance', true );
	if ( $detailed_attendance ) {
		$response->data['meta_data']['detailed_attendance'] = [ $detailed_attendance ];
	}

	$group_reference = get_comment_meta( $comment->comment_ID, 'group_reference', true );
	if ( $group_reference ) {
		$response->data['meta_data']['group_reference'] = [ $group_reference ];
	}

	$selected_sticker = get_comment_meta( $comment->comment_ID, '_selected_sticker', true );
	if ( $selected_sticker ) {
		$response->data['meta_data']['_selected_sticker'] = $selected_sticker;
	}

	return $response;
}
add_filter( 'rest_prepare_comment', 'eveent_prepare_comment_for_api', 10, 3 );

// Register meta_data field so it's not stripped by WP REST API when _fields is used
add_action( 'rest_api_init', function() {
	register_rest_field( 'comment', 'meta_data', [
		'get_callback' => function( $comment_arr ) {
			$meta_data = [];
			
			$attendance_status = get_comment_meta( $comment_arr['id'], 'attendance', true );
			if ( $attendance_status ) {
				$meta_data['attendance'] = [ $attendance_status ];
			}
			
			$konfirmasi = get_comment_meta( $comment_arr['id'], 'konfirmasi', true );
			if ( $konfirmasi ) {
				$meta_data['konfirmasi'] = [ $konfirmasi ];
			}
			
			$guest_count = get_comment_meta( $comment_arr['id'], 'guest', true );
			if ( $guest_count ) {
				$meta_data['guest'] = [ $guest_count ];
			}
			
			$detailed_attendance = get_comment_meta( $comment_arr['id'], 'detailed_attendance', true );
			if ( $detailed_attendance ) {
				$meta_data['detailed_attendance'] = [ $detailed_attendance ];
			}
			
			$group_reference = get_comment_meta( $comment_arr['id'], 'group_reference', true );
			if ( $group_reference ) {
				$meta_data['group_reference'] = [ $group_reference ];
			}
			
			$selected_sticker = get_comment_meta( $comment_arr['id'], '_selected_sticker', true );
			if ( $selected_sticker ) {
				$meta_data['_selected_sticker'] = $selected_sticker;
			}
			
			return $meta_data;
		},
		'schema' => null,
	]);
});


function eveent_catch_cron_trigger() {
    // Rate limit: max 10 trigger per minute untuk mencegah abuse dari user login
    $ip  = sanitize_text_field( $_SERVER['REMOTE_ADDR'] ?? '' );
    $key = 'ev_cron_rl_' . md5( $ip );
    $hits = (int) get_transient( $key );
    if ( $hits >= 10 ) {
        wp_send_json_success( 'Rate limited.' );
    }
    set_transient( $key, $hits + 1, 60 );

    spawn_cron();
    wp_send_json_success( 'Cron trigger received.' );
}
add_action( 'wp_ajax_trigger_wp_cron', 'eveent_catch_cron_trigger' );



function handle_delete_rsvp_comment() {
	if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'delete_rsvp_comment_action' ) ) {
		wp_send_json_error( 'Akses ditolak - Nonce invalid.' );
	}

	if ( ! is_user_logged_in() ) {
		wp_send_json_error( 'Silakan login terlebih dahulu.' );
	}

	$comment_id = intval( $_POST['comment_id'] );

	if ( $comment_id <= 0 ) {
		wp_send_json_error( 'ID Komentar tidak valid.' );
	}
   
	$comment = get_comment( $comment_id );

	if ( ! $comment ) {
		wp_send_json_error( 'Komentar tidak ditemukan.' );
	}

	$current_user_id = get_current_user_id();
	$is_admin        = current_user_can( 'manage_options' );
	$post_author_id  = get_post_field( 'post_author', $comment->comment_post_ID );
	$is_post_owner   = intval( $post_author_id ) === $current_user_id;
   
	if ( ! $is_admin && ! $is_post_owner ) {
		wp_send_json_error( 'Hanya pemilik undangan yang dapat menghapus komentar ini.' );
	}
	
	$deleted = wp_delete_comment( $comment_id, true );

	if ( $deleted ) {
		wp_send_json_success( 'Komentar berhasil dihapus.' );
	} else {
		wp_send_json_error( 'Gagal menghapus komentar dari database.' );
	}
}
add_action( 'wp_ajax_delete_rsvp_comment', 'handle_delete_rsvp_comment' );


function eveent_register_image_compression_library() {
	wp_register_script(
		'image-compress-lib',
		'https://cdn.jsdelivr.net/npm/browser-image-compression@2.0.2/dist/browser-image-compression.js',
		[],
		'2.0.2',
		true
	);
}
add_action( 'wp_enqueue_scripts', 'eveent_register_image_compression_library' );
add_action( 'admin_enqueue_scripts', 'eveent_register_image_compression_library' );