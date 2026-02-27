<?php
// includes/notifications.php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/db.php'; // Need DB to fetch templates

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Helper function to wrap message in a branded HTML template
 */
function _buildEmailBody($settings, $message_content) {
    $logo_html = '';
    if (!empty($settings['email_logo'])) {
        // We assume the script is sending from the root domain level or we use absolute URL if configured.
        // For simplicity, we just reference the path. In a real system you'd use a BASE_URL.
        // But many email clients block relative images. We will try to attach it or use a web url if BASE_URL existed.
        // To be safe for local dev, we just put an img tag. 
        // We'll use a placeholder for $base_url
        $base_url = "http://" . $_SERVER['HTTP_HOST'] . "/garage-system-v2/";
        $logo_html = '<div style="text-align: center; margin-bottom: 20px;">
                        <img src="'.$base_url.$settings['email_logo'].'" alt="Logo" style="max-height: 80px;">
                      </div>';
    }

    return '
    <div style="font-family: Arial, sans-serif; color: #333; max-width: 600px; margin: 0 auto; border: 1px solid #eee; padding: 20px; border-radius: 8px;">
        ' . $logo_html . '
        <div style="line-height: 1.6;">
            ' . nl2br($message_content) . '
        </div>
        <div style="margin-top: 30px; font-size: 12px; color: #999; text-align: center; border-top: 1px solid #eee; padding-top: 10px;">
            This is an automated notification. Please do not reply directly.
        </div>
    </div>';
}

/**
 * Base email dispatcher
 */
function _dispatchEmail($to_email, $subject, $message) {
    global $pdo;
    
    // Validate email
    if (empty($to_email) || !filter_var($to_email, FILTER_VALIDATE_EMAIL)) {
        error_log("Invalid or empty email address provided: " . $to_email);
        return false;
    }

    $stmt = $pdo->query("SELECT * FROM email_settings LIMIT 1");
    $settings = $stmt->fetch();

    if (!$settings || empty($settings['smtp_username'])) {
        error_log("SMTP credentials missing in database.");
        return false;
    }

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = $settings['smtp_host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $settings['smtp_username'];
        $mail->Password   = $settings['smtp_password'];
        $mail->SMTPSecure = $settings['smtp_secure'];
        $mail->Port       = $settings['smtp_port'];

        $mail->setFrom($settings['from_email'], $settings['from_name']);
        $mail->addAddress($to_email);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = _buildEmailBody($settings, $message);
        $mail->AltBody = strip_tags($message);

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

// ==========================================
// FEATURE-SPECIFIC EMAIL TRIGGERS
// ==========================================

function sendWelcomeEmail($to_email, $customer_name) {
    global $pdo;
    $stmt = $pdo->query("SELECT welcome_subject, welcome_body FROM email_settings LIMIT 1");
    $settings = $stmt->fetch();
    
    $subject = $settings['welcome_subject'];
    $body = str_replace('{CUSTOMER_NAME}', htmlspecialchars($customer_name), $settings['welcome_body']);
    return _dispatchEmail($to_email, $subject, $body);
}

function sendBookingEmail($to_email, $customer_name, $booking_ref, $booking_date, $booking_time) {
    global $pdo;
    $stmt = $pdo->query("SELECT booking_subject, booking_body FROM email_settings LIMIT 1");
    $settings = $stmt->fetch();
    
    $subject = $settings['booking_subject'];
    $body = $settings['booking_body'];
    $body = str_replace('{CUSTOMER_NAME}', htmlspecialchars($customer_name), $body);
    $body = str_replace('{BOOKING_REF}', htmlspecialchars($booking_ref), $body);
    $body = str_replace('{BOOKING_DATE}', htmlspecialchars($booking_date), $body);
    $body = str_replace('{BOOKING_TIME}', htmlspecialchars($booking_time), $body);
    
    return _dispatchEmail($to_email, $subject, $body);
}

function sendServiceEndEmail($to_email, $customer_name, $job_number, $notes) {
    global $pdo;
    $stmt = $pdo->query("SELECT service_end_subject, service_end_body FROM email_settings LIMIT 1");
    $settings = $stmt->fetch();
    
    $subject = $settings['service_end_subject'];
    $body = $settings['service_end_body'];
    $body = str_replace('{CUSTOMER_NAME}', htmlspecialchars($customer_name), $body);
    $body = str_replace('{JOB_NUMBER}', htmlspecialchars($job_number), $body);
    $body = str_replace('{NOTES}', htmlspecialchars($notes), $body);
    
    return _dispatchEmail($to_email, $subject, $body);
}
?>
