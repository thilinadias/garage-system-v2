<?php
require_once '../../includes/auth_check.php';
require_once '../../config/db.php';

checkRole(['admin']);

// Get all tables
$tables = [];
$stmt = $pdo->query("SHOW TABLES");
while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
    $tables[] = $row[0];
}

$sqlScript = "-- Database Backup \n";
$sqlScript .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
$sqlScript .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

foreach ($tables as $table) {
    // Structure
    $stmt = $pdo->query("SHOW CREATE TABLE $table");
    $row = $stmt->fetch(PDO::FETCH_NUM);
    $sqlScript .= "\n\n" . $row[1] . ";\n\n";
    
    // Data
    $stmt = $pdo->query("SELECT * FROM $table");
    $rowCount = $stmt->rowCount();
    
    if ($rowCount > 0) {
        $sqlScript .= "INSERT INTO $table VALUES ";
        $rows = $stmt->fetchAll(PDO::FETCH_NUM);
        $count = 0;
        foreach($rows as $row) {
             $count++;
             $sqlScript .= "(";
             $comma = "";
             foreach($row as $cell) {
                 $cell = addslashes($cell);
                 $cell = str_replace("\n", "\\n", $cell);
                 $sqlScript .= $comma . "'" . $cell . "'";
                 $comma = ",";
             }
             $sqlScript .= ")";
             if($count < $rowCount) { $sqlScript .= ","; }
        }
        $sqlScript .= ";\n";
    }
}

$sqlScript .= "\nSET FOREIGN_KEY_CHECKS=1;";

// Download
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="backup_garage_' . date('Y-m-d_H-i') . '.sql"');
header('Content-Length: ' . strlen($sqlScript));
echo $sqlScript;
exit;
?>
