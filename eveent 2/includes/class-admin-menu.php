<?php
if (!defined('ABSPATH')) {
    exit;
}

class Eveent_Admin_Menu
{
    private $plugin;

    public function __construct($plugin)
    {
        $this->plugin = $plugin;
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_styles']);
        add_filter('pre_update_option_ev_widget_license_key', [$this, 'prevent_accidental_license_deletion'], 10, 2);
        add_action('update_option_ev_widget_license_key', [$this, 'save_license_backup'], 10, 2);
        add_action('wp_ajax_ev_toggle_dashboard_tamu', [$this, 'handle_toggle_dashboard_tamu']);
    }

    /**
     * AJAX handler for toggling the Dashboard Buku Tamu active/inactive state.
     */
    public function handle_toggle_dashboard_tamu() {
        check_ajax_referer('ev_toggle_dashboard_tamu_nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $active = isset($_POST['active']) && $_POST['active'] === '1' ? '1' : '0';
        update_option('ev_dashboard_tamu_active', $active);

        if (isset($_POST['slug'])) {
            $slug = sanitize_title($_POST['slug']);
            if (!empty($slug)) {
                update_option('ev_dashboard_tamu_slug', $slug);
            }
        }
        
        // Flush rewrite rules so the /buku-tamu/ endpoint becomes active/inactive immediately
        flush_rewrite_rules();
        
        $site_url = trailingslashit(get_site_url());
        $new_slug = get_option('ev_dashboard_tamu_slug', 'buku-tamu');
        
        wp_send_json_success(['active' => $active, 'slug' => $new_slug, 'url' => $site_url . $new_slug]);
    }

    public function add_admin_menu()
    {
        
        add_menu_page(
            'Eveent Widgets',
            'Eveent Widgets',
            'manage_options',
            'eveent-vendor-register',
            [$this, 'render_register_page'],
            'dashicons-email-alt2',
            25
        );

       
        add_submenu_page(
            'eveent-vendor-register',
            'Aktivasi Buku Tamu',
            'Aktivasi Buku Tamu',
            'manage_options',
            'eveent-vendor-register',
            [$this, 'render_register_page']
        );

       
        add_submenu_page(
            'eveent-vendor-register',
            'Aktivasi Widget',
            'Aktivasi Widget',
            'manage_options',
            'eveent-widgets-settings',
            [$this, 'render_settings_page']
        );

        
        add_submenu_page(
            'eveent-vendor-register', 
            'Atur Module Widget',
            'Atur Module Widget',
            'manage_options',
            'eveent-modules',
            [$this, 'render_module_manager_page'] 
        );

       
        if ($this->plugin->is_licensed()) {
            add_submenu_page(
                'eveent-vendor-register',
                'Atur Domain Replika',
                'Atur Domain Replika',
                'manage_options',
                'eveent-replica-domain',
                [$this, 'render_replica_domain_page']
            );

            add_submenu_page(
                'eveent-vendor-register',
                'Aktivasi Dashboard Buku Tamu',
                'Aktivasi Dashboard Buku Tamu',
                'manage_options',
                'eveent-dashboard-tamu',
                [$this, 'render_dashboard_tamu_page']
            );
        }
    }

  
    public function render_module_manager_page() {
        
        if ( isset( $this->plugin->settings ) ) {
            $this->plugin->settings->render_settings_page();
        } else {
            echo '<div class="wrap"><h1>Error</h1><p>Class Settings belum dimuat.</p></div>';
        }
    }

    public function register_plugin_settings()
    {
        register_setting('eveent_widgets_settings_group', 'ev_widget_license_key', ['type' => 'string', 'sanitize_callback' => 'sanitize_text_field']);
        register_setting('eveent_widgets_settings_group', 'ev_widget_license_domain', ['type' => 'string', 'sanitize_callback' => 'sanitize_text_field']);
        register_setting('eveent_widgets_settings_group', 'ev_global_rsvp_reply', ['type' => 'string', 'sanitize_callback' => 'sanitize_text_field', 'default' => 'no']);
        register_setting('eveent_widgets_settings_group', 'ev_rsvp_password', ['type' => 'string', 'sanitize_callback' => 'sanitize_text_field', 'default' => '1234567']);
        register_setting('eveent_widgets_settings_group', 'ev_api_base_domain', ['type' => 'string', 'sanitize_callback' => 'sanitize_text_field', 'default' => 'eveent.web.id']);
        
        register_setting('eveent_widgets_settings_group', 'ev_wa_gateway', ['type' => 'string', 'sanitize_callback' => 'sanitize_text_field']);
        register_setting('eveent_widgets_settings_group', 'ev_wa_fonnte_token', ['type' => 'string', 'sanitize_callback' => 'sanitize_text_field']);
        register_setting('eveent_widgets_settings_group', 'ev_wa_starsender_apikey', ['type' => 'string', 'sanitize_callback' => 'sanitize_text_field']);
        register_setting('eveent_widgets_settings_group', 'ev_wa_onesender_apikey', ['type' => 'string', 'sanitize_callback' => 'sanitize_text_field']);
        register_setting('eveent_widgets_settings_group', 'ev_wa_onesender_apiurl', ['type' => 'string', 'sanitize_callback' => 'esc_url_raw']);
        register_setting('eveent_widgets_settings_group', 'ev_wa_onesender_disable_sslverify', ['type' => 'string', 'sanitize_callback' => 'sanitize_text_field', 'default' => '0']);

        
        register_setting('eveent_widgets_settings_group', 'ev_wa_default_enable', ['type' => 'string', 'sanitize_callback' => 'sanitize_text_field']);
        register_setting('eveent_widgets_settings_group', 'ev_wa_default_name', ['type' => 'string', 'sanitize_callback' => 'sanitize_text_field']);
        register_setting('eveent_widgets_settings_group', 'ev_wa_default_number', ['type' => 'string', 'sanitize_callback' => 'sanitize_text_field']);
        register_setting('eveent_widgets_settings_group', 'ev_wa_default_template', ['type' => 'string', 'sanitize_callback' => 'wp_kses_post']);

        
        
        register_setting('eveent_widgets_settings_group', 'ev_gift_default_confirmation_enable', ['type' => 'string', 'sanitize_callback' => 'sanitize_text_field']);
        register_setting('eveent_widgets_settings_group', 'ev_gift_wa_default_name', ['type' => 'string', 'sanitize_callback' => 'sanitize_text_field']);
        register_setting('eveent_widgets_settings_group', 'ev_gift_wa_default_number', ['type' => 'string', 'sanitize_callback' => 'sanitize_text_field']);
        register_setting('eveent_widgets_settings_group', 'ev_gift_wa_default_template', ['type' => 'string', 'sanitize_callback' => 'wp_kses_post']);

        register_setting('eveent_replica_settings_group', 'ev_widget_replica_domains', [
            'type' => 'array',
            'sanitize_callback' => [$this, 'eveent_sanitize_replica_domains'],
            'default' => [],
            'show_in_rest' => [
                'schema' => [
                    'type'  => 'array',
                    'items' => [
                        'type' => 'string',
                    ],
                ],
            ],
        ]);
    }

    public function eveent_sanitize_replica_domains($domains)
    {
        if (!is_array($domains)) {
            return [];
        }
        $sanitized_domains = array_values(array_filter(array_map(function ($domain) {
            $domain = sanitize_text_field($domain);
            // Normalize: strip protocol, www prefix, and trailing slashes
            // So user can just enter "brand.com" instead of listing all subdomains.
            // Laravel's isSubdomainOf() will auto-match any subdomain (sub.brand.com, www.brand.com etc)
            $domain = preg_replace('/^https?:\/\/(www\.)?/', '', $domain);
            $domain = rtrim($domain, '/');
            return $domain;
        }, $domains), function ($domain) {
            return !empty($domain);
        }));
        return $sanitized_domains;
    }

    public function enqueue_admin_styles()
    {
        $plugin_base_url = plugin_dir_url(dirname(__FILE__));
        wp_enqueue_style('eveent-admin-style', $plugin_base_url . 'assets/css/admin-style.css');
    }

    public function get_license_key()
    {
        $license_key = get_option('ev_widget_license_key');
        if (empty($license_key)) {
            $backup_key = get_option('ev_widget_license_key_backup');
            if (!empty($backup_key)) {
                update_option('ev_widget_license_key', $backup_key);
                return $backup_key;
            }
        }
        return $license_key;
    }

    public function clear_all_license_keys()
    {
        delete_option('ev_widget_license_key');
        delete_option('ev_widget_license_key_backup');
    }

    public function prevent_accidental_license_deletion($new_value, $old_value)
    {
        if (!empty($old_value) && empty($new_value)) {
            return $old_value;
        }
        return $new_value;
    }

    public function save_license_backup($old_value, $new_value)
    {
        if (!empty($new_value)) {
            update_option('ev_widget_license_key_backup', $new_value);
        }
    }

    public function render_settings_page()
    {
    ?>
        <div class="wrap ev-admin-wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <?php
            $saved_domain = get_option('ev_widget_license_domain');
            $current_host = parse_url(get_site_url(), PHP_URL_HOST);
            $auto_detected_domain = preg_replace('/^www\./', '', $current_host);
            $domain_value = !empty($saved_domain) ? $saved_domain : $auto_detected_domain;
            ?>
            <div class="ev-admin-container">
                <div class="ev-admin-main">
                    <form method="post" action="options.php">
                        <?php
                        settings_fields('eveent_widgets_settings_group');
                        $license_key = $this->get_license_key();
                        $expires_at = get_option('ev_widget_license_expires_at');
                        ?>
                        <div class="card">
                            <h2><span class="dashicons dashicons-admin-network"></span> Lisensi Widget Eveent</h2>
                            <?php if ($this->plugin->is_licensed()) : ?>
                                <div class="license-status active">
                                    <span class="dashicons dashicons-yes-alt"></span>
                                    <div>
                                        <strong>Lisensi Aktif</strong>
                                        <small>Semua fitur premium Eveent Widgets telah diaktifkan.</small>
                                    </div>
                                </div>
                                <table class="form-table">
                                    <tbody>
                                        <tr>
                                            <th scope="row">Kunci Lisensi</th>
                                            <td>
                                                <input type="text" class="regular-text" value="<?php echo esc_attr(substr($license_key, 0, 5) . str_repeat('*', 20) . substr($license_key, -5)); ?>" disabled />
                                                <input type="hidden" name="ev_widget_license_key" value="<?php echo esc_attr($license_key); ?>" />
                                            </td>
                                        </tr>
                                        <tr style="display: none;">
                                            <th scope="row">Domain Terdaftar</th>
                                            <td>
                                                <input type="text" class="regular-text" value="<?php echo esc_attr($domain_value); ?>" disabled />
                                                <input type="hidden" name="ev_widget_license_domain" value="<?php echo esc_attr($domain_value); ?>" />
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row">Berlaku Hingga</th>
                                            <td>
                                                <?php if ($expires_at && strtotime($expires_at)) : ?>
                                                    <p><strong><?php echo esc_html(date_i18n('j F Y', strtotime($expires_at))); ?></strong></p>
                                                <?php else : ?>
                                                    <p><strong>Lifetime</strong></p>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                                <p class="submit" style="display: flex; gap: 10px; padding-bottom: 0;">
                                  <button id="ev-deactivate-license-btn" class="button button-secondary">Nonaktifkan Lisensi Widget Eveent</button>
                                  <button type="button" id="ev-refresh-license-btn" class="button button-primary">
                                    <span class="dashicons dashicons-update"></span> Refresh Status Lisensi
                                  </button>
                                </p>
                            <?php else : ?>
                                <div class="license-status inactive">
                                    <span class="dashicons dashicons-warning"></span>
                                    <div>
                                        <strong>Lisensi Tidak Aktif</strong>
                                        <small><?php echo esc_html($this->plugin->get_license_message()); ?></small>
                                    </div>
                                </div>
                                <table class="form-table">
                                    <tbody>
                                        <tr>
                                            <th scope="row"><label for="ev_widget_license_key">Kunci Lisensi</label></th>
                                            <td>
                                                <input name="ev_widget_license_key" type="text" id="ev_widget_license_key" value="<?php echo esc_attr($license_key); ?>" class="regular-text" required>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><label for="ev_widget_license_domain"></label></th>
                                            <td>
                                                <input name="ev_widget_license_domain" type="hidden" id="ev_widget_license_domain" value="<?php echo esc_attr($domain_value); ?>" class="regular-text" readonly>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                                <p class="submit" style="display: flex; gap: 10px; padding-bottom: 0;">
                                    <button type="submit" name="submit" id="submit" class="button button-primary">Simpan dan Aktifkan Lisensi</button>
                                     <button type="button" id="ev-refresh-license-btn" class="button button-secondary">
                                      <span class="dashicons dashicons-update"></span> Refresh Status Lisensi
                                     </button>
                                   </p>
                            <?php endif; ?>
                        </div>
                        
                        

                        <div class="card">
                            <h2><span class="dashicons dashicons-admin-settings"></span> Pengaturan Umum</h2>
                            <table class="form-table">
                                <tbody>
                                    <tr>
                                        <th scope="row">Fitur Balasan RSVP</th>
                                        <td>
                                            <label for="ev_global_rsvp_reply">
                                                <input name="ev_global_rsvp_reply" type="checkbox" id="ev_global_rsvp_reply" value="yes" <?php checked('yes', get_option('ev_global_rsvp_reply', 'no')); ?>>
                                                Aktifkan fitur balasan publik untuk widget RSVP dan Comment secara global.
                                            </label>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><label for="ev_rsvp_password">Password Balasan RSVP</label></th>
                                        <td>
                                            <input name="ev_rsvp_password" type="text" id="ev_rsvp_password" value="<?php echo esc_attr(get_option('ev_rsvp_password', '1234567')); ?>" class="regular-text">
                                            <p class="description">Password default yang digunakan untuk fitur balasan publik di widget RSVP.</p>
                                            <?php if ( get_option('ev_rsvp_password', '1234567') === '1234567' ) : ?>
                                            <p class="description" style="color: #d63638;"><span class="dashicons dashicons-warning" style="font-size: 14px; vertical-align: middle;"></span> <strong>Peringatan Keamanan:</strong> Kamu masih menggunakan password default <code>1234567</code>. Segera ubah ke password yang lebih kuat!</p>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><label for="ev_api_base_domain_type">Domain Buku Tamu</label></th>
                                        <td>
                                            <?php 
                                            $current_domain = get_option('ev_api_base_domain', 'eveent.web.id');
                                            if (empty($current_domain)) $current_domain = 'eveent.web.id';
                                            $domain_type = ($current_domain === 'eveent.web.id') ? 'default' : 'custom';
                                            ?>
                                            <select id="ev_api_base_domain_type" style="margin-bottom: 10px;">
                                                <option value="default" <?php selected($domain_type, 'default'); ?>>Default (eveent.web.id)</option>
                                                <option value="custom" <?php selected($domain_type, 'custom'); ?>>Custom</option>
                                            </select>
                                            <br>
                                            <div id="ev_api_base_domain_wrap" style="<?php echo $domain_type === 'default' ? 'display: none;' : ''; ?>">
                                                <input name="ev_api_base_domain" type="text" id="ev_api_base_domain" value="<?php echo esc_attr($current_domain); ?>" class="regular-text" placeholder="domainanda.com">
                                                <p class="description">Masukkan domain Buku Tamu Anda tanpa https:// (contoh: <code>domainanda.com</code>).</p>
                                            </div>
                                            <div id="ev_api_base_domain_hidden_wrap" style="<?php echo $domain_type === 'custom' ? 'display: none;' : ''; ?>">
                                                <p class="description">Domain Default Buku Tamu : <code>eveent.web.id</code>.</p>
                                            </div>
                                            
                                            <script>
                                            document.addEventListener('DOMContentLoaded', function() {
                                                var domainTypeSelect = document.getElementById('ev_api_base_domain_type');
                                                var domainInput = document.getElementById('ev_api_base_domain');
                                                var domainWrap = document.getElementById('ev_api_base_domain_wrap');
                                                var hiddenWrap = document.getElementById('ev_api_base_domain_hidden_wrap');
                                                var previousCustomDomain = domainInput.value === 'eveent.web.id' ? '' : domainInput.value;
                                                
                                                if (domainTypeSelect && domainInput) {
                                                    domainTypeSelect.addEventListener('change', function() {
                                                        if (this.value === 'default') {
                                                            if (domainInput.value !== 'eveent.web.id' && domainInput.value !== '') {
                                                                previousCustomDomain = domainInput.value;
                                                            }
                                                            domainInput.value = 'eveent.web.id';
                                                            domainWrap.style.display = 'none';
                                                            hiddenWrap.style.display = 'block';
                                                        } else {
                                                            domainInput.value = previousCustomDomain;
                                                            domainWrap.style.display = 'block';
                                                            hiddenWrap.style.display = 'none';
                                                            domainInput.focus();
                                                        }
                                                    });
                                                }
                                            });
                                            </script>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="card">
                            <h2><span class="dashicons dashicons-whatsapp"></span> Pengaturan Notifikasi WhatsApp</h2>
                            <p>Pilih layanan WhatsApp Gateway yang Anda gunakan dan masukkan kredensial API yang diperlukan.</p>
                            <table class="form-table">
                                <tbody>
                                    <tr>
                                        <th scope="row"><label for="ev_wa_gateway">Pilih Gateway</label></th>
                                        <td>
                                            <select name="ev_wa_gateway" id="ev_wa_gateway">
                                                <option value="" <?php selected(get_option('ev_wa_gateway'), ''); ?>>-- Tidak Aktif --</option>
                                                <option value="fonnte" <?php selected(get_option('ev_wa_gateway'), 'fonnte'); ?>>Fonnte</option>
                                                <option value="starsender" <?php selected(get_option('ev_wa_gateway'), 'starsender'); ?>>Starsender</option>
                                                <option value="starsenderv3" <?php selected(get_option('ev_wa_gateway'), 'starsenderv3'); ?>>Starsender v3</option>
                                                <option value="onesender" <?php selected(get_option('ev_wa_gateway'), 'onesender'); ?>>OneSender</option>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr class="gateway-setting" id="setting-fonnte" style="display:none;">
                                        <th scope="row"><label for="ev_wa_fonnte_token">Fonnte Token</label></th>
                                        <td>
                                            <input name="ev_wa_fonnte_token" type="text" id="ev_wa_fonnte_token" value="<?php echo esc_attr(get_option('ev_wa_fonnte_token')); ?>" class="regular-text">
                                            <p class="description">Masukkan token API dari akun Fonnte Anda.</p>
                                        </td>
                                    </tr>
                                    <tr class="gateway-setting" id="setting-starsender" style="display:none;">
                                        <th scope="row"><label for="ev_wa_starsender_apikey">Starsender API Key</label></th>
                                        <td>
                                            <input name="ev_wa_starsender_apikey" type="text" id="ev_wa_starsender_apikey" value="<?php echo esc_attr(get_option('ev_wa_starsender_apikey')); ?>" class="regular-text">
                                            <p class="description">Masukkan API Key dari akun Starsender Anda.</p>
                                        </td>
                                    </tr>
                                    <tr class="gateway-setting" id="setting-starsenderv3" style="display:none;">
                                        <th scope="row"><label for="ev_wa_starsender_apikey_v3">Starsender API Key</label></th>
                                        <td>
                                            <input name="ev_wa_starsender_apikey" type="text" id="ev_wa_starsender_apikey_v3" value="<?php echo esc_attr(get_option('ev_wa_starsender_apikey')); ?>" class="regular-text">
                                            <p class="description">Masukkan API Key dari akun Starsender Anda.</p>
                                        </td>
                                    </tr>
                                    <tr class="gateway-setting" id="setting-onesender" style="display:none;">
                                        <th scope="row">OneSender</th>
                                        <td>
                                            <p style="margin-top: 0; margin-bottom: 5px;"><label for="ev_wa_onesender_apiurl">API URL</label></p>
                                            <input name="ev_wa_onesender_apiurl" type="text" id="ev_wa_onesender_apiurl" value="<?php echo esc_attr(get_option('ev_wa_onesender_apiurl')); ?>" class="regular-text" placeholder="https://wa123.api-wa.my.id">
                                            <p class="description">Masukkan URL Instalasi OneSender domain utama Anda.</p>

                                            <p style="margin-top: 15px; margin-bottom: 5px;"><label for="ev_wa_onesender_apikey">API Key</label></p>
                                            <input name="ev_wa_onesender_apikey" type="text" id="ev_wa_onesender_apikey" value="<?php echo esc_attr(get_option('ev_wa_onesender_apikey')); ?>" class="regular-text">

                                            <p style="margin-top: 15px; margin-bottom: 5px;"><label for="ev_wa_onesender_disable_sslverify">Keamanan SSL</label></p>
                                            <label for="ev_wa_onesender_disable_sslverify" style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
                                                <input name="ev_wa_onesender_disable_sslverify" type="checkbox" id="ev_wa_onesender_disable_sslverify" value="1" <?php checked(get_option('ev_wa_onesender_disable_sslverify', '0'), '1'); ?>>
                                                Abaikan verifikasi SSL (hanya aktifkan jika server OneSender menggunakan sertifikat self-signed)
                                            </label>
                                            <p class="description" style="color: #d63638;">Menonaktifkan SSL verification dapat membuat koneksi rentan terhadap serangan MITM.</p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="card">
                            <h2><span class="dashicons dashicons-admin-generic"></span> Pengaturan Notifikasi Default</h2>
                            <p>Pengaturan ini akan digunakan secara otomatis jika tidak diatur secara spesifik di dalam widget Elementor.</p>

                            <h3 style="margin-top:20px;">Notifikasi RSVP</h3>
                            <table class="form-table">
                                <tbody>
                                    <tr>
                                        <th scope="row">Aktifkan Notifikasi Default</th>
                                        <td>
                                            <label for="ev_wa_default_enable">
                                                <input name="ev_wa_default_enable" type="checkbox" id="ev_wa_default_enable" value="yes" <?php checked('yes', get_option('ev_wa_default_enable')); ?>>
                                                Ya, aktifkan notifikasi WA secara default untuk semua widget RSVP.
                                            </label>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><label for="ev_wa_default_name">Nama Client Default</label></th>
                                        <td>
                                            <input name="ev_wa_default_name" type="text" id="ev_wa_default_name" value="<?php echo esc_attr(get_option('ev_wa_default_name')); ?>" class="regular-text" placeholder="Contoh: Fulan">
                                            <p class="description" style="font-size:10px;">
                                                Isi dengan meta key:<code>_notif_name</code> jika menggunakan form JFB Notifikasi WDS
                                            </p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><label for="ev_wa_default_number">Nomor WA Client Default</label></th>
                                        <td>
                                            <input name="ev_wa_default_number" type="text" id="ev_wa_default_number" value="<?php echo esc_attr(get_option('ev_wa_default_number')); ?>" class="regular-text" placeholder="628123456789">
                                            <p class="description" style="font-size:10px;">Isi dengan meta key:<code>_notif_whatsapp</code> jika menggunakan form JFB Notifikasi WDS</p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><label for="ev_wa_default_template">Template Pesan WA Default</label></th>
                                        <td>
                                            <?php
                                            $default_template_rsvp = "*Notifikasi RSVP*\n*[post_title]*\n\nHalo Kak [client_name]\nAda ucapan baru di undangan Anda:\n\n*Nama:* [guest_name]\n*Kehadiran:* [attendance_status]\n*Jumlah Tamu:* [guest_count] orang\n\n*Ucapan:*\n[guest_message]\n\n---------------------\nPesan ini dikirim otomatis.";
                                            $template_rsvp = get_option('ev_wa_default_template', $default_template_rsvp);
                                            ?>
                                            <textarea name="ev_wa_default_template" id="ev_wa_default_template" rows="10" class="large-text"><?php echo esc_textarea($template_rsvp); ?></textarea>
                                            <p class="description" style="font-size:10px;">Gunakan variabel di bawah ini untuk data template notifikasi WA:<br>
                                                <code>[post_title]</code>, <code>[client_name]</code>, <code>[guest_name]</code>, <code>[attendance_status]</code>, <code>[guest_count]</code>, <code>[guest_message]</code>
                                            </p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>

                            <hr>
                            <h3 style="margin-top:20px;">Notifikasi Konfirmasi Hadiah (ATM Card)</h3>
                            <table class="form-table">
                                <tbody>
                                    <tr>
                                        <th scope="row">Aktifkan Konfirmasi & Notifikasi</th>
                                        <td>
                                            <label for="ev_gift_default_confirmation_enable">
                                                <input name="ev_gift_default_confirmation_enable" type="checkbox" id="ev_gift_default_confirmation_enable" value="yes" <?php checked('yes', get_option('ev_gift_default_confirmation_enable')); ?>>
                                                Ya, aktifkan "Konfirmasi Hadiah" & "Notifikasi WA" secara default untuk semua widget ATM Card.
                                            </label>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><label for="ev_gift_wa_default_name">Nama Client Default</label></th>
                                        <td>
                                            <input name="ev_gift_wa_default_name" type="text" id="ev_gift_wa_default_name" value="<?php echo esc_attr(get_option('ev_gift_wa_default_name')); ?>" class="regular-text" placeholder="Contoh: Fulan & Fulanah">
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><label for="ev_gift_wa_default_number">Nomor WA Client Default</label></th>
                                        <td>
                                            <input name="ev_gift_wa_default_number" type="text" id="ev_gift_wa_default_number" value="<?php echo esc_attr(get_option('ev_gift_wa_default_number')); ?>" class="regular-text" placeholder="628123456789">
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><label for="ev_gift_wa_default_template">Template Pesan WA Default</label></th>
                                        <td>
                                            <?php
                                            $default_template_gift = "*Notifikasi Hadiah Pernikahan*\n*[post_title]*\n\nHalo Kak [client_name],\nAnda menerima hadiah baru:\n\n*Dari:* [guest_name]\n*Jumlah:* [amount]\n*Ke Rekening:* [account_name]\n\n---------------------\nPesan ini dikirim otomatis.";
                                            $template_gift = get_option('ev_gift_wa_default_template', $default_template_gift);
                                            ?>
                                            <textarea name="ev_gift_wa_default_template" id="ev_gift_wa_default_template" rows="10" class="large-text"><?php echo esc_textarea($template_gift); ?></textarea>
                                            <p class="description" style="font-size:10px;">Gunakan variabel di bawah ini untuk data template notifikasi WA:<br>
                                                <code>[post_title]</code>, <code>[client_name]</code>, <code>[guest_name]</code>, <code>[amount]</code>, <code>[bank_name]</code>, <code>[account_name]</code>
                                            </p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <?php if ($this->plugin->is_licensed()) {
                            submit_button('Simpan Pengaturan');
                        } ?>

                        <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                const gatewaySelect = document.getElementById('ev_wa_gateway');
                                if (gatewaySelect) {
                                    const allSettings = document.querySelectorAll('.gateway-setting');

                                    function toggleGatewayFields() {
                                        const selectedGateway = gatewaySelect.value;
                                        allSettings.forEach(function(setting) {
                                            if (setting.id === 'setting-' + selectedGateway) {
                                                setting.style.display = 'table-row';
                                            } else {
                                                setting.style.display = 'none';
                                            }
                                        });
                                    }
                                    gatewaySelect.addEventListener('change', toggleGatewayFields);
                                    toggleGatewayFields();
                                }

                                const defaultEnableCheckbox = document.getElementById('ev_wa_default_enable');
                                const defaultNameInput = document.getElementById('ev_wa_default_name');
                                const defaultNumberInput = document.getElementById('ev_wa_default_number');

                                if (defaultEnableCheckbox && defaultNameInput && defaultNumberInput) {
                                    defaultEnableCheckbox.addEventListener('change', function() {
                                        if (this.checked) {
                                            if (defaultNameInput.value === '') {
                                                defaultNameInput.value = '_notif_name';
                                            }
                                            if (defaultNumberInput.value === '') {
                                                defaultNumberInput.value = '_notif_whatsapp';
                                            }
                                        }
                                    });
                                }
                                
                                const refreshBtn = document.getElementById('ev-refresh-license-btn');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', function() {
                if (confirm('Apakah Anda yakin ingin me-refresh status lisensi? Ini akan memanggil API lisensi. Setelah ini halaman akan dimuat ulang.')) {
                    refreshBtn.textContent = 'Memproses...';
                    refreshBtn.disabled = true;

                    jQuery.post(ajaxurl, {
                        action: 'ev_widget_refresh_license',
                        _ajax_nonce: '<?php echo wp_create_nonce( 'ev_license_refresh_nonce' ); ?>' 
                    }, function(response) {
                        if (response.success) {
                            alert('Status lisensi berhasil di-refresh.');
                            window.location.reload();
                        } else {
                            alert('Gagal me-refresh status lisensi: ' + (response.data.message || 'Error tidak diketahui.'));
                            refreshBtn.textContent = 'Refresh Status Lisensi';
                            refreshBtn.disabled = false;
                        }
                    }).fail(function() {
                         alert('Gagal terhubung ke server.');
                         refreshBtn.textContent = 'Refresh Status Lisensi';
                         refreshBtn.disabled = false;
                    });
                }
            });
        }
    });
</script>
                            
                    </form>
                </div>

                <div class="ev-admin-sidebar">
                    <div class="card">
                        <h3><span class="dashicons dashicons-editor-help"></span> Butuh Bantuan?</h3>
                        <p>Jika Anda mengalami kendala dengan lisensi atau penggunaan widget, silakan kunjungi dokumentasi kami atau hubungi tim support.</p>
                        <a href="https://eveent.web.id/support" class="button button-primary" target="_blank">Hubungi Support</a>
                        <a href="https://info.eveent.web.id/tutorial" class="button button-secondary" target="_blank">Dokumentasi</a>
                    </div>
                </div>
            </div>
        </div>
<?php
    }

    public function render_register_page() {
    if (isset($_POST['ev_reset_vendor']) && isset($_POST['ev_reset_vendor_nonce']) && wp_verify_nonce($_POST['ev_reset_vendor_nonce'], 'ev_reset_vendor_action')) {
        delete_option('ev_registered_vendor_data');
        echo '<script>window.location.href = window.location.href;</script>';
    }
    $vendor_data = get_option('ev_registered_vendor_data');
    ?>
    
    <div class="wrap ev-admin-wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        
        <?php if ($vendor_data) : ?>
            <?php
            $expires_at = get_option('ev_widget_license_expires_at'); 
            
            $brand_name = $vendor_data['brand_name'];
            $brand_slug = strtolower($brand_name);
            $brand_slug = preg_replace('/[^a-z0-9]+/', '', $brand_slug);
            $guestbook_url = 'https://' . $brand_slug . '.' . eveent_get_api_base_domain();
            ?>
            <div class="ev-admin-container">
                <div class="ev-admin-main">
                    <div class="notice notice-success is-dismissible">
                        <p>Selamat! Lisensi Buku Tamu Anda telah aktif.</p>
                    </div>

                    <div class="card">
                        <h2><span class="dashicons dashicons-admin-network"></span> Data Aktivasi Lisensi Buku Tamu</h2>
                        <table class="form-table">
                            <tbody>
                                
                                <tr>
                                    <th scope="row">Nama Brand</th>
                                    <td><div class="ev-data-display"><?php echo esc_html($vendor_data['brand_name']); ?></div></td>
                                </tr>
                                
                                <tr>
                                    <th scope="row">Nama Lengkap</th>
                                    <td><div class="ev-data-display"><?php echo esc_html($vendor_data['name']); ?></div></td>
                                </tr>
                                <tr>
                                    <th scope="row">Nomor WhatsApp</th>
                                    <td><div class="ev-data-display"><?php echo esc_html($vendor_data['phone']); ?></div></td>
                                </tr>
                                <tr>
                                    <th scope="row">Email</th>
                                    <td><div class="ev-data-display"><?php echo esc_html($vendor_data['email']); ?></div></td>
                                </tr>
                                
                                <tr style="display: none;">
                                    <th scope="row">Domain Brand</th>
                                    <td><div class="ev-data-display"><?php echo esc_html($vendor_data['brand_domain']); ?></div></td>
                                </tr>
                                <tr>
                                    <th scope="row">Kunci Lisensi</th>
                                    <td>
                                        <div style="display: flex !important; align-items: center !important; gap: 8px !important;">
                                            <input type="text" id="license-key-display" value="<?php echo esc_attr(substr($vendor_data['license_key'], 0, 5) . str_repeat('*', 20) . substr($vendor_data['license_key'], -5)); ?>" readonly class="regular-text" style="flex-grow: 1 !important; margin: 0 !important; height: 40px !important;">
                                            <button type="button" id="copy-license-btn" data-license="<?php echo esc_attr($vendor_data['license_key']); ?>" class="button button-secondary" style="height: 40px !important;">
                                                <span class="dashicons dashicons-admin-page" style="vertical-align: text-top; margin-right: 2px;"></span>Salin
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                
                                    <tr>
                                        <th scope="row">Berlaku Hingga</th>
                                        <td>
                                            <?php if ($expires_at && strtotime($expires_at)) : ?>
                                                <p><strong><?php echo esc_html(date_i18n('j F Y', strtotime($expires_at))); ?></strong></p>
                                            <?php else : ?>
                                                <p><strong>-</strong></p>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                               
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="card">
                            <h2><span class="dashicons dashicons-admin-links"></span> Link Akses Buku Tamu</h2>
                            <p>Sebelum Anda mengakses Buku Tamu, pastikan Widget Eveent Anda telah aktif. 
                                <strong><a href="<?php echo esc_url(admin_url('admin.php?page=eveent-widgets-settings')); ?>">Klik Disini</a></strong> untuk aktivasi.
                            </p>
                            <div style="display: flex !important; align-items: center !important; gap: 8px !important; margin-bottom: 15px;">
                                <input type="text" id="guestbook-url-input" value="<?php echo esc_attr($guestbook_url); ?>" readonly class="regular-text" style="flex-grow: 1 !important; margin: 0 !important; height: 40px !important;">
                                <button type="button" id="copy-url-btn" class="button button-secondary" style="height: 40px !important;">
                                    <span class="dashicons dashicons-admin-page" style="vertical-align: text-top; margin-right: 2px;"></span>Salin
                                </button>
                            </div>
                            <a href="<?php echo esc_url($guestbook_url); ?>" class="button button-primary" target="_blank" style="font-size: 15px; padding: 8px 16px; height: auto;">
                                <span class="dashicons dashicons-external" style="margin-top: 4px;"></span> Akses Buku Tamu
                            </a>
                        </div>
                    
                    <div class="card">
                        <h2><span class="dashicons dashicons-trash"></span> Reset Data Form</h2>
                        <p>Fitur ini akan menghapus data form aktivasi dari website Anda, namun tidak akan menonaktifkan lisensi domain yang sudah terikat. Jika ingin melakukan reset silakan hubungi tim support terlebih dahulu.</p>
                        <form method="post" action="">
                            <?php wp_nonce_field('ev_reset_vendor_action', 'ev_reset_vendor_nonce'); ?>
                            <button type="submit" name="ev_reset_vendor" class="button button-secondary" onclick="return confirm('Apakah Anda yakin ingin mereset data form aktivasi?');">
                                <span class="dashicons dashicons-trash" style="vertical-align: text-bottom;"></span> Reset Data
                            </button>
                        </form>
                    </div>
                    
                    <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        
                        const copyUrlBtn = document.getElementById('copy-url-btn');
                        const urlInput = document.getElementById('guestbook-url-input');
                        if (copyUrlBtn && urlInput) {
                            copyUrlBtn.addEventListener('click', function() {
                                urlInput.select();
                                urlInput.setSelectionRange(0, 99999);
                                navigator.clipboard.writeText(urlInput.value).then(function() {
                                    const originalText = copyUrlBtn.innerHTML;
                                    copyUrlBtn.innerHTML = 'Tersalin!';
                                    setTimeout(function() { copyUrlBtn.innerHTML = originalText; }, 2000);
                                }).catch(function(err) {
                                    
                                    try {
                                        document.execCommand('copy');
                                        const originalText = copyUrlBtn.innerHTML;
                                        copyUrlBtn.innerHTML = 'Tersalin!';
                                        setTimeout(function() { copyUrlBtn.innerHTML = originalText; }, 2000);
                                    } catch (e) {
                                        alert('Gagal menyalin URL. Mohon salin secara manual.');
                                    }
                                });
                            });
                        }

                        
                        const copyLicenseBtn = document.getElementById('copy-license-btn');
                        if (copyLicenseBtn) {
                            copyLicenseBtn.addEventListener('click', function() {
                                const licenseKey = this.dataset.license;
                                if (!licenseKey) return;

                                navigator.clipboard.writeText(licenseKey).then(function() {
                                    const originalText = copyLicenseBtn.innerHTML;
                                    copyLicenseBtn.innerHTML = 'Tersalin!';
                                    setTimeout(function() { copyLicenseBtn.innerHTML = originalText; }, 2000);
                                }).catch(function(err) {
                                    alert('Gagal menyalin lisensi.');
                                });
                            });
                        }
                    });
                    </script>
                </div>
                
                <div class="ev-admin-sidebar">
                    <div class="card">
                        <h3><span class="dashicons dashicons-editor-help"></span> Dokumentasi</h3>
                        <p>Butuh bantuan? Yuk, cek dulu dokumentasi lengkap kami. Jika solusi yang Anda cari belum ditemukan, tim support kami siap membantu.</p>
                        <a href="https://info.eveent.web.id/tutorial" class="button button-primary" target="_blank">Cek Panduan</a>
                    </div>
                </div>
            </div>
            
        <?php else : ?>
            <?php
            if (isset($_POST['ev_register_vendor_nonce']) && wp_verify_nonce($_POST['ev_register_vendor_nonce'], 'ev_register_vendor_action')) {
                $this->handle_vendor_registration();
            }
            ?>
            <div class="ev-admin-container">
                <div class="ev-admin-main">
                    <div class="card">
                        <h2><span class="dashicons dashicons-id-alt"></span> Form Aktivasi</h2>
                        <p>Silakan isi form di bawah ini untuk mengaktifkan fitur Buku Tamu Digital Anda.</p>
                        <form method="post" action="">
                            <?php wp_nonce_field('ev_register_vendor_action', 'ev_register_vendor_nonce'); ?>
                            <table class="form-table">
                                <tbody>
                                    
                                    <tr>
                                        <th scope="row"><label for="ev_vendor_brand_name">Nama Brand</label></th>
                                        <td>
                                            <div class="ev-input-group">
                                                <input name="ev_vendor_brand_name" type="text" id="ev_vendor_brand_name" required />
                                                <span class="ev-input-suffix">.<?php echo esc_html(eveent_get_api_base_domain()); ?></span>
                                            </div>
                                            <p class="description">Nama brand akan menjadi subdomain untuk buku tamu Anda. Hanya gunakan huruf dan angka tanpa spasi.</p>
                                        </td>
                                    </tr>
                                    
                                    <tr>
                                        <th scope="row"><label for="ev_vendor_name">Nama Lengkap</label></th>
                                        <td><input name="ev_vendor_name" type="text" id="ev_vendor_name" class="regular-text" required /></td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><label for="ev_vendor_email">Email</label></th>
                                        <td><input name="ev_vendor_email" type="email" id="ev_vendor_email" class="regular-text" required /></td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><label for="ev_vendor_phone">Nomor WhatsApp Aktif</label></th>
                                        <td><input name="ev_vendor_phone" type="text" id="ev_vendor_phone" class="regular-text" /></td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><label for="ev_vendor_password">Buat Password</label></th>
                                        <td><input name="ev_vendor_password" type="password" id="ev_vendor_password" class="regular-text" required /></td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><label for="ev_vendor_password_confirmation">Konfirmasi Password</label></th>
                                        <td><input name="ev_vendor_password_confirmation" type="password" id="ev_vendor_password_confirmation" class="regular-text" required /></td>
                                    </tr>
                                    
                                    <tr>
                                            <td colspan="2" style="padding:0;">
                                                <input name="ev_vendor_brand_domain" type="hidden" id="ev_vendor_brand_domain" required readonly />
                                            </td>
                                    </tr>
                                    
                                    <th scope="row"><label for="ev_vendor_license_key">Kunci Lisensi</label></th>
                                    <td><input name="ev_vendor_license_key" type="text" id="ev_vendor_license_key" class="regular-text" required /></td>
                                    </tr>
                                </tbody>
                            </table>
                            <?php submit_button('Aktivasi Buku Tamu'); ?>
                        </form>
                        <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            var domainInput = document.getElementById('ev_vendor_brand_domain');
                            if (domainInput) {
                                var currentUrl = window.location.hostname;
                                var domain = currentUrl.replace(/^www\./, '');
                                domainInput.value = domain;
                            }
                        });
                        </script>
                    </div>
                </div>
                <div class="ev-admin-sidebar">
                    <div class="card">
                        <h3><span class="dashicons dashicons-admin-network"></span> Aktivasi Lisensi</h3>
                        <p>Setelah mendaftar, akun buku tamu akan dibuat di platform pusat. Lisensi yang Anda miliki akan terhubung secara permanen dengan situs ini.</p>
                        <p>Pastikan semua data yang Anda masukkan sudah benar.</p>
                    </div>
                    <div class="card">
                        <h3><span class="dashicons dashicons-editor-help"></span> Butuh Bantuan?</h3>
                        <p>Mengalami kendala? Kami sarankan untuk mengecek <strong>dokumentasi</strong> kami terlebih dahulu untuk panduan. Namun, jika Anda butuh bantuan langsung, tim kami siap sedia. Silakan klik tombol di bawah.</p>
                        <a href="https://info.eveent.web.id/tutorial" class="button button-primary" target="_blank">Hubungi Support</a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <?php
}

    public function render_replica_domain_page() {
    ?>
    <div class="wrap ev-admin-wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <div class="ev-admin-container">
            <div class="ev-admin-main">
                <div class="card">
                    <h2><span class="dashicons dashicons-admin-site-alt3"></span> Pengaturan Domain Replika</h2>
                    <p>Jika Anda menggunakan domain replika (contoh: WeddingSaas Replika / WDR), masukkan <b>domain utama saja</b> — tanpa perlu mencantumkan setiap subdomain. Sistem akan otomatis mengenali semua subdomain dari domain yang Anda daftarkan.</p>
                    <p><em>Contoh: cukup masukkan <code>brand.com</code>, sistem akan otomatis mengenali <code>sub.brand.com</code>, <code>www.brand.com</code>, dsb.</em></p>
                    <form method="post" action="options.php">
                        <?php
                        settings_fields('eveent_replica_settings_group');
                        do_settings_sections('eveent-replica-domain');
                        
                        $replica_domains = get_option('ev_widget_replica_domains', []);
                        
                        
                        if (!is_array($replica_domains)) {
                            $old_domain = get_option('ev_widget_replica_domain', '');
                            $replica_domains = !empty($old_domain) ? [$old_domain] : [];
                            delete_option('ev_widget_replica_domain');
                        }
                        
                       
                        if (empty($replica_domains)) {
                            $replica_domains = [''];
                        }
                        ?>
                        <div id="replica-domains-container">
                            <?php foreach ($replica_domains as $domain) : ?>
                                <p class="ev-domain-input-group">
                                    <input type="text" name="ev_widget_replica_domains[]" value="<?php echo esc_attr($domain); ?>" class="regular-text ev-replica-domain-field" placeholder="contoh: brand.com" />
                                    <button type="button" class="button button-secondary ev-remove-domain-btn" style="color: #a00;">&times;</button>
                                </p>
                            <?php endforeach; ?>
                        </div>
                        <p>
                            <button type="button" id="ev-add-domain-btn" class="button button-secondary" style="
                            font-size: 14px;
                            height: 30px;
                            line-height: 28px;
                            padding: 0 10px 1px;
                            transition: all 0.2s ease-in-out;
                            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
                        ">
                            <span class="dashicons dashicons-plus-alt"></span> Tambah Domain
                        </button>
                        </p>
                        <?php submit_button('Simpan Pengaturan'); ?>
                    </form>
                </div>
            </div>
            <div class="ev-admin-sidebar">
                <div class="card">
                    <h3><span class="dashicons dashicons-editor-help"></span> Bantuan</h3>
                    <p>Masukkan <strong>domain utama saja</strong> (tanpa <code>https://</code>, <code>www.</code>, atau garis miring di akhir). Plugin otomatis menormalkan format yang Anda masukkan.</p>
                    <p>Contoh yang benar: <code>brand.com</code><br>Contoh yang <em>tidak perlu</em>: <code>sub1.brand.com</code>, <code>sub2.brand.com</code> — cukup <code>brand.com</code> sudah mencakup semuanya.</p>
                    <p>Jika lisensi terlepas, coba klik <strong>Refresh Lisensi</strong> di halaman Lisensi Widget.</p>
                </div>
            </div>
        </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const container = document.getElementById('replica-domains-container');
        const addButton = document.getElementById('ev-add-domain-btn');
        
       
        function addDomainInput() {
            const wrapper = document.createElement('p');
            wrapper.className = 'ev-domain-input-group';
            wrapper.innerHTML = `
                <input type="text" name="ev_widget_replica_domains[]" class="regular-text ev-replica-domain-field" placeholder="contoh: brand.com" />
                <button type="button" class="button button-secondary ev-remove-domain-btn" style="color: #a00;">&times;</button>
            `;
            container.appendChild(wrapper);
            
            wrapper.querySelector('input').focus();
        }

        addButton.addEventListener('click', function() {
            addDomainInput();
        });

        
        container.addEventListener('click', function(e) {
            if (e.target.classList.contains('ev-remove-domain-btn')) {
                const group = e.target.closest('.ev-domain-input-group');
               
                if (container.children.length > 1) {
                    group.remove();
                } else {
                    
                    group.querySelector('input').value = '';
                }
            }
        });
        
       
        
    });
    </script>
    <?php
}

    public function render_dashboard_tamu_page() {
        $vendor_data = get_option('ev_registered_vendor_data');
        $license_key = get_option('ev_widget_license_key');
        $is_active = get_option('ev_dashboard_tamu_active', '0') === '1';

        if (!$vendor_data || !$license_key) {
            ?>
            <div class="wrap ev-admin-wrap">
                <h1>Aktivasi Dashboard Buku Tamu</h1>
                <div class="notice notice-warning">
                    <p>Anda harus mengaktifkan Buku Tamu terlebih dahulu. <a href="<?php echo esc_url(admin_url('admin.php?page=eveent-vendor-register')); ?>">Aktivasi Sekarang</a></p>
                </div>
            </div>
            <?php
            return;
        }

        // Build brand subdomain URL
        $brand_name = $vendor_data['brand_name'];
        $brand_slug = preg_replace('/[^a-z0-9]+/', '', strtolower($brand_name));
        $brand_url = 'https://' . $brand_slug . '.' . eveent_get_api_base_domain();
        $login_url = $brand_url . '/user-login';
        $site_url = trailingslashit(get_site_url());
        $slug = get_option('ev_dashboard_tamu_slug', 'buku-tamu');
        $iframe_page_url = $site_url . $slug;

        ?>
        <div class="wrap ev-admin-wrap">
            <h1>Aktivasi Dashboard Buku Tamu</h1>
            <div class="ev-admin-container">
                <div class="ev-admin-main">
                    <!-- Toggle Card -->
                    <div class="card">
                        <h2><span class="dashicons dashicons-admin-settings"></span> Status Dashboard Buku Tamu</h2>
                        <p style="color: #666; margin-bottom: 20px;">Aktifkan fitur ini agar halaman Buku Tamu tersedia secara publik di website Anda. Pengunjung dapat mengakses halaman login tamu melalui iframe yang disematkan otomatis.</p>
                        
                        <div class="ev-toggle-row">
                            <label class="ev-toggle-switch" for="ev-dashboard-toggle">
                                <input type="checkbox" id="ev-dashboard-toggle" <?php checked($is_active); ?> />
                                <span class="ev-toggle-slider"></span>
                            </label>
                            <div class="ev-toggle-label">
                                <strong id="ev-toggle-status-text"><?php echo $is_active ? 'Aktif' : 'Nonaktif'; ?></strong>
                                <small id="ev-toggle-status-desc"><?php echo $is_active ? 'Halaman Buku Tamu dapat diakses oleh pengunjung.' : 'Halaman Buku Tamu tidak dapat diakses oleh pengunjung.'; ?></small>
                            </div>
                        </div>
                        <div id="ev-toggle-saving" style="display: none; margin-top: 10px; color: #666;"><span class="spinner is-active" style="float: none; margin: 0 5px 0 0;"></span>Menyimpan...</div>
                    </div>

                    <!-- URL Info Card (shown when active) -->
                    <div class="card" id="ev-url-card" style="<?php echo $is_active ? '' : 'display:none;'; ?>">
                        <h2><span class="dashicons dashicons-admin-links"></span> Tautan Akses Buku Tamu</h2>
                        <p style="color: #666; margin-bottom: 15px;">Bagikan tautan ini kepada partisipan agar mereka dapat langsung mengakses sistem buku tamu utama dari domain Anda.</p>

                        <div class="ev-url-section">
                            <label style="font-weight: 600; margin-bottom: 5px; display: block;">Slug Buku Tamu</label>
                            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 15px;">
                                <span style="font-family: monospace; background: #eee; padding: 6px 10px; border-radius: 4px; border: 1px solid #ddd;"><?php echo esc_url($site_url); ?></span>
                                <input type="text" id="ev-dashboard-slug" value="<?php echo esc_attr($slug); ?>" class="regular-text" style="width: 150px; margin: 0; height: 32px; font-size: 13px;" />
                                <button type="button" id="ev-save-slug-btn" class="button button-secondary" style="height: 32px;">Simpan Slug</button>
                                <span id="ev-slug-saving" style="display: none; color: green; font-size: 13px; margin-left: 10px;">Tersimpan!</span>
                            </div>

                            <label style="font-weight: 600; margin-bottom: 5px; display: block;"><span class="dashicons dashicons-welcome-view-site" style="font-size: 16px; vertical-align: text-bottom;"></span> Tautan Lengkap Buku Tamu</label>
                            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px;">
                                <input type="text" id="ev-iframe-page-url" value="<?php echo esc_attr($iframe_page_url); ?>" readonly class="regular-text" style="flex-grow: 1; margin: 0; height: 40px; font-size: 13px; background: #f6f7f7;" />
                                <button type="button" class="button button-secondary ev-copy-btn" data-target="ev-iframe-page-url" style="height: 40px;">
                                    <span class="dashicons dashicons-admin-page" style="vertical-align: text-top; margin-right: 2px;"></span>Salin Tautan
                                </button>
                            </div>
                        </div>

                        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                            <a href="<?php echo esc_url($iframe_page_url); ?>" id="ev-visit-btn" class="button button-primary" target="_blank" style="font-size: 14px; padding: 6px 16px; height: auto; display: inline-flex; align-items: center; gap: 6px;">
                                <span class="dashicons dashicons-external" style="margin-top: 2px;"></span> Kunjungi Halaman
                            </a>
                        </div>
                    </div>

                </div>

                <div class="ev-admin-sidebar">
                    <div class="card">
                        <h3><span class="dashicons dashicons-info"></span> Informasi</h3>
                        <p style="color: #666; font-size: 13px; line-height: 1.6;">Saat diaktifkan, halaman <code>/buku-tamu/</code> akan tersedia di website Anda. Halaman ini menampilkan form login dashboard buku tamu dalam iframe.</p>
                        <p style="color: #666; font-size: 13px; line-height: 1.6;">Pengunjung atau tamu undangan dapat login melalui halaman tersebut untuk mengisi buku tamu digital.</p>
                    </div>
                    <div class="card">
                        <h3><span class="dashicons dashicons-editor-help"></span> Butuh Bantuan?</h3>
                        <p style="color: #666; font-size: 13px;">Jika Anda mengalami kendala, silakan hubungi tim support kami.</p>
                        <a href="https://eveent.web.id/support" class="button button-primary" target="_blank">Hubungi Support</a>
                    </div>
                </div>
            </div>
        </div>

        <style>
            .ev-toggle-row {
                display: flex;
                align-items: center;
                gap: 16px;
                padding: 16px 20px;
                background: #f8f9fa;
                border: 1px solid #e2e4e7;
                border-radius: 8px;
            }
            .ev-toggle-switch {
                position: relative;
                display: inline-block;
                width: 52px;
                height: 28px;
                flex-shrink: 0;
            }
            .ev-toggle-switch input {
                opacity: 0;
                width: 0;
                height: 0;
            }
            .ev-toggle-slider {
                position: absolute;
                cursor: pointer;
                inset: 0;
                background-color: #ccc;
                transition: 0.3s;
                border-radius: 28px;
            }
            .ev-toggle-slider:before {
                content: "";
                position: absolute;
                height: 22px;
                width: 22px;
                left: 3px;
                bottom: 3px;
                background-color: white;
                transition: 0.3s;
                border-radius: 50%;
                box-shadow: 0 1px 3px rgba(0,0,0,0.2);
            }
            .ev-toggle-switch input:checked + .ev-toggle-slider {
                background-color: #27ae60;
            }
            .ev-toggle-switch input:checked + .ev-toggle-slider:before {
                transform: translateX(24px);
            }
            .ev-toggle-label strong {
                display: block;
                font-size: 15px;
                color: #1d2327;
            }
            .ev-toggle-label small {
                display: block;
                font-size: 13px;
                color: #666;
                margin-top: 2px;
            }
            .ev-url-section {
                margin-bottom: 10px;
            }
            .ev-url-section label .dashicons {
                color: #2271b1;
            }
        </style>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var toggle = document.getElementById('ev-dashboard-toggle');
            var urlCard = document.getElementById('ev-url-card');
            var statusText = document.getElementById('ev-toggle-status-text');
            var statusDesc = document.getElementById('ev-toggle-status-desc');
            var savingEl = document.getElementById('ev-toggle-saving');

            if (toggle) {
                toggle.addEventListener('change', function() {
                    var isActive = this.checked;
                    savingEl.style.display = 'block';

                    // Update UI immediately
                    statusText.textContent = isActive ? 'Aktif' : 'Nonaktif';
                    statusDesc.textContent = isActive 
                        ? 'Halaman Buku Tamu dapat diakses oleh pengunjung.' 
                        : 'Halaman Buku Tamu tidak dapat diakses oleh pengunjung.';
                    urlCard.style.display = isActive ? '' : 'none';

                    // Save via AJAX
                    jQuery.post(ajaxurl, {
                        action: 'ev_toggle_dashboard_tamu',
                        active: isActive ? '1' : '0',
                        _ajax_nonce: '<?php echo wp_create_nonce('ev_toggle_dashboard_tamu_nonce'); ?>'
                    }, function(response) {
                        savingEl.style.display = 'none';
                        if (!response.success) {
                            alert('Gagal menyimpan pengaturan: ' + (response.data || 'Error'));
                            toggle.checked = !isActive;
                            toggle.dispatchEvent(new Event('change'));
                        }
                    }).fail(function() {
                        savingEl.style.display = 'none';
                        alert('Gagal terhubung ke server.');
                        toggle.checked = !isActive;
                    });
                });
            }

            var slugInput = document.getElementById('ev-dashboard-slug');
            var saveSlugBtn = document.getElementById('ev-save-slug-btn');
            var slugSavingText = document.getElementById('ev-slug-saving');
            var visitBtn = document.getElementById('ev-visit-btn');

            if (saveSlugBtn && slugInput) {
                saveSlugBtn.addEventListener('click', function() {
                    var slugVal = slugInput.value.trim();
                    if (!slugVal) {
                        alert('Slug tidak boleh kosong.');
                        return;
                    }
                    
                    saveSlugBtn.disabled = true;
                    slugSavingText.style.display = 'none';
                    
                    jQuery.post(ajaxurl, {
                        action: 'ev_toggle_dashboard_tamu',
                        active: toggle.checked ? '1' : '0',
                        slug: slugVal,
                        _ajax_nonce: '<?php echo wp_create_nonce('ev_toggle_dashboard_tamu_nonce'); ?>'
                    }, function(response) {
                        saveSlugBtn.disabled = false;
                        if (response.success) {
                            slugSavingText.style.display = 'inline';
                            setTimeout(() => { slugSavingText.style.display = 'none'; }, 3000);
                            
                            slugInput.value = response.data.slug;
                            var urlInput = document.getElementById('ev-iframe-page-url');
                            if (urlInput) urlInput.value = response.data.url;
                            if (visitBtn) visitBtn.href = response.data.url;
                        } else {
                            alert('Gagal menyimpan slug: ' + (response.data || 'Error'));
                        }
                    }).fail(function() {
                        saveSlugBtn.disabled = false;
                        alert('Gagal terhubung ke server.');
                    });
                });
            }

            // Copy button handlers
            document.querySelectorAll('.ev-copy-btn').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var targetId = this.getAttribute('data-target');
                    var input = document.getElementById(targetId);
                    if (!input) return;

                    input.select();
                    input.setSelectionRange(0, 99999);
                    var self = this;
                    navigator.clipboard.writeText(input.value).then(function() {
                        var orig = self.innerHTML;
                        self.innerHTML = '<span class="dashicons dashicons-yes" style="vertical-align: text-top; margin-right: 2px;"></span>Tersalin!';
                        setTimeout(function() { self.innerHTML = orig; }, 2000);
                    }).catch(function() {
                        try {
                            document.execCommand('copy');
                            var orig = self.innerHTML;
                            self.innerHTML = '<span class="dashicons dashicons-yes" style="vertical-align: text-top; margin-right: 2px;"></span>Tersalin!';
                            setTimeout(function() { self.innerHTML = orig; }, 2000);
                        } catch(e) {
                            alert('Gagal menyalin. Mohon salin secara manual.');
                        }
                    });
                });
            });
        });
        </script>
        <?php
    }

    
    public function handle_vendor_registration() {
    $name = sanitize_text_field($_POST['ev_vendor_name']);
    $phone = sanitize_text_field($_POST['ev_vendor_phone']);
    $email = sanitize_email($_POST['ev_vendor_email']);
    $password = sanitize_text_field($_POST['ev_vendor_password']);
    $password_confirmation = sanitize_text_field($_POST['ev_vendor_password_confirmation']);
    $brand_name = sanitize_text_field($_POST['ev_vendor_brand_name']);
    $brand_domain = sanitize_text_field($_POST['ev_vendor_brand_domain']);
    $license_key = sanitize_text_field($_POST['ev_vendor_license_key']);

    if ($password !== $password_confirmation) {
        echo '<div class="notice notice-error"><p>Konfirmasi password tidak cocok.</p></div>';
        return;
    }

    if (empty($name) || empty($email) || empty($password) || empty($brand_name) || empty($brand_domain) || empty($license_key)) {
        echo '<div class="notice notice-error"><p>Semua kolom yang wajib diisi harus dilengkapi.</p></div>';
        return;
    }

    
    $api_url = 'https://eveent.web.id/api/register-vendor';
    $response = wp_remote_post($api_url, [
        'body' => [
            'name'                  => $name,
            'email'                 => $email,
            'password'              => $password,
            'password_confirmation' => $password_confirmation,
            'brand_name'            => $brand_name,
            'brand_domain'          => $brand_domain,
            'phone'                 => $phone,
            'license_key'           => $license_key, 
        ],
        'headers' => [
            
            'Accept'  => 'application/json'
        ],
        'timeout' => 60,
    ]);

    if (is_wp_error($response)) {
        $error_message = $response->get_error_message();
        echo '<div class="notice notice-error"><p>Error saat menghubungkan ke API: ' . esc_html($error_message) . '</p></div>';
        return;
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    $response_code = wp_remote_retrieve_response_code($response);

    if ($response_code === 200 || $response_code === 201) {
        $vendor_data = [
            'name'         => $name,
            'email'        => $email,
            'brand_name'   => $brand_name,
            'brand_domain' => $brand_domain,
            'license_key'  => $license_key,
            'phone'        => $phone,
        ];
        update_option('ev_registered_vendor_data', $vendor_data);
        update_option('ev_widget_license_key', $license_key);
        update_option('ev_widget_license_domain', preg_replace('/^https?:\/\/(www\.)?/', '', rtrim($brand_domain, '/')));

        echo '<div class="notice notice-success"><p>Aktivasi buku tamu berhasil! Halaman akan dimuat ulang.</p></div>';
        echo '<script>window.location.reload();</script>';
    } else {
        if (isset($data['errors'])) {
            echo '<div class="notice notice-error"><p>Gagal aktivasi buku tamu:</p><ul>';
            foreach ($data['errors'] as $field => $messages) {
                echo '<li><strong>' . esc_html($field) . ':</strong> ' . esc_html(implode(' ', $messages)) . '</li>';
            }
            echo '</ul></div>';
        } else {
            $error_message = isset($data['message']) ? $data['message'] : 'Gagal Aktivasi Buku tamu. Silakan coba lagi.';
            echo '<div class="notice notice-error"><p>' . esc_html($error_message) . '</p></div>';
        }
    }
}

    public function admin_notice_invalid_license() {
        $screen = get_current_screen();
        if ( $screen && $screen->base === 'toplevel_page_eveent-widgets-settings' ) {
            return;
        }
        $settings_url = admin_url('admin.php?page=eveent-widgets-settings');
        $message = sprintf(
            esc_html__( 'Lisensi untuk %1$s tidak aktif. Silakan %2$s untuk mengaktifkan semua fitur.', 'eveent-widgets' ),
            '<strong>Eveent Widgets</strong>',
            '<a href="' . esc_url($settings_url) . '">masukkan lisensi yang valid</a>'
        );
        printf('<div class="notice notice-error is-dismissible"><p>%1$s</p></div>', $message);
    }
    
    public function admin_notice_missing_main_plugin() {
        if ( isset( $_GET['activate'] ) ) unset( $_GET['activate'] );
        $message = sprintf(esc_html__( '"%1$s" membutuhkan "%2$s" untuk diinstal dan diaktifkan.', 'eveent-widgets' ), '<strong>' . esc_html__( 'Eveent Widgets', 'eveent-widgets' ) . '</strong>', '<strong>' . esc_html__( 'Elementor', 'eveent-widgets' ) . '</strong>');
        printf( '<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message );
    }

    public function admin_add_meta_box() {
        add_meta_box('rsvp_attendance_box', 'Status Kehadiran (RSVP)', [ $this, 'admin_render_meta_box' ], 'comment', 'normal', 'high');
    }

    public function admin_render_meta_box( $comment ) {
        $attendance = get_comment_meta( $comment->comment_ID, 'attendance', true );
        wp_nonce_field( 'rsvp_meta_update', 'rsvp_meta_nonce' );
        ?>
        <p>
            <label for="attendance">Ubah status:</label>
            <select name="attendance" id="attendance">
                <option value="present" <?php selected($attendance, 'present'); ?>>Hadir</option>
                <option value="notpresent" <?php selected($attendance, 'notpresent'); ?>>Tidak Hadir</option>
                <option value="notsure" <?php selected($attendance, 'notsure'); ?>>Ragu-ragu</option>
            </select>
        </p>
        <?php
    }

    public function admin_save_meta_fields( $comment_id ) {
            if ( !isset($_POST['rsvp_meta_nonce']) || !wp_verify_nonce($_POST['rsvp_meta_nonce'], 'rsvp_meta_update') ) return;
            if ( isset($_POST['attendance']) ) {
                update_comment_meta( $comment_id, 'attendance', sanitize_key($_POST['attendance']) );
            }
        }
    
        public function admin_add_meta_in_author_comment( $author, $comment_id ) {
        if (!is_admin()) return $author;
    
        $pattern_to_remove = '/ - <span[^>]*>(?:Hadir|Tidak Hadir|Ragu-ragu|Masih Ragu)<\/span>(?: \(\d+ Orang\))?/i';
        $author = preg_replace( $pattern_to_remove, '', $author );
        
        $attendance = get_comment_meta( $comment_id, 'attendance', true );
        $guest = get_comment_meta( $comment_id, 'guest', true );
        $person = ($attendance == 'present' && $guest) ? ' (' . intval($guest) . ' Orang)' : '';
        
        
        $status_map = [
            'present' => '<span style="color:#27ae60; font-weight:bold;">Hadir</span>' . $person,
            'notpresent' => '<span style="color:#c0392b; font-weight:bold;">Tidak Hadir</span>',
            'notsure' => '<span style="color:#f39c12; font-weight:bold;">Ragu-ragu</span>',
        ];
        
        
        if (isset($status_map[$attendance])) {
            $author .= ' - ' . $status_map[$attendance];
        }
        
        return $author;
    }
}