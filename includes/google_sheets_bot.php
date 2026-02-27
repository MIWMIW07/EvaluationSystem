<?php
// includes/google_sheets_bot.php - UPDATED for 5-column structure
require_once __DIR__ . '/../vendor/autoload.php';

function getBotTeachersFromSheets() {
    try {
        $client = new Google_Client();
        $client->setAuthConfig(__DIR__ . '/../credentials.json');
        $client->addScope(Google_Service_Sheets::SPREADSHEETS);
        
        $service = new Google_Service_Sheets($client);
        
        // Use the SAME spreadsheet ID as your student evaluation
        $spreadsheetId = 'YOUR_SPREADSHEET_ID_HERE'; // Replace with your actual ID
        
        // Get ALL 5 columns: A (teacher_name), B (branch), C (department), D (area_of_specialization), E (subjects_handled)
        $range = 'BOT_Teachers!A2:E'; 
        
        $response = $service->spreadsheets_values->get($spreadsheetId, $range);
        $values = $response->getValues();
        
        $teachers = [];
        if (!empty($values)) {
            foreach ($values as $row) {
                if (count($row) >= 4 && !empty($row[0])) { // At least teacher_name exists
                    $teachers[] = [
                        'teacher_name' => $row[0] ?? '',
                        'branch' => $row[1] ?? '',
                        'department' => $row[2] ?? '',
                        'area_of_specialization' => $row[3] ?? '',
                        'subjects_handled' => $row[4] ?? ''
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
        return 0;
    }
    
    // First, deactivate all existing teachers
    $deactivateStmt = $pdo->prepare("UPDATE bot_teachers SET is_active = false");
    $deactivateStmt->execute();
    
    // Insert or update teachers - UPDATED to include all 5 columns
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
        $insertStmt->execute([
            $teacher['teacher_name'],
            $teacher['branch'],
            $teacher['department'],
            $teacher['area_of_specialization'],
            $teacher['subjects_handled']
        ]);
        $count++;
    }
    
    return $count;
}
?>
