<?php
// c:\xampp\htdocs\garage-system-v2\update_photos_schema.php
require_once 'config/db.php';

try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `job_card_photos` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `job_id` int(11) NOT NULL,
          `photo_path` varchar(255) NOT NULL,
          `caption` varchar(255) DEFAULT NULL,
          `uploaded_by` int(11) NOT NULL,
          `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
          PRIMARY KEY (`id`),
          KEY `job_id` (`job_id`),
          KEY `uploaded_by` (`uploaded_by`),
          CONSTRAINT `job_card_photos_ibfk_1` FOREIGN KEY (`job_id`) REFERENCES `job_cards` (`id`) ON DELETE CASCADE,
          CONSTRAINT `job_card_photos_ibfk_2` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ");

    echo "Successfully added `job_card_photos` table.\n";
} catch (PDOException $e) {
    echo "Error updating database schema: " . $e->getMessage() . "\n";
}
?>
