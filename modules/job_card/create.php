<?php
require_once '../../includes/auth_check.php';
require_once '../../config/db.php';
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
require_once '../../includes/functions.php';


checkRole(['admin']);

// Fetch Customers
$customers = $pdo->query("SELECT id, name, phone FROM customers ORDER BY name")->fetchAll();

// Fetch Technicians
$techs = $pdo->query("SELECT id, name FROM users WHERE role = 'technician'")->fetchAll();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $customer_id = $_POST['customer_id'];
    $vehicle_id = $_POST['vehicle_id'];
    $description = trim($_POST['description']);
    $technician_id = !empty($_POST['technician_id']) ? $_POST['technician_id'] : null;
    $labor_cost = !empty($_POST['labor_cost']) ? $_POST['labor_cost'] : 0.00;
    
    // Generate Job ID
    $job_number = "JOB-" . date('Ym') . "-" . rand(1000, 9999);
    
    $sql = "INSERT INTO job_cards (job_number, customer_id, vehicle_id, technician_id, description, labor_cost) VALUES (:job, :cid, :vid, :tid, :desc, :labor)";
    $stmt = $pdo->prepare($sql);
    if($stmt->execute(['job' => $job_number, 'cid' => $customer_id, 'vid' => $vehicle_id, 'tid' => $technician_id, 'desc' => $description, 'labor' => $labor_cost])) {
        $new_id = $pdo->lastInsertId();
        logAction($pdo, $_SESSION['user_id'], 'Created Job Card', 'job_cards', $new_id, "Job: $job_number");
        echo "<script>window.location='view.php?id=$new_id';</script>";
        exit;
    }
 else {
        $error = "Error creating job card.";
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-9">
        <div class="card">
            <div class="card-header">
                <h4>Create New Job Card</h4>
            </div>
            <div class="card-body">
                <form action="" method="post">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Select Customer</label>
                                <select name="customer_id" id="customer_select" class="form-select" required>
                                    <option value="">-- Choose Customer --</option>
                                    <?php foreach($customers as $c): ?>
                                        <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']) . " (" . $c['phone'] . ")"; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Select Vehicle</label>
                                <select name="vehicle_id" id="vehicle_select" class="form-select" required disabled>
                                    <option value="">-- Select Customer First --</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Issue / Work Description</label>
                        <textarea name="description" class="form-control" rows="4" required></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Assign Technician</label>
                                <select name="technician_id" class="form-select">
                                    <option value="">-- Unassigned --</option>
                                    <?php foreach($techs as $t): ?>
                                        <option value="<?php echo $t['id']; ?>"><?php echo htmlspecialchars($t['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                         <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Estimated Labor Cost</label>
                                <input type="number" step="0.01" name="labor_cost" class="form-control" value="0.00">
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="index.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Create Job Card</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('customer_select').addEventListener('change', function() {
    var cid = this.value;
    var vSelect = document.getElementById('vehicle_select');
    
    if(cid) {
        vSelect.innerHTML = '<option value="">-- Loading Vehicles... --</option>';
        vSelect.disabled = true;

        fetch('get_vehicles.php?customer_id=' + cid)
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(data => {
            if (data.error) {
                alert("Error fetching vehicles: " + data.error);
                return;
            }
            
            if (data.length === 0) {
                vSelect.innerHTML = '<option value="">-- No vehicles registered for this customer --</option>';
                vSelect.disabled = true;
            } else {
                vSelect.innerHTML = '<option value="">-- Choose Vehicle --</option>';
                data.forEach(v => {
                    var opt = document.createElement('option');
                    opt.value = v.id;
                    opt.text = v.brand_name + " " + v.model_name + " (" + v.license_plate + ")";
                    vSelect.appendChild(opt);
                });
                vSelect.disabled = false;
            }
        })
        .catch(err => {
            console.error('Fetch error:', err);
            vSelect.innerHTML = '<option value="">-- Error loading vehicles --</option>';
        });
    } else {
        vSelect.innerHTML = '<option value="">-- Select Customer First --</option>';
        vSelect.disabled = true;
    }
});

</script>

<?php require_once '../../includes/footer.php'; ?>
