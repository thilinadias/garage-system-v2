<?php
require_once '../../includes/auth_check.php';
require_once '../../config/db.php';
require_once '../../includes/functions.php';

checkRole(['admin']);

if(!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$id = $_GET['id'];
$customer_stmt = $pdo->prepare("SELECT * FROM customers WHERE id = :id");
$customer_stmt->execute(['id' => $id]);
$customer = $customer_stmt->fetch();

if(!$customer) {
    die("Customer not found.");
}

// Handle Add Vehicle
if(isset($_POST['add_vehicle'])) {
    $model_id = $_POST['model_id'];
    $license_plate = trim($_POST['license_plate']);
    $color = trim($_POST['color']);
    
    if(!empty($model_id) && !empty($license_plate)) {
        $stmt = $pdo->prepare("INSERT INTO customer_vehicles (customer_id, model_id, license_plate, color) VALUES (:cid, :mid, :lp, :col)");
        if($stmt->execute(['cid' => $id, 'mid' => $model_id, 'lp' => $license_plate, 'col' => $color])) {
            $vehicle_id = $pdo->lastInsertId();
            logAction($pdo, $_SESSION['user_id'], 'Registered Vehicle', 'customer_vehicles', $vehicle_id, "Customer ID: $id, Plate: $license_plate");
            header("Location: view.php?id=$id&msg=vehicle_added");
            exit;
        }
 else {
            $error = "Error adding vehicle.";
        }
    }
}

// Fetch Customer Vehicles
$vehicles = $pdo->prepare("SELECT cv.*, vm.name as model_name, vb.name as brand_name 
                           FROM customer_vehicles cv 
                           JOIN vehicle_models vm ON cv.model_id = vm.id 
                           JOIN vehicle_brands vb ON vm.brand_id = vb.id 
                           WHERE cv.customer_id = :id");
$vehicles->execute(['id' => $id]);
$vehicle_list = $vehicles->fetchAll();

// Fetch Models for Dropdown
$models = $pdo->query("SELECT m.id, m.name, b.name as brand_name FROM vehicle_models m JOIN vehicle_brands b ON m.brand_id = b.id ORDER BY b.name, m.name")->fetchAll();

// Fetch Job History
$jobs = $pdo->prepare("SELECT * FROM job_cards WHERE customer_id = :id ORDER BY created_at DESC");
$jobs->execute(['id' => $id]);
$job_list = $jobs->fetchAll();

$invoices = $pdo->prepare("SELECT * FROM invoices WHERE customer_id = :id ORDER BY invoice_date DESC");
$invoices->execute(['id' => $id]);
$invoice_list = $invoices->fetchAll();

// Fetch Bookings
$bookings = $pdo->prepare("SELECT b.*, cv.license_plate, vm.name as model_name 
                          FROM bookings b 
                          JOIN customer_vehicles cv ON b.vehicle_id = cv.id 
                          JOIN vehicle_models vm ON cv.model_id = vm.id 
                          WHERE b.customer_id = :id 
                          ORDER BY b.booking_date DESC, b.booking_time DESC");
$bookings->execute(['id' => $id]);
$booking_list = $bookings->fetchAll();

require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';

?>


<div class="row mb-4">
    <div class="col-md-12 d-flex justify-content-between align-items-center">
        <h2>Customer Profile: <?php echo htmlspecialchars($customer['name']); ?></h2>
        <a href="index.php" class="btn btn-secondary">Back to List</a>
    </div>
</div>

<div class="row">
    <!-- Profile Info -->
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Details</h5>
            </div>
            <div class="card-body">
                <p><strong>Phone:</strong> <?php echo htmlspecialchars($customer['phone'] ?? ''); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($customer['email'] ?? ''); ?></p>
                <p><strong>NIC:</strong> <?php echo htmlspecialchars($customer['nic'] ?? ''); ?></p>
                <p><strong>Address:</strong> <?php echo htmlspecialchars($customer['address'] ?? ''); ?></p>
                <a href="edit.php?id=<?php echo $customer['id']; ?>" class="btn btn-outline-primary btn-sm w-100">Edit Details</a>
            </div>
        </div>
        
        <!-- Add Vehicle Form -->
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">Register Vehicle</h5>
            </div>
            <div class="card-body">
                <form action="" method="post">
                    <div class="mb-2">
                        <label>Vehicle Model</label>
                        <select name="model_id" class="form-select" required>
                            <option value="">Select Model</option>
                            <?php foreach($models as $m): ?>
                                <option value="<?php echo $m['id']; ?>"><?php echo $m['brand_name'] . ' - ' . $m['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label>License Plate</label>
                        <input type="text" name="license_plate" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label>Color</label>
                        <input type="text" name="color" class="form-control">
                    </div>
                    <button type="submit" name="add_vehicle" class="btn btn-success w-100">Add Vehicle</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Related Data -->
    <div class="col-md-8">
        <!-- Vehicles List -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Registered Vehicles</h5>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Brand/Model</th>
                            <th>License Plate</th>
                            <th>Color</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($vehicle_list as $v): ?>
                        <tr>
                            <td><?php echo $v['brand_name'] . ' ' . $v['model_name']; ?></td>
                            <td><?php echo $v['license_plate']; ?></td>
                            <td><?php echo $v['color']; ?></td>
                            <td>
                                <a href="delete_vehicle.php?id=<?php echo $v['id']; ?>&customer_id=<?php echo $id; ?>" 
                                   class="btn btn-sm btn-outline-danger" 
                                   onclick="return confirm('Are you sure you want to delete this vehicle? All related job history for this vehicle will also be removed.')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($vehicle_list)) echo "<tr><td colspan='4'>No vehicles registered.</td></tr>"; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Upcoming Bookings -->
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">Upcoming / Recent Bookings</h5>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Ref #</th>
                            <th>Date & Time</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                         <?php foreach($booking_list as $bk): ?>
                        <tr>
                            <td><?php echo $bk['booking_number']; ?></td>
                            <td>
                                <?php echo $bk['booking_date']; ?><br>
                                <small class="text-muted"><?php echo date('h:i A', strtotime($bk['booking_time'])); ?></small>
                            </td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo match($bk['status']) {
                                        'Pending' => 'warning',
                                        'Confirmed' => 'primary',
                                        'Completed' => 'success',
                                        'Cancelled' => 'danger',
                                        default => 'secondary'
                                    };
                                ?>"><?php echo $bk['status']; ?></span>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="../bookings/edit.php?id=<?php echo $bk['id']; ?>" class="btn btn-xs btn-outline-primary"><i class="fas fa-edit"></i></a>
                                    <?php if($bk['status'] != 'Completed' && $bk['status'] != 'Cancelled'): ?>
                                        <a href="../bookings/check_in.php?id=<?php echo $bk['id']; ?>" class="btn btn-xs btn-success text-white"><i class="fas fa-sign-in-alt"></i></a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                         <?php if(empty($booking_list)) echo "<tr><td colspan='4'>No bookings found.</td></tr>"; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Job History -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Job History</h5>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Job #</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                         <?php foreach($job_list as $j): ?>
                        <tr>
                            <td><?php echo $j['job_number']; ?></td>
                            <td><?php echo $j['status']; ?></td>
                            <td><?php echo date('Y-m-d H:i', strtotime($j['created_at'])); ?></td>
                            <td><a href="../job_card/view.php?id=<?php echo $j['id']; ?>" class="btn btn-xs btn-info">View</a></td>
                        </tr>
                        <?php endforeach; ?>
                         <?php if(empty($job_list)) echo "<tr><td colspan='4'>No job history.</td></tr>"; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Invoice History -->
         <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Invoices</h5>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Invoice #</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                         <?php foreach($invoice_list as $inv): ?>
                        <tr>
                            <td><?php echo $inv['invoice_number']; ?></td>
                            <td><?php echo formatCurrency($pdo, $inv['total_amount']); ?></td>
                            <td><?php echo $inv['status']; ?></td>
                            <td><?php echo date('Y-m-d H:i', strtotime($inv['invoice_date'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                         <?php if(empty($invoice_list)) echo "<tr><td colspan='4'>No invoices found.</td></tr>"; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
