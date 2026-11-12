<?php
// api/check_schedule.php
// UPDATED:
// 1. Auto dispose command inserted BEFORE feed command
// 2. meal_label saved and returned
// 3. Timezone fixed (from db_connect.php)

require_once 'db_connect.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
ini_set('display_errors', 0);

try {
    $now     = new DateTime();
    $timeNow = $now->format('H:i:s');
    $today   = $now->format('Y-m-d');

    // Debug: all active schedules
    $debugStmt = $pdo->prepare("
        SELECT id, schedule_time, meal_label, plate_type, `portion`, is_active, last_triggered
        FROM feeding_schedule
        WHERE is_active = 1
        ORDER BY schedule_time ASC
    ");
    $debugStmt->execute();
    $allActive = $debugStmt->fetchAll(PDO::FETCH_ASSOC);

    // 2-minute window para hindi ma-miss ng 30s polling
    $stmt = $pdo->prepare("
        SELECT id, user_id, schedule_time, meal_label, plate_type, `portion`
        FROM feeding_schedule
        WHERE is_active = 1
          AND schedule_time <= ?
          AND schedule_time >= SUBTIME(?, '00:02:00')
          AND (last_triggered IS NULL OR DATE(last_triggered) < ?)
        ORDER BY schedule_time ASC
        LIMIT 1
    ");
    $stmt->execute([$timeNow, $timeNow, $today]);
    $schedule = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($schedule) {
        // Mark as triggered
        $pdo->prepare("
            UPDATE feeding_schedule SET last_triggered = NOW() WHERE id = ?
        ")->execute([$schedule['id']]);

        // The device disposes the old plate itself before a scheduled feed and
        // waits for that to finish, so no separate dispose command is queued.
        $pdo->prepare("
            INSERT INTO feeding_commands
                (user_id, device_id, command, plate_type, `portion`, is_scheduled, executed)
            VALUES (?, 'esp32_main', 'feed_now', ?, ?, 1, 0)
        ")->execute([
            $schedule['user_id'],
            $schedule['plate_type'],
            $schedule['portion']
        ]);

        echo json_encode([
            'success'       => true,
            'triggered'     => true,
            'schedule_id'   => $schedule['id'],
            'schedule_time' => $schedule['schedule_time'],
            'meal_label'    => $schedule['meal_label'] ?? 'Meal',
            'plate_type'    => $schedule['plate_type'],
            'portion'       => $schedule['portion'],
            'server_time'   => $timeNow,
            'auto_dispose'  => true   // done by the device
        ]);
    } else {
        echo json_encode([
            'success'          => true,
            'triggered'        => false,
            'server_time'      => $timeNow,
            'active_count'     => count($allActive),
            'active_schedules' => $allActive
        ]);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>