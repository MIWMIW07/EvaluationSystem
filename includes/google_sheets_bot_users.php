<?php
// includes/google_sheets_bot_users.php
require_once __DIR__ . '/../vendor/autoload.php';

function getBotUsersFromSheets() {
    try {
        $client = new Google_Client();
        $client->setAuthConfig(__DIR__ . '/../credentials.json');
        $client->addScope(Google_Service_Sheets::SPREADSHEETS_READONLY); // Changed to READONLY
        
        $service = new Google_Service_Sheets($client);
        
        $spreadsheetId = getenv("GOOGLE_SHEETS_ID") ?: ($_ENV["GOOGLE_SHEETS_ID"] ?? $_SERVER["GOOGLE_SHEETS_ID"] ?? null);
        
        if (!$spreadsheetId) {
            error_log("Google Sheets ID not set");
            return [];
        }
        
        $range = 'BOT_Users!A2:D';
        $response = $service->spreadsheets_values->get($spreadsheetId, $range);
        $values = $response->getValues();
        
        $users = [];
        if (!empty($values)) {
            foreach ($values as $row) {
                if (count($row) >= 4 && !empty(trim($row[2] ?? ''))) {
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
        return 0;
    }
    
    // First, deactivate all existing bot users
    $deactivateStmt = $pdo->prepare("UPDATE users SET is_active = false WHERE user_type = 'bot'");
    $deactivateStmt->execute();
    
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
