<?php
/**
 * API Endpoint: guest-details.php
 * Digunakan oleh plugin Eveent Widgets untuk menampilkan info tamu pada e-invitation.
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Izinkan request dari domain WordPress mana pun
header('Access-Control-Allow-Methods: GET, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../koneksi.php';

$id = $_GET['id'] ?? '';
$id_decoded = urldecode($id);

if (empty($id_decoded)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing ID']);
    exit;
}

$id_esc = mysqli_real_escape_string($koneksi, $id_decoded);

// Ambil data tamu berdasarkan nama_tamu (yang dikirim sebagai id oleh plugin Eveent)
$query = mysqli_query($koneksi, "SELECT * FROM tamu WHERE nama_tamu = '$id_esc' ORDER BY id DESC LIMIT 1");

if (mysqli_num_rows($query) > 0) {
    $row = mysqli_fetch_assoc($query);
    
    // Eveent mengharapkan beberapa field: table_number, rsvp_count, allowed_events_keys (sebagai validasi login)
    echo json_encode([
        'status' => 'success',
        'guest_name' => $row['nama_tamu'],
        'table_number' => $row['kategori'], // Menggunakan kategori sebagai representasi meja/kelas
        'rsvp_count' => (int)$row['jumlah_orang'],
        'allowed_events_keys' => ['all'], // Array sembarang agar Eveent menganggap tamu "terdaftar"
        'master_events' => []
    ]);
} else {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Guest not found']);
}
