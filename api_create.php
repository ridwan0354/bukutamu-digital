<?php
/**
 * ============================================================
 * API ENDPOINT: api_create.php
 * BUKUTAMU DIGITAL Eksklusif
 * ============================================================
 * Menerima request dari sistem Laravel (galipatstory.id) untuk
 * membuat user baru dan event baru secara otomatis.
 *
 * Method : POST
 * Auth   : Header X-API-KEY harus cocok dengan LARAVEL_API_KEY
 *
 * Payload JSON yang diharapkan:
 * {
 *   "nama_mempelai"    : "Putra & Putri",
 *   "whatsapp"         : "6282213806914",
 *   "post_id"          : 1234,            // wp_post_id dari Laravel
 *   "invitation_link"  : "https://...",   // wp_link dari Laravel
 *   "event_name"       : "Resepsi Pernikahan",
 *   "event_date"       : "2026-04-13",    // format Y-m-d
 *   "event_location"   : "Hotel Grand Ballroom"
 * }
 *
 * Response JSON:
 * {
 *   "success"    : true,
 *   "is_new_user": true,
 *   "username"   : "6282213806914",
 *   "password"   : "13042026",
 *   "event_id"   : 5,
 *   "login_url"  : "https://qr.galipatstory.com/login.php",
 *   "message"    : "..."
 * }
 * ============================================================
 */

// 1. Header Response
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-KEY');

// Handle CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 2. Hanya izinkan POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed. Gunakan POST.']);
    exit;
}

// 3. Load konfigurasi & koneksi
require_once __DIR__ . '/tables.php';
require_once __DIR__ . '/koneksi.php';

// 4. Validasi API Key
$received_key = $_SERVER['HTTP_X_API_KEY'] ?? '';
if (!defined('LARAVEL_API_KEY') || empty($received_key) || !hash_equals(LARAVEL_API_KEY, $received_key)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized: API Key tidak valid.']);
    exit;
}

// 5. Parse Body JSON
$body = file_get_contents('php://input');
$data = json_decode($body, true);

if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Bad Request: Body harus berupa JSON yang valid.']);
    exit;
}

// 6. Validasi Field Wajib
$required = ['nama_mempelai', 'whatsapp', 'event_name', 'event_date'];
foreach ($required as $field) {
    if (empty($data[$field])) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => "Field '{$field}' wajib diisi."]);
        exit;
    }
}

// ============================================================
// 7. PROSES UTAMA
// ============================================================
$nama_mempelai  = mysqli_real_escape_string($koneksi, trim($data['nama_mempelai']));
$whatsapp       = preg_replace('/[^0-9]/', '', trim($data['whatsapp'])); // Hanya angka
$post_id        = (int)($data['post_id'] ?? 0);
$invitation_link= mysqli_real_escape_string($koneksi, trim($data['invitation_link'] ?? ''));
$event_name     = mysqli_real_escape_string($koneksi, trim($data['event_name']));
$event_date_raw = trim($data['event_date']); // format Y-m-d dari Laravel
$event_location = mysqli_real_escape_string($koneksi, trim($data['event_location'] ?? 'Lokasi Belum Ditentukan'));

// Pastikan format tanggal valid
$event_date_obj = DateTime::createFromFormat('Y-m-d', $event_date_raw);
if (!$event_date_obj) {
    // Coba parse format lain
    try {
        $event_date_obj = new DateTime($event_date_raw);
    } catch (Exception $e) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Format event_date tidak valid. Gunakan Y-m-d.']);
        exit;
    }
}
$event_date_mysql = $event_date_obj->format('Y-m-d');

// ============================================================
// A. TENTUKAN USERNAME — Handle Duplikat dengan Suffix _2, _3 dst
// ============================================================
$base_username = $whatsapp; // contoh: 6282213806914
$username_final = $base_username;
$is_new_user = true;
$existing_user_id = null;

// Cari semua username yang diawali dengan base_username ini
$q_check = mysqli_query($koneksi, "SELECT id, username FROM users WHERE username = '$base_username' OR username LIKE '{$base_username}_%' ORDER BY id ASC");

if (mysqli_num_rows($q_check) > 0) {
    // Ada user sebelumnya — cari suffix tertinggi
    $max_suffix = 1;
    while ($row = mysqli_fetch_assoc($q_check)) {
        $u = $row['username'];
        if ($u === $base_username) {
            // username dasar = suffix 1
            if ($max_suffix < 1) $max_suffix = 1;
        } elseif (preg_match('/^' . preg_quote($base_username, '/') . '_(\d+)$/', $u, $m)) {
            if ((int)$m[1] > $max_suffix) $max_suffix = (int)$m[1];
        }
    }
    // Username baru = base + _(max_suffix + 1)
    $username_final = $base_username . '_' . ($max_suffix + 1);
    $is_new_user = true; // tetap buat user baru
}

// ============================================================
// B. GENERATE PASSWORD dari Tanggal Acara → ddmmyyyy
// ============================================================
$password_plain = $event_date_obj->format('dmY'); // contoh: "13042026"
$password_hashed = password_hash($password_plain, PASSWORD_DEFAULT);

// ============================================================
// C. BUAT USER BARU DI BUKUTAMU
// ============================================================
$username_esc = mysqli_real_escape_string($koneksi, $username_final);
$nama_esc     = $nama_mempelai;

$sql_insert_user = "INSERT INTO users 
    (username, password, nama_lengkap, role, parent_id, event_limit, post_id) 
    VALUES 
    ('$username_esc', '$password_hashed', '$nama_esc', 'mempelai', 0, 1, $post_id)";

if (!mysqli_query($koneksi, $sql_insert_user)) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Gagal membuat user: ' . mysqli_error($koneksi)
    ]);
    exit;
}
$new_user_id = mysqli_insert_id($koneksi);

// ============================================================
// D. BUAT EVENT BARU DI BUKUTAMU (terhubung ke user baru)
// ============================================================
$event_name_esc = $event_name;
$event_loc_esc  = $event_location;
$broadcast_link = $invitation_link;

$sql_insert_event = "INSERT INTO events 
    (user_id, event_name, event_date, event_location, deskripsi_acara, broadcast_link, status) 
    VALUES 
    ($new_user_id, '$event_name_esc', '$event_date_mysql', '$event_loc_esc', 'Dibuat otomatis dari sistem Galipat Story', '$broadcast_link', 'active')";

if (!mysqli_query($koneksi, $sql_insert_event)) {
    // Rollback user jika event gagal
    mysqli_query($koneksi, "DELETE FROM users WHERE id = $new_user_id");
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Gagal membuat event: ' . mysqli_error($koneksi)
    ]);
    exit;
}
$new_event_id = mysqli_insert_id($koneksi);

// ============================================================
// E. BUILD RESPONSE
// ============================================================
$base_url = $is_local 
    ? 'http://localhost/bukutamu' 
    : 'https://qr.galipatstory.com';

$login_url = $base_url . '/login.php';

http_response_code(201);
echo json_encode([
    'success'     => true,
    'is_new_user' => true,
    'user_id'     => $new_user_id,
    'username'    => $username_final,
    'password'    => $password_plain,
    'event_id'    => $new_event_id,
    'event_name'  => $data['event_name'],
    'event_date'  => $event_date_mysql,
    'login_url'   => $login_url,
    'message'     => "Akun dan event berhasil dibuat. Username: {$username_final}"
]);
exit;
