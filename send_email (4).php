<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $to = "info@tridentsys.ca";  // Your business email
    $subject = "New Contact Form Message from your website";

    // Sanitize inputs
    $name = htmlspecialchars(strip_tags(trim($_POST["name"])));
    $email = filter_var(trim($_POST["email"]), FILTER_SANITIZE_EMAIL);
    $message = htmlspecialchars(strip_tags(trim($_POST["message"])));

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "Invalid email address.";
        exit;
    }

    $body = "You have received a new message from your website contact form.\n\n";
    $body .= "Name: $name\n";
    $body .= "Email: $email\n\n";
    $body .= "Message:\n$message\n";

    $headers = "From: $email\r\n";
    $headers .= "Reply-To: $email\r\n";

    if (mail($to, $subject, $body, $headers)) {
        echo "Thank you for contacting us, $name. Your message has been sent.";
    } else {
        echo "Sorry, there was a problem sending your message. Please try again later.";
    }
} else {
    echo "Invalid request.";
}
?>
