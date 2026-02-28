<?php
// includes/google_sheets_bot.php - FINAL CLEAN VERSION
require_once __DIR__ . '/../vendor/autoload.php';

function getBotTeachersFromSheets() {
    try {
        $client = new Google_Client();
        $client->setAuthConfig(__DIR__ . '/../credentials.json');
        $client->addScope(Google_Service_Sheets::SPREADSHEETS_READONLY);
        
        $service = new Google_Service_Sheets($client);
        
        // Get spreadsheet ID from environment variable
        $spreadsheetId = getenv("GOOGLE_SHEETS_ID") ?: ($_ENV["GOOGLE_SHEETS_ID"] ?? $_SERVER["GOOGLE_SHEETS_ID"] ?? null);
        
        if (!$spreadsheetId) {
            error_log("Google Sheets ID not set in environment variables");
            return [];
        }
        
        // Get ALL 5 columns: A (teacher_name), B (branch), C (department), D (area_of_specialization), E (subjects_handled)
        $range = 'BOT_Teachers!A2:E';
        $response = $service->spreadsheets_values->get($spreadsheetId, $range);
        $values = $response->getValues();
        
        $teachers = [];
        if (!empty($values)) {
            foreach ($values as $row) {
                // Make sure we have at least 4 columns with data
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
        }
        
        return $teachers;
        
    } catch (Exception $e) {
        error_log("Google Sheets BOT Error: " . $e->getMessage());
        return [];
    }
}

function syncBotTeachersToDatabase($pdo) {
    $teachers = getBotTeachersFromSheets();
    
    if (empty($teachers)) {
        error_log("syncBotTeachersToDatabase: No teachers found from Google Sheets");
        return 0;
    }
    
    // First, deactivate all existing teachers
    $deactivateStmt = $pdo->prepare("UPDATE bot_teachers SET is_active = false");
    $deactivateStmt->execute();
    
    // Insert or update teachers with all 5 columns
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
        } catch (Exception $e) {
            error_log("Error inserting teacher {$teacher['teacher_name']}: " . $e->getMessage());
        }
    }
    
    return $count;
}
?>
