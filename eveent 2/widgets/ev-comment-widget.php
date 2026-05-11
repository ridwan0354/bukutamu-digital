<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Repeater;

class Elementor_EV_Comment_Widget extends \Elementor\Widget_Base {

    public function get_name() { return 'ev-comment'; }
    public function get_title() { return esc_html__( 'EV Comment', 'eveent-widgets' ); }
    public function get_icon() { return 'eicon-instagram-comments'; }
    public function get_categories() { return [ 'eveent-widgets' ]; }
    
    
    public function get_style_depends() { return [ 'sweetalert2-css', 'ev-rsvp-style', 'elementor-icons-fa-solid', 'elementor-icons-fa-brands', 'elementor-icons-fa-regular' ]; }
    public function get_script_depends() { return [ 'ev-comment-handler' ]; }

    protected function _register_controls() {
        
        $this->start_controls_section('section_content', ['label' => esc_html__( 'Settings', 'eveent-widgets' )]);
        $this->add_control( 'show_main_title', [ 'label' => esc_html__( 'Show Main Title & Count', 'eveent-widgets' ), 'type' => Controls_Manager::SWITCHER, 'default' => 'yes', ] );
        $this->add_control( 'show_form_title', [ 'label' => esc_html__( 'Show Form Title', 'eveent-widgets' ), 'type' => Controls_Manager::SWITCHER, 'default' => 'yes', ] );
        
        $this->add_control( 'hr_notif', [ 'type' => Controls_Manager::DIVIDER, 'separator' => 'before', ] );
        $this->add_control( 'enable_wa_notice', [ 'label' => esc_html__( 'Send Notification WA Client', 'eveent-widgets' ), 'type' => Controls_Manager::SWITCHER, 'label_on' => esc_html__( 'Ya', 'eveent-widgets' ), 'label_off' => esc_html__( 'Tidak', 'eveent-widgets' ), 'return_value' => 'yes', 'default' => 'no', ] );
        $this->add_control( 'wa_notice_name', [ 'label' => esc_html__( 'Nama Client', 'eveent-widgets' ), 'type' => Controls_Manager::TEXT, 'placeholder' => esc_html__( 'Fulan dan Fulanah', 'eveent-widgets' ), 'description' => esc_html__( 'Nama ini akan digunakan di dalam pesan notifikasi.', 'eveent-widgets' ), 'condition' => [ 'enable_wa_notice' => 'yes', ], 'dynamic' => [  'active' => true, ], ] );
        $this->add_control( 'wa_notice_number', [ 'label' => esc_html__( 'Nomor WA Client', 'eveent-widgets' ), 'type' => Controls_Manager::TEXT, 'placeholder' => '6281234567890', 'description' => esc_html__( 'Gunakan format internasional (62). Contoh 6281234567890 .', 'eveent-widgets' ), 'condition' => [ 'enable_wa_notice' => 'yes', ], 'dynamic' => [  'active' => true, ], ] );
        $this->add_control( 'wa_template', [ 'label' => esc_html__( 'Template Pesan WA', 'eveent-widgets' ), 'type' => Controls_Manager::TEXTAREA, 'rows' => 10, 'description' => 'Gunakan variabel: [post_title], [client_name], [guest_name], [guest_message]', 'condition' => [ 'enable_wa_notice' => 'yes', ], 'default' => "*Notifikasi Ucapan Baru*\n*[post_title]*\n\nHalo Kak [client_name],\nAda ucapan baru dari:\n\n*Nama:* [guest_name]\n\n*Ucapan:*\n[guest_message]\n\n---------------------\nPesan ini dikirim otomatis.", ] );
        
        $this->add_control( 'hr_pagination', [ 'type' => Controls_Manager::DIVIDER, 'style' => 'thick', 'separator' => 'before', ] );
        $this->add_control( 'enable_pagination', [ 'label' => esc_html__( 'Enable Comments Page', 'eveent-widgets' ), 'type' => Controls_Manager::SWITCHER, 'default' => 'yes' ] );
        $this->add_control( 'comments_per_page', [ 'label' => esc_html__( 'Komentar per laman', 'eveent-widgets' ), 'type' => Controls_Manager::NUMBER, 'min' => 1, 'max' => 100, 'default' => 10, 'condition' => [ 'enable_pagination' => 'yes' ] ] );
        
        $this->add_control( 'custom_timezone', [ 'label' => esc_html__( 'Timezone', 'eveent-widgets' ), 'type' => Controls_Manager::SELECT, 'default' => 'default', 'options' => [ 'default' => 'Default', 'WIB' => 'WIB', 'WITA' => 'WITA', 'WIT' => 'WIT' ] ] );
        $this->add_control('hide_sticker_button', ['label' => esc_html__( 'Hide Sticker Button', 'eveent-widgets' ), 'type' => Controls_Manager::SWITCHER, 'default' => 'no']);
        $this->end_controls_section();

        $this->start_controls_section('section_stickers', [ 'label' => esc_html__( 'Sticker List', 'eveent-widgets' ), ]);
        $repeater = new Repeater();
        $repeater->add_control( 'sticker_icon', [ 'label' => esc_html__( 'Stiker', 'eveent-widgets' ), 'type' => \Elementor\Controls_Manager::ICONS, 'default' => [ 'value' => 'fas fa-heart', 'library' => 'solid', ], ] );
        $this->add_control( 'sticker_list', [ 'label' => esc_html__( 'Available Stickers', 'eveent-widgets' ), 'type' => \Elementor\Controls_Manager::REPEATER, 'fields' => $repeater->get_controls(), 'default' => [ [ 'sticker_icon' => [ 'value' => 'fas fa-heart' ] ], [ 'sticker_icon' => [ 'value' => 'fas fa-glass-cheers' ] ] ], 'title_field' => '<i class="{{ sticker_icon.value }}"></i>', ] );
        $this->end_controls_section();

        $this->start_controls_section('section_labels', ['label' => esc_html__( 'Form & Title Labels', 'eveent-widgets' )]);
        $this->add_control('text_main_title', ['label' => 'Judul Utama', 'type' => Controls_Manager::TEXT, 'default' => 'Doa & Ucapan', 'dynamic' => ['active' => true]]);
        $this->add_control('text_form_title', ['label' => 'Judul Form', 'type' => Controls_Manager::TEXT, 'default' => 'Kirimkan Doa Terbaik', 'dynamic' => ['active' => true]]);
        $this->add_control('text_name_label', ['label' => 'Label Nama', 'type' => Controls_Manager::TEXT, 'default' => 'Nama Anda', 'dynamic' => ['active' => true]]);
        $this->add_control('text_name_placeholder', ['label' => 'Placeholder Nama', 'type' => Controls_Manager::TEXT, 'default' => 'Nama Anda...', 'dynamic' => ['active' => true]]);
        $this->add_control('text_comment_label', ['label' => 'Label Ucapan', 'type' => Controls_Manager::TEXT, 'default' => 'Ucapan', 'dynamic' => ['active' => true]]);
        $this->add_control('text_comment_placeholder', ['label' => 'Placeholder Ucapan', 'type' => Controls_Manager::TEXT, 'default' => 'Tulis ucapan...', 'dynamic' => ['active' => true]]);
        $this->add_control('text_btn_sticker', ['label' => 'Teks Tombol Pilih Stiker', 'type' => Controls_Manager::TEXT, 'default' => 'Pilih Stiker', 'dynamic' => ['active' => true]]);
        $this->add_control('text_btn_submit', ['label' => 'Teks Tombol Kirim', 'type' => Controls_Manager::TEXT, 'default' => 'Kirim Ucapan', 'dynamic' => ['active' => true]]);
        $this->add_control('text_btn_reply', ['label' => 'Teks Tombol Balas', 'type' => Controls_Manager::TEXT, 'default' => 'Balas', 'dynamic' => ['active' => true]]);
        
        $this->add_control( 'hr_badge_labels', [ 'type' => Controls_Manager::DIVIDER, 'style' => 'thick', 'separator' => 'before', ] );
        $this->add_control('text_btn_present', ['label' => 'Label Badge "Hadir"', 'type' => Controls_Manager::TEXT, 'default' => 'Hadir', 'dynamic' => ['active' => true]]);
        $this->add_control('text_btn_notpresent', ['label' => 'Label Badge "Tidak Hadir"', 'type' => Controls_Manager::TEXT, 'default' => 'Tidak Hadir', 'dynamic' => ['active' => true]]);
        $this->add_control('text_btn_notsure', ['label' => 'Label Badge "Ragu-ragu"', 'type' => Controls_Manager::TEXT, 'default' => 'Ragu-ragu', 'dynamic' => ['active' => true]]);
        $this->end_controls_section();

        $this->start_controls_section('section_pagination_labels', ['label' => esc_html__( 'Pagination Labels', 'eveent-widgets' )]);
        $this->add_control('text_btn_prev', ['label' => 'Teks Tombol Sebelumnya', 'type' => Controls_Manager::TEXT, 'default' => '← Sebelumnya', 'dynamic' => ['active' => true]]);
        $this->add_control('text_btn_next', ['label' => 'Teks Tombol Selanjutnya', 'type' => Controls_Manager::TEXT, 'default' => 'Selanjutnya →', 'dynamic' => ['active' => true]]);
        $this->end_controls_section();

        $this->start_controls_section( 'section_style_swal', [ 'label' => 'Pop up Notification', 'tab' => Controls_Manager::TAB_STYLE, ] );
        $this->add_control( 'heading_swal_title', [ 'label' => 'Title', 'type' => Controls_Manager::HEADING, ] );
        $this->add_group_control( Group_Control_Typography::get_type(), [ 'name' => 'swal_title_typography', 'selector' => 'body .swal-{{ID}} .swal2-title', 'default' => [ 'font_size' => ['unit' => 'px', 'size' => 14], ], ] );
        $this->add_control( 'heading_swal_text', [ 'label' => 'Message', 'type' => Controls_Manager::HEADING, 'separator' => 'before', ] );
        $this->add_group_control( Group_Control_Typography::get_type(), [ 'name' => 'swal_text_typography', 'selector' => 'body .swal-{{ID}} .swal2-html-container', 'default' => [ 'font_size' => ['unit' => 'px', 'size' => 12], ], ] );
        $this->add_control( 'heading_swal_button', [ 'label' => 'Button', 'type' => Controls_Manager::HEADING, 'separator' => 'before', ] );
        $this->add_group_control( Group_Control_Typography::get_type(), [ 'name' => 'swal_button_typography', 'selector' => 'body .swal-{{ID}} .swal2-confirm', 'default' => [ 'font_size' => ['unit' => 'px', 'size' => 12], ], ] );
        $this->add_control( 'swal_button_bg_color', [ 'label' => 'Button Background Color', 'type' => Controls_Manager::COLOR, 'selectors' => [ 'body .swal-{{ID}} .swal2-confirm' => 'background-color: {{VALUE}};', ], ] );
        $this->add_control( 'swal_button_text_color', [ 'label' => 'Button Text Color', 'type' => Controls_Manager::COLOR, 'selectors' => [ 'body .swal-{{ID}} .swal2-confirm' => 'color: {{VALUE}};', ], ] );
        $this->end_controls_section();

        
        
        $this->start_controls_section('section_style_wrapper', ['label' => 'Widget Container', 'tab' => Controls_Manager::TAB_STYLE]);
        $this->add_responsive_control('wrapper_width', ['label' => 'Width', 'type' => Controls_Manager::SLIDER, 'size_units' => ['px', '%', 'vw'], 'range' => ['px' => ['min' => 200, 'max' => 1200], '%' => ['min' => 50, 'max' => 100]], 'default' => ['unit' => 'px', 'size' => 680], 'selectors' => ['{{WRAPPER}} .ev-rsvp-wrapper' => 'max-width: {{SIZE}}{{UNIT}};']]);
        $this->end_controls_section();

        $this->start_controls_section('section_style_title', ['label' => 'Title & Count Style', 'tab' => Controls_Manager::TAB_STYLE]);
        $this->add_control('heading_count_number', ['label' => 'Count Number (n)', 'type' => Controls_Manager::HEADING]);
        $this->add_control('count_color', ['label' => 'Color', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .ev-rsvp-count' => 'color: {{VALUE}};']]);
        $this->add_group_control(Group_Control_Typography::get_type(), ['name' => 'count_typography', 'selector' => '{{WRAPPER}} .ev-rsvp-count']);
        $this->add_control( 'count_bg_color', [ 'label' => esc_html__( 'Background Color', 'eveent-widgets' ), 'type' => Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .ev-rsvp-count' => 'background-color: {{VALUE}};', ], ] );
        $this->add_control('heading_main_title', ['label' => 'Main Title Text', 'type' => Controls_Manager::HEADING, 'separator' => 'before']);
        $this->add_control('main_title_color', ['label' => 'Color', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .ev-rsvp-main-title' => 'color: {{VALUE}};']]);
        $this->add_group_control(Group_Control_Typography::get_type(), ['name' => 'main_title_typography', 'selector' => '{{WRAPPER}} .ev-rsvp-main-title']);
        $this->end_controls_section();

        $this->start_controls_section('section_style_form', ['label' => 'Form Style', 'tab' => Controls_Manager::TAB_STYLE]);
        $this->add_control('form_bg_color', ['label' => 'Form Background', 'type' => Controls_Manager::COLOR, 'default' => '#f7fafc', 'selectors' => ['{{WRAPPER}} .ev-rsvp-form-wrapper' => 'background-color: {{VALUE}};']]);
        $this->add_group_control(Group_Control_Border::get_type(), ['name' => 'form_border', 'selector' => '{{WRAPPER}} .ev-rsvp-form-wrapper']);
        $this->add_control('heading_form_title_style', ['label' => 'Form Title', 'type' => Controls_Manager::HEADING, 'separator' => 'before']);
        $this->add_control('form_title_color', ['label' => 'Color', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .ev-rsvp-form-title' => 'color: {{VALUE}};']]);
        $this->add_group_control(Group_Control_Typography::get_type(), ['name' => 'form_title_typography', 'selector' => '{{WRAPPER}} .ev-rsvp-form-title']);
        $this->add_responsive_control('form_title_margin', ['label' => 'Margin Bottom', 'type' => Controls_Manager::SLIDER, 'selectors' => ['{{WRAPPER}} .ev-rsvp-form-title' => 'margin-bottom: {{SIZE}}px;']]);
        $this->end_controls_section();

        $this->start_controls_section('section_style_form_fields', ['label' => 'Form Fields', 'tab' => Controls_Manager::TAB_STYLE]);
        $this->add_control('heading_form_labels', ['label' => 'Form Labels', 'type' => Controls_Manager::HEADING]);
        $this->add_control('label_color', ['label' => 'Text Color', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .ev-rsvp-form label' => 'color: {{VALUE}};']]);
        $this->add_group_control(Group_Control_Typography::get_type(), ['name' => 'label_typography', 'selector' => '{{WRAPPER}} .ev-rsvp-form label']);
        $this->add_responsive_control('label_margin', ['label' => 'Margin Bottom', 'type' => Controls_Manager::SLIDER, 'size_units' => ['px', 'em'], 'range' => ['px' => ['min' => 0, 'max' => 50]], 'selectors' => ['{{WRAPPER}} .ev-rsvp-form label' => 'margin-bottom: {{SIZE}}{{UNIT}};']]);
        $this->add_control('heading_form_inputs', ['label' => 'Input & Select Fields', 'type' => Controls_Manager::HEADING, 'separator' => 'before']);
        $this->add_group_control(Group_Control_Typography::get_type(), ['name' => 'input_typography', 'selector' => '{{WRAPPER}} .ev-rsvp-form input[type="text"], {{WRAPPER}} .ev-rsvp-form textarea']);
        $this->add_control('input_text_color', ['label' => 'Text Color', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .ev-rsvp-form input[type="text"], {{WRAPPER}} .ev-rsvp-form textarea' => 'color: {{VALUE}};']]);
        $this->add_control('input_placeholder_color', ['label' => 'Placeholder Color', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .ev-rsvp-form input[type="text"]::placeholder, {{WRAPPER}} .ev-rsvp-form textarea::placeholder' => 'color: {{VALUE}};']]);
        $this->add_control('input_bg_color', ['label' => 'Background Color', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .ev-rsvp-form input[type="text"], {{WRAPPER}} .ev-rsvp-form textarea' => 'background-color: {{VALUE}};']]);
        $this->add_group_control(Group_Control_Border::get_type(), ['name' => 'input_border', 'selector' => '{{WRAPPER}} .ev-rsvp-form input[type="text"], {{WRAPPER}} .ev-rsvp-form textarea']);
        $this->add_control('input_radius', ['label' => 'Border Radius', 'type' => Controls_Manager::DIMENSIONS, 'selectors' => ['{{WRAPPER}} .ev-rsvp-form input[type="text"], {{WRAPPER}} .ev-rsvp-form textarea' => 'border-radius: {{TOP}}px {{RIGHT}}px {{BOTTOM}}px {{LEFT}}px;']]);
        $this->add_group_control(Group_Control_Box_Shadow::get_type(), ['name' => 'input_shadow', 'selector' => '{{WRAPPER}} .ev-rsvp-form input[type="text"], {{WRAPPER}} .ev-rsvp-form textarea']);
        $this->end_controls_section();

        $this->start_controls_section('section_style_buttons', ['label' => 'Buttons', 'tab' => Controls_Manager::TAB_STYLE]);
        $this->start_controls_tabs('tabs_buttons_style');
        
        $this->start_controls_tab('tab_sticker_button', ['label' => 'Sticker']);
        $this->add_group_control(Group_Control_Typography::get_type(), ['name' => 'sticker_btn_typography', 'selector' => '{{WRAPPER}} .ev-rsvp-sticker-trigger span']);
        $this->add_control('sticker_btn_text_color', ['label' => 'Text Color', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .ev-rsvp-sticker-trigger span' => 'color: {{VALUE}};']]);
        $this->add_control('sticker_btn_icon_color', ['label' => 'Icon Color', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .ev-rsvp-sticker-trigger i' => 'color: {{VALUE}};']]);
        $this->add_control('sticker_btn_bg_color', ['label' => 'Background Color', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .ev-rsvp-sticker-trigger' => 'background-color: {{VALUE}};']]);
        $this->add_group_control(Group_Control_Border::get_type(), ['name' => 'sticker_btn_border', 'selector' => '{{WRAPPER}} .ev-rsvp-sticker-trigger']);
        $this->add_control('sticker_btn_radius', ['label' => 'Border Radius', 'type' => Controls_Manager::DIMENSIONS, 'selectors' => ['{{WRAPPER}} .ev-rsvp-sticker-trigger' => 'border-radius: {{TOP}}px {{RIGHT}}px {{BOTTOM}}px {{LEFT}}px;']]);
        $this->add_group_control(Group_Control_Box_Shadow::get_type(), ['name' => 'sticker_btn_shadow', 'selector' => '{{WRAPPER}} .ev-rsvp-sticker-trigger']);
        $this->end_controls_tab();

        $this->start_controls_tab('tab_submit_button', ['label' => 'Submit']);
        $this->add_group_control(Group_Control_Typography::get_type(), ['name' => 'submit_typography', 'selector' => '{{WRAPPER}} .ev-rsvp-submit-button']);
        $this->add_control('submit_btn_bg', ['label' => 'Background Color', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .ev-rsvp-submit-button' => 'background-color: {{VALUE}}']]);
        $this->add_control('submit_btn_text_color', ['label' => 'Text Color', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .ev-rsvp-submit-button' => 'color: {{VALUE}}']]);
        $this->add_group_control(Group_Control_Border::get_type(), ['name' => 'submit_btn_border', 'selector' => '{{WRAPPER}} .ev-rsvp-submit-button']);
        $this->add_control('submit_btn_radius', ['label' => 'Border Radius', 'type' => Controls_Manager::DIMENSIONS, 'selectors' => ['{{WRAPPER}} .ev-rsvp-submit-button' => 'border-radius: {{TOP}}px {{RIGHT}}px {{BOTTOM}}px {{LEFT}}px;']]);
        $this->end_controls_tab();
        
        $this->end_controls_tabs();
        $this->end_controls_section();

        $this->start_controls_section('section_style_pagination', ['label' => 'Pagination Buttons', 'tab' => Controls_Manager::TAB_STYLE]);
        $this->add_group_control(Group_Control_Typography::get_type(), ['name' => 'pagination_typography', 'selector' => '{{WRAPPER}} .ev-rsvp-page-btn']);
        $this->start_controls_tabs('tabs_pagination_style');
        $this->start_controls_tab('tab_pagination_normal', ['label' => 'Normal']);
        $this->add_control('pagination_color', ['label' => 'Text Color', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .ev-rsvp-page-btn' => 'color: {{VALUE}};']]);
        $this->add_control('pagination_bg_color', ['label' => 'Background Color', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .ev-rsvp-page-btn' => 'background-color: {{VALUE}};']]);
        $this->add_group_control(Group_Control_Border::get_type(), ['name' => 'pagination_border', 'selector' => '{{WRAPPER}} .ev-rsvp-page-btn']);
        $this->end_controls_tab();
        $this->start_controls_tab('tab_pagination_hover', ['label' => 'Hover']);
        $this->add_control('pagination_color_hover', ['label' => 'Text Color', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .ev-rsvp-page-btn:hover' => 'color: {{VALUE}};']]);
        $this->add_control('pagination_bg_color_hover', ['label' => 'Background Color', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .ev-rsvp-page-btn:hover' => 'background-color: {{VALUE}};']]);
        $this->add_control('pagination_border_color_hover', ['label' => 'Border Color', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .ev-rsvp-page-btn:hover' => 'border-color: {{VALUE}};']]);
        $this->end_controls_tab();
        $this->end_controls_tabs();
        $this->add_control('pagination_border_radius', ['label' => 'Border Radius', 'type' => Controls_Manager::DIMENSIONS, 'size_units' => ['px', '%'], 'selectors' => ['{{WRAPPER}} .ev-rsvp-page-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'], 'separator' => 'before']);
        $this->add_responsive_control('pagination_padding', ['label' => 'Padding', 'type' => Controls_Manager::DIMENSIONS, 'size_units' => ['px', 'em'], 'selectors' => ['{{WRAPPER}} .ev-rsvp-page-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};']]);
        $this->add_responsive_control('pagination_spacing', ['label' => 'Spacing Between Buttons', 'type' => Controls_Manager::SLIDER, 'range' => ['px' => ['min' => 0, 'max' => 100]], 'selectors' => ['{{WRAPPER}} .ev-rsvp-pagination-nav' => 'gap: {{SIZE}}{{UNIT}};']]);
        $this->end_controls_section();

        $this->start_controls_section('section_style_comment_list', ['label' => 'Comment List', 'tab' => Controls_Manager::TAB_STYLE]);
        $this->add_responsive_control('list_max_height', ['label' => 'Max Height', 'type' => Controls_Manager::SLIDER, 'size_units' => ['px', 'vh'], 'range' => ['px' => ['min' => 200, 'max' => 2000]], 'selectors' => ['{{WRAPPER}} .ev-rsvp-list-container' => 'max-height: {{SIZE}}{{UNIT}};']]);
        $this->add_control('card_bg_color', ['label' => 'Card Background', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .ev-rsvp-comment-item-wrapper' => 'background-color: {{VALUE}};']]);
        $this->add_group_control(Group_Control_Border::get_type(), ['name' => 'card_border', 'selector' => '{{WRAPPER}} .ev-rsvp-comment-item-wrapper']);
        $this->add_group_control(Group_Control_Box_Shadow::get_type(), ['name' => 'card_shadow', 'selector' => '{{WRAPPER}} .ev-rsvp-comment-item-wrapper']);
        $this->add_control('heading_initials_avatar', ['label' => 'Initial Name Avatar', 'type' => Controls_Manager::HEADING, 'separator' => 'before']);
        $this->add_group_control(Group_Control_Typography::get_type(), ['name' => 'initials_typography', 'selector' => '{{WRAPPER}} .ev-rsvp-initials-avatar']);
        $this->add_control('initials_color', ['label' => 'Text Color', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .ev-rsvp-initials-avatar' => 'color: {{VALUE}};']]);
        $this->add_control('initials_bg_color', ['label' => 'Background Color', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .ev-rsvp-initials-avatar' => 'background-color: {{VALUE}};']]);
        $this->end_controls_section();

        $this->start_controls_section('section_style_comment_content', ['label' => 'Comment Content Style', 'tab' => Controls_Manager::TAB_STYLE]);
        $this->add_control('heading_author', ['label' => 'Author Name', 'type' => Controls_Manager::HEADING]);
        $this->add_control('author_color', ['label' => 'Color', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .ev-rsvp-comment-author' => 'color: {{VALUE}};']]);
        $this->add_group_control(Group_Control_Typography::get_type(), ['name' => 'author_typography', 'selector' => '{{WRAPPER}} .ev-rsvp-comment-author']);
        $this->add_control('heading_comment_text', ['label' => 'Comment Text', 'type' => Controls_Manager::HEADING, 'separator' => 'before']);
        $this->add_control('comment_text_color', ['label' => 'Color', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .ev-rsvp-comment-body' => 'color: {{VALUE}};']]);
        $this->add_group_control(Group_Control_Typography::get_type(), ['name' => 'comment_text_typography', 'selector' => '{{WRAPPER}} .ev-rsvp-comment-body']);
        $this->add_control('heading_meta', ['label' => 'Time, Btn Reply, Like', 'type' => Controls_Manager::HEADING, 'separator' => 'before']);
        $this->add_control('timestamp_color', ['label' => 'Comment Time Text', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .ev-rsvp-comment-time' => 'color: {{VALUE}};']]);
        $this->add_group_control(Group_Control_Typography::get_type(), ['name' => 'timestamp_typography', 'selector' => '{{WRAPPER}} .ev-rsvp-comment-time']);
        $this->add_control('actions_color', ['label' => 'Like & Reply Button Color', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .ev-rsvp-like-button, {{WRAPPER}} .ev-rsvp-reply-button' => 'color: {{VALUE}};']]);
        $this->add_group_control(Group_Control_Typography::get_type(), ['name' => 'actions_typography', 'selector' => '{{WRAPPER}} .ev-rsvp-like-button, {{WRAPPER}} .ev-rsvp-reply-button']);
        $this->end_controls_section();

        $this->start_controls_section('section_style_badges', ['label' => 'Badges', 'tab' => Controls_Manager::TAB_STYLE]);
        $this->add_group_control(Group_Control_Typography::get_type(), ['name' => 'badge_typography', 'selector' => '{{WRAPPER}} .ev-rsvp-meta-tag, {{WRAPPER}} .ev-rsvp-reply-badge']);
        $this->start_controls_tabs('tabs_badge_style');
        $this->start_controls_tab('tab_badge_present', ['label' => 'Hadir']);
        $this->add_control('badge_present_color', ['label' => 'Text Color', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .ev-rsvp-meta-tag.status-present' => 'color: {{VALUE}};']]);
        $this->add_control('badge_present_bg', ['label' => 'Background', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .ev-rsvp-meta-tag.status-present' => 'background-color: {{VALUE}};']]);
        $this->end_controls_tab();
        $this->start_controls_tab('tab_badge_notpresent', ['label' => 'Tidak Hadir']);
        $this->add_control('badge_notpresent_color', ['label' => 'Text Color', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .ev-rsvp-meta-tag.status-notpresent' => 'color: {{VALUE}};']]);
        $this->add_control('badge_notpresent_bg', ['label' => 'Background', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .ev-rsvp-meta-tag.status-notpresent' => 'background-color: {{VALUE}};']]);
        $this->end_controls_tab();
        $this->start_controls_tab('tab_badge_notsure', ['label' => 'Ragu-ragu']);
        $this->add_control('badge_notsure_color', ['label' => 'Text Color', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .ev-rsvp-meta-tag.status-notsure' => 'color: {{VALUE}};']]);
        $this->add_control('badge_notsure_bg', ['label' => 'Background', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .ev-rsvp-meta-tag.status-notsure' => 'background-color: {{VALUE}};']]);
        $this->end_controls_tab();
        $this->end_controls_tabs();
        $this->end_controls_section();

        $this->start_controls_section('section_style_reply', ['label' => 'Reply Style', 'tab' => Controls_Manager::TAB_STYLE]);
        $this->add_control('enable_public_reply', ['label' => esc_html__( 'Enable Password Reply', 'eveent-widgets' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'no' ]);
        $this->add_control('reply_badge_text', ['label' => esc_html__( 'Reply Badge Text', 'eveent-widgets' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( 'Pemilik Acara', 'eveent-widgets' ), 'dynamic' => ['active' => true]]);
        $this->add_control('reply_bubble_bg_color', ['label' => 'Bubble Background', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .ev-rsvp-reply-bubble' => 'background-color: {{VALUE}};']]);
        $this->add_group_control(Group_Control_Border::get_type(), ['name' => 'reply_bubble_border', 'selector' => '{{WRAPPER}} .ev-rsvp-reply-bubble']);
        $this->add_group_control(Group_Control_Box_Shadow::get_type(), ['name' => 'reply_bubble_shadow', 'selector' => '{{WRAPPER}} .ev-rsvp-reply-bubble']);
        $this->add_control('heading_reply_author', ['label' => 'Author Name', 'type' => Controls_Manager::HEADING, 'separator' => 'before']);
        $this->add_control('reply_author_color', ['label' => 'Text Color', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .ev-rsvp-reply-author' => 'color: {{VALUE}};']]);
        $this->add_group_control(Group_Control_Typography::get_type(), ['name' => 'reply_author_typography', 'selector' => '{{WRAPPER}} .ev-rsvp-reply-author']);
        $this->add_control('heading_reply_text', ['label' => 'Reply Text', 'type' => Controls_Manager::HEADING, 'separator' => 'before']);
        $this->add_control('reply_text_color', ['label' => 'Text Color', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .ev-rsvp-reply-body' => 'color: {{VALUE}};']]);
        $this->add_group_control(Group_Control_Typography::get_type(), ['name' => 'reply_text_typography', 'selector' => '{{WRAPPER}} .ev-rsvp-reply-body']);
        $this->add_control('heading_reply_time', ['label' => 'Reply Time Text', 'type' => Controls_Manager::HEADING, 'separator' => 'before']);
        $this->add_control('reply_time_color', ['label' => 'Text Color', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .ev-rsvp-reply-footer .ev-rsvp-reply-comment-time' => 'color: {{VALUE}};']]);
        $this->add_group_control(Group_Control_Typography::get_type(), ['name' => 'reply_time_typography', 'selector' => '{{WRAPPER}} .ev-rsvp-reply-footer .ev-rsvp-reply-comment-time']);
        $this->add_control('heading_reply_badge', ['label' => 'Reply Badge', 'type' => Controls_Manager::HEADING, 'separator' => 'before']);
        $this->add_control('reply_badge_text_color', ['label' => 'Text Color', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .ev-rsvp-reply-badge' => 'color: {{VALUE}};']]);
        $this->add_control('reply_badge_bg_color', ['label' => 'Background Color', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .ev-rsvp-reply-badge' => 'background-color: {{VALUE}};']]);
        $this->add_group_control(Group_Control_Typography::get_type(), ['name' => 'reply_badge_typography', 'selector' => '{{WRAPPER}} .ev-rsvp-reply-badge']);
        $this->end_controls_section();
    }

    protected function render() {
		
		

		$settings = $this->get_settings_for_display();
		$global_reply = get_option('ev_global_rsvp_reply', 'yes');
		if ($global_reply !== 'yes') {
			$settings['enable_public_reply'] = 'no';
		}
		$post_id = get_the_ID();
		
		
		$is_paging = !empty($settings['enable_pagination']) ? $settings['enable_pagination'] : 'no';
		$per_page = !empty($settings['comments_per_page']) ? $settings['comments_per_page'] : 10;
		$link = '?post_id=' . $post_id;
		$text_prev = !empty($settings['text_btn_prev']) ? $settings['text_btn_prev'] : '← Sebelumnya';
		$text_next = !empty($settings['text_btn_next']) ? $settings['text_btn_next'] : 'Selanjutnya →';
		$labels = [
			'present' => $settings['text_btn_present'] ?? 'Hadir',
			'notpresent' => $settings['text_btn_notpresent'] ?? 'Tidak Hadir',
			'notsure' => $settings['text_btn_notsure'] ?? 'Ragu-ragu',
			'reply' => $settings['text_btn_reply'] ?? 'Balas'
		];

		
		$all_comments_for_count = get_comments([ 'post_id' => $post_id, 'parent' => 0, 'status' => 'approve' ]);
		$num = 0;
		$blocked_phrases = ['Konfirmasi Hadir', 'Konfirmasi Tidak Hadir', 'Konfirmasi Ragu-ragu', 'Konfirmasi Kehadiran'];
		
		foreach ($all_comments_for_count as $c) {
			$txt = trim(strip_tags($c->comment_content)); 
			$has_sticker = get_comment_meta($c->comment_ID, '_selected_sticker', true);
			
			
			if (!empty($has_sticker) || !in_array($txt, $blocked_phrases)) {
				$num++;
			}
		}
		
		if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) { $num = 1; }

		
		?>
		<div class="ev-comment-wrapper ev-rsvp-wrapper ev-comment-widget-mode" 
			 data-pagination="<?php echo esc_attr($is_paging); ?>" 
			 data-per-page="<?php echo esc_attr($per_page); ?>"
			 data-swal-class="swal-<?php echo esc_attr( $this->get_id() ); ?>"
			 data-badge-present-text="<?php echo esc_attr($labels['present']); ?>"
			 data-badge-notpresent-text="<?php echo esc_attr($labels['notpresent']); ?>"
			 data-badge-notsure-text="<?php echo esc_attr($labels['notsure']); ?>"
			 data-reply-button-text="<?php echo esc_attr($labels['reply']); ?>"
             data-public-reply="<?php echo esc_attr($settings['enable_public_reply'] ?? 'no'); ?>"
			 data-text-prev="<?php echo esc_attr($text_prev); ?>"
			 data-text-next="<?php echo esc_attr($text_next); ?>">

			<?php if ( 'yes' === $settings['show_main_title'] ) : ?>
				<div class="ev-rsvp-main-title-wrapper">
					<h3 class="ev-rsvp-main-title"><span class="ev-rsvp-count"><?php echo $num; ?></span> <?php echo esc_html($settings['text_main_title']); ?></h3>
				</div>
			<?php endif; ?>

			<div class="ev-rsvp-form-wrapper">
				<?php if ( 'yes' === $settings['show_form_title'] ) : ?>
					<h4 class="ev-rsvp-form-title"><?php echo esc_html($settings['text_form_title']); ?></h4>
				<?php endif; ?>

				<form id="ev-comment-form" class="ev-rsvp-form" method="post"
					  data-enable-wa-notice="<?php echo esc_attr($settings['enable_wa_notice']); ?>" 
					  data-wa-notice-name="<?php echo esc_attr($settings['wa_notice_name']); ?>" 
					  data-wa-notice-number="<?php echo esc_attr($settings['wa_notice_number']); ?>" 
					  data-wa-template="<?php echo esc_attr($settings['wa_template']); ?>">
					
					<input type="text" name="ev_phone_trap" value="" style="display:none !important;" tabindex="-1" autocomplete="off">
					<div class="ev-rsvp-field">
						<label for="ev-comment-author"><?php echo esc_html($settings['text_name_label']); ?></label>
						<input type="text" id="ev-comment-author" name="author" placeholder="<?php echo esc_attr($settings['text_name_placeholder']); ?>" required>
					</div>
					<div class="ev-rsvp-field ev-rsvp-field-comment">
						<label for="ev-comment-content"><?php echo esc_html($settings['text_comment_label']); ?></label>
						<textarea id="ev-comment-content" name="comment" placeholder="<?php echo esc_attr($settings['text_comment_placeholder']); ?>" rows="4"></textarea>
					</div>
					<?php if ( ! empty( $settings['sticker_list'] ) ) : ?>
						<div class="ev-rsvp-field">
							<?php if ( $settings['hide_sticker_button'] !== 'yes' ) : ?>
								<div class="ev-rsvp-sticker-trigger-wrapper">
									<button type="button" id="ev-comment-sticker-trigger" class="ev-rsvp-sticker-trigger">
										<i class="far fa-smile-beam"></i><span><?php echo esc_html($settings['text_btn_sticker']); ?></span>
									</button>
									<div id="ev-comment-sticker-preview"></div>
								</div>
							<?php endif; ?>
							<input type="hidden" name="selected_sticker" id="ev-comment-selected-sticker" value="">
						</div>
					<?php endif; ?>
					<div class="ev-rsvp-submit-field">
						<button type="submit" class="ev-rsvp-submit-button">
							<span class="button-text"><?php echo esc_html($settings['text_btn_submit']); ?></span>
							<div class="button-loading-content"><span class="button-loader"></span></div>
						</button>
						<input type="hidden" name="comment_post_ID" value="<?php echo $post_id; ?>">
						<input type="hidden" name="attendance" value="">
					</div>
				</form>
			</div>

			<div class="ev-rsvp-list-wrapper">
				<div class="ev-comment-list-container ev-rsvp-list-container" 
					 data-link="<?php echo esc_attr($link); ?>"
					 data-pagination="<?php echo esc_attr($is_paging); ?>" 
					 data-per-page="<?php echo esc_attr($per_page); ?>"
					 data-post-id="<?php echo esc_attr($post_id); ?>">
					
					<?php 
					
					if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
						$this->render_editor_preview($settings, $labels);
					} else {
					
						$comments = get_comments([ 'post_id' => $post_id, 'status' => 'approve', 'parent' => 0, 'number' => $per_page + 10 ]);
						
						if ($comments) {
							$displayed_count = 0;
							
							foreach ($comments as $comment) {
								
								$content = trim(strip_tags($comment->comment_content));
								$is_sticker = get_comment_meta($comment->comment_ID, '_selected_sticker', true);
								
							
								if (in_array($content, $blocked_phrases) && empty($is_sticker)) {
									continue; 
								}

								
								if ($displayed_count >= $per_page) break;

								$this->render_single_comment_html($comment, $settings, $labels);
								$displayed_count++;
							}
							
						
							if ($displayed_count === 0) {
								echo '<div class="ev-rsvp-no-comments combined" style="padding:30px; text-align:center;">
									<div class="icon-wrapper" style="font-size:30px; margin-bottom:10px; color:#ccc;"><i class="fas fa-envelope-open-text"></i></div>
									<h3 style="font-size:16px; margin:0;">Belum Ada Ucapan</h3>
									<p style="font-size:12px; color:#999;">Jadilah yang pertama memberikan Ucapan.</p>
								</div>';
							}
						} else {
							echo '<div class="ev-rsvp-no-comments combined" style="padding:30px; text-align:center;">
								<div class="icon-wrapper" style="font-size:30px; margin-bottom:10px; color:#ccc;"><i class="fas fa-envelope-open-text"></i></div>
								<h3 style="font-size:16px; margin:0;">Belum Ada Ucapan</h3>
								<p style="font-size:12px; color:#999;">Jadilah yang pertama memberikan Ucapan.</p>
							</div>';
						}
					}
					?>
					
					<div class="ev-rsvp-loader" style="display:none;"></div>
				</div>
                
                <?php 
                
                if ( $num > $per_page && $is_paging === 'yes' ) : ?>
                 <div class="ev-rsvp-pagination-nav" style="display:flex;">
                    <button class="ev-rsvp-page-btn prev" style="visibility:hidden;"><?php echo esc_html($text_prev); ?></button>
                    <button class="ev-rsvp-page-btn next" data-page="2"><?php echo esc_html($text_next); ?></button>
                 </div>
                 <?php endif; ?>
			</div>

			<?php if ( ! empty( $settings['sticker_list'] ) && $settings['hide_sticker_button'] !== 'yes' ) : ?>
				<div class="ev-comment-sticker-modal-overlay ev-rsvp-sticker-modal-overlay">
					<div class="ev-rsvp-sticker-modal">
						<h4>Pilih Stiker</h4>
						<div class="ev-rsvp-sticker-grid">
							<?php foreach ( $settings['sticker_list'] as $item ) : 
								$val = $item['sticker_icon']['value'];
								$is_svg = isset($item['sticker_icon']['library']) && $item['sticker_icon']['library'] === 'svg';
								$val = $is_svg ? $item['sticker_icon']['value']['url'] : $val;
								$type = $is_svg ? 'svg' : 'icon';
							?>
								<div class="ev-comment-modal-sticker-option ev-rsvp-modal-sticker-option" 
									 data-sticker-type="<?php echo esc_attr($type); ?>" 
									 data-sticker-value="<?php echo esc_attr($val); ?>">
									<?php \Elementor\Icons_Manager::render_icon( $item['sticker_icon'], [ 'aria-hidden' => 'true' ] ); ?>
								</div>
							<?php endforeach; ?>
						</div>
					</div>
				</div>
			<?php endif; ?>

		</div>
		<?php
	}

	
	protected function render_single_comment_html($comment, $settings, $labels) {
		$author = get_comment_author($comment);
		
		
		$comment_timestamp = get_comment_date('U', $comment);
		$human_time = human_time_diff($comment_timestamp, current_time('timestamp'));
		$date_html = sprintf( 
			'%s <br> <span style="font-weight: normal; font-size: 0.9em; opacity: 0.7;">%s ( %s yang lalu )</span>', 
			get_comment_date('d F Y', $comment), 
			get_comment_date('H.i', $comment),
			$human_time
		);

		
		$sticker_html = '';
		$sticker_json = get_comment_meta($comment->comment_ID, '_selected_sticker', true);
		if ( empty( $sticker_json ) ) {
			$sticker_json = get_comment_meta($comment->comment_ID, 'sticker', true);
		}
		if ( ! empty( $sticker_json ) ) {
			$sticker_data = json_decode($sticker_json, true);
			if ( is_array($sticker_data) ) {
				// Format A: {"type":"svg","value":"url"} or {"type":"icon","value":"class"} (from JS)
				if ( isset($sticker_data['type']) && isset($sticker_data['value']) && is_string($sticker_data['value']) ) {
					if ( $sticker_data['type'] === 'svg' || $sticker_data['type'] === 'image' ) {
						$sticker_html = '<img src="' . esc_url($sticker_data['value']) . '" style="width: 80px; height: auto; object-fit: contain;">';
					} else {
						$sticker_html = '<i class="' . esc_attr($sticker_data['value']) . '"></i>';
					}
				}
				// Format B: {"library":"svg","value":{"url":"..."}} (from Elementor)
				elseif ( isset($sticker_data['library']) && isset($sticker_data['value']) ) {
					$val = $sticker_data['value'];
					if ( $sticker_data['library'] === 'svg' && is_array($val) && isset($val['url']) ) {
						$sticker_html = '<img src="' . esc_url($val['url']) . '" style="width: 80px; height: auto; object-fit: contain;">';
					} elseif ( is_string($val) ) {
						$sticker_html = '<i class="' . esc_attr($val) . '"></i>';
					}
				}
				// Format C: {"value":"..."} only
				elseif ( isset($sticker_data['value']) && is_string($sticker_data['value']) ) {
					$val = $sticker_data['value'];
					if ( preg_match('/^https?:\/\//', $val) || strpos($val, '/') === 0 ) {
						$sticker_html = '<img src="' . esc_url($val) . '" style="width: 80px; height: auto; object-fit: contain;">';
					} else {
						$sticker_html = '<i class="' . esc_attr($val) . '"></i>';
					}
				}
			} elseif ( is_string($sticker_json) ) {
				// Format D: legacy raw HTML — sanitize with wp_kses
				if ( strpos($sticker_json, '<img') !== false || strpos($sticker_json, '<i') !== false || strpos($sticker_json, '<svg') !== false ) {
					$sticker_html = wp_kses( $sticker_json, [
						'img'  => [ 'src' => [], 'alt' => [], 'class' => [], 'style' => [], 'width' => [], 'height' => [] ],
						'i'    => [ 'class' => [], 'style' => [] ],
						'svg'  => [ 'viewBox' => [], 'fill' => [], 'xmlns' => [], 'width' => [], 'height' => [], 'class' => [], 'style' => [] ],
						'path' => [ 'd' => [], 'fill' => [], 'stroke' => [] ],
					]);
				}
				// Format E: plain URL
				elseif ( preg_match('/^https?:\/\//', $sticker_json) ) {
					$sticker_html = '<img src="' . esc_url($sticker_json) . '" style="width: 80px; height: auto; object-fit: contain;">';
				}
			}
            if ( !empty($sticker_html) ) {
                $sticker_html = '<div class="ev-rsvp-comment-sticker" style="margin-top:5px; font-size: 3rem; line-height:1; color: var(--rsvp-accent-color, #1a202c);">' . $sticker_html . '</div>';
            }
		}

		
		$attendance = get_comment_meta($comment->comment_ID, 'attendance', true);
		$badge_html = '';
		if ($attendance === 'present') $badge_html = '<span class="ev-rsvp-meta-tag status-present"><svg xmlns="https://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg> '.$labels['present'].'</span>';
		elseif ($attendance === 'notpresent') $badge_html = '<span class="ev-rsvp-meta-tag status-notpresent">'.$labels['notpresent'].'</span>';
		elseif ($attendance === 'notsure') $badge_html = '<span class="ev-rsvp-meta-tag status-notsure">'.$labels['notsure'].'</span>';

		
		$raw_body = get_comment_text($comment);
		$clean_body = str_replace('(Stiker)', '', $raw_body);
		$body_html = wpautop(trim($clean_body)) . $sticker_html;

		?>
		<div class="ev-rsvp-comment-item-wrapper" id="comment-<?php echo $comment->comment_ID; ?>">
			<div class="ev-rsvp-comment-item">
				<div class="ev-rsvp-comment-avatar ev-rsvp-gravatar-avatar"><?php echo get_avatar($comment, 40); ?></div>
				<div class="ev-rsvp-comment-content-wrapper">
					<div class="ev-rsvp-comment-header">
						<div class="ev-rsvp-author-meta">
							<span class="ev-rsvp-comment-author"><?php echo esc_html($author); ?></span>
							<?php echo $badge_html; ?>
						</div>
						<div class="ev-rsvp-header-actions">
                            <?php if ( isset($settings['enable_public_reply']) && 'yes' === $settings['enable_public_reply'] ) : ?>
                                <button class="ev-rsvp-reply-button" data-comment-id="<?php echo $comment->comment_ID; ?>"><?php echo esc_html($labels['reply']); ?></button>
                            <?php endif; ?>
                        </div>
					</div>
					<div class="ev-rsvp-comment-body"><?php echo $body_html; ?></div>
					<div class="ev-rsvp-comment-footer">
						<time class="ev-rsvp-comment-time" style="font-size: 11px; color: #a0aec0; line-height: 1.4; display:block;"><?php echo $date_html; ?></time>
						<button class="ev-rsvp-like-button" data-comment-id="<?php echo $comment->comment_ID; ?>">
							<svg xmlns="https://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
						</button>
					</div>
				</div>
			</div>
			<?php 
            $replies = get_comments(['parent' => $comment->comment_ID, 'status' => 'approve']);
            if ($replies) : ?>
            <div class="ev-rsvp-replies-wrapper">
                <?php foreach($replies as $reply) : 
                	$r_human = human_time_diff(get_comment_date('U', $reply), current_time('timestamp'));
                ?>
                <div class="ev-rsvp-public-reply">
                    <div class="ev-rsvp-reply-avatar"><?php echo get_avatar($reply, 32); ?></div>
                    <div class="ev-rsvp-reply-bubble">
                        <div class="ev-rsvp-reply-header">
                            <span class="ev-rsvp-reply-author"><?php comment_author($reply); ?></span>
                            <?php if(user_can($reply->user_id, 'manage_options')) : ?><span class="ev-rsvp-reply-badge"><?php echo esc_html($settings['reply_badge_text']); ?></span><?php endif; ?>
                        </div>
                        <div class="ev-rsvp-reply-body"><?php comment_text($reply); ?></div>
                        <div class="ev-rsvp-reply-footer">
                        	<time class="ev-rsvp-reply-comment-time">
                        		<?php echo get_comment_date('d M Y', $reply) . ' (' . $r_human . ' yang lalu)'; ?>
                        	</time>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
		</div>
		<?php
	}

	protected function render_editor_preview($settings, $labels) {
		
		?>
		<div class="ev-rsvp-comment-item-wrapper">
			<div class="ev-rsvp-comment-item">
				<div class="ev-rsvp-comment-avatar ev-rsvp-gravatar-avatar"><img src="https://ui-avatars.com/api/?background=random&name=Contoh" width="40"></div>
				<div class="ev-rsvp-comment-content-wrapper">
					<div class="ev-rsvp-comment-header">
						<div class="ev-rsvp-author-meta">
							<span class="ev-rsvp-comment-author">Contoh Nama</span>
						</div>
						<div class="ev-rsvp-header-actions">
                            <?php if ( isset($settings['enable_public_reply']) && 'yes' === $settings['enable_public_reply'] ) : ?>
                                <button class="ev-rsvp-reply-button"><?php echo esc_html($labels['reply']); ?></button>
                            <?php endif; ?>
                        </div>
					</div>
					<div class="ev-rsvp-comment-body"><p>Contoh ucapan.</p></div>
					<div class="ev-rsvp-comment-footer">
						<time class="ev-rsvp-comment-time">25 Oktober 2025 <br> 08.00</time>
						<button class="ev-rsvp-like-button"><svg xmlns="https://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg></button>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

}