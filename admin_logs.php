<?php
session_start();
require_once 'api/db_connect.php';

// Only allow logged in users (you can add admin check later)
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get filter
$filter = $_GET['filter'] ?? 'all';
$userId = $_SESSION['user_id'];

// Build query
$query = "SELECT * FROM user_logs WHERE user_id = ?";
$params = [$userId];

if ($filter === 'login') {
    $query .= " AND action IN ('login', 'logout')";
} elseif ($filter === 'failed') {
    $query .= " AND action = 'failed_login'";
}

$query .= " ORDER BY created_at DESC LIMIT 100";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$logs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Logs - IntelliFeed</title>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
            margin: 0;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
        }
        .subtitle {
            color: #718096;
            margin-bottom: 30px;
        }
        .filter-bar {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }
        .filter-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            background: #edf2f7;
            color: #4a5568;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s;
        }
        .filter-btn:hover {
            background: #e2e8f0;
            transform: translateY(-2px);
        }
        .filter-btn.active {
            background: #667eea;
            color: white;
        }
        .filter-btn.failed.active {
            background: #f56565;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        th {
            background: #f7fafc;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #4a5568;
            border-bottom: 2px solid #e2e8f0;
        }
        td {
            padding: 12px 15px;
            border-bottom: 1px solid #e2e8f0;
        }
        tr:hover {
            background: #f7fafc;
        }
        .badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        .badge-success {
            background: #c6f6d5;
            color: #22543d;
        }
        .badge-failed {
            background: #fed7d7;
            color: #742a2a;
        }
        .badge-login {
            background: #bee3f8;
            color: #2c5282;
        }
        .badge-logout {
            background: #e9d8fd;
            color: #553c9a;
        }
        .ip-address {
            font-family: monospace;
            color: #718096;
        }
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: #f7fafc;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
        }
        .stat-number {
            font-size: 32px;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 5px;
        }
        .stat-label {
            color: #718096;
            font-size: 14px;
        }
        .user-agent {
            font-size: 12px;
            color: #a0aec0;
            max-width: 250px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php" class="back-link">← Back to Dashboard</a>
        
        <h1>📊 Activity Logs</h1>
        <p class="subtitle">Track your login history and account activity</p>
        
        <!-- Stats -->
        <div class="stats-cards">
            <?php
            $total = count($logs);
            $loginStmt = $pdo->prepare("SELECT COUNT(*) FROM user_logs WHERE user_id = ? AND action IN ('login', 'logout')");
            $loginStmt->execute([$userId]);
            $logins = $loginStmt->fetchColumn();
            
            $failedStmt = $pdo->prepare("SELECT COUNT(*) FROM user_logs WHERE user_id = ? AND action = 'failed_login'");
            $failedStmt->execute([$userId]);
            $failed = $failedStmt->fetchColumn();
            ?>
            <div class="stat-card">
                <div class="stat-number"><?php echo $total; ?></div>
                <div class="stat-label">Total Activities</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $logins; ?></div>
                <div class="stat-label">Logins/Logouts</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $failed; ?></div>
                <div class="stat-label">Failed Attempts</div>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="filter-bar">
            <a href="?filter=all" class="filter-btn <?php echo $filter === 'all' ? 'active' : ''; ?>">All Logs</a>
            <a href="?filter=login" class="filter-btn <?php echo $filter === 'login' ? 'active' : ''; ?>">Logins/Logouts</a>
            <a href="?filter=failed" class="filter-btn failed <?php echo $filter === 'failed' ? 'active' : ''; ?>">Failed Attempts</a>
        </div>
        
        <!-- Logs Table -->
        <table>
            <thead>
                <tr>
                    <th>Time</th>
                    <th>Action</th>
                    <th>Status</th>
                    <th>IP Address</th>
                    <th>Device</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="6" style="text-align:center; padding:40px; color:#a0aec0;">
                            No logs found
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?php echo date('M j, Y g:i A', strtotime($log['created_at'])); ?></td>
                            <td>
                                <?php
                                $actionClass = '';
                                switch($log['action']) {
                                    case 'login':
                                        echo '<span class="badge badge-login">🔐 Login</span>';
                                        break;
                                    case 'logout':
                                        echo '<span class="badge badge-logout">🚪 Logout</span>';
                                        break;
                                    case 'failed_login':
                                        echo '<span class="badge badge-failed">❌ Failed Login</span>';
                                        break;
                                    case 'register':
                                        echo '<span class="badge badge-success">📝 Register</span>';
                                        break;
                                    default:
                                        echo $log['action'];
                                }
                                ?>
                            </td>
                            <td>
                                <span class="badge <?php echo $log['status'] === 'success' ? 'badge-success' : 'badge-failed'; ?>">
                                    <?php echo $log['status']; ?>
                                </span>
                            </td>
                            <td class="ip-address"><?php echo $log['ip_address']; ?></td>
                            <td>
                                <div class="user-agent" title="<?php echo htmlspecialchars($log['user_agent']); ?>">
                                    <?php 
                                    $ua = $log['user_agent'];
                                    if (strpos($ua, 'Windows') !== false) echo '💻 Windows';
                                    elseif (strpos($ua, 'Mac') !== false) echo '🍏 Mac';
                                    elseif (strpos($ua, 'Linux') !== false) echo '🐧 Linux';
                                    elseif (strpos($ua, 'iPhone') !== false) echo '📱 iPhone';
                                    elseif (strpos($ua, 'Android') !== false) echo '📱 Android';
                                    else echo '🌐 Browser';
                                    ?>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($log['details'] ?? ''); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>