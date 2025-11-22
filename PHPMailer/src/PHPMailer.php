<?php
// Install PHPMailer first:
// composer require phpmailer/phpmailer
// OR download from https://github.com/PHPMailer/PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Include PHPMailer files
// require_once 'vendor/autoload.php';
 // If using Composer
// OR if downloaded manually:
require_once 'PHPMailer/src/PHPMailer.php';
require_once 'PHPMailer/src/SMTP.php';
require_once 'PHPMailer/src/Exception.php';

/**
 * Enhanced send email method using PHPMailer
 */
private static function sendEmail($to, $subject, $htmlContent) {
    try {
        $mail = new PHPMailer(true);

        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; // Replace with your SMTP server
        $mail->SMTPAuth   = true;
        $mail->Username   = 'elegantdecosstore@gmail.com'; // Your email
        $mail->Password   = '@mahi1997';    // Your app password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Recipients
        $mail->setFrom('info@elegantdecos.lk', 'Elegant Decos');
        $mail->addAddress($to);
        $mail->addReplyTo('elegantdecosstore@gmail.com', 'Support');

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlContent;
        $mail->AltBody = strip_tags($htmlContent); // Plain text version

        $result = $mail->send();
        
        if ($result) {
            error_log("Email sent successfully to: " . $to);
        }
        
        return $result;
        
    } catch (Exception $e) {
        error_log("PHPMailer Error: {$mail->ErrorInfo}");
        error_log("Exception: " . $e->getMessage());
        return false;
    }
}

/**
 * Alternative: Simple SMTP configuration detection
 */
private static function detectAndConfigureSMTP($mail) {
    // Common SMTP configurations
    $smtpConfigs = [
        'gmail' => [
            'host' => 'smtp.gmail.com',
            'port' => 587,
            'encryption' => PHPMailer::ENCRYPTION_STARTTLS
        ],
        'outlook' => [
            'host' => 'smtp-mail.outlook.com',
            'port' => 587,
            'encryption' => PHPMailer::ENCRYPTION_STARTTLS
        ],
        'yahoo' => [
            'host' => 'smtp.mail.yahoo.com',
            'port' => 587,
            'encryption' => PHPMailer::ENCRYPTION_STARTTLS
        ]
    ];
    
    // You could detect based on email domain or use environment variables
    $config = $smtpConfigs['gmail']; // Default to Gmail
    
    $mail->Host = $config['host'];
    $mail->Port = $config['port'];
    $mail->SMTPSecure = $config['encryption'];
}
?>