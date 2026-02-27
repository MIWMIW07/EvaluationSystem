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

if (isset($_POST['sync'])) {
    $count = syncBotUsersToDatabase($pdo);
    $message = "✅ Synced $count BOT users from Google Sheets!";
}

// Get current BOT users
$bots = $pdo->query("SELECT username, full_name, last_login, created_at FROM users WHERE user_type = 'bot' ORDER BY full_name")->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Sync BOT Users</title>
    <style>
        body { font-family: Arial; background: #f5f5f5; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        .success { background: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background: #800000; color: white; padding: 10px; text-align: left; }
        td { padding: 10px; border-bottom: 1px solid #ddd; }
        .btn { background: #800000; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
        .btn:hover { background: #660000; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Sync BOT Users from Google Sheets</h1>
        
        <?php if ($message): ?>
            <div class="success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <button type="submit" name="sync" class="btn">🔄 Sync BOT Users Now</button>
            <a href="admin.php" style="margin-left: 10px;">← Back to Admin</a>
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
                    <td><?php echo htmlspecialchars($bot['username']); ?></td>
                    <td><?php echo $bot['last_login'] ? date('Y-m-d H:i', strtotime($bot['last_login'])) : 'Never'; ?></td>
                    <td><?php echo date('Y-m-d', strtotime($bot['created_at'])); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
