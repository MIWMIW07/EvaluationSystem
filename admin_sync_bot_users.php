<?php
// admin_sync_bot_users.php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: login.php');
    exit;
}

require_once 'includes/db_connection.php';
require_once 'includes/google_sheets_bot_users.php';

$pdo = getPDO();
$message = '';
$error = '';

if (isset($_POST['sync'])) {
    try {
        $count = syncBotUsersToDatabase($pdo);
        $message = "✅ Successfully synced $count BOT users from Google Sheets!";
    } catch (Exception $e) {
        $error = "❌ Error syncing users: " . $e->getMessage();
    }
}

// Get current BOT users
$bots = $pdo->query("SELECT username, full_name, last_login, created_at FROM users WHERE user_type = 'bot' ORDER BY full_name")->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Sync BOT Users</title>
    <style>
        * { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background: linear-gradient(135deg, #800000 0%, #A52A2A 100%); padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        h1 { color: #800000; border-bottom: 2px solid #800000; padding-bottom: 10px; }
        .success { background: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin-bottom: 20px; }
        .error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background: #800000; color: white; padding: 10px; text-align: left; }
        td { padding: 10px; border-bottom: 1px solid #ddd; }
        .btn { background: #800000; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
        .btn:hover { background: #660000; }
        .back-link { color: #800000; text-decoration: none; margin-left: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Sync BOT Users from Google Sheets</h1>
        
        <?php if ($message): ?>
            <div class="success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <button type="submit" name="sync" class="btn">🔄 Sync BOT Users Now</button>
            <a href="admin.php" class="back-link">← Back to Admin</a>
        </form>
        
        <h2>Current BOT Users in System</h2>
        <table>
            <thead>
                <tr>
                    <th>Full Name</th>
                    <th>Username</th>
                    <th>Last Login</th>
                    <th>Created</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bots as $bot): ?>
                <tr>
                    <td><?php echo htmlspecialchars($bot['full_name']); ?></td>
                    <td><code><?php echo htmlspecialchars($bot['username']); ?></code></td>
                    <td><?php echo $bot['last_login'] ? date('Y-m-d H:i', strtotime($bot['last_login'])) : 'Never'; ?></td>
                    <td><?php echo date('Y-m-d', strtotime($bot['created_at'])); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
