<?php
// Main entry point
session_start();
require_once 'config/db.php';

// Simple check if user is logged in
if (isset($_SESSION['user_id'])) {
    header("Location: modules/dashboard/index.php");
    exit;
} else {
    header("Location: modules/auth/login.php");
    exit;
}
?>
