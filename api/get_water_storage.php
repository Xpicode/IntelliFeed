<?php
// api/get_water_storage.php
require_once 'db_connect.php';
requireLogin();

header('Content-Type: application/json');

try {
    $stmt = $pdo->prepare("
        SELECT 
            wstorage_pct, 
            wstorage_level_cm, 
            wstorage_low,
            timestamp 
        FROM arduino_status 
        WHERE device_id = 'esp32_main' 
        ORDER BY timestamp DESC 
        LIMIT 1
    ");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'wstorage_pct' => (float)$result['wstorage_pct'],
            'wstorage_level_cm' => (float)$result['wstorage_level_cm'],
            'wstorage_low' => (bool)$result['wstorage_low'],
            'timestamp' => $result['timestamp']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No data found'
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => dbError($e)
    ]);
}
?>