<?php
/**
 * Authentication API endpoints for Bella Vista Restaurant
 */

// Define access constant
define('BELLA_VISTA_ACCESS', true);

// Include required files
require_once 'config.php';
require_once 'database.php';

// Start session
session_start();

// Set content type to JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Get request method and action
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Initialize database
try {
    $db = new Database();
} catch (Exception $e) {
    sendJsonResponse(['success' => false, 'message' => 'Database connection failed'], 500);
}

// Rate limiting check
$clientIP = getClientIP();
if (!checkRateLimit($clientIP)) {
    sendJsonResponse(['success' => false, 'message' => 'Too many requests. Please try again later.'], 429);
}

switch ($action) {
    case 'register':
        handleRegister($db);
        break;
    
    case 'login':
        handleLogin($db);
        break;
    
    case 'logout':
        handleLogout($db);
        break;
    
    case 'profile':
        handleProfile($db);
        break;
        
    case 'change-password':
        handleChangePassword($db);
        break;
    
    default:
        sendJsonResponse(['success' => false, 'message' => 'Invalid action'], 400);
}

function handleRegister($db) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendJsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
    }
    
    // Get POST data
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    $requiredFields = ['username', 'email', 'password', 'firstName', 'lastName'];
    foreach ($requiredFields as $field) {
        if (empty($input[$field])) {
            sendJsonResponse(['success' => false, 'message' => "Field '$field' is required"], 400);
        }
    }
    
    // Sanitize input
    $username = sanitizeInput($input['username']);
    $email = sanitizeInput($input['email']);
    $password = $input['password'];
    $firstName = sanitizeInput($input['firstName']);
    $lastName = sanitizeInput($input['lastName']);
    $phone = isset($input['phone']) ? sanitizeInput($input['phone']) : null;
    
    // Validate input
    if (strlen($username) < 3 || strlen($username) > 50) {
        sendJsonResponse(['success' => false, 'message' => 'Username must be between 3 and 50 characters'], 400);
    }
    
    if (!isValidEmail($email)) {
        sendJsonResponse(['success' => false, 'message' => 'Invalid email address'], 400);
    }
    
    if (strlen($password) < 6) {
        sendJsonResponse(['success' => false, 'message' => 'Password must be at least 6 characters long'], 400);
    }
    
    if (strlen($firstName) < 2 || strlen($lastName) < 2) {
        sendJsonResponse(['success' => false, 'message' => 'First name and last name must be at least 2 characters long'], 400);
    }
    
    if ($phone && !isValidPhone($phone)) {
        sendJsonResponse(['success' => false, 'message' => 'Invalid phone number format'], 400);
    }
    
    try {
        $user = $db->registerUser($username, $email, $password, $firstName, $lastName, $phone);
        
        // Set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['session_token'] = $user['session_token'];
        
        sendJsonResponse([
            'success' => true, 
            'message' => 'Registration successful',
            'user' => $user
        ]);
        
    } catch (Exception $e) {
        sendJsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
    }
}

function handleLogin($db) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendJsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
    }
    
    // Get POST data
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    if (empty($input['username']) || empty($input['password'])) {
        sendJsonResponse(['success' => false, 'message' => 'Username and password are required'], 400);
    }
    
    $username = sanitizeInput($input['username']);
    $password = $input['password'];
    
    try {
        $user = $db->loginUser($username, $password);
        
        // Set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['session_token'] = $user['session_token'];
        
        sendJsonResponse([
            'success' => true, 
            'message' => 'Login successful',
            'user' => $user
        ]);
        
    } catch (Exception $e) {
        sendJsonResponse(['success' => false, 'message' => $e->getMessage()], 401);
    }
}

function handleLogout($db) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendJsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
    }
    
    $sessionToken = $_SESSION['session_token'] ?? null;
    
    if ($sessionToken) {
        $db->logoutUser($sessionToken);
    }
    
    // Clear session
    session_destroy();
    
    sendJsonResponse(['success' => true, 'message' => 'Logout successful']);
}

function handleProfile($db) {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        sendJsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
    }
    
    $sessionToken = $_SESSION['session_token'] ?? null;
    
    if (!$sessionToken) {
        sendJsonResponse(['success' => false, 'message' => 'Not authenticated'], 401);
    }
    
    $user = $db->validateSession($sessionToken);
    
    if (!$user) {
        // Clear invalid session
        session_destroy();
        sendJsonResponse(['success' => false, 'message' => 'Session expired'], 401);
    }
    
    sendJsonResponse([
        'success' => true,
        'user' => $user
    ]);
}

function handleChangePassword($db) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendJsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
    }
    
    // Check for Bearer token in Authorization header
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    
    if (empty($authHeader) || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        sendJsonResponse(['success' => false, 'message' => 'Authorization token required'], 401);
    }
    
    $token = $matches[1];
    $user = $db->validateSession($token);
    
    if (!$user) {
        sendJsonResponse(['success' => false, 'message' => 'Invalid or expired session'], 401);
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['current_password']) || !isset($input['new_password'])) {
        sendJsonResponse(['success' => false, 'message' => 'Missing current_password or new_password'], 400);
    }
    
    if (strlen($input['new_password']) < 6) {
        sendJsonResponse(['success' => false, 'message' => 'New password must be at least 6 characters long'], 400);
    }
    
    $success = $db->changePassword($user['id'], $input['current_password'], $input['new_password']);
    
    if ($success) {
        sendJsonResponse(['success' => true, 'message' => 'Password changed successfully']);
    } else {
        sendJsonResponse(['success' => false, 'message' => 'Current password is incorrect'], 400);
    }
}
?>