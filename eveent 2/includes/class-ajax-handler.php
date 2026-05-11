<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class Eveent_Ajax_Handler {

    private $plugin;

    public function __construct( $plugin ) {
        $this->plugin = $plugin;

       
        add_action( 'eveent_process_comment_tasks', [ $this, 'background_process_comment_tasks' ], 10, 1 );
        add_action( 'eveent_process_gift_api', [ $this, 'background_process_gift_api' ], 10, 2 );

       
        $actions = [
            'send_gift_notification' => 'ajax_send_gift_notification',
            'ev_get_comments'        => 'ajax_get_comments',
            'ev_insert_comment'      => 'ajax_insert_comment',
            'ev_rsvp_toggle_like'    => 'ajax_toggle_like',
            'ev_insert_public_reply' => 'ajax_insert_public_reply',
            'ev_lite_guestbook'      => 'ajax_lite_guestbook'
        ];

        foreach ( $actions as $tag => $method ) {
            add_action( "wp_ajax_{$tag}", [ $this, $method ] );
            add_action( "wp_ajax_nopriv_{$tag}", [ $this, $method ] );
        }

        
        add_filter( 'rest_pre_insert_comment', [ $this, 'block_rest_api_comments' ], 10, 2 );
    }

  

    private function get_client_ip() {
        // Prioritize REMOTE_ADDR to prevent IP spoofing via headers
        // Only use forwarded headers if behind a trusted proxy
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        
        // If behind Cloudflare or reverse proxy, check trusted headers
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

    private function check_rate_limit( $action, $limit_count, $seconds ) {
        $ip = $this->get_client_ip();
        $key = 'ev_limit_' . $action . '_' . md5( $ip );
        $count = get_transient( $key );

        if ( false !== $count && $count >= $limit_count ) return false;

        $new_count = ( false === $count ) ? 1 : $count + 1;
        set_transient( $key, $new_count, $seconds );
        return true;
    }

    public function block_rest_api_comments( $prepared_comment, $request ) {
        if ( ! is_user_logged_in() ) {
            return new WP_Error( 'rest_forbidden', __( 'REST API comments are disabled.' ), [ 'status' => 403 ] );
        }
        return $prepared_comment;
    }

    private function get_blocked_contents() {
        $base = [ 
            'Konfirmasi Hadir', 'Konfirmasi Tidak Hadir', 
            'Konfirmasi Ragu-ragu', 'Konfirmasi Kehadiran',
            '(Stiker)'
        ];
        $blocked = [];
        foreach ($base as $b) {
            $blocked[] = $b;
            $blocked[] = "<p>$b</p>";
            $blocked[] = "<p>$b<br /></p>";
            $blocked[] = "<p>$b<br></p>";
            $blocked[] = "$b<br />";
            $blocked[] = "$b<br>";
        }
        $blocked[] = '<p></p>';
        $blocked[] = '<p>&nbsp;</p>';
        $blocked[] = '<br>';
        $blocked[] = '<br />';
        return $blocked;
    }

    public function filter_blocked_comments_sql( $clauses ) {
        global $wpdb;
        $blocked = $this->get_blocked_contents();
        $blocked[] = ''; // Block empty strings as well
        $escaped = array_map( function( $w ) { return "'" . esc_sql( $w ) . "'"; }, $blocked );
        
        $sticker_subquery = "{$wpdb->comments}.comment_ID IN (SELECT comment_id FROM {$wpdb->commentmeta} WHERE meta_key = '_selected_sticker' AND meta_value != '')";
        
        if ( ! empty( $escaped ) ) {
            $in_sql = implode( ',', $escaped );
            // Exclude blocked content AND system messages, but allow through if comment has a sticker
            $clauses['where'] .= " AND (
                (TRIM({$wpdb->comments}.comment_content) NOT IN ($in_sql)
                 AND {$wpdb->comments}.comment_ID NOT IN (SELECT comment_id FROM {$wpdb->commentmeta} WHERE meta_key = '_is_system_message' AND meta_value = '1'))
                OR {$sticker_subquery}
            ) ";
        }
        return $clauses;
    }

    private function get_optimized_query_args( $post_id, $number = 0, $paged = 1 ) {
        $args = [
            'post_id' => $post_id,
            'status'  => 'approve',
            'parent'  => 0,
            'orderby' => 'comment_date',
            'order'   => 'DESC',
        ];

        if ( $number > 0 ) {
            $args['number'] = $number;
            $args['offset'] = ( $paged - 1 ) * $number;
        }
        return $args;
    }

    private function get_visible_comments_count( $post_id ) {
        $args = $this->get_optimized_query_args( $post_id );
        $args['fields'] = 'ids';
        $args['no_found_rows'] = true;
        
        add_filter( 'comments_clauses', [ $this, 'filter_blocked_comments_sql' ] );
        $ids = get_comments( $args );
        remove_filter( 'comments_clauses', [ $this, 'filter_blocked_comments_sql' ] );
        
        return is_array($ids) ? count($ids) : 0;
    }

    

    public function ajax_get_comments() {
        if ( ! $this->check_rate_limit( 'read', 60, 60 ) ) {
            wp_send_json_error( 'Terlalu banyak permintaan.' );
        }

        // Soft nonce check for read-only operation — don't die() with non-JSON response
        // This prevents "Koneksi Error" on cached pages where nonce has expired
        // Rate limiting above already protects against abuse
        $nonce_valid = isset( $_POST['nonce'] ) && wp_verify_nonce( $_POST['nonce'], 'ewf_rsvp' );
        if ( ! $nonce_valid ) {
            // Still allow read — nonce might be stale from page cache
            // but log for monitoring
            error_log( '[Eveent] Stale nonce on ev_get_comments — likely cached page. IP: ' . $this->get_client_ip() );
        }

        $post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
        if ( ! $post_id ) wp_send_json_error( 'Post ID invalid.' );

        $paged  = isset( $_POST['paged'] ) ? max( 1, intval( $_POST['paged'] ) ) : 1;
        $number = isset( $_POST['number'] ) ? intval( $_POST['number'] ) : 10;
        
        
        $total = $this->get_visible_comments_count( $post_id );
        $max_pages = max( 1, ceil( $total / $number ) );

       
        $args = $this->get_optimized_query_args( $post_id, $number, $paged );
        add_filter( 'comments_clauses', [ $this, 'filter_blocked_comments_sql' ] );
        $comments = get_comments( $args );
        remove_filter( 'comments_clauses', [ $this, 'filter_blocked_comments_sql' ] );

      
        $parent_ids = wp_list_pluck( $comments, 'comment_ID' );
        $replies_grouped = $this->fetch_replies_batch( $parent_ids );

       
        $settings = $this->get_rsvp_widget_settings( $post_id );
        $badges = [
            'present'    => sanitize_text_field( $_POST['badge_present_text'] ?? '' ),
            'notpresent' => sanitize_text_field( $_POST['badge_notpresent_text'] ?? '' ),
            'notsure'    => sanitize_text_field( $_POST['badge_notsure_text'] ?? '' ),
        ];

        $output = [];
        foreach ( $comments as $c ) {
            $cid = $c->comment_ID;
          
            $comment_replies = isset( $replies_grouped[ $cid ] ) ? $this->format_replies( $replies_grouped[ $cid ], $settings ) : [];

            $output[] = [
                'comment_id'      => $cid,
                'author'          => esc_html( get_comment_author( $c ) ),
                'group_reference' => get_comment_meta( $cid, 'group_reference', true ),
                'avatar'          => esc_url( 'https://ui-avatars.com/api/?background=random&name=' . rawurlencode( get_comment_author( $c ) ) ),
                'time_ago'        => $this->format_custom_comment_time( $cid, $settings ),
                'content'         => $this->clean_display_content( get_comment_text( $c ) ),
                'sticker_data'    => $this->get_sticker_data( $cid ),
                'sticker_html'    => $this->get_sticker_html( $cid ),
                'attendance_tag'  => $this->get_attendance_tag( get_comment_meta( $cid, 'attendance', true ), $settings, $badges ),
                'like_count'      => intval( get_comment_meta( $cid, '_comment_likes', true ) ),
                'replies'         => $comment_replies
            ];
        }

        wp_send_json_success([ 
            'comments' => $output, 
            'meta' => [ 'current_page' => $paged, 'max_pages' => $max_pages, 'total' => $total ] 
        ]);
    }

    private function fetch_replies_batch( $parent_ids ) {
        if ( empty( $parent_ids ) ) return [];

        $replies = get_comments([
            'parent__in' => $parent_ids,
            'status'     => 'approve',
            'orderby'    => 'comment_date',
            'order'      => 'ASC',
            'no_found_rows' => true 
        ]);

        $grouped = [];
        foreach ( $replies as $r ) {
            $grouped[ $r->comment_parent ][] = $r;
        }
        return $grouped;
    }

    private function get_sticker_raw( $comment_id ) {
        $sticker = get_comment_meta( $comment_id, '_selected_sticker', true );
        if ( empty( $sticker ) ) {
            $sticker = get_comment_meta( $comment_id, 'sticker', true );
        }
        return $sticker;
    }

    private function sanitize_sticker_input( $raw ) {
        if ( empty( $raw ) || ! is_string( $raw ) ) return '';
        // Limit size to prevent DB bloat (max 200KB for SVG stickers)
        if ( strlen( $raw ) > 204800 ) return '';
        $data = json_decode( $raw, true );
        if ( is_array( $data ) ) {
            // Format JS frontend: {"type":"svg|icon|image","value":"..."}
            if ( isset( $data['type'] ) && isset( $data['value'] ) && is_string( $data['value'] ) ) {
                $type = sanitize_key( $data['type'] );
                if ( in_array( $type, ['svg', 'image'], true ) ) {
                    return wp_json_encode([ 'type' => $type, 'value' => esc_url_raw( $data['value'] ) ]);
                } elseif ( $type === 'icon' ) {
                    return wp_json_encode([ 'type' => $type, 'value' => sanitize_text_field( $data['value'] ) ]);
                }
                return '';
            }
            // Format Elementor: {"library":"svg","value":{"url":"..."}}
            if ( isset( $data['library'] ) && isset( $data['value'] ) ) {
                $lib = sanitize_key( $data['library'] );
                if ( $lib === 'svg' && is_array( $data['value'] ) && isset( $data['value']['url'] ) ) {
                    return wp_json_encode([ 'library' => $lib, 'value' => [ 'url' => esc_url_raw( $data['value']['url'] ) ] ]);
                } elseif ( is_string( $data['value'] ) ) {
                    return wp_json_encode([ 'library' => $lib, 'value' => sanitize_text_field( $data['value'] ) ]);
                }
                return '';
            }
        }
        // Reject raw HTML and other unknown formats for new submissions
        return '';
    }

    private function get_allowed_sticker_kses() {
        return [
            'img'  => [ 'src' => [], 'alt' => [], 'class' => [], 'style' => [], 'width' => [], 'height' => [] ],
            'i'    => [ 'class' => [], 'style' => [] ],
            'svg'  => [ 'viewBox' => [], 'fill' => [], 'xmlns' => [], 'width' => [], 'height' => [], 'class' => [], 'style' => [] ],
            'path' => [ 'd' => [], 'fill' => [], 'stroke' => [] ],
            'use'  => [ 'href' => [], 'xlink:href' => [] ],
        ];
    }

    private function get_sticker_data( $comment_id ) {
        $raw = $this->get_sticker_raw( $comment_id );
        if ( empty( $raw ) ) return null;
        $decoded = json_decode( $raw, true );
        return is_array( $decoded ) ? $decoded : null;
    }

    private function get_sticker_html( $comment_id ) {
        return $this->render_frontend_sticker_html( $this->get_sticker_raw( $comment_id ) );
    }

    private function clean_display_content( $text ) {
        $blocked = array_merge( $this->get_blocked_contents(), ['(Stiker)'] );
        $cleaned = str_replace( $blocked, '', $text );
        $cleaned = trim( $cleaned );
        return ! empty( $cleaned ) ? wpautop( $cleaned ) : '';
    }

    private function render_frontend_sticker_html( $sticker_json ) {
        if ( empty( $sticker_json ) ) return '';
        $sticker_html = '';
        $s_data = json_decode( $sticker_json, true );
        if ( is_array( $s_data ) ) {
            // Format A: {"type":"svg","value":"url"} or {"type":"icon","value":"class"} (from JS frontend)
            if ( isset( $s_data['type'] ) && isset( $s_data['value'] ) && is_string( $s_data['value'] ) ) {
                if ( $s_data['type'] === 'svg' || $s_data['type'] === 'image' ) {
                    $sticker_html = '<img src="' . esc_url( $s_data['value'] ) . '" class="ev-rsvp-sticker-image" alt="Sticker">';
                } else {
                    $sticker_html = '<i class="' . esc_attr( $s_data['value'] ) . '"></i>';
                }
            }
            // Format B: {"library":"svg","value":{"url":"..."}} (from Elementor icon control)
            elseif ( isset( $s_data['library'] ) && isset( $s_data['value'] ) ) {
                $val = $s_data['value'];
                if ( $s_data['library'] === 'svg' && is_array($val) && isset($val['url']) ) {
                    $sticker_html = '<img src="' . esc_url( $val['url'] ) . '" class="ev-rsvp-sticker-image" alt="Sticker">';
                } elseif ( is_string($val) ) {
                    $sticker_html = '<i class="' . esc_attr( $val ) . '"></i>';
                }
            }
            // Format C: {"value":"..."} only, no type or library
            elseif ( isset( $s_data['value'] ) && is_string( $s_data['value'] ) ) {
                $val = $s_data['value'];
                if ( preg_match( '/^https?:\/\//', $val ) || strpos( $val, '/' ) === 0 ) {
                    $sticker_html = '<img src="' . esc_url( $val ) . '" class="ev-rsvp-sticker-image" alt="Sticker">';
                } else {
                    $sticker_html = '<i class="' . esc_attr( $val ) . '"></i>';
                }
            }
        } elseif ( is_string( $sticker_json ) ) {
            // Format D: legacy raw HTML — sanitize with wp_kses
            if ( strpos( $sticker_json, '<img' ) !== false || strpos( $sticker_json, '<i' ) !== false || strpos( $sticker_json, '<svg' ) !== false ) {
                $sticker_html = wp_kses( $sticker_json, $this->get_allowed_sticker_kses() );
            }
            // Format E: plain URL string
            elseif ( preg_match( '/^https?:\/\//', $sticker_json ) ) {
                $sticker_html = '<img src="' . esc_url( $sticker_json ) . '" class="ev-rsvp-sticker-image" alt="Sticker">';
            }
        }
        if ( ! empty( $sticker_html ) ) {
            return '<div class="ev-rsvp-comment-sticker">' . $sticker_html . '</div>';
        }
        return '';
    }

    private function format_replies( $replies, $settings ) {
        $formatted = [];
        foreach ( $replies as $r ) {
            $formatted[] = [
                'comment_id' => $r->comment_ID,
                'author'     => esc_html( get_comment_author( $r ) ),
                'avatar'     => esc_url( 'https://ui-avatars.com/api/?background=random&name=' . rawurlencode( get_comment_author( $r ) ) ),
                'time_ago'   => $this->format_custom_comment_time( $r->comment_ID, $settings ),
                'content'    => wpautop( get_comment_text( $r ) ),
            ];
        }
        return $formatted;
    }

    public function ajax_insert_comment() {
        if ( ! empty( $_POST['ev_phone_trap'] ) ) wp_send_json_success([ 'message' => 'Terkirim.' ]);

        if ( ! $this->check_rate_limit( 'write', 1, 8 ) ) {
            wp_send_json_error([ 'message' => 'Mohon coba lagi.' ]);
        }

        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'ewf_rsvp' ) ) wp_send_json_error([ 'message' => 'Sesi telah kedaluwarsa. Silakan muat ulang (refresh) halaman terlebih dahulu.' ]);
        if ( empty( $_POST['commentpress'] ) ) wp_send_json_error([ 'message' => 'Request invalid.' ]);

        $post_id    = intval( $_POST['comment_post_ID'] ?? 0 );
        $author     = mb_substr( sanitize_text_field( trim( $_POST['author'] ?? '' ) ), 0, 100 );
        $content    = mb_substr( wp_kses_post( trim( $_POST['comment'] ?? '' ) ), 0, 2000 );
        $group_name = sanitize_text_field( $_POST['group_reference'] ?? '' );
        $attendance = sanitize_key( $_POST['attendance'] ?? '' );
        $guest_count = ( $attendance === 'present' ) ? intval( $_POST['guest'] ?? 1 ) : 0;
        $sticker_json = $this->sanitize_sticker_input( wp_unslash( $_POST['selected_sticker'] ?? '' ) );
        $guest_id   = isset( $_POST['guest_id'] ) ? trim( $_POST['guest_id'] ) : '';
        $is_req     = isset( $_POST['is_guest_name_required'] ) && $_POST['is_guest_name_required'] === 'yes';
        
        if ( ! $post_id || ! comments_open( $post_id ) ) wp_send_json_error([ 'message' => 'Komentar ditutup.' ]);
        
        
        $is_system = false;
        if ( empty( $content ) && ! empty( $attendance ) ) {
            if ( empty( $sticker_json ) ) {
                // Pure attendance without text or sticker => system message (hidden)
                $map = [ 'present' => 'Konfirmasi Hadir', 'notpresent' => 'Konfirmasi Tidak Hadir', 'notsure' => 'Konfirmasi Ragu-ragu' ];
                $content = ($map[ $attendance ] ?? 'Konfirmasi Kehadiran') . ' - ' . uniqid();
                $is_system = true;
            }
        }
        if ( ! empty( $sticker_json ) && empty( $content ) ) $content = '(Stiker)';
        
        if ( empty( $content ) || empty( $author ) ) wp_send_json_error([ 'message' => 'Data tidak lengkap.' ]);

        
        // Grace mode: jika server validate-guest down (is_wp_error/timeout), izinkan RSVP masuk
        // tapi tandai dengan meta _pending_validation agar bisa dicek ulang saat server pulih.
        // Jika server merespons dengan 404/4xx → tamu memang tidak ada, tetap ditolak.
        $pending_validation = false;
        if ( $is_req && ! empty( $guest_id ) ) {
            if ( ! preg_match( '/^[a-zA-Z0-9_-]+$/', $guest_id ) ) {
                wp_send_json_error([ 'message' => 'ID Tamu tidak valid.' ]);
            }
            
            $cache_key = 'ev_guest_' . md5( $guest_id );
            if ( false === get_transient( $cache_key ) ) {
                $api = eveent_get_api_base_url() . '/api/validate-guest/' . $guest_id;
                $res = wp_remote_get( $api, [ 'timeout' => 3 ] );
                if ( is_wp_error( $res ) ) {
                    // Koneksi gagal / timeout — server mungkin sementara down
                    // Izinkan masuk ke penyimpanan sementara, tandai untuk validasi ulang
                    $pending_validation = true;
                } elseif ( 200 !== wp_remote_retrieve_response_code( $res ) ) {
                    // Server merespons tapi tamu tidak ditemukan (404 dll)
                    wp_send_json_error([ 'message' => 'Tamu tidak terdaftar.' ]);
                    return;
                } else {
                    set_transient( $cache_key, 'valid', HOUR_IN_SECONDS );
                }
            }
        }

        $generated_email = 'guest@' . preg_replace( '#^www\.#', '', strtolower( parse_url( get_site_url(), PHP_URL_HOST ) ) );
        $settings = $this->get_rsvp_widget_settings( $post_id );
        
        
        $recent = get_comments([ 
            'post_id' => $post_id, 'author_email' => $generated_email, 
            'number' => 1, 'orderby' => 'comment_ID', 'order' => 'DESC', 
            'date_query' => [[ 'after' => '1 hour ago' ]] 
        ]);
        
        if ( ! empty( $recent ) && strtolower( trim( $recent[0]->comment_author ) ) === strtolower( $author ) ) {
            $prev = $recent[0];
            $prev_sys = get_comment_meta( $prev->comment_ID, '_is_system_message', true ) === '1';

            // We update the comment if:
            // 1. It was a system message and now it's text (add text)
            // 2. It was text and now it's system message (just update attendance, keep text)
            // 3. Both are system messages (just update attendance)
            // 4. Both are text messages (this is a duplicate submit, we should update the text or just return success)
            
            // To simplify, if it's the SAME user within 1 hour, we ALWAYS update the existing comment instead of creating spam.
            if ( true ) {
                $args_update = [ 'comment_ID' => $prev->comment_ID ];
                
                if ( ! $is_system ) {
                    $args_update['comment_content'] = $content;
                    delete_comment_meta( $prev->comment_ID, '_is_system_message' );
                    if ( ! empty( $sticker_json ) ) update_comment_meta( $prev->comment_ID, '_selected_sticker', $sticker_json );
                }
                
                // ALWAYS update attendance if provided
                if ( ! empty( $attendance ) ) {
                    update_comment_meta( $prev->comment_ID, 'attendance', $attendance );
                    update_comment_meta( $prev->comment_ID, 'guest', $guest_count );
                }
                
                if ( isset( $_POST['detailed_attendance'] ) ) {
                    $details = array_map( 'sanitize_text_field', $_POST['detailed_attendance'] );
                    update_comment_meta( $prev->comment_ID, 'detailed_attendance', implode( ', ', $details ) );
                }
                
                $_POST['comment'] = $prev->comment_content; 
                $_POST['is_system_message'] = $is_system && $prev_sys;
                $this->send_whatsapp_notification( $post_id, $_POST, $guest_count, $group_name );
                
                if ( isset( $args_update['comment_content'] ) ) wp_update_comment( $args_update );
                clean_comment_cache( $prev->comment_ID );
                // Tandai jika validasi tamu ditunda karena server down saat submit
                if ( $pending_validation ) {
                    update_comment_meta( $prev->comment_ID, '_pending_validation', '1' );
                } else {
                    delete_comment_meta( $prev->comment_ID, '_pending_validation' );
                }

                
                $badges = [
                    'present'    => sanitize_text_field( $_POST['badge_present_text'] ?? '' ),
                    'notpresent' => sanitize_text_field( $_POST['badge_notpresent_text'] ?? '' ),
                    'notsure'    => sanitize_text_field( $_POST['badge_notsure_text'] ?? '' ),
                ];

                wp_send_json_success([
                    'comment_id' => $prev->comment_ID,
                    'author' => esc_html( $author ),
                    'avatar' => esc_url( 'https://ui-avatars.com/api/?background=random&name=' . rawurlencode( $author ) ),
                    'time_ago' => $this->format_custom_comment_time( $prev->comment_ID, $settings ),
                    'content' => wpautop( $is_system ? $prev->comment_content : $content ),
                    'sticker_data' => $this->get_sticker_data( $prev->comment_ID ),
                    'sticker_html' => $this->get_sticker_html( $prev->comment_ID ),
                    'attendance_tag' => $this->get_attendance_tag( get_comment_meta( $prev->comment_ID, 'attendance', true ), $settings, $badges ),
                    'like_count' => intval( get_comment_meta( $prev->comment_ID, '_comment_likes', true ) ),
                    'replies' => [], 
                    'total_count' => $this->get_visible_comments_count( $post_id )
                ]);
                return;
            }
        }

        
        $user_ip = $this->get_client_ip();
        $user_agent = substr( $_SERVER['HTTP_USER_AGENT'] ?? '', 0, 254 );
        
        if ( wp_blacklist_check( $author, '', '', $content, $user_ip, $user_agent ) ) {
            wp_send_json_error([ 'message' => 'Komentar ditolak.' ]);
        }

        $data = [
            'comment_post_ID' => $post_id,
            'comment_author' => $author,
            'comment_author_email' => $generated_email,
            'comment_content' => $content,
            'user_id' => get_current_user_id(),
            'comment_approved' => 1,
            'comment_author_IP' => $user_ip,
            'comment_agent' => $user_agent
        ];
        
        $cid = wp_insert_comment( $data );
        if ( is_wp_error( $cid ) ) wp_send_json_error([ 'message' => 'Gagal simpan.' ]);

        if ( ! empty( $group_name ) ) update_comment_meta( $cid, 'group_reference', $group_name );
        if ( ! empty( $sticker_json ) ) update_comment_meta( $cid, '_selected_sticker', $sticker_json );
        
        if ( ! empty( $attendance ) ) {
            update_comment_meta( $cid, 'attendance', $attendance );
            update_comment_meta( $cid, 'guest', $guest_count );
        }
        if ( $is_system ) update_comment_meta( $cid, '_is_system_message', '1' );
        // Tandai jika validasi tamu ditunda karena server down saat submit
        if ( $pending_validation ) update_comment_meta( $cid, '_pending_validation', '1' );
        
        if ( isset( $_POST['detailed_attendance'] ) ) {
            $details = array_map( 'sanitize_text_field', $_POST['detailed_attendance'] );
            update_comment_meta( $cid, 'detailed_attendance', implode( ', ', $details ) );
        }

        $_POST['is_system_message'] = $is_system;
        $this->send_whatsapp_notification( $post_id, $_POST, $guest_count, $group_name );

        // Guest already validated synchronously above (line ~381), skip redundant cron validation
        // Previously scheduled eveent_process_comment_tasks for background re-validation, but this was a double API call
        
        $badges = [
            'present'    => sanitize_text_field( $_POST['badge_present_text'] ?? '' ),
            'notpresent' => sanitize_text_field( $_POST['badge_notpresent_text'] ?? '' ),
            'notsure'    => sanitize_text_field( $_POST['badge_notsure_text'] ?? '' ),
        ];

        wp_send_json_success([
            'comment_id' => $cid,
            'author' => esc_html( $author ),
            'avatar' => esc_url( 'https://ui-avatars.com/api/?background=random&name=' . rawurlencode( $author ) ),
            'time_ago' => $this->format_custom_comment_time( $cid, $settings ),
            'content' => ( $content === '(Stiker)' || $is_system ) ? '' : wpautop( $content ),
            'sticker_data' => json_decode( $sticker_json, true ),
            'sticker_html' => $this->render_frontend_sticker_html( $sticker_json ),
            'attendance_tag' => $this->get_attendance_tag( $attendance, $settings, $badges ),
            'like_count' => 0,
            'replies' => [],
            'total_count' => $this->get_visible_comments_count( $post_id )
        ]);
    }

    public function ajax_send_gift_notification() {
        if ( ! $this->check_rate_limit( 'gift', 3, 60 ) ) wp_send_json_error([ 'message' => 'Terlalu sering request.' ]);

        $nonce_key = '';
        foreach ( $_POST as $k => $v ) {
            if ( strpos( $k, 'ewf_gift_nonce' ) === 0 ) { $nonce_key = $k; break; }
        }
        if ( empty( $nonce_key ) || ! wp_verify_nonce( $_POST[ $nonce_key ], 'ewf_send_gift_notification' ) ) {
            wp_send_json_error([ 'message' => 'Security check failed.' ]);
        }
        if ( ! empty( $_POST['user_nickname'] ) ) wp_send_json_success([ 'message' => 'Success.' ]);

        $post_id    = intval( $_POST['post_id'] ?? 0 );
        $guest_name = sanitize_text_field( $_POST['guest_name'] ?? '' );
        $amount_raw = sanitize_text_field( $_POST['amount'] ?? '' );
        $bank_name  = sanitize_text_field( $_POST['bank_name'] ?? '' );
        $amount     = (int) preg_replace( '/[^0-9]/', '', $amount_raw );

        if ( $post_id <= 0 || empty( $guest_name ) || empty( $bank_name ) || $amount <= 0 ) {
            wp_send_json_error([ 'message' => 'Data tidak valid.' ]);
        }
        if ( $amount > 100000000 ) wp_send_json_error([ 'message' => 'Nominal terlalu besar.' ]);

        $file_path = null;
        if ( isset( $_FILES['proof_of_transfer'] ) && $_FILES['proof_of_transfer']['error'] === UPLOAD_ERR_OK ) {
            if ( $_FILES['proof_of_transfer']['size'] > 3 * 1024 * 1024 ) wp_send_json_error([ 'message' => 'Ukuran foto maksimal 3MB. Silakan kompres foto Anda terlebih dahulu sebelum dikirim.' ]);
            if ( ! function_exists( 'wp_handle_upload' ) ) require_once( ABSPATH . 'wp-admin/includes/file.php' );
            $upload = wp_handle_upload( $_FILES['proof_of_transfer'], [ 'test_form' => false, 'mimes' => [ 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'webp' => 'image/webp' ] ] );
            if ( isset( $upload['error'] ) ) wp_send_json_error([ 'message' => $upload['error'] ]);
            $file_path = $upload['file'];
        }

        $client_num = $this->resolve_meta_field( $post_id, $_POST['client_number'] ?? '' );
        $template   = wp_kses_post( wp_unslash( $_POST['template'] ?? '' ) );
        
        if ( ! empty( $client_num ) && ! empty( $template ) ) {
            $msg = str_replace(
                [ '[post_title]', '[client_name]', '[guest_name]', '[amount]', '[account_name]', '[bank_name]' ],
                [ get_the_title( $post_id ), $this->resolve_meta_field( $post_id, $_POST['client_name'] ?? '' ), $guest_name, 'Rp ' . number_format( $amount, 0, ',', '.' ), sanitize_text_field( $_POST['account_name'] ?? '' ), $bank_name ],
                $template
            );
            if ( class_exists( 'Eveent_WhatsApp_Sender' ) ) Eveent_WhatsApp_Sender::send( $client_num, html_entity_decode( $msg ) );
        }

        $api_enabled = ( $_POST['enable_digital_gift_api'] ?? 'no' ) === 'yes' || strtolower( get_post_meta( $post_id, 'gift_eveent', true ) ) === 'ya';
        if ( $api_enabled ) {
            $data = [ 
                'wordpress_post_id' => $post_id, 
                'guest_name' => $guest_name, 
                'amount' => $amount_raw, 
                'bank_name' => $bank_name, 
                'account_name' => $_POST['account_name'] ?? '' 
            ];
            wp_schedule_single_event( time() + 5, 'eveent_process_gift_api', [ $data, $file_path ] );
        } else if ( $file_path && file_exists( $file_path ) ) {
            unlink( $file_path );
        }

        wp_send_json_success([ 'message' => 'Konfirmasi diterima.' ]);
    }

    private function resolve_meta_field( $pid, $key ) {
        return ( strpos( trim( $key ), '_' ) === 0 ) ? get_post_meta( $pid, trim( $key ), true ) : $key;
    }

    
    public function background_process_gift_api( $data, $path = null ) {
        $lock_key = 'ev_lock_gift_' . md5( serialize( $data ) );
        if ( get_transient( $lock_key ) ) return; 
        set_transient( $lock_key, 1, 60 ); 

        $boundary = wp_generate_password( 24, false );
        $body = '';
        foreach ( $data as $k => $v ) $body .= "--$boundary\r\nContent-Disposition: form-data; name=\"$k\"\r\n\r\n$v\r\n";
        
        if ( $path && file_exists( $path ) ) {
            $mime = mime_content_type( $path ) ?: 'application/octet-stream';
            $body .= "--$boundary\r\nContent-Disposition: form-data; name=\"proof_of_transfer\"; filename=\"" . basename( $path ) . "\"\r\nContent-Type: $mime\r\n\r\n" . file_get_contents( $path ) . "\r\n";
        }
        $body .= "--$boundary--\r\n";

        wp_remote_post( eveent_get_api_base_url() . '/api/gifts', [ 
            'method' => 'POST', 
            'headers' => [ 'Content-Type' => 'multipart/form-data; boundary=' . $boundary ], 
            'body' => $body, 
            'timeout' => 15,
            'blocking' => true 
        ]);
        
        if ( $path && file_exists( $path ) ) {
            unlink( $path );
        }
    }

    private function send_whatsapp_notification( $post_id, $data, $guest_count, $group_name = '' ) {
        $enabled = ( $data['enable_wa_notice'] ?? 'no' ) === 'yes' ? 'yes' : get_option( 'ev_wa_default_enable', 'no' );
        if ( $enabled !== 'yes' ) return;

        $settings = $this->get_rsvp_widget_settings( $post_id );
        $wa_name = $this->resolve_meta_field( $post_id, $data['wa_notice_name'] ?? ( $settings['wa_notice_name'] ?? get_option( 'ev_wa_default_name' ) ) );
        $wa_num  = $this->resolve_meta_field( $post_id, $data['wa_notice_number'] ?? ( $settings['wa_notice_number'] ?? get_option( 'ev_wa_default_number' ) ) );
        
        if ( empty( $wa_num ) ) return;

        $template = $data['wa_template'] ?? ( $settings['wa_template'] ?? get_option( 'ev_wa_default_template' ) );
        $status = [ 'present' => 'Hadir', 'notpresent' => 'Tidak Hadir', 'notsure' => 'Ragu-ragu' ][ $data['attendance'] ?? '' ] ?? 'Tidak Mengisi';
        
        if ( isset( $data['attendance'] ) && $data['attendance'] === 'present' && ! empty( $data['detailed_attendance'] ) ) {
            $status .= ' (' . implode( ', ', array_map( 'sanitize_text_field', $data['detailed_attendance'] ) ) . ')';
        }

        $msg = ( $data['is_system_message'] ?? false ) ? '-' : ( sanitize_textarea_field( trim( $data['comment'] ?? '' ) ) ?: '_Memberikan Stiker_' );
        
        $final = str_replace(
            [ '[post_title]', '[client_name]', '[guest_name]', '[group_name]', '[attendance_status]', '[guest_count]', '[guest_message]' ],
            [ html_entity_decode( get_the_title( $post_id ) ), $wa_name, $data['author'], $group_name, $status, $guest_count, $msg ],
            $template
        );
        
        $final = empty( $group_name ) ? preg_replace( '/\{group_block\}.*?\{\/group_block\}\s*\R?/', '', $final ) : str_replace( [ '{group_block}', '{/group_block}' ], '', $final );
        
        if ( class_exists( 'Eveent_WhatsApp_Sender' ) ) Eveent_WhatsApp_Sender::send( $wa_num, $final );
    }

    
    public function background_process_comment_tasks( $args ) {
        $gid = $args['guest_id'] ?? '';
        $cid = $args['comment_id'] ?? 0;
        if ( empty( $gid ) || empty( $cid ) ) return;

        $lock_key = 'ev_lock_ct_' . $cid . '_' . md5( $gid );
        if ( get_transient( $lock_key ) ) return;
        set_transient( $lock_key, 1, 60 );

        // Cek cache dulu sebelum panggil API (reuse dari ajax_insert_comment)
        $cache_key = 'ev_guest_' . md5( $gid );
        if ( get_transient( $cache_key ) === 'valid' ) return; // Sudah tervalidasi, skip API call

        $res      = wp_remote_get( eveent_get_api_base_url() . '/api/validate-guest/' . $gid, [ 'timeout' => 10 ] );
        $res_code = is_wp_error( $res ) ? 0 : wp_remote_retrieve_response_code( $res );

        if ( is_wp_error( $res ) || $res_code >= 500 ) {
            // Koneksi gagal atau server error (5xx) — skip, jangan unapprove
            // Tamu mungkin valid, tapi server sementara tidak bisa dihubungi
            return;
        }

        if ( $res_code === 404 ) {
            // Tamu benar-benar tidak ditemukan di server → unapprove comment
            wp_update_comment([ 'comment_ID' => $cid, 'comment_approved' => 0 ]);
        } elseif ( $res_code === 200 ) {
            // Tamu valid — simpan ke cache agar tidak perlu re-validasi
            set_transient( $cache_key, 'valid', HOUR_IN_SECONDS );
        }
        // Kode lain (401, 403, dll) — skip, tidak ada aksi
    }

    public function ajax_toggle_like() {
        if ( ! $this->check_rate_limit( 'like', 20, 60 ) ) wp_send_json_error([ 'message' => 'Too fast' ]);
        
        check_ajax_referer( 'ewf_rsvp', 'nonce' );
        $cid = intval( $_POST['comment_id'] ?? 0 );
        if ( ! $cid ) wp_send_json_error();

        
        $comment = get_comment($cid);
        if ( ! $comment ) wp_send_json_error();

        // Server-side like tracking per IP to prevent bot manipulation
        $ip = $this->get_client_ip();
        $like_key = '_ev_liked_ips_' . $cid;
        $liked_ips = get_comment_meta( $cid, $like_key, true );
        if ( ! is_array( $liked_ips ) ) $liked_ips = [];
        
        $ip_hash = md5( $ip );
        $is_liked = in_array( $ip_hash, $liked_ips, true );
        
        $likes = intval( get_comment_meta( $cid, '_comment_likes', true ) );
        
        if ( $is_liked ) {
            // Unlike: remove IP hash
            $liked_ips = array_values( array_diff( $liked_ips, [ $ip_hash ] ) );
            $new = max( 0, $likes - 1 );
        } else {
            // Like: add IP hash (cap at 10000 to prevent bloat)
            if ( $likes >= 10000 ) wp_send_json_error([ 'message' => 'Max likes reached' ]);
            $liked_ips[] = $ip_hash;
            // Keep only last 500 IP hashes to prevent meta bloat
            if ( count( $liked_ips ) > 500 ) $liked_ips = array_slice( $liked_ips, -500 );
            $new = $likes + 1;
        }
        
        update_comment_meta( $cid, '_comment_likes', $new );
        update_comment_meta( $cid, $like_key, $liked_ips );
        wp_send_json_success([ 'new_count' => $new, 'liked' => ! $is_liked ]);
    }

    public function ajax_insert_public_reply() {
        if ( ! $this->check_rate_limit( 'reply', 5, 60 ) ) wp_send_json_error([ 'message' => 'Too fast' ]);

        check_ajax_referer( 'ewf_rsvp', 'nonce' );
        $pid = intval( $_POST['post_id'] ?? 0 );
        $parent = intval( $_POST['parent_id'] ?? 0 );
        $pass = $_POST['password'] ?? '';
        
        if ( ! $pid || ! $parent || empty( $pass ) ) wp_send_json_error([ 'message' => 'Data invalid.' ]);
        
        
        $real_pass = get_option( 'ev_rsvp_password' );
        // Support both legacy plaintext and new hashed password
        $is_valid_pass = false;
        if ( ! empty( $real_pass ) ) {
            if ( str_starts_with( $real_pass, '$P$' ) || str_starts_with( $real_pass, '$2y$' ) ) {
                // Already hashed — use wp_check_password
                $is_valid_pass = wp_check_password( $pass, $real_pass );
            } else {
                // Legacy plaintext — compare directly, then auto-upgrade to hash
                $is_valid_pass = ( $pass === $real_pass );
                if ( $is_valid_pass ) {
                    // Upgrade to hashed on first successful plaintext login
                    update_option( 'ev_rsvp_password', wp_hash_password( $real_pass ) );
                }
            }
        }
        if ( empty( $real_pass ) || ! $is_valid_pass ) {
            wp_send_json_error([ 'message' => 'Password salah atau belum diset admin.' ]);
        }

        $data = [
            'comment_post_ID' => $pid,
            'comment_author' => mb_substr( sanitize_text_field( $_POST['author_name'] ?? '' ), 0, 100 ),
            'comment_author_email' => 'reply@' . parse_url( get_site_url(), PHP_URL_HOST ),
            'comment_content' => mb_substr( wp_kses_post( $_POST['reply_content'] ?? '' ), 0, 2000 ),
            'comment_parent' => $parent,
            'comment_author_IP' => $this->get_client_ip()
        ];
        
        $cid = wp_insert_comment( $data );
        if ( is_wp_error( $cid ) ) wp_send_json_error([ 'message' => 'Gagal.' ]);

        $c = get_comment( $cid );
        wp_send_json_success([
            'author' => get_comment_author( $c ),
            'avatar' => get_avatar_url( $c->comment_author_email ),
            'content' => wpautop( get_comment_text( $c ) ),
            'time_ago' => $this->format_custom_comment_time( $cid, $this->get_rsvp_widget_settings( $pid ) )
        ]);
    }

    private function get_rsvp_widget_settings( $post_id ) {
        $cache_key = 'ev_widget_settings_' . $post_id;
        $settings = wp_cache_get( $cache_key );
        
        if ( false === $settings ) {
            $settings = [];
            $data = json_decode( get_post_meta( $post_id, '_elementor_data', true ), true );
            if ( is_array( $data ) ) $this->find_widget_recursive( $data, $settings );
            wp_cache_set( $cache_key, $settings, '', 300 );
        }
        
        return $settings;
    }

    private function find_widget_recursive( $elements, &$settings ) {
        foreach ( $elements as $el ) {
            if ( isset( $el['widgetType'] ) && in_array( $el['widgetType'], [ 'ev-rsvp', 'ev-comment' ] ) ) {
                $settings = $el['settings'] ?? []; return;
            }
            if ( ! empty( $el['elements'] ) ) {
                $this->find_widget_recursive( $el['elements'], $settings );
                if ( ! empty( $settings ) ) return;
            }
        }
    }

    public function render_single_comment_html( $c, $args, $depth ) { } 

    private function get_attendance_tag( $status, $settings, $badges ) {
        $map = [
            'present' => [ 'text' => $badges['present'] ?: ( $settings['text_btn_present'] ?? 'Hadir' ), 'icon' => '<path d="M20 6L9 17l-5-5"/>' ],
            'notpresent' => [ 'text' => $badges['notpresent'] ?: ( $settings['text_btn_notpresent'] ?? 'Tidak Hadir' ), 'icon' => '<path d="M18 6L6 18M6 6l12 12"/>' ],
            'notsure' => [ 'text' => $badges['notsure'] ?: ( $settings['text_btn_notsure'] ?? 'Ragu-ragu' ), 'icon' => '<circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><path d="M12 17h.01"/>' ]
        ];
        
        if ( isset( $map[ $status ] ) ) {
            return sprintf( '<span class="ev-rsvp-meta-tag status-%s"><svg xmlns="https://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">%s</svg> %s</span>', esc_attr( $status ), $map[ $status ]['icon'], esc_html( $map[ $status ]['text'] ) );
        }
        return '';
    }

    private function format_custom_comment_time( $cid, $settings = [] ) {
        $c = get_comment( $cid ); if ( ! $c ) return '';
        $pid = $c->comment_post_ID;
        $tz_meta = strtoupper( get_post_meta( $pid, '_rsvp_timezone', true ) );
        $tz_key = in_array( $tz_meta, [ 'WIB', 'WITA', 'WIT' ] ) ? $tz_meta : ( $settings['custom_timezone'] ?? 'default' );
        
        $tz_map = [ 'WIB' => 'Asia/Jakarta', 'WITA' => 'Asia/Makassar', 'WIT' => 'Asia/Jayapura' ];
        $tz_str = $tz_map[ $tz_key ] ?? wp_timezone_string();
        
        try {
            $date = new DateTime( $c->comment_date_gmt, new DateTimeZone( 'UTC' ) );
            $date->setTimezone( new DateTimeZone( $tz_str ) );
        } catch ( Exception $e ) {
            return $c->comment_date;
        }
        
        $is_eng = get_post_meta( $pid, '_rsvp_eng', true ) === 'ya';
        if ( $is_eng ) return $date->format( 'j F Y - h.i a' );
        
        $ago = human_time_diff( get_comment_date( 'U', $cid ), current_time( 'timestamp' ) );
        
        
        return sprintf( 
            '%s <br> <span style="font-weight: normal; font-size: 0.9em; opacity: 0.7;">%s ( %s yang lalu )</span>', 
            $date->format( 'j F Y' ), 
            $date->format( 'H.i' ), 
            $ago 
        );
    }

    public function ajax_lite_guestbook() {
        // Tighten rate limit to 30 requests per minute per IP to prevent brute-forcing passkeys
        if ( ! $this->check_rate_limit( 'lite_guestbook', 30, 60 ) ) {
            wp_send_json_error( [ 'message' => 'Terlalu banyak percobaan. Silakan tunggu sebentar.' ] );
        }

        // Nonce check — JS already sends evGuestbookLiteConfig.nonce
        check_ajax_referer( 'ev_lite_guestbook_nonce', 'nonce' );

        $post_id = intval( $_POST['post_id'] ?? 0 );
        $route   = sanitize_text_field( $_POST['route'] ?? '' );
        // Credential sources (priority order)
        $direct_slug    = sanitize_text_field( $_POST['direct_slug']    ?? '' );
        $direct_passkey = sanitize_text_field( $_POST['direct_passkey'] ?? '' );
        $url_slug       = sanitize_text_field( $_POST['url_slug']       ?? '' );

        if ( ! $post_id || empty( $route ) ) {
            wp_send_json_error( [ 'message' => 'Parameter tidak lengkap.' ] );
        }

        // --- Resolve event_link & passkey (3-tier priority) ---

        // Priority 1: Direct credentials from JS login session (no post meta needed)
        if ( ! empty( $direct_slug ) && ! empty( $direct_passkey ) ) {
            $event_link = $direct_slug;
            $passkey    = $direct_passkey;

        // Priority 2: URL slug → lookup in passkey_map post meta
        } elseif ( ! empty( $url_slug ) ) {
            $map_raw = get_post_meta( $post_id, '_ev_lite_passkey_map', true );
            if ( ! empty( $map_raw ) ) {
                $map = json_decode( $map_raw, true );
                if ( is_array( $map ) && isset( $map[ $url_slug ] ) ) {
                    $event_link = $url_slug;
                    $passkey    = sanitize_text_field( $map[ $url_slug ] );
                }
            }
            if ( empty( $event_link ) ) {
                $event_link = get_post_meta( $post_id, '_ev_lite_event_link', true );
                $passkey    = get_post_meta( $post_id, '_ev_lite_passkey',    true );
            }

        // Priority 3: Static post meta (widget configured with Event Link + Passkey)
        } else {
            $event_link = get_post_meta( $post_id, '_ev_lite_event_link', true );
            $passkey    = get_post_meta( $post_id, '_ev_lite_passkey',    true );
        }

        if ( empty( $event_link ) || empty( $passkey ) ) {
            wp_send_json_error( [ 'message' => 'Konfigurasi tidak ditemukan. Silakan login kembali atau hubungi administrator.' ] );
        }

        $base_url = eveent_get_api_base_url();
        $api_url  = rtrim( $base_url, '/' ) . '/api/lite-guestbook/' . ltrim( $route, '/' );

        // Build payload – whitelist allowed keys only (security: prevent field injection)
        $allowed_keys = ['page', 'per_page', 'search', 'filter', 'sort', 'order', 'comment_id', 'content', 'author_name', 'attendance', 'guest', 'sticker', 'reply_to'];
        $payload = [];
        foreach ( $allowed_keys as $k ) {
            if ( isset( $_POST[ $k ] ) ) {
                $payload[ $k ] = is_array( $_POST[ $k ] ) ? array_map( 'sanitize_text_field', $_POST[ $k ] ) : sanitize_text_field( $_POST[ $k ] );
            }
        }
        $payload['event_link'] = $event_link;
        $payload['passkey']    = $passkey;

        $args = [
            'method'  => 'POST',
            'timeout' => 10,
            'body'    => $payload
        ];

        $response = wp_remote_post( $api_url, $args );

        if ( is_wp_error( $response ) ) {
            wp_send_json_error( [ 'message' => 'Gagal terhubung ke server. Silakan coba lagi.' ] );
        }

        $body        = wp_remote_retrieve_body( $response );
        $status_code = wp_remote_retrieve_response_code( $response );
        $data        = json_decode( $body, true );

        if ( $status_code !== 200 ) {
            $msg = isset( $data['message'] ) ? $data['message'] : 'Terjadi kesalahan di server Guestbook.';
            wp_send_json_error( [ 'message' => $msg ] );
        }

        if ( $route === 'stats' ) {

            if ( ! isset($data['data']) || ! is_array($data['data']) ) {
                $data['data'] = [];
            }

            if ( ! empty( $event_link ) ) {
                $event_posts = get_posts([
                    'name'           => $event_link,
                    'post_type'      => 'any',
                    'post_status'    => 'any',
                    'posts_per_page' => 1,
                    'fields'         => 'ids'
                ]);
                $actual_post_id = !empty($event_posts) ? $event_posts[0] : 0;
                
                if ( $actual_post_id ) {
                    $data['data']['event_title']     = html_entity_decode(get_the_title( $actual_post_id ), ENT_QUOTES, 'UTF-8');
                    $event_thumb = get_the_post_thumbnail_url( $actual_post_id, 'full' );
                    if(empty($event_thumb)) {
                        $attachments = get_posts([
                            'post_type' => 'attachment',
                            'post_mime_type' => 'image',
                            'post_parent' => $actual_post_id,
                            'posts_per_page' => 1,
                            'fields' => 'ids'
                        ]);
                        if(!empty($attachments)) {
                            $event_thumb = wp_get_attachment_url($attachments[0]);
                        }
                    }
                    $data['data']['event_thumbnail'] = $event_thumb;
                }
            }
            if ( ! $actual_post_id ) $actual_post_id = $post_id;
            
            // Cache stats result for 60s to prevent N+1 query storm
            $stats_cache_key = 'ev_lite_stats_' . $actual_post_id;
            $cached_stats = get_transient( $stats_cache_key );
            
            if ( false !== $cached_stats ) {
                $data['data'] = array_merge( $data['data'], $cached_stats );
            } else {
                $comments = get_comments( [ 'post_id' => $actual_post_id, 'status' => 'approve', 'number' => 500 ] );
                
                // Batch-prime meta cache in ONE query instead of 3000+ individual queries
                if ( ! empty( $comments ) ) {
                    $comment_ids = wp_list_pluck( $comments, 'comment_ID' );
                    update_meta_cache( 'comment', $comment_ids );
                }
                
                $hadir = 0; $tidak_hadir = 0; $ragu = 0; $rsvp_pax = 0;
                $total_ucapan = count( $comments );
                $settings = $this->get_rsvp_widget_settings( $actual_post_id );
                
                $text_hadir = !empty($settings['text_btn_present']) ? $settings['text_btn_present'] : 'Hadir';
                $text_tidak = !empty($settings['text_btn_notpresent']) ? $settings['text_btn_notpresent'] : 'Tidak Hadir';
                $text_ragu  = !empty($settings['text_btn_notsure']) ? $settings['text_btn_notsure'] : 'Masih Ragu';

                $all_wishes = [];
                foreach ( $comments as $c ) {
                    $status = get_comment_meta( $c->comment_ID, 'attendance', true );
                    if ( in_array( strtolower( $status ), [ 'hadir', 'present' ] ) ) {
                        $hadir++;
                        $pax = get_comment_meta( $c->comment_ID, 'guest', true );
                        $rsvp_pax += intval( $pax ) ?: 1;
                        $norm_status = 'hadir';
                        $label = $text_hadir;
                    } elseif ( in_array( strtolower( $status ), [ 'tidak hadir', 'notpresent' ] ) ) {
                        $tidak_hadir++;
                        $norm_status = 'tidak_hadir';
                        $label = $text_tidak;
                    } else {
                        if ( in_array( strtolower( $status ), [ 'masih ragu', 'notsure' ] ) ) {
                            $ragu++;
                        }
                        $norm_status = 'ragu';
                        $label = $text_ragu;
                    }

                    $sticker      = get_comment_meta( $c->comment_ID, '_selected_sticker', true );
                    if(empty($sticker)) {
                        $sticker = get_comment_meta( $c->comment_ID, 'sticker', true );
                    }

                    $sticker_html = '';
                    if ( ! empty( $sticker ) ) {
                        $sticker_data = json_decode( $sticker, true );
                        if ( is_array( $sticker_data ) ) {
                            // Format A: {"type":"svg","value":"url"} (from JS frontend)
                            if ( isset( $sticker_data['type'] ) && isset( $sticker_data['value'] ) && is_string( $sticker_data['value'] ) ) {
                                if ( $sticker_data['type'] === 'svg' || $sticker_data['type'] === 'image' ) {
                                    $sticker_html = '<img src="' . esc_url( $sticker_data['value'] ) . '" alt="Sticker" style="max-height: 80px; display: block; margin-top: 8px;">';
                                } else {
                                    $sticker_html = '<i class="' . esc_attr( $sticker_data['value'] ) . '" style="font-size: 32px; color: #f59e0b; margin-top: 8px;"></i>';
                                }
                            }
                            // Format B: {"library":"svg","value":{"url":"..."}} (from Elementor)
                            elseif ( isset( $sticker_data['library'] ) && isset( $sticker_data['value'] ) ) {
                                $val = $sticker_data['value'];
                                if ( $sticker_data['library'] === 'svg' && is_array($val) && isset($val['url']) ) {
                                    $sticker_html = '<img src="' . esc_url( $val['url'] ) . '" alt="Sticker" style="max-height: 80px; display: block; margin-top: 8px;">';
                                } elseif ( is_string($val) ) {
                                    $sticker_html = '<i class="' . esc_attr( $val ) . '" style="font-size: 32px; color: #f59e0b; margin-top: 8px;"></i>';
                                }
                            }
                            // Format C: {"value":"..."} only
                            elseif ( isset( $sticker_data['value'] ) && is_string( $sticker_data['value'] ) ) {
                                $val = $sticker_data['value'];
                                if ( preg_match( '/^https?:\/\//', $val ) || strpos( $val, '/' ) === 0 ) {
                                    $sticker_html = '<img src="' . esc_url( $val ) . '" alt="Sticker" style="max-height: 80px; display: block; margin-top: 8px;">';
                                } else {
                                    $sticker_html = '<i class="' . esc_attr( $val ) . '" style="font-size: 32px; color: #f59e0b; margin-top: 8px;"></i>';
                                }
                            }
                        } elseif ( is_string($sticker) ) {
                            // Format D: legacy raw HTML — sanitize with wp_kses
                            if ( strpos($sticker, '<img') !== false || strpos($sticker, '<i') !== false || strpos($sticker, '<svg') !== false ) {
                                 $sticker_html = wp_kses( $sticker, $this->get_allowed_sticker_kses() );
                            }
                            // Format E: plain URL
                            elseif ( preg_match( '/^https?:\/\//', $sticker ) ) {
                                 $sticker_html = '<img src="' . esc_url( $sticker ) . '" alt="Sticker" style="max-height: 80px; display: block; margin-top: 8px;">';
                            }
                        }
                    }

                    $wish_content = $c->comment_content;
                    // Strip system/sticker phrases from display when sticker exists
                    if ( ! empty( $sticker_html ) ) {
                        $wish_content = str_replace( ['Konfirmasi Hadir', 'Konfirmasi Tidak Hadir', 'Konfirmasi Ragu-ragu', 'Konfirmasi Kehadiran', '(Stiker)'], '', $wish_content );
                        $wish_content = trim( $wish_content );
                    }

                    $all_wishes[] = [
                        'author'  => $c->comment_author,
                        'content' => $wish_content,
                        'status'  => $norm_status,
                        'label'   => $label,
                        'sticker' => $sticker_html,
                        'date'    => get_comment_date( 'd M Y', $c->comment_ID )
                    ];
                }
                
                $stats_result = [
                    'wp_rsvp_hadir'       => $hadir,
                    'wp_rsvp_tidak_hadir' => $tidak_hadir,
                    'wp_rsvp_ragu'        => $ragu,
                    'wp_rsvp_total'       => $hadir + $tidak_hadir + $ragu,
                    'wp_rsvp_pax'         => $rsvp_pax,
                    'wp_ucapan'           => $total_ucapan,
                    'wishes'              => $all_wishes,
                ];
                
                // Cache for 5 minutes — prevents DB storm from concurrent users
                set_transient( $stats_cache_key, $stats_result, 300 );
                $data['data'] = array_merge( $data['data'], $stats_result );
            }
        }

        wp_send_json_success( $data );
    }
}