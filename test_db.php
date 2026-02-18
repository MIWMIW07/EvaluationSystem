<?php
// test_db.php - Test database connection
require_once 'includes/db_connection.php';

echo "<h2>Database Connection Test</h2>";

try {
    // Test 1: Regular connection
    echo "<h3>Test 1: Regular Connection</h3>";
    $result = testDatabaseConnection();
    if ($result['success']) {
        echo "✅ Database connected successfully!<br>";
        echo "Current time: " . $result['current_time'] . "<br>";
        echo "PostgreSQL: " . $result['postgres_version'] . "<br>";
    } else {
        echo "❌ Error: " . $result['error'] . "<br>";
    }
    
    // Test 2: Check environment
    echo "<h3>Test 2: Environment Check</h3>";
    $dbUrl = getenv("DATABASE_URL");
    echo "DATABASE_URL: " . ($dbUrl ? substr($dbUrl, 0, 50) . "..." : "NOT SET") . "<br>";
    
    if ($dbUrl) {
        $parsed = parse_url($dbUrl);
        echo "<pre>Parsed URL: " . print_r($parsed, true) . "</pre>";
    }
    
} catch (Exception $e) {
    echo "❌ Fatal error: " . $e->getMessage() . "<br>";
}
?>
