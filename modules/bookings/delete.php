<?php
require_once '../../includes/auth_check.php';
require_once '../../config/db.php';
require_once '../../includes/functions.php';

checkRole(['admin']);

$id = $_GET['id'] ?? null;
if($id) {
    try {
        $stmt = $pdo->prepare("DELETE FROM bookings WHERE id = ?");
        $stmt->execute([$id]);
        logAction($pdo, $_SESSION['user_id'], 'Delete Booking', 'bookings', $id, "Booking ID $id deleted.");
    } catch (PDOException $e) {
        // Handle error
    }
}

header("Location: index.php");
exit;
