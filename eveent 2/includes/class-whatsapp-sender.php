<?php


if (!defined('ABSPATH')) {
    exit;
}

class Eveent_WhatsApp_Sender {

    public static function send($phone, $message) {
        if (empty($phone) || empty($message)) return;

        $gateway = get_option('ev_wa_gateway');

        switch ($gateway) {
            case 'fonnte':
                self::fonnte($phone, $message);
                break;
            case 'starsender':
                self::starsender($phone, $message);
                break;
            case 'starsenderv3':
                self::starsenderv3($phone, $message);
                break;
            case 'onesender':
                self::onesender($phone, $message);
                break;
            case 'responic':
                self::responic($phone, $message);
                break;
            case 'dripsender':
                self::dripsender($phone, $message);
                break;
            case 'autowa':
                self::autowa($phone, $message);
                break;
        }
    }

    private static function fonnte($phone, $message) {
        $token = get_option('ev_wa_fonnte_token');
        if (empty($token)) return;

        wp_remote_post('https://api.fonnte.com/send', [
            'headers'  => ['Authorization' => $token],
            'body'     => ['target' => $phone, 'message' => $message],
            'timeout'  => 5,
            'blocking' => false,
        ]);
    }

    private static function starsender($phone, $message) {
        $apikey = get_option('ev_wa_starsender_apikey');
        if (empty($apikey)) return;

        $url = 'https://starsender.online/api/sendText?message=' . rawurlencode($message) . '&tujuan=' . rawurlencode($phone . '@s.whatsapp.net');
        wp_remote_post($url, ['headers' => ['apikey' => $apikey], 'timeout' => 5, 'blocking' => false]);
    }

    private static function starsenderv3($phone, $message) {
        $apikey = get_option('ev_wa_starsender_apikey'); 
        if (empty($apikey)) return;

        wp_remote_post('https://api.starsender.online/api/send', [
            'body'     => wp_json_encode(['messageType' => 'text', 'to' => $phone, 'body' => $message]),
            'headers'  => ['Content-Type' => 'application/json', 'Authorization' => $apikey],
            'timeout'  => 5,
            'blocking' => false,
        ]);
    }
    
    private static function onesender($phone, $message) {
        $api_key = get_option('ev_wa_onesender_apikey');
        $api_url = get_option('ev_wa_onesender_apiurl');
        if (empty($api_key) || empty($api_url)) return;

        if (strlen($phone) >= 8 && substr($phone, 0, 2) === '08') {
            $phone = '628' . substr($phone, 2);
        }

        $api_url = trailingslashit($api_url) . 'api/v1/messages';
        $data = ['recipient_type' => 'individual', 'to' => $phone, 'type' => 'text', 'text' => ['body' => $message]];

        wp_remote_post($api_url, [
            'headers'   => ['Authorization' => 'Bearer ' . $api_key, 'Content-Type'  => 'application/json'],
            'body'      => wp_json_encode($data),
            'sslverify' => ! (bool) get_option( 'ev_wa_onesender_disable_sslverify', false ),
            'timeout'   => 5,
            'blocking'  => false,
        ]);
    }

    private static function responic($phone, $message) {
        $apikey = get_option('ev_wa_responic_apikey');
        if (empty($apikey)) return;

        wp_remote_post('https://panel.responic.com/api/message', [
            'body'     => wp_json_encode(['receiver' => $phone, 'message'  => ['text' => $message]]),
            'headers'  => ['Content-Type' => 'application/json', 'Authorization' => 'Bearer ' . $apikey],
            'timeout'  => 5,
            'blocking' => false,
        ]);
    }

    private static function dripsender($phone, $message) {
        $apikey = get_option('ev_wa_dripsender_apikey');
        if (empty($apikey)) return;
        
        wp_remote_post('https://api.dripsender.id/send', [
            'headers'  => ['Content-Type' => 'application/json'],
            'body'     => wp_json_encode(['api_key' => $apikey, 'phone' => $phone, 'text' => $message]),
            'timeout'  => 5,
            'blocking' => false,
        ]);
    }

    private static function autowa($phone, $message) {
        $apikey = get_option('ev_wa_autowa_apikey');
        $client = get_option('ev_wa_autowa_clientid');
        if (empty($apikey) || empty($client)) return;

        $query_params = http_build_query([
            'client_id' => $client,
            'mobile'    => $phone,
            'text'      => $message,
            'token'     => $apikey,
        ]);
        $api_url = 'https://app.autowa.site/api/user/v2/send_message_url?' . $query_params;

        wp_remote_get($api_url, [ 'timeout' => 5, 'blocking' => false ]); 
    }

} 