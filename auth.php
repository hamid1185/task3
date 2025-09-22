<?php
// auth.php - Authentication API endpoints

require_once 'config.php';
require_once 'database.php';

session_start();
corsHeaders();

$method = $_SERVER['REQUEST_METHOD'];
$path = $_GET['action'] ?? '';

switch ($method . ' ' . $path) {
    case 'POST register':
        handleRegister();
        break;
    
    case 'POST login':
        handleLogin();
        break;
    
    case 'POST logout':
        handleLogout();
        break;
    
    case 'GET me':
        handleGetCurrentUser();
        break;
    
    default:
        jsonResponse(['error' => 'Endpoint not found'], 404);
}

function handleRegister() {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate input
    $errors = [];
    if (empty($input['username'])) $errors[] = 'Username is required';
    if (empty($input['email']) || !filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Valid email is required';
    }
    if (empty($input['password']) || strlen($input['password']) < 6) {
        $errors[] = 'Password must be at least 6 characters';
    }
    if ($input['password'] !== $input['confirm_password']) {
        $errors[] = 'Passwords do not match';
    }
    
    if (!empty($errors)) {
        jsonResponse(['error' => implode(', ', $errors)], 400);
    }
    
    $result = Database::createUser($input);
    
    if (isset($result['error'])) {
        jsonResponse(['error' => $result['error']], 400);
    }
    
    // Auto-login after registration
    $_SESSION['user_id'] = $result['id'];
    $_SESSION['username'] = $result['username'];
    $_SESSION['role'] = $result['role'];
    
    jsonResponse([
        'message' => 'Registration successful',
        'user' => $result
    ]);
}

function handleLogin() {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input['username']) || empty($input['password'])) {
        jsonResponse(['error' => 'Username and password are required'], 400);
    }
    
    $user = Database::getUserByCredentials($input['username'], $input['password']);
    
    if (!$user) {
        jsonResponse(['error' => 'Invalid credentials'], 401);
    }
    
    if ($user['status'] !== 'active') {
        jsonResponse(['error' => 'Account is not active'], 403);
    }
    
    // Set session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];
    
    jsonResponse([
        'message' => 'Login successful',
        'user' => $user
    ]);
}

function handleLogout() {
    session_destroy();
    jsonResponse(['message' => 'Logout successful']);
}

function handleGetCurrentUser() {
    if (!isset($_SESSION['user_id'])) {
        jsonResponse(['error' => 'Not authenticated'], 401);
    }
    
    $user = Database::getUserById($_SESSION['user_id']);
    
    if (!$user) {
        session_destroy();
        jsonResponse(['error' => 'User not found'], 404);
    }
    
    jsonResponse(['user' => $user]);
}

// Helper function to check if user is logged in
function requireAuth() {
    if (!isset($_SESSION['user_id'])) {
        jsonResponse(['error' => 'Authentication required'], 401);
    }
    return $_SESSION['user_id'];
}

// Helper function to check if user is admin
function requireAdmin() {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        jsonResponse(['error' => 'Admin access required'], 403);
    }
    return $_SESSION['user_id'];
}
?>