<?php
require_once '../../includes/auth_check.php';
require_once '../../config/db.php';

header('Content-Type: application/json');

$start = $_GET['start'] ?? null;
$end = $_GET['end'] ?? null;

$sql = "SELECT b.id, b.booking_number, b.booking_date, b.booking_time, b.status, c.name as customer_name 
        FROM bookings b 
        JOIN customers c ON b.customer_id = c.id 
        WHERE b.status != 'Cancelled'";

if ($start && $end) {
    $sql .= " AND b.booking_date BETWEEN :start AND :end";
}

$stmt = $pdo->prepare($sql);
$params = [];
if ($start && $end) {
    $params['start'] = $start;
    $params['end'] = $end;
}
$stmt->execute($params);
$bookings = $stmt->fetchAll();

$events = [];
foreach ($bookings as $b) {
    $color = match($b['status']) {
        'Pending' => '#ffc107',
        'Confirmed' => '#0d6efd',
        'Completed' => '#198754',
        default => '#6c757d'
    };
    
    $events[] = [
        'id' => $b['id'],
        'title' => $b['customer_name'] . " (" . $b['booking_number'] . ")",
        'start' => $b['booking_date'] . "T" . $b['booking_time'],
        'color' => $color,
        'url' => 'edit.php?id=' . $b['id']
    ];
}

echo json_encode($events);
