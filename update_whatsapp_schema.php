<?php
// c:\xampp\htdocs\garage-system-v2\update_whatsapp_schema.php
require_once 'config/db.php';

try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `whatsapp_settings` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `api_url` varchar(255) NOT NULL DEFAULT 'https://graph.facebook.com/v19.0/',
          `phone_number_id` varchar(50) DEFAULT NULL,
          `access_token` text DEFAULT NULL,
          `is_active` tinyint(1) NOT NULL DEFAULT 0,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ");

    // Insert default row if none exists
    $stmt = $pdo->query("SELECT COUNT(*) FROM whatsapp_settings");
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO whatsapp_settings (api_url, is_active) VALUES ('https://graph.facebook.com/v19.0/', 0)");
    }

    echo "Successfully added `whatsapp_settings` table.\n";
} catch (PDOException $e) {
    echo "Error updating database schema: " . $e->getMessage() . "\n";
}
?>
