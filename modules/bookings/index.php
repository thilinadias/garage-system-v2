<?php
require_once '../../includes/auth_check.php';
require_once '../../config/db.php';
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
require_once '../../includes/functions.php';

$search = $_GET['search'] ?? '';
$where = "WHERE 1=1";
$params = [];

if($search) {
    $where .= " AND (b.booking_number LIKE :s OR c.name LIKE :s OR cv.license_plate LIKE :s)";
    $params['s'] = "%$search%";
}

$sql = "SELECT b.*, c.name as customer_name, cv.license_plate, vm.name as model_name, u.name as tech_name 
        FROM bookings b 
        JOIN customers c ON b.customer_id = c.id 
        JOIN customer_vehicles cv ON b.vehicle_id = cv.id 
        JOIN vehicle_models vm ON cv.model_id = vm.id 
        LEFT JOIN users u ON b.technician_id = u.id 
        $where 
        ORDER BY b.booking_date DESC, b.booking_time DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Bookings / Appointments</h2>
    <a href="add.php" class="btn btn-primary"><i class="fas fa-plus me-2"></i> New Booking</a>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form action="" method="get" class="row g-2">
            <div class="col-md-10">
                <input type="text" name="search" class="form-control" placeholder="Search by Booking #, Customer, or Plate..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-secondary w-100">Search</button>
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
                        <th>Booking #</th>
                        <th>Customer / Vehicle</th>
                        <th>Date & Time</th>
                        <th>Technician</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bookings as $b): ?>
                    <tr>
                        <td><strong><?php echo $b['booking_number']; ?></strong></td>
                        <td>
                            <?php echo htmlspecialchars($b['customer_name']); ?><br>
                            <small class="text-muted"><?php echo htmlspecialchars($b['model_name']); ?> (<?php echo $b['license_plate']; ?>)</small>
                        </td>
                        <td>
                            <?php echo $b['booking_date']; ?><br>
                            <small><?php echo date('h:i A', strtotime($b['booking_time'])); ?></small>
                        </td>
                        <td><?php echo $b['tech_name'] ?? '<span class="text-muted">Not Assigned</span>'; ?></td>
                        <td>
                            <?php 
                            $badge = match($b['status']) {
                                'Pending' => 'warning',
                                'Confirmed' => 'primary',
                                'Cancelled' => 'danger',
                                'Completed' => 'success',
                                default => 'secondary'
                            };
                            ?>
                            <span class="badge bg-<?php echo $badge; ?>"><?php echo $b['status']; ?></span>
                        </td>
                        <td>
                            <div class="btn-group">
                                <a href="edit.php?id=<?php echo $b['id']; ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="fas fa-edit"></i></a>
                                <?php if($b['status'] != 'Completed' && $b['status'] != 'Cancelled'): ?>
                                    <a href="check_in.php?id=<?php echo $b['id']; ?>" class="btn btn-sm btn-success" title="Check-in to Job"><i class="fas fa-sign-in-alt"></i></a>
                                <?php endif; ?>
                                <a href="delete.php?id=<?php echo $b['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Cancel this booking?')" title="Cancel"><i class="fas fa-times"></i></a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(empty($bookings)): ?>
                    <tr><td colspan="6" class="text-center">No bookings found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
