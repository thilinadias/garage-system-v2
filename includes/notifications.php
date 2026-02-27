<?php
// includes/notifications.php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/db.php'; // Need DB to fetch templates

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Helper function to wrap message in a branded HTML template
 */
function _buildEmailBody($settings, $message_content, $logo_cid = null, $is_html_block = false) {
    $logo_html = '';
    if ($logo_cid) {
        $logo_html = '<div style="text-align: center; margin-bottom: 20px;">
                        <img src="cid:'.$logo_cid.'" alt="Logo" style="max-height: 80px;">
                      </div>';
    }

    $body_content = $is_html_block ? $message_content : nl2br($message_content);

    return '
    <div style="font-family: Arial, sans-serif; color: #333; max-width: 600px; margin: 0 auto; border: 1px solid #eee; padding: 20px; border-radius: 8px;">
        ' . $logo_html . '
        <div style="line-height: 1.6;">
            ' . $body_content . '
        </div>
        <div style="margin-top: 30px; font-size: 12px; color: #999; text-align: center; border-top: 1px solid #eee; padding-top: 10px;">
            This is an automated notification. Please do not reply directly.
        </div>
    </div>';
}

/**
 * Base email dispatcher
 */
function _dispatchEmail($to_email, $subject, $message, $is_html_block = false) {
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
        
        // Handle Embedded Logo (CID)
        $logo_cid = null;
        if (!empty($settings['email_logo'])) {
            $logo_path = __DIR__ . '/../' . $settings['email_logo'];
            if (file_exists($logo_path)) {
                $logo_cid = 'logo_' . md5(uniqid());
                $mail->addEmbeddedImage($logo_path, $logo_cid, 'logo.png');
            }
        }

        $mail->Body    = _buildEmailBody($settings, $message, $logo_cid, $is_html_block);
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

function sendPromotionalOfferEmail($to_email, $customer_name, $service) {
    global $pdo;
    
    // We don't have a DB template row for this so we build a dynamic HTML one.
    $stmt = $pdo->query("SELECT company_name, currency_symbol FROM company_profile LIMIT 1");
    $profile = $stmt->fetch();
    $company_name = $profile['company_name'] ?? 'The Garage';
    $currency = $profile['currency_symbol'] ?? '$';

    $subject = "Special Offer: " . htmlspecialchars($service['offer_name']) . " at " . $company_name . "!";
    
    // Calculate math cleanly
    $price_calc = calculateServicePrice($service);
    $original_price_str = $currency . number_format($service['original_price'], 2);
    $final_price_str = $currency . number_format($price_calc['final_price'], 2);
    
    $discount_type = $service['offer_discount_type'] == 'percentage' ? htmlspecialchars($service['offer_discount_value']) . '%' : $currency . number_format($service['offer_discount_value'], 2);
    
    $expiry_html = "";
    if (!empty($service['offer_end_date'])) {
        $expiry_html = "<br><br><b>Hurry! This offer expires on: " . date('F j, Y', strtotime($service['offer_end_date'])) . "</b>";
    }

    $body = "
    <div style='font-family: Arial, sans-serif;'>
        <h2 style='color: #2c3e50;'>Hi " . htmlspecialchars($customer_name) . "!</h2>
        <p>We've just launched an exclusive special offer for our valued customers at <b>" . htmlspecialchars($company_name) . "</b>!</p>
        
        <div style='background: #f8f9fa; border-left: 4px solid #f39c12; padding: 20px; margin: 20px 0;'>
            <h3 style='margin-top: 0; color: #e67e22;'>&#127881; " . htmlspecialchars($service['offer_name']) . "</h3>
            <p style='font-size: 16px; font-weight: bold;'>Service: " . htmlspecialchars($service['name']) . "</p>
            <p>" . htmlspecialchars($service['description']) . "</p>
            <hr style='border: 0; border-top: 1px solid #ddd; margin: 15px 0;'>
            
            <p style='margin: 5px 0;'>Original Price: <strike style='color: #999;'>" . $original_price_str . "</strike></p>
            <p style='margin: 5px 0; font-size: 20px;'><b>Your Price: <span style='color: #27ae60;'>" . $final_price_str . "</span></b></p>
            <p style='margin: 5px 0; color: #16a085; font-weight: bold;'>(You save " . $discount_type . "!)" . $expiry_html . "</p>
        </div>
        
        <p>Book your appointment today and don't miss out on these incredible savings! Reply to this email or call us to reserve your spot.</p>
        <p>Best regards,<br>The Team at " . htmlspecialchars($company_name) . "</p>
    </div>
    ";
    
    return _dispatchEmail($to_email, $subject, $body, true); // true to force passing as block HTML instead of nl2br wrapper
}
?>
