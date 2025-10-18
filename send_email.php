<?php
// Enhanced Contact Form Handler for Trident Systems
// Fire Flow Testing Services - Professional Contact Processing

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// Only allow POST requests
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo "Method Not Allowed";
    exit;
}

// Rate limiting (basic)
session_start();
$current_time = time();
if (isset($_SESSION['last_submission']) && ($current_time - $_SESSION['last_submission']) < 60) {
    http_response_code(429);
    echo "Please wait before submitting another message.";
    exit;
}

// Configuration
$to = "info@tridentsys.ca";
$subject = "New Contact Form Message - Trident Systems Fire Flow Testing";

// Input validation and sanitization
function validate_and_sanitize($input, $type = 'text') {
    $input = trim($input);
    $input = stripslashes($input);
    
    switch($type) {
        case 'email':
            return filter_var($input, FILTER_SANITIZE_EMAIL);
        case 'text':
        default:
            return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    }
}

// Validate required fields
if (empty($_POST["name"]) || empty($_POST["email"]) || empty($_POST["message"])) {
    http_response_code(400);
    echo "All fields are required.";
    exit;
}

// Sanitize inputs
$name = validate_and_sanitize($_POST["name"]);
$email = validate_and_sanitize($_POST["email"], 'email');
$message = validate_and_sanitize($_POST["message"]);

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo "Invalid email address format.";
    exit;
}

// Basic spam protection
if (strlen($message) < 10) {
    http_response_code(400);
    echo "Message too short. Please provide more details.";
    exit;
}

// Check for suspicious content
$spam_keywords = ['viagra', 'casino', 'lottery', 'million dollars', 'click here', 'free money'];
foreach ($spam_keywords as $keyword) {
    if (stripos($message, $keyword) !== false) {
        http_response_code(400);
        echo "Message contains prohibited content.";
        exit;
    }
}

// Prepare email content
$email_body = "New Contact Form Submission - Trident Systems\n\n";
$email_body .= "=== FIRE FLOW TESTING INQUIRY ===\n\n";
$email_body .= "Contact Information:\n";
$email_body .= "Name: $name\n";
$email_body .= "Email: $email\n";
$email_body .= "Submitted: " . date('Y-m-d H:i:s T') . "\n\n";
$email_body .= "Message:\n";
$email_body .= "$message\n\n";
$email_body .= "=== END OF MESSAGE ===\n\n";
$email_body .= "This message was sent from the Trident Systems contact form at tridentsys.ca\n";
$email_body .= "For fire flow testing services in GTA, Hamilton, and Niagara regions.\n";

// Email headers
$headers = "From: noreply@tridentsys.ca\r\n";
$headers .= "Reply-To: $email\r\n";
$headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

// Send email
if (mail($to, $subject, $email_body, $headers)) {
    $_SESSION['last_submission'] = $current_time;
    http_response_code(200);
    echo "Thank you for contacting Trident Systems, $name. Your fire flow testing inquiry has been sent successfully. We will respond within 24 hours.";
} else {
    http_response_code(500);
    error_log("Failed to send email from contact form - " . date('Y-m-d H:i:s'));
    echo "Sorry, there was a technical issue sending your message. Please try again later or contact us directly at info@tridentsys.ca";
}
?>