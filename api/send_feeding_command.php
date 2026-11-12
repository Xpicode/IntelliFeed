<?php
// api/send_feeding_command.php

session_start();
require_once 'db_connect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200); exit;
}

ini_set('display_errors', 0);
date_default_timezone_set('Asia/Manila');
$pdo->exec("SET time_zone = '+08:00'");

$input = file_get_contents('php://input');
$data  = json_decode($input, true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
    exit;
}

$command     = $data['command']    ?? '';
$portion     = (float)($data['portion']   ?? 30);
$plateType   = $data['plate_type'] ?? $data['type'] ?? 'stock';
$isScheduled = isset($data['scheduled']) ? (bool)$data['scheduled'] : false;

if ($command !== 'feed_now') {
    echo json_encode(['success' => false, 'message' => 'Invalid command: ' . $command]);
    exit;
}

// Para sa scheduled feeding, force NEW PLATE
if ($isScheduled) {
    $plateType = 'new';
}

if (!in_array($plateType, ['stock', 'new'])) $plateType = 'stock';
if ($portion < 10 || $portion > 1000) $portion = 30;

$userId = requireLogin();

try {
    // 2. Insert command for ESP32 with is_scheduled flag
    $stmt = $pdo->prepare("
        INSERT INTO `feeding_commands`
            (`user_id`, `device_id`, `command`, `plate_type`, `portion`, `is_scheduled`, `executed`)
        VALUES (?, 'esp32_main', ?, ?, ?, ?, 0)
    ");
    $stmt->execute([$userId, $command, $plateType, $portion, $isScheduled ? 1 : 0]);
    $commandId = $pdo->lastInsertId();

    // feeding_history is written by log_feed_complete.php when the device
    // reports the feed actually finished - not here, where it is only queued.
    $source = $isScheduled ? 'schedule' : 'manual';

    // Log the action
    $logMsg = $isScheduled ? 
        "Scheduled feeding command saved - {$plateType} plate, {$portion}g" :
        "Manual feeding command saved - {$plateType} plate, {$portion}g";
    
    // Optional: Save to activity log table if exists
    try {
        $log = $pdo->prepare("INSERT INTO `activity_logs` (`user_id`, `action`, `details`) VALUES (?, 'feeding_command', ?)");
        $log->execute([$userId, $logMsg]);
    } catch (Exception $e) {
        // Log table might not exist, ignore
    }

    echo json_encode([
        'success'      => true,
        'message'      => $isScheduled ? 'Scheduled feeding command saved' : 'Manual feeding command saved',
        'command_id'   => $commandId,
        'plate_type'   => $plateType,
        'portion'      => $portion,
        'user_id'      => $userId,
        'is_scheduled' => $isScheduled,
        'source'       => $source
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => dbError($e)
    ]);
}
?>