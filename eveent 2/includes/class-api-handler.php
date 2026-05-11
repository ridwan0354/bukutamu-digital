<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class Eveent_API_Handler {

    private $plugin;

    public function __construct( $plugin ) {
        $this->plugin = $plugin;
        add_action( 'rest_api_init', [ $this, 'register_endpoints' ] );
        add_filter( 'eveent_get_guest_data', [ $this, 'filter_validate_guest_id' ], 10, 2 );
        add_filter( 'rest_prepare_comment', [ $this, 'clean_system_message_content' ], 10, 3 );
        add_filter( 'rest_pre_dispatch', [ $this, 'rate_limit_wp_comments_api' ], 10, 3 );
    }

    public function clean_system_message_content( $response, $comment, $request ) {
        if ( get_comment_meta( $comment->comment_ID, '_is_system_message', true ) ) {
            $response->data['content']['rendered'] = '';
        }
        return $response;
    }

    private function get_client_ip() {
        // Prioritize REMOTE_ADDR to prevent IP spoofing via headers
        $ip = isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1';
        
        // Only trust Cloudflare or nginx real IP headers
        $trusted_headers = [ 'HTTP_CF_CONNECTING_IP', 'HTTP_X_REAL_IP' ];
        foreach ( $trusted_headers as $header ) {
            if ( ! empty( $_SERVER[ $header ] ) ) {
                $candidate = trim( explode( ',', $_SERVER[ $header ] )[0] );
                if ( filter_var( $candidate, FILTER_VALIDATE_IP ) !== false ) {
                    return $candidate;
                }
            }
        }
        
        return $ip;
    }

    public function register_endpoints() {
        register_rest_route( 'eveent/v1', '/comments/(?P<id>\d+)', [
            'methods'             => 'DELETE',
            'callback'            => [ $this, 'handle_delete_comment_request' ],
            'permission_callback' => [ $this, 'check_admin_permission' ], 
            'args'                => [
                'id' => [
                    'required'          => true,
                    'validate_callback' => function( $param ) { return is_numeric( $param ); },
                ],
            ],
        ] );

        register_rest_route( 'eveent/v1', '/comments/(?P<id>\d+)/reply', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'handle_eveent_reply' ],
            'permission_callback' => [ $this, 'check_api_license_permission' ],
        ] );

        register_rest_route( 'custom/v1', '/comments/(?P<post_id>\d+)', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_v1_comments_with_meta' ],
            'permission_callback' => [ $this, 'check_comments_read_permission' ],
        ] );
        
        register_rest_route( 'eveent/v1', '/gift-status/(?P<id>\d+)', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_gift_status' ],
            'permission_callback' => [ $this, 'check_gift_status_permission' ],
        ] );
        
        register_rest_route( 'custom/v1', '/timezone', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_timezone' ],
            'permission_callback' => '__return_true',
        ] );
    }

    private function check_api_rate_limit( $type, $limit = 60, $seconds = 60 ) {
        $ip = $this->get_client_ip(); 
        $key = 'ev_api_limit_' . $type . '_' . md5( $ip );
        $count = get_transient( $key );

        if ( false !== $count && $count >= $limit ) {
            return false;
        }
        
        $count = ( false === $count ) ? 1 : $count + 1;
        set_transient( $key, $count, $seconds );
        return true;
    }

   
    private function check_global_rate_limit( $type, $limit = 120, $seconds = 60 ) {
        $key = 'ev_global_limit_' . $type;
        $count = get_transient( $key );

        if ( false !== $count && $count >= $limit ) {
            return false;
        }

        $count = ( false === $count ) ? 1 : $count + 1;
        set_transient( $key, $count, $seconds );
        return true;
    }

   
    public function check_gift_status_permission( $request ) {
        if ( ! $this->check_api_rate_limit( 'gift_status', 30 ) ) {
            return new WP_Error( 'rate_limit', 'Terlalu banyak permintaan.', [ 'status' => 429 ] );
        }
        return true;
    }

    public function check_comments_read_permission( $request ) {
       
        if ( is_user_logged_in() ) return true;

        $site_host    = parse_url( get_site_url(), PHP_URL_HOST );
        $api_domain   = function_exists( 'eveent_get_api_base_domain' ) ? eveent_get_api_base_domain() : 'eveent.web.id';

       
        $origin = sanitize_text_field( $_SERVER['HTTP_ORIGIN'] ?? '' );
        if ( ! empty( $origin ) ) {
            $origin_host = parse_url( $origin, PHP_URL_HOST );
        
            if (
                $origin_host === $site_host ||
                str_ends_with( $origin_host, '.' . $api_domain ) ||
                $origin_host === $api_domain
            ) {
                return true;
            }
           
            return new WP_Error( 'forbidden', 'Akses ditolak.', [ 'status' => 403 ] );
        }

        
        $referer = sanitize_text_field( $_SERVER['HTTP_REFERER'] ?? '' );
        if ( ! empty( $referer ) ) {
            $referer_host = parse_url( $referer, PHP_URL_HOST );
            if (
                $referer_host === $site_host ||
                str_ends_with( $referer_host, '.' . $api_domain ) ||
                $referer_host === $api_domain
            ) {
                return true;
            }
            return new WP_Error( 'forbidden', 'Akses ditolak.', [ 'status' => 403 ] );
        }

        
        if ( ! $this->check_api_rate_limit( 'comments_no_origin', 15 ) ) {
            return new WP_Error( 'rate_limit', 'Terlalu banyak permintaan.', [ 'status' => 429 ] );
        }
        return true;
    }

  
    public function rate_limit_wp_comments_api( $result, $server, $request ) {
       
        $route = $request->get_route();
        if ( strpos( $route, '/wp/v2/comments' ) !== 0 ) {
            return $result;
        }

      
        if ( is_user_logged_in() ) {
            return $result;
        }

      
        if ( ! $this->check_api_rate_limit( 'wp_comments_api', 20 ) ) {
            return new WP_Error( 'rate_limit', 'Terlalu banyak permintaan.', [ 'status' => 429 ] );
        }

        return $result;
    }

    public function check_admin_permission( $request ) {

        $license_check = $this->check_api_license_permission( $request );
        if ( is_wp_error( $license_check ) ) return $license_check;

        if ( ! current_user_can( 'moderate_comments' ) ) {
             return new WP_Error( 'forbidden', 'Hanya admin yang boleh menghapus.', [ 'status' => 403 ] );
        }
        return true;
    }

    public function get_v1_comments_with_meta( $request ) {
        $post_id = (int) $request['post_id'];
        
        $post = get_post( $post_id );
        if ( ! $post || $post->post_status !== 'publish' ) {
            return new WP_REST_Response( [ 'error' => 'Post not found or private' ], 404 );
        }
        
        if ( post_password_required( $post_id ) ) {
            return new WP_REST_Response( [ 'error' => 'Password required' ], 403 );
        }

        if ( ! $this->check_api_rate_limit( 'read_comments', 30 ) ) {
            return new WP_REST_Response( [ 'error' => 'Too many requests' ], 429 );
        }
        
        $cache_key = 'ev_api_comments_' . $post_id;
        $cached_response = get_transient( $cache_key );

        if ( false !== $cached_response ) {
            return new WP_REST_Response( $cached_response, 200 );
        }

        $args = [
            'post_id' => $post_id,
            'status'  => 'approve',
            'number'  => 100, 
        ];

        $comments = get_comments( $args );
        $formatted_comments = [];

        if ( ! empty( $comments ) ) {
            foreach ( $comments as $comment ) {
                $formatted_comments[] = [
                    'id'            => (string) $comment->comment_ID,
                    'parent'        => (string) $comment->comment_parent,
                    'author_name'   => $comment->comment_author,
                    'date'          => $comment->comment_date,
                    'date_gmt'      => $comment->comment_date_gmt,
                    'content'       => [ 'rendered' => get_comment_meta( $comment->comment_ID, '_is_system_message', true ) ? '' : wpautop( $comment->comment_content ) ],
                    'meta_data'     => [
                        'konfirmasi'        => [ get_comment_meta( $comment->comment_ID, 'konfirmasi', true ) ],
                        'attendance'        => [ get_comment_meta( $comment->comment_ID, 'attendance', true ) ],
                        'guest'             => [ get_comment_meta( $comment->comment_ID, 'guest', true ) ],
                        '_selected_sticker' => [ esc_html( get_comment_meta( $comment->comment_ID, '_selected_sticker', true ) ) ],
                        'group_reference'   => [ get_comment_meta( $comment->comment_ID, 'group_reference', true ) ],
                        'detailed_attendance' => [ get_comment_meta( $comment->comment_ID, 'detailed_attendance', true ) ],
                    ],
                ];
            }
        }

        
        set_transient( $cache_key, $formatted_comments, 5 * MINUTE_IN_SECONDS );
        return new WP_REST_Response( $formatted_comments, 200 );
    }

    public function handle_eveent_reply( $request ) {
        if ( ! $this->check_api_rate_limit( 'post_reply', 10 ) ) {
            return new WP_Error( 'rate_limit', 'Terlalu cepat. Tunggu sebentar.', [ 'status' => 429 ] );
        }

        $parent_id    = (int) $request->get_param( 'id' ); 
        $post_id      = (int) $request->get_param( 'post' );
        $content      = sanitize_textarea_field( $request->get_param( 'content' ) );
        $author_name  = sanitize_text_field( $request->get_param( 'author_name' ) );
        $author_email = sanitize_email( $request->get_param( 'author_email' ) );

        if ( empty( $parent_id ) || empty( $post_id ) || empty( $content ) ) {
            return new WP_Error( 'invalid_data', 'Data tidak lengkap.', [ 'status' => 400 ] );
        }

        $parent_comment = get_comment( $parent_id );
        if ( ! $parent_comment || $parent_comment->comment_post_ID != $post_id ) {
             return new WP_Error( 'invalid_relation', 'Parent comment mismatch.', [ 'status' => 400 ] );
        }

        $comment_data = [
            'comment_post_ID'      => $post_id,
            'comment_content'      => $content,
            'comment_parent'       => $parent_id,
            'comment_author'       => $author_name,
            'comment_author_email' => $author_email,
            'comment_type'         => 'comment',
            'comment_approved'     => 1, 
        ];

        $comment_id = wp_insert_comment( $comment_data );

        if ( is_wp_error( $comment_id ) ) {
            return new WP_Error( 'comment_failed', 'Gagal membalas.', [ 'status' => 500 ] );
        }
        
        delete_transient( 'ev_api_comments_' . $post_id );
        return new WP_REST_Response( [ 'message' => 'Berhasil.', 'comment_id' => $comment_id ], 201 );
    }

    public function get_timezone() {
        return [ 'timezone' => get_option( 'timezone_string' ) ];
    }

    public function refresh_license_status_ajax_handler() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => 'Akses ditolak.' ] );
            return;
        }

       
        $cooldown_key = 'ev_license_refresh_cooldown';
        if ( get_transient( $cooldown_key ) ) {
            wp_send_json_error( [ 'message' => 'Mohon tunggu 15 menit sebelum refresh lagi.' ] );
            return;
        }
        set_transient( $cooldown_key, 1, 15 * MINUTE_IN_SECONDS );

        $transient_key = 'eveent_widget_license_status';
       
        delete_transient( $transient_key );
        
        $this->plugin->is_licensed = $this->check_license();
        wp_send_json_success( [ 'message' => 'Lisensi di-refresh.' ] );
    }
    
    public function check_license() {
        $this->plugin->license_message = 'Aktif.';
        return true;
    }

    public function deactivate_license_ajax_handler() {
        check_ajax_referer( 'ev_deactivate_nonce' );
        delete_option( 'ev_widget_license_key' );
        delete_option( 'ev_widget_license_domain' );
        delete_option( 'ev_widget_license_expires_at' );
        wp_send_json_success( [ 'message' => 'Berhasil dinonaktifkan.' ] );
    }

    public function handle_delete_comment_request( $request ) {
        $comment_id = $request->get_param( 'id' );
        $force_delete = filter_var( $request->get_param( 'force' ), FILTER_VALIDATE_BOOLEAN ); 
        $comment = get_comment( $comment_id );

        if ( ! $comment ) return new WP_Error( 'not_found', 'Tidak ditemukan.', [ 'status' => 404 ] );
        
        $deleted = $force_delete ? wp_delete_comment( $comment_id, true ) : wp_trash_comment( $comment_id );

        if ( $deleted ) {
            delete_transient( 'ev_api_comments_' . $comment->comment_post_ID );
            return new WP_REST_Response( [ 'message' => 'Terhapus.' ], 200 );
        } else {
            return new WP_Error( 'failed', 'Gagal hapus.', [ 'status' => 500 ] );
        }
    }

    public function check_api_license_permission( $request ) {
        return true;
    }

    public function fetch_guest_events_data_from_backend( $barcode_uid ) {
        $barcode_uid_lower = strtolower( $barcode_uid );
        
        // Removed validation prefix to support custom guest names from Bukutamu Digital.
        $is_valid_barcode = true;

        if ( ! $is_valid_barcode ) return [ 'guest_name' => null, 'allowed_events_keys' => [], 'master_events' => [] ];
        
        $cache_key    = 'ev_guest_data_v2_' . md5( $barcode_uid_lower );
        $fail_key     = 'ev_guest_fail_' . md5( $barcode_uid_lower );
     
        $fallback_key = 'ev_guest_data_fallback_' . md5( $barcode_uid_lower );

        $cached_data = get_transient( $cache_key );
        if ( false !== $cached_data ) return $cached_data; 

        if ( get_transient( $fail_key ) ) {
           
            $fallback = get_transient( $fallback_key );
            return $fallback !== false ? $fallback : [ 'guest_name' => null, 'allowed_events_keys' => [], 'master_events' => [] ];
        }

       
        if ( ! $this->check_global_rate_limit( 'guest_details', 120, 60 ) ) {
            $fallback = get_transient( $fallback_key );
            return $fallback !== false ? $fallback : [ 'guest_name' => null, 'allowed_events_keys' => [], 'master_events' => [] ];
        }

        $api_url = eveent_get_api_base_url() . '/api/guest-details/' . rawurlencode( $barcode_uid );
        $response = wp_remote_get( $api_url, [ 'timeout' => 5, 'headers' => [ 'Accept' => 'application/json' ] ] ); 

        $final_data = [ 'guest_name' => null, 'allowed_events_keys' => [], 'master_events' => [] ];

        if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
            $data = json_decode( wp_remote_retrieve_body( $response ), true );
            if ( $data ) {
                if ( isset( $data['allowed_events_keys'] ) ) $final_data = $data;
                elseif ( isset( $data['master_events'] ) ) $final_data['master_events'] = $data['master_events'];
            }
          
            set_transient( $cache_key, $final_data, 5 * MINUTE_IN_SECONDS );
            
            set_transient( $fallback_key, $final_data, 24 * HOUR_IN_SECONDS );
        } else {
           
            set_transient( $fail_key, true, 2 * MINUTE_IN_SECONDS );
            
            $fallback = get_transient( $fallback_key );
            if ( $fallback !== false ) return $fallback;
        }
        
        return $final_data; 
    }
    
    public function filter_validate_guest_id( $default_data, $barcode_uid ) {
        if ( empty( $barcode_uid ) ) return $default_data;
        $guest_data = $this->fetch_guest_events_data_from_backend( $barcode_uid );
        $is_registered = ! empty( $guest_data['allowed_events_keys'] );
        return array_merge( $guest_data, [ 'is_registered' => $is_registered ] );
    }

    public function get_gift_status( $request ) {
        $post_id = (int) $request['id'];

        
        $cache_key = 'ev_gift_status_' . $post_id;
        $cached = get_transient( $cache_key );
        if ( false !== $cached ) {
            return new WP_REST_Response( $cached, 200 );
        }

        $status_gift = get_post_meta( $post_id, 'gift_eveent', true );
        if ( empty($status_gift) ) $status_gift = get_post_meta( $post_id, 'ev_is_gift_confirmation_active', true );
        
        $is_active = ( in_array( strtolower($status_gift), ['yes', 'ya', 'on', '1', 'enable', 'true', 'aktif'] ) ) ? 'yes' : 'no';

        $result = [ 'post_id' => $post_id, 'is_active' => $is_active ];
        set_transient( $cache_key, $result, 5 * MINUTE_IN_SECONDS );

        return new WP_REST_Response( $result, 200 );
    }
}