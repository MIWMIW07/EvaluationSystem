<?php
// test_bot_sync.php
require_once 'includes/db_connection.php';
require_once 'includes/google_sheets_bot.php';
require_once 'includes/google_sheets_bot_users.php';

$pdo = getPDO();

echo "<h1>🔍 BOT Sync Test</h1>";

// Test 1: Check if Google Sheets can read teachers
echo "<h2>Test 1: Reading from Google Sheets (Teachers)</h2>";
$teachers = getBotTeachersFromSheets();
echo "Found " . count($teachers) . " teachers in Google Sheets<br>";
if (!empty($teachers)) {
    echo "<pre>";
    print_r(array_slice($teachers, 0, 3));
    echo "</pre>";
}

// Test 2: Check if Google Sheets can read users
echo "<h2>Test 2: Reading from Google Sheets (Users)</h2>";
$users = getBotUsersFromSheets();
echo "Found " . count($users) . " users in Google Sheets<br>";
if (!empty($users)) {
    echo "<pre>";
    print_r(array_slice($users, 0, 3));
    echo "</pre>";
}

// Test 3: Check database tables
echo "<h2>Test 3: Database Status</h2>";
try {
    $teacherCount = $pdo->query("SELECT COUNT(*) FROM bot_teachers")->fetchColumn();
    echo "bot_teachers table: $teacherCount records<br>";
    
    $userCount = $pdo->query("SELECT COUNT(*) FROM users WHERE user_type = 'bot'")->fetchColumn();
    echo "users table (bot type): $userCount records<br>";
    
    $evalCount = $pdo->query("SELECT COUNT(*) FROM bot_evaluations")->fetchColumn();
    echo "bot_evaluations table: $evalCount records<br>";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}

// Test 4: Try a manual sync
echo "<h2>Test 4: Manual Sync</h2>";
if (!empty($teachers)) {
    $synced = syncBotTeachersToDatabase($pdo);
    echo "Synced $synced teachers to database<br>";
}
if (!empty($users)) {
    $synced = syncBotUsersToDatabase($pdo);
    echo "Synced $synced users to database<br>";
}
?>
