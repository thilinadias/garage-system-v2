<?php
require_once '../../includes/auth_check.php';
require_once '../../config/db.php';
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';

checkRole(['admin']);
require_once '../../includes/functions.php';


$id = $_GET['id'] ?? null;
if(!$id) {
    header("Location: index.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM inventory WHERE id = :id");
$stmt->execute(['id' => $id]);
$part = $stmt->fetch();

if(!$part) {
    echo "Part not found.";
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $qty = (int)$_POST['qty'];
    $type = $_POST['type']; // add or remove
    $remarks = trim($_POST['remarks']);
    
    if($qty > 0) {
        $change = ($type == 'add') ? $qty : -$qty;
        $new_stock = $part['stock_quantity'] + $change;
        
        if($new_stock < 0) {
            $error = "Cannot remove more than current stock.";
        } else {
            // Update inventory
            $upd = $pdo->prepare("UPDATE inventory SET stock_quantity = :stock WHERE id = :id");
            $upd->execute(['stock' => $new_stock, 'id' => $id]);
            
            // Log It
            $action = ($type == 'add') ? 'added' : 'used';
            if($type == 'adjust_fix') $action = 'adjusted'; // Custom case if needed
            
            $log = $pdo->prepare("INSERT INTO inventory_logs (part_id, change_qty, action_type, remarks) VALUES (:pid, :qty, :action, :remarks)");
            $log->execute(['pid' => $id, 'qty' => $qty, 'action' => $action, 'remarks' => $remarks]);
            
            logAction($pdo, $_SESSION['user_id'], 'Adjusted Stock', 'inventory', $id, "Type: $type, Quantity: $qty, Reason: $remarks");

            
            echo "<script>window.location='index.php?msg=stock_adjusted';</script>";
            exit;
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h4>Adjust Stock: <?php echo htmlspecialchars($part['part_name']); ?></h4>
            </div>
            <div class="card-body">
                <p>Current Stock: <strong><?php echo $part['stock_quantity']; ?></strong></p>
                
                <?php if(isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <form action="" method="post">
                    <div class="mb-3">
                        <label class="form-label">Action</label>
                        <select name="type" class="form-select">
                            <option value="add">Add Stock (+)</option>
                            <option value="remove">Remove / Damage (-)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Quantity</label>
                        <input type="number" name="qty" class="form-control" min="1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Remarks / Reason</label>
                        <textarea name="remarks" class="form-control" required></textarea>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Update Stock</button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Recent Logs -->
        <h5 class="mt-4">Recent Movements</h5>
        <div class="card">
            <ul class="list-group list-group-flush">
                <?php 
                $logs = $pdo->prepare("SELECT * FROM inventory_logs WHERE part_id = :id ORDER BY created_at DESC LIMIT 5");
                $logs->execute(['id' => $id]);
                while($l = $logs->fetch()):
                    $color = $l['action_type'] == 'added' ? 'text-success' : 'text-danger';
                    $sign = $l['action_type'] == 'added' ? '+' : '-';
                ?>
                <li class="list-group-item d-flex justify-content-between">
                    <span>
                        <span class="<?php echo $color; ?> fw-bold"><?php echo ucfirst($l['action_type']); ?> <?php echo $l['change_qty']; ?></span>
                        <br><small class="text-muted"><?php echo htmlspecialchars($l['remarks']); ?></small>
                    </span>
                    <small class="text-muted"><?php echo date('M d, H:i', strtotime($l['created_at'])); ?></small>
                </li>
                <?php endwhile; ?>
            </ul>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
