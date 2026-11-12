<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$stmt = $pdo->prepare("INSERT INTO arduino_commands (command, status, created_at) VALUES ('plate_in', 'pending', NOW())");
$stmt->execute();

echo json_encode(['success' => true]);
?>