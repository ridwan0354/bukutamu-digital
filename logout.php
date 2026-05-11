<?php
/**
 * File: logout.php
 * Deskripsi: Menghapus session dan mengeluarkan pengguna dari sistem.
 */

// Memulai session agar bisa diakses data session yang ingin dihapus
session_start();

// Menghapus semua variabel session
$_SESSION = array();

// Jika ingin benar-benar menghapus session cookie (opsional tapi disarankan)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Menghancurkan session secara total
session_destroy();

// Mengarahkan kembali ke halaman login
header("Location: login");
exit;