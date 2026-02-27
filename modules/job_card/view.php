<?php
require_once '../../includes/auth_check.php';
require_once '../../config/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/notifications.php';


$id = $_GET['id'] ?? null;
if(!$id) { header("Location: index.php"); exit; }

$user_role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

// Fetch Job Details initially for logic
$stmt = $pdo->prepare("SELECT j.*, c.name as customer_name, c.email as customer_email, c.phone, vm.name as vehicle_model, cv.license_plate, u.name as tech_name, u.avatar as tech_avatar 
                       FROM job_cards j 
                       JOIN customers c ON j.customer_id = c.id 
                       JOIN customer_vehicles cv ON j.vehicle_id = cv.id 
                       JOIN vehicle_models vm ON cv.model_id = vm.id 
                       LEFT JOIN users u ON j.technician_id = u.id 
                       WHERE j.id = :id");
$stmt->execute(['id' => $id]);
$job = $stmt->fetch();

if(!$job) { die("Job not found."); }

// Permission Check: Technician can only view their own jobs
if($user_role == 'technician' && $job['technician_id'] != $user_id) {
    die("You are not authorized to view this job.");
}

$msg = "";
$error = "";

// Handle Updates (Status, Notes, Labor, Tech Assign)
if(isset($_POST['update_job'])) {
    $status = $_POST['status'];
    $notes = $_POST['technician_notes'];
    $labor = isset($_POST['labor_cost']) ? $_POST['labor_cost'] : $job['labor_cost'];
    $tech_id = isset($_POST['technician_id']) ? $_POST['technician_id'] : $job['technician_id'];
    
    $completed_at = ($status == 'Completed' && $job['status'] != 'Completed') ? date('Y-m-d H:i:s') : $job['completed_at'];

    $sql = "UPDATE job_cards SET status = :st, technician_notes = :notes, labor_cost = :lc, technician_id = :tid, completed_at = :ca WHERE id = :id";
    $upd = $pdo->prepare($sql);
    if($upd->execute(['st' => $status, 'notes' => $notes, 'lc' => $labor, 'tid' => $tech_id, 'ca' => $completed_at, 'id' => $id])) {
        logAction($pdo, $user_id, 'Updated Job Details', 'job_cards', $id, "Status: $status");

        // If status changed to Completed, send Service End email
        if ($status == 'Completed' && $job['status'] != 'Completed' && !empty($job['customer_email'])) {
            sendServiceEndEmail($job['customer_email'], $job['customer_name'], $job['job_number'], $notes);
        }

        header("Location: view.php?id=$id&msg=updated");
        exit;
    }

}

// Handle Add Service
if(isset($_POST['add_service'])) {
    $service_id = $_POST['service_id'];
    
    // Fetch the service details
    $chk = $pdo->prepare("SELECT * FROM services WHERE id = :id");
    $chk->execute(['id' => $service_id]);
    $service_data = $chk->fetch();
    
    if($service_data) {
        $price_calc = calculateServicePrice($service_data);
        
        $ins = $pdo->prepare("INSERT INTO job_card_services 
                              (job_id, service_id, service_name_snapshot, original_price_snapshot, final_price_charged, offer_applied_snapshot) 
                              VALUES (:jid, :sid, :sname, :oprice, :fprice, :offer)");
        $ins->execute([
            'jid' => $id,
            'sid' => $service_id,
            'sname' => $service_data['name'],
            'oprice' => $price_calc['original_price'],
            'fprice' => $price_calc['final_price'],
            'offer' => $price_calc['offer_text']
        ]);
        
        logAction($pdo, $user_id, 'Added Service to Job', 'job_card_services', $pdo->lastInsertId(), "Service: {$service_data['name']}, Job: {$job['job_number']}");
        header("Location: view.php?id=$id&msg=service_added");
        exit;
    }
}

// Handle Add Part
if(isset($_POST['add_part'])) {
    $part_id = $_POST['part_id'];
    $qty = (int)$_POST['qty'];
    
    $chk = $pdo->prepare("SELECT stock_quantity, unit_price, part_name FROM inventory WHERE id = :id");
    $chk->execute(['id' => $part_id]);
    $part_data = $chk->fetch();
    
    if($part_data && $part_data['stock_quantity'] >= $qty) {
        $price = $part_data['unit_price'];
        
        $ins = $pdo->prepare("INSERT INTO job_card_parts (job_id, part_id, quantity, unit_price) VALUES (:jid, :pid, :qty, :price)");
        $ins->execute(['jid' => $id, 'pid' => $part_id, 'qty' => $qty, 'price' => $price]);
        
        $deduct = $pdo->prepare("UPDATE inventory SET stock_quantity = stock_quantity - :qty WHERE id = :id");
        $deduct->execute(['qty' => $qty, 'id' => $part_id]);
        
        $log = $pdo->prepare("INSERT INTO inventory_logs (part_id, change_qty, action_type, remarks) VALUES (:pid, :qty, 'used', :rem)");
        $log->execute(['pid' => $part_id, 'qty' => $qty, 'rem' => "Used in Job " . $job['job_number']]);
        
        logAction($pdo, $user_id, 'Added Part to Job', 'job_card_parts', $pdo->lastInsertId(), "Part: {$part_data['part_name']}, Qty: $qty, Job: {$job['job_number']}");
        header("Location: view.php?id=$id&msg=part_added");
        exit;

    } else {
        $error = "Insufficient stock.";
    }
}

// Handle Delete Service
if(isset($_GET['delete_service'])) {
    $row_id = $_GET['delete_service'];
    
    // Validate it belongs to this job
    $chk = $pdo->prepare("SELECT id FROM job_card_services WHERE id = :id AND job_id = :jid");
    $chk->execute(['id' => $row_id, 'jid' => $id]);
    if($chk->fetch()) {
        $del = $pdo->prepare("DELETE FROM job_card_services WHERE id = :id");
        $del->execute(['id' => $row_id]);
        
        logAction($pdo, $user_id, 'Removed Service from Job', 'job_card_services', $row_id, "Job: {$job['job_number']}");
        header("Location: view.php?id=$id&msg=service_removed");
        exit;
    }
}

// Handle Delete Part
if(isset($_GET['delete_part'])) {
    $row_id = $_GET['delete_part'];
    $get_p = $pdo->prepare("SELECT * FROM job_card_parts WHERE id = :id");
    $get_p->execute(['id' => $row_id]);
    $part_row = $get_p->fetch();
    
    if($part_row) {
        $res = $pdo->prepare("UPDATE inventory SET stock_quantity = stock_quantity + :qty WHERE id = :pid");
        $res->execute(['qty' => $part_row['quantity'], 'pid' => $part_row['part_id']]);
        
        $log = $pdo->prepare("INSERT INTO inventory_logs (part_id, change_qty, action_type, remarks) VALUES (:pid, :qty, 'added', :rem)");
        $log->execute(['pid' => $part_row['part_id'], 'qty' => $part_row['quantity'], 'rem' => "Removed from Job " . $job['job_number']]);

        $del = $pdo->prepare("DELETE FROM job_card_parts WHERE id = :id");
        $del->execute(['id' => $row_id]);
        
        logAction($pdo, $user_id, 'Removed Part from Job', 'job_card_parts', $row_id, "Part: (Removed), Job: {$job['job_number']}");
        header("Location: view.php?id=$id&msg=part_removed");
        exit;

    }
}

// Handle Photo Upload
if(isset($_POST['upload_photo'])) {
    if(isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $caption = trim($_POST['caption']);
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['photo']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if(in_array($ext, $allowed)) {
            $new_name = 'job_' . $id . '_' . time() . '.' . $ext;
            $upload_dir = '../../assets/uploads/job_photos/';
            
            if(move_uploaded_file($_FILES['photo']['tmp_name'], $upload_dir . $new_name)) {
                $ins = $pdo->prepare("INSERT INTO job_card_photos (job_id, photo_path, caption, uploaded_by) VALUES (:jid, :path, :cap, :uby)");
                $ins->execute(['jid' => $id, 'path' => $new_name, 'cap' => $caption, 'uby' => $user_id]);
                
                logAction($pdo, $user_id, 'Uploaded Job Photo', 'job_card_photos', $pdo->lastInsertId(), "Photo: $new_name, Job: {$job['job_number']}");
                header("Location: view.php?id=$id&msg=photo_uploaded");
                exit;
            } else {
                $error = "Failed to move uploaded file.";
            }
        } else {
            $error = "Invalid file type. Only JPG, PNG, and GIF are allowed.";
        }
    } else {
        $error = "No file uploaded or an upload error occurred.";
    }
}

// Handle Photo Delete
if(isset($_GET['delete_photo'])) {
    $row_id = $_GET['delete_photo'];
    $chk = $pdo->prepare("SELECT * FROM job_card_photos WHERE id = :id AND job_id = :jid");
    $chk->execute(['id' => $row_id, 'jid' => $id]);
    $photo = $chk->fetch();
    
    if($photo) {
        $filepath = '../../assets/uploads/job_photos/' . $photo['photo_path'];
        if(file_exists($filepath)) {
            unlink($filepath);
        }
        $del = $pdo->prepare("DELETE FROM job_card_photos WHERE id = :id");
        $del->execute(['id' => $row_id]);
        
        logAction($pdo, $user_id, 'Deleted Job Photo', 'job_card_photos', $row_id, "Job: {$job['job_number']}");
        header("Location: view.php?id=$id&msg=photo_deleted");
        exit;
    }
}

// Fetch Job Services
$srv_stmt = $pdo->prepare("SELECT * FROM job_card_services WHERE job_id = :id");
$srv_stmt->execute(['id' => $id]);
$job_services = $srv_stmt->fetchAll();

// Fetch Job Parts
$parts_stmt = $pdo->prepare("SELECT jcp.*, i.part_name, i.part_number FROM job_card_parts jcp JOIN inventory i ON jcp.part_id = i.id WHERE jcp.job_id = :id");
$parts_stmt->execute(['id' => $id]);
$job_parts = $parts_stmt->fetchAll();

// Fetch Job Photos
$photo_stmt = $pdo->prepare("SELECT p.*, u.name as tech_name FROM job_card_photos p JOIN users u ON p.uploaded_by = u.id WHERE p.job_id = :id ORDER BY p.uploaded_at DESC");
$photo_stmt->execute(['id' => $id]);
$job_photos = $photo_stmt->fetchAll();

// Fetch Vehicle History Photos
$vh_photo_stmt = $pdo->prepare("
    SELECT p.*, j.job_number, j.created_at as job_date 
    FROM job_card_photos p 
    JOIN job_cards j ON p.job_id = j.id 
    WHERE j.vehicle_id = :vid AND j.id != :jid 
    ORDER BY p.uploaded_at DESC
");
$vh_photo_stmt->execute(['vid' => $job['vehicle_id'], 'jid' => $id]);
$history_photos = $vh_photo_stmt->fetchAll();

// Fetch Inventory for dropdown
$inv_list = $pdo->query("SELECT id, part_name, stock_quantity, unit_price FROM inventory WHERE stock_quantity > 0 ORDER BY part_name")->fetchAll();

// Fetch Available Services for dropdown
$sys_services_list = $pdo->query("SELECT * FROM services ORDER BY name")->fetchAll();

// Calculate Totals
$total_services = 0;
foreach($job_services as $js) { $total_services += $js['final_price_charged']; }

$total_parts = 0;
foreach($job_parts as $jp) { $total_parts += ($jp['quantity'] * $jp['unit_price']); }

$total_labor = $job['labor_cost'];
$grand_total = $total_services + $total_parts + $total_labor;

// Fetch Technicians for Admin
if($user_role == 'admin') {
    $techs = $pdo->query("SELECT id, name FROM users WHERE role = 'technician'")->fetchAll();
}

require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>


<div class="d-flex justify-content-between align-items-center mb-3 print-hide">
    <h3>Job Card: <?php echo $job['job_number']; ?></h3>
    <div>
        <?php if($user_role == 'admin' && $job['status'] == 'Completed'): ?>
            <a href="../../modules/invoices/generate.php?job_id=<?php echo $job['id']; ?>" class="btn btn-success"><i class="fas fa-file-invoice-dollar me-2"></i> Generate Invoice</a>
        <?php endif; ?>
        <button onclick="window.print()" class="btn btn-outline-secondary"><i class="fas fa-print"></i></button>
        <a href="index.php" class="btn btn-secondary">Back</a>
    </div>
</div>

<div class="print-only mb-4 text-center">
    <h2><?php echo $job['job_number']; ?> - Job Card</h2>
</div>

<?php if(isset($_GET['msg'])): ?>
    <div class="alert alert-success">Action successful!</div>
<?php endif; ?>
<?php if($error): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<div class="row">
    <!-- Main Details -->
    <div class="col-md-8">
        <!-- Status & Info -->
        <div class="card mb-4">
            <div class="card-header bg-light">
                <h5 class="mb-0">Job Details</h5>
            </div>
            <div class="card-body">
                <form action="" method="post">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Customer:</strong> <?php echo htmlspecialchars($job['customer_name']); ?><br>
                            <strong>Phone:</strong> <?php echo htmlspecialchars($job['phone']); ?><br>
                            <strong>Vehicle:</strong> <?php echo htmlspecialchars($job['vehicle_model']); ?> (<?php echo htmlspecialchars($job['license_plate']); ?>)<br>
                            <strong>Created:</strong> <?php echo date('Y-m-d H:i', strtotime($job['created_at'])); ?>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-2">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select status-<?php echo strtolower(str_replace(' ', '-', $job['status'])); ?>">
                                    <option value="Open" <?php echo $job['status']=='Open'?'selected':''; ?>>Open</option>
                                    <option value="In Progress" <?php echo $job['status']=='In Progress'?'selected':''; ?>>In Progress</option>
                                    <option value="Completed" <?php echo $job['status']=='Completed'?'selected':''; ?>>Completed</option>
                                    <option value="Delivered" <?php echo $job['status']=='Delivered'?'selected':''; ?>>Delivered</option>
                                </select>
                            </div>
                            <?php if($user_role == 'admin'): ?>
                            <div class="mb-2">
                                <label class="form-label">Assigned Technician</label>
                                <select name="technician_id" class="form-select">
                                    <option value="">-- Unassigned --</option>
                                    <?php foreach($techs as $t): ?>
                                        <option value="<?php echo $t['id']; ?>" <?php echo $job['technician_id']==$t['id']?'selected':''; ?>><?php echo htmlspecialchars($t['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php else: ?>
                                <div class="d-flex align-items-center">
                                    <?php if($job['tech_avatar']): ?>
                                        <img src="../../assets/uploads/profiles/<?php echo $job['tech_avatar']; ?>" class="rounded-circle me-2 shadow-sm" style="width: 40px; height: 40px; object-fit: cover;">
                                    <?php endif; ?>
                                    <div>
                                        <small class="text-muted d-block">Technician</small>
                                        <strong><?php echo htmlspecialchars($job['tech_name'] ?? 'Unassigned'); ?></strong>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Issue Description</label>
                        <textarea class="form-control bg-light" readonly rows="2"><?php echo htmlspecialchars($job['description'] ?? ''); ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Technician Notes</label>
                        <textarea name="technician_notes" class="form-control" rows="3" placeholder="Enter findings, work done, etc."><?php echo htmlspecialchars($job['technician_notes'] ?? ''); ?></textarea>
                    </div>

                    <?php if($user_role == 'admin'): ?>
                    <div class="mb-3">
                        <label class="form-label">Labor Cost</label>
                         <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" step="0.01" name="labor_cost" class="form-control" value="<?php echo htmlspecialchars($job['labor_cost'] ?? '0.00'); ?>">
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="text-end">
                        <button type="submit" name="update_job" class="btn btn-primary">Update Job Details</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Services Rendered -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Services Rendered</h5>
            </div>
            <div class="card-body">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Service Name</th>
                            <th>Original Price</th>
                            <th>Applied Offer</th>
                            <th>Final Charge</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($job_services as $js): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($js['service_name_snapshot']); ?></td>
                            <td><?php echo formatCurrency($pdo, $js['original_price_snapshot']); ?></td>
                            <td>
                                <?php if($js['offer_applied_snapshot']): ?>
                                    <span class="badge bg-warning text-dark"><i class="fas fa-tag"></i> <?php echo htmlspecialchars($js['offer_applied_snapshot']); ?></span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td><strong><?php echo formatCurrency($pdo, $js['final_price_charged']); ?></strong></td>
                            <td>
                                <a href="?id=<?php echo $id; ?>&delete_service=<?php echo $js['id']; ?>" class="text-danger" onclick="return confirm('Remove service from job?')"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($job_services)) echo "<tr><td colspan='5' class='text-center'>No services added yet.</td></tr>"; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-end"><strong>Services Total:</strong></td>
                            <td><strong><?php echo formatCurrency($pdo, $total_services); ?></strong></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>

                <!-- Add Service Form -->
                <hr>
                <h6>Add Service</h6>
                <form action="" method="post" class="row g-2">
                    <div class="col-md-10">
                        <select name="service_id" class="form-select" required>
                            <option value="">Select Pre-Defined Service</option>
                             <?php foreach($sys_services_list as $sl): 
                                 $sl_calc = calculateServicePrice($sl);
                             ?>
                                <option value="<?php echo $sl['id']; ?>">
                                    <?php echo htmlspecialchars($sl['name']); ?> 
                                    (<?php echo formatCurrency($pdo, $sl_calc['final_price'], ""); ?>)
                                    <?php echo $sl_calc['is_discounted'] ? " - " . $sl_calc['offer_text'] : ""; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" name="add_service" class="btn btn-outline-primary w-100">Add Service</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Parts Used -->
        <div class="card">
            <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Spare Parts Used</h5>
            </div>
            <div class="card-body">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Part Name</th>
                            <th>Qty</th>
                            <th>Unit Price</th>
                            <th>Total</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($job_parts as $jp): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($jp['part_name']); ?> <small class="text-muted">(<?php echo $jp['part_number']; ?>)</small></td>
                            <td><?php echo $jp['quantity']; ?></td>
                            <td><?php echo formatCurrency($pdo, $jp['unit_price']); ?></td>
                            <td><?php echo formatCurrency($pdo, $jp['quantity'] * $jp['unit_price']); ?></td>
                            <td>
                                <a href="?id=<?php echo $id; ?>&delete_part=<?php echo $jp['id']; ?>" class="text-danger" onclick="return confirm('Remove part? Stock will be restored.')"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($job_parts)) echo "<tr><td colspan='5' class='text-center'>No parts added yet.</td></tr>"; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-end"><strong>Parts Total:</strong></td>
                            <td><strong><?php echo formatCurrency($pdo, $total_parts); ?></strong></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>

                <!-- Add Part Form -->
                <hr>
                <h6>Add Part</h6>
                <form action="" method="post" class="row g-2">
                    <div class="col-md-6">
                        <select name="part_id" class="form-select" required>
                            <option value="">Select Part</option>
                             <?php foreach($inv_list as $inv): ?>
                                <option value="<?php echo $inv['id']; ?>"><?php echo htmlspecialchars($inv['part_name']) . " ($" . $inv['unit_price'] . ") - Stock: " . $inv['stock_quantity']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="number" name="qty" class="form-control" placeholder="Qty" min="1" value="1" required>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" name="add_part" class="btn btn-outline-success w-100">Add</button>
                    </div>
                </form>
            </div>
        </div>
        <!-- Job Photos -->
        <div class="card mb-4 print-hide">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Current Job Photos</h5>
            </div>
            <div class="card-body bg-light">
                <div class="row g-3 mb-3">
                    <?php foreach($job_photos as $p): ?>
                        <div class="col-md-4 col-sm-6">
                            <div class="card h-100 shadow-sm border-0 position-relative">
                                <a href="?id=<?php echo $id; ?>&delete_photo=<?php echo $p['id']; ?>" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-2" style="z-index: 10;" onclick="return confirm('Delete this photo permanently?')"><i class="fas fa-trash"></i></a>
                                <a href="../../assets/uploads/job_photos/<?php echo $p['photo_path']; ?>" target="_blank">
                                    <img src="../../assets/uploads/job_photos/<?php echo $p['photo_path']; ?>" class="card-img-top" style="height: 150px; object-fit: cover;" alt="Job Photo">
                                </a>
                                <div class="card-body p-2 text-center">
                                    <small class="d-block text-muted text-truncate" title="<?php echo htmlspecialchars($p['caption']); ?>"><?php echo htmlspecialchars($p['caption'] ?: 'No Caption'); ?></small>
                                    <small class="text-primary" style="font-size: 0.75rem;">By <?php echo htmlspecialchars($p['tech_name']); ?></small>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if(empty($job_photos)) echo "<div class='col-12 text-center text-muted'>No photos attached to this job yet.</div>"; ?>
                </div>

                <hr>
                <h6><i class="fas fa-camera text-secondary me-2"></i> Upload Photo</h6>
                <form action="" method="post" enctype="multipart/form-data" class="row g-2 align-items-center">
                    <div class="col-md-5">
                        <input type="file" name="photo" class="form-control form-control-sm" accept="image/jpeg, image/png, image/gif" required>
                    </div>
                    <div class="col-md-5">
                        <input type="text" name="caption" class="form-control form-control-sm" placeholder="Add a short caption...">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" name="upload_photo" class="btn btn-sm btn-outline-dark w-100"><i class="fas fa-upload me-1"></i> Upload</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Vehicle History Gallery -->
        <div class="card mb-4 print-hide">
            <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-history me-2"></i> Vehicle Photo History</h5>
            </div>
            <div class="card-body">
                <div class="row g-2" style="max-height: 400px; overflow-y: auto;">
                    <?php foreach($history_photos as $hp): ?>
                        <div class="col-md-3 col-sm-4 text-center">
                            <a href="../../assets/uploads/job_photos/<?php echo $hp['photo_path']; ?>" target="_blank" class="d-block mb-1">
                                <img src="../../assets/uploads/job_photos/<?php echo $hp['photo_path']; ?>" class="img-thumbnail" style="width: 100%; height: 100px; object-fit: cover;" alt="History">
                            </a>
                            <small class="d-block text-muted" style="font-size: 0.7rem;">Job: <a href="view.php?id=<?php echo $hp['job_id']; ?>"><?php echo $hp['job_number']; ?></a></small>
                            <small class="d-block text-truncate" style="font-size: 0.7rem;"><?php echo htmlspecialchars($hp['caption'] ?: '-'); ?></small>
                        </div>
                    <?php endforeach; ?>
                    <?php if(empty($history_photos)) echo "<div class='col-12 text-center text-muted p-3'>No previous photos found for this vehicle.</div>"; ?>
                </div>
            </div>
        </div>

    </div>

    <!-- Summary Sidebar -->
    <div class="col-md-4">
        <div class="card bg-info text-white mb-4">
            <div class="card-body">
                <h5 class="card-title">Billing Summary (Est.)</h5>
                <hr>
                <div class="d-flex justify-content-between mb-2">
                    <span>Labor Cost:</span>
                    <span><?php echo formatCurrency($pdo, $job['labor_cost']); ?></span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Services:</span>
                    <span><?php echo formatCurrency($pdo, $total_services); ?></span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Spare Parts:</span>
                    <span><?php echo formatCurrency($pdo, $total_parts); ?></span>
                </div>
                <hr>
                <div class="d-flex justify-content-between h4">
                    <span>Total:</span>
                    <span><?php echo formatCurrency($pdo, $grand_total); ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
