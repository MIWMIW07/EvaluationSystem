<?php
// debug_credentials.php
require_once 'vendor/autoload.php';

echo "<h1>🔐 Credentials Debug</h1>";

// Check environment variables
$googleCreds = getenv("GOOGLE_CREDENTIALS_JSON");
$sheetId = getenv("GOOGLE_SHEETS_ID");

echo "<h2>Environment Variables:</h2>";
echo "GOOGLE_SHEETS_ID: " . ($sheetId ? substr($sheetId, 0, 10) . "..." : "NOT SET") . "<br>";
echo "GOOGLE_CREDENTIALS_JSON: " . ($googleCreds ? "SET (" . strlen($googleCreds) . " characters)" : "NOT SET") . "<br>";

if (!$googleCreds) {
    echo "<p style='color:red'>❌ GOOGLE_CREDENTIALS_JSON is not set in environment!</p>";
    exit;
}

// Try to decode the JSON
try {
    $credsArray = json_decode($googleCreds, true);
    if (!$credsArray) {
        echo "<p style='color:red'>❌ GOOGLE_CREDENTIALS_JSON is not valid JSON!</p>";
        echo "<pre>" . htmlspecialchars(substr($googleCreds, 0, 200)) . "...</pre>";
        exit;
    }
    
    echo "<h2>✅ Credentials JSON is valid</h2>";
    echo "Client Email: " . ($credsArray['client_email'] ?? 'MISSING') . "<br>";
    echo "Project ID: " . ($credsArray['project_id'] ?? 'MISSING') . "<br>";
    
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Error decoding JSON: " . $e->getMessage() . "</p>";
    exit;
}

// Try to initialize Google Client
try {
    $client = new Google_Client();
    $client->setAuthConfig($credsArray);
    $client->addScope(Google_Service_Sheets::SPREADSHEETS_READONLY);
    
    echo "<h2>✅ Google Client initialized successfully</h2>";
    
    // Try to access the spreadsheet
    $service = new Google_Service_Sheets($client);
    
    // First, try to get spreadsheet metadata
    $spreadsheet = $service->spreadsheets->get($sheetId);
    $sheets = $spreadsheet->getSheets();
    
    echo "<h2>📊 Spreadsheet found! Sheets:</h2>";
    echo "<ul>";
    foreach ($sheets as $sheet) {
        $title = $sheet->getProperties()->getTitle();
        echo "<li>✅ $title</li>";
    }
    echo "</ul>";
    
    // Try to read Students sheet
    try {
        $range = 'Students!A1:G5';
        $response = $service->spreadsheets_values->get($sheetId, $range);
        $values = $response->getValues();
        echo "<h2>📝 Students sheet sample:</h2>";
        echo "<pre>";
        print_r($values);
        echo "</pre>";
    } catch (Exception $e) {
        echo "<p style='color:red'>❌ Error reading Students sheet: " . $e->getMessage() . "</p>";
    }
    
    // Try to read BOT_Teachers sheet
    try {
        $range = 'BOT_Teachers!A1:E5';
        $response = $service->spreadsheets_values->get($sheetId, $range);
        $values = $response->getValues();
        echo "<h2>📝 BOT_Teachers sheet sample:</h2>";
        echo "<pre>";
        print_r($values);
        echo "</pre>";
    } catch (Exception $e) {
        echo "<p style='color:red'>❌ Error reading BOT_Teachers sheet: " . $e->getMessage() . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Error initializing Google Client: " . $e->getMessage() . "</p>";
}
?>
