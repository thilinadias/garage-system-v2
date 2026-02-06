<?php
require_once '../../includes/auth_check.php';
require_once '../../config/db.php';

checkRole(['admin']);

$type = $_GET['type'] ?? 'revenue';
$start = $_GET['start'] ?? date('Y-m-01');
$end = $_GET['end'] ?? date('Y-m-d');

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="report_' . $type . '_' . date('Ymd') . '.csv"');

$output = fopen('php://output', 'w');

if($type == 'revenue') {
    fputcsv($output, ['Invoice ID', 'Date', 'Customer', 'Subtotal', 'Tax', 'Total', 'Status']);
    
    $sql = "SELECT i.invoice_number, i.invoice_date, c.name, i.subtotal, i.tax_amount, i.total_amount, i.status 
            FROM invoices i 
            JOIN customers c ON i.customer_id = c.id 
            WHERE i.invoice_date BETWEEN :start AND :end";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['start' => $start . " 00:00:00", 'end' => $end . " 23:59:59"]);
    
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, $row);
    }
}

fclose($output);
exit;
?>
