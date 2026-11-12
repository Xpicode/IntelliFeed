<?php
session_start();
header('Content-Type: application/json');
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

// ── Fix timezone to Philippine Time ──────────────────────
date_default_timezone_set('Asia/Manila');
$pdo->exec("SET time_zone = '+08:00'");

$userId = $_SESSION['user_id'];
$range  = isset($_GET['range']) ? $_GET['range'] : 'all';

try {
    // ── Ensure table exists ───────────────────────────────
    $pdo->exec("CREATE TABLE IF NOT EXISTS feeding_history (
        id           INT AUTO_INCREMENT PRIMARY KEY,
        user_id      INT NOT NULL,
        device_id    VARCHAR(50) DEFAULT 'esp32_main',
        plate_type   VARCHAR(20) DEFAULT 'stock',
        portion      FLOAT DEFAULT 0,
        weight_final FLOAT DEFAULT NULL,
        created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user_date (user_id, created_at)
    )");

    // ── Build query ───────────────────────────────────────
    $sql    = "SELECT id, user_id, device_id, plate_type, portion, weight_final, created_at
               FROM feeding_history
               WHERE user_id = ?";
    $params = [$userId];

    $today = date('Y-m-d'); // Philippine time

    if ($range === 'today') {
        $sql .= " AND DATE(created_at) = ?";
        $params[] = $today;
    } elseif ($range === 'week') {
        $weekAgo = date('Y-m-d H:i:s', strtotime('-7 days'));
        $sql .= " AND created_at >= ?";
        $params[] = $weekAgo;
    } elseif ($range === 'month') {
        $monthAgo = date('Y-m-d H:i:s', strtotime('-30 days'));
        $sql .= " AND created_at >= ?";
        $params[] = $monthAgo;
    }
    // 'all' = no filter

    $sql .= " ORDER BY created_at DESC LIMIT 500";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ── Stats ─────────────────────────────────────────────
    $totalFeedings = count($history);
    $totalGrams    = $totalFeedings > 0 ? array_sum(array_column($history, 'portion')) : 0;
    $avgPortion    = $totalFeedings > 0 ? $totalGrams / $totalFeedings : 0;

    echo json_encode([
        'success' => true,
        'history' => $history,
        'stats'   => [
            'total_feedings' => $totalFeedings,
            'total_grams'    => round($totalGrams, 1),
            'avg_portion'    => round($avgPortion, 1)
        ]
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>