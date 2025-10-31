<?php
// Enhanced Contact Form Handler for Trident Systems
// Fire Flow Testing Services & HydrantHub Software - Professional Contact Processing

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
$form_type = isset($_POST['form_type']) ? $_POST['form_type'] : 'general';

// Set subject based on form type
switch($form_type) {
    case 'hydrant_hub_demo':
        $subject = "HydrantHub Demo Request - Trident Systems";
        break;
    default:
        $subject = "New Contact Form Message - Trident Systems Fire Flow Testing";
        break;
}

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
if (empty($_POST["name"]) || empty($_POST["email"])) {
    http_response_code(400);
    echo "Name and email are required fields.";
    exit;
}

// For general form, message is required
if ($form_type !== 'hydrant_hub_demo' && empty($_POST["message"])) {
    http_response_code(400);
    echo "Message is required.";
    exit;
}

// Sanitize inputs
$name = validate_and_sanitize($_POST["name"]);
$email = validate_and_sanitize($_POST["email"], 'email');
$message = isset($_POST["message"]) ? validate_and_sanitize($_POST["message"]) : '';

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo "Invalid email address format.";
    exit;
}

// Basic spam protection (if message exists)
if (!empty($message) && strlen($message) < 10) {
    http_response_code(400);
    echo "Message too short. Please provide more details.";
    exit;
}

// Check for suspicious content
$spam_keywords = ['viagra', 'casino', 'lottery', 'million dollars', 'click here', 'free money'];
foreach ($spam_keywords as $keyword) {
    if ((!empty($message) && stripos($message, $keyword) !== false) || stripos($name, $keyword) !== false) {
        http_response_code(400);
        echo "Message contains prohibited content.";
        exit;
    }
}

// Prepare email content based on form type
if ($form_type === 'hydrant_hub_demo') {
    // HydrantHub Demo Request
    $title = validate_and_sanitize($_POST["title"] ?? '');
    $phone = validate_and_sanitize($_POST["phone"] ?? '');
    $organization = validate_and_sanitize($_POST["organization"] ?? '');
    $hydrant_count = validate_and_sanitize($_POST["hydrant_count"] ?? '');
    $challenges = validate_and_sanitize($_POST["challenges"] ?? '');
    
    $email_body = "New HydrantHub Demo Request - Trident Systems\n\n";
    $email_body .= "=== HYDRANTHUB SOFTWARE DEMO REQUEST ===\n\n";
    $email_body .= "Contact Information:\n";
    $email_body .= "Name: $name\n";
    $email_body .= "Email: $email\n";
    $email_body .= "Title: $title\n";
    $email_body .= "Phone: $phone\n";
    $email_body .= "Organization: $organization\n";
    $email_body .= "Number of Hydrants: $hydrant_count\n";
    $email_body .= "Submitted: " . date('Y-m-d H:i:s T') . "\n\n";
    
    if (!empty($challenges)) {
        $email_body .= "Current Challenges:\n";
        $email_body .= "$challenges\n\n";
    }
    
    $email_body .= "=== ACTION REQUIRED ===\n";
    $email_body .= "1. Schedule personalized demo presentation\n";
    $email_body .= "2. Show NFPA 291 compliance features\n";
    $email_body .= "3. Demonstrate preventive maintenance module\n";
    $email_body .= "4. Provide ROI analysis for their organization\n";
    $email_body .= "5. Discuss pricing tier recommendations\n\n";
    $email_body .= "=== END OF REQUEST ===\n\n";
    $email_body .= "This demo request was submitted from the HydrantHub page at tridentsys.ca/hydrant-hub.html\n";
    $email_body .= "Priority: HIGH - Software lead conversion opportunity\n";
    
} else {
    // General Contact Form
    $project_type = validate_and_sanitize($_POST["project_type"] ?? '');
    
    $email_body = "New Contact Form Submission - Trident Systems\n\n";
    $email_body .= "=== FIRE FLOW TESTING INQUIRY ===\n\n";
    $email_body .= "Contact Information:\n";
    $email_body .= "Name: $name\n";
    $email_body .= "Email: $email\n";
    
    if (!empty($project_type)) {
        $email_body .= "Project Type: $project_type\n";
    }
    
    $email_body .= "Submitted: " . date('Y-m-d H:i:s T') . "\n\n";
    $email_body .= "Message:\n";
    $email_body .= "$message\n\n";
    $email_body .= "=== END OF MESSAGE ===\n\n";
    $email_body .= "This message was sent from the Trident Systems contact form at tridentsys.ca\n";
    $email_body .= "For fire flow testing services in GTA, Hamilton, and Niagara regions.\n";
}

// Email headers
$headers = "From: noreply@tridentsys.ca\r\n";
$headers .= "Reply-To: $email\r\n";
$headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

// Send email
if (mail($to, $subject, $email_body, $headers)) {
    $_SESSION['last_submission'] = $current_time;
    http_response_code(200);
    
    if ($form_type === 'hydrant_hub_demo') {
        echo "Thank you for requesting a HydrantHub demo, $name! We will contact you within 24 hours to schedule a personalized demonstration of our hydrant management software.";
    } else {
        echo "Thank you for contacting Trident Systems, $name. Your fire flow testing inquiry has been sent successfully. We will respond within 24 hours.";
    }
} else {
    http_response_code(500);
    error_log("Failed to send email from contact form - " . date('Y-m-d H:i:s'));
    echo "Sorry, there was a technical issue sending your message. Please try again later or contact us directly at info@tridentsys.ca";
}
?>