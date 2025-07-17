<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Start output buffering
ob_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Validate input
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Verify reCAPTCHA
    /*
    $recaptcha_secret = "YOUR_SECRET_KEY";
    $recaptcha_response = $_POST['g-recaptcha-response'];
    
    $verify_response = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret='.$recaptcha_secret.'&response='.$recaptcha_response);
    $response_data = json_decode($verify_response);
    
    if (!$response_data->success) {
        die("reCAPTCHA verification failed. Please try again.");
    }
    */

    // Sanitize input using htmlspecialchars instead of deprecated FILTER_SANITIZE_STRING
    $name = htmlspecialchars($_POST['name'] ?? '', ENT_QUOTES, 'UTF-8');
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $phone = htmlspecialchars($_POST['phone'] ?? '', ENT_QUOTES, 'UTF-8');
    $message = htmlspecialchars($_POST['message'] ?? '', ENT_QUOTES, 'UTF-8');

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("Invalid email format");
    }

    require "vendor/autoload.php";

    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->SMTPAuth = true;
        $mail->Host = "smtp.gmail.com";
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Credentials
        $mail->Username = "benji@benjaminmaxwellcreative.com";
        $mail->Password = "zbmz wsjo kmdn mfsk";

        // Recipients
        $mail->setFrom($email, $name);
        $mail->addAddress("bennysreal@gmail.com", "Ben");
        $mail->addReplyTo($email, $name); // Customer's email for replies

        // Content
        $mail->isHTML(true);
        $mail->Subject = "New Contact Form Submission";
        $mail->Body = "
            <h2>New Contact Form Submission</h2>
            <p><strong>Name:</strong> {$name}</p>
            <p><strong>Email:</strong> {$email}</p>
            <p><strong>Phone:</strong> {$phone}</p>
            <p><strong>Message:</strong><br>{$message}</p>
        ";

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