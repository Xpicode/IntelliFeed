<?php
// api/save_feeding_record.php
// FIX: Accept user_id from POST body as fallback when session is unavailable
//      (cross-origin fetch with credentials may not carry session cookie)

session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200); exit;
}

require_once 'db_connect.php';

date_default_timezone_set('Asia/Manila');
$pdo->exec("SET time_zone = '+08:00'");

// ── Debug log inside api/ folder for easy access ──────────
function debugLog($msg) {
    $logFile = __DIR__ . '/save_feeding_debug.log';
    file_put_contents($logFile, date('Y-m-d H:i:s') . ' | ' . $msg . "\n", FILE_APPEND);
}

$input = file_get_contents('php://input');
$data  = json_decode($input, true);

debugLog("=== NEW REQUEST ===");
debugLog("RAW: " . $input);
debugLog("SESSION user_id: " . ($_SESSION['user_id'] ?? 'NOT SET'));

if (!$data) {
    debugLog("ERROR: Invalid JSON");
    echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
    exit;
}

// ── Get user_id: session first, fallback to POST body ─────
$userId = null;
if (isset($_SESSION['user_id'])) {
    $userId = (int)$_SESSION['user_id'];
    debugLog("user_id from SESSION: $userId");
} elseif (isset($data['user_id'])) {
    $userId = (int)$data['user_id'];
    debugLog("user_id from POST body: $userId");
} else {
    debugLog("ERROR: No user_id found anywhere");
    echo json_encode(['success' => false, 'message' => 'Not authenticated - no user_id']);
    exit;
}

$action      = $data['action']       ?? 'save';
$plateType   = $data['plate_type']   ?? 'stock';
$portion     = isset($data['portion'])      ? floatval($data['portion'])      : 0;
$weightFinal = isset($data['weight_final']) ? floatval($data['weight_final']) : null;
$deviceId    = 'esp32_main';

debugLog("action=$action plate_type=$plateType portion=$portion weight_final=" . ($weightFinal ?? 'null') . " user_id=$userId");

if (!in_array($plateType, ['stock', 'new'])) {
    $plateType = 'stock';
}

try {
    if ($action === 'save') {
        if ($portion <= 0) {
            debugLog("ERROR: Invalid portion=$portion");
            echo json_encode(['success' => false, 'message' => 'Invalid portion: ' . $portion]);
            exit;
        }

        $stmt = $pdo->prepare("
            INSERT INTO feeding_history
                (user_id, device_id, plate_type, `portion`, weight_final, source, created_at)
            VALUES (?, ?, ?, ?, ?, 'simulated', NOW())
        ");
        $result = $stmt->execute([$userId, $deviceId, $plateType, $portion, $weightFinal]);

        if (!$result) {
            $err = $stmt->errorInfo();
            debugLog("DB INSERT FAILED: " . implode(' | ', $err));
            echo json_encode(['success' => false, 'message' => 'DB error: ' . ($err[2] ?? 'unknown')]);
            exit;
        }

        $newId = $pdo->lastInsertId();
        debugLog("SUCCESS: inserted id=$newId");

        echo json_encode([
            'success' => true,
            'id'      => $newId,
            'message' => 'Feeding record saved'
        ]);

    } else {
        debugLog("ERROR: Unknown action=$action");
        echo json_encode(['success' => false, 'message' => 'Unknown action']);
    }

} catch (PDOException $e) {
    debugLog("PDOException: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>