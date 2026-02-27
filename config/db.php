<?php
// Configuration for Database Connection

define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'garage_sys');

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    // Set the PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec("SET NAMES 'utf8mb4'");

    // Fetch and set timezone
    $tz_stmt = $pdo->query("SELECT timezone FROM company_profile LIMIT 1");
    if ($tz_stmt) {
        $tz_row = $tz_stmt->fetch();
        if ($tz_row && !empty($tz_row['timezone'])) {
            date_default_timezone_set($tz_row['timezone']);
        }
    }

} catch(PDOException $e) {
    die("ERROR: Could not connect to the database. " . $e->getMessage());
}
?>
