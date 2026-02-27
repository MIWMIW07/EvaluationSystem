<?php
// debug_sheets.php
require_once 'vendor/autoload.php';
require_once 'includes/db_connection.php';

echo "<h1>Google Sheets Debug</h1>";

try {
    $googleCredentials = getenv('GOOGLE_CREDENTIALS_JSON');
    $spreadsheetId = getenv('GOOGLE_SHEETS_ID');
    
    echo "<p><strong>Spreadsheet ID:</strong> " . substr($spreadsheetId, 0, 10) . "...</p>";
    
    // Initialize client
    $client = new Google_Client();
    $client->setAuthConfig(json_decode($googleCredentials, true));
    $client->addScope(Google_Service_Sheets::SPREADSHEETS_READONLY);
    
    $service = new Google_Service_Sheets($client);
    
    // Get spreadsheet metadata (this will show all sheets/tabs)
    $spreadsheet = $service->spreadsheets->get($spreadsheetId);
    $sheets = $spreadsheet->getSheets();
    
    echo "<h2>Sheets Found:</h2>";
    echo "<ul>";
    foreach ($sheets as $sheet) {
        $title = $sheet->getProperties()->getTitle();
        echo "<li>📄 <strong>$title</strong></li>";
        
        // Try to read first few rows of each sheet
        try {
            $range = "$title!A1:E5";
            $response = $service->spreadsheets_values->get($spreadsheetId, $range);
            $values = $response->getValues();
            
            if (!empty($values)) {
                echo "<ul>";
                echo "<li>✅ Has data - First row: " . implode(" | ", array_slice($values[0] ?? [], 0, 3)) . "</li>";
                echo "<li>Total rows found: " . count($values) . "</li>";
                echo "</ul>";
            } else {
                echo "<ul><li>⚠️ Sheet is empty</li></ul>";
            }
        } catch (Exception $e) {
            echo "<ul><li>❌ Error reading: " . $e->getMessage() . "</li></ul>";
        }
    }
    echo "</ul>";
    
    // Specifically check BOT_Teachers
    echo "<h2>Checking BOT_Teachers specifically:</h2>";
    try {
        $range = 'BOT_Teachers!A2:E';
        $response = $service->spreadsheets_values->get($spreadsheetId, $range);
        $values = $response->getValues();
        
        echo "<p>Found " . count($values) . " teacher rows</p>";
        
        if (!empty($values)) {
            echo "<pre>";
            print_r(array_slice($values, 0, 3));
            echo "</pre>";
        }
    } catch (Exception $e) {
        echo "<p>❌ Error: " . $e->getMessage() . "</p>";
    }
    
    // Specifically check BOT_Users
    echo "<h2>Checking BOT_Users specifically:</h2>";
    try {
        $range = 'BOT_Users!A2:D';
        $response = $service->spreadsheets_values->get($spreadsheetId, $range);
        $values = $response->getValues();
        
        echo "<p>Found " . count($values) . " user rows</p>";
        
        if (!empty($values)) {
            echo "<pre>";
            print_r(array_slice($values, 0, 3));
            echo "</pre>";
        }
    } catch (Exception $e) {
        echo "<p>❌ Error: " . $e->getMessage() . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
?>
