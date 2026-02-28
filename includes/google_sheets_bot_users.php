<?php
// includes/google_sheets_bot_users.php - FINAL CLEAN VERSION
require_once __DIR__ . '/../vendor/autoload.php';

function getBotUsersFromSheets() {
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
        
        // Get from the "BOT_Users" sheet
        $range = 'BOT_Users!A2:D';
        $response = $service->spreadsheets_values->get($spreadsheetId, $range);
        $values = $response->getValues();
        
        $users = [];
        if (!empty($values)) {
            foreach ($values as $row) {
                if (count($row) >= 4 && !empty(trim($row[2] ?? ''))) { // username is required
                    $users[] = [
                        'bot_id' => trim($row[0] ?? ''),
                        'full_name' => trim($row[1] ?? ''),
                        'username' => trim($row[2]),
                        'password' => trim($row[3] ?? '')
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
        error_log("syncBotUsersToDatabase: No users found from Google Sheets");
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
        try {
            // Hash the password before storing
            $hashedPassword = password_hash($user['password'], PASSWORD_DEFAULT);
            
            $insertStmt->execute([
                $user['username'],
                $hashedPassword,
                $user['full_name']
            ]);
            $count++;
        } catch (Exception $e) {
            error_log("Error inserting user {$user['username']}: " . $e->getMessage());
        }
    }
    
    return $count;
}
?>
