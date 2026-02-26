<?php
// includes/notifications.php

require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Send an email notification using PHPMailer
 *
 * @param string $to_email
 * @param string $subject
 * @param string $message (HTML body)
 * @return bool True if sent, False otherwise
 */
function sendEmailNotification($to_email, $subject, $message) {
    // Validate email
    if (empty($to_email) || !filter_var($to_email, FILTER_VALIDATE_EMAIL)) {
        error_log("Invalid or empty email address provided: " . $to_email);
        return false;
    }

    $mailConfig = require __DIR__ . '/../config/mail.php';
    
    // If the config is still default/placeholder, don't try to send
    if ($mailConfig['smtp_username'] === 'your_email@gmail.com') {
        error_log("Mail config is using placeholder credentials. Email to $to_email was not sent.");
        return false;
    }

    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = $mailConfig['smtp_host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $mailConfig['smtp_username'];
        $mail->Password   = $mailConfig['smtp_password'];
        $mail->SMTPSecure = $mailConfig['smtp_secure'];
        $mail->Port       = $mailConfig['smtp_port'];

        // Recipients
        $mail->setFrom($mailConfig['from_email'], $mailConfig['from_name']);
        $mail->addAddress($to_email);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $message;
        $mail->AltBody = strip_tags($message);

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}
?>
