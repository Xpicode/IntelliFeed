<?php
// api/get_feeding_command.php
// Called by ESP32 every 2 seconds to check for pending commands.
//
// FIX (IMPROVE): Old executed commands are now pruned to prevent
// the feeding_commands table from growing forever.
// Cleanup runs once per request with a 1% probability to avoid overhead.

require_once 'db_connect.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
ini_set('display_errors', 0);
error_reporting(0);

try {
    // FIX: Probabilistic cleanup — runs ~1 in 100 requests (every ~200s at 2s poll rate)
    // Deletes executed commands older than 7 days.
    if (rand(1, 100) === 1) {
        $pdo->exec("
            DELETE FROM feeding_commands
            WHERE executed <> 0
              AND executed_at < NOW() - INTERVAL 7 DAY
        ");
    }

    // Expire commands the device never picked up (e.g. it was offline), so a
    // stale feed does not run hours later. executed = 2 means "expired".
    $pdo->exec("
        UPDATE feeding_commands
        SET executed = 2, executed_at = NOW()
        WHERE executed = 0
          AND created_at < NOW() - INTERVAL 2 MINUTE
    ");

    // Get oldest unexecuted command
    $stmt = $pdo->prepare("
        SELECT id, command, plate_type, `portion`, created_at
        FROM feeding_commands
        WHERE executed = 0
        ORDER BY created_at ASC
        LIMIT 1
    ");
    $stmt->execute();
    $command = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($command) {
        // Mark as executed immediately (deduplication — won't be picked up twice)
        $pdo->prepare("
            UPDATE feeding_commands
            SET executed = 1, executed_at = NOW()
            WHERE id = ?
        ")->execute([$command['id']]);

        echo json_encode([
            'success'    => true,
            'command'    => $command['command'],
            'plate_type' => $command['plate_type'] ?? 'stock',
            'portion'    => (float)($command['portion'] ?? 30),
            'command_id' => $command['id'],
            'timestamp'  => $command['created_at']
        ]);
    } else {
        echo json_encode(['success' => true, 'command' => null]);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
