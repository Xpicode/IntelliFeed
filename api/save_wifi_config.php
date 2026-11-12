<?php
// api/save_wifi_config.php
// Saves the last-used WiFi SSID and API base URL to DB for reference.
// Does NOT store the password server-side.

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200); exit;
}

session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$ssid    = trim($input['ssid']     ?? '');
$apiBase = trim($input['api_base'] ?? '');

if (!$ssid || !$apiBase) {
    echo json_encode(['success' => false, 'message' => 'Missing fields']);
    exit;
}

try {
    // Create table if needed
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS wifi_config (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            ssid VARCHAR(64),
            api_base VARCHAR(255),
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");

    // Upsert — one record per user
    $stmt = $pdo->prepare("
        INSERT INTO wifi_config (user_id, ssid, api_base, updated_at)
        VALUES (?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE ssid = VALUES(ssid), api_base = VALUES(api_base), updated_at = NOW()
    ");

    // Check if record exists
    $check = $pdo->prepare("SELECT id FROM wifi_config WHERE user_id = ?");
    $check->execute([$_SESSION['user_id']]);
    $existing = $check->fetch();

    if ($existing) {
        $upd = $pdo->prepare("UPDATE wifi_config SET ssid=?, api_base=?, updated_at=NOW() WHERE user_id=?");
        $upd->execute([$ssid, $apiBase, $_SESSION['user_id']]);
    } else {
        $ins = $pdo->prepare("INSERT INTO wifi_config (user_id, ssid, api_base) VALUES (?,?,?)");
        $ins->execute([$_SESSION['user_id'], $ssid, $apiBase]);
    }

    echo json_encode(['success' => true, 'message' => 'Config saved']);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>