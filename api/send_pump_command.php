<?php
session_start();
require_once 'db_connect.php';

header('Content-Type: application/json');
ini_set('display_errors', 0);

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$input = file_get_contents('php://input');
$data  = json_decode($input, true);
$secs  = (int)($data['seconds'] ?? 5);
if ($secs < 1 || $secs > 60) $secs = 5;

try {
    $userId = (int)$_SESSION['user_id'];

    $stmt = $pdo->prepare("
        INSERT INTO feeding_commands
            (user_id, device_id, command, plate_type, `portion`, executed)
        VALUES
            (?, 'esp32_main', ?, 'stock', ?, 0)
    ");
    $stmt->execute([$userId, 'pump', $secs]);
    $commandId = $pdo->lastInsertId();

    echo json_encode([
        'success'    => true,
        'message'    => "Pump command sent ({$secs}s)",
        'command_id' => $commandId,
        'seconds'    => $secs
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'DB error: ' . $e->getMessage()]);
}
?>
