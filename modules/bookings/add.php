<?php
require_once '../../includes/auth_check.php';
require_once '../../config/db.php';
require_once '../../includes/functions.php';

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $customer_id = $_POST['customer_id'];
    $vehicle_id = $_POST['vehicle_id'];
    $technician_id = !empty($_POST['technician_id']) ? $_POST['technician_id'] : null;
    $booking_date = $_POST['booking_date'];
    $booking_time = $_POST['booking_time'];
    $description = $_POST['description'];
    
    // Generate Booking Number
    $booking_number = "BK-" . date('ym') . "-" . rand(1000, 9999);

    try {
        $stmt = $pdo->prepare("INSERT INTO bookings (booking_number, customer_id, vehicle_id, technician_id, booking_date, booking_time, description) 
                               VALUES (:bn, :cid, :vid, :tid, :bd, :bt, :desc)");
        $res = $stmt->execute([
            'bn' => $booking_number,
            'cid' => $customer_id,
            'vid' => $vehicle_id,
            'tid' => $technician_id,
            'bd' => $booking_date,
            'bt' => $booking_time,
            'desc' => $description
        ]);

        if ($res) {
            $booking_id = $pdo->lastInsertId();
            logAction($pdo, $_SESSION['user_id'], 'Create Booking', 'bookings', $booking_id, "Booking $booking_number created.");
            header("Location: index.php?success=Booking created successfully.");
            exit;
        }
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

$customers = $pdo->query("SELECT id, name, phone FROM customers ORDER BY name ASC")->fetchAll();
$technicians = $pdo->query("SELECT id, name FROM users WHERE role = 'technician' ORDER BY name ASC")->fetchAll();

require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Schedule New Booking</h5>
                <a href="index.php" class="btn btn-sm btn-outline-secondary">Back</a>
            </div>
            <div class="card-body">
                <?php if($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <form action="" method="post">
                    <div class="row g-3">
                        <!-- Customer Selection -->
                        <div class="col-md-12">
                            <label class="form-label">Customer</label>
                            <select name="customer_id" id="customer_select" class="form-select" required>
                                <option value="">-- Choose Customer --</option>
                                <?php foreach($customers as $c): ?>
                                    <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?> (<?php echo $c['phone']; ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Vehicle Selection (Dynamic) -->
                        <div class="col-md-12">
                            <label class="form-label">Vehicle</label>
                            <select name="vehicle_id" id="vehicle_select" class="form-select" required disabled>
                                <option value="">-- Select Customer First --</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Booking Date</label>
                            <input type="date" name="booking_date" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Preferred Time</label>
                            <input type="time" name="booking_time" class="form-control" required>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Assign Technician (Optional)</label>
                            <select name="technician_id" class="form-select">
                                <option value="">-- Auto/Unassigned --</option>
                                <?php foreach($technicians as $t): ?>
                                    <option value="<?php echo $t['id']; ?>"><?php echo htmlspecialchars($t['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Description / Inspection Reason</label>
                            <textarea name="description" class="form-control" rows="3" placeholder="e.g. Engine oil leak inspection..."></textarea>
                        </div>

                        <div class="col-md-12 mt-4 text-end">
                            <button type="submit" class="btn btn-primary px-5">Schedule Booking</button>
                        </div>
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

        fetch('../job_card/get_vehicles.php?customer_id=' + cid)
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
                vSelect.innerHTML = '<option value="">-- No vehicles registered --</option>';
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
