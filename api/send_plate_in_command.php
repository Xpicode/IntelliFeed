<?php
// api/send_plate_in_command.php
// Queues a "plate_in" command; the ESP32 picks it up via get_feeding_command.php.
session_start();
require_once 'db_connect.php';

header('Content-Type: application/json');
ini_set('display_errors', 0);

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO feeding_commands
            (user_id, device_id, command, plate_type, `portion`, executed)
        VALUES
            (?, 'esp32_main', 'plate_in', 'stock', 0, 0)
    ");
    $stmt->execute([(int)$_SESSION['user_id']]);

    echo json_encode([
        'success'    => true,
        'message'    => 'Plate-in command sent',
        'command_id' => $pdo->lastInsertId()
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'DB error: ' . $e->getMessage()]);
}
?>
