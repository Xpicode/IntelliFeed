<?php
// api/arduino_data.php
// UPDATED: May validation at debug logs
header('ngrok-skip-browser-warning: true');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'db_connect.php';

header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(0);

define('KEEP_HISTORY', true);
define('DEBUG_MODE', true); // Set to false in production

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
        // First, check if arduino_status table exists and has correct structure
        $checkTable = $pdo->query("SHOW TABLES LIKE 'arduino_status'");
        if ($checkTable->rowCount() == 0) {
            // Create table if not exists
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS arduino_status (
                    device_id VARCHAR(50) PRIMARY KEY,
                    weight FLOAT DEFAULT 0,
                    water_weight FLOAT DEFAULT 0,
                    feeding TINYINT DEFAULT 0,
                    food_empty TINYINT DEFAULT 0,
                    no_plate TINYINT DEFAULT 0,
                    plate_inside TINYINT DEFAULT 0,
                    plate_distance FLOAT DEFAULT 0,
                    wstorage_pct FLOAT DEFAULT 0,
                    wstorage_level_cm FLOAT DEFAULT 0,
                    wstorage_low TINYINT DEFAULT 0,
                    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )
            ");
            error_log("Created arduino_status table");
        }
        
        // Update or insert into arduino_status
        $stmt = $pdo->prepare("
            INSERT INTO arduino_status
                (device_id, weight, water_weight, feeding, food_empty,
                 no_plate, plate_inside, plate_distance,
                 wstorage_pct, wstorage_level_cm, wstorage_low,
                 timestamp)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
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
            $wstorageLow ? 1 : 0
        ]);
        
        if (!$result) {
            throw new PDOException("Failed to insert/update arduino_status");
        }
        
        if (DEBUG_MODE) {
            error_log("arduino_status updated successfully");
        }

        // Save to history if enabled
        if (KEEP_HISTORY) {
            // Check if arduino_data table exists
            $checkHistory = $pdo->query("SHOW TABLES LIKE 'arduino_data'");
            if ($checkHistory->rowCount() == 0) {
                // Create history table if not exists
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS arduino_data (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        device_id VARCHAR(50),
                        weight FLOAT,
                        water_weight FLOAT,
                        feeding TINYINT,
                        food_empty TINYINT,
                        no_plate TINYINT,
                        plate_inside TINYINT,
                        plate_distance FLOAT,
                        wstorage_pct FLOAT,
                        wstorage_level_cm FLOAT,
                        wstorage_low TINYINT,
                        timestamp DATETIME,
                        INDEX idx_device_timestamp (device_id, timestamp)
                    )
                ");
                error_log("Created arduino_data table");
            }
            
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
            
            if (DEBUG_MODE) {
                error_log("History record saved");
            }
        }

        echo json_encode(['status' => 'success', 'command' => null, 'water_storage' => $wstoragePct]);

    } catch (PDOException $e) {
        error_log("Database error in arduino_data.php: " . $e->getMessage());
        echo json_encode([
            'error' => 'Database error', 
            'message' => $e->getMessage(),
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