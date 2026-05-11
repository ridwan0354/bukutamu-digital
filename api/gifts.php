<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

// Ambil data dari POST
$wordpress_post_id = isset($_POST['wordpress_post_id']) ? intval($_POST['wordpress_post_id']) : 0;
$guest_name = isset($_POST['guest_name']) ? esc($_POST['guest_name']) : '';
$amount = isset($_POST['amount']) ? esc($_POST['amount']) : '0';
$bank_name = isset($_POST['bank_name']) ? esc($_POST['bank_name']) : '';
$account_name = isset($_POST['account_name']) ? esc($_POST['account_name']) : '';

if (empty($guest_name) || empty($bank_name)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Data tidak lengkap']);
    exit;
}

$proof_file = '';

if (isset($_FILES['proof_of_transfer']) && $_FILES['proof_of_transfer']['error'] === UPLOAD_ERR_OK) {
    $target_dir = __DIR__ . '/../assets/gifts/';
    if (!is_dir($target_dir)) {
        @mkdir($target_dir, 0777, true);
    }
    
    $tmp_name = $_FILES['proof_of_transfer']['tmp_name'];
    $filename = $_FILES['proof_of_transfer']['name'];
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    if (empty($ext)) $ext = 'jpg';
    
    $new_name = 'gift_' . time() . '_' . rand(100, 999) . '.' . $ext;
    
    if (move_uploaded_file($tmp_name, $target_dir . $new_name)) {
        $proof_file = $new_name;
    }
}

$query = "INSERT INTO hadiah (wordpress_post_id, guest_name, amount, bank_name, account_name, proof_file) 
          VALUES ('$wordpress_post_id', '$guest_name', '$amount', '$bank_name', '$account_name', '$proof_file')";

if (mysqli_query($koneksi, $query)) {
    echo json_encode(['status' => 'success', 'message' => 'Data berhasil disimpan']);
} else {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan data']);
}
?>
