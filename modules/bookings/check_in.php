<?php
require_once '../../includes/auth_check.php';
require_once '../../config/db.php';
require_once '../../includes/functions.php';

$id = $_GET['id'] ?? null;
if(!$id) {
    header("Location: index.php");
    exit;
}

// Fetch booking data
$stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ?");
$stmt->execute([$id]);
$b = $stmt->fetch();

if(!$b) die("Booking not found.");

if($b['status'] == 'Completed') {
    header("Location: index.php?error=This booking has already been checked-in.");
    exit;
}

// Logic: Create a new Job Card from this booking
$job_number = "JOB-" . date('ym') . "-" . rand(1000, 9999);

try {
    $pdo->beginTransaction();

    // 1. Create Job Card
    $stmt = $pdo->prepare("INSERT INTO job_cards (job_number, customer_id, vehicle_id, booking_id, technician_id, description, status) 
                           VALUES (:jn, :cid, :vid, :bid, :tid, :desc, 'Open')");
    $stmt->execute([
        'jn' => $job_number,
        'cid' => $b['customer_id'],
        'vid' => $b['vehicle_id'],
        'bid' => $b['id'],
        'tid' => $b['technician_id'],
        'desc' => "BOOKING REF: " . $b['booking_number'] . "\n" . $b['description']
    ]);
    
    $job_id = $pdo->lastInsertId();

    // 2. Mark Booking as Completed
    $stmt = $pdo->prepare("UPDATE bookings SET status = 'Completed' WHERE id = ?");
    $stmt->execute([$id]);

    logAction($pdo, $_SESSION['user_id'], 'Booking Check-in', 'bookings', $id, "Booking $b[booking_number] converted to Job Card $job_number");

    $pdo->commit();

    header("Location: ../job_card/view.php?id=$job_id&success=Booking checked-in successfully. Job Card created.");
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    die("Error during check-in: " . $e->getMessage());
}
