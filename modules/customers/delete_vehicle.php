<?php
require_once '../../includes/auth_check.php';
require_once '../../config/db.php';
require_once '../../includes/functions.php';

checkRole(['admin']);

$id = $_GET['id'] ?? null;
$customer_id = $_GET['customer_id'] ?? null;

if($id) {
    try {
        // Get vehicle info for logging
        $v_stmt = $pdo->prepare("SELECT license_plate FROM customer_vehicles WHERE id = ?");
        $v_stmt->execute([$id]);
        $vehicle = $v_stmt->fetch();
        $plate = $vehicle ? $vehicle['license_plate'] : "Unknown";

        $stmt = $pdo->prepare("DELETE FROM customer_vehicles WHERE id = ?");
        $stmt->execute([$id]);
        
        logAction($pdo, $_SESSION['user_id'], 'Delete Vehicle', 'customer_vehicles', $id, "Deleted vehicle with plate: $plate");
    } catch (PDOException $e) {
        // Could pass error via session if needed
    }
}

if($customer_id) {
    header("Location: view.php?id=$customer_id&msg=vehicle_deleted");
} else {
    header("Location: index.php");
}
exit;
