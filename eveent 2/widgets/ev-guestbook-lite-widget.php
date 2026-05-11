<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class Elementor_Guestbook_Lite_Widget extends \Elementor\Widget_Base
{

    public function get_name()
    {
        return 'ev_guestbook_lite';
    }

    public function get_title()
    {
        return esc_html__('Guestbook', 'eveent-widgets');
    }

    public function get_icon()
    {
        return 'eicon-dashboard';
    }

    public function get_categories()
    {
        return ['eveent-widgets'];
    }

    public function get_script_depends()
    {
        return ['ev-guestbook-lite-handler'];
    }

    public function get_style_depends()
    {
        return ['ev-guestbook-lite-style'];
    }

    protected function register_controls()
    {

        $this->start_controls_section(
            'content_section',
            [
                'label' => esc_html__('Pengaturan Widget', 'eveent-widgets'),
                'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'login_info',
            [
                'type' => \Elementor\Controls_Manager::RAW_HTML,
                'raw'  => '<div style="background:#1e293b; color:#f8fafc; padding:12px; border-left:4px solid #3b82f6; border-radius:4px; font-size:12px; line-height:1.6;">
                            <strong style="color:#60a5fa; font-size:13px;">Auto-Login (Dynamic Tag)</strong><br>
                            Isi <strong>Slug Event</strong> dan <strong>Passkey</strong> di bawah ini (bisa via Dynamic Tag seperti ACF/JetEngine) untuk langsung membuka Dashboard tanpa melewati halaman Login.
                           </div>',
            ]
        );

        $this->add_control(
            'login_logo',
            [
                'label'   => esc_html__('Logo Login / Brand', 'eveent-widgets'),
                'type'    => \Elementor\Controls_Manager::MEDIA,
                'dynamic' => [
                    'active' => true,
                ],
            ]
        );

        $this->add_control(
            'widget_label',
            [
                'label'       => esc_html__('Label / Judul', 'eveent-widgets'),
                'type'        => \Elementor\Controls_Manager::TEXT,
                'label_block' => true,
                'default'     => 'Buku Tamu',
                'placeholder' => 'Buku Tamu',
                'dynamic'     => [ 'active' => true ],
            ]
        );

        

        $this->add_control(
            'ev_divider_hint',
            [ 'type' => \Elementor\Controls_Manager::DIVIDER ]
        );

        $this->add_control(
            'login_subtitle',
            [
                'label'       => esc_html__('Teks Login (opsional)', 'eveent-widgets'),
                'type'        => \Elementor\Controls_Manager::TEXT,
                'label_block' => true,
                'default'     => 'Masukkan passkey Anda untuk mengakses dashboard',
                'placeholder' => 'Masukkan passkey Anda untuk mengakses dashboard',
            ]
        );

        $this->add_control(
            'slug_hint',
            [
                'label'       => esc_html__('Slug Event (Link Undangan)', 'eveent-widgets'),
                'type'        => \Elementor\Controls_Manager::TEXT,
                'label_block' => true,
                'placeholder' => 'contoh: romeo-juliet',
                'description' => esc_html__('Isi ini untuk menyembunyikan input Slug dari user (mode satu event). Kosongkan untuk tampilkan input slug.', 'eveent-widgets'),
                'dynamic'     => [ 'active' => true ],
            ]
        );

        $this->add_control(
            'passkey_hint',
            [
                'label'       => esc_html__('Passkey Auto-Login (opsional)', 'eveent-widgets'),
                'type'        => \Elementor\Controls_Manager::TEXT,
                'label_block' => true,
                'placeholder' => 'contoh: EV123456',
                'description' => esc_html__('Isi ini bersamaan dengan Slug Event untuk mem-bypass halaman login sepenuhnya.', 'eveent-widgets'),
                'dynamic'     => [ 'active' => true ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'style_section',
            [
                'label' => esc_html__('Style & Warna', 'eveent-widgets'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'primary_color',
            [
                'label' => esc_html__('Warna Utama', 'eveent-widgets'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ev-lite-tab.active' => 'border-bottom-color: {{VALUE}}; color: {{VALUE}};',
                    '{{WRAPPER}} .ev-lite-btn-primary' => 'background-color: {{VALUE}}; border-color: {{VALUE}};',
                    '{{WRAPPER}} .ev-stat-icon' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .ev-lite-login-btn' => 'background-color: {{VALUE}}; border-color: {{VALUE}};',
                    '{{WRAPPER}} .ev-lite-login-icon' => 'background: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'event_info_heading',
            [
                'label' => esc_html__('Info Event (Thumb & Title)', 'eveent-widgets'),
                'type' => \Elementor\Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'event_title_typography',
                'label' => esc_html__('Tipografi Judul Event', 'eveent-widgets'),
                'selector' => '{{WRAPPER}} .ev-lite-event-title',
            ]
        );

        $this->add_control(
            'event_info_align',
            [
                'label' => esc_html__('Alignment Info Event', 'eveent-widgets'),
                'type' => \Elementor\Controls_Manager::CHOOSE,
                'options' => [
                    'left' => [
                        'title' => esc_html__('Kiri', 'eveent-widgets'),
                        'icon' => 'eicon-text-align-left',
                    ],
                    'center' => [
                        'title' => esc_html__('Tengah', 'eveent-widgets'),
                        'icon' => 'eicon-text-align-center',
                    ],
                    'right' => [
                        'title' => esc_html__('Kanan', 'eveent-widgets'),
                        'icon' => 'eicon-text-align-right',
                    ],
                ],
                'default' => 'center',
                'selectors' => [
                    '{{WRAPPER}} #lite-event-banner' => 'display: block !important; width: 100% !important; text-align: {{VALUE}} !important;',
                    '{{WRAPPER}} #lite-event-banner.align-left' => 'align-items: flex-start;',
                    '{{WRAPPER}} #lite-event-banner.align-center' => 'align-items: center;',
                    '{{WRAPPER}} #lite-event-banner.align-right' => 'align-items: flex-end;',
                ],
            ]
        );

        $this->add_control(
            'event_title_color',
            [
                'label' => esc_html__('Warna Judul Event', 'eveent-widgets'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ev-lite-event-title' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'event_thumbnail_radius',
            [
                'label' => esc_html__('Border Radius Gambar', 'eveent-widgets'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .ev-lite-event-thumbnail' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'title_align',
            [
                'label' => esc_html__('Alignment Judul', 'eveent-widgets'),
                'type' => \Elementor\Controls_Manager::CHOOSE,
                'options' => [
                    'left' => [
                        'title' => esc_html__('Left', 'eveent-widgets'),
                        'icon' => 'eicon-text-align-left',
                    ],
                    'center' => [
                        'title' => esc_html__('Center', 'eveent-widgets'),
                        'icon' => 'eicon-text-align-center',
                    ],
                    'right' => [
                        'title' => esc_html__('Right', 'eveent-widgets'),
                        'icon' => 'eicon-text-align-right',
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .ev-lite-main-title' => 'display: block !important; width: 100% !important; text-align: {{VALUE}} !important;',
                    '{{WRAPPER}} .ev-lite-login-title' => 'text-align: {{VALUE}} !important;',
                ],
            ]
        );

        $this->add_control(
            'event_thumbnail_size',
            [
                'label' => esc_html__('Ukuran Gambar Event', 'eveent-widgets'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px', '%', 'vw'],
                'range' => [
                    'px' => [ 'min' => 20, 'max' => 500, 'step' => 1 ],
                    '%' => [ 'min' => 10, 'max' => 100, 'step' => 1 ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .ev-lite-event-thumbnail' => 'width: {{SIZE}}{{UNIT}}; height: auto;',
                ],
            ]
        );

        $this->add_control(
            'text_color',
            [
                'label' => esc_html__('Warna Teks', 'eveent-widgets'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ev-lite-guestbook-container' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'title_typography',
                'label' => esc_html__('Typography Judul Utama', 'eveent-widgets'),
                'selector' => '{{WRAPPER}} .ev-lite-header h2, {{WRAPPER}} .ev-lite-login-title',
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'stats_title_typography',
                'label' => esc_html__('Typography Statistik', 'eveent-widgets'),
                'selector' => '{{WRAPPER}} .ev-lite-section-title, {{WRAPPER}} .ev-stat-label',
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'stats_value_typography',
                'label' => esc_html__('Typography Angka Statistik', 'eveent-widgets'),
                'selector' => '{{WRAPPER}} .ev-stat-value h3',
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'content_typography',
                'label' => esc_html__('Typography Konten', 'eveent-widgets'),
                'selector' => '{{WRAPPER}} .ev-lite-guestbook-container',
            ]
        );

        $this->add_control(
            'tab_style_heading',
            [
                'label' => esc_html__('Pengaturan Tab', 'eveent-widgets'),
                'type' => \Elementor\Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'tab_padding',
            [
                'label' => esc_html__('Padding Tab', 'eveent-widgets'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .ev-lite-tab' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->add_control(
            'tab_active_bg_color',
            [
                'label' => esc_html__('Warna Background Tab', 'eveent-widgets'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ev-lite-tab.active' => 'background-color: {{VALUE}};',
                    '{{WRAPPER}} .ev-lite-tab:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'tab_active_text_color',
            [
                'label' => esc_html__('Warna Teks Tab (Active)', 'eveent-widgets'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ev-lite-tab.active' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'card_style_heading',
            [
                'label' => esc_html__('Pengaturan Kartu/Statistik', 'eveent-widgets'),
                'type' => \Elementor\Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'card_padding',
            [
                'label' => esc_html__('Padding Dalam (Card Body)', 'eveent-widgets'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .ev-lite-stat-card, {{WRAPPER}} .ev-lite-add-form-wrap' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    '{{WRAPPER}} .ev-lite-accordion-body' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    '{{WRAPPER}} .ev-lite-accordion-header' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'card_margin',
            [
                'label' => esc_html__('Margin Luar Kartu', 'eveent-widgets'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .ev-lite-accordion-card, {{WRAPPER}} .ev-lite-add-form-wrap' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'card_border_radius',
            [
                'label' => esc_html__('Border Radius Kartu', 'eveent-widgets'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .ev-lite-stat-card, {{WRAPPER}} .ev-lite-accordion-card, {{WRAPPER}} .ev-lite-add-form-wrap' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'card_box_shadow',
                'label' => esc_html__('Box Shadow Kartu', 'eveent-widgets'),
                'selector' => '{{WRAPPER}} .ev-lite-stat-card, {{WRAPPER}} .ev-lite-guest-card',
            ]
        );

        $this->add_control(
            'btn_add_heading',
            [
                'label' => esc_html__('Tombol "+ Tambah Tamu Baru"', 'eveent-widgets'),
                'type' => \Elementor\Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'btn_add_border_radius',
            [
                'label' => esc_html__('Border Radius Tombol', 'eveent-widgets'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .ev-lite-toggle-add' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'btn_add_padding',
            [
                'label' => esc_html__('Padding Tombol', 'eveent-widgets'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .ev-lite-toggle-add' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->start_controls_tabs('btn_add_tabs');
        
        $this->start_controls_tab('btn_add_normal', ['label' => esc_html__('Normal', 'eveent-widgets')]);
        $this->add_control(
            'btn_add_bg_normal',
            [
                'label' => esc_html__('Background', 'eveent-widgets'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => ['{{WRAPPER}} .ev-lite-toggle-add' => 'background: {{VALUE}};']
            ]
        );
        $this->add_control(
            'btn_add_color_normal',
            [
                'label' => esc_html__('Warna Teks', 'eveent-widgets'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => ['{{WRAPPER}} .ev-lite-toggle-add' => 'color: {{VALUE}};']
            ]
        );
        $this->end_controls_tab();

        $this->start_controls_tab('btn_add_hover', ['label' => esc_html__('Hover', 'eveent-widgets')]);
        $this->add_control(
            'btn_add_bg_hover',
            [
                'label' => esc_html__('Background', 'eveent-widgets'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => ['{{WRAPPER}} .ev-lite-toggle-add:hover' => 'background: {{VALUE}};']
            ]
        );
        $this->add_control(
            'btn_add_color_hover',
            [
                'label' => esc_html__('Warna Teks', 'eveent-widgets'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => ['{{WRAPPER}} .ev-lite-toggle-add:hover' => 'color: {{VALUE}};']
            ]
        );
        $this->end_controls_tab();
        $this->end_controls_tabs();

        $this->add_control(
            'form_style_heading',
            [
                'label' => esc_html__('Pengaturan Form', 'eveent-widgets'),
                'type' => \Elementor\Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );
        
        $this->add_control(
            'input_border_radius',
            [
                'label' => esc_html__('Border Radius Input', 'eveent-widgets'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .ev-lite-form-group input, {{WRAPPER}} .ev-lite-form-group select, {{WRAPPER}} .ev-lite-form-group textarea' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    '{{WRAPPER}} .ev-lite-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function render()
    {
        $settings     = $this->get_settings_for_display();
        $post_id      = get_the_ID();
        $widget_label = esc_html($settings['widget_label'] ?? 'Buku Tamu');
        if (empty($widget_label)) $widget_label = 'Buku Tamu';
        $login_subtitle = esc_html($settings['login_subtitle'] ?? 'Masukkan passkey Anda untuk mengakses dashboard');
        $slug_hint      = sanitize_text_field($settings['slug_hint'] ?? '');
        $passkey_hint   = sanitize_text_field($settings['passkey_hint'] ?? '');
        // If admin set a slug_hint (static slug), store it for JS to use via data attribute
        ?>
        <div class="ev-lite-guestbook-container"
             data-post-id="<?php echo esc_attr($post_id); ?>"
             data-slug-hint="<?php echo esc_attr($slug_hint); ?>"
             data-passkey-hint="<?php echo esc_attr($passkey_hint); ?>"
             data-label="<?php echo esc_attr($widget_label); ?>">

            <!-- Login screen (shown by JS if no session) -->
            <div class="ev-lite-login-screen" style="display:none;">
                <div class="ev-lite-login-card">
                    <div class="ev-lite-login-logo">
                        <?php if (!empty($settings['login_logo']['url'])) : ?>
                            <img src="<?php echo esc_url($settings['login_logo']['url']); ?>" alt="Login Logo" style="max-height:60px; border-radius:12px;">
                        <?php else : ?>
                            <div class="ev-lite-login-icon">GB</div>
                        <?php endif; ?>
                    </div>
                    <h3 class="ev-lite-login-title"><?php echo $widget_label; ?></h3>
                    <p class="ev-lite-login-subtitle"><?php echo $login_subtitle; ?></p>
                    <?php if (empty($slug_hint)) : ?>
                    <div class="ev-lite-login-field">
                        <label>Slug Event</label>
                        <input type="text" id="ev-login-slug" placeholder="contoh: romeo-juliet" autocomplete="off">
                    </div>
                    <?php endif; ?>
                    <div class="ev-lite-login-field">
                        <label>Passkey</label>
                        <div class="ev-lite-passkey-wrap">
                            <input type="password" id="ev-login-passkey" placeholder="Masukkan passkey Anda" autocomplete="off">
                            <button type="button" class="ev-lite-toggle-pass" tabindex="-1">Lihat</button>
                        </div>
                    </div>
                    <div class="ev-lite-login-error" id="ev-login-error" style="display:none;"></div>
                    <button type="button" class="ev-lite-login-btn" id="ev-login-submit">Masuk</button>
                </div>
            </div>

            <!-- Dashboard (shown after login) -->
            <div class="ev-lite-dashboard" style="display:none;">

            <div class="ev-lite-header" style="position:relative; width:100%;">
                <h2 class="ev-lite-main-title" style="margin: 0; z-index: 1; position: relative;"><?php echo $widget_label; ?></h2>
                <button type="button" class="ev-lite-logout-btn" id="ev-lite-logout" title="Keluar" style="position: absolute; right: 0; top: 0;">Keluar</button>
            </div>
            <div id="lite-event-banner" style="display:none; width:100%; padding-top:14px; padding-bottom: 4px;"></div>

            <div class="ev-lite-tabs-nav">
                <button class="ev-lite-tab active" data-target="tab-guests">Daftar Tamu</button>
                <button class="ev-lite-tab" data-target="tab-stats">Statistik</button>
            </div>

            <div class="ev-lite-tabs-content">

                <!-- Tab Daftar Tamu (Add + List merged) -->
                <div class="ev-lite-tab-pane active" id="tab-guests">

                    <!-- Collapsible Add Form -->
                    <div class="ev-lite-add-section">
                        <button type="button" class="ev-lite-toggle-add open" id="lite-toggle-add-form">
                            <span><i class="fas fa-plus"></i> Tambah Tamu Baru</span>
                            <span class="ev-lite-toggle-arrow"><i class="fas fa-chevron-down"></i></span>
                        </button>
                        <div class="ev-lite-add-form-wrap" id="lite-add-form-wrap">
                            <form id="ev-lite-add-form" class="ev-lite-form">
                                <div class="ev-lite-form-group">
                                    <label>List Daftar Nama Tamu *</label>
                                    <textarea name="guest_name" required rows="4" placeholder="Gunakan baris baru / enter untuk memisahkan nama tamu." autocomplete="off"></textarea>
                                    <p class="ev-lite-hint">Gunakan baris baru / enter untuk memisahkan nama tamu.</p>
                                </div>
                                <div class="ev-lite-form-group">
                                    <label>Pilih Template Pesan WA</label>
                                    <select name="wa_template_select" id="lite-wa-template-select">
                                        <option value="">Pilih Template Teks</option>
                                    </select>
                                </div>
                                <div class="ev-lite-form-group">
                                    <div class="ev-lite-label-row">
                                        <label>Template Pesan WhatsApp</label>
                                        <span class="ev-lite-info-icon" id="lite-wa-info-toggle" title="Variabel yang tersedia"><i class="fas fa-info-circle"></i></span>
                                    </div>
                                    <div class="ev-lite-tooltip" id="lite-wa-tooltip" style="display:none;">
                                        <p><strong>Variabel yang tersedia:</strong></p>
                                        <ul>
                                            <li>[NamaTamu]</li>
                                            <li>[LinkUndangan]</li>
                                            <li>[dari]</li>
                                            <li>[sesi]</li>
                                            <li>[mempelai_pria]</li>
                                            <li>[mempelai_wanita]</li>
                                            <li>[hormat_kami]</li>
                                        </ul>
                                    </div>
                                    <textarea name="wa_template_text" id="lite-wa-template-text" rows="6" placeholder="Isi template pesan WhatsApp..."></textarea>
                                </div>
                                <div class="ev-lite-form-group">
                                    <label>Pilih Tipe Tamu</label>
                                    <select name="guest_type" id="lite-guest-type-select">
                                        <option value="Umum">Umum</option>
                                    </select>
                                </div>
                                <button type="submit" class="ev-lite-btn ev-lite-btn-primary ev-lite-btn-block">
                                    <span class="btn-text">TAMBAH DAFTAR TAMU</span>
                                    <span class="btn-loading" style="display:none;"><i class="fa fa-spinner fa-spin"></i> Menyimpan...</span>
                                </button>
                                <div id="lite-add-message" style="margin-top:15px;"></div>
                            </form>
                        </div>
                    </div>

                    <!-- Guest List -->
                    <div class="ev-lite-list-section">
                        <div class="ev-lite-filters">
                            <input type="text" id="lite-guest-search" placeholder="Cari nama tamu..." autocomplete="off">
                            <select id="lite-guest-type-filter" class="ev-lite-select">
                                <option value="">Semua Tipe</option>
                            </select>
                            <button class="ev-lite-btn ev-lite-btn-secondary" id="lite-btn-search">Cari</button>
                        </div>
                        <div class="ev-lite-loader">Memuat daftar tamu...</div>
                        <div class="ev-lite-guest-list-container" id="lite-guest-list-wrapper" style="display:none;"></div>
                        <div class="ev-lite-pagination" style="display:none; text-align:center; margin-top:15px;">
                            <span style="font-size:12px; color:#9ca3af;">Menampilkan <span id="lite-guest-count">0</span> tamu</span>
                        </div>
                    </div>
                </div>

                <!-- Tab Statistik -->
                <div class="ev-lite-tab-pane" id="tab-stats">
                    <div class="ev-lite-loader">Memuat data statistik...</div>
                    <div class="ev-lite-stats-wrapper" style="display:none;">
                        <h4 class="ev-lite-section-title" style="margin-top:30px;">Status Undangan</h4>
                        <div class="ev-lite-stats-grid">
                            <div class="ev-lite-stat-card bg-indigo-50">
                                <p class="ev-stat-label text-indigo-700">Total Undangan</p>
                                <div class="ev-stat-value text-indigo-800" style="display:flex; align-items:center;"><h3 id="lite-stat-total">0</h3></div>
                            </div>
                            <div class="ev-lite-stat-card bg-green-50">
                                <p class="ev-stat-label text-green-700">Dibuka</p>
                                <div class="ev-stat-value text-green-800" style="display:flex; align-items:center;"><h3 id="lite-stat-opened">0</h3></div>
                            </div>
                            <div class="ev-lite-stat-card bg-purple-50">
                                <p class="ev-stat-label text-purple-700">Dibagikan</p>
                                <div class="ev-stat-value text-purple-800" style="display:flex; align-items:center;"><h3 id="lite-stat-shared">0</h3></div>
                            </div>
                            <div class="ev-lite-stat-card bg-orange-50">
                                <p class="ev-stat-label text-orange-700">Belum Dikirim</p>
                                <div class="ev-stat-value text-orange-800" style="display:flex; align-items:center;"><h3 id="lite-stat-not-shared">0</h3></div>
                            </div>
                        </div>

                        <h4 class="ev-lite-section-title" style="margin-top:50px;">Statistik RSVP & Kehadiran</h4>
                        <div class="ev-lite-stats-grid">
                            <div class="ev-lite-stat-card bg-green-50">
                                <p class="ev-stat-label text-green-700">Hadir</p>
                                <div class="ev-stat-value text-green-800" style="display:flex; align-items:center;"><h3 id="lite-stat-rsvp-hadir">0</h3></div>
                            </div>
                            <div class="ev-lite-stat-card bg-red-50">
                                <p class="ev-stat-label text-red-700">Tidak Hadir</p>
                                <div class="ev-stat-value text-red-800" style="display:flex; align-items:center;"><h3 id="lite-stat-rsvp-tidak">0</h3></div>
                            </div>
                            <div class="ev-lite-stat-card bg-yellow-50">
                                <p class="ev-stat-label text-yellow-700">Masih Ragu</p>
                                <div class="ev-stat-value text-yellow-800" style="display:flex; align-items:center;"><h3 id="lite-stat-rsvp-ragu">0</h3></div>
                            </div>
                            <div class="ev-lite-stat-card bg-purple-50">
                                <p class="ev-stat-label text-purple-700">Jumlah Ucapan</p>
                                <div class="ev-stat-value text-purple-800" style="display:flex; align-items:center;"><h3 id="lite-stat-checkin">0</h3></div>
                            </div>
                        </div>

                        <h4 class="ev-lite-section-title" style="margin-top:50px;">Daftar Ucapan Terbaru</h4>
                        <div class="ev-lite-wishes-list" id="lite-wishes-wrapper" style="margin-top: 12px; display: flex; flex-direction: column; gap: 10px;">
                            <div class="ev-lite-loader">Memuat ucapan...</div>
                        </div>
                    </div>
                </div>

            </div><!-- /.ev-lite-dashboard -->

        </div><!-- /.ev-lite-guestbook-container -->
        <?php
    }
}
