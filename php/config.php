<?php
/**
 * Configuration file for Bella Vista Restaurant Website
 * Contains database settings, email configuration, and other constants
 */

// Prevent direct access
if (!defined('BELLA_VISTA_ACCESS')) {
    http_response_code(403);
    exit('Access denied');
}

// Environment settings
define('ENVIRONMENT', 'production'); // Change to 'development' for debugging
define('DEBUG_MODE', ENVIRONMENT === 'development');

// Email configuration
define('SMTP_HOST', getenv('SMTP_HOST') ?: 'localhost');
define('SMTP_PORT', getenv('SMTP_PORT') ?: 587);
define('SMTP_USERNAME', getenv('SMTP_USERNAME') ?: '');
define('SMTP_PASSWORD', getenv('SMTP_PASSWORD') ?: '');
define('SMTP_ENCRYPTION', getenv('SMTP_ENCRYPTION') ?: 'tls');

// Restaurant email settings
define('RESTAURANT_EMAIL', getenv('RESTAURANT_EMAIL') ?: 'info@bellavista.com');
define('RESTAURANT_NAME', 'Bella Vista Restaurant');
define('ADMIN_EMAIL', getenv('ADMIN_EMAIL') ?: 'reservations@bellavista.com');

// Form validation settings
define('MAX_MESSAGE_LENGTH', 1000);
define('MIN_MESSAGE_LENGTH', 10);
define('MAX_NAME_LENGTH', 100);
define('MIN_NAME_LENGTH', 2);

// Rate limiting (requests per IP per hour)
define('RATE_LIMIT_REQUESTS', 100);
define('RATE_LIMIT_WINDOW', 3600); // 1 hour in seconds

// Security settings
define('CSRF_TOKEN_EXPIRY', 3600); // 1 hour
define('SESSION_TIMEOUT', 7200); // 2 hours

// Business hours for validation
define('BUSINESS_HOURS', [
    'monday' => ['17:00', '22:00'],
    'tuesday' => ['17:00', '22:00'],
    'wednesday' => ['17:00', '22:00'],
    'thursday' => ['17:00', '22:00'],
    'friday' => ['17:00', '23:00'],
    'saturday' => ['17:00', '23:00'],
    'sunday' => ['16:00', '21:00']
]);

// Closed days (format: Y-m-d)
define('CLOSED_DATES', [
    '2024-12-25', // Christmas
    '2024-01-01', // New Year's Day
]);

// Time zone
define('RESTAURANT_TIMEZONE', 'America/New_York');

// File upload settings (if needed for future features)
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_FILE_TYPES', ['jpg', 'jpeg', 'png', 'pdf']);

// Database configuration (if needed for future features)
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'bellavista_db');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_CHARSET', 'utf8mb4');

// Error logging
define('LOG_ERRORS', true);
define('ERROR_LOG_FILE', __DIR__ . '/logs/error.log');

// Create logs directory if it doesn't exist
if (LOG_ERRORS && !file_exists(dirname(ERROR_LOG_FILE))) {
    mkdir(dirname(ERROR_LOG_FILE), 0755, true);
}

/**
 * Custom error handler
 */
function customErrorHandler($errno, $errstr, $errfile, $errline) {
    if (LOG_ERRORS) {
        $error_message = date('[Y-m-d H:i:s] ') . "Error: [$errno] $errstr in $errfile on line $errline" . PHP_EOL;
        error_log($error_message, 3, ERROR_LOG_FILE);
    }
    
    if (DEBUG_MODE) {
        echo "Error: [$errno] $errstr in $errfile on line $errline";
    }
    
    return true;
}

// Set custom error handler
set_error_handler('customErrorHandler');

/**
 * Utility functions
 */

/**
 * Sanitize input data
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Validate email address
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate phone number (US format)
 */
function isValidPhone($phone) {
    $phone = preg_replace('/[^\d]/', '', $phone);
    return preg_match('/^\d{10}$/', $phone);
}

/**
 * Validate date format and availability
 */
function isValidReservationDate($date) {
    // Check if date is valid
    $dateTime = DateTime::createFromFormat('Y-m-d', $date);
    if (!$dateTime || $dateTime->format('Y-m-d') !== $date) {
        return false;
    }
    
    // Check if date is not in the past
    $today = new DateTime();
    $today->setTime(0, 0, 0);
    if ($dateTime < $today) {
        return false;
    }
    
    // Check if date is not too far in the future (90 days)
    $maxDate = clone $today;
    $maxDate->add(new DateInterval('P90D'));
    if ($dateTime > $maxDate) {
        return false;
    }
    
    // Check if restaurant is closed on this date
    if (in_array($date, CLOSED_DATES)) {
        return false;
    }
    
    return true;
}

/**
 * Validate reservation time
 */
function isValidReservationTime($time, $date) {
    // Check time format
    if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $time)) {
        return false;
    }
    
    // Get day of week
    $dateTime = DateTime::createFromFormat('Y-m-d', $date);
    $dayOfWeek = strtolower($dateTime->format('l'));
    
    // Check if within business hours
    if (!isset(BUSINESS_HOURS[$dayOfWeek])) {
        return false;
    }
    
    $businessHours = BUSINESS_HOURS[$dayOfWeek];
    $openTime = $businessHours[0];
    $closeTime = $businessHours[1];
    
    return $time >= $openTime && $time <= $closeTime;
}

/**
 * Rate limiting check
 */
function checkRateLimit($ip) {
    $rateFile = __DIR__ . '/logs/rate_' . md5($ip) . '.json';
    
    if (!file_exists($rateFile)) {
        $rateData = ['count' => 1, 'timestamp' => time()];
        file_put_contents($rateFile, json_encode($rateData));
        return true;
    }
    
    $rateData = json_decode(file_get_contents($rateFile), true);
    $currentTime = time();
    
    // Reset counter if window has passed
    if ($currentTime - $rateData['timestamp'] > RATE_LIMIT_WINDOW) {
        $rateData = ['count' => 1, 'timestamp' => $currentTime];
        file_put_contents($rateFile, json_encode($rateData));
        return true;
    }
    
    // Check if limit exceeded
    if ($rateData['count'] >= RATE_LIMIT_REQUESTS) {
        return false;
    }
    
    // Increment counter
    $rateData['count']++;
    file_put_contents($rateFile, json_encode($rateData));
    return true;
}

/**
 * Get client IP address
 */
function getClientIP() {
    $ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
    
    foreach ($ipKeys as $key) {
        if (array_key_exists($key, $_SERVER) === true) {
            $ip = $_SERVER[$key];
            if (strpos($ip, ',') !== false) {
                $ip = explode(',', $ip)[0];
            }
            $ip = trim($ip);
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
}

/**
 * Send JSON response
 */
function sendJsonResponse($data, $httpCode = 200) {
    http_response_code($httpCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Log activity
 */
function logActivity($message, $level = 'INFO') {
    if (LOG_ERRORS) {
        $logMessage = date('[Y-m-d H:i:s] ') . "[$level] $message" . PHP_EOL;
        error_log($logMessage, 3, ERROR_LOG_FILE);
    }
}
?>
