<?php
/**
 * API Endpoint: guest-rsvp-status.php
 * Digunakan oleh plugin Eveent Widgets untuk mengetahui apakah tamu sudah check-in.
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); 
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

// Ambil data tamu berdasarkan nama_tamu
$query = mysqli_query($koneksi, "SELECT * FROM tamu WHERE nama_tamu = '$id_esc' ORDER BY id DESC LIMIT 1");

if (mysqli_num_rows($query) > 0) {
    $row = mysqli_fetch_assoc($query);
    
    $is_attending = !empty($row['checkin_at']);
    $checkin_time = $is_attending ? date('H:i', strtotime($row['checkin_at'])) : null;
    
    // Format JSON yang diharapkan oleh ev-barcode-handler / widget JS
    echo json_encode([
        'status' => 'success',
        'rsvp' => [
            'is_attending' => $is_attending,
            'checkin_time' => $checkin_time,
            'wp_rsvp_pax' => (int)$row['jumlah_orang']
        ]
    ]);
} else {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Guest not found']);
}
