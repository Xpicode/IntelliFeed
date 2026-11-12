<?php
// api/save_schedule.php
// 3-slot schedule system: Breakfast, Lunch, Dinner
// Supports: get, add, update, delete, toggle

session_start();
require_once 'db_connect.php';

header('Content-Type: application/json');
ini_set('display_errors', 0);

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$input  = file_get_contents('php://input');
$data   = json_decode($input, true);
$action = $data['action'] ?? '';

try {
    // ── GET all schedules ──
    if ($action === 'get') {
        $stmt = $pdo->prepare("
            SELECT id, slot_index, schedule_time, meal_label, plate_type, `portion`, is_active, last_triggered
            FROM feeding_schedule
            WHERE user_id = ? AND device_id = 'esp32_main'
            ORDER BY slot_index ASC
        ");
        $stmt->execute([$userId]);
        $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'schedules' => $schedules]);
        exit;
    }

    // ── ADD schedule to a slot ──
    if ($action === 'add') {
        $slotIndex = (int)($data['slot_index'] ?? 0);
        $time      = $data['schedule_time'] ?? '';
        $mealLabel = trim($data['meal_label'] ?? 'Meal');
        $plateType = 'new'; // always new plate for scheduled feeds
        $portion   = (float)($data['portion'] ?? 30);

        if (!$time) { echo json_encode(['success' => false, 'message' => 'Time required']); exit; }
        if ($portion < 10 || $portion > 1000) $portion = 30;
        if ($slotIndex < 0 || $slotIndex > 2) $slotIndex = 0;

        // Delete existing schedule for this slot first (replace)
        $pdo->prepare("
            DELETE FROM feeding_schedule WHERE user_id = ? AND device_id = 'esp32_main' AND slot_index = ?
        ")->execute([$userId, $slotIndex]);

        $stmt = $pdo->prepare("
            INSERT INTO feeding_schedule
                (user_id, device_id, slot_index, schedule_time, meal_label, plate_type, `portion`, is_active)
            VALUES (?, 'esp32_main', ?, ?, ?, ?, ?, 1)
        ");
        $stmt->execute([$userId, $slotIndex, $time, $mealLabel, $plateType, $portion]);

        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
        exit;
    }

    // ── UPDATE existing slot ──
    if ($action === 'update') {
        $id        = (int)($data['id'] ?? 0);
        $time      = $data['schedule_time'] ?? '';
        $mealLabel = trim($data['meal_label'] ?? 'Meal');
        $portion   = (float)($data['portion'] ?? 30);

        if (!$time) { echo json_encode(['success' => false, 'message' => 'Time required']); exit; }
        if ($portion < 10 || $portion > 1000) $portion = 30;

        $pdo->prepare("
            UPDATE feeding_schedule
            SET schedule_time = ?, meal_label = ?, `portion` = ?
            WHERE id = ? AND user_id = ?
        ")->execute([$time, $mealLabel, $portion, $id, $userId]);

        echo json_encode(['success' => true]);
        exit;
    }

    // ── DELETE (clear) slot ──
    if ($action === 'delete') {
        $id = (int)($data['id'] ?? 0);
        $pdo->prepare("DELETE FROM feeding_schedule WHERE id = ? AND user_id = ?")
            ->execute([$id, $userId]);
        echo json_encode(['success' => true]);
        exit;
    }

    // ── TOGGLE active ──
    if ($action === 'toggle') {
        $id = (int)($data['id'] ?? 0);
        $pdo->prepare("
            UPDATE feeding_schedule SET is_active = NOT is_active WHERE id = ? AND user_id = ?
        ")->execute([$id, $userId]);
        $row = $pdo->prepare("SELECT is_active FROM feeding_schedule WHERE id = ?");
        $row->execute([$id]);
        $r = $row->fetch(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'is_active' => (int)$r['is_active']]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Unknown action']);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => dbError($e)]);
}
?>