<?php
require_once '../../includes/auth_check.php';
require_once '../../config/db.php';
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';

checkRole(['admin']);
require_once '../../includes/functions.php';


$id = $_GET['id'] ?? null;
$part = [];
$title = "Add New Part";

if($id) {
    $stmt = $pdo->prepare("SELECT * FROM inventory WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $part = $stmt->fetch();
    $title = "Edit Part: " . htmlspecialchars($part['part_name']);
}

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $part_name = trim($_POST['part_name']);
    $part_number = trim($_POST['part_number']);
    $category = trim($_POST['category']);
    $brand = trim($_POST['brand']);
    $cost_price = $_POST['cost_price'];
    $unit_price = $_POST['unit_price'];
    $min_stock = $_POST['min_stock'];
    
     // Handle File Upload
    $image_path = $part['image'] ?? null;
    if(isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $target_dir = "../../assets/uploads/";
         if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $file_extension = pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION);
        $new_filename = "part_" . time() . "." . $file_extension;
        $target_file = $target_dir . $new_filename;
        
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            $image_path = $new_filename;
        }
    }

    if($id) {
        // Update
        $sql = "UPDATE inventory SET part_name=:name, part_number=:no, category=:cat, brand=:brand, cost_price=:cp, unit_price=:up, min_stock_alert=:ms, image=:img WHERE id=:id";
        $stmt = $pdo->prepare($sql);
        $res = $stmt->execute(['name' => $part_name, 'no' => $part_number, 'cat' => $category, 'brand' => $brand, 'cp' => $cost_price, 'up' => $unit_price, 'ms' => $min_stock, 'img' => $image_path, 'id' => $id]);
        if($res) {
            logAction($pdo, $_SESSION['user_id'], 'Updated Spare Part', 'inventory', $id, "Part: $part_name");
        }
    } else {

        // Insert
        // Initial stock is 0 for new item, adjusted later via stock adjustment or separate field. 
        // Adding initial stock field here for convenience
        $initial_stock = $_POST['initial_stock'] ?? 0;
        
        $sql = "INSERT INTO inventory (part_name, part_number, category, brand, cost_price, unit_price, stock_quantity, min_stock_alert, image) VALUES (:name, :no, :cat, :brand, :cp, :up, :stock, :ms, :img)";
        $stmt = $pdo->prepare($sql);
        $res = $stmt->execute(['name' => $part_name, 'no' => $part_number, 'cat' => $category, 'brand' => $brand, 'cp' => $cost_price, 'up' => $unit_price, 'stock' => $initial_stock, 'ms' => $min_stock, 'img' => $image_path]);
        
        if($res) {
            $new_id = $pdo->lastInsertId();
            logAction($pdo, $_SESSION['user_id'], 'Added New Spare Part', 'inventory', $new_id, "Part: $part_name");
            
            // Log initial stock
            if($initial_stock > 0) {
                $log = $pdo->prepare("INSERT INTO inventory_logs (part_id, change_qty, action_type, remarks) VALUES (:pid, :qty, 'added', 'Initial Stock')");
                $log->execute(['pid' => $new_id, 'qty' => $initial_stock]);
            }
        }
    }

    if($res) {
        echo "<script>window.location='index.php';</script>";
        exit;
    } else {
        $error = "Database Error.";
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h4><?php echo $title; ?></h4>
            </div>
            <div class="card-body">
                <?php if($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form action="" method="post" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Part Name *</label>
                                <input type="text" name="part_name" class="form-control" value="<?php echo htmlspecialchars($part['part_name'] ?? ''); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Part Number (SKU)</label>
                                <input type="text" name="part_number" class="form-control" value="<?php echo htmlspecialchars($part['part_number'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Category</label>
                                <input type="text" name="category" class="form-control" list="cat_list" value="<?php echo htmlspecialchars($part['category'] ?? ''); ?>">
                                <datalist id="cat_list">
                                    <option value="Engine">
                                    <option value="Body">
                                    <option value="Electrical">
                                    <option value="Suspension">
                                </datalist>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Brand</label>
                                <input type="text" name="brand" class="form-control" value="<?php echo htmlspecialchars($part['brand'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Cost Price</label>
                                <input type="number" step="0.01" name="cost_price" class="form-control" value="<?php echo htmlspecialchars($part['cost_price'] ?? '0.00'); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Selling Price *</label>
                                <input type="number" step="0.01" name="unit_price" class="form-control" value="<?php echo htmlspecialchars($part['unit_price'] ?? '0.00'); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Min Stock Alert</label>
                                <input type="number" name="min_stock" class="form-control" value="<?php echo htmlspecialchars($part['min_stock_alert'] ?? '5'); ?>">
                            </div>
                        </div>
                    </div>

                    <?php if(!$id): ?>
                    <div class="mb-3 bg-light p-2 rounded">
                        <label class="form-label">Initial Stock Quantity</label>
                        <input type="number" name="initial_stock" class="form-control" value="0">
                    </div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label">Image</label>
                        <input type="file" name="image" class="form-control">
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="index.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Save Part</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
