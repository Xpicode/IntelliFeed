<?php
session_start();
require_once 'api/db_connect.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $full_name = trim($_POST['full_name'] ?? '');
    
    // Validate input
    if (empty($username) || empty($email) || empty($password)) {
        $error = 'All fields are required';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $error = 'Username can only contain letters, numbers, and underscores';
    } else {
        try {
            // Check if username or email already exists
            $checkStmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $checkStmt->execute([$username, $email]);
            
            if ($checkStmt->fetch()) {
                $error = 'Username or email already exists';
            } else {
                // Hash password
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert new user
                $insertStmt = $pdo->prepare("
                    INSERT INTO users (username, email, password_hash, full_name) 
                    VALUES (?, ?, ?, ?)
                ");
                
                if ($insertStmt->execute([$username, $email, $passwordHash, $full_name])) {
                    $newUserId = $pdo->lastInsertId();
                    
                    // Log the registration
                    $logStmt = $pdo->prepare("
                        INSERT INTO activity_logs (user_id, username, action, status, details, ip_address) 
                        VALUES (?, ?, 'register', 'success', 'New account created', ?)
                    ");
                    $logStmt->execute([$newUserId, $username, $_SERVER['REMOTE_ADDR']]);
                    
                    $success = 'Registration successful! You can now login.';
                } else {
                    $error = 'Registration failed';
                }
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - IntelliFeed</title>
    <link rel="stylesheet" href="style.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .auth-container {
            max-width: 450px;
            width: 100%;
            background: rgba(255, 255, 255, 0.98);
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: fadeIn 0.5s ease;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .auth-title {
            text-align: center;
            margin-bottom: 10px;
            color: #333;
            font-size: 28px;
            font-weight: 700;
        }
        
        .auth-subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-size: 14px;
            font-family: inherit;
            transition: all 0.3s ease;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .password-input-wrapper {
            position: relative;
        }
        
        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            font-size: 18px;
            color: #999;
            padding: 5px;
        }
        
        .btn-primary {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        
        .divider {
            text-align: center;
            margin: 25px 0;
            position: relative;
            display: none; /* HIDDEN */
        }
        
        .divider::before,
        .divider::after {
            content: '';
            position: absolute;
            top: 50%;
            width: calc(50% - 40px);
            height: 1px;
            background: #e0e0e0;
        }
        
        .divider::before {
            left: 0;
        }
        
        .divider::after {
            right: 0;
        }
        
        .divider span {
            background: white;
            padding: 0 15px;
            color: #999;
            font-size: 12px;
        }
        
        .google-btn-wrapper {
            display: none; /* HIDDEN */
            justify-content: center;
            margin: 20px 0;
        }
        
        .error {
            background: #fee;
            color: #c62828;
            padding: 12px 16px;
            border-radius: 12px;
            margin-bottom: 20px;
            border-left: 4px solid #c62828;
            font-size: 14px;
        }
        
        .success {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 12px 16px;
            border-radius: 12px;
            margin-bottom: 20px;
            border-left: 4px solid #2e7d32;
            font-size: 14px;
        }
        
        .links {
            text-align: center;
            margin-top: 25px;
            font-size: 14px;
        }
        
        .links a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        
        .links a:hover {
            text-decoration: underline;
        }
        
        .password-hint {
            font-size: 11px;
            color: #999;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <h1 class="auth-title">📝 Create Account</h1>
        <p class="auth-subtitle">Join IntelliFeed smart pet care system</p>
        
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success">
                <?php echo htmlspecialchars($success); ?>
                <br><a href="login.php" style="color: #2e7d32; font-weight: 600;">Click here to login →</a>
            </div>
        <?php endif; ?>
        
        <!-- Google Sign-In - HIDDEN MUNA -->
        <!--
        <div class="google-btn-wrapper">
            <div id="g_id_onload"
                 data-client_id="YOUR_GOOGLE_CLIENT_ID.apps.googleusercontent.com"
                 data-context="signup"
                 data-ux_mode="popup"
                 data-callback="handleGoogleSignIn"
                 data-auto_prompt="false">
            </div>
            <div class="g_id_signin"
                 data-type="standard"
                 data-shape="rectangular"
                 data-theme="outline"
                 data-text="signup_with"
                 data-size="large"
                 data-logo_alignment="left">
            </div>
        </div>
        
        <div class="divider">
            <span>OR register with email</span>
        </div>
        -->
        
        <!-- Regular Registration Form -->
        <form method="POST" action="" id="registerForm">
            <div class="form-group">
                <label>Username *</label>
                <input type="text" name="username" required placeholder="Choose a username" 
                       value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label>Email *</label>
                <input type="email" name="email" required placeholder="your@email.com"
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label>Full Name (Optional)</label>
                <input type="text" name="full_name" placeholder="Enter your full name"
                       value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label>Password *</label>
                <div class="password-input-wrapper">
                    <input type="password" name="password" id="password" required placeholder="********">
                    <button type="button" class="toggle-password" onclick="togglePassword('password', this)">
                        👁️
                    </button>
                </div>
                <div class="password-hint">Minimum 6 characters</div>
            </div>
            
            <div class="form-group">
                <label>Confirm Password *</label>
                <div class="password-input-wrapper">
                    <input type="password" name="confirm_password" id="confirm_password" required placeholder="********">
                    <button type="button" class="toggle-password" onclick="togglePassword('confirm_password', this)">
                        👁️
                    </button>
                </div>
            </div>
            
            <button type="submit" class="btn-primary">Create Account</button>
        </form>
        
        <div class="links">
            <p>Already have an account? <a href="login.php">Login here</a></p>
        </div>
    </div>

    <script>
        // Toggle password visibility
        function togglePassword(inputId, button) {
            const input = document.getElementById(inputId);
            if (input.type === 'password') {
                input.type = 'text';
                button.textContent = '🔒';
            } else {
                input.type = 'password';
                button.textContent = '👁️';
            }
        }
    </script>
</body>
</html>