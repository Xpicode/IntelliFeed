<?php
// api/log_feed_complete.php
// Called by ESP32 when it receives FEED_COMPLETE from the Arduino.
// This ensures feeding_history records ACTUAL dispenses, not just queued commands.
//
// ESP32 should POST to this endpoint after receiving "FEED_COMPLETE:XX.Xg" from Arduino.
// Add to ESP32 firmware inside the Serial2 FEED_COMPLETE handler:
//
//   HTTPClient http;
//   http.begin(feedCompleteUrl);   // "http://192.168.0.37/IntelliFeed/api/log_feed_complete.php"
//   http.addHeader("Content-Type", "application/json");
//   StaticJsonDocument<128> doc;
//   doc["device_id"]  = "esp32_main";
//   doc["dispensed_g"]= dispensed;
//   doc["plate_type"] = lastPlateType;   // store the last plate type used
//   String body; serializeJson(doc, body);
//   http.POST(body);
//   http.end();

require_once 'db_connect.php';

header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(0);

$input = file_get_contents('php://input');
$data  = json_decode($input, true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$data) {
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

$deviceId   =       ($data['device_id']   ?? 'esp32_main');
$dispensed  = (float)($data['dispensed_g'] ?? 0);
$plateType  =       ($data['plate_type']   ?? 'stock');

if ($dispensed <= 0) {
    echo json_encode(['error' => 'Invalid dispensed weight']);
    exit;
}

try {
    // Find the most recent executed feeding command for this device
    // to get the user_id (ESP32 doesn't have a session)
    $cmdStmt = $pdo->prepare("
        SELECT user_id FROM feeding_commands
        WHERE device_id = ? AND executed = 1
        ORDER BY executed_at DESC
        LIMIT 1
    ");
    $cmdStmt->execute([$deviceId]);
    $cmd = $cmdStmt->fetch(PDO::FETCH_ASSOC);
    $userId = $cmd ? (int)$cmd['user_id'] : 1;  // fallback to user 1 if not found

    $pdo->prepare("
        INSERT INTO feeding_history
            (user_id, device_id, plate_type, `portion`)
        VALUES
            (?, ?, ?, ?)
    ")->execute([$userId, $deviceId, $plateType, $dispensed]);

    echo json_encode(['success' => true, 'logged_grams' => $dispensed]);

} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
