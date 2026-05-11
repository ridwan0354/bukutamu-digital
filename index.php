<?php
/**
 * File: index.php
 * Deskripsi: Mencegah directory listing dan mengarahkan pengguna ke halaman login.
 */

// Memulai session untuk mengecek status login
session_start();

// Jika pengguna sudah login, arahkan langsung ke Dashboard
if (isset($_SESSION['status']) && $_SESSION['status'] == "login") {
    header("Location: dashboard.php");
    exit;
} else {
    // Jika belum login, arahkan ke halaman Login
    header("Location: login.php");
    exit;
}
?>