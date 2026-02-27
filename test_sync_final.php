<?php
// test_sync_final.php
require_once 'includes/db_connection.php';
require_once 'includes/google_sheets_bot.php';
require_once 'includes/google_sheets_bot_users.php';

$pdo = getPDO();

echo "<h1>Testing BOT Teachers Sync</h1>";
$teachers = getBotTeachersFromSheets();
echo "Found " . count($teachers) . " teachers from Google Sheets<br>";

if (!empty($teachers)) {
    $count = syncBotTeachersToDatabase($pdo);
    echo "✅ Synced $count teachers to database<br>";
    
    // Verify in database
    $result = $pdo->query("SELECT COUNT(*) FROM bot_teachers WHERE is_active = true");
    $dbCount = $result->fetchColumn();
    echo "📊 Database now has $dbCount active teachers<br>";
}

echo "<h1>Testing BOT Users Sync</h1>";
$users = getBotUsersFromSheets();
echo "Found " . count($users) . " users from Google Sheets<br>";

if (!empty($users)) {
    $count = syncBotUsersToDatabase($pdo);
    echo "✅ Synced $count users to database<br>";
    
    // Verify in database
    $result = $pdo->query("SELECT COUNT(*) FROM users WHERE user_type = 'bot' AND is_active = true");
    $dbCount = $result->fetchColumn();
    echo "📊 Database now has $dbCount active BOT users<br>";
}
?>
