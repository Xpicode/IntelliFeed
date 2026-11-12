<?php
header('ngrok-skip-browser-warning: true');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}
// api/get_arduino_status.php
require_once 'db_connect.php';

header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(0);

date_default_timezone_set('Asia/Manila');
$pdo->exec("SET time_zone = '+08:00'");

session_start();
$userId = $_SESSION['user_id'] ?? null;

try {
    $stmt = $pdo->query("SELECT * FROM arduino_status ORDER BY timestamp DESC LIMIT 1");
    $data = $stmt->fetch();

    // CRITICAL FIX: Check if data exists and is recent
    $hasValidData = ($data !== false);
    $isRecent = false;
    
    if ($hasValidData) {
        $lastUpdateTime = strtotime($data['timestamp']);
        $currentTime = time();
        $isRecent = ($currentTime - $lastUpdateTime) < 15; // 15 seconds timeout
    }
    
    // Device is connected ONLY if we have valid AND recent data
    $deviceConnected = ($hasValidData && $isRecent);

    // Feeding stats — per user if logged in, global if not
    if ($userId) {
        $feedStmt = $pdo->prepare("
            SELECT COUNT(*) as count, COALESCE(SUM(`portion`), 0) as total_grams
            FROM feeding_history
            WHERE user_id = ? AND DATE(created_at) = CURDATE()
        ");
        $feedStmt->execute([$userId]);
        $feedingStats = $feedStmt->fetch();

        $lastFeedStmt = $pdo->prepare("
            SELECT created_at FROM feeding_history
            WHERE user_id = ?
            ORDER BY created_at DESC LIMIT 1
        ");
        $lastFeedStmt->execute([$userId]);
        $lastFeed = $lastFeedStmt->fetch();
    } else {
        $feedStmt = $pdo->query("
            SELECT COUNT(*) as count, COALESCE(SUM(`portion`), 0) as total_grams
            FROM feeding_history WHERE DATE(created_at) = CURDATE()
        ");
        $feedingStats = $feedStmt->fetch();

        $lastFeed = $pdo->query("
            SELECT created_at FROM feeding_history
            ORDER BY created_at DESC LIMIT 1
        ")->fetch();
    }

    $pendingCmd = $pdo->query("
        SELECT COUNT(*) as pending FROM feeding_commands WHERE executed = 0
    ")->fetch();

    echo json_encode([
        'success'           => true,
        'weight'            => (float)($data['weight'] ?? 0),
        'water_weight'      => (float)($data['water_weight'] ?? 0),
        'feeding'           => (bool)($data['feeding'] ?? false),
        'food_empty'        => (bool)($data['food_empty'] ?? false),
        'no_plate'          => (bool)($data['no_plate'] ?? false),
        'plate_inside'      => (bool)($data['plate_inside'] ?? false),
        'plate_distance'    => (float)($data['plate_distance'] ?? 0),
        'plate_idle_warn'   => (bool)($data['plate_idle_warn'] ?? false),
        'plate_timeout'     => (bool)($data['plate_timeout'] ?? false),
        'wstorage_pct'      => (float)($data['wstorage_pct'] ?? 0),
        'wstorage_level_cm' => (float)($data['wstorage_level_cm'] ?? 0),
        'wstorage_low'      => (bool)($data['wstorage_low'] ?? false),
        'last_update'       => $data['timestamp'] ?? null,
        'feeding_today'     => (int)($feedingStats['count'] ?? 0),
        'total_grams_today' => (float)($feedingStats['total_grams'] ?? 0),
        'last_feeding_time' => $lastFeed['created_at'] ?? null,
        'pending_commands'  => (int)($pendingCmd['pending'] ?? 0),
        'device_connected'  => $deviceConnected,  // ITO ANG GAGAMITIN NG FRONTEND
        'has_data'          => $hasValidData,
        'is_recent'         => $isRecent
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage(), 'device_connected' => false]);
}
?>