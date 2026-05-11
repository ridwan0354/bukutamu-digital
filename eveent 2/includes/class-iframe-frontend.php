<?php
/**
 * Frontend Iframe Handler
 * Provides a public page at domain-a.com/buku-tamu/ that embeds the 
 * Laravel guestbook login page inside an iframe.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Eveent_Iframe_Frontend {

    public function __construct() {
        add_action('init', [$this, 'register_rewrite_rules']);
        add_filter('query_vars', [$this, 'register_query_vars']);
        add_action('template_redirect', [$this, 'handle_iframe_page']);
    }

    /**
     * Register custom rewrite rule for /buku-tamu/
     */
    public function register_rewrite_rules() {
        $slug = get_option('ev_dashboard_tamu_slug', 'buku-tamu');
        $slug = sanitize_title($slug);
        add_rewrite_rule('^' . $slug . '/?$', 'index.php?ev_iframe_page=1', 'top');
    }

    /**
     * Register query variable
     */
    public function register_query_vars($vars) {
        $vars[] = 'ev_iframe_page';
        return $vars;
    }

    /**
     * Handle the iframe page request
     */
    public function handle_iframe_page() {
        if (!get_query_var('ev_iframe_page')) {
            return;
        }

        // Check if dashboard buku tamu is activated
        $is_active = get_option('ev_dashboard_tamu_active', '0') === '1';
        if (!$is_active) {
            $this->render_error_page('Halaman Tidak Tersedia', 'Halaman Buku Tamu tidak aktif pada situs ini.');
        }

        $vendor_data = get_option('ev_registered_vendor_data');
        if (!$vendor_data) {
            $this->render_error_page('Akses Ditolak', 'Buku Tamu belum diaktifkan pada situs ini.');
        }

        $brand_name = $vendor_data['brand_name'];
        $brand_slug = preg_replace('/[^a-z0-9]+/', '', strtolower($brand_name));
        $brand_url = 'https://' . $brand_slug . '.' . eveent_get_api_base_domain();

        // Process dynamic passkey link -> route through Laravel's seamless gateway
        if (isset($_GET['passkey'])) {
            $eventLink = sanitize_text_field($_GET['passkey']);
            // rawurlencode untuk mencegah manipulasi URL (Open Redirect)
            $apiDomain = eveent_get_api_base_domain();
            $iframe_url = 'https://' . $apiDomain . '/iframe-passkey/' . rawurlencode($eventLink) . '?brand_name=' . urlencode($brand_slug);
        } else {
            // Default: show generic team login page
            $iframe_url = $brand_url . '/user-login?iframe=1';
        }

        // Get site info for the page
        $site_name = get_bloginfo('name');

        // Render full-page HTML (outside WordPress theme)
        ?>
        <!DOCTYPE html>
        <html lang="id">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Buku Tamu - <?php echo esc_html($site_name); ?></title>
            <link rel="icon" href="data:;base64,iVBORw0KGgo=">
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                html, body { 
                    width: 100%; height: 100%; 
                    overflow: hidden;
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                }
                .ev-iframe-container {
                    width: 100%;
                    height: 100%;
                    position: relative;
                }
                .ev-iframe-container iframe {
                    width: 100%;
                    height: 100%;
                    border: none;
                    display: block;
                }
                .ev-iframe-loading {
                    position: absolute;
                    inset: 0;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    background: #1a1a2e;
                    color: #fff;
                    font-size: 14px;
                    transition: opacity 0.4s ease;
                }
                .ev-iframe-loading.loaded {
                    opacity: 0;
                    pointer-events: none;
                }
                .ev-spinner {
                    width: 32px; height: 32px;
                    border: 3px solid rgba(255,255,255,0.2);
                    border-top-color: #fff;
                    border-radius: 50%;
                    animation: ev-spin 0.8s linear infinite;
                    margin-right: 12px;
                }
                @keyframes ev-spin {
                    to { transform: rotate(360deg); }
                }
            </style>
        </head>
        <body>
            <div class="ev-iframe-container">
                <div class="ev-iframe-loading" id="ev-loading">
                    <div class="ev-spinner"></div>
                    <span>Memuat Buku Tamu...</span>
                </div>
                <iframe 
                    src="<?php echo esc_url($iframe_url); ?>"
                    id="ev-guestbook-iframe"
                    allow="camera; microphone; clipboard-write; clipboard-read"
                    onload="document.getElementById('ev-loading').classList.add('loaded');"
                ></iframe>
            </div>
        </body>
        </html>
        <?php
        exit;
    }

    private function render_error_page($title, $message) {
        $site_name = get_bloginfo('name');
        status_header(404);
        ?>
        <!DOCTYPE html>
        <html lang="id">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php echo esc_html($title . ' - ' . $site_name); ?></title>
            <link rel="icon" href="data:;base64,iVBORw0KGgo=">
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body {
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
                    background-color: #f8fafc;
                    color: #334155;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    min-height: 100vh;
                    padding: 20px;
                }
                .error-container {
                    background: #ffffff;
                    border-radius: 16px;
                    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -4px rgba(0, 0, 0, 0.05);
                    max-width: 420px;
                    width: 100%;
                    padding: 48px 32px;
                    text-align: center;
                    border: 1px solid #f1f5f9;
                }
                .error-icon {
                    width: 72px;
                    height: 72px;
                    background: #f1f5f9;
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    margin: 0 auto 24px;
                    color: #94a3b8;
                }
                .error-icon svg {
                    width: 36px;
                    height: 36px;
                }
                h1 {
                    font-size: 20px;
                    font-weight: 600;
                    margin-bottom: 12px;
                    color: #0f172a;
                    letter-spacing: -0.01em;
                }
                p {
                    font-size: 15px;
                    line-height: 1.6;
                    color: #64748b;
                    margin-bottom: 32px;
                }
                .btn {
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    background: #2563eb;
                    color: #ffffff;
                    text-decoration: none;
                    padding: 12px 24px;
                    border-radius: 8px;
                    font-size: 14px;
                    font-weight: 500;
                    transition: background 0.2s, transform 0.1s;
                    box-shadow: 0 2px 4px rgba(37, 99, 235, 0.2);
                }
                .btn:hover {
                    background: #1d4ed8;
                }
                .btn:active {
                    transform: scale(0.98);
                }
            </style>
        </head>
        <body>
            <div class="error-container">
                <div class="error-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h1><?php echo esc_html($title); ?></h1>
                <p><?php echo esc_html($message); ?></p>
                <a href="<?php echo esc_url(home_url()); ?>" class="btn">Kembali ke Beranda</a>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}
