<?php
require_once '../../includes/auth_check.php';
require_once '../../config/db.php';
require_once '../../includes/functions.php';

checkRole(['admin']);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['backup_file'])) {
    $file = $_FILES['backup_file'];
    
    if ($file['error'] === UPLOAD_ERR_OK) {
        $sql = file_get_contents($file['tmp_name']);
        
        try {
            $pdo->beginTransaction();
            
            // Temporary disable foreign key checks
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
            
            // Execute the SQL multi-query
            // Note: exec() doesn't handle multi-queries well in some PDO configs.
            // Using a loop or a direct query is better if the file is large.
            // For simplicity in a local environment:
            $pdo->exec($sql);
            
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
            
            $pdo->commit();
            
            logAction($pdo, $_SESSION['user_id'], 'Restored Database', 'database', null, 'File: ' . $file['name']);
            
            header("Location: index.php?msg=restored");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            die("Restore failed: " . $e->getMessage());
        }
    } else {
        die("Upload error.");
    }
} else {
    header("Location: index.php");
    exit;
}
?>
