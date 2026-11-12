<?php
// db_connect.php
// MERGED: Timezone fix + original logUserActivity + checkLogsTable functions

// ── Timezone fix ───────────────────────────────────────────
date_default_timezone_set('Asia/Manila');

// ── Database config ────────────────────────────────────────
$host     = 'localhost';
$dbname   = 'intellifeed';
$username = 'root';
$password = '';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE,            PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // ── Fix MySQL session timezone ─────────────────────────
    $pdo->exec("SET time_zone = '+08:00'");

} catch (PDOException $e) {
    die("❌ Connection failed: " . $e->getMessage());
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

// ── Check / create user_logs table ────────────────────────
function checkLogsTable() {
    global $pdo;

    try {
        $result = $pdo->query("SHOW TABLES LIKE 'user_logs'");
        if ($result->rowCount() == 0) {
            $pdo->exec("
                CREATE TABLE user_logs (
                    id         INT AUTO_INCREMENT PRIMARY KEY,
                    user_id    INT          NOT NULL,
                    username   VARCHAR(50)  NOT NULL,
                    action     ENUM('login','logout','failed_login','register') NOT NULL,
                    ip_address VARCHAR(45),
                    user_agent TEXT,
                    status     VARCHAR(20)  DEFAULT 'success',
                    details    TEXT,
                    created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            return "Table created";
        }

        // Check if 'details' column exists
        $columns = $pdo->query("DESCRIBE user_logs")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('details', $columns)) {
            $pdo->exec("ALTER TABLE user_logs ADD COLUMN details TEXT AFTER status");
            return "Added details column";
        }

        return "Table OK";

    } catch (Exception $e) {
        return "Error: " . $e->getMessage();
    }
}

checkLogsTable();
?>