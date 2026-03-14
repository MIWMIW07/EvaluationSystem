<?php
// login.php - Handle login for all user types (automatic detection)
session_start();

require_once 'includes/db_connection.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password';
    } else {
        try {
            $manager = getDataManager();
            
            // Try to authenticate as student first
            $user = $manager->authenticateUser($username, $password, 'student');
            
            // If not student, try admin (special case)
            if (!$user && $username === 'GUIDANCE' && $password === 'guidanceservice2025') {
                $user = ['id' => 'admin', 'type' => 'admin', 'full_name' => 'Administrator'];
            }
            
            // If not admin, try BOT
            if (!$user) {
                $user = $manager->authenticateUser($username, $password, 'bot');
            }
            
            if ($user) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $username;
                $_SESSION['full_name'] = $user['full_name'] ?? $username;
                $_SESSION['user_type'] = $user['type'];
                
                // If student, also store additional info
                if ($user['type'] === 'student' && isset($user['student_data'])) {
                    $_SESSION['student_id'] = $user['student_data']['student_id'] ?? '';
                    $_SESSION['section'] = $user['student_data']['section'] ?? '';
                    $_SESSION['program'] = $user['student_data']['program'] ?? '';
                }
                
                // Log the login
                logActivity($username, $user['type'], 'Login', 'User logged in successfully');
                
                // Redirect based on user type
                if ($user['type'] === 'admin') {
                    header('Location: admin.php');
                } elseif ($user['type'] === 'student') {
                    header('Location: student_dashboard.php');
                } elseif ($user['type'] === 'bot') {
                    header('Location: bot_dashboard.php');
                } else {
                    header('Location: index.php');
                }
                exit;
            } else {
                $error = 'Invalid username or password';
                logActivity($username, 'unknown', 'Failed Login', 'Invalid credentials');
            }
        } catch (Exception $e) {
            $error = 'Login error: ' . $e->getMessage();
            error_log("Login Error: " . $e->getMessage());
        }
    }
}

// If there's an error, redirect back with error message
if ($error) {
    header('Location: index.php?error=' . urlencode($error));
    exit;
}

// If no POST data, redirect to login page
header('Location: index.php');
exit;
?>
