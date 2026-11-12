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
                
                // Log successful login
                logUserActivity(
                    $user['id'], 
                    $user['username'], 
                    'login', 
                    'success', 
                    'Logged in successfully from ' . ($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown device')
                );
                
                // New session id on login, so a pre-login id cannot be reused
                session_regenerate_id(true);

                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'] ?? $user['username'];
                $_SESSION['is_admin'] = $is_admin;
                
                // Update last login
                $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $updateStmt->execute([$user['id']]);
                
                // Redirect to index.php
                header('Location: index.php');
                exit;
            } else {
                // Log failed login attempt
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
            $error = 'Something went wrong. Please try again.';
            error_log("Login error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes, viewport-fit=cover">
    <title>Login · IntelliFeed</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* ===== SAME STYLES AS INDEX.PHP ===== */
        :root {
            --bg-primary: #faf7f2;
            --bg-gradient: radial-gradient(circle at top right, #f5efe6, #f0e9e0);
            --text-primary: #2c3e4f;
            --text-secondary: #5d6d7e;
            --text-muted: #8a9aa8;
            --card-bg: rgba(255, 255, 255, 0.85);
            --card-border: rgba(210, 190, 170, 0.3);
            --shadow-color: rgba(0, 0, 0, 0.05);
            --hover-bg: #faf0e6;
            --input-bg: #ffffff;
            --input-border: #e2d9d0;
            --badge-bg: #f2ebe4;
            --primary-accent: #9b8c7c;
            --secondary-accent: #b9a89b;
            --success: #10B981;
            --warning: #F59E0B;
            --danger: #EF4444;
            --info: #3B82F6;
            --success-light: #e8f3ed;
            --warning-light: #fff1e0;
            --danger-light: #ffe9e9;
            --info-light: #e6f0ff;
        }
        
        [data-theme="dark"] {
            --bg-primary: #1a1e24;
            --bg-gradient: radial-gradient(circle at top right, #232931, #1a1f26);
            --text-primary: #e6e9f0;
            --text-secondary: #b4bcca;
            --text-muted: #8e99aa;
            --card-bg: rgba(35, 41, 49, 0.85);
            --card-border: rgba(75, 85, 99, 0.2);
            --shadow-color: rgba(0, 0, 0, 0.3);
            --hover-bg: #2d353f;
            --input-bg: #2d353f;
            --input-border: #404b5a;
            --badge-bg: #2d353f;
            --primary-accent: #b9a89b;
            --secondary-accent: #9b8c7c;
            --success-light: #1e2b24;
            --warning-light: #2e281c;
            --danger-light: #2e1e1e;
            --info-light: #1e2a3a;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-primary);
            min-height: 100vh;
            color: var(--text-primary);
            transition: background-color 0.3s ease, color 0.3s ease;
            line-height: 1.5;
            -webkit-tap-highlight-color: transparent;
        }
        
        .gradient-bg {
            background: var(--bg-gradient);
            min-height: 100vh;
            padding: 20px;
            position: relative;
            overflow: hidden;
            transition: background 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            position: relative;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* ===== LOGIN CARD STYLES ===== */
        .login-card {
            background: var(--card-bg);
            backdrop-filter: blur(10px);
            border-radius: 28px;
            padding: 40px;
            border: 1px solid var(--card-border);
            box-shadow: 0 20px 40px var(--shadow-color);
            width: 100%;
            max-width: 450px;
            transition: all 0.3s ease;
        }
        
        .login-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 25px 50px var(--shadow-color);
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 32px;
        }
        
        .login-logo {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
        }
        
        .login-logo img {
            height: 80px;
            width: auto;
            border-radius: 16px;
            box-shadow: 0 4px 12px var(--shadow-color);
            transition: transform 0.2s ease;
            border: 1px solid var(--card-border);
        }
        
        .login-logo img:hover {
            transform: scale(1.02);
        }
        
        .login-header h1 {
            font-size: 1.8rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 8px;
        }
        
        .login-header .subtitle {
            font-size: 0.85rem;
            color: var(--text-muted);
        }
        
        /* Form Styles */
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            font-size: 0.85rem;
            color: var(--text-secondary);
        }
        
        .input-wrapper {
            position: relative;
        }
        
        .input-wrapper input {
            width: 100%;
            padding: 12px 16px;
            background: var(--input-bg);
            border: 1px solid var(--input-border);
            border-radius: 16px;
            font-family: 'Inter', sans-serif;
            font-size: 0.95rem;
            color: var(--text-primary);
            transition: all 0.2s ease;
        }
        
        .input-wrapper input:focus {
            outline: none;
            border-color: var(--primary-accent);
            box-shadow: 0 0 0 3px rgba(155, 140, 124, 0.1);
        }
        
        .toggle-password {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            font-size: 1.1rem;
            padding: 4px;
            color: var(--text-muted);
            transition: color 0.2s ease;
        }
        
        .toggle-password:hover {
            color: var(--primary-accent);
        }
        
        /* Remember Me */
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 15px 0;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: 16px;
            height: 16px;
            cursor: pointer;
            accent-color: var(--primary-accent);
        }
        
        .checkbox-group label {
            margin: 0;
            cursor: pointer;
            font-size: 0.8rem;
            color: var(--text-secondary);
        }
        
        /* Submit Button */
        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, var(--primary-accent), var(--secondary-accent));
            color: white;
            border: none;
            border-radius: 40px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(155, 140, 124, 0.3);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        /* Links */
        .links {
            text-align: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid var(--card-border);
        }
        
        .links a {
            color: var(--primary-accent);
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 500;
            transition: color 0.2s ease;
        }
        
        .links a:hover {
            color: var(--secondary-accent);
            text-decoration: underline;
        }
        
        .links p {
            margin-top: 12px;
            color: var(--text-muted);
            font-size: 0.75rem;
        }
        
        /* Alert Messages */
        .alert {
            padding: 12px 16px;
            border-radius: 16px;
            margin-bottom: 20px;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-error {
            background: var(--danger-light);
            border-left: 4px solid var(--danger);
            color: var(--danger);
        }
        
        .alert-success {
            background: var(--success-light);
            border-left: 4px solid var(--success);
            color: var(--success);
        }
        
        .alert-icon {
            font-size: 1.1rem;
        }
        
        /* Theme Toggle for Login Page */
        .theme-toggle {
            position: fixed;
            top: 20px;
            right: 20px;
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 40px;
            padding: 8px 14px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--text-secondary);
            backdrop-filter: blur(10px);
            transition: all 0.2s ease;
            z-index: 100;
        }
        
        .theme-toggle:hover {
            background: var(--hover-bg);
            color: var(--text-primary);
        }
        
        .theme-toggle .moon { display: none; }
        [data-theme="dark"] .theme-toggle .sun { display: none; }
        [data-theme="dark"] .theme-toggle .moon { display: inline-block; }
        
        /* Responsive */
        @media (max-width: 768px) {
            .gradient-bg { padding: 16px; }
            .login-card { padding: 28px; margin: 0 12px; }
            .login-header h1 { font-size: 1.5rem; }
            .login-logo img { height: 60px; }
            .btn-login { padding: 12px; }
            .theme-toggle { top: 10px; right: 10px; padding: 6px 12px; font-size: 0.75rem; }
        }
        
        @media (max-width: 480px) {
            .login-card { padding: 24px; }
            .form-group input { padding: 10px 14px; }
            .btn-login { padding: 12px; }
            .login-logo img { height: 50px; }
        }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@400..700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/regular/style.css">
    <link rel="stylesheet" href="theme.css">
</head>
<body data-theme="light">
<div class="gradient-bg">
    <!-- Theme Toggle Button -->
    <button id="themeToggle" class="theme-toggle" onclick="toggleTheme()">
        <span class="sun">☀️</span>
        <span class="moon">🌙</span>
        <span id="themeText">Light</span>
    </button>
    
    <div class="container">
        <div class="login-card">
            <div class="login-header">
                <div class="login-logo">
                    <img src="images/logo.jpg" alt="IntelliFeed Logo" onerror="this.style.display='none'">
                </div>
                <h1>Welcome to IntelliFeed+</h1>
                <p class="subtitle">Log in to your smart pet care dashboard</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <span class="alert-icon">⚠️</span>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <span class="alert-icon">✅</span>
                    <span><?php echo htmlspecialchars($success); ?></span>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" id="loginForm">
                <div class="form-group">
                    <label>Username or Email</label>
                    <div class="input-wrapper">
                        <input type="text" name="username" required placeholder="Enter your username or email"
                               value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Password</label>
                    <div class="input-wrapper">
                        <input type="password" name="password" id="password" required placeholder="Enter your password">
                        <button type="button" class="toggle-password" onclick="togglePassword()" id="toggleBtn">👁️</button>
                    </div>
                </div>
                
                <div class="checkbox-group">
                    <input type="checkbox" name="remember" id="remember">
                    <label for="remember">Remember me</label>
                </div>
                
                <button type="submit" class="btn-login">Log In</button>
            </form>
            
            <div class="links">
                <a href="register.php">Create an account</a>
                <p>
                    <a href="forgot_password.php">Forgot password?</a>
                </p>
            </div>
        </div>
    </div>
</div>

<script>
    // ===== THEME TOGGLE =====
    function toggleTheme() {
        const currentTheme = document.body.getAttribute('data-theme') || 'light';
        const newTheme = currentTheme === 'light' ? 'dark' : 'light';
        
        document.body.setAttribute('data-theme', newTheme);
        
        const themeText = document.getElementById('themeText');
        if (themeText) {
            themeText.textContent = newTheme === 'light' ? 'Light' : 'Dark';
        }
        
        localStorage.setItem('login_theme', newTheme);
    }
    
    function loadThemePreference() {
        const savedTheme = localStorage.getItem('login_theme') || 'light';
        document.body.setAttribute('data-theme', savedTheme);
        
        const themeText = document.getElementById('themeText');
        if (themeText) {
            themeText.textContent = savedTheme === 'light' ? 'Light' : 'Dark';
        }
    }
    
    // ===== PASSWORD TOGGLE =====
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
    
    // ===== INITIALIZE =====
    document.addEventListener('DOMContentLoaded', function() {
        loadThemePreference();
        
        // Add animation to login card
        const loginCard = document.querySelector('.login-card');
        if (loginCard) {
            loginCard.style.opacity = '0';
            loginCard.style.transform = 'translateY(20px)';
            setTimeout(() => {
                loginCard.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                loginCard.style.opacity = '1';
                loginCard.style.transform = 'translateY(0)';
            }, 100);
        }
        
        // Focus on username field
        const usernameField = document.querySelector('input[name="username"]');
        if (usernameField && !usernameField.value) {
            usernameField.focus();
        }
    });
    
    // Remember me functionality (store in localStorage)
    const rememberCheckbox = document.getElementById('remember');
    const usernameInput = document.querySelector('input[name="username"]');
    const passwordInput = document.getElementById('password');
    
    // Load saved credentials if remember me was checked
    if (localStorage.getItem('remembered_username')) {
        usernameInput.value = localStorage.getItem('remembered_username');
        rememberCheckbox.checked = true;
    }
    
    // Save credentials when form is submitted
    document.getElementById('loginForm').addEventListener('submit', function() {
        if (rememberCheckbox.checked) {
            localStorage.setItem('remembered_username', usernameInput.value);
        } else {
            localStorage.removeItem('remembered_username');
        }
    });
</script>
<script src="motion.js"></script>
</body>
</html>