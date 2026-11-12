<?php
session_start();
require_once 'api/db_connect.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = isset($_GET['message']) && $_GET['message'] === 'logged_out' ? 'You have been logged out successfully' : '';
$debug_mode = isset($_GET['debug']) || true; // Enable debug mode

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter username and password';
    } else {
        try {
            // Get user by username or email
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password_hash'])) {
                // Check if is_admin column exists, if not, default to 0
                $is_admin = isset($user['is_admin']) ? $user['is_admin'] : 0;
                
                // LOG SUCCESSFUL LOGIN
                $logResult = logUserActivity(
                    $user['id'], 
                    $user['username'], 
                    'login', 
                    'success', 
                    'Logged in successfully from ' . ($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown device')
                );
                
                // Debug: Store log result in session
                $_SESSION['debug_log_result'] = $logResult;
                
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'] ?? $user['username'];
                $_SESSION['is_admin'] = $is_admin; // ADD THIS LINE
                
                // Update last login
                $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $updateStmt->execute([$user['id']]);
                
                // Redirect to index.php
                header('Location: index.php');
                exit;
            } else {
                // LOG FAILED LOGIN ATTEMPT
                $userCheck = $pdo->prepare("SELECT id, username FROM users WHERE username = ? OR email = ?");
                $userCheck->execute([$username, $username]);
                $failedUser = $userCheck->fetch();
                
                if ($failedUser) {
                    logUserActivity(
                        $failedUser['id'], 
                        $failedUser['username'], 
                        'failed_login', 
                        'failed', 
                        'Incorrect password attempt'
                    );
                } else {
                    logUserActivity(
                        0, 
                        $username, 
                        'failed_login', 
                        'failed', 
                        'Username not found'
                    );
                }
                
                $error = 'Invalid username or password';
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
            error_log("Login error: " . $e->getMessage());
        }
    }
}

// Get debug info
$debug_info = [];
if ($debug_mode) {
    try {
        // Check if user_logs table exists and has records
        $tableCheck = $pdo->query("SHOW TABLES LIKE 'user_logs'")->rowCount();
        $debug_info['table_exists'] = $tableCheck > 0;
        
        if ($debug_info['table_exists']) {
            $count = $pdo->query("SELECT COUNT(*) FROM user_logs")->fetchColumn();
            $debug_info['log_count'] = $count;
            
            // Get last 5 logs
            $lastLogs = $pdo->query("SELECT * FROM user_logs ORDER BY id DESC LIMIT 5")->fetchAll();
            $debug_info['last_logs'] = $lastLogs;
        }
        
        // Check users
        $userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $debug_info['user_count'] = $userCount;
        
    } catch (Exception $e) {
        $debug_info['error'] = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - IntelliFeed</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 400px;
            padding: 40px;
        }
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }
        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
            font-size: 14px;
        }
        .input-wrapper {
            position: relative;
        }
        input {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s;
            font-family: inherit;
        }
        input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
        }
        .toggle-password {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #999;
            font-size: 18px;
        }
        .toggle-password:hover {
            color: #667eea;
        }
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 15px 0;
        }
        .checkbox-group input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        .checkbox-group label {
            margin: 0;
            cursor: pointer;
            color: #666;
        }
        button[type="submit"] {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
        }
        button[type="submit"]:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102,126,234,0.3);
        }
        .error {
            background: #fee;
            color: #e53e3e;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            border-left: 4px solid #e53e3e;
        }
        .success {
            background: #e6fffa;
            color: #2c7a7b;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            border-left: 4px solid #2c7a7b;
        }
        .links {
            text-align: center;
            margin-top: 25px;
        }
        .links a {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
        }
        .links a:hover {
            text-decoration: underline;
        }
        .demo-credentials {
            background: #f7fafc;
            border-radius: 8px;
            padding: 15px;
            margin-top: 20px;
            font-size: 13px;
            color: #718096;
            border: 1px dashed #cbd5e0;
        }
        .debug-section {
            margin-top: 30px;
            padding: 15px;
            background: #1a202c;
            border-radius: 10px;
            color: #cbd5e0;
            font-size: 12px;
            font-family: monospace;
            display: <?php echo $debug_mode ? 'block' : 'none'; ?>;
        }
        .debug-section h3 {
            color: #a0aec0;
            margin-bottom: 10px;
            font-size: 14px;
        }
        .debug-item {
            padding: 5px 0;
            border-bottom: 1px solid #2d3748;
        }
        .debug-success {
            color: #68d391;
        }
        .debug-error {
            color: #fc8181;
        }
        .debug-link {
            text-align: center;
            margin-top: 15px;
            font-size: 12px;
        }
        .debug-link a {
            color: #a0aec0;
            text-decoration: none;
        }
        .debug-link a:hover {
            color: white;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1>🔐 Welcome Back</h1>
        <p class="subtitle">Log in to your IntelliFeed account</p>
        
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="" id="loginForm">
            <div class="form-group">
                <label>Username or Email</label>
                <input type="text" name="username" required placeholder="Enter your username or email"
                       value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label>Password</label>
                <div class="input-wrapper">
                    <input type="password" name="password" id="password" required placeholder="Enter your password">
                    <button type="button" class="toggle-password" onclick="togglePassword()" id="toggleBtn">👁️</button>
                </div>
              
            </div>
            
            <button type="submit">Log In</button>
        </form>
        
        <div class="links">
            <p>Don't have an account? <a href="register.php">Sign up</a></p>
            <p style="margin-top:10px;"><a href="forgot_password.php">Forgot password?</a></p>
        </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleBtn = document.getElementById('toggleBtn');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleBtn.textContent = '🔒';
            } else {
                passwordInput.type = 'password';
                toggleBtn.textContent = '👁️';
            }
        }      
        
    </script>
</body>
</html>