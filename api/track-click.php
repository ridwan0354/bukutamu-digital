<?php
/**
 * API Endpoint: track-click.php
 * Endpoint dummy untuk menampung request click tracking dari widget Eveent.
 * Kita cukup mengembalikan 200 OK agar console browser tidak menampilkan error.
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); 
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

echo json_encode(['status' => 'success', 'message' => 'Click tracked']);
