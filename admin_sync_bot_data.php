<?php
// admin_sync_bot_data.php - Sync BOT Teachers from Google Sheets
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: login.php');
    exit;
}

require_once 'includes/db_connection.php';
require_once 'includes/google_sheets_bot.php';

$pdo = getPDO();
$message = '';
$error = '';

if (isset($_POST['sync'])) {
    try {
        $count = syncBotTeachersToDatabase($pdo);
        $message = "✅ Successfully synced $count BOT teachers from Google Sheets!";
        logActivity($_SESSION['username'], 'admin', 'Sync BOT Teachers', "Synced $count teachers");
    } catch (Exception $e) {
        $error = "❌ Error syncing teachers: " . $e->getMessage();
    }
}

// Get current BOT teachers
$teachers = $pdo->query("SELECT * FROM bot_teachers WHERE is_active = true ORDER BY teacher_name")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sync BOT Teachers</title>
    <style>
        * { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background: linear-gradient(135deg, #800000 0%, #A52A2A 100%); padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; }
        h1 { color: #800000; border-bottom: 2px solid #800000; padding-bottom: 10px; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .btn { padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; }
        .btn-primary { background: #800000; color: white; }
        .btn-primary:hover { background: #660000; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background: #800000; color: white; padding: 12px; text-align: left; }
        td { padding: 10px; border-bottom: 1px solid #ddd; }
        .stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin: 20px 0; }
        .stat-card { background: #f8f9fa; padding: 15px; border-radius: 8px; text-align: center; border-left: 4px solid #800000; }
        .stat-number { font-size: 2em; font-weight: bold; color: #800000; }
        .back-link { color: #800000; text-decoration: none; margin-left: 15px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔄 Sync BOT Teachers from Google Sheets</h1>
        
        <?php if ($message): ?>
            <div class="success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="stats">
            <div class="stat-card">
                <div class="stat-number"><?php echo count($teachers); ?></div>
                <div>Teachers in Database</div>
            </div>
        </div>
        
        <form method="POST">
            <button type="submit" name="sync" class="btn btn-primary">
                <i class="fas fa-sync-alt"></i> Sync Teachers Now
            </button>
            <a href="admin.php" class="back-link">← Back to Admin</a>
        </form>
        
        <h2 style="margin-top: 30px;">Current BOT Teachers</h2>
        <table>
            <thead>
                <tr>
                    <th>Teacher Name</th>
                    <th>Branch</th>
                    <th>Department</th>
                    <th>Specialization</th>
                    <th>Subjects Handled</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($teachers as $t): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($t['teacher_name']); ?></strong></td>
                    <td><?php echo htmlspecialchars($t['branch']); ?></td>
                    <td><?php echo htmlspecialchars($t['department']); ?></td>
                    <td><?php echo htmlspecialchars($t['area_of_specialization']); ?></td>
                    <td><?php echo htmlspecialchars($t['subjects_handled'] ?: 'N/A'); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
