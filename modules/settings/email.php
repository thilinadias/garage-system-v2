<?php
// c:\xampp\htdocs\garage-system-v2\modules\settings\email.php
require_once '../../includes/auth_check.php';
require_once '../../config/db.php';
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';

checkRole(['admin']);

$error = '';
$success = '';

// Fetch existing email settings
$stmt = $pdo->query("SELECT * FROM email_settings LIMIT 1");
$email_settings = $stmt->fetch();

if (!$email_settings) {
    $pdo->exec("INSERT INTO `email_settings` (`id`) VALUES (1)");
    $stmt = $pdo->query("SELECT * FROM email_settings LIMIT 1");
    $email_settings = $stmt->fetch();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $smtp_host = trim($_POST['smtp_host']);
    $smtp_username = trim($_POST['smtp_username']);
    $smtp_password = trim($_POST['smtp_password']);
    $smtp_port = $_POST['smtp_port'];
    $smtp_secure = $_POST['smtp_secure'];
    $from_email = trim($_POST['from_email']);
    $from_name = trim($_POST['from_name']);
    
    $welcome_subject = trim($_POST['welcome_subject']);
    $welcome_body = trim($_POST['welcome_body']);
    $booking_subject = trim($_POST['booking_subject']);
    $booking_body = trim($_POST['booking_body']);
    $service_end_subject = trim($_POST['service_end_subject']);
    $service_end_body = trim($_POST['service_end_body']);

    // Handle File Upload for Email Logo
    $logo_path = $email_settings['email_logo'] ?? null;
    if(isset($_FILES['email_logo']) && $_FILES['email_logo']['error'] == 0) {
        $target_dir = "../../assets/uploads/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $file_extension = pathinfo($_FILES["email_logo"]["name"], PATHINFO_EXTENSION);
        $new_filename = "email_logo_" . time() . "." . $file_extension;
        $target_file = $target_dir . $new_filename;
        
        if (move_uploaded_file($_FILES["email_logo"]["tmp_name"], $target_file)) {
            $logo_path = "assets/uploads/" . $new_filename;
        } else {
            $error = "Error uploading email logo.";
        }
    }

    if(!$error) {
        $sql = "UPDATE email_settings SET 
                smtp_host = :smtp_host, 
                smtp_username = :smtp_username, 
                smtp_password = :smtp_password, 
                smtp_port = :smtp_port, 
                smtp_secure = :smtp_secure, 
                from_email = :from_email, 
                from_name = :from_name, 
                welcome_subject = :welcome_subject, 
                welcome_body = :welcome_body, 
                booking_subject = :booking_subject, 
                booking_body = :booking_body, 
                service_end_subject = :service_end_subject, 
                service_end_body = :service_end_body, 
                email_logo = :email_logo 
                WHERE id = 1";
                
        $stmt = $pdo->prepare($sql);
        $res = $stmt->execute([
            'smtp_host' => $smtp_host,
            'smtp_username' => $smtp_username,
            'smtp_password' => $smtp_password,
            'smtp_port' => $smtp_port,
            'smtp_secure' => $smtp_secure,
            'from_email' => $from_email,
            'from_name' => $from_name,
            'welcome_subject' => $welcome_subject,
            'welcome_body' => $welcome_body,
            'booking_subject' => $booking_subject,
            'booking_body' => $booking_body,
            'service_end_subject' => $service_end_subject,
            'service_end_body' => $service_end_body,
            'email_logo' => $logo_path
        ]);
        
        if($res) {
            $success = "Email Settings updated successfully.";
            // Refresh data
            $stmt = $pdo->query("SELECT * FROM email_settings LIMIT 1");
            $email_settings = $stmt->fetch();
        } else {
            $error = "Database error.";
        }
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div class="d-flex align-items-center gap-3">
        <a href="index.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left"></i></a>
        <h2 class="mb-0">Email Configuration & Templates</h2>
    </div>
</div>

<?php if($error): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>
<?php if($success): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<form action="" method="post" enctype="multipart/form-data">

    <!-- SMTP Configuration -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-server me-2"></i> SMTP Settings</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">SMTP Host</label>
                    <input type="text" name="smtp_host" class="form-control" value="<?php echo htmlspecialchars($email_settings['smtp_host'] ?? 'smtp.gmail.com'); ?>" required>
                </div>
                <div class="col-md-2 mb-3">
                    <label class="form-label">Port</label>
                    <input type="number" name="smtp_port" class="form-control" value="<?php echo htmlspecialchars($email_settings['smtp_port'] ?? '587'); ?>" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Encryption</label>
                    <select name="smtp_secure" class="form-select">
                        <option value="tls" <?php echo ($email_settings['smtp_secure'] == 'tls') ? 'selected' : ''; ?>>TLS</option>
                        <option value="ssl" <?php echo ($email_settings['smtp_secure'] == 'ssl') ? 'selected' : ''; ?>>SSL</option>
                    </select>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">SMTP Username (Email)</label>
                    <input type="text" name="smtp_username" class="form-control" value="<?php echo htmlspecialchars($email_settings['smtp_username'] ?? ''); ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">SMTP Password (App Password)</label>
                    <input type="password" name="smtp_password" class="form-control" value="<?php echo htmlspecialchars($email_settings['smtp_password'] ?? ''); ?>" required>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Sender Name ("From" Name)</label>
                    <input type="text" name="from_name" class="form-control" value="<?php echo htmlspecialchars($email_settings['from_name'] ?? 'Garage System'); ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Sender Email ("From" Email)</label>
                    <input type="email" name="from_email" class="form-control" value="<?php echo htmlspecialchars($email_settings['from_email'] ?? ''); ?>" required>
                </div>
            </div>
        </div>
    </div>

    <!-- Email Branding -->
    <div class="card mb-4">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0"><i class="fas fa-paint-brush me-2"></i> Email Branding</h5>
        </div>
        <div class="card-body text-center">
            <div class="mb-3">
                <label class="form-label d-block">Header Logo (Appears at the top of all emails)</label>
                <?php 
                $logo_filename = basename($email_settings['email_logo'] ?? '');
                if(!empty($logo_filename)): ?>
                    <div class="bg-light p-3 border rounded d-inline-block mb-3">
                        <img src="../../assets/uploads/<?php echo $logo_filename; ?>" style="max-height: 80px;">
                    </div>
                <?php else: ?>
                    <p class="text-muted">No specific email logo set. Standard text will be used.</p>
                <?php endif; ?>
                <input type="file" name="email_logo" class="form-control w-50 mx-auto">
            </div>
        </div>
    </div>

    <!-- Email Templates -->
    <div class="card mb-4">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0"><i class="fas fa-file-alt me-2"></i> Message Templates</h5>
        </div>
        <div class="card-body">
            
            <!-- Welcome Email -->
            <h6 class="border-bottom pb-2 mb-3 fw-bold">1. New Customer Welcome Email</h6>
            <div class="mb-3">
                <label class="form-label">Email Subject</label>
                <input type="text" name="welcome_subject" class="form-control" value="<?php echo htmlspecialchars($email_settings['welcome_subject'] ?? 'Welcome to our Garage!'); ?>">
            </div>
            <div class="mb-4">
                <label class="form-label">Message Body</label>
                <div class="form-text mb-2">Use <code>{CUSTOMER_NAME}</code> to dynamically insert the customer's name.</div>
                <textarea name="welcome_body" class="form-control" rows="4"><?php echo htmlspecialchars($email_settings['welcome_body'] ?? "Dear {CUSTOMER_NAME},\n\nWelcome to our Garage! We are thrilled to have you as a new customer.\n\nYou can now easily book services, track your vehicle's repair progress, and view invoices.\n\nThank you for choosing us!"); ?></textarea>
            </div>

            <!-- Booking Confirmation -->
            <h6 class="border-bottom pb-2 mb-3 fw-bold">2. Booking Confirmation</h6>
            <div class="mb-3">
                <label class="form-label">Email Subject</label>
                <input type="text" name="booking_subject" class="form-control" value="<?php echo htmlspecialchars($email_settings['booking_subject'] ?? 'Your Booking Confirmation'); ?>">
            </div>
            <div class="mb-4">
                <label class="form-label">Message Body</label>
                <div class="form-text mb-2">Available variables: <code>{CUSTOMER_NAME}</code>, <code>{BOOKING_REF}</code>, <code>{BOOKING_DATE}</code>, <code>{BOOKING_TIME}</code></div>
                <textarea name="booking_body" class="form-control" rows="4"><?php echo htmlspecialchars($email_settings['booking_body'] ?? "Dear {CUSTOMER_NAME},\n\nYour booking has been successfully confirmed!\n\nBooking Reference: #{BOOKING_REF}\nDate: {BOOKING_DATE}\nTime: {BOOKING_TIME}\n\nPlease arrive 10 minutes prior to your scheduled time.\n\nThank you!"); ?></textarea>
            </div>

            <!-- Service End Notice -->
            <h6 class="border-bottom pb-2 mb-3 fw-bold">3. Service Completed Notice</h6>
            <div class="mb-3">
                <label class="form-label">Email Subject</label>
                <input type="text" name="service_end_subject" class="form-control" value="<?php echo htmlspecialchars($email_settings['service_end_subject'] ?? 'Your Vehicle is Ready!'); ?>">
            </div>
            <div class="mb-4">
                <label class="form-label">Message Body</label>
                <div class="form-text mb-2">Available variables: <code>{CUSTOMER_NAME}</code>, <code>{JOB_NUMBER}</code>, <code>{NOTES}</code></div>
                <textarea name="service_end_body" class="form-control" rows="4"><?php echo htmlspecialchars($email_settings['service_end_body'] ?? "Dear {CUSTOMER_NAME},\n\nGood news! The service for your vehicle (Job #{JOB_NUMBER}) is now complete and ready for pickup.\n\nTechnician Notes: {NOTES}\n\nWe look forward to seeing you soon."); ?></textarea>
            </div>

        </div>
    </div>

    <!-- Final Save -->
    <div class="card mb-4">
        <div class="card-body d-flex justify-content-end">
            <button type="submit" class="btn btn-primary px-4 py-2 fw-bold"><i class="fas fa-save me-2"></i> Save All Settings</button>
        </div>
    </div>

</form>

<?php require_once '../../includes/footer.php'; ?>
