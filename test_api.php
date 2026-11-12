<?php
echo "<h1>🔍 API Test</h1>";

$apiUrl = 'http://localhost/IntelliFeed/api/saved_pets.php';

echo "<h2>Testing API URL:</h2>";
echo "<p>$apiUrl</p>";

// Test if file exists
$filePath = __DIR__ . '/api/saved_pets.php';
if (file_exists($filePath)) {
    echo "<p style='color:green'>✅ File exists at: $filePath</p>";
} else {
    echo "<p style='color:red'>❌ File does NOT exist at: $filePath</p>";
}

// Test API response
echo "<h2>Testing API Response:</h2>";
$ch = curl_init($apiUrl . '?action=get');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, false);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<p>HTTP Status Code: $httpCode</p>";

if ($httpCode === 200) {
    echo "<p style='color:green'>✅ API is reachable!</p>";
    echo "<h3>Response:</h3>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
} else {
    echo "<p style='color:red'>❌ API returned error code $httpCode</p>";
}
?>