<?php
// api/confirm_wifi_applied.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'db_connect.php';

$input = json_decode(file_get_contents('php://input'), true);
$status = $input['status'] ?? '';

if ($status === 'success') {
    // Clear pending config or mark as completed
    $stmt = $pdo->prepare("UPDATE wifi_config SET status = 'completed' WHERE status = 'applied'");
    $stmt->execute();
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}
?>