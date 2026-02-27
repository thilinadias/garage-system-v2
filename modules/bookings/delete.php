<?php
require_once '../../includes/auth_check.php';
require_once '../../config/db.php';
require_once '../../includes/functions.php';

checkRole(['admin']);

$id = $_GET['id'] ?? null;
if($id) {
    try {
        $stmt = $pdo->prepare("UPDATE bookings SET status = 'Cancelled' WHERE id = ?");
        $stmt->execute([$id]);
        logAction($pdo, $_SESSION['user_id'], 'Cancel Booking', 'bookings', $id, "Booking ID $id status changed to Cancelled.");
    } catch (PDOException $e) {
        // Handle error
    }
}

header("Location: index.php");
exit;
