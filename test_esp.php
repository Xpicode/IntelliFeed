<?php
$esp_ip = 'http://192.168.0.37';

echo "<h2>ESP32 Connection Test</h2>";

// Test 1: Ping ang ESP32
$ch = curl_init($esp_ip . '/');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<h3>Test 1: ESP32 Root Page</h3>";
if ($response) {
    echo "✅ Connected! HTTP Code: $httpCode<br>";
    echo "Response: " . htmlspecialchars($response);
} else {
    echo "❌ Cannot connect to ESP32";
}

// Test 2: /data endpoint
echo "<h3>Test 2: ESP32 Data Endpoint</h3>";
$ch = curl_init($esp_ip . '/data');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response) {
    echo "✅ Connected! HTTP Code: $httpCode<br>";
    echo "Response: " . htmlspecialchars($response);
} else {
    echo "❌ Cannot connect to /data endpoint";
}
?>