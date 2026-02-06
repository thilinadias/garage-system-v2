<?php
require_once '../../includes/auth_check.php';
require_once '../../config/db.php';
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
require_once '../../includes/functions.php';


checkRole(['admin']);

$sql = "SELECT i.*, c.name as customer_name, j.job_number 
        FROM invoices i 
        JOIN customers c ON i.customer_id = c.id 
        LEFT JOIN job_cards j ON i.job_id = j.id 
        ORDER BY i.invoice_date DESC";
$invoices = $pdo->query($sql)->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Invoices</h2>
    <a href="create_manual.php" class="btn btn-primary"><i class="fas fa-plus me-2"></i> New Manual Invoice</a>
</div>

<div class="card">
    <div class="card-body">
         <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Invoice #</th>
                        <th>Customer</th>
                        <th>Job Ref</th>
                        <th>Date</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($invoices as $inv): ?>
                    <tr>
                        <td><strong><?php echo $inv['invoice_number']; ?></strong></td>
                        <td><?php echo htmlspecialchars($inv['customer_name']); ?></td>
                        <td><?php echo $inv['job_number'] ? $inv['job_number'] : '<span class="text-muted">Manual</span>'; ?></td>
                        <td><?php echo date('Y-m-d H:i', strtotime($inv['invoice_date'])); ?></td>
                        <td><?php echo formatCurrency($pdo, $inv['total_amount']); ?></td>
                        <td>
                            <?php if($inv['status'] == 'Paid'): ?>
                                <span class="badge bg-success">Paid</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Unpaid</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="view.php?id=<?php echo $inv['id']; ?>" class="btn btn-sm btn-info text-white">View</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
