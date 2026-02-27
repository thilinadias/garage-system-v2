<?php
// c:\xampp\htdocs\garage-system-v2\modules\services\edit.php
require_once '../../includes/auth_check.php';
require_once '../../config/db.php';
require_once '../../includes/functions.php';

checkRole(['admin']);

$id = $_GET['id'] ?? null;
if(!$id) { header("Location: index.php"); exit; }

$stmt = $pdo->prepare("SELECT * FROM services WHERE id = :id");
$stmt->execute(['id' => $id]);
$service = $stmt->fetch();

if(!$service) { header("Location: index.php"); exit; }

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $desc = trim($_POST['description']);
    $price = (float)$_POST['original_price'];
    
    // Offer fields
    $offer_name = trim($_POST['offer_name']);
    $offer_type = $_POST['offer_discount_type'];
    $offer_val = !empty($_POST['offer_discount_value']) ? (float)$_POST['offer_discount_value'] : null;
    $offer_end = !empty($_POST['offer_end_date']) ? $_POST['offer_end_date'] : null;

    if (empty($name) || $price < 0) {
        $error = "Name and a valid Original Price are required.";
    } else {
        try {
            $sql = "UPDATE services SET 
                    name = :nm, 
                    description = :desc, 
                    original_price = :price, 
                    offer_name = :oname, 
                    offer_discount_type = :otype, 
                    offer_discount_value = :oval, 
                    offer_end_date = :oend 
                    WHERE id = :id";
            $update = $pdo->prepare($sql);
            $update->execute([
                'nm' => $name,
                'desc' => $desc,
                'price' => $price,
                'oname' => empty($offer_name) ? null : $offer_name,
                'otype' => empty($offer_type) ? null : $offer_type,
                'oval' => $offer_val,
                'oend' => $offer_end,
                'id' => $id
            ]);
            
            logAction($pdo, $_SESSION['user_id'], 'Updated Service', 'services', $id, "Service: $name");
            header("Location: index.php?msg=updated");
            exit;
        } catch (PDOException $e) {
            $error = "Database Error: " . $e->getMessage();
        }
    }
}

require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-edit me-2 text-primary"></i> Edit Service</h2>
    <a href="index.php" class="btn btn-secondary">Back to Catalog</a>
</div>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <?php if($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <form action="" method="post">
                    <h5 class="mb-3 border-bottom pb-2">Basic Details</h5>
                    <div class="mb-3">
                        <label class="form-label">Service Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required value="<?php echo htmlspecialchars($service['name']); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($service['description'] ?? ''); ?></textarea>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Original Price <span class="text-danger">*</span></label>
                         <div class="input-group">
                            <span class="input-group-text"><?php echo formatCurrency($pdo, 0, ""); ?></span>
                            <input type="number" step="0.01" name="original_price" class="form-control" required min="0" value="<?php echo htmlspecialchars($service['original_price']); ?>">
                        </div>
                    </div>

                    <!-- Special Offer Section -->
                    <div class="p-3 bg-light rounded border border-warning border-opacity-50">
                        <h5 class="text-warning mb-3 border-bottom border-warning border-opacity-50 pb-2"><i class="fas fa-star me-2"></i> Seasonal / Special Offer Settings</h5>
                        
                        <div class="mb-3">
                            <label class="form-label">Offer Name (e.g. "Winter Special")</label>
                            <input type="text" name="offer_name" class="form-control" placeholder="Leave blank to disable offer" value="<?php echo htmlspecialchars($service['offer_name'] ?? ''); ?>">
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Discount Type</label>
                                    <select name="offer_discount_type" class="form-select">
                                        <option value="">-- Select --</option>
                                        <option value="fixed" <?php echo ($service['offer_discount_type'] == 'fixed') ? 'selected' : ''; ?>>Fixed Amount</option>
                                        <option value="percentage" <?php echo ($service['offer_discount_type'] == 'percentage') ? 'selected' : ''; ?>>Percentage (%)</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Discount Value</label>
                                    <input type="number" step="0.01" name="offer_discount_value" class="form-control" min="0" value="<?php echo htmlspecialchars($service['offer_discount_value'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Expiry Date</label>
                                    <input type="date" name="offer_end_date" class="form-control" value="<?php echo htmlspecialchars($service['offer_end_date'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                        <small class="text-muted d-block mt-2"><i class="fas fa-info-circle"></i> If set, the system will automatically calculate the final price when a technician selects this service until the Expiry Date is reached.</small>
                    </div>

                    <hr class="mt-4">
                    <div class="d-flex justify-content-end">
                        <a href="index.php" class="btn btn-light me-2">Cancel</a>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i> Update Service</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
