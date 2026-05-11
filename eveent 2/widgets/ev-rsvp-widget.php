<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Repeater;

class Elementor_evRSVP_Widget extends \Elementor\Widget_Base {

    public function get_name() { return 'ev-rsvp'; }
    public function get_title() { return esc_html__( 'EV RSVP', 'eveent-widgets' ); }
    public function get_icon() { return 'eicon-comments'; }
    public function get_categories() { return [ 'eveent-widgets' ]; }
    public function get_style_depends() { return [ 'ev-rsvp-style', 'elementor-icons-fa-solid', 'elementor-icons-fa-brands', 'elementor-icons-fa-regular' ]; }
    public function get_script_depends() { return [ 'ev-rsvp-handler' ]; }

    protected function _register_controls() {
        
        $this->start_controls_section('section_content', ['label' => esc_html__( 'Settings', 'eveent-widgets' )]);

        $this->add_control( 'show_main_title', [ 'label' => esc_html__( 'Show Main Title & Count', 'eveent-widgets' ), 'type' => Controls_Manager::SWITCHER, 'label_on' => esc_html__( 'Ya', 'eveent-widgets' ), 'label_off' => esc_html__( 'Tidak', 'eveent-widgets' ), 'return_value' => 'yes', 'default' => 'yes', ] );

        $this->add_control( 'show_form_title', [ 'label' => esc_html__( 'Show Form Title', 'eveent-widgets' ), 'type' => Controls_Manager::SWITCHER, 'label_on' => esc_html__( 'Ya', 'eveent-widgets' ), 'label_off' => esc_html__( 'Tidak', 'eveent-widgets' ), 'return_value' => 'yes', 'default' => 'yes', ] );
        
        $this->add_control( 'hide_message_input', [ 
            'label' => esc_html__( 'Confirmation Only', 'eveent-widgets' ), 
            'type' => Controls_Manager::SWITCHER, 
            'label_on' => esc_html__( 'Ya', 'eveent-widgets' ), 
            'label_off' => esc_html__( 'Tidak', 'eveent-widgets' ), 
            'return_value' => 'yes', 
            'default' => 'no', 
            'description' => esc_html__( 'Aktifkan untuk menyembunyikan kolom ucapan dan stiker. Formulir hanya akan menampilkan konfirmasi kehadiran. Tombol kirim otomatis berubah menjadi "Kirim RSVP".', 'eveent-widgets' ),
        ] );

        $this->add_control( 'hide_comment_list', [ 
            'label' => esc_html__( 'Hide Comment List', 'eveent-widgets' ), 
            'type' => Controls_Manager::SWITCHER, 
            'label_on' => esc_html__( 'Ya', 'eveent-widgets' ), 
            'label_off' => esc_html__( 'Tidak', 'eveent-widgets' ), 
            'return_value' => 'yes', 
            'default' => 'no', 
            'description' => esc_html__( 'Sembunyikan daftar ucapan di bawah form.', 'eveent-widgets' ),
            'condition' => [ 'hide_message_input!' => 'yes' ] 
        ] );

        $this->add_control( 'enable_rsvp_lock', [ 
            'label' => esc_html__( 'Enable RSVP Lock', 'eveent-widgets' ), 
            'type' => Controls_Manager::SWITCHER, 
            'label_on' => esc_html__( 'Ya', 'eveent-widgets' ), 
            'label_off' => esc_html__( 'Tidak', 'eveent-widgets' ), 
            'return_value' => 'yes', 
            'default' => 'no', 
            'description' => esc_html__( 'Aktifkan batas waktu untuk menutup form RSVP.', 'eveent-widgets' ),
            'separator' => 'before',
        ] );

        $this->add_control( 'rsvp_deadline_date', [ 
            'label' => esc_html__( 'Batas Waktu RSVP', 'eveent-widgets' ), 
            'type' => Controls_Manager::TEXT, 
            'placeholder' => '2025-12-31 23:59',
            'description' => esc_html__( 'Masukkan batas tanggal (Misal: 2025-12-31 23:59)', 'eveent-widgets' ),
            'condition' => [ 'enable_rsvp_lock' => 'yes' ],
            'dynamic' => [ 'active' => true ], 
        ] );

        $this->add_control( 'rsvp_locked_message', [ 
            'label' => esc_html__( 'Pesan Saat RSVP Ditutup', 'eveent-widgets' ), 
            'type' => Controls_Manager::TEXTAREA, 
            'default' => esc_html__( 'Mohon maaf, batas waktu pengisian RSVP telah berakhir.', 'eveent-widgets' ), 
            'condition' => [ 'enable_rsvp_lock' => 'yes' ],
            'dynamic' => [ 'active' => true ], 
        ] );

        $this->add_control( 'hr_notif', [ 'type' => Controls_Manager::DIVIDER, 'separator' => 'before', ] );
        
        $this->add_control( 'enable_wa_notice', [ 'label' => esc_html__( 'Send Notification WA Client', 'eveent-widgets' ), 'type' => Controls_Manager::SWITCHER, 'label_on' => esc_html__( 'Ya', 'eveent-widgets' ), 'label_off' => esc_html__( 'Tidak', 'eveent-widgets' ), 'return_value' => 'yes', 'default' => 'no', ] );
        
        $this->add_control( 'wa_notice_name', [ 'label' => esc_html__( 'Nama Client', 'eveent-widgets' ), 'type' => Controls_Manager::TEXT, 'placeholder' => esc_html__( 'Fulan dan Fulanah', 'eveent-widgets' ), 'description' => esc_html__( 'Nama ini akan digunakan di dalam pesan notifikasi.', 'eveent-widgets' ), 'condition' => [ 'enable_wa_notice' => 'yes', ], 'dynamic' => [  'active' => true, ], ] );
        
        $this->add_control( 'wa_notice_number', [ 'label' => esc_html__( 'Nomor WA Client', 'eveent-widgets' ), 'type' => Controls_Manager::TEXT, 'placeholder' => '6281234567890', 'description' => esc_html__( 'Gunakan format internasional (62). Contoh 6281234567890 .', 'eveent-widgets' ), 'condition' => [ 'enable_wa_notice' => 'yes', ], 'dynamic' => [  'active' => true, ], ] );
        
        $this->add_control( 'wa_template', [ 'label' => esc_html__( 'Template Pesan WA', 'eveent-widgets' ), 'type' => Controls_Manager::TEXTAREA, 'rows' => 10, 'description' => 'Buat format pesan Anda. Gunakan variabel di bawah ini untuk data template notifikasi WA:<br> <code>[post_title]</code> - Judul Undangan<br> <code>[client_name]</code> - Nama Client<br> <code>[guest_name]</code> - Nama Tamu<br>  <code>[group_name]</code> - Nama Grup (jika dari undangan grup)<br>  <code>[attendance_status]</code> - Status Kehadiran<br> <code>[guest_count]</code> - Jumlah Tamu<br> <code>[guest_message]</code> - Isi Ucapan dari Tamu', 'condition' => [ 'enable_wa_notice' => 'yes', ], 'default' => "*Notifikasi RSVP*\n*[post_title]*\n\nHalo Kak [client_name]\nAda ucapan baru di undangan Anda:\n\n*Nama:* [guest_name]\n{group_block}*Grup:* [group_name]{/group_block}\n*Kehadiran:* [attendance_status]\n*Jumlah Tamu:* [guest_count] orang\n\n*Ucapan:*\n[guest_message]\n\n---------------------\nPesan ini dikirim otomatis.", ] );

        $this->add_control('guest_max', ['label' => esc_html__( 'Max Guests', 'eveent-widgets' ), 'type' => Controls_Manager::NUMBER, 'min' => 0, 'max' => 10, 'step' => 1, 'default' => 2, 'dynamic' => ['active' => true]]);
        $this->add_control('hide_max_guest', ['label' => esc_html__( 'Hide Max Guest', 'eveent-widgets' ), 'type' => Controls_Manager::SWITCHER, 'label_on' => esc_html__( 'Ya', 'eveent-widgets' ), 'label_off' => esc_html__( 'Tidak', 'eveent-widgets' ), 'return_value' => 'yes', 'default' => 'no', 'description' => esc_html__( 'Sembunyikan opsi jumlah tamu, atau bisa isi dengan max guest = 0 untuk menyembunyikan opsi jumlah tamu .', 'eveent-widgets' ),]);
        $this->add_control('hide_notsure', ['label' => esc_html__( 'Hide "Not Sure" Option', 'eveent-widgets' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'no']);
        $this->add_control('hide_sticker_button', ['label' => esc_html__( 'Hide Sticker Button', 'eveent-widgets' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'no']);
        $this->add_control('hide_attendance_badge', ['label' => esc_html__( 'Hide Attendance Badge in Comment List', 'eveent-widgets' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'no']);
        
        $this->add_control( 'custom_timezone', [ 'label' => esc_html__( 'Timezone', 'eveent-widgets' ), 'type' => Controls_Manager::SELECT, 'default' => 'default', 'options' => [ 'default'   => esc_html__( 'Default', 'eveent-widgets' ), 'WIB'       => 'WIB', 'WITA'      => 'WITA', 'WIT'       => 'WIT', ], 'description' => esc_html__( 'Pilih zona waktu untuk tampilan tanggal & jam ucapan.', 'eveent-widgets' ), ] );
        
        $this->add_control( 'enable_auto_guest_name', [ 'label' => esc_html__( 'Enable Automatic Guest Name', 'eveent-widgets' ), 'type' => Controls_Manager::SWITCHER, 'label_on' => esc_html__( 'Ya', 'eveent-widgets' ), 'label_off' => esc_html__( 'Tidak', 'eveent-widgets' ), 'return_value' => 'yes', 'default' => 'no', 'separator' => 'before', ] );
        $this->add_control( 'auto_guest_name_value', [ 'label' => 'Guest Name', 'type' => Controls_Manager::TEXT, 'default' => '', 'placeholder' => 'Masukkan nama tamu...', 'dynamic' => ['active' => true], 'condition' => [ 'enable_auto_guest_name' => 'yes', ], ] );
        
        $this->add_control( 'url_param_name', [ 'label' => 'URL Parameter Name', 'type' => Controls_Manager::TEXT, 'default' => 'to', 'description' => 'Nama parameter di URL untuk nama tamu (default: to -> ?to=Nama).', 'condition' => [ 'enable_auto_guest_name' => 'yes', ], ] );

        $this->add_control('guest_name_required',['label' => esc_html__( 'Enable Guest name is required', 'eveent-widgets' ),'type' => Controls_Manager::SWITCHER,'label_on' => esc_html__( 'Ya', 'eveent-widgets' ),'label_off' => esc_html__( 'Tidak', 'eveent-widgets' ),'return_value' => 'yes','default' => 'no','description' => esc_html__( 'Jika aktif, tamu hanya bisa mengirim ucapan jika mengakses dari link unik (mengandung ID tamu) dan nama tidak bisa diubah.', 'eveent-widgets' ),]);
        
        $this->add_control('hide_attendance_confirmation', [ 'label' => esc_html__( 'Hide Attendance Confirmation', 'eveent-widgets' ), 'type' => Controls_Manager::SWITCHER, 'label_on' => esc_html__( 'Ya', 'eveent-widgets' ), 'label_off' => esc_html__( 'Tidak', 'eveent-widgets' ), 'return_value' => 'yes', 'default' => 'no', 'description' => esc_html__( 'Jika aktif, form konfirmasi kehadiran akan disembunyikan dan hanya menampilkan form untuk mengirim ucapan.', 'eveent-widgets' ), ]);

        $this->add_control( 'hr_detailed_attendance', [ 'type' => Controls_Manager::DIVIDER, 'style' => 'thick', 'separator' => 'before', ] );

        $this->add_control( 'enable_detailed_attendance', [ 'label' => esc_html__( 'Enable Details Session Event', 'eveent-widgets' ), 'type' => Controls_Manager::SWITCHER, 'label_on' => esc_html__( 'Ya', 'eveent-widgets' ), 'label_off' => esc_html__( 'Tidak', 'eveent-widgets' ), 'return_value' => 'yes', 'default' => 'no', 'description' => esc_html__( 'Jika aktif, tamu yang memilih "Hadir" dapat memilih acara spesifik yang akan dihadiri.', 'eveent-widgets' ), ] );

        $repeater_events = new Repeater();

        $repeater_events->add_control( 'event_name', [ 'label' => esc_html__( 'Nama Acara', 'eveent-widgets' ), 'type' => Controls_Manager::TEXT, 'label_block' => true, 'default' => esc_html__( 'Akad Nikah' , 'eveent-widgets' ), 'dynamic' => [ 'active' => true, ], ] );

        $this->add_control( 'event_list', [ 'label' => esc_html__( 'Daftar Acara', 'eveent-widgets' ), 'type' => Controls_Manager::REPEATER, 'fields' => $repeater_events->get_controls(), 'default' => [ [ 'event_name' => 'Akad Nikah', ],  [ 'event_name' => 'Resepsi', ],    ], 'title_field' => '{{{ event_name }}}', 'condition' => [ 'enable_detailed_attendance' => 'yes', ], ] );
        
        $this->add_control( 'hr_merge_rsvp', [ 'type' => Controls_Manager::DIVIDER, 'style' => 'thick', 'separator' => 'before', ] );

        $this->add_control( 'merge_rsvp', [ 'label' => esc_html__( 'Enable merge join RSVP', 'eveent-widgets' ), 'type' => Controls_Manager::SWITCHER, 'label_on' => esc_html__( 'Ya', 'eveent-widgets' ), 'label_off' => esc_html__( 'Tidak', 'eveent-widgets' ), 'return_value' => 'yes', 'default' => 'no', 'description' => esc_html__( 'Aktifkan untuk menampilkan dan menyimpan ucapan ke Post ID lain.', 'eveent-widgets' ), ] );

        $this->add_control( 'source_post_id', [ 'label' => esc_html__( 'ID Post Sumber', 'eveent-widgets' ), 'type' => Controls_Manager::TEXT, 'placeholder' => esc_html__( 'Contoh: 78', 'eveent-widgets' ), 'description' => esc_html__( 'Semua ucapan akan ditampilkan dari dan disimpan ke ID Post ini.', 'eveent-widgets' ), 'condition' => [ 'merge_rsvp' => 'yes', ], 'dynamic' => [ 'active' => true, ], ] );

        $this->add_control( 'hr_pagination', [ 'type' => Controls_Manager::DIVIDER, 'style' => 'thick', 'separator' => 'before', ] );

        $this->add_control( 'enable_pagination', [ 'label' => esc_html__( 'Enable Comments Page', 'eveent-widgets' ), 'type' => Controls_Manager::SWITCHER, 'label_on' => esc_html__( 'Ya', 'eveent-widgets' ), 'label_off' => esc_html__( 'Tidak', 'eveent-widgets' ), 'return_value' => 'yes', 'default' => 'yes', 'description' => esc_html__( 'Aktifkan untuk membuat list komentar menjadi beberapa halaman.', 'eveent-widgets' ), 'condition' => [ 'hide_comment_list!' => 'yes', 'hide_message_input!' => 'yes' ] ] );

        $this->add_control( 'comments_per_page', [ 'label' => esc_html__( 'Komentar per laman', 'eveent-widgets' ), 'type' => Controls_Manager::NUMBER, 'min' => 1, 'max' => 100, 'step' => 1, 'default' => 10, 'condition' => [ 'enable_pagination' => 'yes', 'hide_comment_list!' => 'yes', 'hide_message_input!' => 'yes' ], 'dynamic' => [ 'active' => true, ], ] );

        $this->end_controls_section();
        
        

        $this->start_controls_section( 'section_reply_settings', ['label' => esc_html__( 'Reply Settings', 'eveent-widgets' )] );
        $this->add_control('enable_public_reply', ['label' => esc_html__( 'Enable Password Reply', 'eveent-widgets' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'no' ]);
        $this->add_control('reply_badge_text', ['label' => esc_html__( 'Reply Badge Text', 'eveent-widgets' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( 'Pemilik Acara', 'eveent-widgets' ), 'dynamic' => ['active' => true]]);
        $this->add_control( 'reply_avatar_image', [ 'label' => esc_html__( 'Reply Avatar', 'eveent-widgets' ), 'type' => Controls_Manager::MEDIA, 'default' => [ 'url' => '' ], 'dynamic' => ['active' => true]]);
        $this->end_controls_section();

        $this->start_controls_section('section_stickers', [ 'label' => esc_html__( 'Sticker List', 'eveent-widgets' ), ]);
        $repeater = new Repeater();
        $repeater->add_control( 'sticker_icon', [ 'label' => esc_html__( 'Stiker', 'eveent-widgets' ), 'type' => \Elementor\Controls_Manager::ICONS, 'default' => [ 'value' => 'fas fa-heart', 'library' => 'solid', ], ] );
        $this->add_control( 'sticker_list', [ 'label' => esc_html__( 'Available Stickers', 'eveent-widgets' ), 'type' => \Elementor\Controls_Manager::REPEATER, 'fields' => $repeater->get_controls(), 'default' => [ [ 'sticker_icon' => [ 'value' => 'fas fa-heart' ] ], [ 'sticker_icon' => [ 'value' => 'fas fa-glass-cheers' ] ] ], 'title_field' => '<span style="display: inline-block; width: 48px; height: 48px; line-height: 48px; text-align: center; font-size: 32px; vertical-align: middle; padding:5px;">{{{ (sticker_icon.library == "svg") ? \'<img src="\' + sticker_icon.value.url + \'" style="max-width: 100%; max-height: 100%;">\' : elementor.helpers.renderIcon( this, sticker_icon, {}, "i", "panel" ) || \'<i class="\' + sticker_icon.value + \'"></i>\' }}}</span>', ] );
        $this->end_controls_section();
        
        $this->start_controls_section('section_labels', ['label' => esc_html__( 'Form & Title Labels', 'eveent-widgets' )]);
        $this->add_control('text_main_title', ['label' => 'Judul Utama', 'type' => Controls_Manager::TEXT, 'default' => 'Ucapan & Doa', 'dynamic' => ['active' => true]]);
        $this->add_control('text_form_title', ['label' => 'Judul Form', 'type' => Controls_Manager::TEXT, 'default' => 'Kirim Ucapan & Konfirmasi Kehadiran', 'dynamic' => ['active' => true]]);
        $this->add_control('text_name_label', ['label' => 'Label Nama', 'type' => Controls_Manager::TEXT, 'default' => 'Nama Anda', 'dynamic' => ['active' => true]]);
        $this->add_control('text_name_placeholder', ['label' => 'Placeholder Nama', 'type' => Controls_Manager::TEXT, 'default' => 'Tulis nama Anda di sini...', 'dynamic' => ['active' => true]]);
        $this->add_control('text_confirm_label', ['label' => 'Label Konfirmasi', 'type' => Controls_Manager::TEXT, 'default' => 'Konfirmasi Kehadiran', 'dynamic' => ['active' => true]]);
        $this->add_control('text_btn_present', ['label' => 'Teks Tombol Hadir', 'type' => Controls_Manager::TEXT, 'default' => 'Hadir', 'dynamic' => ['active' => true]]);
        $this->add_control('text_btn_notpresent', ['label' => 'Teks Tombol Tidak Hadir', 'type' => Controls_Manager::TEXT, 'default' => 'Tidak Hadir', 'dynamic' => ['active' => true]]);
        $this->add_control('text_btn_notsure', ['label' => 'Teks Tombol Ragu-ragu', 'type' => Controls_Manager::TEXT, 'default' => 'Ragu-ragu', 'dynamic' => ['active' => true]]);
        
        $this->add_control('text_detailed_attendance_label', ['label' => 'Label Pilihan Acara', 'type' => Controls_Manager::TEXT, 'default' => 'Saya akan hadir di acara:', 'dynamic' => ['active' => true], 'condition' => ['enable_detailed_attendance' => 'yes']]);

        $this->add_control('text_guest_option', ['label' => 'Teks Opsi Tamu', 'type' => Controls_Manager::TEXT, 'default' => 'Orang', 'dynamic' => ['active' => true]]);
        $this->add_control('text_comment_label', ['label' => 'Label Ucapan', 'type' => Controls_Manager::TEXT, 'default' => 'Ucapan & Doa', 'dynamic' => ['active' => true]]);
        $this->add_control('text_comment_placeholder', ['label' => 'Placeholder Ucapan', 'type' => Controls_Manager::TEXT, 'default' => 'Tulis ucapan atau pilih stiker...', 'dynamic' => ['active' => true]]);
        $this->add_control('text_btn_sticker', ['label' => 'Teks Tombol Pilih Stiker', 'type' => Controls_Manager::TEXT, 'default' => 'Pilih Stiker', 'dynamic' => ['active' => true]]);
        $this->add_control('text_btn_submit', ['label' => 'Teks Tombol Kirim', 'type' => Controls_Manager::TEXT, 'default' => 'Kirim Ucapan', 'dynamic' => ['active' => true]]);
        $this->end_controls_section();

        $this->start_controls_section('section_pagination_labels', ['label' => esc_html__( 'Pagination Labels', 'eveent-widgets' ), 'condition' => [ 'hide_comment_list!' => 'yes', 'hide_message_input!' => 'yes' ]]);
        $this->add_control('text_btn_prev', ['label' => 'Teks Tombol Sebelumnya', 'type' => Controls_Manager::TEXT, 'default' => '← Sebelumnya', 'dynamic' => ['active' => true]]);
        $this->add_control('text_btn_next', ['label' => 'Teks Tombol Selanjutnya', 'type' => Controls_Manager::TEXT, 'default' => 'Selanjutnya →', 'dynamic' => ['active' => true]]);
        $this->end_controls_section();
        
        $this->start_controls_section(
            'section_documentation',
            [
                'label' => esc_html__( 'Meta Key', 'eveent-widgets' ),
            ]
        );

        $this->add_control(
            'doc_meta_keys',
            [
                'type' => Controls_Manager::RAW_HTML,
                'raw' => '
                    <div style="font-family: Roboto, Arial, sans-serif; font-style: normal !important; line-height: 1.5; color: #333;">
                        
                        <div style="background-color: #eef2f5; padding: 10px; border-radius: 5px; margin-bottom: 12px; border-left: 4px solid #3582c4;">
                            <div style="font-size: 12px; font-weight: bold; color: #1d2327; font-style: normal !important;">Tipe Value: "ya"</div>
                            <div style="font-size: 11px; color: #555; font-style: normal !important;">Isi dengan teks <b>ya</b> untuk mengaktifkan:</div>
                        </div>

                        <div style="display: grid; gap: 8px; margin-bottom: 20px;">
                            <div style="background: #fff; border: 1px solid #ddd; padding: 8px; border-radius: 4px;">
                                <code style="font-style: normal !important; color: #d63638; font-weight: bold; font-size: 11px; background: none; padding: 0; display: block; margin-bottom: 4px;">_rsvp_hide_notsure</code>
                                <span style="font-size: 11px; color: #666; font-style: normal !important;">Sembunyikan opsi "Ragu-ragu"</span>
                            </div>
                            <div style="background: #fff; border: 1px solid #ddd; padding: 8px; border-radius: 4px;">
                                <code style="font-style: normal !important; color: #d63638; font-weight: bold; font-size: 11px; background: none; padding: 0; display: block; margin-bottom: 4px;">_rsvp_hide_max_guest</code>
                                <span style="font-size: 11px; color: #666; font-style: normal !important;">Sembunyikan opsi jumlah tamu</span>
                            </div>
                            <div style="background: #fff; border: 1px solid #ddd; padding: 8px; border-radius: 4px;">
                                <code style="font-style: normal !important; color: #d63638; font-weight: bold; font-size: 11px; background: none; padding: 0; display: block; margin-bottom: 4px;">_rsvp_hide_sticker</code>
                                <span style="font-size: 11px; color: #666; font-style: normal !important;">Sembunyikan tombol stiker</span>
                            </div>
                            <div style="background: #fff; border: 1px solid #ddd; padding: 8px; border-radius: 4px;">
                                <code style="font-style: normal !important; color: #d63638; font-weight: bold; font-size: 11px; background: none; padding: 0; display: block; margin-bottom: 4px;">_rsvp_hide_badge</code>
                                <span style="font-size: 11px; color: #666; font-style: normal !important;">Sembunyikan badge kehadiran</span>
                            </div>
                            <div style="background: #fff; border: 1px solid #ddd; padding: 8px; border-radius: 4px;">
                                <code style="font-style: normal !important; color: #d63638; font-weight: bold; font-size: 11px; background: none; padding: 0; display: block; margin-bottom: 4px;">_rsvp_hide_attendance</code>
                                <span style="font-size: 11px; color: #666; font-style: normal !important;">Sembunyikan pilihan hadir/tidak</span>
                            </div>
                            <div style="background: #fff; border: 1px solid #ddd; padding: 8px; border-radius: 4px;">
                                <code style="font-style: normal !important; color: #d63638; font-weight: bold; font-size: 11px; background: none; padding: 0; display: block; margin-bottom: 4px;">_rsvp_hide_list</code>
                                <span style="font-size: 11px; color: #666; font-style: normal !important;">Sembunyikan daftar ucapan di bawah form</span>
                            </div>
                            <div style="background: #fff; border: 1px solid #ddd; padding: 8px; border-radius: 4px;">
                                <code style="font-style: normal !important; color: #d63638; font-weight: bold; font-size: 11px; background: none; padding: 0; display: block; margin-bottom: 4px;">_rsvp_sesi</code>
                                <span style="font-size: 11px; color: #666; font-style: normal !important;">Aktifkan pilihan Sesi Acara</span>
                            </div>
                            <div style="background: #fff; border: 1px solid #ddd; padding: 8px; border-radius: 4px;">
                                <code style="font-style: normal !important; color: #d63638; font-weight: bold; font-size: 11px; background: none; padding: 0; display: block; margin-bottom: 4px;">_rsvp_only</code>
                                <span style="font-size: 11px; color: #666; font-style: normal !important;">Form hanya mode Konfirmasi / RSVP tanpa ucapan komentar</span>
                            </div>
                            <div style="background: #fff; border: 1px solid #ddd; padding: 8px; border-radius: 4px;">
                                <code style="font-style: normal !important; color: #d63638; font-weight: bold; font-size: 11px; background: none; padding: 0; display: block; margin-bottom: 4px;">_rsvp_join</code>
                                <span style="font-size: 11px; color: #666; font-style: normal !important;">Gabungkan data RSVP ke ID lain</span>
                            </div>
                        </div>

                        <div style="background-color: #f0f4e6; padding: 10px; border-radius: 5px; margin-bottom: 12px; border-left: 4px solid #71a92c;">
                            <div style="font-size: 12px; font-weight: bold; color: #1d2327; font-style: normal !important;">Tipe Value: Bebas/Khusus</div>
                        </div>

                        <div style="display: grid; gap: 8px;">
                            <div style="background: #fff; border: 1px solid #ddd; padding: 8px; border-radius: 4px;">
                                <code style="font-style: normal !important; color: #007cba; font-weight: bold; font-size: 11px; background: none; padding: 0; display: block; margin-bottom: 4px;">_rsvp_max</code>
                                <span style="font-size: 11px; color: #666; font-style: normal !important;">Isi angka max tamu (misal: 5)</span>
                            </div>
                            <div style="background: #fff; border: 1px solid #ddd; padding: 8px; border-radius: 4px;">
                                <code style="font-style: normal !important; color: #007cba; font-weight: bold; font-size: 11px; background: none; padding: 0; display: block; margin-bottom: 4px;">_rsvp_join_id</code>
                                <span style="font-size: 11px; color: #666; font-style: normal !important;">Isi ID Post target (misal: 123)</span>
                            </div>
                            <div style="background: #fff; border: 1px solid #ddd; padding: 8px; border-radius: 4px;">
                                <code style="font-style: normal !important; color: #007cba; font-weight: bold; font-size: 11px; background: none; padding: 0; display: block; margin-bottom: 4px;">_rsvp_sesi_list</code>
                                <span style="font-size: 11px; color: #666; font-style: normal !important;">Nama sesi (misal: Akad, Resepsi)</span>
                            </div>
                        </div>

                    </div>
                ',
            ]
        );

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

        $this->start_controls_section('section_style_form_title', ['label' => 'Form Title Style', 'tab' => Controls_Manager::TAB_STYLE, 'condition' => [ 'show_form_title' => 'yes' ]]);
        $this->add_control('form_title_color', ['label' => 'Color', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .ev-rsvp-form-title' => 'color: {{VALUE}};']]);
        $this->add_group_control(Group_Control_Typography::get_type(), ['name' => 'form_title_typography', 'selector' => '{{WRAPPER}} .ev-rsvp-form-title']);
        $this->add_responsive_control('form_title_align', ['label' => 'Alignment', 'type' => Controls_Manager::CHOOSE, 'options' => ['left' => ['title' => 'Left', 'icon' => 'eicon-text-align-left'], 'center' => ['title' => 'Center', 'icon' => 'eicon-text-align-center'], 'right' => ['title' => 'Right', 'icon' => 'eicon-text-align-right']], 'default' => 'center', 'selectors' => ['{{WRAPPER}} .ev-rsvp-form-title' => 'text-align: {{VALUE}};']]);
        $this->add_responsive_control('form_title_margin', ['label' => 'Margin', 'type' => Controls_Manager::DIMENSIONS, 'size_units' => ['px', '%', 'em'], 'selectors' => ['{{WRAPPER}} .ev-rsvp-form-title' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};']]);
        $this->end_controls_section();

        $this->start_controls_section('section_style_form', ['label' => 'Form Style', 'tab' => Controls_Manager::TAB_STYLE]);
        $this->add_control('form_bg_color', ['label' => 'Form Background', 'type' => Controls_Manager::COLOR, 'default' => '#f7fafc', 'selectors' => ['{{WRAPPER}} .ev-rsvp-form-wrapper' => 'background-color: {{VALUE}};']]);
        $this->add_group_control(Group_Control_Border::get_type(), ['name' => 'form_border', 'selector' => '{{WRAPPER}} .ev-rsvp-form-wrapper']);
        $this->end_controls_section();
        
        $this->start_controls_section('section_style_form_fields', ['label' => 'Form Fields', 'tab' => Controls_Manager::TAB_STYLE]);
        $this->add_control('heading_form_labels', ['label' => 'Form Labels', 'type' => Controls_Manager::HEADING]);
        $this->add_control('label_color', ['label' => 'Text Color', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .ev-rsvp-form label, {{WRAPPER}} .ev-rsvp-form legend' => 'color: {{VALUE}};']]);
        $this->add_group_control(Group_Control_Typography::get_type(), ['name' => 'label_typography', 'selector' => '{{WRAPPER}} .ev-rsvp-form label, {{WRAPPER}} .ev-rsvp-form legend']);
        $this->add_responsive_control('label_margin', ['label' => 'Margin Bottom', 'type' => Controls_Manager::SLIDER, 'size_units' => ['px', 'em'], 'range' => ['px' => ['min' => 0, 'max' => 50]], 'selectors' => ['{{WRAPPER}} .ev-rsvp-form label, {{WRAPPER}} .ev-rsvp-form legend' => 'margin-bottom: {{SIZE}}{{UNIT}};']]);

        $this->add_control('heading_form_inputs', ['label' => 'Input & Select Fields', 'type' => Controls_Manager::HEADING, 'separator' => 'before']);
        $this->add_group_control(Group_Control_Typography::get_type(), ['name' => 'input_typography', 'selector' => '{{WRAPPER}} .ev-rsvp-form input[type="text"], {{WRAPPER}} .ev-rsvp-form textarea, {{WRAPPER}} .ev-rsvp-form select']);
        $this->add_control('input_text_color', ['label' => 'Text Color', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .ev-rsvp-form input[type="text"], {{WRAPPER}} .ev-rsvp-form textarea, {{WRAPPER}} .ev-rsvp-form select' => 'color: {{VALUE}};']]);
        $this->add_control('input_placeholder_color', ['label' => 'Placeholder Color', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .ev-rsvp-form input[type="text"]::placeholder, {{WRAPPER}} .ev-rsvp-form textarea::placeholder' => 'color: {{VALUE}};']]);
        $this->add_control('input_bg_color', ['label' => 'Background Color', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .ev-rsvp-form input[type="text"], {{WRAPPER}} .ev-rsvp-form textarea, {{WRAPPER}} .ev-rsvp-form select' => 'background-color: {{VALUE}};']]);
        $this->add_group_control(Group_Control_Border::get_type(), ['name' => 'input_border', 'selector' => '{{WRAPPER}} .ev-rsvp-form input[type="text"], {{WRAPPER}} .ev-rsvp-form textarea, {{WRAPPER}} .ev-rsvp-form select']);
        $this->add_control('input_radius', ['label' => 'Border Radius', 'type' => Controls_Manager::DIMENSIONS, 'selectors' => ['{{WRAPPER}} .ev-rsvp-form input[type="text"], {{WRAPPER}} .ev-rsvp-form textarea, {{WRAPPER}} .ev-rsvp-form select' => 'border-radius: {{TOP}}px {{RIGHT}}px {{BOTTOM}}px {{LEFT}}px;']]);
        $this->add_group_control(Group_Control_Box_Shadow::get_type(), ['name' => 'input_shadow', 'selector' => '{{WRAPPER}} .ev-rsvp-form input[type="text"], {{WRAPPER}} .ev-rsvp-form textarea, {{WRAPPER}} .ev-rsvp-form select']);
        $this->end_controls_section();
        
        $this->start_controls_section( 'section_style_detailed_attendance', [ 'label' => esc_html__( 'List Detailed Event', 'eveent-widgets' ), 'tab' => Controls_Manager::TAB_STYLE, 'condition' => [ 'enable_detailed_attendance' => 'yes', ], ] );
        $this->add_control( 'heading_detailed_container', [ 'label' => esc_html__( 'Container Style', 'eveent-widgets' ), 'type' => Controls_Manager::HEADING, ] );
        $this->add_control( 'detailed_container_bg_color', [ 'label' => esc_html__( 'Background Color', 'eveent-widgets' ), 'type' => Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .ev-rsvp-detailed-attendance-wrapper' => 'background-color: {{VALUE}};', ], ] );
        $this->add_responsive_control( 'detailed_container_padding', [ 'label' => esc_html__( 'Padding', 'eveent-widgets' ), 'type' => Controls_Manager::DIMENSIONS, 'size_units' => [ 'px', '%', 'em' ], 'selectors' => [ '{{WRAPPER}} .ev-rsvp-detailed-attendance-wrapper' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};', ], ] );
        $this->add_group_control( Group_Control_Border::get_type(), [ 'name' => 'detailed_container_border', 'selector' => '{{WRAPPER}} .ev-rsvp-detailed-attendance-wrapper', ] );
        $this->add_control( 'detailed_container_radius', [ 'label' => esc_html__( 'Border Radius', 'eveent-widgets' ), 'type' => Controls_Manager::DIMENSIONS, 'size_units' => [ 'px', '%' ], 'selectors' => [ '{{WRAPPER}} .ev-rsvp-detailed-attendance-wrapper' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};', ], ] );
        $this->add_control( 'heading_detailed_checkboxes', [ 'label' => esc_html__( 'Checkbox Style', 'eveent-widgets' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before', ] );
        $this->add_group_control( Group_Control_Typography::get_type(), [ 'name' => 'detailed_checkbox_typography', 'selector' => '{{WRAPPER}} .ev-rsvp-checkbox-item label', ] );
        $this->start_controls_tabs( 'tabs_detailed_checkbox_style' );
        $this->start_controls_tab( 'tab_detailed_checkbox_normal', [ 'label' => esc_html__( 'Normal', 'eveent-widgets' ), ] );
        $this->add_control( 'detailed_checkbox_text_color', [ 'label' => esc_html__( 'Text Color', 'eveent-widgets' ), 'type' => Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .ev-rsvp-checkbox-item label' => 'color: {{VALUE}};', ], ] );
        $this->add_control( 'detailed_checkbox_bg_color', [ 'label' => esc_html__( 'Background Color', 'eveent-widgets' ), 'type' => Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .ev-rsvp-checkbox-item label' => 'background-color: {{VALUE}};', ], ] );
        $this->add_group_control( Group_Control_Border::get_type(), [ 'name' => 'detailed_checkbox_border', 'selector' => '{{WRAPPER}} .ev-rsvp-checkbox-item label', ] );
        $this->end_controls_tab();
        $this->start_controls_tab( 'tab_detailed_checkbox_active', [ 'label' => esc_html__( 'Active', 'eveent-widgets' ), ] );
        $this->add_control( 'detailed_checkbox_text_color_active', [ 'label' => esc_html__( 'Text Color', 'eveent-widgets' ), 'type' => Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .ev-rsvp-checkbox-item input[type="checkbox"]:checked + label' => 'color: {{VALUE}};', ], ] );
        $this->add_control( 'detailed_checkbox_bg_color_active', [ 'label' => esc_html__( 'Background Color', 'eveent-widgets' ), 'type' => Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .ev-rsvp-checkbox-item input[type="checkbox"]:checked + label' => 'background-color: {{VALUE}}; border-color: transparent;', ], ] );
        $this->end_controls_tab();
        $this->end_controls_tabs();
        $this->end_controls_section();

        $this->start_controls_section('section_style_buttons', ['label' => 'Buttons', 'tab' => Controls_Manager::TAB_STYLE]);
        $this->start_controls_tabs('tabs_buttons_style');
        $this->start_controls_tab('tab_confirmation_buttons', ['label' => 'Confirmation']);
        $this->add_group_control(Group_Control_Typography::get_type(), ['name' => 'confirm_btn_typography', 'selector' => '{{WRAPPER}} .ev-rsvp-attendance-button span', 'separator' => 'after']);
        $this->add_responsive_control('confirm_btn_padding', ['label' => 'Padding', 'type' => Controls_Manager::DIMENSIONS, 'size_units' => ['px', 'em'], 'selectors' => ['{{WRAPPER}} .ev-rsvp-attendance-button span' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};']]);
        $this->add_control('heading_btn_present', ['label' => 'Tombol Hadir', 'type' => Controls_Manager::HEADING, 'separator' => 'before']);
        $this->add_control('present_text_color', ['label' => 'Text Color', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .attendance-present span' => 'color: {{VALUE}};']]);
        $this->add_control('present_bg_color', ['label' => 'Background', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .attendance-present span' => 'background-color: {{VALUE}};']]);
        $this->add_group_control(Group_Control_Border::get_type(), ['name' => 'present_border', 'selector' => '{{WRAPPER}} .attendance-present span']);
        $this->add_control('present_text_color_active', ['label' => 'Text Color (Active)', 'type' => Controls_Manager::COLOR, 'default' => '#ffffff', 'selectors' => ['{{WRAPPER}} .attendance-present input:checked + span' => 'color: {{VALUE}};']]);
        $this->add_control('present_bg_color_active', ['label' => 'Background (Active)', 'type' => Controls_Manager::COLOR, 'default' => '#16a34a', 'selectors' => ['{{WRAPPER}} .attendance-present input:checked + span' => 'background-color: {{VALUE}}; border-color: {{VALUE}};']]);
        $this->add_control('heading_btn_notpresent', ['label' => 'Tombol Tidak Hadir', 'type' => Controls_Manager::HEADING, 'separator' => 'before']);
        $this->add_control('notpresent_text_color', ['label' => 'Text Color', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .attendance-notpresent span' => 'color: {{VALUE}};']]);
        $this->add_control('notpresent_bg_color', ['label' => 'Background', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .attendance-notpresent span' => 'background-color: {{VALUE}};']]);
        $this->add_group_control(Group_Control_Border::get_type(), ['name' => 'notpresent_border', 'selector' => '{{WRAPPER}} .attendance-notpresent span']);
        $this->add_control('notpresent_text_color_active', ['label' => 'Text Color (Active)', 'type' => Controls_Manager::COLOR, 'default' => '#ffffff', 'selectors' => ['{{WRAPPER}} .attendance-notpresent input:checked + span' => 'color: {{VALUE}};']]);
        $this->add_control('notpresent_bg_color_active', ['label' => 'Background (Active)', 'type' => Controls_Manager::COLOR, 'default' => '#dc2626', 'selectors' => ['{{WRAPPER}} .attendance-notpresent input:checked + span' => 'background-color: {{VALUE}}; border-color: {{VALUE}};']]);
        $this->add_control('heading_btn_notsure', ['label' => 'Tombol Ragu-ragu', 'type' => Controls_Manager::HEADING, 'separator' => 'before']);
        $this->add_control('notsure_text_color', ['label' => 'Text Color', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .attendance-notsure span' => 'color: {{VALUE}};']]);
        $this->add_control('notsure_bg_color', ['label' => 'Background', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .attendance-notsure span' => 'background-color: {{VALUE}};']]);
        $this->add_group_control(Group_Control_Border::get_type(), ['name' => 'notsure_border', 'selector' => '{{WRAPPER}} .attendance-notsure span']);
        $this->add_control('notsure_text_color_active', ['label' => 'Text Color (Active)', 'type' => Controls_Manager::COLOR, 'default' => '#ffffff', 'selectors' => ['{{WRAPPER}} .attendance-notsure input:checked + span' => 'color: {{VALUE}};']]);
        $this->add_control('notsure_bg_color_active', ['label' => 'Background (Active)', 'type' => Controls_Manager::COLOR, 'default' => '#718096', 'selectors' => ['{{WRAPPER}} .attendance-notsure input:checked + span' => 'background-color: {{VALUE}}; border-color: {{VALUE}};']]);
        $this->end_controls_tab();
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

        $this->start_controls_section('section_style_pagination', ['label' => 'Pagination Buttons', 'tab' => Controls_Manager::TAB_STYLE, 'condition' => [ 'hide_comment_list!' => 'yes', 'hide_message_input!' => 'yes' ]]);
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

        $this->start_controls_section('section_style_comment_list', ['label' => 'Comment List', 'tab' => Controls_Manager::TAB_STYLE, 'condition' => [ 'hide_comment_list!' => 'yes', 'hide_message_input!' => 'yes' ]]);
        $this->add_responsive_control('list_max_height', ['label' => 'Max Height', 'type' => Controls_Manager::SLIDER, 'size_units' => ['px', 'vh'], 'range' => ['px' => ['min' => 200, 'max' => 2000]], 'selectors' => ['{{WRAPPER}} .ev-rsvp-list-container' => 'max-height: {{SIZE}}{{UNIT}};']]);
        $this->add_control('card_bg_color', ['label' => 'Card Background', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .ev-rsvp-comment-item-wrapper' => 'background-color: {{VALUE}};']]);
        $this->add_group_control(Group_Control_Border::get_type(), ['name' => 'card_border', 'selector' => '{{WRAPPER}} .ev-rsvp-comment-item-wrapper']);
        $this->add_group_control(Group_Control_Box_Shadow::get_type(), ['name' => 'card_shadow', 'selector' => '{{WRAPPER}} .ev-rsvp-comment-item-wrapper']);
        $this->add_control('heading_initials_avatar', ['label' => 'Initial Name Avatar', 'type' => Controls_Manager::HEADING, 'separator' => 'before']);
        $this->add_group_control(Group_Control_Typography::get_type(), ['name' => 'initials_typography', 'selector' => '{{WRAPPER}} .ev-rsvp-initials-avatar']);
        $this->add_control('initials_color', ['label' => 'Text Color', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .ev-rsvp-initials-avatar' => 'color: {{VALUE}};']]);
        $this->add_control('initials_bg_color', ['label' => 'Background Color', 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .ev-rsvp-initials-avatar' => 'background-color: {{VALUE}};']]);
        $this->end_controls_section();

        $this->start_controls_section('section_style_comment_content', ['label' => 'Comment Content Style', 'tab' => Controls_Manager::TAB_STYLE, 'condition' => [ 'hide_comment_list!' => 'yes', 'hide_message_input!' => 'yes' ]]);
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

        $this->start_controls_section('section_style_badges', ['label' => 'Badges', 'tab' => Controls_Manager::TAB_STYLE, 'condition' => [ 'hide_comment_list!' => 'yes', 'hide_message_input!' => 'yes' ]]);
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
        
        $this->start_controls_section('section_style_reply', ['label' => 'Reply Style', 'tab' => Controls_Manager::TAB_STYLE, 'condition' => [ 'hide_comment_list!' => 'yes', 'hide_message_input!' => 'yes' ]]);
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
    $barcode_uid = isset($_GET['id']) ? sanitize_text_field($_GET['id']) : null;
    $guest_allowed_events_keys = [];
    $guest_rsvp_count = null;
    $is_personal_invite = false;
    $guest_name_attributes = '';
    $is_readonly = false;
    $placeholder_text = $settings['text_name_placeholder'];
    $param_name = !empty($settings['url_param_name']) ? $settings['url_param_name'] : 'to';

    if ( ! \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
        if ( $barcode_uid ) {
            $guest_data = apply_filters('eveent_get_guest_data', [], $barcode_uid);
            $guest_allowed_events_keys = $guest_data['allowed_events_keys'] ?? [];
            if ( isset($guest_data['rsvp_count']) && is_numeric($guest_data['rsvp_count']) && $guest_data['rsvp_count'] > 0 ) {
                $guest_rsvp_count = min(10, intval($guest_data['rsvp_count']));  
            }
        }
        $is_personal_invite = !empty($barcode_uid) && !empty($guest_allowed_events_keys);
        
        if ( get_post_meta( $post_id, '_rsvp_hide_notsure', true ) === 'ya' ) $settings['hide_notsure'] = 'yes';
        if ( get_post_meta( $post_id, '_rsvp_hide_max_guest', true ) === 'ya' ) $settings['hide_max_guest'] = 'yes';
        if ( get_post_meta( $post_id, '_rsvp_hide_sticker', true ) === 'ya' ) $settings['hide_sticker_button'] = 'yes';
        if ( get_post_meta( $post_id, '_rsvp_hide_badge', true ) === 'ya' ) $settings['hide_attendance_badge'] = 'yes';
        if ( get_post_meta( $post_id, '_rsvp_hide_attendance', true ) === 'ya' ) $settings['hide_attendance_confirmation'] = 'yes';
        if ( get_post_meta( $post_id, '_rsvp_hide_list', true ) === 'ya' ) $settings['hide_comment_list'] = 'yes';
        if ( get_post_meta( $post_id, '_rsvp_sesi', true ) === 'ya' ) $settings['enable_detailed_attendance'] = 'yes';
        
        if ( get_post_meta( $post_id, '_rsvp_only', true ) === 'ya' ) {
            $settings['hide_message_input'] = 'yes';
        }

        if ( get_post_meta( $post_id, '_rsvp_join', true ) === 'ya' ) {
            $settings['merge_rsvp'] = 'yes';
            $join_post_id = get_post_meta( $post_id, '_rsvp_join_id', true );
            if ( ! empty( $join_post_id ) && is_numeric( $join_post_id ) ) {
                $settings['source_post_id'] = $join_post_id;
            }
        }
        
        if ( ! empty( $settings['merge_rsvp'] ) && 'yes' === $settings['merge_rsvp'] && ! empty( $settings['source_post_id'] ) ) {
            $source_id = intval( $settings['source_post_id'] );
            if ( $source_id > 0 ) $post_id = $source_id;
        }

        $max_guest_meta = get_post_meta( $post_id, '_rsvp_max', true );
        if ( ! empty( $max_guest_meta ) && is_numeric( $max_guest_meta ) ) $settings['guest_max'] = $max_guest_meta;
        if ( !empty($guest_rsvp_count) ) $settings['guest_max'] = $guest_rsvp_count;
        
        $label_meta_map = [
            '_rsvp_comments'     => 'text_main_title',
            '_rsvp_name'         => 'text_name_label',
            '_rsvp_confirm'      => 'text_confirm_label',
            '_rsvp_present'      => 'text_btn_present',
            '_rsvp_notpresent'   => 'text_btn_notpresent',
            '_rsvp_notsure'      => 'text_btn_notsure',
            '_rsvp_comment'      => 'text_comment_label',
            '_rsvp_sticker'      => 'text_btn_sticker',
            '_rsvp_submit'       => 'text_btn_submit',
            '_rsvp_person'       => 'text_guest_option',
            '_rsvp_reply'        => 'text_btn_reply',
            '_rsvp_sesi_label'   => 'text_detailed_attendance_label',
            '_rsvp_pc_comment'   => 'text_comment_placeholder'
        ];

        foreach ( $label_meta_map as $meta_key => $setting_key ) {
            $meta_value = get_post_meta( $post_id, $meta_key, true );
            if ( ! empty( $meta_value ) ) $settings[ $setting_key ] = $meta_value;
        }
        
        $sesi_list_meta = get_post_meta( $post_id, '_rsvp_sesi_list', true );
        if ( ! empty( trim( $sesi_list_meta ) ) ) {
            $events = explode(',', $sesi_list_meta);
            $new_event_list = [];
            foreach ( $events as $event_name ) {
                $trimmed_name = trim( $event_name );
                if ( ! empty( $trimmed_name ) ) $new_event_list[] = [ 'event_name' => $trimmed_name ];
            }
            if ( ! empty( $new_event_list ) ) $settings['event_list'] = $new_event_list;
        }
        
        $name_placeholder_meta = get_post_meta( $post_id, '_rsvp_pc_name', true );
        if ( ! empty( $name_placeholder_meta ) ) $placeholder_text = $name_placeholder_meta; 

        $is_group_invite = isset($_GET['group']) && $_GET['group'] === 'invite';
        
        if (!$is_group_invite) {
            if ( !empty($settings['guest_name_required']) && 'yes' === $settings['guest_name_required'] ) {
                $is_readonly = true;
                $placeholder_text = 'Khusus Tamu Terdaftar';  
            }
            if ( !empty($settings['enable_auto_guest_name']) && 'yes' === $settings['enable_auto_guest_name'] && !empty($settings['auto_guest_name_value']) ) {
                $guest_name = $settings['auto_guest_name_value'];
                $guest_name_processed = html_entity_decode($guest_name, ENT_QUOTES, 'UTF-8');
                $guest_name_attributes .= 'value="' . esc_attr($guest_name_processed) . '"';
                
            }
        } else {
            $is_readonly = false;  
            $guest_name_attributes = 'value=""';  
        }
        
        if ($is_readonly) $guest_name_attributes .= ' readonly';
    }

    if ( isset( $settings['hide_message_input'] ) && 'yes' === $settings['hide_message_input'] ) {
        $settings['hide_comment_list'] = 'yes';
        $settings['text_btn_submit'] = 'Kirim RSVP';
    }
    if ( isset($settings['guest_max']) && intval($settings['guest_max']) === 0 ) {
        $settings['hide_max_guest'] = 'yes';
    }
    
    $checkbox_styles = [];
    if ( ! empty( $settings['checkbox_label_color'] ) ) {
        $checkbox_styles[] = 'color: ' . $settings['checkbox_label_color'] . ' !important;';
    }
    if ( ! empty( $settings['checkbox_label_typography_font_size']['size'] ) ) {
        $font_size = $settings['checkbox_label_typography_font_size']['size'];
        $font_unit = $settings['checkbox_label_typography_font_size']['unit'];
        $checkbox_styles[] = 'font-size: ' . $font_size . $font_unit . ' !important;';
    }
    if ( ! empty( $settings['checkbox_label_typography_font_weight'] ) ) {
        $checkbox_styles[] = 'font-weight: ' . $settings['checkbox_label_typography_font_weight'] . ' !important;';
    }
    
    $checkbox_styles[] = 'opacity: 1 !important;';
    $checkbox_styles[] = 'visibility: visible !important;';
    $checkbox_styles[] = 'text-indent: 0 !important;';
    $checkbox_styles[] = 'display: inline !important;';

    $preview_placeholder_text = $settings['text_name_placeholder'];
    $preview_guest_name_attributes = '';

    if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
        if ( !empty($settings['guest_name_required']) && 'yes' === $settings['guest_name_required'] ) {
            $preview_guest_name_attributes = 'value="Khusus Tamu Terdaftar" readonly';
            $preview_placeholder_text = 'Khusus Tamu Terdaftar';
        } else if ( !empty($settings['enable_auto_guest_name']) && 'yes' === $settings['enable_auto_guest_name'] && !empty($settings['auto_guest_name_value']) ) {
            $guest_name = $settings['auto_guest_name_value'];
            $guest_name_processed = html_entity_decode($guest_name, ENT_QUOTES, 'UTF-8');
            $preview_guest_name_attributes = 'value="' . esc_attr($guest_name_processed) . '"'; 
        }
    }

    if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
        ?>
        <div class="ev-rsvp-wrapper" data-url-param="<?php echo esc_attr($param_name); ?>">
             <?php if ( 'yes' === $settings['show_main_title'] && $settings['hide_comment_list'] !== 'yes' ) : ?>
             <div class="ev-rsvp-main-title-wrapper">
                  <h3 class="ev-rsvp-main-title"><span class="ev-rsvp-count">1</span> <?php echo esc_html($settings['text_main_title']); ?></h3>
             </div>
             <?php endif; ?>
             <div class="ev-rsvp-form-wrapper">
                  <?php if ( 'yes' === $settings['show_form_title'] ) : ?>
                  <h4 class="ev-rsvp-form-title"><?php echo esc_html($settings['text_form_title']); ?></h4>
                  <?php endif; ?>
                  <form id="ev-rsvp-form-preview" class="ev-rsvp-form" method="post">
                        <div class="ev-rsvp-field"><label for="author-preview"><?php echo esc_html($settings['text_name_label']); ?></label><input type="text" id="author-preview" name="author" placeholder="<?php echo esc_attr($preview_placeholder_text); ?>" required <?php echo $preview_guest_name_attributes; ?>></div>
                        
                        <?php if ( 'yes' !== $settings['hide_attendance_confirmation'] ) : ?>
                        <div class="ev-rsvp-field">
                            <label><?php echo esc_html($settings['text_confirm_label']); ?></label>
                            <div class="ev-rsvp-attendance-grid">
                               <div class="ev-rsvp-attendance-row"><div class="ev-rsvp-attendance-button attendance-present"><label><input type="radio" name="attendance_preview" value="present" checked><span><?php echo esc_html($settings['text_btn_present']); ?></span></label></div>
                               <?php if ( 'yes' !== $settings['hide_max_guest'] ) :  ?><div class="ev-rsvp-guest-field" style="display: block;"><select id="guest-preview" name="guest">
                                    <?php for ( $i = 1; $i <= $settings['guest_max']; $i++ ) : ?>
                                            <option value="<?php echo $i; ?>"><?php echo $i . ' ' . esc_html($settings['text_guest_option']); ?></option>
                                    <?php endfor; ?>
                                    </select></div><?php endif; ?>
                                    </div>
                               <div class="ev-rsvp-attendance-row"><?php if ($settings['hide_notsure'] !== 'yes') : ?><div class="ev-rsvp-attendance-button attendance-notsure"><label><input type="radio" name="attendance_preview" value="notsure"><span><?php echo esc_html($settings['text_btn_notsure']); ?></span></label></div><?php endif; ?><div class="ev-rsvp-attendance-button attendance-notpresent"><label><input type="radio" name="attendance_preview" value="notpresent"><span><?php echo esc_html($settings['text_btn_notpresent']); ?></span></label></div></div>
                           </div>
                           <?php if ( 'yes' === $settings['enable_detailed_attendance'] && ! empty( $settings['event_list'] ) ) : ?>
                            <div class="ev-rsvp-guest-field ev-rsvp-detailed-attendance-wrapper" style="display: block; margin-top: 15px;">
                                 <label style="margin-bottom: 8px; display: block;"><?php echo esc_html($settings['text_detailed_attendance_label']); ?></label>
                                 <div class="ev-rsvp-checkbox-grid">
                                      <?php foreach ( $settings['event_list'] as $event ) : ?>
                                           <div class="ev-rsvp-checkbox-item" style="margin-bottom: 5px;">
                                                <input type="checkbox" disabled>
                                                <label style="opacity:1; visibility:visible; text-indent:0; display:inline;"><?php echo esc_html($event['event_name']); ?></label>
                                           </div>
                                      <?php endforeach; ?>
                                 </div>
                            </div>
                        <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ( 'yes' !== $settings['hide_message_input'] ) : ?>
                            <div class="ev-rsvp-field ev-rsvp-field-comment"><label for="comment-preview"><?php echo esc_html($settings['text_comment_label']); ?></label><textarea id="comment-preview" name="comment" placeholder="<?php echo esc_attr($settings['text_comment_placeholder']); ?>" rows="4"></textarea></div>
                            <?php if ( ! empty( $settings['sticker_list'] ) && $settings['hide_sticker_button'] !== 'yes' ) : ?><div class="ev-rsvp-field"><div class="ev-rsvp-sticker-trigger-wrapper"><button type="button" id="ev-rsvp-sticker-trigger-preview" class="ev-rsvp-sticker-trigger"><i class="far fa-smile-beam"></i><span><?php echo esc_html($settings['text_btn_sticker']); ?></span></button></div></div><?php endif; ?>
                        <?php endif; ?>
                        <div class="ev-rsvp-submit-field"><div class="ev-rsvp-notice" style="display:none;"></div><button type="submit" id="submit-preview" class="ev-rsvp-submit-button"><span><?php echo esc_html($settings['text_btn_submit']); ?></span></button></div>
                  </form>
             </div>
             
             <?php if ( $settings['hide_comment_list'] !== 'yes' ) : ?>
             <div class="ev-rsvp-list-wrapper">
                  <div class="ev-rsvp-list-container">
                      <div class="ev-rsvp-comment-item-wrapper">
                         <div class="ev-rsvp-comment-item">
                             <div class="ev-rsvp-comment-avatar ev-rsvp-gravatar-avatar"><img src="https://ui-avatars.com/api/?background=random&name=Contoh+Nama" alt="" width="40" height="40"></div>
                             <div class="ev-rsvp-comment-content-wrapper">
                                 <div class="ev-rsvp-comment-header">
                                     <div class="ev-rsvp-author-meta">
                                         <span class="ev-rsvp-comment-author">Contoh Nama</span>
                                         <span class="ev-rsvp-meta-tag status-present"><svg xmlns="https://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg> <?php echo esc_html($settings['text_btn_present']); ?></span>
                                     </div>
                                     <div class="ev-rsvp-header-actions">
                                        <?php if ( isset($settings['enable_public_reply']) && 'yes' === $settings['enable_public_reply'] ) : ?>
                                            <button class="ev-rsvp-reply-button">Balas</button>
                                        <?php endif; ?>
                                     </div>
                                 </div>
                                 <div class="ev-rsvp-comment-body"><p>Ini adalah contoh ucapan agar Anda bisa mengubah gaya font, warna, dan lainnya melalui panel Style di Elementor.</p></div>
                                 <div class="ev-rsvp-comment-footer">
                                     <time class="ev-rsvp-comment-time">25 Oktober 2025 <br>08.00 (15 Menit yang lalu)</time>
                                     <button class="ev-rsvp-like-button liked"><svg xmlns="https://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg><span class="ev-rsvp-like-count">12</span></button>
                                 </div>
                             </div>
                         </div>
                      </div>
                      <div class="ev-rsvp-replies-wrapper">
                         <div class="ev-rsvp-public-reply">
                             <div class="ev-rsvp-reply-avatar"><img src="https://ui-avatars.com/api/?background=d9e3f0&name=P" alt="" width="32" height="32"></div>
                             <div class="ev-rsvp-reply-bubble">
                                 <div class="ev-rsvp-reply-header"><span class="ev-rsvp-reply-author">Pengantin</span><span class="ev-rsvp-reply-badge">Pemilik Acara</span></div>
                                 <div class="ev-rsvp-reply-body"><p>Ini adalah contoh balasan.</p></div>
                                 <div class="ev-rsvp-reply-footer">
                                 <time class="ev-rsvp-reply-comment-time">25 Oktober 2025 <br>09.00 (10 Menit yang lalu)</time>
                                 </div>
                             </div>
                         </div>
                      </div>
                  </div>
             </div>
             </div>
             <?php endif; ?>
        </div>
        <?php
    } else {
        $all_comments_for_count = get_comments([
            'post_id' => $post_id,
            'parent' => 0,
            'status' => 'approve'
        ]);
        
        $num = 0;
        $blocked_phrases = ['Konfirmasi Hadir', 'Konfirmasi Tidak Hadir', 'Konfirmasi Ragu-ragu', 'Konfirmasi Kehadiran'];
        
        foreach ($all_comments_for_count as $c) {
            $raw_txt = trim(get_comment_text($c));
            $is_sys = get_comment_meta($c->comment_ID, '_is_system_message', true) === '1';
            $is_blocked = in_array($raw_txt, $blocked_phrases);
            $is_sticker_txt = ($raw_txt === '(Stiker)');
            
            if ($is_sys || $is_blocked || $is_sticker_txt) {
                $sticker = get_comment_meta($c->comment_ID, '_selected_sticker', true);
                $sticker_data = json_decode($sticker, true);
                if (!empty($sticker_data)) {
                    $num++; 
                }
            } else {
                $num++; 
            }
        }

        $link = '?post_id=' . $post_id;
        $per_page = !empty($settings['comments_per_page']) ? $settings['comments_per_page'] : 5;
        $is_paging = !empty($settings['enable_pagination']) ? $settings['enable_pagination'] : 'no';
        $text_prev = !empty($settings['text_btn_prev']) ? $settings['text_btn_prev'] : '← Sebelumnya';
        $text_next = !empty($settings['text_btn_next']) ? $settings['text_btn_next'] : 'Selanjutnya →';

        ?>
        <div class="ev-rsvp-wrapper"
             data-url-param="<?php echo esc_attr($param_name); ?>"
             data-guest-name-required="<?php echo esc_attr($settings['guest_name_required'] ?? 'no'); ?>"
             data-public-reply="<?php echo esc_attr($settings['enable_public_reply'] ?? 'no'); ?>"
             data-reply-badge-text="<?php echo esc_attr($settings['reply_badge_text']); ?>"
             data-reply-avatar-url="<?php echo esc_attr($settings['reply_avatar_image']['url'] ?? ''); ?>"
             data-hide-initial-name="<?php echo esc_attr($settings['hide_initial_name'] ?? 'no'); ?>"
             data-hide-attendance-badge="<?php echo esc_attr($settings['hide_attendance_badge'] ?? 'no'); ?>"
             data-attendance-disabled="<?php echo esc_attr($settings['hide_attendance_confirmation']);  ?>"
             data-badge-present-text="<?php echo esc_attr($settings['text_btn_present']); ?>"
             data-badge-notpresent-text="<?php echo esc_attr($settings['text_btn_notpresent']); ?>"
             data-badge-notsure-text="<?php echo esc_attr($settings['text_btn_notsure']); ?>"
             data-reply-button-text="<?php echo esc_attr( $settings['text_btn_reply'] ?? 'Balas' ); ?>" 
             data-text-prev="<?php echo esc_attr($text_prev); ?>"
             data-text-next="<?php echo esc_attr($text_next); ?>"
             >
             
             <?php if ( 'yes' === $settings['show_main_title'] && $settings['hide_comment_list'] !== 'yes' ) : ?>
             <div class="ev-rsvp-main-title-wrapper">
                 <h3 class="ev-rsvp-main-title"><span class="ev-rsvp-count"><?php echo $num; ?></span> <?php echo esc_html($settings['text_main_title']); ?></h3>
             </div>
             <?php endif; ?>
             
             <?php
             $is_locked = false;
             if ( isset($settings['enable_rsvp_lock']) && 'yes' === $settings['enable_rsvp_lock'] ) {
                 $deadline = $settings['rsvp_deadline_date'] ?? '';
                 if ( ! empty( $deadline ) ) {
                     $deadline_time = strtotime($deadline);
                     if ( $deadline_time && current_time('timestamp') > $deadline_time ) {
                         $is_locked = true;
                     }
                 }
             }
             
             if ( $is_locked ) :
                 $locked_msg = $settings['rsvp_locked_message'] ?? '';
                 if ( ! empty( $locked_msg ) ) :
             ?>
                 <div class="ev-rsvp-form-wrapper">
                     <div class="ev-rsvp-locked-message" style="text-align:center; padding: 20px; background: rgba(255,0,0,0.05); color: #d63638; border-radius: 8px; font-weight: 500;">
                         <?php echo wp_kses_post( $locked_msg ); ?>
                     </div>
                 </div>
             <?php 
                 endif; 
             else : 
             ?>
             <div class="ev-rsvp-form-wrapper">
                 <?php if ( 'yes' === $settings['show_form_title'] ) : ?>
                 <h4 class="ev-rsvp-form-title"><?php echo esc_html($settings['text_form_title']); ?></h4>
                 <?php endif; ?>

                 <form id="ev-rsvp-form" class="ev-rsvp-form" method="post" data-enable-wa-notice="<?php echo esc_attr($settings['enable_wa_notice']); ?>" data-wa-notice-name="<?php echo esc_attr($settings['wa_notice_name']); ?>" data-wa-notice-number="<?php echo esc_attr($settings['wa_notice_number']); ?>" data-wa-template="<?php echo esc_attr($settings['wa_template']); ?>" data-swal-class="swal-<?php echo esc_attr( $this->get_id() ); ?>">
                      <input type="text" name="ev_phone_trap" value="" style="display:none !important;" tabindex="-1" autocomplete="off">
                      <div class="ev-rsvp-field"><label for="author"><?php echo esc_html($settings['text_name_label']); ?></label><input type="text" id="author" name="author" placeholder="<?php echo esc_attr($placeholder_text); ?>" required <?php echo $guest_name_attributes; ?>></div>
                      
                      <?php
                      $is_group_invite_frontend = isset($_GET['group']) && $_GET['group'] === 'invite';
                      if ( $is_group_invite_frontend && isset($_GET[$param_name]) ) {
                          $group_name = sanitize_text_field(urldecode($_GET[$param_name]));
                      ?>
                          <div class="ev-rsvp-field">
                              <label for="group_name">Grup</label>
                              <input type="text" id="group_name" name="group_name" class="ev-group-readonly-field" value="<?php echo esc_attr($group_name); ?>" readonly>
                          </div>
                          <input type="hidden" name="group_reference" value="<?php echo esc_attr($group_name); ?>">
                      <?php
                      }
                      ?>
                      
                      <?php if ( 'yes' !== $settings['hide_attendance_confirmation'] ) : ?>
                      <fieldset class="ev-rsvp-field">
                          <legend><?php echo esc_html($settings['text_confirm_label']); ?></legend>
                          <div class="ev-rsvp-attendance-grid">
                              <div class="ev-rsvp-attendance-row">
                                  <div class="ev-rsvp-attendance-button attendance-present"><label><input type="radio" name="attendance" value="present"><span><?php echo esc_html($settings['text_btn_present']); ?></span></label></div>
                                  <?php if ( 'yes' !== $settings['hide_max_guest'] ) :  ?>
                                  <div class="ev-rsvp-guest-field" style="display: none;">
                                      <select id="guest" name="guest">
                                          <?php 
                                          $max_guests = max(1, intval($settings['guest_max'])); 
                                          for ( $i = 1; $i <= $max_guests; $i++ ) : ?>
                                              <option value="<?php echo $i; ?>" <?php selected( $i, 1 ); ?>><?php echo $i . ' ' . esc_html($settings['text_guest_option']); ?></option>
                                          <?php endfor; ?>
                                      </select>
                                  </div>
                                   <?php endif; ?>
                              </div>
                              <div class="ev-rsvp-attendance-row">
                                  <?php if ($settings['hide_notsure'] !== 'yes') : ?><div class="ev-rsvp-attendance-button attendance-notsure"><label><input type="radio" name="attendance" value="notsure"><span><?php echo esc_html($settings['text_btn_notsure']); ?></span></label></div><?php endif; ?>
                                  <div class="ev-rsvp-attendance-button attendance-notpresent"><label><input type="radio" name="attendance" value="notpresent"><span><?php echo esc_html($settings['text_btn_notpresent']); ?></span></label></div>
                              </div>
                          </div>

                          <?php
                          if ( 'yes' === $settings['enable_detailed_attendance'] && ! empty( $settings['event_list'] ) ) : ?>
                              <div class="ev-rsvp-detailed-attendance-wrapper" style="display: none;">
                                  <fieldset class="ev-rsvp-field">
                                      <legend><?php echo esc_html($settings['text_detailed_attendance_label']); ?></legend>
                                      <div class="ev-rsvp-checkbox-grid">
                                      <?php foreach ( $settings['event_list'] as $index => $event ) : 
                                          if ( empty( $event['event_name'] ) ) continue;
                                          $clean_event_name = trim($event['event_name']);
                                          $event_key = strtolower(preg_replace('/[^a-z0-9]/i', '', $clean_event_name));
                                          $is_allowed = true;
                                          if ( $is_personal_invite ) {
                                               if (!in_array($event_key, $guest_allowed_events_keys)) {
                                                    $is_allowed = false;
                                               }
                                          }
                                          if (!$is_allowed) {
                                               continue;
                                          }
                                          $checkbox_id = 'event-' . $this->get_id() . '-' . $index;
                                      ?>
                                          <div class="ev-rsvp-checkbox-item">
                                               <input type="checkbox" name="detailed_attendance[]" value="<?php echo esc_attr($event['event_name']); ?>" id="<?php echo esc_attr($checkbox_id); ?>">
                                               <label for="<?php echo esc_attr($checkbox_id); ?>"><?php echo esc_html($event['event_name']); ?></label>
                                          </div>
                                      <?php endforeach; ?>
                                      </div>
                                  </fieldset>
                              </div>
                          <?php endif; ?>

                      </fieldset>
                      <?php endif; ?>

                      <?php if ( 'yes' !== $settings['hide_message_input'] ) : ?>
                          <div class="ev-rsvp-field ev-rsvp-field-comment"><label for="comment"><?php echo esc_html($settings['text_comment_label']); ?></label><textarea id="comment" name="comment" placeholder="<?php echo esc_attr($settings['text_comment_placeholder']); ?>" rows="4"></textarea></div>
                          <?php if ( ! empty( $settings['sticker_list'] ) ) : ?>
                          <div class="ev-rsvp-field">
                              <?php if ( $settings['hide_sticker_button'] !== 'yes' ) : ?>
                              <div class="ev-rsvp-sticker-trigger-wrapper"><button type="button" id="ev-rsvp-sticker-trigger" class="ev-rsvp-sticker-trigger"><i class="far fa-smile-beam"></i><span><?php echo esc_html($settings['text_btn_sticker']); ?></span></button><div id="ev-rsvp-sticker-preview"></div></div>
                              <?php endif; ?>
                              <input type="hidden" name="selected_sticker" id="selected_sticker" value="">
                          </div>
                          <?php endif; ?>
                      <?php endif; ?>
                      <div class="ev-rsvp-submit-field">
                          <div class="ev-rsvp-notice" style="display:none;"></div>
                          <button type="submit" id="submit" class="ev-rsvp-submit-button"><span class="button-text"><?php echo esc_html($settings['text_btn_submit']); ?></span><div class="button-loading-content"><span class="button-loader"></span><span class="loading-text">Mohon tunggu, ucapan sedang diproses</span></div></button>
                          <input type="hidden" name="comment_post_ID" value="<?php echo $post_id; ?>" id="comment_post_ID">
                      </div>
                  </form>
             </div>
             <?php endif; ?>
             
             <?php if ( $settings['hide_comment_list'] !== 'yes' ) : ?>
             <div class="ev-rsvp-list-wrapper">
                <div class="ev-rsvp-list-container" 
                     data-link="<?php echo esc_attr($link); ?>"
                     data-pagination="<?php echo esc_attr($is_paging); ?>" 
                     data-per-page="<?php echo esc_attr($per_page); ?>">
                    <div class="ev-rsvp-loader"></div>
                </div>
            </div>
             <?php if ( ! empty( $settings['sticker_list'] ) && $settings['hide_sticker_button'] !== 'yes' ) : ?>
             <div class="ev-rsvp-sticker-modal-overlay">
                 <div class="ev-rsvp-sticker-modal">
                     <h4>Pilih Stiker</h4>
                     <div class="ev-rsvp-sticker-grid">
                         <?php foreach ( $settings['sticker_list'] as $item ) :
                             $sticker_type = 'icon'; $sticker_value = $item['sticker_icon']['value'];
                             if ( isset($item['sticker_icon']['library']) && $item['sticker_icon']['library'] === 'svg' ) { $sticker_type = 'svg'; $sticker_value = $item['sticker_icon']['value']['url']; }
                         ?>
                         <div class="ev-rsvp-modal-sticker-option" data-sticker-type="<?php echo esc_attr($sticker_type); ?>" data-sticker-value="<?php echo esc_attr($sticker_value); ?>"><?php \Elementor\Icons_Manager::render_icon( $item['sticker_icon'], [ 'aria-hidden' => 'true' ] ); ?></div>
                         <?php endforeach; ?>
                     </div>
                 </div>
             </div>
             <?php endif; ?>
             <?php endif; ?>
         </div>
        <?php
        if ( ! empty( $checkbox_styles ) ) {
            $widget_id = $this->get_id();
            ?>
            <style>
            .elementor-element.elementor-element-<?php echo $widget_id; ?> .ev-rsvp-checkbox-item label {
                <?php echo implode(' ', $checkbox_styles); ?>
            }
            </style>
            <?php
        }
        $eveent_api_base_url = eveent_get_api_base_url();  
        ?>
        <script>
             document.addEventListener('DOMContentLoaded', function() {
                 const urlParams = new URLSearchParams(window.location.search);
                 const barcodeUid = urlParams.get('id');
                 const eveentApiBaseUrl = '<?php echo esc_url($eveent_api_base_url); ?>';
                 if (barcodeUid && eveentApiBaseUrl) {
                     // Cek localStorage dengan cooldown 24 jam agar tidak spam ke server
                     const storageKey = 'ev_clicked_' + barcodeUid;
                     const lastClicked = localStorage.getItem(storageKey);
                     const now = Date.now();
                     const cooldown = 24 * 60 * 60 * 1000; // 24 jam dalam milidetik

                     if (!lastClicked || (now - parseInt(lastClicked, 10)) > cooldown) {
                         fetch(`${eveentApiBaseUrl}/api/track-click/${barcodeUid}`, {
                             method: 'POST',
                             headers: { 'Content-Type': 'application/json' }
                         })
                         .then(response => {
                             if (!response.ok && response.status !== 404) {
                                 throw new Error('Server Error');
                             }
                             return response;
                         })
                         .then(response => {
                             if (response.ok) {
                                 localStorage.setItem(storageKey, now.toString());
                             }
                         })
                         .catch(error => {});
                     }
                 }
             });
        </script>
        <?php
    }
}
}