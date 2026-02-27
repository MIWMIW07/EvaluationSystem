<?php
// login.php - Handle login for all user types (Student, Admin, BOT)
session_start();

require_once 'includes/db_connection.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $userType = $_POST['user_type'] ?? 'student';
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password';
    } else {
        try {
            $manager = getDataManager();
            $user = $manager->authenticateUser($username, $password, $userType);
            
            if ($user) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $username;
                $_SESSION['full_name'] = $user['full_name'] ?? $username;
                $_SESSION['user_type'] = $user['type'];
                
                // If student, also store additional info if available
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
                    // Default fallback
                    header('Location: index.php');
                }
                exit;
            } else {
                $error = 'Invalid username or password';
                logActivity($username, $userType, 'Failed Login', 'Invalid credentials');
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
