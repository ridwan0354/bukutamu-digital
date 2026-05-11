<?php
$host = "127.0.0.1";
$is_local = (php_sapi_name() === 'cli') || (isset($_SERVER['REMOTE_ADDR']) && in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1'])) || (isset($_SERVER['SERVER_NAME']) && $_SERVER['SERVER_NAME'] == 'localhost');

if ($is_local) {
    // Localhost Settings (XAMPP Default)
    $user = "root";
    $pass = "";
    $db   = "buktam"; // Database Utama

    $host_wp = "127.0.0.1";
    $user_wp = "root";
    $pass_wp = "";
    $db_wp   = ""; // Kosong = WP DB belum ada di lokal, isi jika ada
    $tabel_komentar = "wpli_comments"; // Tabel lokal
    $tabel_meta = "wpli_commentmeta"; // Tabel meta lokal
} else {
    // Production/Live Settings (qr.galipatstory.com)
    $user = "qr_galipatst";
    $pass = "]H`~!2yPG2qo{5UW";
    $db   = "qr_galipatst"; // Database Utama BUKU TAMU

    $host_wp = "localhost";
    $user_wp = "by_groovite_";
    $pass_wp = ")]k0XoG}i)RXdVPn";
    $db_wp   = "by_groovite_";
    $tabel_komentar = "wpli_comments"; // Tabel COMEN (Sesuai DI cPanel)
    $tabel_meta = "wpli_commentmeta"; 
}

// ============================================================
// API KEY — Untuk validasi request dari sistem Laravel
// Harus sama dengan BUKUTAMU_API_KEY di .env Laravel
// ============================================================
define('LARAVEL_API_KEY', 'glpst_bukutamu_2026_X9kM3pQrZwN7vJ2s');
?>
