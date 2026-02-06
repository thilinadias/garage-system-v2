<?php
require_once '../../includes/auth_check.php';
require_once '../../config/db.php';
require_once '../../includes/functions.php';


checkRole(['admin']);

if(isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // Prevent self-deletion
    if($id == $_SESSION['user_id']) {
        header("Location: index.php?error=cannot_delete_self");
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
    if($stmt->execute(['id' => $id])) {
        logAction($pdo, $_SESSION['user_id'], 'Deleted User', 'users', $id);
        header("Location: index.php?msg=deleted");

    } else {
        header("Location: index.php?error=delete_failed");
    }
} else {
    header("Location: index.php");
}
?>
