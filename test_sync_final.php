<?php
// test_sync_final.php - DEBUG VERSION
require_once 'includes/db_connection.php';
require_once 'includes/google_sheets_bot.php';
require_once 'includes/google_sheets_bot_users.php';

$pdo = getPDO();

echo "<h1>🔍 DEBUG SYNC TEST</h1>";

echo "<h2>Testing BOT Teachers Sync</h2>";
$teachers = getBotTeachersFromSheets();
echo "<p>Found " . count($teachers) . " teachers from Google Sheets</p>";

if (!empty($teachers)) {
    $count = syncBotTeachersToDatabase($pdo);
    echo "<p>✅ Synced $count teachers to database</p>";
    
    // Verify in database
    $result = $pdo->query("SELECT COUNT(*) FROM bot_teachers WHERE is_active = true");
    $dbCount = $result->fetchColumn();
    echo "<p>📊 Database now has $dbCount active teachers</p>";
}

echo "<h2>Testing BOT Users Sync</h2>";
$users = getBotUsersFromSheets();
echo "<p>Found " . count($users) . " users from Google Sheets</p>";

if (!empty($users)) {
    $count = syncBotUsersToDatabase($pdo);
    echo "<p>✅ Synced $count users to database</p>";
    
    // Verify in database
    $result = $pdo->query("SELECT COUNT(*) FROM users WHERE user_type = 'bot' AND is_active = true");
    $dbCount = $result->fetchColumn();
    echo "<p>📊 Database now has $dbCount active BOT users</p>";
}
?>
