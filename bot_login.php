<?php
// bot_login.php - Login page for Board of Trustees
session_start();

// If already logged in as bot, redirect to dashboard
if (isset($_SESSION['user_id']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'bot') {
    header('Location: bot_dashboard.php');
    exit;
}

require_once 'includes/db_connection.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password';
    } else {
        try {
            $pdo = getPDO();
            
            // Check if users table exists, if not create it with bot type
            $pdo->exec("
                DO $$ 
                BEGIN 
                    IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'user_type_enum') THEN
                        CREATE TYPE user_type_enum AS ENUM ('student', 'teacher', 'admin', 'bot');
                    END IF;
                END $$;
            ");
            
            // Create users table if not exists with bot type
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS users (
                    id SERIAL PRIMARY KEY,
                    username VARCHAR(50) UNIQUE NOT NULL,
                    password VARCHAR(255) NOT NULL,
                    full_name VARCHAR(100) NOT NULL,
                    user_type user_type_enum NOT NULL,
                    is_active BOOLEAN DEFAULT true,
                    last_login TIMESTAMP,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");
            
            // Check if bot user exists, if not create default bot account
            $checkBot = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = 'bot_admin'");
            $checkBot->execute();
            if ($checkBot->fetchColumn() == 0) {
                $defaultPassword = password_hash('bot123', PASSWORD_DEFAULT);
                $insertBot = $pdo->prepare("
                    INSERT INTO users (username, password, full_name, user_type) 
                    VALUES ('bot_admin', ?, 'BOT Administrator', 'bot')
                ");
                $insertBot->execute([$defaultPassword]);
            }
            
            // Verify login
            $stmt = $pdo->prepare("
                SELECT id, username, password, full_name, user_type, is_active 
                FROM users 
                WHERE username = ? AND user_type = 'bot' AND is_active = true
            ");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                // Update last login
                $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $updateStmt->execute([$user['id']]);
                
                // Set session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['user_type'] = $user['user_type'];
                
                // Log activity
                logActivity($user['username'], 'bot', 'Login', 'BOT logged in successfully');
                
                header('Location: bot_dashboard.php');
                exit;
            } else {
                $error = 'Invalid username or password';
                logActivity($username, 'bot', 'Failed Login', 'Invalid credentials');
            }
            
        } catch (Exception $e) {
            $error = 'Database error: ' . $e->getMessage();
            error_log("BOT Login Error: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BOT Login - Board of Trustees</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #800000 0%, #A52A2A 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .login-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 400px;
            padding: 40px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .header h1 {
            color: #800000;
            font-size: 24px;
            margin-bottom: 10px;
        }

        .header p {
            color: #666;
            font-size: 14px;
        }

        .logo {
            width: 80px;
            height: 80px;
            background: #800000;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: white;
            font-size: 40px;
            font-weight: bold;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: 500;
        }

        input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        input:focus {
            outline: none;
            border-color: #800000;
        }

        .btn-login {
            width: 100%;
            padding: 14px;
            background: #800000;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s;
        }

        .btn-login:hover {
            background: #660000;
        }

        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #dc3545;
        }

        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #28a745;
        }

        .info-box {
            background: #f8f9fa;
            border-left: 4px solid #800000;
            padding: 15px;
            margin-top: 20px;
            border-radius: 5px;
            font-size: 14px;
        }

        .info-box p {
            margin: 5px 0;
            color: #666;
        }

        .back-link {
            text-align: center;
            margin-top: 20px;
        }

        .back-link a {
            color: #800000;
            text-decoration: none;
            font-size: 14px;
        }

        .back-link a:hover {
            text-decoration: underline;
        }

        .footer {
            text-align: center;
            margin-top: 30px;
            color: #999;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="header">
            <div class="logo">BOT</div>
            <h1>Board of Trustees Login</h1>
            <p>Classroom Observation Evaluation System</p>
        </div>

        <?php if ($error): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" placeholder="Enter your username" required autofocus>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" required>
            </div>

            <button type="submit" class="btn-login">Login to Dashboard</button>
        </form>

        <div class="info-box">
            <p><strong>Default Credentials:</strong></p>
            <p>Username: <code>bot_admin</code></p>
            <p>Password: <code>bot123</code></p>
            <p style="margin-top: 10px; color: #800000;"><strong>Note:</strong> Please change your password after first login.</p>
        </div>

        <div class="back-link">
            <a href="index.php">← Back to Main Login</a>
        </div>

        <div class="footer">
            &copy; <?php echo date('Y'); ?> PHILTECH GMA. All rights reserved.
        </div>
    </div>
</body>
</html>
