<?php
require_once 'tables.php';
register_shutdown_function(function() {
    // Jangan tampilkan signature jika respon adalah JSON (untuk AJAX)
    $is_json = false;
    foreach (headers_list() as $header) {
        if (strpos(strtolower($header), 'application/json') !== false) {
            $is_json = true;
            break;
        }
    }
    
    if (!$is_json && (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest')) {
        echo "\n<!--
        ============================================================
        APPLICATION : BUKU TAMU DIGITAL Eksklusif
        VERSION     : 2.1 Standard Edition
        LICENSE     : Licensed for Exclusive Use
        DEVELOPED BY: ACHMAD BUKHORI
        CONTACT     : WhatsApp (0822 2222 6900)
        ============================================================
        Copyright © 2026. All Rights Reserved.
    -->";
    }
});

// Matikan exception fatal di PHP 8.1+ agar bisa ditangani manual
mysqli_report(MYSQLI_REPORT_OFF);

// 1. KONEKSI UTAMA
$koneksi = @mysqli_connect($host, $user, $pass, $db);

if (!$koneksi) {
    if ($is_local) {
        die("<div style='font-family:sans-serif;padding:20px;background:#fff5f5;border:1px solid #feb2b2;border-radius:8px;'>
            <h3 style='color:#c53030'>Koneksi Database Utama Gagal!</h3>
            <p>Pastikan MySQL XAMPP aktif & database <b>$db</b> sudah dibuat.</p>
            <hr><small>Error: " . mysqli_connect_error() . "</small>
        </div>");
    }
    // Sembunyikan detail error di produksi
    die("Koneksi database gagal. Silakan hubungi administrator.");
}

// 2. KONEKSI WORDPRESS (KHUSUS UNTUK UCAPAN.PHP)
$koneksi_wp = @mysqli_connect($host_wp, $user_wp, $pass_wp, $db_wp);

// Auto-detect table prefix jika $tabel_komentar tidak ada
if ($koneksi_wp) {
    $check_prefix = mysqli_query($koneksi_wp, "SHOW TABLES LIKE '$tabel_komentar'");
    if (!$check_prefix || mysqli_num_rows($check_prefix) == 0) {
        $fallback = mysqli_query($koneksi_wp, "SHOW TABLES LIKE '%_comments'");
        if ($fallback && mysqli_num_rows($fallback) > 0) {
            $row = mysqli_fetch_array($fallback);
            $tabel_komentar = $row[0];
            // Auto detect meta table based on comments table name
            $tabel_meta = str_replace('_comments', '_commentmeta', $tabel_komentar);
        }
    }
}

// 3. SYSTEM AUTO-MIGRATION (Logika dari Kode Anda)
$table_check = mysqli_query($koneksi, "SHOW TABLES LIKE 'users'");
if (mysqli_num_rows($table_check) > 0) {
    // Migrasi users
    if(mysqli_num_rows(mysqli_query($koneksi, "SHOW COLUMNS FROM users LIKE 'parent_id'")) == 0){
        mysqli_query($koneksi, "ALTER TABLE users ADD COLUMN parent_id INT DEFAULT 0 AFTER role");
        mysqli_query($koneksi, "ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'mempelai', 'receptionist') NOT NULL DEFAULT 'mempelai'");
    }
    // Migrasi tamu
    if(mysqli_num_rows(mysqli_query($koneksi, "SHOW COLUMNS FROM tamu LIKE 'event_id'")) == 0){
        mysqli_query($koneksi, "ALTER TABLE tamu ADD COLUMN event_id INT DEFAULT 0 AFTER id");
    }
    if(mysqli_num_rows(mysqli_query($koneksi, "SHOW COLUMNS FROM tamu LIKE 'nomor_wa'")) == 0){
        mysqli_query($koneksi, "ALTER TABLE tamu ADD COLUMN nomor_wa VARCHAR(20) DEFAULT '' AFTER no_hp");
    }
    // Migrasi events (Tampilan)
    $check_ev = mysqli_query($koneksi, "SHOW COLUMNS FROM events LIKE 'bg_mode'");
    if($check_ev && mysqli_num_rows($check_ev) == 0){
        mysqli_query($koneksi, "ALTER TABLE events ADD COLUMN bg_mode VARCHAR(20) DEFAULT 'default', ADD COLUMN bg_youtube VARCHAR(255) DEFAULT NULL, ADD COLUMN bg_image VARCHAR(255) DEFAULT NULL, ADD COLUMN frame_img VARCHAR(255) DEFAULT NULL, ADD COLUMN show_frame TINYINT(1) DEFAULT 0");
    }
    // Migrasi tambahan pengaturan
    if(mysqli_num_rows(mysqli_query($koneksi, "SHOW COLUMNS FROM pengaturan LIKE 'show_frame'")) == 0){
        mysqli_query($koneksi, "ALTER TABLE pengaturan ADD COLUMN show_frame TINYINT(1) DEFAULT 0");
    }
    if(mysqli_num_rows(mysqli_query($koneksi, "SHOW COLUMNS FROM pengaturan LIKE 'favicon'")) == 0){
        mysqli_query($koneksi, "ALTER TABLE pengaturan ADD COLUMN favicon VARCHAR(255) DEFAULT '' AFTER logo_dashboard");
    }
    if(mysqli_num_rows(mysqli_query($koneksi, "SHOW COLUMNS FROM pengaturan LIKE 'wa_support'")) == 0){
        mysqli_query($koneksi, "ALTER TABLE pengaturan ADD COLUMN wa_support VARCHAR(20) DEFAULT '6282322226900' AFTER favicon");
    }
    // Migrasi Hadiah
    if(mysqli_num_rows(mysqli_query($koneksi, "SHOW TABLES LIKE 'hadiah'")) == 0){
        mysqli_query($koneksi, "CREATE TABLE hadiah (
            id INT AUTO_INCREMENT PRIMARY KEY,
            wordpress_post_id INT DEFAULT 0,
            guest_name VARCHAR(255) NOT NULL,
            amount VARCHAR(50) DEFAULT '0',
            bank_name VARCHAR(100) NOT NULL,
            account_name VARCHAR(255) DEFAULT '',
            proof_file VARCHAR(255) DEFAULT '',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
    }
}

// 4. SECURITY HELPERS (RBAC & CSRF)
if(session_status() === PHP_SESSION_NONE) session_start();

// CSRF Protection
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!function_exists('verify_csrf_token')) {
    function verify_csrf_token($token) { 
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string)$token); 
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field() { 
        return '<input type="hidden" name="csrf_token" value="' . $_SESSION['csrf_token'] . '">'; 
    }
}

if (!function_exists('check_csrf')) {
    function check_csrf() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
                die("CSRF token validation failed.");
            }
        }
    }
}

// Role Based Access Control
if (!function_exists('require_login')) {
    function require_login() {
        if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
            header("Location: login.php");
            exit;
        }
    }
}

if (!function_exists('require_admin')) {
    function require_admin() {
        require_login();
        if ($_SESSION['role'] != 'admin') {
            header("Location: dashboard.php");
            exit;
        }
    }
}

// Global Sanitization
if (!function_exists('esc')) {
    function esc($str) {
        global $koneksi;
        return mysqli_real_escape_string($koneksi, trim($str));
    }
}

// Global File Upload Utility (Hardened Security)
if (!function_exists('secure_upload')) {
    function secure_upload($file, $prefix = 'up', $target_dir = 'assets/') {
        if (!isset($file['name']) || empty($file['name']) || $file['error'] !== UPLOAD_ERR_OK) return false;

        $allowed_ext = ['jpg', 'jpeg', 'png', 'webp', 'svg', 'ico'];
        $allowed_mime = [
            'image/jpeg', 'image/png', 'image/webp', 'image/svg+xml', 
            'image/x-icon', 'image/vnd.microsoft.icon'
        ];
        
        $filename = $file['name'];
        $tmp_name = $file['tmp_name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        // 1. Cek Ekstensi
        if (!in_array($ext, $allowed_ext)) return false;

        // 2. Cek MIME Type menggunakan finfo (Lebih Akurat)
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $tmp_name);
            finfo_close($finfo);
            if (!in_array($mime, $allowed_mime)) return false;
        }

        // 3. Cek Header Gambar untuk non-SVG
        if ($ext !== 'svg') {
            $check = @getimagesize($tmp_name);
            if ($check === false) return false;
        }

        // 4. Validasi Ukuran (Maks 2MB)
        if ($file['size'] > 2 * 1024 * 1024) return false;

        $new_name = $prefix . '_' . time() . '_' . rand(100, 999) . '.' . $ext;
        
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        if (move_uploaded_file($tmp_name, $target_dir . $new_name)) {
            return $new_name;
        }

        return false;
    }
}
?>
