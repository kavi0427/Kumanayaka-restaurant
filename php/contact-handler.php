<?php
/**
 * Contact form handler for Bella Vista Restaurant
 * Processes reservation requests and contact inquiries
 */

// Define access constant
define('BELLA_VISTA_ACCESS', true);

// Include configuration
require_once 'config.php';

// Set security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Enable CORS for same origin
header('Access-Control-Allow-Origin: ' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

// Rate limiting
$clientIP = getClientIP();
if (!checkRateLimit($clientIP)) {
    logActivity("Rate limit exceeded for IP: $clientIP", 'WARNING');
    sendJsonResponse(['success' => false, 'message' => 'Too many requests. Please try again later.'], 429);
}

try {
    // Get and validate form data
    $firstName = sanitizeInput($_POST['firstName'] ?? '');
    $lastName = sanitizeInput($_POST['lastName'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $date = sanitizeInput($_POST['date'] ?? '');
    $time = sanitizeInput($_POST['time'] ?? '');
    $guests = sanitizeInput($_POST['guests'] ?? '');
    $occasion = sanitizeInput($_POST['occasion'] ?? '');
    $message = sanitizeInput($_POST['message'] ?? '');

    // Validation array to collect errors
    $errors = [];

    // Validate required fields
    if (empty($firstName) || strlen($firstName) < MIN_NAME_LENGTH || strlen($firstName) > MAX_NAME_LENGTH) {
        $errors[] = 'First name must be between ' . MIN_NAME_LENGTH . ' and ' . MAX_NAME_LENGTH . ' characters.';
    }

    if (empty($lastName) || strlen($lastName) < MIN_NAME_LENGTH || strlen($lastName) > MAX_NAME_LENGTH) {
        $errors[] = 'Last name must be between ' . MIN_NAME_LENGTH . ' and ' . MAX_NAME_LENGTH . ' characters.';
    }

    if (empty($email) || !isValidEmail($email)) {
        $errors[] = 'Please provide a valid email address.';
    }

    if (!empty($phone) && !isValidPhone($phone)) {
        $errors[] = 'Please provide a valid phone number.';
    }

    if (empty($date) || !isValidReservationDate($date)) {
        $errors[] = 'Please select a valid reservation date within the next 90 days.';
    }

    if (empty($time) || !isValidReservationTime($time, $date)) {
        $errors[] = 'Please select a valid time during our business hours.';
    }

    if (empty($guests) || !in_array($guests, ['1', '2', '3', '4', '5', '6', '7', '8', 'more'])) {
        $errors[] = 'Please select the number of guests.';
    }

    if (!empty($message) && (strlen($message) < MIN_MESSAGE_LENGTH || strlen($message) > MAX_MESSAGE_LENGTH)) {
        $errors[] = 'Message must be between ' . MIN_MESSAGE_LENGTH . ' and ' . MAX_MESSAGE_LENGTH . ' characters.';
    }

    // Additional validation for names (no numbers or special characters)
    if (!preg_match('/^[a-zA-Z\s\'-]+$/', $firstName)) {
        $errors[] = 'First name contains invalid characters.';
    }

    if (!preg_match('/^[a-zA-Z\s\'-]+$/', $lastName)) {
        $errors[] = 'Last name contains invalid characters.';
    }

    // Check for SQL injection patterns (additional security)
    $dangerousPatterns = ['union', 'select', 'insert', 'update', 'delete', 'drop', 'script', 'javascript'];
    $allInputs = [$firstName, $lastName, $email, $phone, $message];
    
    foreach ($allInputs as $input) {
        foreach ($dangerousPatterns as $pattern) {
            if (stripos($input, $pattern) !== false) {
                logActivity("Potential security threat detected from IP: $clientIP - Input: $input", 'SECURITY');
                sendJsonResponse(['success' => false, 'message' => 'Invalid input detected.'], 400);
            }
        }
    }

    // If there are validation errors, return them
    if (!empty($errors)) {
        sendJsonResponse(['success' => false, 'message' => implode(' ', $errors)], 400);
    }

    // Format the reservation data
    $reservationData = [
        'firstName' => $firstName,
        'lastName' => $lastName,
        'email' => $email,
        'phone' => $phone,
        'date' => $date,
        'time' => $time,
        'guests' => $guests,
        'occasion' => $occasion,
        'message' => $message,
        'submitted_at' => date('Y-m-d H:i:s'),
        'ip_address' => $clientIP
    ];

    // Send email notification
    if (sendReservationEmail($reservationData)) {
        // Log successful submission
        logActivity("Reservation request submitted: {$firstName} {$lastName} ({$email}) for {$date} at {$time}", 'INFO');
        
        // Save to file (you can replace this with database storage)
        saveReservationToFile($reservationData);
        
        sendJsonResponse([
            'success' => true,
            'message' => 'Thank you! Your reservation request has been submitted successfully. We will contact you within 24 hours to confirm your booking.'
        ]);
    } else {
        throw new Exception('Failed to send email notification');
    }

} catch (Exception $e) {
    logActivity("Error processing reservation: " . $e->getMessage(), 'ERROR');
    sendJsonResponse([
        'success' => false,
        'message' => 'We apologize, but there was an error processing your request. Please call us directly at (555) 123-4567 to make your reservation.'
    ], 500);
}

/**
 * Send reservation email to restaurant
 */
function sendReservationEmail($data) {
    try {
        // Format the date and time nicely
        $formattedDate = date('l, F j, Y', strtotime($data['date']));
        $formattedTime = date('g:i A', strtotime($data['time']));
        
        // Prepare email content
        $subject = 'New Reservation Request - ' . $data['firstName'] . ' ' . $data['lastName'];
        
        $emailBody = "
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #E67300; color: white; padding: 20px; text-align: center; }
        .content { background: #f9f9f9; padding: 20px; }
        .detail-row { margin-bottom: 10px; }
        .label { font-weight: bold; color: #E67300; }
        .footer { background: #333; color: white; padding: 15px; text-align: center; font-size: 12px; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1>🍽️ Bella Vista Restaurant</h1>
            <h2>New Reservation Request</h2>
        </div>
        
        <div class='content'>
            <h3>Customer Information:</h3>
            <div class='detail-row'>
                <span class='label'>Name:</span> {$data['firstName']} {$data['lastName']}
            </div>
            <div class='detail-row'>
                <span class='label'>Email:</span> {$data['email']}
            </div>
            <div class='detail-row'>
                <span class='label'>Phone:</span> " . ($data['phone'] ?: 'Not provided') . "
            </div>
            
            <h3>Reservation Details:</h3>
            <div class='detail-row'>
                <span class='label'>Date:</span> {$formattedDate}
            </div>
            <div class='detail-row'>
                <span class='label'>Time:</span> {$formattedTime}
            </div>
            <div class='detail-row'>
                <span class='label'>Number of Guests:</span> {$data['guests']}
            </div>
            <div class='detail-row'>
                <span class='label'>Occasion:</span> " . ($data['occasion'] ?: 'Not specified') . "
            </div>
            
            " . (!empty($data['message']) ? "
            <h3>Special Requests:</h3>
            <div style='background: white; padding: 15px; border-left: 4px solid #E67300; margin: 10px 0;'>
                " . nl2br(htmlspecialchars($data['message'])) . "
            </div>
            " : "") . "
            
            <div style='margin-top: 20px; padding: 15px; background: #fff3e0; border-radius: 5px;'>
                <strong>⚠️ Action Required:</strong> Please contact the customer within 24 hours to confirm this reservation.
            </div>
        </div>
        
        <div class='footer'>
            <p>Submitted on: " . date('F j, Y \a\t g:i A') . "</p>
            <p>IP Address: {$data['ip_address']}</p>
            <p>Bella Vista Restaurant Management System</p>
        </div>
    </div>
</body>
</html>";

        // Prepare plain text version
        $plainTextBody = "
NEW RESERVATION REQUEST - BELLA VISTA RESTAURANT

Customer Information:
Name: {$data['firstName']} {$data['lastName']}
Email: {$data['email']}
Phone: " . ($data['phone'] ?: 'Not provided') . "

Reservation Details:
Date: {$formattedDate}
Time: {$formattedTime}
Guests: {$data['guests']}
Occasion: " . ($data['occasion'] ?: 'Not specified') . "

" . (!empty($data['message']) ? "Special Requests:\n" . $data['message'] . "\n\n" : "") . "

ACTION REQUIRED: Please contact the customer within 24 hours to confirm this reservation.

Submitted: " . date('F j, Y \a\t g:i A') . "
IP: {$data['ip_address']}
        ";

        // Email headers
        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: multipart/alternative; boundary="boundary_' . md5(time()) . '"',
            'From: ' . RESTAURANT_NAME . ' Website <' . RESTAURANT_EMAIL . '>',
            'Reply-To: ' . $data['email'],
            'X-Mailer: PHP/' . phpversion(),
            'X-Priority: 1 (High)',
            'Importance: High'
        ];

        $boundary = 'boundary_' . md5(time());
        
        $multipartBody = "--{$boundary}\r\n";
        $multipartBody .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $multipartBody .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
        $multipartBody .= $plainTextBody . "\r\n\r\n";
        
        $multipartBody .= "--{$boundary}\r\n";
        $multipartBody .= "Content-Type: text/html; charset=UTF-8\r\n";
        $multipartBody .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
        $multipartBody .= $emailBody . "\r\n\r\n";
        
        $multipartBody .= "--{$boundary}--\r\n";

        // Send email to restaurant
        $emailSent = mail(ADMIN_EMAIL, $subject, $multipartBody, implode("\r\n", $headers));
        
        if ($emailSent) {
            // Send confirmation email to customer
            sendCustomerConfirmationEmail($data);
            return true;
        }
        
        return false;

    } catch (Exception $e) {
        logActivity("Email sending failed: " . $e->getMessage(), 'ERROR');
        return false;
    }
}

/**
 * Send confirmation email to customer
 */
function sendCustomerConfirmationEmail($data) {
    try {
        $formattedDate = date('l, F j, Y', strtotime($data['date']));
        $formattedTime = date('g:i A', strtotime($data['time']));
        
        $subject = 'Reservation Request Received - Bella Vista Restaurant';
        
        $customerEmailBody = "
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #E67300; color: white; padding: 20px; text-align: center; }
        .content { background: #f9f9f9; padding: 20px; }
        .highlight { background: #fff3e0; padding: 15px; border-radius: 5px; margin: 15px 0; }
        .footer { background: #333; color: white; padding: 15px; text-align: center; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1>🍽️ Bella Vista Restaurant</h1>
            <h2>Thank You for Your Reservation Request!</h2>
        </div>
        
        <div class='content'>
            <p>Dear {$data['firstName']},</p>
            
            <p>Thank you for choosing Bella Vista Restaurant! We have received your reservation request and our team will contact you within 24 hours to confirm your booking.</p>
            
            <div class='highlight'>
                <h3>📅 Your Reservation Request:</h3>
                <p><strong>Date:</strong> {$formattedDate}<br>
                <strong>Time:</strong> {$formattedTime}<br>
                <strong>Party Size:</strong> {$data['guests']} guest(s)<br>
                " . (!empty($data['occasion']) ? "<strong>Occasion:</strong> {$data['occasion']}<br>" : "") . "
                </p>
            </div>
            
            <p><strong>📞 Contact Information:</strong><br>
            Phone: (555) 123-4567<br>
            Email: info@bellavista.com</p>
            
            <p><strong>📍 Location:</strong><br>
            123 Culinary Street<br>
            Food District, City 12345</p>
            
            <p><strong>🕒 Business Hours:</strong><br>
            Monday - Thursday: 5:00 PM - 10:00 PM<br>
            Friday - Saturday: 5:00 PM - 11:00 PM<br>
            Sunday: 4:00 PM - 9:00 PM</p>
            
            <p>We look forward to providing you with an exceptional dining experience!</p>
            
            <p>Best regards,<br>
            The Bella Vista Team</p>
        </div>
        
        <div class='footer'>
            <p>This is an automated confirmation. Please do not reply to this email.</p>
            <p>If you need to make changes to your reservation, please call us at (555) 123-4567</p>
        </div>
    </div>
</body>
</html>";

        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . RESTAURANT_NAME . ' <' . RESTAURANT_EMAIL . '>',
            'X-Mailer: PHP/' . phpversion()
        ];

        return mail($data['email'], $subject, $customerEmailBody, implode("\r\n", $headers));

    } catch (Exception $e) {
        logActivity("Customer confirmation email failed: " . $e->getMessage(), 'ERROR');
        return false;
    }
}

/**
 * Save reservation to file (can be replaced with database storage)
 */
function saveReservationToFile($data) {
    try {
        $reservationsDir = __DIR__ . '/reservations';
        if (!file_exists($reservationsDir)) {
            mkdir($reservationsDir, 0755, true);
        }
        
        $filename = $reservationsDir . '/reservation_' . date('Y-m-d_H-i-s') . '_' . md5($data['email']) . '.json';
        $jsonData = json_encode($data, JSON_PRETTY_PRINT);
        
        return file_put_contents($filename, $jsonData) !== false;
        
    } catch (Exception $e) {
        logActivity("Failed to save reservation to file: " . $e->getMessage(), 'ERROR');
        return false;
    }
}
?>
