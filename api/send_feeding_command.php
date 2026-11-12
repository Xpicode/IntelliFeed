<?php
// api/send_feeding_command.php

session_start();
require_once 'db_connect.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

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

// user_id: session first, then POST body (same page embeds logged-in id), then first DB user
$userId = null;
if (isset($_SESSION['user_id'])) {
    $userId = (int) $_SESSION['user_id'];
} elseif (isset($data['user_id']) && (int) $data['user_id'] > 0) {
    $userId = (int) $data['user_id'];
} else {
    try {
        $u = $pdo->query('SELECT id FROM users ORDER BY id ASC LIMIT 1')->fetch();
        $userId = $u ? (int) $u['id'] : 1;
    } catch (Exception $e) {
        $userId = 1;
    }
}

try {
    // 1. Ensure feeding_commands table exists with is_scheduled column
    $pdo->exec("CREATE TABLE IF NOT EXISTS `feeding_commands` (
        `id`           INT AUTO_INCREMENT PRIMARY KEY,
        `user_id`      INT NOT NULL DEFAULT 1,
        `device_id`    VARCHAR(50) DEFAULT 'esp32_main',
        `command`      VARCHAR(50) NOT NULL,
        `plate_type`   VARCHAR(20) DEFAULT 'stock',
        `portion`      FLOAT DEFAULT 30,
        `is_scheduled` TINYINT DEFAULT 0,
        `executed`     TINYINT DEFAULT 0,
        `executed_at`  DATETIME DEFAULT NULL,
        `created_at`   DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Check if is_scheduled column exists, if not add it
    try {
        $pdo->exec("ALTER TABLE `feeding_commands` ADD COLUMN `is_scheduled` TINYINT DEFAULT 0 AFTER `portion`");
    } catch (PDOException $e) {
        // Column already exists, ignore error
    }

    // 2. Insert command for ESP32 with is_scheduled flag
    $stmt = $pdo->prepare("
        INSERT INTO `feeding_commands`
            (`user_id`, `device_id`, `command`, `plate_type`, `portion`, `is_scheduled`, `executed`)
        VALUES (?, 'esp32_main', ?, ?, ?, ?, 0)
    ");
    $stmt->execute([$userId, $command, $plateType, $portion, $isScheduled ? 1 : 0]);
    $commandId = $pdo->lastInsertId();

    // 3. Ensure feeding_history table exists with source column
    $pdo->exec("CREATE TABLE IF NOT EXISTS `feeding_history` (
        `id`           INT AUTO_INCREMENT PRIMARY KEY,
        `user_id`      INT NOT NULL DEFAULT 1,
        `device_id`    VARCHAR(50) DEFAULT 'esp32_main',
        `plate_type`   VARCHAR(20) DEFAULT 'stock',
        `portion`      FLOAT DEFAULT 0,
        `weight_final` FLOAT DEFAULT NULL,
        `source`       VARCHAR(20) DEFAULT 'manual',
        `created_at`   DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_user_date` (`user_id`, `created_at`)
    )");

    // Check if source column exists, if not add it
    try {
        $pdo->exec("ALTER TABLE `feeding_history` ADD COLUMN `source` VARCHAR(20) DEFAULT 'manual' AFTER `weight_final`");
    } catch (PDOException $e) {
        // Column already exists, ignore error
    }

    // 4. Save to feeding_history with source
    $source = $isScheduled ? 'schedule' : 'manual';
    $hist = $pdo->prepare("
        INSERT INTO `feeding_history`
            (`user_id`, `device_id`, `plate_type`, `portion`, `source`, `created_at`)
        VALUES (?, 'esp32_main', ?, ?, ?, NOW())
    ");
    $hist->execute([$userId, $plateType, $portion, $source]);
    $histId = $pdo->lastInsertId();

    // Log the action
    $logMsg = $isScheduled ? 
        "Scheduled feeding command saved - {$plateType} plate, {$portion}g" :
        "Manual feeding command saved - {$plateType} plate, {$portion}g";
    
    // Optional: Save to activity log table if exists
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `activity_logs` (
            `id`         INT AUTO_INCREMENT PRIMARY KEY,
            `user_id`    INT DEFAULT 1,
            `action`     VARCHAR(100),
            `details`    TEXT,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        
        $log = $pdo->prepare("INSERT INTO `activity_logs` (`user_id`, `action`, `details`) VALUES (?, 'feeding_command', ?)");
        $log->execute([$userId, $logMsg]);
    } catch (Exception $e) {
        // Log table might not exist, ignore
    }

    echo json_encode([
        'success'      => true,
        'message'      => $isScheduled ? 'Scheduled feeding command saved' : 'Manual feeding command saved',
        'command_id'   => $commandId,
        'history_id'   => $histId,
        'plate_type'   => $plateType,
        'portion'      => $portion,
        'user_id'      => $userId,
        'is_scheduled' => $isScheduled,
        'source'       => $source
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'DB error: ' . $e->getMessage()
    ]);
}
?>