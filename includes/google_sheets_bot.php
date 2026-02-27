<?php
// includes/google_sheets_bot.php
require_once __DIR__ . '/../vendor/autoload.php';

function getBotTeachersFromSheets() {
    try {
        // Use the SAME credentials as your student evaluation
        $client = new Google_Client();
        $client->setAuthConfig(__DIR__ . '/../credentials.json'); // Path to your existing credentials
        $client->addScope(Google_Service_Sheets::SPREADSHEETS);
        
        $service = new Google_Service_Sheets($client);
        
        // Use the SAME spreadsheet ID as your student evaluation
        $spreadsheetId = 'YOUR_SPREADSHEET_ID_HERE'; // Same ID you're already using
        
        // Specify the NEW sheet name "BOT_Teachers"
        $range = 'BOT_Teachers!A2:C'; // Get all rows starting from row 2
        
        $response = $service->spreadsheets_values->get($spreadsheetId, $range);
        $values = $response->getValues();
        
        $teachers = [];
        if (!empty($values)) {
            foreach ($values as $row) {
                if (count($row) >= 3 && !empty($row[0])) {
                    $teachers[] = [
                        'teacher_name' => $row[0],
                        'branch' => $row[1] ?? '',
                        'area_of_specialization' => $row[2] ?? ''
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

// Optional: Function to sync Google Sheets data to database
function syncBotTeachersToDatabase($pdo) {
    $teachers = getBotTeachersFromSheets();
    
    if (empty($teachers)) {
        return 0;
    }
    
    // First, deactivate all existing teachers
    $deactivateStmt = $pdo->prepare("UPDATE bot_teachers SET is_active = false");
    $deactivateStmt->execute();
    
    // Insert or update teachers
    $insertStmt = $pdo->prepare("
        INSERT INTO bot_teachers (teacher_name, branch, area_of_specialization, is_active)
        VALUES (?, ?, ?, true)
        ON CONFLICT (teacher_name, branch) 
        DO UPDATE SET 
            area_of_specialization = EXCLUDED.area_of_specialization,
            is_active = true,
            updated_at = CURRENT_TIMESTAMP
    ");
    
    $count = 0;
    foreach ($teachers as $teacher) {
        $insertStmt->execute([
            $teacher['teacher_name'],
            $teacher['branch'],
            $teacher['area_of_specialization']
        ]);
        $count++;
    }
    
    return $count;
}
?>
