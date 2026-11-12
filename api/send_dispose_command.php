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

// dispose       = plate nasa labas (papasok muna)
// dispose_in    = plate nasa loob na (direct dispose)
$command = $data['command'] ?? 'dispose';
if (!in_array($command, ['dispose', 'dispose_in'])) {
    $command = 'dispose';
}

try {
    $userId = (int)$_SESSION['user_id'];

    $stmt = $pdo->prepare("
        INSERT INTO feeding_commands
            (user_id, device_id, command, plate_type, `portion`, executed)
        VALUES
            (?, 'esp32_main', ?, 'stock', 0, 0)
    ");
    $stmt->execute([$userId, $command]);
    $commandId = $pdo->lastInsertId();

    echo json_encode([
        'success'    => true,
        'message'    => 'Dispose command sent',
        'command'    => $command,
        'command_id' => $commandId
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => dbError($e)]);
}
?>
