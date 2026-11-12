<?php
session_start();
require_once 'api/db_connect.php';

// Only allow if logged in (for security)
if (!isset($_SESSION['user_id'])) {
    die('Please login first');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Logs - IntelliFeed</title>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
            margin: 0;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        }
        h1 {
            color: #333;
            margin-bottom: 20px;
        }
        .test-buttons {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        button {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-login {
            background: #48bb78;
            color: white;
        }
        .btn-failed {
            background: #f56565;
            color: white;
        }
        .btn-view {
            background: #4299e1;
            color: white;
        }
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th {
            background: #f7fafc;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #4a5568;
        }
        td {
            padding: 10px 12px;
            border-bottom: 1px solid #e2e8f0;
        }
        tr:hover {
            background: #f7fafc;
        }
        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-success {
            background: #c6f6d5;
            color: #22543d;
        }
        .badge-failed {
            background: #fed7d7;
            color: #742a2a;
        }
        .ip-address {
            font-family: monospace;
            color: #718096;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📊 User Logs Test</h1>
        
        <div class="test-buttons">
            <button class="btn-login" onclick="testLogin()">Test Successful Login</button>
            <button class="btn-failed" onclick="testFailedLogin()">Test Failed Login</button>
            <button class="btn-view" onclick="window.location.reload()">Refresh Logs</button>
            <button class="btn-view" onclick="window.location.href='index.php'">Back to Home</button>
        </div>
        
        <?php
        // Test successful login (simulated)
        if (isset($_GET['test']) && $_GET['test'] === 'login') {
            logUserActivity(
                $_SESSION['user_id'],
                $_SESSION['username'],
                'login',
                'success',
                'Test login from test page'
            );
            echo '<div style="background:#c6f6d5; color:#22543d; padding:12px; border-radius:8px; margin-bottom:20px;">✅ Test login logged successfully!</div>';
        }
        
        // Test failed login (simulated)
        if (isset($_GET['test']) && $_GET['test'] === 'failed') {
            logUserActivity(
                $_SESSION['user_id'],
                $_SESSION['username'],
                'failed_login',
                'failed',
                'Test failed login attempt'
            );
            echo '<div style="background:#fed7d7; color:#742a2a; padding:12px; border-radius:8px; margin-bottom:20px;">⚠️ Test failed login logged!</div>';
        }
        
        // Display logs
        try {
            $stmt = $pdo->prepare("
                SELECT * FROM user_logs 
                WHERE user_id = ? 
                ORDER BY created_at DESC 
                LIMIT 50
            ");
            $stmt->execute([$_SESSION['user_id']]);
            $logs = $stmt->fetchAll();
            
            echo "<h2>Your Recent Logs</h2>";
            
            if (count($logs) === 0) {
                echo "<p style='color:#718096; text-align:center; padding:30px;'>No logs found. Try the test buttons above!</p>";
            } else {
                echo "<table>";
                echo "<tr>
                        <th>Time</th>
                        <th>Action</th>
                        <th>Status</th>
                        <th>IP Address</th>
                        <th>Details</th>
                      </tr>";
                
                foreach ($logs as $log) {
                    $statusClass = $log['status'] === 'success' ? 'badge-success' : 'badge-failed';
                    $actionIcon = '';
                    switch($log['action']) {
                        case 'login': $actionIcon = '🔐'; break;
                        case 'logout': $actionIcon = '🚪'; break;
                        case 'failed_login': $actionIcon = '❌'; break;
                        case 'register': $actionIcon = '📝'; break;
                        default: $actionIcon = '📌';
                    }
                    
                    echo "<tr>";
                    echo "<td>" . date('M j, g:i A', strtotime($log['created_at'])) . "</td>";
                    echo "<td><span class='badge'>{$actionIcon} {$log['action']}</span></td>";
                    echo "<td><span class='badge {$statusClass}'>{$log['status']}</span></td>";
                    echo "<td class='ip-address'>{$log['ip_address']}</td>";
                    echo "<td>" . htmlspecialchars(substr($log['details'] ?? '', 0, 50)) . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
            
        } catch (Exception $e) {
            echo "<div style='background:#fed7d7; color:#742a2a; padding:12px; border-radius:8px;'>Error loading logs: " . $e->getMessage() . "</div>";
        }
        ?>
    </div>
    
    <script>
        function testLogin() {
            window.location.href = '?test=login';
        }
        
        function testFailedLogin() {
            window.location.href = '?test=failed';
        }
    </script>
</body>
</html>