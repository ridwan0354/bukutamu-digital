<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Elementor_Barcode_Widget extends \Elementor\Widget_Base {

    public function get_name() { return 'ewf-barcode'; }
    public function get_title() { return esc_html__( 'EV Card QR', 'eveent-widgets' ); }
    public function get_icon() { return 'eicon-apps'; }
    public function get_categories() { return [ 'eveent-widgets' ]; }

    public function get_style_depends() { return [ 'ev-barcode-style' ]; }
    public function get_script_depends() { return [ 'ewf-html2canvas', 'ewf-qrcode-js', 'ev-barcode-handler', 'ev-card-dl' ]; }

    protected function _register_controls() {

        $this->start_controls_section( 'section_card_content', [ 'label' => esc_html__( 'Konten QR Code', 'eveent-widgets' ) ] );
        
        $this->add_control( 'header_button_text', [
            'label' => esc_html__( 'Teks Header', 'eveent-widgets' ),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => 'QR Check in',
            'dynamic' => [ 'active' => true ],
        ] );

        $this->add_control( 'main_image', [
            'label' => esc_html__( 'Gambar Utama', 'eveent-widgets' ),
            'type' => \Elementor\Controls_Manager::MEDIA,
            'dynamic' => [ 'active' => true ],
            'default' => [ 'url' => \Elementor\Utils::get_placeholder_image_src(), ],
        ] );
        
        $this->add_control( 'image_aspect_ratio', [
            'label' => esc_html__( 'Rasio Aspek Gambar', 'eveent-widgets' ),
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => 'landscape',
            'options' => [
                'landscape' => esc_html__( 'Landscape (16:9)', 'eveent-widgets' ),
                'square'    => esc_html__( 'Square (1:1)', 'eveent-widgets' ),
            ],
            'prefix_class' => 'ewf-image-ratio-', 
            'condition' => [
                'main_image[url]!' => '', 
            ],
        ] );

        $this->add_control( 'image_overlay_line1', [
            'label' => esc_html__( 'Nama Undangan', 'eveent-widgets' ),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => 'Fulan & Fulanah',
            'dynamic' => [ 'active' => true ],
        ] );

        $this->add_control( 'image_overlay_line2', [
            'label' => esc_html__( 'Acara', 'eveent-widgets' ),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => 'Minggu, 20 Juli 2025',
            'dynamic' => [ 'active' => true ],
        ] );

        $this->add_control( 'guest_label', [
            'label' => esc_html__( 'Label Tamu', 'eveent-widgets' ),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => 'Kepada Yth',
            'dynamic' => [ 'active' => true ],
        ] );

        $this->add_control( 'guest_name', [
            'label' => esc_html__( 'Nama Tamu', 'eveent-widgets' ),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => 'Nama Tamu',
            'dynamic' => [ 'active' => true ],
        ] );

        $this->add_control( 'instruction_text', [
            'label' => esc_html__( 'Teks Petunjuk', 'eveent-widgets' ),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => 'Mohon tunjukkan QR code ini',
            'dynamic' => [ 'active' => true ],
        ] );

        $this->add_control( 'download_button_text', [
            'label' => esc_html__( 'Teks Tombol Download QR', 'eveent-widgets' ),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => 'Download Barcode',
            'dynamic' => [ 'active' => true ],
        ] );

        $this->add_control( 'download_filename', [
            'label' => esc_html__( 'Nama File Download QR', 'eveent-widgets' ),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => 'qrcode.png',
            'dynamic' => [ 'active' => true ],
            
        ] );

        $this->add_control( 'hr_e_invitation', [ 'type' => \Elementor\Controls_Manager::DIVIDER, 'style' => 'thick' ] );

        $this->add_control( 'e_invitation_button_text', [
            'label' => esc_html__( 'Teks Tombol E-Invitation', 'eveent-widgets' ),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => 'Download E-Invitation',
            'dynamic' => [ 'active' => true ],
        ] );

        $this->add_control( 'e_invitation_filename', [
            'label' => esc_html__( 'Nama File E-Invitation', 'eveent-widgets' ),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => 'e-invitation.png',
            'dynamic' => [ 'active' => true ],
            
        ] );

        $this->add_control( 'hr_footer', [ 'type' => \Elementor\Controls_Manager::DIVIDER, 'style' => 'thick' ] );
        
        $this->add_control( 'footer_logo', [
            'label' => esc_html__( 'Logo Footer', 'eveent-widgets' ),
            'type' => \Elementor\Controls_Manager::MEDIA,
            'dynamic' => [ 'active' => true ],
        ] );

        $this->add_control( 'copyright_text', [
            'label' => esc_html__( 'Teks Copyright', 'eveent-widgets' ),
            'type' => \Elementor\Controls_Manager::TEXTAREA,
            'default' => '&copy; 2025 Undangan Digital',
            'dynamic' => [ 'active' => true ],
            'rows' => 3,
        ] );

        $this->end_controls_section();

        $this->start_controls_section( 'section_qr_settings', [ 'label' => esc_html__( 'Pengaturan', 'eveent-widgets' ) ] );

        $this->add_control( 'target_url', [
            'label' => esc_html__( 'Sumber URL', 'eveent-widgets' ),
            'type' => \Elementor\Controls_Manager::URL,
            'dynamic' => [ 'active' => true, ],
            'description' => 'Gunakan Dynamic Tag "Post URL" di sini.',
        ] );

        $this->add_control( 'url_parse_mode', [
            'label' => esc_html__( 'Mode Penggunaan URL', 'eveent-widgets' ),
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => 'full_url',
            'options' => [
                'full_url' => esc_html__( 'URL Default', 'eveent-widgets' ),
                'slug_only' => esc_html__( 'URL Buku Tamu', 'eveent-widgets' ),
            ],
            'condition' => [ 'target_url[url]!' => '', ],
        ] );

        $this->add_control( 'tamu_mode', [
            'label' => esc_html__( 'Integrasi Buku Tamu', 'eveent-widgets' ),
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => 'eveent',
            'options' => [
                'eveent' => esc_html__( 'Default (Sesuai Pengaturan Admin)', 'eveent-widgets' ),
                'custom' => esc_html__( 'Custom Domain Lainnya', 'eveent-widgets' ),
            ],
            'condition' => [ 'url_parse_mode' => 'slug_only' ],
        ] );

        $this->add_control( 'domain_name', [
            'label' => esc_html__( 'Domain Baru', 'eveent-widgets' ),
            'type' => \Elementor\Controls_Manager::TEXT,
            'placeholder' => 'domain.com',
            'condition' => [ 
                'url_parse_mode' => 'slug_only',
                'tamu_mode' => 'custom',
            ],
            'dynamic' => [ 'active' => true ],
        ] );

        $this->add_control( 'hr_param', [ 'type' => \Elementor\Controls_Manager::DIVIDER ] );

        $this->add_control( 'query_param_key', [
            'label' => esc_html__( 'Key', 'eveent-widgets' ),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => 'to',
            'dynamic' => [ 'active' => true ]
        ] );

        $this->add_control( 'query_param_fallback', [
            'label' => esc_html__( 'Value', 'eveent-widgets' ),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => 'Tamu Undangan',
            'dynamic' => [ 'active' => true ],
            'description' => 'Id Passkey.'
        ] );

        $this->end_controls_section();

        $this->start_controls_section( 'section_button_settings', [ 'label' => esc_html__( 'Tombol QR Code', 'eveent-widgets' ) ] );

        $this->add_control( 'button_icon', [
            'label' => esc_html__( 'Ikon Tombol', 'eveent-widgets' ),
            'type' => \Elementor\Controls_Manager::ICONS,
            'default' => [ 'value' => 'fas fa-qrcode', 'library' => 'fa-solid', ],
        ] );

        $this->add_responsive_control( 'button_offset_y', [
            'label' => esc_html__( 'Jarak dari Bawah', 'eveent-widgets' ),
            'type' => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px', '%'],
            'default' => [ 'unit' => 'px', 'size' => 40 ],
            'range' => [ 'px' => [ 'min' => 0, 'max' => 500 ] ],
            'selectors' => [ '{{WRAPPER}} .ewf-barcode-trigger' => 'bottom: {{SIZE}}{{UNIT}};' ]
        ] );

        $this->add_responsive_control( 'button_offset_x', [
            'label' => esc_html__( 'Jarak dari Kanan', 'eveent-widgets' ),
            'type' => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px', '%'],
            'default' => [ 'unit' => 'px', 'size' => 40 ],
            'range' => [ 'px' => [ 'min' => 0, 'max' => 500 ] ],
            'selectors' => [ '{{WRAPPER}} .ewf-barcode-trigger' => 'right: {{SIZE}}{{UNIT}};' ]
        ] );

        $this->add_control(
			'hide_on_scroll',
			[
				'label'   => __( 'Hide on Scroll', 'eveent-widgets' ),
				'type'    => \Elementor\Controls_Manager::SWITCHER,
				'label_on' => __( 'Yes', 'eveent-widgets' ),
				'label_off' => __( 'No', 'eveent-widgets' ),
				'return_value' => 'yes',
				'default' => 'no',
				'separator' => 'before',
			]
		);

        $this->end_controls_section();

        $this->start_controls_section( 'section_style_card', [ 'label' => esc_html__( 'Card QR', 'eveent-widgets' ), 'tab' => \Elementor\Controls_Manager::TAB_STYLE ] );

        $this->add_responsive_control( 'card_max_width', [
            'label' => esc_html__( 'Lebar Maksimal', 'eveent-widgets' ),
            'type' => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range' => [ 'px' => [ 'min' => 280, 'max' => 800 ] ],
            'default' => [ 'unit' => 'px', 'size' => 450 ],
            'selectors' => [ '{{WRAPPER}} .ewf-checkin-card' => 'max-width: {{SIZE}}{{UNIT}};' ]
        ] );
        
        $this->add_control(
        'popup_animation',
        [
            'label' => esc_html__( 'Animasi Pop-up', 'eveent-widgets' ),
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => 'slide-in-right',
            'options' => [
                'fade-in-zoom' => esc_html__( 'Fade In Zoom', 'eveent-widgets' ),
                'slide-in-bottom' => esc_html__( 'Geser dari Bawah', 'eveent-widgets' ),
                'slide-in-top' => esc_html__( 'Geser dari Atas', 'eveent-widgets' ),
                'slide-in-left' => esc_html__( 'Geser dari Kiri', 'eveent-widgets' ),
                'slide-in-right' => esc_html__( 'Geser dari Kanan', 'eveent-widgets' ),
            ],
            'prefix_class' => 'ewf-popup-animation-',
            'separator' => 'before',
        ]
    );

        $this->add_control( 'card_bg_color', [
            'label' => esc_html__( 'Warna Latar', 'eveent-widgets' ),
            'type' => \Elementor\Controls_Manager::COLOR,
            'selectors' => [ '{{WRAPPER}} .ewf-checkin-card' => 'background-color: {{VALUE}}' ]
        ] );

        $this->add_group_control( \Elementor\Group_Control_Border::get_type(), [ 'name' => 'card_border', 'selector' => '{{WRAPPER}} .ewf-checkin-card' ] );

        $this->add_group_control( \Elementor\Group_Control_Box_Shadow::get_type(), [ 'name' => 'card_box_shadow', 'selector' => '{{WRAPPER}} .ewf-checkin-card' ] );

        $this->end_controls_section();
        
        $this->start_controls_section( 'section_style_content', [ 'label' => esc_html__( 'Konten Teks', 'eveent-widgets' ), 'tab' => \Elementor\Controls_Manager::TAB_STYLE ] );
        
        $this->add_control( 'heading_style_header', [ 'label' => esc_html__( 'Tombol Header', 'eveent-widgets' ), 'type' => \Elementor\Controls_Manager::HEADING ] );
        $this->add_control( 'header_button_bg', [ 'label' => 'Warna Latar', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .ewf-header-button' => 'background-color: {{VALUE}};' ] ] );
        $this->add_control( 'header_button_color', [ 'label' => 'Warna Teks', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .ewf-header-button' => 'color: {{VALUE}};' ] ] );
        $this->add_group_control( \Elementor\Group_Control_Typography::get_type(), [ 'name' => 'header_button_typography', 'selector' => '{{WRAPPER}} .ewf-header-button' ] );
        
        $this->add_control( 'heading_style_overlay', [ 'label' => esc_html__( 'Konten Acara', 'eveent-widgets' ), 'type' => \Elementor\Controls_Manager::HEADING, 'separator' => 'before' ] );
        $this->add_control( 'overlay_color_line1', [ 'label' => 'Nama Undangan', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .ewf-image-overlay .line1' => 'color: {{VALUE}};' ] ] );
        $this->add_group_control( \Elementor\Group_Control_Typography::get_type(), [ 'name' => 'overlay_typography_line1', 'selector' => '{{WRAPPER}} .ewf-image-overlay .line1' ] );
        $this->add_control( 'overlay_color_line2', [ 'label' => 'Acara', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .ewf-image-overlay .line2' => 'color: {{VALUE}};' ] ] );
        $this->add_group_control( \Elementor\Group_Control_Typography::get_type(), [ 'name' => 'overlay_typography_line2', 'selector' => '{{WRAPPER}} .ewf-image-overlay .line2' ] );
        
        $this->add_control( 'heading_style_guest', [ 'label' => esc_html__( 'Info Tamu', 'eveent-widgets' ), 'type' => \Elementor\Controls_Manager::HEADING, 'separator' => 'before' ] );
        $this->add_control( 'guest_label_color', [ 'label' => 'Dear', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .ewf-content-left .label' => 'color: {{VALUE}};' ] ] );
        $this->add_group_control( \Elementor\Group_Control_Typography::get_type(), [ 'name' => 'guest_label_typography', 'selector' => '{{WRAPPER}} .ewf-content-left .label' ] );
        $this->add_control( 'guest_name_color', [ 'label' => 'Nama Tamu', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .ewf-content-left .guest-name' => 'color: {{VALUE}};' ] ] );
        $this->add_group_control( \Elementor\Group_Control_Typography::get_type(), [ 'name' => 'guest_name_typography', 'selector' => '{{WRAPPER}} .ewf-content-left .guest-name' ] );
        
        $this->add_control( 'heading_style_footer', [ 'label' => esc_html__( 'Footer', 'eveent-widgets' ), 'type' => \Elementor\Controls_Manager::HEADING, 'separator' => 'before' ] );
        $this->add_control( 'instruction_color', [ 'label' => 'Teks Petunjuk', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .ewf-footer-area .instruction' => 'color: {{VALUE}};' ] ] );
        $this->add_group_control( \Elementor\Group_Control_Typography::get_type(), [ 'name' => 'instruction_typography', 'selector' => '{{WRAPPER}} .ewf-footer-area .instruction' ] );
        
        $this->add_control( 'download_button_style_heading', [ 'label' => esc_html__( 'Tombol Download QR', 'eveent-widgets' ), 'type' => \Elementor\Controls_Manager::HEADING, 'separator' => 'before' ] );
        $this->start_controls_tabs( 'tabs_download_button_style' );
        $this->start_controls_tab( 'tab_download_button_normal', [ 'label' => esc_html__( 'Normal', 'eveent-widgets' ) ] );
        $this->add_control( 'download_button_color', [ 'label' => 'Warna Teks', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .ewf-download-button' => 'color: {{VALUE}};' ] ] );
        $this->add_control( 'download_button_bg', [ 'label' => 'Warna Latar', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .ewf-download-button' => 'background-color: {{VALUE}};' ] ] );
        $this->end_controls_tab();
        $this->start_controls_tab( 'tab_download_button_hover', [ 'label' => esc_html__( 'Hover', 'eveent-widgets' ) ] );
        $this->add_control( 'download_button_color_hover', [ 'label' => 'Warna Teks', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .ewf-download-button:hover' => 'color: {{VALUE}};' ] ] );
        $this->add_control( 'download_button_bg_hover', [ 'label' => 'Warna Latar', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .ewf-download-button:hover' => 'background-color: {{VALUE}};' ] ] );
        $this->end_controls_tab();
        $this->end_controls_tabs();
        $this->add_group_control( \Elementor\Group_Control_Typography::get_type(), [ 'name' => 'download_button_typography', 'selector' => '{{WRAPPER}} .ewf-download-button' ] );
        
        $this->add_control( 'e_invitation_button_style_heading', [ 'label' => esc_html__( 'Tombol E-Invitation', 'eveent-widgets' ), 'type' => \Elementor\Controls_Manager::HEADING, 'separator' => 'before' ] );
        $this->start_controls_tabs( 'tabs_e_invitation_button_style' );
        $this->start_controls_tab( 'tab_e_invitation_button_normal', [ 'label' => esc_html__( 'Normal', 'eveent-widgets' ) ] );
        $this->add_control( 'e_invitation_button_color', [
            'label' => 'Warna Teks',
            'type' => \Elementor\Controls_Manager::COLOR,
            'selectors' => [ '{{WRAPPER}} .ewf-e-invitation-button' => 'color: {{VALUE}};' ],
            'default' => '#000000',
        ] );
        $this->add_control( 'e_invitation_button_bg', [
            'label' => 'Warna Latar',
            'type' => \Elementor\Controls_Manager::COLOR,
            'selectors' => [ '{{WRAPPER}} .ewf-e-invitation-button' => 'background-color: {{VALUE}};' ],
            'default' => '#FFFFFF',
        ] );
        $this->add_group_control( \Elementor\Group_Control_Typography::get_type(), ['name' => 'e_invitation_button_typography', 'selector' => '{{WRAPPER}} .ewf-e-invitation-button', 'fields_options' => ['typography' => ['default' => 'yes'], 'font_weight' => ['default' => '600'], 'font_size' => ['default' => ['size' => 16, 'unit' => 'px']], 'text_transform' => ['default' => 'uppercase'], 'letter_spacing' => ['default' => ['size' => 0.5, 'unit' => 'px']]], ] );
        $this->add_responsive_control( 'e_invitation_button_padding', [ 'label' => esc_html__( 'Padding', 'eveent-widgets' ), 'type' => \Elementor\Controls_Manager::DIMENSIONS, 'size_units' => [ 'px', 'em', '%' ], 'selectors' => [ '{{WRAPPER}} .ewf-e-invitation-button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ], 'default' => [ 'top' => '10', 'right' => '20', 'bottom' => '10', 'left' => '20', 'unit' => 'px', 'isLinked' => false ], ] );
        $this->add_group_control( \Elementor\Group_Control_Border::get_type(), [ 'name' => 'e_invitation_button_border', 'selector' => '{{WRAPPER}} .ewf-e-invitation-button', 'fields_options' => [ 'width' => [ 'default' => [ 'top' => 1, 'right' => 1, 'bottom' => 1, 'left' => 1, 'isLinked' => true ] ], 'color' => [ 'default' => 'var(--e-global-color-accent)' ] ], ] );
        $this->add_responsive_control( 'e_invitation_button_border_radius', [ 'label' => esc_html__( 'Border Radius', 'eveent-widgets' ), 'type' => \Elementor\Controls_Manager::DIMENSIONS, 'size_units' => [ 'px', '%' ], 'selectors' => [ '{{WRAPPER}} .ewf-e-invitation-button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ], 'default' => [ 'top' => '5', 'right' => '5', 'bottom' => '5', 'left' => '5', 'unit' => 'px', 'isLinked' => true ], ] );
        $this->add_group_control( \Elementor\Group_Control_Box_Shadow::get_type(), [ 'name' => 'e_invitation_button_box_shadow', 'selector' => '{{WRAPPER}} .ewf-e-invitation-button' ] );
        $this->end_controls_tab();
        $this->start_controls_tab( 'tab_e_invitation_button_hover', [ 'label' => esc_html__( 'Hover', 'eveent-widgets' ) ] );
        $this->add_control( 'e_invitation_button_color_hover', [ 'label' => 'Warna Teks', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .ewf-e-invitation-button:hover' => 'color: {{VALUE}};' ] ] );
        $this->add_control( 'e_invitation_button_bg_hover', [ 'label' => 'Warna Latar', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .ewf-e-invitation-button:hover' => 'background-color: {{VALUE}};' ] ] );
        
        $this->add_group_control( \Elementor\Group_Control_Box_Shadow::get_type(), [ 'name' => 'e_invitation_button_box_shadow_hover', 'selector' => '{{WRAPPER}} .ewf-e-invitation-button:hover' ] );
        $this->end_controls_tab();
        $this->end_controls_tabs();

        $this->add_control( 'footer_logo_style_heading', [ 'label' => esc_html__( 'Logo & Copyright Footer', 'eveent-widgets' ), 'type' => \Elementor\Controls_Manager::HEADING, 'separator' => 'before' ] );
        $this->add_responsive_control( 'footer_logo_max_width', [
            'label' => esc_html__( 'Lebar Maksimal Logo', 'eveent-widgets' ),
            'type' => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px', '%'],
            'range' => [ 'px' => [ 'min' => 20, 'max' => 300 ], '%' => [ 'min' => 10, 'max' => 100 ] ],
            'default' => [ 'unit' => 'px', 'size' => 120 ],
            'selectors' => [ '{{WRAPPER}} .ewf-footer-logo img' => 'max-width: {{SIZE}}{{UNIT}};' ]
        ] );
        $this->add_control( 'copyright_text_color', [
            'label' => 'Teks Copyright',
            'type' => \Elementor\Controls_Manager::COLOR,
            'selectors' => [ '{{WRAPPER}} .ewf-copyright-text' => 'color: {{VALUE}};' ],
            'separator' => 'before'
        ] );
        $this->add_group_control( \Elementor\Group_Control_Typography::get_type(), [
            'name' => 'copyright_text_typography',
            'selector' => '{{WRAPPER}} .ewf-copyright-text'
        ] );

        $this->end_controls_section();
        
        $this->start_controls_section( 'section_style_trigger_button', [ 'label' => esc_html__( 'Tombol QR Code', 'eveent-widgets' ), 'tab' => \Elementor\Controls_Manager::TAB_STYLE ] );
        
        $this->add_responsive_control( 'trigger_button_size', [
            'label' => esc_html__( 'Ukuran Tombol', 'eveent-widgets' ),
            'type' => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range' => [ 'px' => [ 'min' => 30, 'max' => 100 ] ],
            'selectors' => [ '{{WRAPPER}} .ewf-barcode-trigger' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};' ]
        ] );

        $this->add_responsive_control( 'trigger_icon_size', [
            'label' => esc_html__( 'Ukuran Ikon', 'eveent-widgets' ),
            'type' => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range' => [ 'px' => [ 'min' => 10, 'max' => 50 ] ],
            'selectors' => [ '{{WRAPPER}} .ewf-barcode-trigger i, {{WRAPPER}} .ewf-barcode-trigger svg' => 'font-size: {{SIZE}}{{UNIT}};' ]
        ] );

        $this->add_control( 'trigger_button_color', [
            'label' => esc_html__( 'Warna Ikon', 'eveent-widgets' ),
            'type' => \Elementor\Controls_Manager::COLOR,
            'selectors' => [ '{{WRAPPER}} .ewf-barcode-trigger' => 'color: {{VALUE}}' ]
        ] );

        $this->add_control( 'trigger_button_bg_color', [
            'label' => esc_html__( 'Warna Latar', 'eveent-widgets' ),
            'type' => \Elementor\Controls_Manager::COLOR,
            'selectors' => [ '{{WRAPPER}} .ewf-barcode-trigger' => 'background-color: {{VALUE}}' ]
        ] );

        $this->add_group_control( \Elementor\Group_Control_Border::get_type(), [ 'name' => 'trigger_border', 'selector' => '{{WRAPPER}} .ewf-barcode-trigger' ] );
        
        $this->add_group_control( \Elementor\Group_Control_Box_Shadow::get_type(), [ 'name' => 'trigger_box_shadow', 'selector' => '{{WRAPPER}} .ewf-barcode-trigger' ] );
        
        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        $id = $this->get_id();
        $base_url = '';
        $sumber_url = !empty($settings['target_url']['url']) ? $settings['target_url']['url'] : '';
        
        if ($settings['url_parse_mode'] === 'slug_only' && !empty($sumber_url)) {
            $path = wp_parse_url($sumber_url, PHP_URL_PATH);
            $slug = basename($path);
            
            $domain = '';
            if ($settings['tamu_mode'] === 'eveent') {
                $domain = eveent_get_api_base_domain();
            } else {
                $domain = !empty($settings['domain_name']) ? $settings['domain_name'] : '';
            }

            $base_url = "https://" . trim($domain, '/') . "/" . $slug;
        } else {
            $base_url = $sumber_url;
        }

        $param_key = !empty($settings['query_param_key']) ? $settings['query_param_key'] : '';
        $param_value = !empty($settings['query_param_fallback']) ? $settings['query_param_fallback'] : '';
        $final_url = (!empty($param_key) && !empty($param_value)) ? add_query_arg(rawurlencode($param_key), rawurlencode($param_value), $base_url) : $base_url;

        $this->add_render_attribute('qr_wrapper', [ 'class' => 'ewf-barcode-wrapper', 'data-url' => esc_url($final_url) ]);
        $this->add_render_attribute('download_button', [ 'class' => 'ewf-download-button ewf-download-exclude', 'href' => '#', 'data-filename' => esc_attr($settings['download_filename']) ]);

        $card_id = 'ewf-card-' . $id;
        $this->add_render_attribute('e_invitation_button', [ 'class' => 'ewf-e-invitation-button', 'href' => '#', 'data-filename' => esc_attr($settings['e_invitation_filename']), 'data-card-id' => $card_id ]);
        
        $eveent_domain = '';
        if ($settings['tamu_mode'] === 'eveent') {
            $eveent_domain = eveent_get_api_base_domain();
        } else {
            $eveent_domain = !empty($settings['domain_name']) ? $settings['domain_name'] : '';
        }
        $eveent_api_base_url = "https://" . trim($eveent_domain, '/');
        
        $trigger_classes = 'ewf-barcode-trigger';
        $is_edit_mode = \Elementor\Plugin::instance()->editor->is_edit_mode();
        if ( 'yes' === $settings['hide_on_scroll'] && !$is_edit_mode ) {
            $trigger_classes .= ' ev-hide-on-scroll';
        }
        
        ?>
        <?php if ( 'yes' === $settings['hide_on_scroll'] && !$is_edit_mode ) : ?>
        <style>
        .ewf-barcode-trigger.ev-hide-on-scroll.ev-is-scrolling {
            opacity: 0 !important;
            pointer-events: none !important;
        }
        </style>
        <?php endif; ?>
        
        <div id="ewf-trigger-<?php echo esc_attr($id); ?>" class="<?php echo esc_attr($trigger_classes); ?>">
            <?php \Elementor\Icons_Manager::render_icon($settings['button_icon'], ['aria-hidden' => 'true']); ?>
        </div>
        <div id="ewf-modal-<?php echo esc_attr($id); ?>" class="ewf-barcode-modal-wrapper">
            <div id="<?php echo esc_attr($card_id); ?>" class="ewf-checkin-card">
                <div class="ewf-barcode-modal-close">&times;</div>
                <a href="#" class="ewf-header-button" onclick="event.preventDefault();"><?php echo esc_html($settings['header_button_text']); ?></a>
                <div class="ewf-main-image-wrapper">
                    <?php if (!empty($settings['main_image']['url'])) : ?><img src="<?php echo esc_url($settings['main_image']['url']); ?>" alt="Check-in Image"><?php endif; ?>
                    <div class="ewf-image-overlay">
                        <span class="line1"><?php echo esc_html($settings['image_overlay_line1']); ?></span>
                        <span class="line2"><?php echo esc_html($settings['image_overlay_line2']); ?></span>
                    </div>
                </div>
                <div class="ewf-content-area">
                    <div class="ewf-content-left">
                        <span class="label"><?php echo esc_html($settings['guest_label']); ?></span>
                        <span class="guest-name">
                            <?php
                            $guest_name = $settings['guest_name'];

                            $guest_name_decoded = $guest_name;
                            while (strpos($guest_name_decoded, '&amp;') !== false) {
                                $guest_name_decoded = html_entity_decode($guest_name_decoded, ENT_QUOTES, 'UTF-8');
                            }
                            
                            $pattern = '/\s*(\b(dan|and)\b|&)\s*/i';
                            $replacement = '<br><span style="font-size: 75%; font-weight: normal;">$1</span><br>';
                            $guest_name_formatted = preg_replace($pattern, $replacement, $guest_name_decoded);
                            
                            $allowed_html = [
                                'br' => [],
                                'span' => [ 'style' => true, ],
                            ];
                            echo wp_kses( $guest_name_formatted, $allowed_html );
                            ?>
                        </span>
                    </div>
                    <div class="ewf-content-right">
                        <div <?php echo $this->get_render_attribute_string('qr_wrapper'); ?>></div>
                    </div>
                </div>
                <div class="ewf-footer-area">
                    <p class="instruction"><?php echo esc_html($settings['instruction_text']); ?></p>
                    <a <?php echo $this->get_render_attribute_string('download_button'); ?>><?php echo esc_html($settings['download_button_text']); ?></a>
                    <a <?php echo $this->get_render_attribute_string('e_invitation_button'); ?>><?php echo esc_html($settings['e_invitation_button_text']); ?></a>
                    
                    <?php if (!empty($settings['footer_logo']['url'])) : ?>
                        <div class="ewf-footer-logo">
                            <img src="<?php echo esc_url($settings['footer_logo']['url']); ?>" alt="Footer Logo">
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($settings['copyright_text'])) : ?>
                        <p class="ewf-copyright-text"><?php echo wp_kses_post($settings['copyright_text']); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
            
                const urlParams = new URLSearchParams(window.location.search);
                const paramKey = '<?php echo esc_js($param_key ? $param_key : "id"); ?>';
                let barcodeUid = urlParams.get(paramKey);
                if (!barcodeUid && paramKey !== 'id') {
                    barcodeUid = urlParams.get('id');
                }
                const eveentApiBaseUrl = '<?php echo esc_url($eveent_api_base_url); ?>';
            
               
                function handleFetch(url, options = {}) {
                    return fetch(url, options).then(response => {
                        // If the response is a 404, or if it's OK, we continue.
                        // For any other error (like 500), we throw an error.
                        if (!response.ok && response.status !== 404) {
                            throw new Error('Server Error: ' + response.status);
                        }
                        return response;
                    });
                }
            
                if (barcodeUid && eveentApiBaseUrl) {
            
                   
                    if (sessionStorage.getItem('clicked_' + barcodeUid) !== 'true') {
                        handleFetch(`${eveentApiBaseUrl}/api/track-click/${barcodeUid}`, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' }
                        })
                        .then(response => {
                           
                            if (response.ok) {
                                sessionStorage.setItem('clicked_' + barcodeUid, 'true');
                            }
                        })
                        .catch(error => {
                            
                        });
                    }
            
                   
                    handleFetch(`${eveentApiBaseUrl}/api/guest-details/${barcodeUid}`)
                        .then(response => {
                           
                            if (response.status === 404) {
                                return Promise.reject('Guest not found');
                            }
                            return response.json();
                        })
                        .then(data => {
                            if (data.status === 'success') {
                                const guestDetails = {
                                table_number: data.table_number, // Ambil dari root
                                rsvp_count: data.rsvp_count,     // Ambil dari root
                                // Tambahkan properti lain yang dibutuhkan, misal:
                                // name: data.guest_name,
                            };
                                const cardElement = document.getElementById('<?php echo esc_attr($card_id); ?>');
            
                                if (cardElement) {
                                    const contentLeft = cardElement.querySelector('.ewf-content-left');
            
                                    // Nested fetch for RSVP status
                                    handleFetch(`${eveentApiBaseUrl}/api/guest-rsvp-status/${barcodeUid}`)
                                        .then(rsvpResponse => rsvpResponse.json()) // Assume this will always succeed if guest exists
                                        .then(rsvpData => {
                                            if (rsvpData.status === 'success') {
                                                const rsvpDetails = rsvpData.rsvp || {};
                                                const contentArea = cardElement.querySelector('.ewf-content-area');
            
                                                let statusHtml = '';
                                                if (rsvpDetails.is_attending) {
                                                    statusHtml = `<div class="ewf-checkin-status status-checked-in"><span class="status-icon">✓</span><div class="status-text"><strong>Sudah Check-in</strong><span class="checkin-time">${rsvpDetails.checkin_time}</span></div></div>`;
                                                } else {
                                                    statusHtml = `<div class="ewf-checkin-status status-pending"><span class="status-icon">!</span><div class="status-text"><strong>Belum Check-in</strong><span class="checkin-time">Silakan scan di lokasi acara</span></div></div>`;
                                                }
            
                                                const existingStatus = cardElement.querySelector('.ewf-checkin-status');
                                                if (existingStatus) existingStatus.remove();
                                                if (contentArea) {
                                                    contentArea.insertAdjacentHTML('beforebegin', statusHtml);
                                                }
                                                
                                                const detailsDiv = document.createElement('div');
                                                detailsDiv.className = 'ewf-guest-details';
                                                let detailsHtml = '';
            
                                                const rsvpDisplayCount = guestDetails.rsvp_count || rsvpDetails.wp_rsvp_pax || null;
            
                                                detailsHtml += `<div class="detail-item"><span class="detail-label">Jumlah RSVP</span><span class="detail-value">${rsvpDisplayCount ? `${rsvpDisplayCount} Orang` : '-'}</span></div>`;
            
                                                if (guestDetails.table_number && (guestDetails.rsvp_count > 0 || rsvpDetails.wp_rsvp_pax > 0)) { 
                                                    detailsHtml += `<div class="detail-item"><span class="detail-label">No. Meja</span><span class="detail-value">${guestDetails.table_number}</span></div>`;
                                                }
                                                
                                                detailsDiv.innerHTML = detailsHtml;
            
                                                const existingDetails = contentLeft.querySelector('.ewf-guest-details');
                                                if (existingDetails) existingDetails.remove();
            
                                                if (contentLeft && detailsHtml) {
                                                    contentLeft.appendChild(detailsDiv);
                                                }
                                            }
                                        });
                                }
                            }
                        })
                        .catch(error => {
                            
                        });
                }
            });
            </script>
        <?php
    }
    protected function _content_template() {
        ?>
        <#
        var base_url = '';
        var sumber_url = (settings.target_url && settings.target_url.url) ? settings.target_url.url : '';

        if (settings.url_parse_mode === 'slug_only' && sumber_url) {
            var path = '';
            try {
                var url_obj = new URL(sumber_url);
                path = url_obj.pathname;
            } catch(e) {
                path = sumber_url;
            }
            var slug = path.substring(path.lastIndexOf('/') + 1);
            
            var domain = '';
            if (settings.tamu_mode === 'eveent') {
                domain = '<?php echo esc_js(eveent_get_api_base_domain()); ?>';
            } else {
                domain = settings.domain_name || '';
            }

            base_url = "https://" + domain.replace(/\/$/g, '') + "/" + slug;
        } else {
            base_url = sumber_url;
        }

        var param_key = settings.query_param_key || '';
        var param_value = settings.query_param_fallback || '';
        var final_url = base_url;
        if (param_key && param_value) {
            var separator = base_url.includes('?') ? '&' : '?';
            final_url = base_url + separator + encodeURIComponent(param_key) + '=' + encodeURIComponent(param_value);
        }
        var qr_wrapper_id = 'ewf-preview-qr-' + view.cid;
        var card_id = 'ewf-card-preview-' + view.cid;

        view.addRenderAttribute('download_button', { 'class': ['ewf-download-button', 'ewf-download-exclude'], 'href': '#', 'data-filename': settings.download_filename });
        view.addRenderAttribute('e_invitation_button', { 'class': ['ewf-e-invitation-button', 'ewf-download-exclude'], 'href': '#', 'data-filename': settings.e_invitation_filename, 'data-card-id': card_id });

        var iconHTML = elementor.helpers.renderIcon( view, settings.button_icon, { 'aria-hidden': true }, 'i' , 'object' );

        var guest_name = settings.guest_name || '';

        var guest_name_decoded = guest_name;
        while (guest_name_decoded.includes('&amp;')) {
            guest_name_decoded = guest_name_decoded.replace(/&amp;/g, '&');
        }

        var replacement_string = '<br><span style="font-size: 75%; font-weight: normal;">$1</span><br>';
        var guest_name_formatted = guest_name_decoded.replace(/\s*(\b(dan|and)\b|&)\s*/gi, replacement_string);
        #>
        <div class="ewf-editor-preview-wrapper" style="position: relative; min-height: 400px; border: 2px dashed #ccc; padding: 15px; box-sizing: border-box; overflow: hidden;">
            <div class="ewf-barcode-trigger" style="position: absolute; transform: scale(0.9); bottom: 20px; right: 20px;">
                <# if ( iconHTML.value ) { #> {{{ iconHTML.value }}} <# } #>
            </div>
            <div id="{{ card_id }}" class="ewf-checkin-card" style="margin: 0 auto; transform: none; display: block; visibility: visible; opacity: 1;">
                <a href="#" class="ewf-header-button" onclick="event.preventDefault();">{{{ settings.header_button_text }}}</a>
                <div class="ewf-main-image-wrapper">
                    <# if (settings.main_image.url) { #><img src="{{ settings.main_image.url }}" alt="Check-in Image"><# } #>
                    <div class="ewf-image-overlay">
                        <span class="line1">{{{ settings.image_overlay_line1 }}}</span>
                        <span class="line2">{{{ settings.image_overlay_line2 }}}</span>
                    </div>
                </div>
                <div class="ewf-content-area">
                    <div class="ewf-content-left">
                        <span class="label">{{{ settings.guest_label }}}</span>
                        <span class="guest-name">{{{ guest_name_formatted }}}</span>
                    </div>
                    <div class="ewf-content-right">
                        <div id="{{ qr_wrapper_id }}" class="ewf-barcode-wrapper"></div>
                    </div>
                </div>
                <div class="ewf-footer-area">
                    <p class="instruction">{{{ settings.instruction_text }}}</p>
                    <a {{{ view.getRenderAttributeString('download_button') }}}>{{{ settings.download_button_text }}}</a>
                    <a {{{ view.getRenderAttributeString('e_invitation_button') }}}>{{{ settings.e_invitation_button_text }}}</a>
                    
                    <# if (settings.footer_logo.url) { #>
                        <div class="ewf-footer-logo">
                            <img src="{{ settings.footer_logo.url }}" alt="Footer Logo">
                        </div>
                    <# } #>
                    <# if (settings.copyright_text) { #>
                        <p class="ewf-copyright-text">{{{ settings.copyright_text }}}</p>
                    <# } #>
                </div>
            </div>
        </div>
        <#
        if (final_url && typeof QRCode !== 'undefined') {
            setTimeout(function() {
                var qr_element = document.getElementById(qr_wrapper_id);
                if (qr_element) {
                    qr_element.innerHTML = '';
                    new QRCode(qr_element, { text: final_url, width: 120, height: 120, colorDark : '#000000', colorLight : '#FFFFFF', correctLevel : QRCode.CorrectLevel.H });
                }
            }, 300);
        }
        #>
        <?php
    }
}