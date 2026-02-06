<?php
require_once '../../includes/auth_check.php';
require_once '../../config/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';

checkRole(['admin']);

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Count total
$count_stmt = $pdo->query("SELECT COUNT(*) FROM audit_logs");
$total_records = $count_stmt->fetchColumn();

// Fetch Logs
$sql = "SELECT a.*, u.name as user_name 
        FROM audit_logs a 
        LEFT JOIN users u ON a.user_id = u.id 
        ORDER BY a.created_at DESC 
        LIMIT $limit OFFSET $offset";
$logs = $pdo->query($sql)->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Audit Logs</h2>
    <a href="index.php" class="btn btn-secondary">Back to Settings</a>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover table-sm">
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Table</th>
                        <th>Record ID</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                    <tr>
                        <td class="small text-muted"><?php echo $log['created_at']; ?></td>
                        <td><strong><?php echo h($log['user_name'] ?? 'System'); ?></strong></td>
                        <td><?php echo h($log['action']); ?></td>
                        <td><span class="badge bg-light text-dark"><?php echo h($log['table_name']); ?></span></td>
                        <td><?php echo $log['record_id']; ?></td>
                        <td class="small"><?php echo h($log['details']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="mt-3">
            <?php echo getPagination($total_records, $limit, $page, "audit_log.php?"); ?>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
