<?php
// api/get_wifi_config.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'db_connect.php';

try {
    // Get pending WiFi config
    $stmt = $pdo->prepare("
        SELECT id, ssid, password, server_host, status 
        FROM wifi_config 
        WHERE status = 'pending' 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->execute();
    $config = $stmt->fetch();
    
    if ($config) {
        // Mark as applied so ESP32 doesn't get it again
        $update = $pdo->prepare("UPDATE wifi_config SET status = 'applied' WHERE id = ?");
        $update->execute([$config['id']]);
        
        echo json_encode([
            'success' => true,
            'has_config' => true,
            'ssid' => $config['ssid'],
            'password' => $config['password'],
            'server_host' => $config['server_host']
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'has_config' => false
        ]);
    }
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>