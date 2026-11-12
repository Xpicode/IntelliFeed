<?php
// api/get_esp32_config.php
header('Content-Type: application/json');
require_once 'db_connect.php';

session_start();

try {
    // You can store ESP32 config in database
    $stmt = $pdo->query("SELECT config_value FROM system_config WHERE config_key = 'esp32_api_host'");
    $config = $stmt->fetch();
    
    $apiHost = $config ? $config['config_value'] : '10.213.15.60';
    
    echo json_encode([
        'success' => true,
        'api_host' => $apiHost,
        'api_path' => '/IntelliFeed/api/'
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>