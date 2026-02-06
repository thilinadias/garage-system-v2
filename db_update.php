<?php
require_once 'config/db.php';

echo "<h2>Starting Database Update...</h2>";

$tables = [
    "invoice_items" => "CREATE TABLE `invoice_items` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `invoice_id` int(11) NOT NULL,
        `description` varchar(255) NOT NULL,
        `amount` decimal(10,2) NOT NULL,
        PRIMARY KEY (`id`),
        KEY `invoice_id` (`invoice_id`),
        CONSTRAINT `invoice_items_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;",
    
    "audit_logs" => "CREATE TABLE `audit_logs` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) DEFAULT NULL,
        `action` varchar(255) NOT NULL,
        `table_name` varchar(50) DEFAULT NULL,
        `record_id` int(11) DEFAULT NULL,
        `details` text DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`),
        KEY `user_id` (`user_id`),
        CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;"
];

foreach ($tables as $name => $sql) {
    try {
        $check = $pdo->query("SHOW TABLES LIKE '$name'");
        if ($check->rowCount() == 0) {
            $pdo->exec($sql);
            echo "<p style='color:green;'>✅ Table '$name' created successfully.</p>";
        } else {
            echo "<p style='color:blue;'>ℹ️ Table '$name' already exists.</p>";
        }
    } catch (PDOException $e) {
        echo "<p style='color:red;'>❌ Error creating table '$name': " . $e->getMessage() . "</p>";
    }
}

echo "<h3>Update Complete!</h3>";
echo "<p><a href='modules/dashboard/index.php'>Go to Dashboard</a></p>";
?>
