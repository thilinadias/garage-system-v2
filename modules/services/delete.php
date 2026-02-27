<?php
// c:\xampp\htdocs\garage-system-v2\modules\services\delete.php
require_once '../../includes/auth_check.php';
require_once '../../config/db.php';
require_once '../../includes/functions.php';

checkRole(['admin']);

$id = $_GET['id'] ?? null;
if($id) {
    try {
        // Fetch to confirm and log
        $stmt = $pdo->prepare("SELECT name FROM services WHERE id = ?");
        $stmt->execute([$id]);
        $service = $stmt->fetch();
        
        if($service) {
            $del = $pdo->prepare("DELETE FROM services WHERE id = ?");
            $del->execute([$id]);
            logAction($pdo, $_SESSION['user_id'], 'Deleted Service', 'services', $id, "Service: {$service['name']} deleted.");
        }
    } catch (PDOException $e) {
        // Handle error if needed (like foreign key constraints)
    }
}

header("Location: index.php?msg=deleted");
exit;
