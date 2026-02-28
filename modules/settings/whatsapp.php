<?php
// c:\xampp\htdocs\garage-system-v2\modules\settings\whatsapp.php
require_once '../../includes/auth_check.php';
require_once '../../config/db.php';
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
require_once '../../includes/functions.php';

checkRole(['admin']);

$error = '';
$success = '';

// Fetch existing WhatsApp settings
$stmt = $pdo->query("SELECT * FROM whatsapp_settings LIMIT 1");
$wa_settings = $stmt->fetch();

if (!$wa_settings) {
    $pdo->exec("INSERT INTO `whatsapp_settings` (`id`) VALUES (1)");
    $stmt = $pdo->query("SELECT * FROM whatsapp_settings LIMIT 1");
    $wa_settings = $stmt->fetch();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $api_url = trim($_POST['api_url']);
    $phone_number_id = trim($_POST['phone_number_id']);
    $access_token = trim($_POST['access_token']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    $sql = "UPDATE whatsapp_settings SET 
            api_url = :api_url, 
            phone_number_id = :phone_number_id, 
            access_token = :access_token, 
            is_active = :is_active 
            WHERE id = 1";
            
    $stmt = $pdo->prepare($sql);
    $res = $stmt->execute([
        'api_url' => $api_url,
        'phone_number_id' => $phone_number_id,
        'access_token' => $access_token,
        'is_active' => $is_active
    ]);
    
    if($res) {
        logAction($pdo, $_SESSION['user_id'], 'Updated Settings', 'whatsapp_settings', 1, "WhatsApp settings modified.");
        $success = "WhatsApp config updated successfully.";
        // Refresh data
        $stmt = $pdo->query("SELECT * FROM whatsapp_settings LIMIT 1");
        $wa_settings = $stmt->fetch();
    } else {
        $error = "Database error.";
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div class="d-flex align-items-center gap-3">
        <a href="index.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left"></i></a>
        <h2 class="mb-0">WhatsApp Business API Configuration</h2>
    </div>
</div>

<?php if($error): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>
<?php if($success): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4 shadow-sm border-0">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fab fa-whatsapp me-2"></i> Meta API Connection</h5>
            </div>
            <div class="card-body bg-light">
                <p class="text-muted mb-4">Integrate the official Meta WhatsApp Business API to automatically send booking confirmations, repair updates, and promotional offers directly to your customers' phones.</p>

                <form action="" method="post">
                    <div class="form-check form-switch mb-4 fs-5 p-3 rounded <?php echo $wa_settings['is_active'] ? 'bg-success text-white' : 'bg-white border'; ?>">
                        <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" <?php echo $wa_settings['is_active'] ? 'checked' : ''; ?> style="transform: scale(1.5); margin-right: 15px; margin-left: 0;">
                        <label class="form-check-label fw-bold" for="is_active">Enable Automated WhatsApp Notifications</label>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Graph API URL</label>
                        <input type="text" name="api_url" class="form-control" value="<?php echo htmlspecialchars($wa_settings['api_url'] ?? 'https://graph.facebook.com/v19.0/'); ?>" required>
                        <div class="form-text">Default: <code>https://graph.facebook.com/v19.0/</code></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Phone Number ID</label>
                        <input type="text" name="phone_number_id" class="form-control font-monospace" value="<?php echo htmlspecialchars($wa_settings['phone_number_id'] ?? ''); ?>" placeholder="e.g. 101234567890123">
                        <div class="form-text">Found in your Meta App Dashboard under WhatsApp > API Setup.</div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Permanent Access Token</label>
                        <input type="password" name="access_token" class="form-control font-monospace" value="<?php echo htmlspecialchars($wa_settings['access_token'] ?? ''); ?>" placeholder="EAA...">
                        <div class="form-text text-danger"><i class="fas fa-exclamation-triangle"></i> Keep this token completely secret! Do not share it.</div>
                    </div>

                    <hr>

                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-success px-4 py-2 fw-bold"><i class="fas fa-save me-2"></i> Save WhatsApp Configuration</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-dark text-white">
                <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i> How it works</h6>
            </div>
            <div class="card-body">
                <p>When WhatsApp Notifications are <strong>Enabled</strong>, the system will automatically mirror your existing email alerts:</p>
                <ul class="ps-3 mb-0 text-muted small">
                    <li class="mb-2"><strong>New Bookings:</strong> Customer receives instant confirmation.</li>
                    <li class="mb-2"><strong>Job Completion:</strong> "Vehicle Ready" alert is dispatched.</li>
                    <li class="mb-2"><strong>Special Offers:</strong> Whenever you hit "Broadcast Offer" in the Services module, a WhatsApp message containing the discount is blasted!</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
