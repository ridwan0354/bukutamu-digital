<?php
namespace Eveent\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Background;
use Elementor\Icons_Manager;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class EV_Privacy_Widget extends Widget_Base {

    public function get_name() {
        return 'ev-privacy-settings';
    }

    public function get_title() {
        return esc_html__( 'EV Privasi', 'eveent-widgets' );
    }

    public function get_icon() {
        return 'eicon-lock';
    }

    public function get_categories() {
        return [ 'eveent-widgets' ];
    }
    
    public function get_style_depends() {
        return [ 'ewf-privacy-widget' ]; 
    }

    protected function register_controls() {

        $is_edit_mode = \Elementor\Plugin::instance()->editor->is_edit_mode();

        $this->start_controls_section(
            'privacy_section',
            [
                'label' => esc_html__( 'Pengaturan Privasi', 'eveent-widgets' ),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );
        
        $this->add_control(
            'guest_privacy_heading',
            [
                'label' => esc_html__( 'Mode Privasi Tamu', 'eveent-widgets' ),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );
        
        $this->add_control(
            'hide_post_toggle',
            [
                'label'    => esc_html__( 'Aktifkan Privasi Undangan', 'eveent-widgets' ),
                'type'    => Controls_Manager::SWITCHER,
                'label_on'    => esc_html__( 'ON', 'eveent-widgets' ),
                'label_off'    => esc_html__( 'OFF', 'eveent-widgets' ),
                'return_value' => 'yes',
                'default'    => $is_edit_mode ? 'yes' : 'no', 
                'description' => esc_html__( 'Jika diaktifkan undangan akan di privasi dan hanya bisa dibuka saat memasukkan kode akses passkey.', 'eveent-widgets' ),
            ]
        );

        $this->add_control(
            'access_code',
            [
                'label' => esc_html__( 'Passkey', 'eveent-widgets' ),
                'type' => Controls_Manager::TEXT,
                'default' => 'EVEENT',
                'description' => esc_html__( 'Masukkan passkey untuk halaman ini.', 'eveent-widgets' ),
                'dynamic' => [      
                    'active' => true,
                ],
                'condition' => [
                    'hide_post_toggle' => 'yes',
                ],
            ]
        );
        
        $this->add_control(
            'hide_guest_privacy_toggle',
            [
                'label'    => esc_html__( 'Privasi Buku Tamu', 'eveent-widgets' ),
                'type'    => Controls_Manager::SWITCHER,
                'label_on'    => esc_html__( 'ON', 'eveent-widgets' ),
                'label_off'    => esc_html__( 'OFF', 'eveent-widgets' ),
                'return_value' => 'yes',
                'default'    => $is_edit_mode ? 'yes' : 'no', 
                'description' => esc_html__( 'Privasi tamu undangan berdasarkan data dari Buku Tamu. Jika di aktifkan, maka Wajib aktifkan Privasi Undangan', 'eveent-widgets' ),
                'condition' => [
                    'hide_post_toggle' => 'yes',
                ],
            ]
        );
        
        $this->add_control(
            'guest_not_found_heading',
            [
                'label' => esc_html__( 'Pesan', 'eveent-widgets' ),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__( 'Maaf, Akses Khusus Tamu Undangan.', 'eveent-widgets' ),
                'dynamic' => [      
                    'active' => true,
                ],
                'condition' => [
                    'hide_guest_privacy_toggle' => 'yes',
                    'hide_post_toggle' => 'yes',
                ],
            ]
        );
        
        $this->add_control(
            'parameter_privacy_toggle',
            [
                'label'    => esc_html__( 'Privasi Parameter', 'eveent-widgets' ),
                'type'    => Controls_Manager::SWITCHER,
                'label_on'    => esc_html__( 'ON', 'eveent-widgets' ),
                'label_off'    => esc_html__( 'OFF', 'eveent-widgets' ),
                'return_value' => 'yes',
                'default'    => 'no', 
                'description' => esc_html__( 'Jika diaktifkan, halaman akan tampil jika parameter URL yang ditentukan mengandung Nilai Akses yang benar.', 'eveent-widgets' ),
                'separator' => 'before',
                'condition' => [
                    'hide_post_toggle' => 'yes',
                ],
            ]
        );
        
        $this->add_control(
            'parameter_key_name',
            [
                'label' => esc_html__( 'Nama Kunci Parameter Wajib', 'eveent-widgets' ),
                'type' => Controls_Manager::TEXT,
                'default' => 'key',
                'description' => esc_html__( 'Masukkan nama kunci parameter yang harus digunakan (Contoh: key). URL harus menggunakan format ?key=nilai.',
                'eveent-widgets' ),
                'dynamic' => [      
                    'active' => true,
                ],
                'condition' => [
                    'parameter_privacy_toggle' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'parameter_access_value',
            [
                'label' => esc_html__( 'Nilai Parameter Akses', 'eveent-widgets' ),
                'type' => Controls_Manager::TEXT,
                'default' => 'eveent',
                'description' => esc_html__( 'Masukkan nilai unik (Contoh: eveent). Halaman akan tampil jika URL adalah ?key_wajib=eveent.', 'eveent-widgets' ),
                'dynamic' => [      
                    'active' => true,
                ],
                'condition' => [
                    'parameter_privacy_toggle' => 'yes',
                ],
            ]
        );
        
        $this->add_control(
            'heading_text',
            [
                'label' => esc_html__( 'Judul Akses', 'eveent-widgets' ),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__( 'Masukkan PassKey', 'eveent-widgets' ),
                'placeholder' => esc_html__( 'Masukkan Judul...', 'eveent-widgets' ),
                'dynamic' => [      
                    'active' => true,
                ],
                'condition' => [
                    'hide_post_toggle' => 'yes',
                ],
            ]
        );
        
        $this->add_control(
            'placeholder_text',
            [
                'label' => esc_html__( 'Teks Placeholder', 'eveent-widgets' ),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__( 'Kode Akses...', 'eveent-widgets' ),
                'placeholder' => esc_html__( 'Masukkan teks placeholder...', 'eveent-widgets' ),
                'dynamic' => [      
                    'active' => true,
                ],
                'condition' => [
                    'hide_post_toggle' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'button_text',
            [
                'label' => esc_html__( 'Teks Tombol', 'eveent-widgets' ),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__( 'Masuk', 'eveent-widgets' ),
                'placeholder' => esc_html__( 'Masukkan Teks Tombol...', 'eveent-widgets' ),
                'dynamic' => [      
                    'active' => true,
                ],
                'condition' => [
                    'hide_post_toggle' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'icon_style_heading',
            [
                'label' => esc_html__( 'Icon Login', 'eveent-widgets' ),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => [
                    'hide_post_toggle' => 'yes',
                ],
            ]
        );
        
        $this->add_control(
            'icon_select',
            [
                'label' => esc_html__( 'Pilih Icon', 'eveent-widgets' ),
                'type' => Controls_Manager::ICONS,
                'default' => [
                    'value' => 'eicon-envelope',
                    'library' => 'eicons',
                ],
                'condition' => [
                    'hide_post_toggle' => 'yes',
                ],
            ]
        );
        
        $this->add_control(
            'icon_size',
            [
                'label' => esc_html__( 'Ukuran Icon', 'eveent-widgets' ),
                'type' => Controls_Manager::SLIDER,
                'size_units' => [ 'px', 'em' ],
                'range' => [
                    'px' => [
                        'min' => 10,
                        'max' => 100,
                        'step' => 1,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 48,
                ],
                'selectors' => [
                    '{{WRAPPER}} .ewf-privacy-login-content .ewf-login-icon' => 'font-size: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .ewf-privacy-login-content .ewf-login-icon svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                ],
                'condition' => [
                    'icon_select[value]!' => '',
                ],
            ]
        );

        $this->end_controls_section();
        
        $this->start_controls_section(
            'design_section',
            [
                'label' => esc_html__( 'Desain Halaman Privasi', 'eveent-widgets' ),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'hide_post_toggle' => 'yes',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name' => 'background_overlay_control',
                'label' => esc_html__( 'Latar Belakang Image', 'eveent-widgets' ),
                'types' => [ 'classic', 'gradient' ],
                'selector' => '{{WRAPPER}} .ewf-privacy-login-wrap', 
            ]
        );
        
        $this->add_control(
            'overlay_color',
            [
                'label' => esc_html__( 'Warna Overlay', 'eveent-widgets' ),
                'type' => Controls_Manager::COLOR,
                'default' => '#000000',
                'selectors' => [
                    '{{WRAPPER}} .ewf-privacy-login-wrap:before' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'overlay_opacity',
            [
                'label' => esc_html__( 'Kegelapan Overlay (Opacity)', 'eveent-widgets' ),
                'type' => Controls_Manager::SLIDER,
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 1,
                        'step' => 0.01,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 0.0, 
                ],
                'selectors' => [
                    '{{WRAPPER}} .ewf-privacy-login-wrap:before' => 'opacity: {{SIZE}};',
                ],
            ]
        );

        $this->add_control(
            'icon_color',
            [
                'label' => esc_html__( 'Warna Icon', 'eveent-widgets' ),
                'type' => Controls_Manager::COLOR,
                'default' => '#a1887f',
                'selectors' => [
                    '{{WRAPPER}} .ewf-privacy-login-content .ewf-login-icon' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .ewf-privacy-login-content .ewf-login-icon svg' => 'fill: {{VALUE}};',
                ],
                'condition' => [
                    'icon_select[value]!' => '',
                ],
                'separator' => 'before',
            ]
        );
        
        $this->add_control(
            'login_wall_title_style_heading',
            [
                'label' => esc_html__( 'Teks Judul', 'eveent-widgets' ),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => [
                    'hide_post_toggle' => 'yes',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'login_wall_title_typography',
                'selector' => '{{WRAPPER}} .ewf-privacy-login-content h2',
                'condition' => [
                    'hide_post_toggle' => 'yes',
                ],
            ]
        );
        
        $this->add_control(
            'login_wall_title_color',
            [
                'label' => esc_html__( 'Warna Judul', 'eveent-widgets' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ewf-privacy-login-content h2' => 'color: {{VALUE}};',
                ],
                'condition' => [
                    'hide_post_toggle' => 'yes',
                ],
            ]
        );
        
        $this->add_control(
            'guest_message_style_heading',
            [
                'label' => esc_html__( 'Teks Pesan', 'eveent-widgets' ),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => [
                    'hide_post_toggle' => 'yes',
                    'hide_guest_privacy_toggle' => 'yes',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'guest_message_typography',
                'selector' => '{{WRAPPER}} .ewf-guest-not-registered-message',
                'condition' => [
                    'hide_post_toggle' => 'yes',
                    'hide_guest_privacy_toggle' => 'yes',
                ],
            ]
        );
        
        $this->add_control(
            'guest_message_text_color',
            [
                'label' => esc_html__( 'Warna Teks Pesan', 'eveent-widgets' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ewf-guest-not-registered-message' => 'color: {{VALUE}};',
                ],
                'condition' => [
                    'hide_post_toggle' => 'yes',
                    'hide_guest_privacy_toggle' => 'yes',
                ],
            ]
        );
        
        $this->add_control(
            'form_box_style_heading',
            [
                'label' => esc_html__( 'Kotak Form', 'eveent-widgets' ),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );
        
        $this->add_control(
            'content_bg_color',
            [
                'label' => esc_html__( 'Background Kotak', 'eveent-widgets' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ewf-privacy-login-content' => 'background-color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'content_border',
                'label' => esc_html__( 'Border Kotak', 'eveent-widgets' ),
                'selector' => '{{WRAPPER}} .ewf-privacy-login-content',
            ]
        );

        $this->add_control(
            'input_style_heading',
            [
                'label' => esc_html__( 'Input Barcode', 'eveent-widgets' ),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'input_typography',
                'selector' => '{{WRAPPER}} .ewf-privacy-login-input',
            ]
        );
        
        $this->add_control(
            'input_text_color',
            [
                'label' => esc_html__( 'Warna Teks Input', 'eveent-widgets' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ewf-privacy-login-input' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'input_border',
                'label' => esc_html__( 'Border Input', 'eveent-widgets' ),
                'selector' => '{{WRAPPER}} .ewf-privacy-login-input',
            ]
        );
        
        $this->add_control(
            'input_bg_color',
            [
                'label' => esc_html__( 'Background Input', 'eveent-widgets' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ewf-privacy-login-input' => 'background-color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_control(
            'input_border_radius',
            [
                'label' => esc_html__( 'Border Radius Input', 'eveent-widgets' ),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', '%', 'em' ],
                'selectors' => [
                    '{{WRAPPER}} .ewf-privacy-login-input' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'default' => [
                    'top' => 8,
                    'right' => 8,
                    'bottom' => 8,
                    'left' => 8,
                    'unit' => 'px',
                    'isLinked' => true,
                ],
            ]
        );
        
        $this->add_control(
            'button_style_heading',
            [
                'label' => esc_html__( 'Tombol Style', 'eveent-widgets' ),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => [
                    'hide_post_toggle' => 'yes',
                ],
            ]
        );
        
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'button_typography',
                'selector' => '{{WRAPPER}} #ewf_submit_barcode',
                'condition' => [
                    'hide_post_toggle' => 'yes',
                ],
            ]
        );
        
        $this->add_control(
            'button_bg_color',
            [
                'label' => esc_html__( 'Background Tombol', 'eveent-widgets' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} #ewf_submit_barcode' => 'background-color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_control(
            'button_text_color',
            [
                'label' => esc_html__( 'Warna Teks Tombol', 'eveent-widgets' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} #ewf_submit-barcode' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();
    }
    
    protected function render_clear_lock() {
        ?>
        <script>
             document.body.classList.remove('ewf-show-wall');
        </script>
        <?php
    }

    private function is_guest_access_valid( $settings ) {
        if ( ! function_exists( 'wp_remote_get' ) || ! function_exists( 'get_the_ID' ) || ! function_exists( 'get_transient' ) ) {
            return false;
        }

        $id = isset( $_GET['id'] ) ? sanitize_text_field( $_GET['id'] ) : '';
        $hide_status_guest = $settings['hide_guest_privacy_toggle'] === 'yes';

        if ( $hide_status_guest && empty( $id ) ) {
            return 'NOT_FOUND_GUEST';  
        }

        if ( ! $hide_status_guest ) {
            return false;
        }

        $guest_id_to_check = $id;  
        
        $user_ip = $_SERVER['REMOTE_ADDR'];
        $ip_transient_key = 'ewf_api_rate_' . md5( $user_ip );  
        $ip_lockout_key = 'ewf_api_lockout_' . md5( $user_ip );  
        
        $max_requests = 3;  
        $rate_window = MINUTE_IN_SECONDS * 1;  
        $lockout_duration = HOUR_IN_SECONDS * 1;  

        $is_locked_out = get_transient( $ip_lockout_key );
        
        if ( $is_locked_out ) {
            return 'LOCKED_OUT';
        }

        $request_count = get_transient( $ip_transient_key );

        if ( $request_count === false ) {
            set_transient( $ip_transient_key, 1, $rate_window );
        } else {
            if ( $request_count + 1 > $max_requests ) {
                set_transient( $ip_lockout_key, 'locked', $lockout_duration );  
                return 'LOCKED_OUT';
            }
            set_transient( $ip_transient_key, $request_count + 1, $rate_window );
        }

        $transient_key = 'ewf_guest_access_' . md5( $guest_id_to_check );
        $fallback_key  = 'ewf_guest_fallback_' . md5( $guest_id_to_check ); // last known valid, 24 jam

        $cached_result = get_transient( $transient_key );

        if ( 'VALID' === $cached_result ) {
            return true;
        }
        if ( 'INVALID' === $cached_result ) {
            return 'INVALID_GUEST';
        }

        $api_url = eveent_get_api_base_url() . '/api/validate-guest/';
        $full_api_url = $api_url . $guest_id_to_check;
        
        $response = wp_remote_get(  
            $full_api_url,
            [  
                'timeout' => 5,
            ]  
        );

        $response_code = wp_remote_retrieve_response_code( $response );

        if ( is_wp_error( $response ) || $response_code >= 500 ) {
            // Koneksi gagal atau server error sementara
            // Cek fallback: jika tamu sebelumnya pernah valid dalam 24 jam, izinkan akses (grace mode)
            $fallback = get_transient( $fallback_key );
            if ( $fallback === 'VALID' ) {
                // Server down tapi tamu pernah tervalidasi — izinkan sementara
                // Cache pendek 5 menit agar segera retry saat server pulih
                set_transient( $transient_key, 'VALID', MINUTE_IN_SECONDS * 5 );
                return true;
            }
            // Tidak ada riwayat valid → blokir seperti biasa
            set_transient( $transient_key, 'INVALID', MINUTE_IN_SECONDS * 1 );
            return 'INVALID_GUEST';
        }

        if ( $response_code === 200 ) {
            set_transient( $transient_key, 'VALID', HOUR_IN_SECONDS * 1 );
            // Simpan juga ke fallback 24 jam sebagai cadangan saat server down
            set_transient( $fallback_key, 'VALID', 24 * HOUR_IN_SECONDS );
            return true;  
        }

        set_transient( $transient_key, 'INVALID', MINUTE_IN_SECONDS * 5 );
        return 'INVALID_GUEST';
    }

    protected function render_login_wall_html( $settings, $post_id, $is_not_found_mode ) {
        
        $heading_text = $is_not_found_mode ? $settings['guest_not_found_heading'] : $settings['heading_text'];
        $guest_not_found_message = $settings['guest_not_found_heading'];  
        
        $wrapper_class = 'ewf-privacy-login-wrap ewf-hidden'; 
        if ($is_not_found_mode) {
             $wrapper_class = 'ewf-privacy-login-wrap ewf-guest-not-found-wrap';
        }
        
        if ( \Elementor\Plugin::instance()->editor->is_edit_mode() ) {
        ?>
        <style>
            .elementor-element-<?php echo $this->get_id(); ?> .ewf-privacy-login-wrap {
            position: relative; 
            height: auto;
            min-height: 100vh;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            }
            .elementor-element-<?php echo $this->get_id(); ?> .ewf-hidden {
                display: flex !important; 
            }
        </style>
        <?php
        }

        echo '<div class="' . esc_attr( $wrapper_class ) . '">';
        echo '<div class="ewf-privacy-login-content">';
        
        if ( ! empty( $settings['icon_select']['value'] ) && !$is_not_found_mode ) {
            echo '<div class="ewf-login-icon-wrap">';
            \Elementor\Icons_Manager::render_icon(  
                $settings['icon_select'],  
                [  
                    'aria-hidden' => 'true',  
                    'class' => 'ewf-login-icon'  
                ]  
            );
            echo '</div>';
        }
        
        if ( !$is_not_found_mode && $settings['hide_guest_privacy_toggle'] === 'yes' ) {
            echo '<p class="ewf-guest-not-registered-message">' . wp_kses_post( $guest_not_found_message ) . '</p>';
        }
        
        echo '<h2>' . esc_html( $heading_text ) . '</h2>';
        
        if (!$is_not_found_mode) {
            echo '<div class="ewf-privacy-login-input-container">';
            echo '<input type="text" id="ewf_barcode_input" class="ewf-privacy-login-input" placeholder="' . esc_attr( $settings['placeholder_text'] ) . '">';
            echo '<button id="ewf_submit_barcode">' . esc_html( $settings['button_text'] ) . '</button>';
            echo '<p id="ewf_message_area" class="ewf-message-area"></p>';
            echo '</div>';
        }

        echo '</div>';
        echo '</div>';
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        $post_id = get_the_ID();
        
        $is_editor = \Elementor\Plugin::instance()->editor->is_edit_mode();
        
       
        $elementor_param_toggle = isset( $settings['parameter_privacy_toggle'] ) ? $settings['parameter_privacy_toggle'] : 'no';
        $parameter_key_name = isset( $settings['parameter_key_name'] ) ? $settings['parameter_key_name'] : 'key';
        $parameter_access_value = isset( $settings['parameter_access_value'] ) ? $settings['parameter_access_value'] : 'eveent';

        $final_param_toggle = $elementor_param_toggle; 
        
        if (!$is_editor) {
            
            $meta_param_privacy = get_post_meta( $post_id, 'privasi_parameter', true );
            if ($meta_param_privacy === 'ya') {
                $final_param_toggle = 'yes';
            } elseif ($meta_param_privacy === 'tidak') {
                $final_param_toggle = 'no';
            }  
        }

        if ( $final_param_toggle === 'yes' && ! $is_editor ) {
            $required_key = strtolower( sanitize_text_field( $parameter_key_name ) );
            $required_value = strtolower( sanitize_text_field( $parameter_access_value ) );
            
            if ( isset( $_GET[ $required_key ] ) ) {
                $actual_value = strtolower( sanitize_text_field( $_GET[ $required_key ] ) );
                
                if ( ! empty( $required_value ) && $actual_value === $required_value ) {
                    $this->render_clear_lock(); 
                    return; 
                }
            }
        }

      
        $elementor_guest_toggle = isset( $settings['hide_guest_privacy_toggle'] ) ? $settings['hide_guest_privacy_toggle'] : 'no';
        $elementor_post_toggle = isset( $settings['hide_post_toggle'] ) ? $settings['hide_post_toggle'] : 'no';

        if ($is_editor) {
            $hide_status_guest = $elementor_guest_toggle;
            $hide_status_code = $elementor_post_toggle;
            $parameter_privacy_toggle = $final_param_toggle; 
        } else {
            $meta_guest_privacy = get_post_meta( $post_id, 'privasi_nama_tamu', true );
            $meta_hide_post = get_post_meta( $post_id, 'privasi_undangan', true );

          
            if ($meta_guest_privacy === 'ya') {
                $hide_status_guest = 'yes';
            } elseif ($meta_guest_privacy === 'tidak') {  
                $hide_status_guest = 'no';
            } else {
                $hide_status_guest = $elementor_guest_toggle;
            }

            if ($meta_hide_post === 'ya') {
                $hide_status_code = 'yes';
            } elseif ($meta_hide_post === 'tidak') {  
                $hide_status_code = 'no';
            } else {
                $hide_status_code = $elementor_post_toggle;
            }
            
            $parameter_privacy_toggle = $final_param_toggle;
        }

       
        $settings['hide_guest_privacy_toggle'] = $hide_status_guest;
        $settings['hide_post_toggle'] = $hide_status_code;
        
        

        $show_login_wall = false;
        $show_not_found_guest = false;
        $guest_access_valid = false;
        $guest_status = null;
        
      
        if ( $hide_status_guest === 'yes' && !$is_editor ) {
            $guest_status = $this->is_guest_access_valid( $settings );  
            
            if ( $guest_status === true ) {
                $guest_access_valid = true;
            } elseif ( $guest_status === 'NOT_FOUND_GUEST' ) {
                if ($hide_status_code === 'no') {
                    $show_not_found_guest = true;
                } else {
                    $show_login_wall = true;
                }
            } elseif ( $guest_status === 'INVALID_GUEST' ) {
                $show_login_wall = true;
            } elseif ( $guest_status === 'LOCKED_OUT' ) {
                $show_login_wall = true;
            }
        }
        
        if ( $hide_status_code === 'yes' && ($guest_status === false || $hide_status_guest === 'no') ) {
            $show_login_wall = true;
        }

        if ( $guest_access_valid ) {
            if (!$is_editor) {
                $this->render_clear_lock(); 
            }
            return;
        }
        
        if ($show_login_wall && $guest_status === 'LOCKED_OUT') {
            $lockout_message = esc_html__( 'Maaf, Akses terkunci.', 'eveent-widgets' ) .  
                                     '<br>' .  
                                     esc_html__( 'Silakan coba lagi nanti atau hubungi pemilik acara.', 'eveent-widgets' );
                                     
            $settings['heading_text'] = esc_html__( 'Akses Terkunci Sementara', 'eveent-widgets' );
            $settings['guest_not_found_heading'] = $lockout_message;  
        }

        if ( $is_editor ) {
            if ( $hide_status_code === 'yes' ) {
                $this->render_login_wall_html( $settings, $post_id, false );
            } elseif ( $hide_status_guest === 'yes' ) {
                ?>
                <div class="elementor-widget-container">
                    <div style="padding: 15px; border: 1px dashed red; background: #fff8f8; text-align: center;">
                        <strong><?php esc_html_e( 'EWF Privasi Tamu AKTIF', 'eveent-widgets' ); ?></strong>
                        <p style="margin: 5px 0 0; font-size: 14px; color: #cc0000;">Halaman akan disembunyikan jika ID Tamu tidak terdaftar.</p>
                        <small>Pesan: "<?php echo esc_html($settings['guest_not_found_heading']); ?>"</small>
                    </div>
                </div>
                <?php
            } elseif ($parameter_privacy_toggle === 'yes') {
                ?>
                <div class="elementor-widget-container">
                    <div style="padding: 15px; border: 1px dashed blue; background: #f8f8ff; text-align: center;">
                        <strong><?php esc_html_e( 'EWF Privasi Parameter AKTIF ', 'eveent-widgets' ); ?></strong>
                        <p style="margin: 5px 0 0; font-size: 14px; color: #0000cc;">Halaman akan dibuka hanya jika URL menggunakan **Kunci: `<?php echo esc_html($parameter_key_name); ?>`** dan **Nilai: `<?php echo esc_html($parameter_access_value); ?>`**</p>
                        <small>Contoh URL yang akan tampil: `.../?<?php echo esc_html($parameter_key_name); ?>=<?php echo esc_html($parameter_access_value); ?>`</small>
                    </div>
                </div>
                <?php
            } else {
                ?>
                <div class="elementor-widget-container">
                    <div style="padding: 15px; border: 1px solid #c9c9c9; background: #fff; text-align: center;">
                        <strong><?php esc_html_e( 'EWF Privasi Widget', 'eveent-widgets' ); ?></strong>
                        <p style="margin: 5px 0 0; font-size: 14px; color: #666;">Status: NON-AKTIF (Konten ditampilkan secara normal)</p>
                        <small style="display: block; margin-top: 5px;">Aktifkan privasi untuk melihat Preview Halaman Akses.</small>
                    </div>
                </div>
                <?php
            }
            return;
        }  
        
        if ( $show_login_wall ) {
            $this->render_login_wall_html( $settings, $post_id, false );
            $this->render_script( $post_id, $settings['access_code'] );
        } elseif ( $show_not_found_guest ) {
            $this->render_login_wall_html( $settings, $post_id, true );
        } elseif ( $hide_status_code === 'yes' ) {
            $this->render_script( $post_id, $settings['access_code'] ); 
        }
    }

    protected function render_script( $post_id, $access_code ) {
        if ( ! defined( 'EWF_URL' ) || ! defined( 'EWF_VERSION' ) ) {
            return;
        }

        $valid_code = sanitize_text_field( $access_code );
        
        if (\Elementor\Plugin::instance()->editor->is_edit_mode()) {
            return;
        }
        
        wp_enqueue_script( 'ev-privacy-handler', EWF_URL . 'assets/js/ev-privacy-handler.js', ['jquery'], EWF_VERSION, true );
        
        $script_data = [
            'postId'    => esc_js( $post_id ),
            'validCode' => esc_js( strtoupper( $valid_code ) ), 
        ];
        
        wp_localize_script( 'ev-privacy-handler', 'EWF_PRIVACY_DATA', $script_data );
    }
}