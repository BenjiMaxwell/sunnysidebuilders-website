<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Load environment variables
require_once 'vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Start output buffering
ob_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Validate input
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Verify reCAPTCHA
    $recaptcha_secret = "RECAPTCHA_SECRET_KEY"; // Replace with your actual secret key
    $recaptcha_response = $_POST['g-recaptcha-response'] ?? '';
    
    if (empty($recaptcha_response)) {
        die("Please complete the reCAPTCHA verification.");
    }
    
    $verify_response = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret='.$recaptcha_secret.'&response='.$recaptcha_response);
    $response_data = json_decode($verify_response);
    
    if (!$response_data->success) {
        die("reCAPTCHA verification failed. Please try again.");
    }

    // Sanitize input using htmlspecialchars instead of deprecated FILTER_SANITIZE_STRING
    $name = htmlspecialchars($_POST['name'] ?? '', ENT_QUOTES, 'UTF-8');
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $phone = htmlspecialchars($_POST['phone'] ?? '', ENT_QUOTES, 'UTF-8');
    $message = htmlspecialchars($_POST['message'] ?? '', ENT_QUOTES, 'UTF-8');
    $date = date('Y-m-d H:i:s'); // Add date for the template

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("Invalid email format");
    }

    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->SMTPAuth = true;
        $mail->Host = $_ENV['SMTP_HOST'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $_ENV['SMTP_PORT'];

        // Credentials
        $mail->Username = $_ENV['SMTP_USERNAME'];
        $mail->Password = $_ENV['SMTP_PASSWORD'];

        // Recipients
        $mail->setFrom($_ENV['FROM_EMAIL'], $name);
        $mail->addAddress($_ENV['TO_EMAIL'], $_ENV['TO_NAME']);
        $mail->addReplyTo($email, $name); // Customer's email for replies

        // Content
        $mail->isHTML(true);
        $mail->Subject = "New Contact Form Submission";

        // Embed the HTML template using heredoc
        $html_template = <<<EOT
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Form Submission</title>
    <style>
        /* Inline styles for better compatibility */
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 20px auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .header { background-color:rgb(27, 28, 29); /* Replace with your brand color */ padding: 20px; text-align: center; }
        .header img { max-width: 150px; height: auto; }
        .content { padding: 20px; }
        .content h2 { color: #333333; }
        .content p { margin: 10px 0; color: #555555; }
        .footer { background-color: #f4f4f4; padding: 10px; text-align: center; font-size: 12px; color: #777777; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="logo.png" alt="Company Logo">
            <h1 style="color: #ffffff; margin: 10px 0;">New Contact Form Submission</h1>
        </div>
        <div class="content">
            <h2>Submission Details</h2>
            <p><strong>Name:</strong> {NAME}</p>
            <p><strong>Email:</strong> {EMAIL}</p>
            <p><strong>Phone:</strong> {PHONE}</p>
            <p><strong>Message:</strong> {MESSAGE}</p>
        </div>
        <div class="footer">
            © Sunnyside Builders Inc. All rights reserved.<br>
            Sent from sunnysidebuildersinc.com on {DATE}.
        </div>
    </div>
</body>
</html>
EOT;

        // Replace placeholders with actual values
        $mail->Body = str_replace(
            array('{NAME}', '{EMAIL}', '{PHONE}', '{MESSAGE}', '{DATE}'),
            array($name, $email, $phone, nl2br($message), $date), // nl2br preserves line breaks in message
            $html_template
        );

        // Add plain text alternative for non-HTML clients
        $mail->AltBody = "New Contact Form Submission\n\nName: $name\nEmail: $email\nPhone: $phone\nMessage: $message\nDate: $date";

        $mail->send();
        
        // Clear any output and redirect
        ob_end_clean();
        header("Location: thank-you.html");
        exit();
    } catch (Exception $e) {
        ob_end_clean();
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
} else {
    ob_end_clean();
    echo "Invalid request method";
}