<?php
// test_sync.php
require_once 'includes/db_connection.php';
require_once 'includes/google_sheets_bot.php';
require_once 'includes/google_sheets_bot_users.php';

$pdo = getPDO();

echo "<h1>Manual Sync Test</h1>";

echo "<h2>Testing BOT Teachers:</h2>";
$teachers = getBotTeachersFromSheets();
echo "<pre>Found " . count($teachers) . " teachers:\n";
print_r($teachers);
echo "</pre>";

if (!empty($teachers)) {
    $count = syncBotTeachersToDatabase($pdo);
    echo "<p>✅ Synced $count teachers to database</p>";
}

echo "<h2>Testing BOT Users:</h2>";
$users = getBotUsersFromSheets();
echo "<pre>Found " . count($users) . " users:\n";
print_r($users);
echo "</pre>";

if (!empty($users)) {
    $count = syncBotUsersToDatabase($pdo);
    echo "<p>✅ Synced $count users to database</p>";
}
?>
