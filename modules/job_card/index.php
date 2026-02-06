<?php
require_once '../../includes/auth_check.php';
require_once '../../config/db.php';
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';

$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

// Filters
$status = $_GET['status'] ?? 'All';
$search = $_GET['search'] ?? '';

$where = "WHERE 1=1";
$params = [];

if($role == 'technician') {
    $where .= " AND technician_id = :uid";
    $params['uid'] = $user_id;
}

if($status != 'All') {
    $where .= " AND status = :status";
    $params['status'] = $status;
}

if($search) {
    $where .= " AND (job_number LIKE :search OR description LIKE :search)";
    $params['search'] = "%$search%";
}

$sql = "SELECT j.*, c.name as customer_name, vm.name as vehicle_name, cv.license_plate 
        FROM job_cards j 
        JOIN customers c ON j.customer_id = c.id
        JOIN customer_vehicles cv ON j.vehicle_id = cv.id
        JOIN vehicle_models vm ON cv.model_id = vm.id
        $where 
        ORDER BY j.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$jobs = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Job Cards</h2>
    <?php if($role == 'admin'): ?>
    <a href="create.php" class="btn btn-primary"><i class="fas fa-plus me-2"></i> New Job Card</a>
    <?php endif; ?>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form action="" method="get" class="row g-3">
            <div class="col-md-3">
                 <select name="status" class="form-select">
                    <option value="All">All Status</option>
                    <option value="Open" <?php echo $status=='Open'?'selected':''; ?>>Open</option>
                    <option value="In Progress" <?php echo $status=='In Progress'?'selected':''; ?>>In Progress</option>
                    <option value="Completed" <?php echo $status=='Completed'?'selected':''; ?>>Completed</option>
                    <option value="Delivered" <?php echo $status=='Delivered'?'selected':''; ?>>Delivered</option>
                </select>
            </div>
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="Search Job # or Description" value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-secondary w-100">Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Job #</th>
                        <th>Customer</th>
                        <th>Vehicle</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($jobs as $job): ?>
                    <tr>
                        <td><strong><?php echo $job['job_number']; ?></strong></td>
                        <td><?php echo htmlspecialchars($job['customer_name']); ?></td>
                        <td>
                            <?php echo htmlspecialchars($job['vehicle_name']); ?>
                            <br><small class="text-muted"><?php echo htmlspecialchars($job['license_plate']); ?></small>
                        </td>
                        <td>
                            <?php 
                            $badge = match($job['status']) {
                                'Open' => 'secondary',
                                'In Progress' => 'primary',
                                'Completed' => 'success',
                                'Delivered' => 'dark',
                                default => 'light'
                            };
                            ?>
                            <span class="badge bg-<?php echo $badge; ?>"><?php echo $job['status']; ?></span>
                        </td>
                        <td><?php echo date('M d, Y H:i', strtotime($job['created_at'])); ?></td>
                        <td>
                            <a href="view.php?id=<?php echo $job['id']; ?>" class="btn btn-sm btn-info text-white">View</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
