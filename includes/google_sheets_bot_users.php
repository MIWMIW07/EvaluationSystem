<?php
// includes/google_sheets_bot_users.php
require_once __DIR__ . '/../vendor/autoload.php';

function getBotUsersFromSheets() {
    try {
        $client = new Google_Client();
        $client->setAuthConfig(__DIR__ . '/../credentials.json');
        $client->addScope(Google_Service_Sheets::SPREADSHEETS);
        
        $service = new Google_Service_Sheets($client);
        
        // Use the SAME spreadsheet ID as your student evaluation
        $spreadsheetId = 'YOUR_SPREADSHEET_ID_HERE';
        
        // Get from the new "BOT_Users" sheet
        $range = 'BOT_Users!A2:D'; // Get all rows starting from row 2
        $response = $service->spreadsheets_values->get($spreadsheetId, $range);
        $values = $response->getValues();
        
        $users = [];
        if (!empty($values)) {
            foreach ($values as $row) {
                if (count($row) >= 4 && !empty($row[2])) { // username is required
                    $users[] = [
                        'bot_id' => $row[0] ?? '',
                        'full_name' => $row[1] ?? '',
                        'username' => $row[2],
                        'password' => $row[3] ?? ''
                    ];
                }
            }
        }
        
        return $users;
        
    } catch (Exception $e) {
        error_log("Google Sheets BOT Users Error: " . $e->getMessage());
        return [];
    }
}

function syncBotUsersToDatabase($pdo) {
    $users = getBotUsersFromSheets();
    
    if (empty($users)) {
        return 0;
    }
    
    // First, deactivate all existing bot users
    $deactivateStmt = $pdo->prepare("UPDATE users SET is_active = false WHERE user_type = 'bot'");
    $deactivateStmt->execute();
    
    // Insert or update bot users
    $insertStmt = $pdo->prepare("
        INSERT INTO users (username, password, full_name, user_type, is_active)
        VALUES (?, ?, ?, 'bot', true)
        ON CONFLICT (username) 
        DO UPDATE SET 
            full_name = EXCLUDED.full_name,
            password = EXCLUDED.password,
            is_active = true,
            updated_at = CURRENT_TIMESTAMP
    ");
    
    $count = 0;
    foreach ($users as $user) {
        // Hash the password before storing
        $hashedPassword = password_hash($user['password'], PASSWORD_DEFAULT);
        
        $insertStmt->execute([
            $user['username'],
            $hashedPassword,
            $user['full_name']
        ]);
        $count++;
    }
    
    return $count;
}
?>
