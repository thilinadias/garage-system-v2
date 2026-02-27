<?php
// c:\xampp\htdocs\garage-system-v2\modules\services\send_offer.php
require_once '../../includes/auth_check.php';
require_once '../../config/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/notifications.php';

checkRole(['admin']);

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['service_id'])) {
    $service_id = $_POST['service_id'];

    try {
        // Fetch the service details
        $stmt = $pdo->prepare("SELECT * FROM services WHERE id = :id");
        $stmt->execute(['id' => $service_id]);
        $service = $stmt->fetch();

        if (!$service) {
            header("Location: index.php?error=Invalid Service");
            exit;
        }

        // Validate that an offer actually exists conceptually
        $price_calc = calculateServicePrice($service);
        if (!$price_calc['is_discounted']) {
            header("Location: index.php?error=This service does not have an active offer to broadcast.");
            exit;
        }

        // Fetch all customers with a valid email address
        $cust_stmt = $pdo->query("SELECT name, email FROM customers WHERE email IS NOT NULL AND email != ''");
        $customers = $cust_stmt->fetchAll();

        $success_count = 0;
        $fail_count = 0;

        foreach ($customers as $cust) {
            // Attempt dispatch
            if (sendPromotionalOfferEmail($cust['email'], $cust['name'], $service)) {
                $success_count++;
            } else {
                $fail_count++;
            }
        }
        
        logAction($pdo, $_SESSION['user_id'], 'Broadcasted Promotion', 'services', $service_id, "Sent offer to $success_count customers. Failed: $fail_count");

        header("Location: index.php?msg=offer_sent&success=$success_count&failed=$fail_count");
        exit;

    } catch (Exception $e) {
        header("Location: index.php?error=Failed to dispatch mass email: " . urlencode($e->getMessage()));
        exit;
    }
} else {
    header("Location: index.php");
    exit;
}
?>
