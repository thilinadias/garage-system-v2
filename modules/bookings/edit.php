<?php
require_once '../../includes/auth_check.php';
require_once '../../config/db.php';
require_once '../../includes/functions.php';

$id = $_GET['id'] ?? null;
if(!$id) {
    header("Location: index.php");
    exit;
}

$error = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $technician_id = !empty($_POST['technician_id']) ? $_POST['technician_id'] : null;
    $booking_date = $_POST['booking_date'];
    $booking_time = $_POST['booking_time'];
    $description = $_POST['description'];
    $status = $_POST['status'];

    try {
        $stmt = $pdo->prepare("UPDATE bookings SET technician_id = :tid, booking_date = :bd, booking_time = :bt, description = :desc, status = :status WHERE id = :id");
        $res = $stmt->execute([
            'tid' => $technician_id,
            'bd' => $booking_date,
            'bt' => $booking_time,
            'desc' => $description,
            'status' => $status,
            'id' => $id
        ]);

        if ($res) {
            logAction($pdo, $_SESSION['user_id'], 'Edit Booking', 'bookings', $id, "Booking ID $id updated.");
            header("Location: index.php?success=Booking updated successfully.");
            exit;
        }
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

$booking = $pdo->prepare("SELECT b.*, c.name as customer_name, cv.license_plate, vm.name as model_name 
                          FROM bookings b 
                          JOIN customers c ON b.customer_id = c.id 
                          JOIN customer_vehicles cv ON b.vehicle_id = cv.id 
                          JOIN vehicle_models vm ON cv.model_id = vm.id 
                          WHERE b.id = ?");
$booking->execute([$id]);
$b = $booking->fetch();

if(!$b) die("Booking not found.");

$technicians = $pdo->query("SELECT id, name FROM users WHERE role = 'technician' ORDER BY name ASC")->fetchAll();

require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Edit Booking: <?php echo $b['booking_number']; ?></h5>
                <a href="index.php" class="btn btn-sm btn-outline-secondary">Back</a>
            </div>
            <div class="card-body">
                <?php if($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <form action="" method="post">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label">Customer / Vehicle</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($b['customer_name']); ?> - <?php echo $b['model_name']; ?> (<?php echo $b['license_plate']; ?>)" readonly>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Booking Date</label>
                            <input type="date" name="booking_date" class="form-control" value="<?php echo $b['booking_date']; ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Preferred Time</label>
                            <input type="time" name="booking_time" class="form-control" value="<?php echo $b['booking_time']; ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Technician</label>
                            <select name="technician_id" class="form-select">
                                <option value="">-- Unassigned --</option>
                                <?php foreach($technicians as $t): ?>
                                    <option value="<?php echo $t['id']; ?>" <?php echo ($b['technician_id'] == $t['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($t['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="Pending" <?php echo ($b['status'] == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                                <option value="Confirmed" <?php echo ($b['status'] == 'Confirmed') ? 'selected' : ''; ?>>Confirmed</option>
                                <option value="Cancelled" <?php echo ($b['status'] == 'Cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                                <option value="Completed" <?php echo ($b['status'] == 'Completed') ? 'selected' : ''; ?>>Completed</option>
                            </select>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Description / Notes</label>
                            <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($b['description']); ?></textarea>
                        </div>

                        <div class="col-md-12 mt-4 text-end">
                            <button type="submit" class="btn btn-primary px-5">Save Changes</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
