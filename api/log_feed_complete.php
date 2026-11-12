<?php
// api/log_feed_complete.php
// Called by the ESP32 when the Arduino reports FEED_COMPLETE:<grams>.
// This is the ONLY place a real feed is written to feeding_history, so history
// shows what was actually dispensed (not what was merely queued).
//
// POST JSON: { "device_id": "esp32_main", "dispensed_g": 28.4, "command_id": 123 }
// command_id = id from get_feeding_command.php, or 0 for a physical-button feed.

require_once 'db_connect.php';

header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(0);

$data = json_decode(file_get_contents('php://input'), true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$data) {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

$deviceId  =        ($data['device_id']   ?? 'esp32_main');
$dispensed = (float)($data['dispensed_g'] ?? 0);
$commandId = (int)  ($data['command_id']  ?? 0);

if ($dispensed <= 0 || $dispensed > 2000) {
    echo json_encode(['success' => false, 'error' => 'Invalid dispensed weight']);
    exit;
}

try {
    $cmd = false;
    if ($commandId > 0) {
        $stmt = $pdo->prepare("
            SELECT user_id, plate_type, is_scheduled
            FROM feeding_commands
            WHERE id = ? AND command = 'feed_now'
        ");
        $stmt->execute([$commandId]);
        $cmd = $stmt->fetch();
    }

    if ($cmd) {
        $userId    = (int)$cmd['user_id'];
        $plateType = $cmd['plate_type'];
        $source    = $cmd['is_scheduled'] ? 'schedule' : 'manual';
    } else {
        // Physical button: no command row. Credit the feeder's most recent user.
        $last = $pdo->query("
            SELECT user_id FROM feeding_commands
            WHERE user_id > 0 ORDER BY id DESC LIMIT 1
        ")->fetch();
        if (!$last) {
            $last = $pdo->query("SELECT id AS user_id FROM users ORDER BY id ASC LIMIT 1")->fetch();
        }
        $userId    = $last ? (int)$last['user_id'] : 0;
        $plateType = 'stock';
        $source    = 'button';
    }

    $pdo->prepare("
        INSERT INTO feeding_history (user_id, device_id, plate_type, `portion`, source)
        VALUES (?, ?, ?, ?, ?)
    ")->execute([$userId, $deviceId, $plateType, $dispensed, $source]);

    echo json_encode([
        'success'      => true,
        'logged_grams' => $dispensed,
        'source'       => $source,
        'history_id'   => (int)$pdo->lastInsertId()
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
