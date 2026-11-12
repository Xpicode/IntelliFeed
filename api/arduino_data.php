<?php
// api/arduino_data.php
// UPDATED: May validation at debug logs
header('ngrok-skip-browser-warning: true');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'db_connect.php';
requireDeviceKey();

header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(0);

define('KEEP_HISTORY', true);
define('DEBUG_MODE', false); // Set to false in production

$input = file_get_contents('php://input');
$data  = json_decode($input, true);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $data) {

    // Debug: Log received data
    if (DEBUG_MODE) {
        error_log("=== Arduino Data Received ===");
        error_log("Raw input: " . $input);
        error_log("Parsed data: " . print_r($data, true));
    }

    $weight         = (float)($data['weight']            ?? 0);
    $waterWeight    = (float)($data['water_weight']      ?? 0);
    $feeding        = (bool)($data['feeding']            ?? false);
    $foodEmpty      = (bool)($data['food_empty']         ?? false);
    $noPlate        = (bool)($data['no_plate']           ?? false);
    $plateInside    = (bool)($data['plate_inside']       ?? false);
    $plateDist      = (float)($data['plate_distance']    ?? 0);
    $plateIdleWarn  = (bool)($data['plate_idle_warn']    ?? false);
    $plateTimeout   = (bool)($data['plate_timeout']      ?? false);
    $plateOut       = (bool)($data['plate_out']          ?? false);
    $plateMov       = (bool)($data['plate_mov']          ?? false);
    
    // Validate water storage data
    $wstoragePct    = (float)($data['wstorage_pct']      ?? 0);
    $wstorageLvlCm  = (float)($data['wstorage_level_cm'] ?? 0);
    $wstorageLow    = (bool)($data['wstorage_low']       ?? false);
    
    // Ensure values are within valid ranges
    $wstoragePct = max(0, min(100, $wstoragePct));  // Between 0-100%
    $wstorageLvlCm = max(0, $wstorageLvlCm);        // No negative values
    
    // Auto-set wstorage_low based on percentage if not provided
    if (!isset($data['wstorage_low'])) {
        $wstorageLow = ($wstoragePct <= 20);
    }
    
    $deviceId       =       ($data['device_id']          ?? 'esp32_main');

    // Debug: Log processed water storage values
    if (DEBUG_MODE) {
        error_log("Processed water storage:");
        error_log("  - Percentage: " . $wstoragePct . "%");
        error_log("  - Level: " . $wstorageLvlCm . " cm");
        error_log("  - Low: " . ($wstorageLow ? "YES" : "NO"));
    }

    try {
        // Update or insert into arduino_status
        $stmt = $pdo->prepare("
            INSERT INTO arduino_status
                (device_id, weight, water_weight, feeding, food_empty,
                 no_plate, plate_inside, plate_distance,
                 wstorage_pct, wstorage_level_cm, wstorage_low,
                 plate_idle_warn, plate_timeout, plate_out, plate_mov,
                 timestamp)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
                weight            = VALUES(weight),
                water_weight      = VALUES(water_weight),
                feeding           = VALUES(feeding),
                food_empty        = VALUES(food_empty),
                no_plate          = VALUES(no_plate),
                plate_inside      = VALUES(plate_inside),
                plate_distance    = VALUES(plate_distance),
                wstorage_pct      = VALUES(wstorage_pct),
                wstorage_level_cm = VALUES(wstorage_level_cm),
                wstorage_low      = VALUES(wstorage_low),
                plate_idle_warn   = VALUES(plate_idle_warn),
                plate_timeout     = VALUES(plate_timeout),
                plate_out         = VALUES(plate_out),
                plate_mov         = VALUES(plate_mov),
                timestamp         = VALUES(timestamp)
        ");
        
        $result = $stmt->execute([
            $deviceId, 
            $weight, 
            $waterWeight,
            $feeding ? 1 : 0, 
            $foodEmpty ? 1 : 0,
            $noPlate ? 1 : 0, 
            $plateInside ? 1 : 0, 
            $plateDist,
            $wstoragePct, 
            $wstorageLvlCm, 
            $wstorageLow ? 1 : 0,
            $plateIdleWarn ? 1 : 0,
            $plateTimeout ? 1 : 0,
            $plateOut ? 1 : 0,
            $plateMov ? 1 : 0
        ]);
        
        if (!$result) {
            throw new PDOException("Failed to insert/update arduino_status");
        }
        
        if (DEBUG_MODE) {
            error_log("arduino_status updated successfully");
        }

        // Save to history if enabled
        if (KEEP_HISTORY) {
            $stmtHistory = $pdo->prepare("
                INSERT INTO arduino_data
                    (device_id, weight, water_weight, feeding, food_empty,
                     no_plate, plate_inside, plate_distance,
                     wstorage_pct, wstorage_level_cm, wstorage_low,
                     timestamp)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmtHistory->execute([
                $deviceId, $weight, $waterWeight,
                $feeding ? 1 : 0, $foodEmpty ? 1 : 0,
                $noPlate ? 1 : 0, $plateInside ? 1 : 0, $plateDist,
                $wstoragePct, $wstorageLvlCm, $wstorageLow ? 1 : 0
            ]);
            
            // Keep 48 hours of history. Runs on roughly 1 request in 500
            // (replaces the old intellifeed_cleanup.bat scheduled task).
            if (random_int(1, 500) === 1) {
                $pdo->exec("DELETE FROM arduino_data WHERE timestamp < NOW() - INTERVAL 48 HOUR");
            }
        }

        echo json_encode(['status' => 'success', 'command' => null, 'water_storage' => $wstoragePct]);

    } catch (PDOException $e) {
        error_log("Database error in arduino_data.php: " . $e->getMessage());
        echo json_encode([
            'error' => 'Database error', 
            'message' => dbError($e),
            'status' => 'error'
        ]);
    }

} else {
    echo json_encode([
        'error' => 'Invalid request',
        'method' => $_SERVER['REQUEST_METHOD'],
        'has_data' => !empty($data)
    ]);
}
?>