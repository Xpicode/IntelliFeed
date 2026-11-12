<?php
// db_connect.php
// MERGED: Timezone fix + original logUserActivity

// ── Timezone fix ───────────────────────────────────────────
date_default_timezone_set('Asia/Manila');

// ── Settings: config.php if present, otherwise the XAMPP defaults ──
$config = require (file_exists(__DIR__ . '/config.php')
    ? __DIR__ . '/config.php'
    : __DIR__ . '/config.example.php');

try {
    $pdo = new PDO(
        "mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8mb4",
        $config['db_user'],
        $config['db_pass']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE,            PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // ── Fix MySQL session timezone ─────────────────────────
    $pdo->exec("SET time_zone = '+08:00'");

} catch (PDOException $e) {
    error_log("IntelliFeed DB connection failed: " . $e->getMessage());
    http_response_code(500);
    die("Database connection failed.");
}

// ── Security helpers ───────────────────────────────────────

// Log the real error on the server, show the user a generic one.
function dbError(Throwable $e) {
    error_log("IntelliFeed DB error in " . basename($_SERVER['SCRIPT_NAME'] ?? '') . ": " . $e->getMessage());
    return 'Database error';
}

function denyJson($message) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

// For endpoints the dashboard calls: returns the logged-in user's id.
function requireLogin() {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    if (!isset($_SESSION['user_id'])) denyJson('Not authenticated');
    return (int)$_SESSION['user_id'];
}

// For endpoints only the feeder (ESP32) calls: it must send the shared key.
function requireDeviceKey() {
    global $config;
    $sent = $_SERVER['HTTP_X_DEVICE_KEY'] ?? '';
    if (!hash_equals((string)$config['device_key'], $sent)) denyJson('Invalid device key');
}

// ── Log user activity ──────────────────────────────────────
function logUserActivity($userId, $username, $action, $status = 'success', $details = '') {
    global $pdo;

    $ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

    try {
        $stmt = $pdo->prepare("
            INSERT INTO user_logs (user_id, username, action, ip_address, user_agent, status, details)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $username, $action, $ipAddress, $userAgent, $status, $details]);
        error_log("✅ Log inserted: $action for user $username");
        return true;
    } catch (PDOException $e) {
        error_log("❌ Failed to log user activity: " . $e->getMessage());
        return false;
    }
}

?>