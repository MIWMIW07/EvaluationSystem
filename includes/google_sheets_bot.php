<?php
// includes/google_sheets_bot.php - DEBUG VERSION
require_once __DIR__ . '/../vendor/autoload.php';

function getBotTeachersFromSheets() {
    echo "<div style='background:#f0f0f0; padding:10px; margin:10px 0;'>";
    echo "<h3>🔍 DEBUG: getBotTeachersFromSheets()</h3>";
    
    try {
        $client = new Google_Client();
        $client->setAuthConfig(__DIR__ . '/../credentials.json');
        $client->addScope(Google_Service_Sheets::SPREADSHEETS_READONLY);
        
        $service = new Google_Service_Sheets($client);
        
        // Get spreadsheet ID from environment variable
        $spreadsheetId = getenv("GOOGLE_SHEETS_ID") ?: ($_ENV["GOOGLE_SHEETS_ID"] ?? $_SERVER["GOOGLE_SHEETS_ID"] ?? null);
        echo "Spreadsheet ID: " . ($spreadsheetId ? substr($spreadsheetId, 0, 10) . "..." : "NOT FOUND") . "<br>";
        
        if (!$spreadsheetId) {
            echo "<span style='color:red'>❌ Google Sheets ID not found!</span><br>";
            return [];
        }
        
        // Try to read the sheet
        $range = 'BOT_Teachers!A2:E';
        echo "Range: $range<br>";
        
        $response = $service->spreadsheets_values->get($spreadsheetId, $range);
        $values = $response->getValues();
        
        echo "Raw data rows: " . count($values) . "<br>";
        
        if (empty($values)) {
            echo "<span style='color:orange'>⚠️ No data found in range</span><br>";
            
            // Try reading headers to see if sheet exists
            $headerRange = 'BOT_Teachers!A1:E1';
            $headerResponse = $service->spreadsheets_values->get($spreadsheetId, $headerRange);
            $headers = $headerResponse->getValues();
            echo "Headers: " . (!empty($headers) ? implode(", ", $headers[0]) : "No headers") . "<br>";
            
            return [];
        }
        
        $teachers = [];
        foreach ($values as $index => $row) {
            echo "Row " . ($index+2) . ": " . count($row) . " columns - " . ($row[0] ?? 'empty') . "<br>";
            
            if (count($row) >= 4 && !empty(trim($row[0] ?? ''))) {
                $teachers[] = [
                    'teacher_name' => trim($row[0] ?? ''),
                    'branch' => trim($row[1] ?? ''),
                    'department' => trim($row[2] ?? ''),
                    'area_of_specialization' => trim($row[3] ?? ''),
                    'subjects_handled' => isset($row[4]) ? trim($row[4]) : ''
                ];
            }
        }
        
        echo "<span style='color:green'>✅ Found " . count($teachers) . " teachers</span><br>";
        echo "</div>";
        return $teachers;
        
    } catch (Exception $e) {
        echo "<span style='color:red'>❌ ERROR: " . $e->getMessage() . "</span><br>";
        echo "</div>";
        error_log("Google Sheets BOT Error: " . $e->getMessage());
        return [];
    }
}

function syncBotTeachersToDatabase($pdo) {
    echo "<h3>🔄 Syncing teachers to database...</h3>";
    $teachers = getBotTeachersFromSheets();
    
    if (empty($teachers)) {
        echo "<span style='color:red'>❌ No teachers to sync</span><br>";
        return 0;
    }
    
    // First, deactivate all existing teachers
    $deactivateStmt = $pdo->prepare("UPDATE bot_teachers SET is_active = false");
    $deactivateStmt->execute();
    echo "Deactivated existing teachers<br>";
    
    // Insert or update teachers
    $insertStmt = $pdo->prepare("
        INSERT INTO bot_teachers (teacher_name, branch, department, area_of_specialization, subjects_handled, is_active)
        VALUES (?, ?, ?, ?, ?, true)
        ON CONFLICT (teacher_name, branch, department) 
        DO UPDATE SET 
            area_of_specialization = EXCLUDED.area_of_specialization,
            subjects_handled = EXCLUDED.subjects_handled,
            is_active = true,
            updated_at = CURRENT_TIMESTAMP
    ");
    
    $count = 0;
    foreach ($teachers as $teacher) {
        try {
            $insertStmt->execute([
                $teacher['teacher_name'],
                $teacher['branch'],
                $teacher['department'],
                $teacher['area_of_specialization'],
                $teacher['subjects_handled']
            ]);
            $count++;
            echo "✅ Inserted: {$teacher['teacher_name']}<br>";
        } catch (Exception $e) {
            echo "❌ Error inserting {$teacher['teacher_name']}: " . $e->getMessage() . "<br>";
        }
    }
    
    echo "<span style='color:green'>✅ Synced $count teachers</span><br>";
    return $count;
}
?>
