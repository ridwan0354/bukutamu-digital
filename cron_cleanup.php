<?php
/**
 * CRON CLEANUP SCRIPT
 * BUKU TAMU DIGITAL
 * 
 * Script ini digunakan untuk menghapus data event secara otomatis
 * jika umur event sudah lebih dari 1 tahun dari event_date.
 * 
 * Eksekusi disarankan menggunakan Cron Job di CPanel (misal setiap jam 00:00)
 */

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require 'koneksi.php';

// Tentukan batas waktu: 1 Tahun yang lalu dari hari ini
$one_year_ago = date('Y-m-d', strtotime('-1 year'));

// Cari semua event yang tanggalnya sudah <= 1 tahun yang lalu
$q_events = mysqli_query($koneksi, "SELECT * FROM events WHERE event_date <= '$one_year_ago'");

$deleted_events = 0;
$deleted_users = 0;
$deleted_guests = 0;
$deleted_photos = 0;

if ($q_events && mysqli_num_rows($q_events) > 0) {
    while ($event = mysqli_fetch_assoc($q_events)) {
        $event_id = $event['id'];
        $user_id = $event['user_id'];
        
        // 1. Hapus Foto Selfie & Record Tamu
        $q_tamu = mysqli_query($koneksi, "SELECT id, photo FROM tamu WHERE event_id = '$event_id'");
        if ($q_tamu && mysqli_num_rows($q_tamu) > 0) {
            while ($tamu = mysqli_fetch_assoc($q_tamu)) {
                // Hapus fisik foto jika ada
                if (!empty($tamu['photo']) && file_exists($tamu['photo'])) {
                    unlink($tamu['photo']);
                    $deleted_photos++;
                }
                $deleted_guests++;
            }
        }
        // Hapus record seluruh tamu untuk event ini
        mysqli_query($koneksi, "DELETE FROM tamu WHERE event_id = '$event_id'");
        
        // (Opsional) Hapus file Event Logo / Cover Image dari folder assets 
        // Hanya jika Anda yakin tidak digunakan bersamaan oleh event lain.
        // if (!empty($event['event_logo']) && file_exists('assets/' . $event['event_logo'])) { unlink('assets/' . $event['event_logo']); }
        // if (!empty($event['event_photo']) && file_exists('assets/' . $event['event_photo'])) { unlink('assets/' . $event['event_photo']); }
        
        // 2. Hapus Event
        mysqli_query($koneksi, "DELETE FROM events WHERE id = '$event_id'");
        $deleted_events++;
        
        // 3. Cek apakah User (Mempelai) masih memiliki event aktif yang lain
        $q_check_user_events = mysqli_query($koneksi, "SELECT id FROM events WHERE user_id = '$user_id'");
        if (mysqli_num_rows($q_check_user_events) == 0) {
            // User ini tidak punya event lagi, HAPUS AKUN TOTAL
            
            // Hapus Kategori Tamu miliknya
            mysqli_query($koneksi, "DELETE FROM kategori_tamu WHERE user_id = '$user_id'");
            
            // Hapus Akun Mempelai dan Resepsionis yang terkait (parent_id)
            mysqli_query($koneksi, "DELETE FROM users WHERE id = '$user_id' OR parent_id = '$user_id'");
            
            $deleted_users++;
        }
    }
}

// Log Response Output
header('Content-Type: application/json');
echo json_encode([
    'status' => 'success',
    'message' => 'Cleanup process finished.',
    'summary' => [
        'batas_waktu_penghapusan' => $one_year_ago,
        'events_deleted' => $deleted_events,
        'guests_deleted' => $deleted_guests,
        'photos_deleted' => $deleted_photos,
        'users_deleted' => $deleted_users
    ]
]);
?>
