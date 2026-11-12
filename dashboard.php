<?php
session_start();
require_once 'api/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get user info
$userId = $_SESSION['user_id'];
$username = $_SESSION['username'];
$fullName = $_SESSION['full_name'] ?? $username;

// Get user's saved pets count
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM saved_pets WHERE user_id = ?");
$stmt->execute([$userId]);
$petCount = $stmt->fetch()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - IntelliFeed</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .dashboard-container {
            max-width: 1000px;
            margin: 30px auto;
        }
        
        .welcome-card {
            background: var(--blur-bg);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 15px;
            text-align: center;
            box-shadow: var(--shadow-md);
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary);
        }
        
        .action-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .action-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        
        .action-card:hover {
            transform: translateY(-5px);
            border-color: var(--primary);
            box-shadow: var(--shadow-lg);
        }
        
        .action-icon {
            font-size: 3rem;
            margin-bottom: 15px;
        }
        
        .btn-logout {
            background: var(--danger);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="gradient-bg">
        <div class="container dashboard-container">
            <!-- Welcome Card -->
            <div class="welcome-card">
                <div>
                    <h1>👋 Welcome, <?php echo htmlspecialchars($fullName); ?>!</h1>
                    <p>Manage your pets and feeding schedule</p>
                </div>
                <form action="logout.php" method="POST">
                    <button type="submit" class="btn-logout">Logout</button>
                </form>
            </div>
            
            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $petCount; ?></div>
                    <div>Saved Pets</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">0</div>
                    <div>Today's Feedings</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">0g</div>
                    <div>Food Dispensed</div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <h2 style="margin-bottom:20px;">Quick Actions</h2>
            <div class="action-grid">
                <div class="action-card" onclick="location.href='camera.html'">
                    <div class="action-icon">📹</div>
                    <h3>Watch My Pet</h3>
                    <p>Detect and save your pets</p>
                </div>
                
                <div class="action-card" onclick="location.href='my-pets.php'">
                    <div class="action-icon">🐾</div>
                    <h3>My Saved Pets</h3>
                    <p>View all your saved pets</p>
                </div>
                
                <div class="action-card" onclick="location.href='index.html'">
                    <div class="action-icon">🏠</div>
                    <h3>Main Dashboard</h3>
                    <p>Return to main page</p>
                </div>
                <!-- Add this to your action-grid in dashboard.php -->
<div class="action-card" onclick="location.href='admin_logs.php'">
    <div class="action-icon">📊</div>
    <h3>View Logs</h3>
    <p>User activity tracking</p>
</div>
            </div>
        </div>
    </div>
</body>
</html>