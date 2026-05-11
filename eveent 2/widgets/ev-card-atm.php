<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Background;
use Elementor\Icons_Manager;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Repeater;

class Eveent_EV_Card_ATM_Widget extends Widget_Base {

    public function get_name() { return 'ev-atm-card'; }
    public function get_title() { return esc_html__( 'EV ATM Card', 'eveent-widgets' ); }
    public function get_icon() { return 'fas fa-credit-card'; }
    public function get_categories() { return [ 'eveent-widgets' ]; }
    public function get_keywords() { return [ 'copy', 'clipboard', 'card', 'atm', 'credit', 'member', 'ewf', 'rekening', 'flip', 'ucapan' ]; }
    public function get_style_depends() { return ['ewf-card-atm-style', 'ev-rsvp-style']; }
    public function get_script_depends() { return [ 'ev-card-atm-handler' ]; }

    private function get_jet_engine_queries() {
        if ( defined( 'ELEMENTOR_EDITOR' ) && ELEMENTOR_EDITOR ) {
            return [ '' => esc_html__( 'Query loading disabled in Editor mode for stability', 'eveent-widgets' ) ];
        }
        if ( ! class_exists( '\Jet_Engine\Query_Builder\Manager' ) || ! defined( 'ELEMENTOR_VERSION' ) ) {
            return [ '' => esc_html__( 'JetEngine not installed or active', 'eveent-widgets' ) ];
        }

        $queries = \Jet_Engine\Query_Builder\Manager::instance()->get_queries();
        $options = [ '' => esc_html__( 'Select Query', 'eveent-widgets' ) ];
        if ( empty( $queries ) ) {
            return [ '' => esc_html__( 'No Queries Found', 'eveent-widgets' ) ];
        }
        foreach ( $queries as $query ) {
            $options[ $query->id ?? '' ] = $query->name ?? esc_html__( 'Unnamed Query', 'eveent-widgets' );
        }
        return array_filter( $options );
    }

    protected function _register_controls() {
        $default_confirm_enable = get_option('ev_gift_default_confirmation_enable') === 'yes' ? 'yes' : '';
        $default_wa_enable = get_option('ev_gift_wa_default_confirmation_enable') === 'yes' ? 'yes' : '';
        $default_wa_name = get_option('ev_gift_wa_default_name') ?: '';
        $default_wa_number = get_option('ev_gift_wa_default_number') ?: '';
        $default_wa_template = get_option('ev_gift_wa_default_template') ?: "*Notifikasi Hadiah Pernikahan*\n*[post_title]*\n\nHalo Kak [client_name],\nAnda menerima hadiah baru:\n\n*Dari:* [guest_name]\n*Jumlah:* [amount]\n*Ke Rekening:* [account_name]\n\n---------------------\nPesan ini dikirim otomatis.";
        
        
        $this->start_controls_section('section_version_control', [ 'label' => esc_html__( 'Mode Widget', 'eveent-widgets' ), ]);
        $this->add_control('mode_widget_heading', [ 'label' => esc_html__( 'Pengaturan Dasar Widget', 'eveent-widgets' ), 'type' => Controls_Manager::HEADING, ]);
        $this->add_control('widget_version', [
            'label' => esc_html__( 'Mode ATM Card', 'eveent-widgets' ), 'type' => Controls_Manager::SELECT, 'label_block' => true,
            'options' => [ 'v1' => esc_html__( 'Single (Satu Kartu)', 'eveent-widgets' ), 'v2' => esc_html__( 'Repeater (Jet Engine Query)', 'eveent-widgets' ), ],
            'default' => 'v1', 'description' => esc_html__('Pilih mode tampilan untuk widget ATM Card.', 'eveent-widgets'),
        ]);
        $this->add_control('mode_widget_divider', [ 'type' => Controls_Manager::DIVIDER, ]);
        $this->end_controls_section();
        
        
        $this->start_controls_section('section_card_content_v1', [
            'label' => esc_html__( 'Card Content (Single)', 'eveent-widgets' ), 'condition' => [ 'widget_version' => 'v1', ],
        ]);
        $this->add_control('card_title', [ 'label' => esc_html__( 'Card Title', 'eveent-widgets' ), 'type' => Controls_Manager::TEXT, 'default' => 'Gift Card', 'dynamic' => ['active' => true], ]);
        $this->add_control('card_logo', [ 'label' => esc_html__( 'Card Logo', 'eveent-widgets' ), 'type' => Controls_Manager::MEDIA, 'dynamic' => ['active' => true], ]);
        $this->add_control('card_number_label', [ 'label' => esc_html__( 'Card Number Label', 'eveent-widgets' ), 'type' => Controls_Manager::TEXT, 'default' => 'Nomor Rekening', 'dynamic' => ['active' => true], ]);
        $this->add_control('card_number', [ 'label' => esc_html__( 'Card Number', 'eveent-widgets' ), 'type' => Controls_Manager::TEXTAREA, 'default' => '123 456 7890', 'dynamic' => ['active' => true], ]);
        $this->add_control('show_qr_code', [ 'label' => esc_html__( 'Tampilkan QR Code / QRIS', 'eveent-widgets' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'no', 'separator' => 'before', 'description' => esc_html__('Aktifkan untuk menampilkan gambar QR Code di kartu, menggantikan Nomor Rekening.', 'eveent-widgets'), ]);
        $this->add_control('qr_code_image', [ 'label' => esc_html__( 'Gambar QR Code / QRIS', 'eveent-widgets' ), 'type' => Controls_Manager::MEDIA, 'dynamic' => ['active' => true], 'condition' => [ 'show_qr_code' => 'yes', ], ]);
        $this->add_control('qr_label_above_barcode', [ 'label' => esc_html__( 'Label di Atas QRIS', 'eveent-widgets' ), 'type' => Controls_Manager::TEXT, 'default' => 'Scan untuk Kirim Hadiah', 'dynamic' => ['active' => true], 'condition' => [ 'show_qr_code' => 'yes', 'widget_version' => 'v1', ], ]);
        $this->add_control('qr_instruction_above_barcode', [ 'label' => esc_html__( 'Instruksi di Atas QRIS', 'eveent-widgets' ), 'type' => Controls_Manager::TEXTAREA, 'rows' => 3, 'default' => 'Gunakan QRIS di bawah ini untuk mengirim hadiah digital. Pastikan nama penerima sesuai, jika ragu silakan hubungi pemilik undangan', 'dynamic' => ['active' => true], 'condition' => [ 'show_qr_code' => 'yes', 'widget_version' => 'v1', ], ]);
        $this->add_control('qr_code_label', [ 'label' => esc_html__( 'Teks di bawah QR (Akan Disembunyikan)', 'eveent-widgets' ), 'type' => Controls_Manager::TEXT, 'default' => 'Scan QRIS untuk transfer', 'dynamic' => ['active' => true], 'condition' => [ 'show_qr_code' => 'yes', ], ]);
        $this->add_control('footer_left_label', [ 'label' => esc_html__( 'Left Label', 'eveent-widgets' ), 'type' => Controls_Manager::TEXT, 'default' => 'Nama Pemilik', 'dynamic' => ['active' => true], ]);
        $this->add_control('footer_left_value', [ 'label' => esc_html__( 'Account Name', 'eveent-widgets' ), 'type' => Controls_Manager::TEXT, 'default' => 'Fulan', 'dynamic' => ['active' => true], ]);
        $this->add_control('footer_right_label', [ 'label' => esc_html__( 'Event Label', 'eveent-widgets' ), 'type' => Controls_Manager::TEXT, 'default' => 'Event', 'dynamic' => ['active' => true], ]);
        $this->add_control('footer_right_value', [ 'label' => esc_html__( 'Event Value', 'eveent-widgets' ), 'type' => Controls_Manager::TEXT, 'default' => '01/24', 'dynamic' => ['active' => true], ]);
        $this->end_controls_section();
        
        
        $this->start_controls_section('section_card_content_v2', [ 'label' => esc_html__( 'Card Content (Repeater)', 'eveent-widgets' ), 'condition' => [ 'widget_version' => 'v2' ], ]);
        $this->add_control('jet_engine_query_id', [ 'label' => esc_html__( 'Pilih Query', 'eveent-widgets' ), 'type' => Controls_Manager::SELECT, 'options' => $this->get_jet_engine_queries(), 'description' => esc_html__('Pilih Repeater Query dari JetEngine.', 'eveent-widgets'), ]);
        $this->add_control('heading_v2_mapping', [ 'label' => esc_html__( 'Field Repeater', 'eveent-widgets' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before', ]);
        $this->add_control('v2_bank_field', [ 'label' => esc_html__( 'Field Bank', 'eveent-widgets' ), 'type' => Controls_Manager::TEXT, 'default' => '_bank', ]);
        $this->add_control('v2_account_name_field', [ 'label' => esc_html__( 'Field Nama Pemilik', 'eveent-widgets' ), 'type' => Controls_Manager::TEXT, 'default' => '_nama', ]);
        $this->add_control('v2_account_number_field', [ 'label' => esc_html__( 'Field Nomor Rekening', 'eveent-widgets' ), 'type' => Controls_Manager::TEXT, 'default' => '_rekening', ]);
        $this->add_control('v2_qr_mode_enable', [ 'label' => esc_html__( 'Aktifkan QR/QRIS Mode di Repeater', 'eveent-widgets' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'no', 'separator' => 'before', ]);
        $this->add_control('v2_qr_image_field', [ 'label' => esc_html__( 'Field URL Gambar QR', 'eveent-widgets' ), 'type' => Controls_Manager::TEXT, 'default' => '_qr_image_url', 'condition' => [ 'v2_qr_mode_enable' => 'yes', ] ]);
        $this->add_control('qr_label_above_barcode_v2', [ 'label' => esc_html__( 'Label di Atas QRIS (Global)', 'eveent-widgets' ), 'type' => Controls_Manager::TEXT, 'default' => 'Scan untuk Kirim Hadiah', 'dynamic' => ['active' => true], 'condition' => [ 'v2_qr_mode_enable' => 'yes', ], 'name' => 'qr_label_above_barcode', 'separator' => 'before', ]);
        $this->add_control('qr_instruction_above_barcode_v2', [ 'label' => esc_html__( 'Instruksi di Atas QRIS (Global)', 'eveent-widgets' ), 'type' => Controls_Manager::TEXTAREA, 'rows' => 3, 'default' => 'Silakan Scan QR Qris dibawah, dan pastikan nama penerima sesuai, jika ragu silakan hubungi pemilik undangan', 'dynamic' => ['active' => true], 'condition' => [ 'v2_qr_mode_enable' => 'yes', ], 'name' => 'qr_instruction_above_barcode', ]);
        $repeater = new Repeater();
        $repeater->add_control('card_number_label', [ 'label' => 'Label No. Rekening', 'type' => Controls_Manager::TEXT, 'default' => 'Nomor Rekening' ]);
        $repeater->add_control('footer_left_label', [ 'label' => 'Label Nama Pemilik', 'type' => Controls_Manager::TEXT, 'default' => 'Nama Pemilik' ]);
        $repeater->add_control('footer_right_label', [ 'label' => 'Label Kanan Bawah', 'type' => Controls_Manager::TEXT, 'default' => 'Berlaku Sejak' ]);
        $repeater->add_control('footer_right_value', [ 'label' => 'Nilai Kanan Bawah', 'type' => Controls_Manager::TEXT, 'default' => '01/24' ]);
        $this->add_control('card_template_repeater', [
            'label' => esc_html__( 'Label Card ATM', 'eveent-widgets' ), 'type' => Controls_Manager::REPEATER, 'fields' => $repeater->get_controls(),
            'default' => [ [ 'card_number_label' => 'Nomor Rekening', 'footer_left_label' => 'Nama Pemilik', 'footer_right_label' => 'Event', 'footer_right_value' => '01/29', ] ], 'title_field' => 'ATM Card',
        ]);
        $this->end_controls_section();
        
        
        $this->start_controls_section('section_card_back_content', [ 'label' => esc_html__( 'Card Back Content (Flip)', 'eveent-widgets' ), ]);
        $this->add_control('enable_flip_effect', [ 'label' => esc_html__( 'Enable Flip Effect', 'eveent-widgets' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => '', 'description' => esc_html__( 'Jika aktif, kartu akan memiliki sisi belakang dan bisa dibalik.', 'eveent-widgets' ), ]);
        $this->add_control('card_back_title', [ 'label' => esc_html__( 'Back Title', 'eveent-widgets' ), 'type' => Controls_Manager::TEXT, 'default' => 'Terima Kasih', 'dynamic' => ['active' => true], 'condition' => ['enable_flip_effect' => 'yes'], ]);
        $this->add_control('card_back_image', [ 'label' => esc_html__( 'Back Image', 'eveent-widgets' ), 'type' => Controls_Manager::MEDIA, 'dynamic' => ['active' => true], 'condition' => ['enable_flip_effect' => 'yes'], ]);
        $this->add_control('card_back_description', [ 'label' => esc_html__( 'Back Description', 'eveent-widgets' ), 'type' => Controls_Manager::WYSIWYG, 'default' => 'Kehadiran Anda membuat momen ini lebih istimewa.', 'dynamic' => ['active' => true], 'condition' => ['enable_flip_effect' => 'yes'], ]);
        $this->end_controls_section();

        
        $this->start_controls_section('section_copy_button', [ 'label' => esc_html__( 'Copy Button', 'eveent-widgets' ) ]);
        $this->add_control('show_copy_button', [ 'label' => esc_html__( 'Show Copy Button', 'eveent-widgets' ), 'type' => Controls_Manager::SWITCHER, 'label_on' => 'Show', 'label_off' => 'Hide', 'return_value' => 'yes', 'default' => 'yes' ]);
        $this->add_control('copy_button_text', [ 'label' => esc_html__( 'Button Text', 'eveent-widgets' ), 'type' => Controls_Manager::TEXT, 'default' => 'Salin', 'condition' => ['show_copy_button' => 'yes'] ]);
        $this->add_control('copy_button_icon', [ 'label' => esc_html__('Button Icon', 'eveent-widgets'), 'type' => Controls_Manager::ICONS, 'default' => [ 'value' => 'far fa-copy', 'library' => 'fa-regular' ], 'condition' => ['show_copy_button' => 'yes'] ]);
        $this->add_control('copy_button_icon_align', [ 'label' => esc_html__('Icon Position', 'eveent-widgets'), 'type' => Controls_Manager::SELECT, 'default' => 'before', 'options' => [ 'before' => 'Before', 'after' => 'After' ], 'condition' => ['copy_button_icon[value]!' => ''] ]);
        $this->add_control('copy_button_icon_indent', [ 'label' => esc_html__('Icon Spacing', 'eveent-widgets'), 'type' => Controls_Manager::SLIDER, 'range' => [ 'px' => [ 'max' => 50 ] ], 'default' => ['size' => 5], 'selectors' => [ '{{WRAPPER}} .ewf-copy-button .ewf-button-icon-after' => 'margin-left: {{SIZE}}{{UNIT}};', '{{WRAPPER}} .ewf-copy-button .ewf-button-icon-before' => 'margin-right: {{SIZE}}{{UNIT}};' ], 'condition' => ['copy_button_icon[value]!' => ''] ]);
        $this->end_controls_section();

       
        $this->start_controls_section('section_gift_confirmation', [ 'label' => esc_html__( 'Confirmation & WA Notif', 'eveent-widgets' ) ]);
        $this->add_control( 'enable_gift_confirmation', [ 'label' => esc_html__( 'Enable Gift Confirmation', 'eveent-widgets' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => $default_confirm_enable, 'description' => esc_html__( 'Jika aktif, akan muncul tombol "Konfirmasi Transfer" di pop-up setelah menyalin nomor.', 'eveent-widgets' ), ] );
        $this->add_control( 'confirm_button_text', [ 'label' => esc_html__( 'Confirmation Button Text', 'eveent-widgets' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( 'Konfirmasi Transfer', 'eveent-widgets' ), 'condition' => [ 'enable_gift_confirmation' => 'yes', ], 'dynamic' => [ 'active' => true, ], ] );
        $this->add_control( 'hr_notif', [ 'type' => Controls_Manager::DIVIDER, 'separator' => 'before', 'condition' => [ 'enable_gift_confirmation' => 'yes', ], ] );
        $this->add_control( 'enable_wa_notice', [ 'label' => esc_html__( 'Send WA Notification to Client', 'eveent-widgets' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => $default_wa_enable, 'condition' => [ 'enable_gift_confirmation' => 'yes', ], ] );
        $this->add_control( 'wa_notice_name', [ 'label' => esc_html__( 'Client Name', 'eveent-widgets' ), 'type' => Controls_Manager::TEXT, 'default' => $default_wa_name, 'condition' => [ 'enable_gift_confirmation' => 'yes', 'enable_wa_notice' => 'yes', ], 'dynamic' => [ 'active' => true ], ] );
        $this->add_control( 'wa_notice_number', [ 'label' => esc_html__( 'Client WA Number', 'eveent-widgets' ), 'type' => Controls_Manager::TEXT, 'default' => $default_wa_number, 'condition' => [ 'enable_gift_confirmation' => 'yes', 'enable_wa_notice' => 'yes', ], 'dynamic' => [ 'active' => true ], ] );
        $this->add_control( 'wa_template', [ 'label' => esc_html__( 'WA Message Template', 'eveent-widgets' ), 'type' => Controls_Manager::TEXTAREA, 'rows' => 10, 'default' => $default_wa_template, 'condition' => [ 'enable_gift_confirmation' => 'yes', 'enable_wa_notice' => 'yes', ], ] );
        $this->add_control('enable_digital_gift_api', [ 'label' => esc_html__( 'Enable Digital Gift Eveent', 'eveent-widgets' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'no', 'condition' => [ 'enable_gift_confirmation' => 'yes', ], ]);
        $this->add_control('digital_gift_api_warning', [
            'type' => Controls_Manager::RAW_HTML, 'raw' => '<strong>PERHATIAN <br></strong>Pastikan Buku Tamu untuk acara ini telah dibuat.',
            'content_classes' => 'elementor-panel-alert elementor-panel-alert-warning', 'condition' => [ 'enable_gift_confirmation' => 'yes', 'enable_digital_gift_api' => 'yes', ],
        ]);
        $this->end_controls_section();

        
        $this->start_controls_section('section_success_alert', [ 'label' => esc_html__( 'Notif Confirm', 'eveent-widgets' ) ]);
        $this->add_control('show_sweetalert', [ 'label' => esc_html__('Gunakan Notifikasi Pop-up', 'eveent-widgets'), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes' ]);
        $this->add_control('copy_button_success_text', [ 'label' => esc_html__( 'Success Text (Jika Pop-up Nonaktif)', 'eveent-widgets' ), 'type' => Controls_Manager::TEXT, 'default' => 'Tersalin!', 'condition' => ['show_sweetalert!' => 'yes'] ]);
        $this->add_control('sweetalert_title', [ 'label' => esc_html__( 'Title Notif', 'eveent-widgets' ), 'type' => Controls_Manager::TEXT, 'default' => 'Nomor Rekening disalin', 'condition' => ['show_sweetalert' => 'yes'], 'dynamic' => [ 'active' => true, ] ]);
        $this->add_control('sweetalert_text', [ 'label' => esc_html__( 'Message', 'eveent-widgets' ), 'type' => Controls_Manager::TEXTAREA, 'default' => '<b>Mohon diperhatikan</b>:<br>Pastikan nama penerima dan nomor rekening sesuai <br> Jika ragu, silakan hubungi mempelai terlebih dahulu sebelum melakukan pengiriman', 'condition' => ['show_sweetalert' => 'yes'], 'dynamic' => [ 'active' => true, ] ]);
        $this->add_control('sa_timer', [ 'label' => esc_html__( 'Duration (ms)', 'eveent-widgets' ), 'type' => Controls_Manager::SLIDER, 'default' => [ 'size' => 5000 ], 'range' => [ 'px' => [ 'min' => 0, 'max' => 10000, 'step' => 500 ] ], 'description' => '0 = tidak akan tertutup otomatis.', 'condition' => ['show_sweetalert' => 'yes'] ]);
        $this->add_control('sa_icon_type', [ 'label' => 'Icon Type', 'type' => Controls_Manager::CHOOSE, 'options' => [ 'default' => [ 'title' => 'Default', 'icon' => 'eicon-star' ], 'image' => [ 'title' => 'Custom Image', 'icon' => 'eicon-image-bold' ] ], 'default' => 'default', 'toggle' => false, 'condition' => ['show_sweetalert' => 'yes']]);
        $this->add_control('sweetalert_icon', [ 'label' => 'Icon', 'type' => Controls_Manager::SELECT, 'default' => 'success', 'options' => [ 'success' => 'Success', 'error' => 'Error', 'warning' => 'Warning', 'info' => 'Info' ], 'condition' => [ 'sa_icon_type' => 'default', 'show_sweetalert' => 'yes' ] ]);
        $this->add_control('sa_custom_image', [ 'label' => 'Choose Image', 'type' => Controls_Manager::MEDIA, 'condition' => [ 'sa_icon_type' => 'image', 'show_sweetalert' => 'yes' ] ]);
        $this->end_controls_section();
        
        // --- STYLE CONTROL ---
        
        $this->start_controls_section('section_card_style', [ 'label' => esc_html__( 'Card Style', 'eveent-widgets' ), 'tab' => Controls_Manager::TAB_STYLE ]);
        $this->add_control('card_skin', [ 'label' => esc_html__( 'Card Skin', 'eveent-widgets' ), 'type' => Controls_Manager::SELECT, 'default' => 'none', 'options' => [ 'none' => esc_html__( 'None (Gaya Kustom)', 'eveent-widgets' ), 'skin-dark' => esc_html__( 'Modern Dark', 'eveent-widgets' ), 'skin-gold' => esc_html__( 'Classic Gold', 'eveent-widgets' ), 'skin-light' => esc_html__( 'Light Minimalist', 'eveent-widgets' ), ], 'selectors' => ['{{WRAPPER}} .elementor-control-card_skin' => 'display: none;',], 'prefix_class' => 'ewf-', ]);
        $this->add_responsive_control('card_layout_direction', [ 'label' => esc_html__( 'Susunan Kartu', 'eveent-widgets' ), 'type' => Controls_Manager::CHOOSE, 'options' => [ 'row' => [ 'title' => esc_html__( 'Menyamping (Horizontal)', 'eveent-widgets' ), 'icon' => 'eicon-h-align-stretch', ], 'column' => [ 'title' => esc_html__( 'Ke Bawah (Vertikal)', 'eveent-widgets' ), 'icon' => 'eicon-v-align-stretch', ], ], 'default' => 'column', 'toggle' => false, 'selectors' => [ '{{WRAPPER}} .ewf-atm-card-list' => 'flex-direction: {{VALUE}};', ], 'condition' => [ 'widget_version' => 'v2', ], 'description' => esc_html__('Pilih susunan kartu. Jika memilih vertikal, pastikan mengatur lebar kartu (Card Width) menjadi 100% untuk hasil terbaik.', 'eveent-widgets'), ]);
        $this->add_responsive_control('card_alignment_v1', [ 'label' => esc_html__( 'Card Alignment', 'eveent-widgets' ), 'type' => Controls_Manager::CHOOSE, 'options' => [ 'flex-start' => [ 'title' => 'Left', 'icon' => 'eicon-h-align-left' ], 'center' => [ 'title' => 'Center', 'icon' => 'eicon-h-align-center' ], 'flex-end' => [ 'title' => 'Right', 'icon' => 'eicon-h-align-right' ] ], 'default' => 'center', 'selectors' => [ '{{WRAPPER}} .ewf-atm-card-list' => 'display: flex; justify-content: {{VALUE}};', '{{WRAPPER}} .ewf-atm-card-widget-wrapper' => 'display: inline-block;' ], 'condition' => [ 'widget_version' => 'v1', ], 'description' => 'Atur perataan horizontal kartu di dalam kolom.' ]);
        $this->add_responsive_control('card_alignment_horizontal', [ 'label' => esc_html__( 'Card Alignment', 'eveent-widgets' ), 'type' => Controls_Manager::CHOOSE, 'options' => [ 'flex-start' => [ 'title' => 'Left', 'icon' => 'eicon-h-align-left' ], 'center' => [ 'title' => 'Center', 'icon' => 'eicon-h-align-center' ], 'flex-end' => [ 'title' => 'Right', 'icon' => 'eicon-h-align-right' ] ], 'default' => 'center', 'selectors' => [ '{{WRAPPER}} .ewf-atm-card-list' => 'justify-content: {{VALUE}};', '{{WRAPPER}} .ewf-atm-card-widget-wrapper' => 'display: inline-block;' ], 'condition' => [ 'widget_version' => 'v2', 'card_layout_direction' => 'row', ], 'description' => 'Atur perataan horizontal kartu di dalam kolom.' ]);
        $this->add_responsive_control('card_alignment_vertical', [ 'label' => esc_html__( 'Card Alignment', 'eveent-widgets' ), 'type' => Controls_Manager::CHOOSE, 'options' => [ 'flex-start' => [ 'title' => 'Left', 'icon' => 'eicon-h-align-left' ], 'center' => [ 'title' => 'Center', 'icon' => 'eicon-h-align-center' ], 'flex-end' => [ 'title' => 'Right', 'icon' => 'eicon-h-align-right' ] ], 'default' => 'center', 'selectors' => [ '{{WRAPPER}} .ewf-atm-card-list' => 'align-items: {{VALUE}};', '{{WRAPPER}} .ewf-atm-card-widget-wrapper' => 'display: block;' ], 'condition' => [ 'widget_version' => 'v2', 'card_layout_direction' => 'column', ], 'description' => 'Atur perataan horizontal kartu di dalam kolom.' ]);
        $this->add_responsive_control('card_gap', [ 'label' => esc_html__( 'Gap Antar Kartu', 'eveent-widgets' ), 'type' => Controls_Manager::SLIDER, 'size_units' => ['px', 'em', '%'], 'default' => [ 'size' => 15, 'unit' => 'px' ], 'selectors' => [ '{{WRAPPER}} .ewf-atm-card-list' => 'display: flex; flex-wrap: wrap; gap: {{SIZE}}{{UNIT}};', ], ]);
        $this->add_responsive_control('card_max_width', [ 'label' => esc_html__( 'Card Max Width', 'eveent-widgets' ), 'type' => Controls_Manager::SLIDER, 'size_units' => ['px', 'vw'], 'range' => [ 'px' => [ 'min' => 250, 'max' => 1200 ] ], 'default' => ['unit' => 'px', 'size' => 450], 'selectors' => [ '{{WRAPPER}} .ewf-atm-card-widget-wrapper' => 'max-width: {{SIZE}}{{UNIT}};' ], 'description' => 'Atur batas lebar maksimal kartu agar tidak overflow.' ]);
         $this->add_responsive_control('card_width', [ 
        'label' => esc_html__( 'Card Width', 'eveent-widgets' ), 
        'type' => Controls_Manager::SLIDER, 
        'size_units' => ['px', '%', 'vw'], 
        'range' => [ 
            'px' => [ 'min' => 100, 'max' => 1200 ], 
            '%' => ['min' => 20, 'max' => 100], 
            'vw' => ['min' => 20, 'max' => 100] 
        ], 
        'default' => ['unit' => 'px', 'size' => 450], 
        'devices' => [ 'desktop', 'tablet', 'mobile' ], 
        'mobile_default' => [ 
            'unit' => '%',
            'size' => 100, 
        ],
        
        'selectors' => [ 
            '{{WRAPPER}} .ewf-atm-card-widget-wrapper' => 'width: {{SIZE}}{{UNIT}};', 
        ] 
    ]);
        $this->add_responsive_control('card_height_global', [ 
            'label' => esc_html__( 'Card Height (Global)', 'eveent-widgets' ), 'type' => Controls_Manager::SLIDER, 'size_units' => ['px', 'vh', 'em'], 
            'range' => [ 'px' => [ 'min' => 100, 'max' => 500 ], 'vh' => [ 'min' => 10, 'max' => 100 ], ], 'default' => [ 'unit' => 'px', 'size' => 250 ], 
            'selectors' => [ '{{WRAPPER}} .ewf-flip-card-container' => 'height: {{SIZE}}{{UNIT}};', '{{WRAPPER}} .ewf-card-front' => 'height: {{SIZE}}{{UNIT}};', ],
        ]);
        $this->add_group_control(Group_Control_Background::get_type(), [ 'name' => 'card_background', 'types' => [ 'classic', 'gradient' ], 'selector' => '{{WRAPPER}} .ewf-card-front' ]);
        $this->add_responsive_control('card_padding', [ 'label' => esc_html__( 'Padding', 'eveent-widgets' ), 'type' => Controls_Manager::DIMENSIONS, 'size_units' => [ 'px', '%' ], 'default' => ['top'=>20,'right'=>25,'bottom'=>20,'left'=>25,'unit'=>'px'], 'selectors' => [ '{{WRAPPER}} .ewf-card-front, {{WRAPPER}} .ewf-card-back' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ] ]);
        $this->add_control('card_border_radius', [ 'label' => esc_html__( 'Border Radius', 'eveent-widgets' ), 'type' => Controls_Manager::DIMENSIONS, 'default' => [ 'top' => 8, 'right' => 8, 'bottom' => 8, 'left' => 8, 'unit' => 'px' ], 'selectors' => [ '{{WRAPPER}} .ewf-card-front, {{WRAPPER}} .ewf-card-back' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ] ]);
       
        $this->add_control('emboss_effect', [ 'label' => esc_html__( 'Emboss Effect on Text', 'eveent-widgets' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'ewf-card-emboss', 'default' => 'ewf-card-emboss', 'prefix_class' => '' ]);
        
        $this->add_control('heading_card_text', [ 'label' => esc_html__( 'Front Card Text', 'eveent-widgets' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ]);

        
        $this->add_group_control(Group_Control_Typography::get_type(), [
          'name' => 'title_typography',
          'label' => 'Card Title (Typography)',
          'selector' => '{{WRAPPER}} .ewf-card-title', 
          'fields_options' => [
            'font_color' => [ 'type' => Controls_Manager::HIDDEN ],
          ]
        ]);
        $this->add_control('title_color', [
          'label' => esc_html__( 'Card Title Color', 'eveent-widgets' ),
          'type' => Controls_Manager::COLOR,
          'selectors' => [
           
            '{{WRAPPER}}.ewf-none .ewf-card-title' => 'color: {{VALUE}}'
          ],
          'separator' => 'after',
        ]);
    
       
        $this->add_group_control(Group_Control_Typography::get_type(), [
          'name' => 'number_label_typography',
          'label' => 'Title Card Number (Typography)',
          'selector' => '{{WRAPPER}} .ewf-card-number-label',
          'fields_options' => [
            'font_color' => [ 'type' => Controls_Manager::HIDDEN ],
          ]
        ]);
        $this->add_control('number_label_color', [
          'label' => esc_html__( 'Title Card Number Color', 'eveent-widgets' ),
          'type' => Controls_Manager::COLOR,
          'selectors' => [
            '{{WRAPPER}}.ewf-none .ewf-card-number-label' => 'color: {{VALUE}}'
          ],
          'separator' => 'after',
        ]);
    
        
        $this->add_group_control(Group_Control_Typography::get_type(), [
          'name' => 'number_typography',
          'label' => 'Card Number (Typography)',
          'selector' => '{{WRAPPER}} .ewf-card-number',
          'fields_options' => [
            'font_size' => ['default' => ['unit' => 'px', 'size' => 14]],
            'font_color' => [ 'type' => Controls_Manager::HIDDEN ],
          ]
        ]);
        $this->add_control('number_color', [
          'label' => esc_html__( 'Card Number Color', 'eveent-widgets' ),
          'type' => Controls_Manager::COLOR,
          'selectors' => [
            '{{WRAPPER}}.ewf-none .ewf-card-number' => 'color: {{VALUE}}'
          ],
          'separator' => 'after',
        ]);
    
      
        $this->add_group_control(Group_Control_Typography::get_type(), [
          'name' => 'footer_label_typography',
          'label' => 'Label Left & Event (Typography)',
          'selector' => '{{WRAPPER}} .ewf-card-footer-label',
          'fields_options' => [
            'font_color' => [ 'type' => Controls_Manager::HIDDEN ],
          ]
        ]);
        $this->add_control('footer_label_color', [
          'label' => esc_html__( 'Label Left & Event Color', 'eveent-widgets' ),
          'type' => Controls_Manager::COLOR,
          'selectors' => [
            '{{WRAPPER}}.ewf-none .ewf-card-footer-label' => 'color: {{VALUE}}'
          ],
          'separator' => 'after',
        ]);
    
       
        $this->add_group_control(Group_Control_Typography::get_type(), [
          'name' => 'footer_value_typography',
          'label' => 'Account Name & Event (Typography)',
          'selector' => '{{WRAPPER}} .ewf-card-footer-value',
          'fields_options' => [
            'font_color' => [ 'type' => Controls_Manager::HIDDEN ],
          ]
        ]);
        $this->add_control('footer_value_color', [
          'label' => esc_html__( 'Account Name & Event Color', 'eveent-widgets' ),
          'type' => Controls_Manager::COLOR,
          'selectors' => [
            '{{WRAPPER}}.ewf-none .ewf-card-footer-value' => 'color: {{VALUE}}'
          ],
        ]);
        
        $this->add_control('heading_card_back_style', [ 'label' => esc_html__( 'Card Back Style', 'eveent-widgets' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before', 'condition' => ['enable_flip_effect' => 'yes'] ]);
        $this->add_group_control(Group_Control_Background::get_type(), [ 'name' => 'card_back_background', 'types' => ['classic', 'gradient'], 'selector' => '{{WRAPPER}} .ewf-card-back', 'condition' => ['enable_flip_effect' => 'yes'] ]);
        $this->add_control('card_back_text_color', [ 'label' => esc_html__( 'Back Text Color', 'eveent-widgets' ), 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .ewf-card-back, {{WRAPPER}} .ewf-card-back h3, {{WRAPPER}} .ewf-card-back div' => 'color: {{VALUE}}'], 'condition' => ['enable_flip_effect' => 'yes'] ]);
        $this->add_group_control(Group_Control_Typography::get_type(), [ 'name' => 'back_title_typography', 'label' => 'Back Title Typography', 'selector' => '{{WRAPPER}} .ewf-card-back-title', 'condition' => ['enable_flip_effect' => 'yes'] ]);
        $this->add_group_control(Group_Control_Typography::get_type(), [ 'name' => 'back_desc_typography', 'label' => 'Back Description Typography', 'selector' => '{{WRAPPER}} .ewf-card-back-description', 'condition' => ['enable_flip_effect' => 'yes'] ]);
        $this->add_responsive_control('card_back_image_width', ['label' => esc_html__( 'Back Image Width', 'eveent-widgets' ),'type' => Controls_Manager::SLIDER,'size_units' => ['px', '%'],'range' => ['px' => ['min' => 0,'max' => 1000,'step' => 1,],'%' => ['min' => 0,'max' => 100,'step' => 1,], ],'default' => ['unit' => 'px','size' => 80,], 'selectors' => [ '{{WRAPPER}} .ewf-card-back-image img' => 'width: {{SIZE}}{{UNIT}};' ], 'condition' => ['enable_flip_effect' => 'yes','card_back_image[url]!' =>'']]);
        
        $this->add_control('heading_qr_code', [ 'label' => esc_html__( 'QR Code Style (Global)', 'eveent-widgets' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ]);
        $this->add_responsive_control('qr_code_size', [
            'label' => esc_html__( 'QR Code Size (Global)', 'eveent-widgets' ), 'type' => Controls_Manager::SLIDER, 'size_units' => ['px', '%', 'vw'],
            'range' => [ 'px' => [ 'min' => 50, 'max' => 300 ], '%' => [ 'min' => 10, 'max' => 100 ], ], 'default' => ['unit' => 'px', 'size' => 200],	
            'selectors' => [ '{{WRAPPER}} .ewf-qr-image' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};', ],
            'description' => esc_html__('Ukuran gambar QR Code. Berlaku untuk mode v1 (QR) dan v2 (QR).', 'eveent-widgets'),
        ]);
        $this->add_group_control(Group_Control_Typography::get_type(), [ 'name' => 'qr_label_typography', 'label' => 'QR Label Typography', 'selector' => '{{WRAPPER}} .ewf-qr-label', ]);
        $this->add_control('qr_label_color', [ 'label' => esc_html__( 'QR Label Color', 'eveent-widgets' ), 'type' => Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .ewf-qr-label' => 'color: {{VALUE}}' ], ]);
        
        $this->add_control('heading_logo_chip', [ 'label' => esc_html__( 'Logo & Chip', 'eveent-widgets' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ]);
        $this->add_control('card_chip_icon', ['label' => esc_html__( 'Card Chip Icon', 'eveent-widgets' ),'type' => Controls_Manager::ICONS,'default' => [ 'value' => 'fas fa-sim-card', 'library' => 'fa-solid' ],]);
        $this->add_control('logo_alignment', [ 'label' => esc_html__('Logo Position', 'eveent-widgets'), 'type' => Controls_Manager::CHOOSE, 'options' => [ 'left' => [ 'title' => 'Left', 'icon' => 'eicon-h-align-left' ], 'right' => [ 'title' => 'Right', 'icon' => 'eicon-h-align-right' ] ], 'default' => 'right', 'prefix_class' => 'ewf-logo-align-', 'toggle' => false ]);
        $this->add_responsive_control('logo_width', [ 'label' => esc_html__( 'Logo Width', 'eveent-widgets' ), 'type' => Controls_Manager::SLIDER, 'size_units' => ['px', '%'], 'range' => ['px' => ['min' => 20, 'max' => 200], '%' => ['min' => 5, 'max' => 50]], 'default' => ['unit' => 'px', 'size' => 90], 'selectors' => [ '{{WRAPPER}} .ewf-card-logo img' => 'width: {{SIZE}}{{UNIT}};' ] ]);
        $this->add_responsive_control('logo_margin', [ 'label' => esc_html__( 'Logo Margin', 'eveent-widgets' ), 'type' => Controls_Manager::DIMENSIONS, 'size_units' => ['px', '%'], 'selectors' => [ '{{WRAPPER}} .ewf-card-logo' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ] ]);
        $this->add_responsive_control('chip_size', [ 'label' => esc_html__( 'Chip Size', 'eveent-widgets' ), 'type' => Controls_Manager::SLIDER, 'size_units' => ['px', 'em'], 'range' => [ 'px' => [ 'min' => 10, 'max' => 100 ] ], 'default' => [ 'unit' => 'px', 'size' => 25 ], 'selectors' => [ '{{WRAPPER}} .ewf-card-chip' => 'font-size: {{SIZE}}{{UNIT}};' ] ]);
        $this->add_control('chip_color', [ 'label' => esc_html__( 'Chip Color', 'eveent-widgets' ), 'type' => Controls_Manager::COLOR, 'default' => '#8D918D', 'selectors' => [ '{{WRAPPER}} .ewf-card-chip i' => 'color: {{VALUE}};', '{{WRAPPER}} .ewf-card-chip svg' => 'fill: {{VALUE}};' ], 'condition' => [ 'card_skin' => 'none', ], ]);
        $this->end_controls_section();

        $this->start_controls_section('section_copy_button_style', [ 'label' => esc_html__( 'Copy Button Style', 'eveent-widgets' ), 'tab' => Controls_Manager::TAB_STYLE, 'condition' => [ 'show_copy_button' => 'yes'] ]);
        $this->add_group_control(Group_Control_Typography::get_type(), [ 'name' => 'copy_button_typography', 'selector' => '{{WRAPPER}} .ewf-copy-button', 'fields_options' => ['font_size' => ['default' => ['unit' => 'px', 'size' => 12]]] ]);
        $this->add_control('copy_button_bg_color', [ 'label' => 'Background Color', 'type' => Controls_Manager::COLOR, 'default' => '#282727', 'selectors' => ['{{WRAPPER}} .ewf-copy-button' => 'background-color: {{VALUE}}']]);
        $this->add_control('heading_button_icon', [ 'label' => esc_html__( 'Icon', 'eveent-widgets' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ]);
        $this->add_responsive_control('copy_button_icon_size', [ 'label' => 'Icon Size', 'type' => Controls_Manager::SLIDER, 'selectors' => [ '{{WRAPPER}} .ewf-copy-button .ewf-button-icon-before, {{WRAPPER}} .ewf-copy-button .ewf-button-icon-after' => 'font-size: {{SIZE}}{{UNIT}};' ] ]);
        $this->start_controls_tabs('button_icon_colors');
        $this->start_controls_tab('button_icon_normal', [ 'label' => 'Normal' ]);
        $this->add_control('copy_button_icon_color', [ 'label' => 'Icon Color', 'type' => Controls_Manager::COLOR, 'default' => '#FFFFFF', 'selectors' => [ '{{WRAPPER}} .ewf-copy-button' => 'color: {{VALUE}}' ] ]);
        $this->end_controls_tab();
        $this->start_controls_tab('button_icon_hover', [ 'label' => 'Hover' ]);
        $this->add_control('copy_button_icon_hover_color', [ 'label' => 'Icon Color', 'type' => Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .ewf-copy-button:hover' => 'color: {{VALUE}}' ] ]);
        $this->end_controls_tab();
        $this->end_controls_tabs();
        $this->add_group_control(Group_Control_Border::get_type(), ['name' => 'copy_button_border', 'selector' => '{{WRAPPER}} .ewf-copy-button']);
        $this->add_control('copy_button_border_radius', [ 'label' => 'Border Radius', 'type' => Controls_Manager::DIMENSIONS, 'default' => ['size' => 5], 'selectors' => [ '{{WRAPPER}} .ewf-copy-button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ] ]);
        $this->add_control('copy_button_padding', [ 'label' => 'Padding', 'type' => Controls_Manager::DIMENSIONS, 'size_units' => ['px', 'em'], 'default' => ['top' => 5, 'right' => 12, 'bottom' => 5, 'left' => 12], 'selectors' => [ '{{WRAPPER}} .ewf-copy-button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ] ]);
        $this->end_controls_section();
        
        $this->start_controls_section('section_style_sweetalert', [
            'label' => esc_html__( 'Notif Confirm Style', 'eveent-widgets' ), 'tab' => Controls_Manager::TAB_STYLE, 'condition' => [ 'show_sweetalert' => 'yes' ]
        ]);
        $popup_selector = '.swal2-popup.ewf-atm-card-popup';
        $this->add_responsive_control('sa_text_align', [
            'label' => 'Text Alignment', 'type' => Controls_Manager::CHOOSE, 'options' => [ 'left' => [ 'title' => 'Left', 'icon' => 'eicon-text-align-left' ], 'center' => [ 'title' => 'Center', 'icon' => 'eicon-text-align-center' ], 'right' => [ 'title' => 'Right', 'icon' => 'eicon-text-align-right' ], ], 'default' => 'center', 'selectors' => [ $popup_selector => 'text-align: {{VALUE}};', $popup_selector . ' .swal2-title::before' => 'margin-left: {{VALUE}} === "center" ? "auto" : 0; margin-right: {{VALUE}} === "center" ? "auto" : 0;', ],
        ]);
        $this->add_responsive_control('sa_popup_width', [
            'label' => 'Popup Width', 'type' => Controls_Manager::SLIDER, 'separator' => 'before', 'size_units' => [ 'px', '%', 'vw' ], 'default' => ['unit' => 'px', 'size' => 450], 'selectors' => [ $popup_selector => 'width: {{SIZE}}{{UNIT}};' ]
        ]);
        $this->add_control( 'sa_popup_background', [ 'label' => 'Popup Background', 'type' => Controls_Manager::COLOR, 'selectors' => [ $popup_selector => 'background: {{VALUE}};' ] ]);
        $this->add_control('sa_title_heading', [ 'label' => esc_html__( 'Title', 'eveent-widgets' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ]);
        $this->add_control( 'sa_title_color', [ 'label' => 'Title Color', 'type' => Controls_Manager::COLOR, 'selectors' => [ $popup_selector . ' .swal2-title' => 'color: {{VALUE}};' ] ]);
        $this->add_group_control(Group_Control_Typography::get_type(), [ 'name' => 'sa_title_typography', 'selector' => $popup_selector . ' .swal2-title', 'fields_options' => ['font_size' => ['default' => ['unit' => 'px', 'size' => 22]]] ]);
        $this->add_control('sa_text_heading', [ 'label' => esc_html__( 'Message', 'eveent-widgets' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ]);
        $this->add_control( 'sa_text_color', [ 'label' => 'Message Color', 'type' => Controls_Manager::COLOR, 'selectors' => [ $popup_selector . ' .swal2-html-container' => 'color: {{VALUE}};' ] ]);
        $this->add_group_control(Group_Control_Typography::get_type(), [ 'name' => 'sa_text_typography', 'selector' => $popup_selector . ' .swal2-html-container', 'fields_options' => ['font_size' => ['default' => ['unit' => 'px', 'size' => 15]]] ]);
        $this->add_control('sa_icon_heading', [ 'label' => esc_html__( 'Icon', 'eveent-widgets' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ]);
        $this->add_control('sa_custom_icon', [ 'label' => 'Custom Icon', 'type' => Controls_Manager::ICONS, 'description' => 'Pilih ikon untuk menggantikan ikon default. Warna hanya bisa diubah untuk ikon dari library (cth: Font Awesome), bukan SVG yang di-upload.', 'default' => [ 'value' => 'fas fa-check-circle', 'library' => 'fa-solid', ], ]);
        $this->add_control( 'sa_custom_icon_color', [ 'label' => 'Custom Icon Color', 'type' => Controls_Manager::COLOR, 'description' => 'Hanya berfungsi untuk ikon dari library, bukan SVG.', 'selectors' => [ $popup_selector . ' .swal2-icon-html i' => 'color: {{VALUE}};', $popup_selector . ' .swal2-icon-html svg' => 'fill: {{VALUE}};', ], ]);
        $this->add_responsive_control('sa_custom_icon_size', [ 'label' => 'Custom Icon Size', 'type' => Controls_Manager::SLIDER, 'range' => [ 'px' => [ 'min' => 20, 'max' => 200 ] ], 'default' => [ 'size' => 60 ], 'selectors' => [ $popup_selector . ' .swal2-icon-html i' => 'font-size: {{SIZE}}{{UNIT}};', $popup_selector . ' .swal2-icon-html svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};', ], ]);
        $this->add_responsive_control('sa_custom_icon_align', [ 'label' => 'Custom Icon Alignment', 'type' => Controls_Manager::CHOOSE, 'options' => [ 'left' => [ 'title' => 'Left', 'icon' => 'eicon-h-align-left' ], 'center' => [ 'title' => 'Center', 'icon' => 'eicon-h-align-center' ], 'right' => [ 'title' => 'Right', 'icon' => 'eicon-h-align-right' ], ], 'default' => 'center', 'selectors' => [ $popup_selector . ' .swal2-icon-html' => 'text-align: {{VALUE}};', ], 'separator' => 'after', ]);
        $this->end_controls_section();
        
        $this->start_controls_section('section_style_confirm_button', [ 'label' => esc_html__( 'Confirm Button Style', 'eveent-widgets' ), 'tab' => Controls_Manager::TAB_STYLE, 'condition' => [ 'enable_gift_confirmation' => 'yes' ] ]);
        $confirm_button_selector = '.swal2-popup.ewf-atm-card-popup .swal2-deny';
        $this->add_group_control(Group_Control_Typography::get_type(), [ 'name' => 'confirm_btn_typography', 'selector' => $confirm_button_selector ]);
        $this->start_controls_tabs( 'tabs_confirm_button_style' );
        $this->start_controls_tab( 'tab_confirm_button_normal', [ 'label' => esc_html__( 'Normal', 'eveent-widgets' ) ] );
        $this->add_control('confirm_btn_text_color', [ 'label' => 'Text Color', 'type' => Controls_Manager::COLOR, 'selectors' => [ $confirm_button_selector => 'color: {{VALUE}};' ] ]);
        $this->add_control('confirm_btn_bg_color', [ 'label' => 'Background Color', 'type' => Controls_Manager::COLOR, 'selectors' => [ $confirm_button_selector => 'background: {{VALUE}};' ] ]);
        $this->add_group_control(Group_Control_Border::get_type(), [ 'name' => 'confirm_btn_border', 'selector' => $confirm_button_selector ]);
        $this->end_controls_tab();
        $this->start_controls_tab( 'tab_confirm_button_hover', [ 'label' => esc_html__( 'Hover', 'eveent-widgets' ) ] );
        $this->add_control('confirm_btn_hover_text_color', [ 'label' => 'Text Color', 'type' => Controls_Manager::COLOR, 'selectors' => [ $confirm_button_selector . ':hover' => 'color: {{VALUE}};' ] ]);
        $this->add_control('confirm_btn_hover_bg_color', [ 'label' => 'Background Color', 'type' => Controls_Manager::COLOR, 'selectors' => [ $confirm_button_selector . ':hover' => 'background: {{VALUE}};' ] ]);
        $this->add_group_control(Group_Control_Border::get_type(), [ 'name' => 'confirm_btn_hover_border', 'selector' => $confirm_button_selector . ':hover' ]);
        $this->end_controls_tab();
        $this->end_controls_tabs();
        $this->add_control('confirm_btn_border_radius', [ 'label' => 'Border Radius', 'type' => Controls_Manager::DIMENSIONS, 'separator' => 'before', 'selectors' => [ $confirm_button_selector => 'border-radius: {{TOP}}px {{RIGHT}}px {{BOTTOM}}px {{LEFT}}px;' ] ]);
        $this->add_responsive_control('confirm_btn_padding', [ 'label' => 'Padding', 'type' => Controls_Manager::DIMENSIONS, 'selectors' => [ $confirm_button_selector => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};', ] ]);
        $this->end_controls_section();
        
        $this->start_controls_section('section_style_gift_modal', [ 'label' => esc_html__( 'Gift Confirmation Gift', 'eveent-widgets' ), 'tab' => Controls_Manager::TAB_STYLE, 'condition' => [ 'enable_gift_confirmation' => 'yes' ], ]);
        $modal_selector = '.ewf-gift-modal';
        $this->add_control('modal_heading_container', [ 'label' => esc_html__( 'Modal Container', 'eveent-widgets' ), 'type' => Controls_Manager::HEADING, ]);
        $this->add_responsive_control('modal_width', [ 'label' => 'Width', 'type' => Controls_Manager::SLIDER, 'size_units' => ['px', '%', 'vw'], 'default' => ['unit' => 'px', 'size' => 450], 'range' => ['px' => ['min' => 250, 'max' => 800]], 'selectors' => [ $modal_selector => 'max-width: {{SIZE}}{{UNIT}};' ], ]);
        $this->add_control('modal_background_color', [ 'label' => 'Background Color', 'type' => Controls_Manager::COLOR, 'selectors' => [ $modal_selector => 'background-color: {{VALUE}};' ], ]);
        $this->add_responsive_control('modal_padding', [ 'label' => 'Padding', 'type' => Controls_Manager::DIMENSIONS, 'selectors' => [ $modal_selector => 'padding: {{TOP}}px {{RIGHT}}px {{BOTTOM}}px {{LEFT}}px;' ], ]);
        $this->add_group_control(Group_Control_Border::get_type(), [ 'name' => 'modal_border', 'selector' => $modal_selector, ]);
        $this->add_control('modal_border_radius', [ 'label' => 'Border Radius', 'type' => Controls_Manager::DIMENSIONS, 'selectors' => [ $modal_selector => 'border-radius: {{TOP}}px {{RIGHT}}px {{BOTTOM}}px {{LEFT}}px;' ], ]);
        $this->add_group_control(Group_Control_Box_Shadow::get_type(), [ 'name' => 'modal_box_shadow', 'selector' => $modal_selector, ]);
        $this->add_control('modal_heading_title', [ 'label' => esc_html__( 'Title', 'eveent-widgets' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before', ]);
        $this->add_control('modal_title_color', [ 'label' => 'Color', 'type' => Controls_Manager::COLOR, 'selectors' => [ $modal_selector . ' h4' => 'color: {{VALUE}};' ], ]);
        $this->add_group_control(Group_Control_Typography::get_type(), [ 'name' => 'modal_title_typography', 'selector' => $modal_selector . ' h4', ]);
        $this->add_responsive_control('modal_title_spacing', [ 'label' => 'Spacing Bottom', 'type' => Controls_Manager::SLIDER, 'selectors' => [ $modal_selector . ' h4' => 'margin-bottom: {{SIZE}}px;' ], ]);
        $this->add_control('modal_heading_form', [ 'label' => esc_html__( 'Form Fields', 'eveent-widgets' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before', ]);
        $this->add_control('modal_label_color', [ 'label' => 'Label Color', 'type' => Controls_Manager::COLOR, 'selectors' => [ $modal_selector . ' .ev-rsvp-form label' => 'color: {{VALUE}};' ], ]);
        $this->add_group_control(Group_Control_Typography::get_type(), [ 'name' => 'modal_label_typography', 'selector' => $modal_selector . ' .ev-rsvp-form label', ]);
        $this->add_group_control(Group_Control_Typography::get_type(), [ 'name' => 'modal_input_typography', 'selector' => $modal_selector . ' .ev-rsvp-form input[type="text"]', ]);
        $this->add_control('modal_input_color', [ 'label' => 'Input Text Color', 'type' => Controls_Manager::COLOR, 'separator' => 'before', 'selectors' => [ $modal_selector . ' .ev-rsvp-form input[type="text"]' => 'color: {{VALUE}};' ], ]);
        $this->add_control('modal_input_bg_color', [ 'label' => 'Input Background Color', 'type' => Controls_Manager::COLOR, 'selectors' => [ $modal_selector . ' .ev-rsvp-form input[type="text"]' => 'background-color: {{VALUE}};' ], ]);
        $this->add_group_control(Group_Control_Border::get_type(), [ 'name' => 'modal_input_border', 'selector' => $modal_selector . ' .ev-rsvp-form input[type="text"]', ]);
        $this->add_control('modal_input_border_radius', [ 'label' => 'Input Border Radius', 'type' => Controls_Manager::DIMENSIONS, 'selectors' => [ $modal_selector . ' .ev-rsvp-form input[type="text"]' => 'border-radius: {{TOP}}px {{RIGHT}}px {{BOTTOM}}px {{LEFT}}px;' ], ]);
        $this->add_control('modal_heading_submit', [ 'label' => esc_html__( 'Submit Button', 'eveent-widgets' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before', ]);
        $this->add_group_control(Group_Control_Typography::get_type(), [ 'name' => 'modal_submit_typography', 'selector' => $modal_selector . ' .ev-rsvp-submit-button', ]);
        $this->start_controls_tabs( 'tabs_modal_submit_style' );
        $this->start_controls_tab('tab_modal_submit_normal', [ 'label' => esc_html__( 'Normal', 'eveent-widgets' ) ]);
        $this->add_control('modal_submit_text_color', [ 'label' => 'Text Color', 'type' => Controls_Manager::COLOR, 'selectors' => [ $modal_selector . ' .ev-rsvp-submit-button' => 'color: {{VALUE}};' ] ]);
        $this->add_control('modal_submit_bg_color', [ 'label' => 'Background Color', 'type' => Controls_Manager::COLOR, 'selectors' => [ $modal_selector . ' .ev-rsvp-submit-button' => 'background-color: {{VALUE}};' ] ]);
        $this->end_controls_tab();
        $this->start_controls_tab('tab_modal_submit_hover', [ 'label' => esc_html__( 'Hover', 'eveent-widgets' ) ] );
        $this->add_control('modal_submit_hover_text_color', [ 'label' => 'Text Color', 'type' => Controls_Manager::COLOR, 'selectors' => [ $modal_selector . ' .ev-rsvp-submit-button:hover' => 'color: {{VALUE}};' ] ]);
        $this->add_control('modal_submit_hover_bg_color', [ 'label' => 'Background Color', 'type' => Controls_Manager::COLOR, 'selectors' => [ $modal_selector . ' .ev-rsvp-submit-button:hover' => 'background: {{VALUE}};' ] ]);
        $this->end_controls_tab();
        $this->end_controls_tabs();
        $this->add_control('modal_submit_border_radius', [ 'label' => 'Border Radius', 'type' => Controls_Manager::DIMENSIONS, 'separator' => 'before', 'selectors' => [ $modal_selector . ' .ev-rsvp-submit-button' => 'border-radius: {{TOP}}px {{RIGHT}}px {{BOTTOM}}px {{LEFT}}px;' ] ]);
        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
       
        $current_post_id = get_the_ID();
        
        $widget_id = $this->get_id();
       
        
        $meta_skin_value = get_post_meta( $current_post_id, 'style_card_ev', true );
        $skin_map = [ 'card_1' => 'skin-dark', 'card_2' => 'skin-gold', 'card_3' => 'skin-light', ];
        if ( ! empty( $meta_skin_value ) && isset( $skin_map[ $meta_skin_value ] ) ) {
            $settings['card_skin'] = $skin_map[ $meta_skin_value ];
        }
        if ( !empty($settings['card_skin']) && $settings['card_skin'] !== 'none' ) {
            $this->add_render_attribute('_wrapper', 'class', 'ewf-' . esc_attr($settings['card_skin']));
        }
        
        if ( $settings['widget_version'] === 'v2' ) {
            $this->render_v2( $settings );
        } else {
            $this->render_v1( $settings );
        }
        
        
        if ('yes' === $settings['enable_gift_confirmation']) : ?>
        <div id="ewf-gift-modal-<?php echo esc_attr($widget_id); ?>" class="ewf-gift-modal-overlay" style="display:none;">
            <div class="ewf-gift-modal">
                <button class="ewf-gift-modal-close">&times;</button>
                <h4>Konfirmasi Pemberian Hadiah</h4>
                <form id="ewf-gift-form-<?php echo esc_attr($widget_id); ?>" class="ev-rsvp-form">
                    <input type="hidden" name="is_gift_active" value="yes">
                    <input type="hidden" name="post_id" value="<?php echo intval(get_the_ID()); ?>">
                    <input type="hidden" name="action" value="send_gift_notification">
                    <?php 
                        $nonce_name = 'ewf_gift_nonce_' . $widget_id;
                        wp_nonce_field( 'ewf_send_gift_notification', $nonce_name ); ?>
                        
                    <div class="ewf-honeypot" style="display:none !important;" aria-hidden="true">
                        <label for="user_nickname_<?php echo esc_attr($widget_id); ?>">Nickname</label>
                        <input type="text" id="user_nickname_<?php echo esc_attr($widget_id); ?>" name="user_nickname" tabindex="-1" autocomplete="off">
                    </div>
                    <div class="ev-rsvp-field">
                        <label for="guest_name_<?php echo esc_attr($widget_id); ?>">Nama Anda</label>
                        <input type="text" id="guest_name_<?php echo esc_attr($widget_id); ?>" name="guest_name" placeholder="Tulis nama Anda di sini..." required>
                    </div>
                    <div class="ev-rsvp-field">
                        <label for="amount_<?php echo esc_attr($widget_id); ?>">Jumlah Transfer (Rp)</label>
                        <input type="text" id="amount_<?php echo esc_attr($widget_id); ?>" name="amount" placeholder="Contoh: 100.000" required inputmode="numeric">
                    </div>
                    <div class="ev-rsvp-field">
                        <label for="bank_name_<?php echo esc_attr($widget_id); ?>">Bank Tujuan</label>
                        <input type="text" id="bank_name_<?php echo esc_attr($widget_id); ?>" name="bank_name" placeholder="Contoh: BCA" required>
                    </div>
                    <div class="ev-rsvp-field">
                        <label for="proof_of_transfer_<?php echo esc_attr($widget_id); ?>">Bukti Transfer (Max 2MB, JPG/JPEG/WEBP)</label>
                        <input type="file" id="proof_of_transfer_<?php echo esc_attr($widget_id); ?>" name="proof_of_transfer" accept="image/jpeg, image/jpg, image/webp">
                        <small class="ewf-file-info-<?php echo esc_attr($widget_id); ?>" style="color: blue; display: none;">*File sedang diproses...</small>
                        <small class="ewf-file-error-<?php echo esc_attr($widget_id); ?>" style="color: red; display: none;">Ukuran file melebihi batas atau gagal.</small>
                    </div>
                    <div class="ev-rsvp-submit-field">
                        <button type="submit" class="ev-rsvp-submit-button">
                            <span class="button-text">Kirim Konfirmasi</span>
                            <div class="button-loading-content"><span class="button-loader"></span><span class="loading-text">Mengirim...</span></div>
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <?php endif;
    }

    protected function render_v1( $settings ) {
        $qr_image_url = $settings['qr_code_image']['url'] ?? '';
        if ( empty( $qr_image_url ) && ! empty( $settings['qr_code_image']['id'] ) ) {
            $qr_image_id = (int) $settings['qr_code_image']['id'];
            if ( $qr_image_id ) {
                $attachment_url_array = wp_get_attachment_image_src( $qr_image_id, 'full' );	
                if ( ! empty( $attachment_url_array[0] ) ) {
                    $qr_image_url = $attachment_url_array[0];
                }
            }
        }
        $show_qr_code = $settings['show_qr_code'];
        $card_data = [
            'card_title'	        => $this->parse_text_editor($settings['card_title']),
            'logo_url'		        => $settings['card_logo']['url'] ?? '',
            'card_chip_icon'        => $settings['card_chip_icon'] ?? [],
            'card_number_label'     => $this->parse_text_editor($settings['card_number_label']),
            'account_number'	    => $this->parse_text_editor($settings['card_number']),
            'footer_left_label'     => $this->parse_text_editor($settings['footer_left_label']),
            'account_name'	        => $this->parse_text_editor($settings['footer_left_value']),
            'footer_right_label'    => $this->parse_text_editor($settings['footer_right_label']),
            'footer_right_value'    => $this->parse_text_editor($settings['footer_right_value']),
            'show_qr_code'	        => ($show_qr_code === 'yes' && !empty($qr_image_url)) ? 'yes' : 'no',
            'qr_code_image'	        => $qr_image_url,
            'qr_code_label'	        => $this->parse_text_editor($settings['qr_code_label']),
            'qr_label_above_barcode' => $this->parse_text_editor($settings['qr_label_above_barcode']),
            'qr_instruction_above_barcode' => $this->parse_text_editor($settings['qr_instruction_above_barcode']),
        ];
        ?>
        <div class="ewf-atm-card-list">
        <?php $this->render_single_card( $card_data, 'v1' ); ?>
        </div>
        <?php
    }

    protected function render_v2( $settings ) {
        $query_id = $settings['jet_engine_query_id'];
        if ( ! $query_id || ! class_exists('\Jet_Engine\Query_Builder\Manager') ) { return; }
        $query = \Jet_Engine\Query_Builder\Manager::instance()->get_query_by_id( $query_id );
        if ( ! $query ) { return; }
        $items = $query->get_items();	
        if ( empty( $items ) ) { return; }
        
        $template_item = $settings['card_template_repeater'][0] ?? [];
        $bank_field = $settings['v2_bank_field'];
        $name_field = $settings['v2_account_name_field'];
        $number_field = $settings['v2_account_number_field'];
        $enable_qr_v2 = $settings['v2_qr_mode_enable'] ?? 'no';
        $qr_image_field = $settings['v2_qr_image_field'];

        ?>
        <div class="ewf-atm-card-list">
            <?php
            foreach ( $items as $index => $item_object ) {
                $logo_url = ( ! empty( $bank_field ) && isset( $item_object->{$bank_field} ) ) ? trim( $item_object->{$bank_field} ) : '';
                $card_title = 'Gift Card';
                $account_name = ! empty( $name_field ) && isset( $item_object->{$name_field} ) ? $item_object->{$name_field} : 'N/A';
                $account_number = ! empty( $number_field ) && isset( $item_object->{$number_field} ) ? $item_object->{$number_field} : 'N/A';

                $qr_image_url = '';
                $is_qr_enabled = 'no';	
                if ( 'yes' === $enable_qr_v2 && ! empty( $qr_image_field ) ) {
                    $qr_image_id = isset( $item_object->{$qr_image_field} ) ? $item_object->{$qr_image_field} : '';
                    if ( ! empty( $qr_image_id ) ) {
                        $attachment_url_array = wp_get_attachment_image_src( (int)$qr_image_id, 'full' );	
                        if ( ! empty( $attachment_url_array[0] ) ) {
                            $qr_image_url = $attachment_url_array[0];
                            $is_qr_enabled = 'yes';
                        }
                    }
                }

                $card_data = [
                    'card_title'	        => $card_title,
                    'logo_url'		        => esc_url($logo_url), 
                    'card_chip_icon'        => $settings['card_chip_icon'],
                    'card_number_label'     => $template_item['card_number_label'] ?? 'Nomor Rekening',
                    'account_number'	    => $account_number,
                    'footer_left_label'     => $template_item['footer_left_label'] ?? 'Nama Pemilik',
                    'account_name'	        => $account_name,
                    'footer_right_label'    => $template_item['footer_right_label'] ?? 'Event',
                    'footer_right_value'    => $template_item['footer_right_value'] ?? '01/29',
                    'show_qr_code'	        => $is_qr_enabled,	
                    'qr_code_image'	        => $qr_image_url,	
                    'qr_code_label'	        => $settings['qr_code_label'],
                    'qr_label_above_barcode' => $this->parse_text_editor($settings['qr_label_above_barcode_v2']),
                    'qr_instruction_above_barcode' => $this->parse_text_editor($settings['qr_instruction_above_barcode_v2']),
                ];

                $this->render_single_card( $card_data, $index );
            }
            ?>
        </div>
        <?php
    }

    protected function render_single_card( $card_data, $unique_id ) {
    $settings = $this->get_settings_for_display();
    $is_flip_enabled = ($settings['enable_flip_effect'] === 'yes');
    $is_qr_enabled = ($card_data['show_qr_code'] === 'yes');	 	

    $card_title	     = $card_data['card_title'] ?? '';
    $logo_url	     = $card_data['logo_url'] ?? '';
    $card_chip_icon  = $card_data['card_chip_icon'] ?? [];
    $card_number_label = $card_data['card_number_label'] ?? '';
    $account_number	 = $card_data['account_number'] ?? '';
    $footer_left_label = $card_data['footer_left_label'] ?? '';
    $account_name	 = $card_data['account_name'] ?? '';
    $footer_right_label= $card_data['footer_right_label'] ?? '';
    $footer_right_value= $card_data['footer_right_value'] ?? '';
    $qr_image_url	 = $card_data['qr_code_image'] ?? '';
    $qr_label_above_barcode = $card_data['qr_label_above_barcode'] ?? '';
    $qr_instruction_above_barcode = $card_data['qr_instruction_above_barcode'] ?? '';

    $wrapper_key = 'card_wrapper_' . $unique_id;
    
    
    $this->add_render_attribute( $wrapper_key, 'class', 'ewf-atm-card-widget-wrapper' );
    if ($is_qr_enabled) {
        $this->add_render_attribute( $wrapper_key, 'class', 'ewf-qr-active' );
    }
    
    
    $this->add_render_attribute( $wrapper_key, 'data-post-id', intval(get_the_ID()) );
    $this->add_render_attribute( $wrapper_key, 'data-enable-gift-confirmation', esc_attr($settings['enable_gift_confirmation']) );
    $this->add_render_attribute( $wrapper_key, 'data-confirm-button-text', esc_attr($settings['confirm_button_text']) );
    $this->add_render_attribute( $wrapper_key, 'data-post-title', esc_attr(get_the_title()) );
    $this->add_render_attribute( $wrapper_key, 'data-wa-notice-name', esc_attr($this->parse_text_editor($settings['wa_notice_name'])));
    $this->add_render_attribute( $wrapper_key, 'data-wa-notice-number', esc_attr($this->parse_text_editor($settings['wa_notice_number'])));
    $this->add_render_attribute( $wrapper_key, 'data-enable-digital-gift-api', esc_attr($settings['enable_digital_gift_api'] ?? 'no') );
    $this->add_render_attribute( $wrapper_key, 'data-wa-template', esc_attr($settings['wa_template']));
    
    
    $this->add_render_attribute( $wrapper_key, 'data-show-alert', esc_attr($settings['show_sweetalert']));
    if ('yes' === $settings['show_sweetalert']) {
         $this->add_render_attribute( $wrapper_key, 'data-sa-title', esc_attr($this->parse_text_editor($settings['sweetalert_title'])));
         $this->add_render_attribute( $wrapper_key, 'data-sa-text', esc_attr($this->parse_text_editor_kses($settings['sweetalert_text']))); 
         $this->add_render_attribute( $wrapper_key, 'data-sa-icon', esc_attr($settings['sweetalert_icon']));
         $this->add_render_attribute( $wrapper_key, 'data-sa-timer', esc_attr($settings['sa_timer']['size'] ?? 0));
    }
   
    $icon_html = '';
    if ( ! empty( $settings['sa_custom_icon']['value'] ) ) {
        ob_start();
        \Elementor\Icons_Manager::render_icon( $settings['sa_custom_icon'], [ 'aria-hidden' => 'true' ] );
        $icon_html = ob_get_clean();
        $this->add_render_attribute($wrapper_key, 'data-sa-icon-html', $icon_html);
    }
    
    $flip_container_key = 'flip_container_' . $unique_id;
    $this->add_render_attribute( $flip_container_key, 'class', 'ewf-flip-card-container' );
    if ( $is_flip_enabled ) {
        $this->add_render_attribute( $flip_container_key, 'class', 'ewf-can-flip' );
    }

    ?>
    <div <?php echo $this->get_render_attribute_string($wrapper_key); ?>>
        <div <?php echo $this->get_render_attribute_string($flip_container_key); ?>>
            <div class="ewf-flip-card-inner">
                
                <div class="ewf-card-front ewf-atm-card <?php echo esc_attr($settings['emboss_effect']); ?>">
                    
                    <?php if (!$is_qr_enabled) : ?>
                    <div class="ewf-card-header">
                        <div class="ewf-card-title"><?php echo esc_html( $card_title ); ?></div>
                        <?php if ( ! empty( $logo_url ) ) : ?>
                            <div class="ewf-card-logo"><img src="<?php echo esc_url( $logo_url ); ?>" alt="Card Logo"></div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="ewf-card-body">
                        <?php if ( !$is_qr_enabled && ! empty( $card_chip_icon['value'] ) ) : ?>
                            <div class="ewf-card-chip"><?php \Elementor\Icons_Manager::render_icon( $card_chip_icon, [ 'aria-hidden' => 'true' ] ); ?></div>
                        <?php endif; ?>
                        
                        <div class="ewf-card-main-content">
                            <?php if ( $is_qr_enabled && !empty($qr_image_url) ) : ?>
                                
                                <div class="ewf-qr-content-top">
                                    <?php if (!empty($qr_label_above_barcode)) : ?>
                                    <div class="ewf-qr-label-above">
                                        <h3 class="ewf-qr-label-title"><?php echo esc_html($qr_label_above_barcode); ?></h3>
                                        <?php if (!empty($qr_instruction_above_barcode)) : ?>
                                            <p class="ewf-qr-label-instruction"><?php echo nl2br(esc_html($qr_instruction_above_barcode)); ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <?php endif; ?>
                                    <div class="ewf-qr-code-area">
                                        <img src="<?php echo esc_url($qr_image_url); ?>" alt="QR Code/QRIS" class="ewf-qr-image">
                                    </div>
                                </div>

                                <div class="ewf-qr-content-bottom">
                                    <div class="ewf-card-footer ewf-qr-footer-inline">
                                        <div class="ewf-card-footer-left">
                                            <div class="ewf-card-footer-label"><?php echo esc_html( $footer_left_label ); ?></div>
                                            <div class="ewf-card-footer-value"><?php echo esc_html( $account_name ); ?></div>
                                        </div>
                                        </div>
                                    
                                    <div class="ewf-qr-buttons-wrapper">
                                        <?php if ('yes' === $settings['show_copy_button']) :	
                                            $download_button_key = 'download_button_' . $unique_id;
                                            $this->add_render_attribute($download_button_key, 'class', 'ewf-copy-button ewf-download-button ewf-qr-download-btn');
                                            $this->add_render_attribute($download_button_key, 'role', 'button');
                                            $this->add_render_attribute($download_button_key, 'data-download-url', esc_url($qr_image_url));	
                                            $this->add_render_attribute($download_button_key, 'data-download-filename', esc_attr(sanitize_title($card_title . '-' . $account_name . '-QRIS')));	
                                            ?>
                                            <div <?php echo $this->get_render_attribute_string($download_button_key); ?>>
                                                <span class="ewf-button-text-wrapper">
                                                    <span class="ewf-button-icon-before"><i class="fas fa-download"></i></span>
                                                    <span class="ewf-button-text">Unduh QR</span>
                                                </span>
                                            </div>
                                        <?php endif; ?>

                                        <?php if ('yes' === $settings['enable_gift_confirmation']) : ?>
                                            <a href="#ewf-gift-modal-<?php echo esc_attr($this->get_id()); ?>"	
                                                class="ewf-copy-button ewf-confirm-button ewf-open-modal"	
                                                role="button"	
                                                data-target="ewf-gift-modal-<?php echo esc_attr($this->get_id()); ?>">
                                                <span class="ewf-button-text-wrapper">
                                                        <span class="ewf-button-icon-before"><i class="fas fa-check-circle"></i></span>
                                                        <span class="ewf-button-text">Konfirmasi</span>
                                                    </span>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>

                            <?php else : ?>
                                <div class="ewf-card-number-label"><?php echo esc_html( $card_number_label ); ?></div>
                                <div class="ewf-card-number-area">
                                    <span class="ewf-card-number"><?php echo esc_html( $account_number ); ?></span>
                                    <?php if ('yes' === $settings['show_copy_button']) :	
                                        $copy_button_key = 'copy_button_' . $unique_id;
                                        $this->add_render_attribute($copy_button_key, 'class', 'ewf-copy-button');
                                        $this->add_render_attribute($copy_button_key, 'href', '#');
                                        $this->add_render_attribute($copy_button_key, 'role', 'button');
                                        $this->add_render_attribute($copy_button_key, 'data-copy-content', esc_attr(preg_replace('/\s+/', '', $account_number)));
                                        $this->add_render_attribute($copy_button_key, 'data-account-name', esc_attr($account_name));
                                        $this->add_render_attribute($copy_button_key, 'data-bank-name', esc_attr($card_title));
                                    ?>
                                        <a <?php echo $this->get_render_attribute_string($copy_button_key); ?>>
                                            <span class="ewf-button-text-wrapper">
                                                <?php if (!empty($settings['copy_button_icon']['value']) && $settings['copy_button_icon_align'] === 'before') : ?>
                                                    <span class="ewf-button-icon-before"><?php Icons_Manager::render_icon($settings['copy_button_icon'], ['aria-hidden' => 'true']); ?></span>
                                                <?php endif; ?>
                                                <span class="ewf-button-text"><?php echo esc_html($settings['copy_button_text']); ?></span>
                                                <?php if (!empty($settings['copy_button_icon']['value']) && $settings['copy_button_icon_align'] === 'after') : ?>
                                                    <span class="ewf-button-icon-after"><?php Icons_Manager::render_icon($settings['copy_button_icon'], ['aria-hidden' => 'true']); ?></span>
                                                <?php endif; ?>
                                            </span>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php if ( !$is_qr_enabled ) :	 ?>
                        <div class="ewf-card-footer">
                            <div class="ewf-card-footer-left">
                                <div class="ewf-card-footer-label"><?php echo esc_html( $footer_left_label ); ?></div>
                                <div class="ewf-card-footer-value"><?php echo esc_html( $account_name ); ?></div>
                            </div>
                            <div class="ewf-card-footer-right">
                                <div class="ewf-card-footer-label"><?php echo esc_html( $footer_right_label ); ?></div>
                                <div class="ewf-card-footer-value"><?php echo esc_html( $footer_right_value ); ?></div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if ($is_flip_enabled) : ?>
                <div class="ewf-card-back">
                    <?php if (!empty($settings['card_back_image']['url'])) : ?>
                        <div class="ewf-card-back-image"><img src="<?php echo esc_url($settings['card_back_image']['url']); ?>" alt="<?php echo esc_attr($this->parse_text_editor($settings['card_back_title'])); ?>"></div>
                    <?php endif; ?>
                    <?php if (!empty($settings['card_back_title'])) : ?>
                        <h3 class="ewf-card-back-title"><?php echo esc_html($this->parse_text_editor($settings['card_back_title'])); ?></h3>
                    <?php endif; ?>
                    <?php if (!empty($settings['card_back_description'])) : ?>
                        <div class="ewf-card-back-description"><?php echo $this->parse_text_editor_kses($settings['card_back_description']); ?></div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
            </div>
        </div>
    </div>
    <?php
}

    protected function parse_text_editor( $content ) {
        if ( empty($content) ) {
            return '';
        }
        $content = apply_filters( 'widget_text', $content );
        $content = do_shortcode( shortcode_unautop( $content ) );
        return $content;
    }

    protected function parse_text_editor_kses( $content ) {
        if ( empty($content) ) {
            return '';
        }
        $content = apply_filters( 'widget_text', $content );
        $content = do_shortcode( shortcode_unautop( $content ) );
        return wp_kses_post( $content ); 
    }
}