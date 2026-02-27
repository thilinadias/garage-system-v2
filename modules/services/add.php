<?php
// c:\xampp\htdocs\garage-system-v2\modules\services\add.php
require_once '../../includes/auth_check.php';
require_once '../../config/db.php';
require_once '../../includes/functions.php';

checkRole(['admin']);

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
            $sql = "INSERT INTO services (name, description, original_price, offer_name, offer_discount_type, offer_discount_value, offer_end_date) 
                    VALUES (:nm, :desc, :price, :oname, :otype, :oval, :oend)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'nm' => $name,
                'desc' => $desc,
                'price' => $price,
                'oname' => empty($offer_name) ? null : $offer_name,
                'otype' => empty($offer_type) ? null : $offer_type,
                'oval' => $offer_val,
                'oend' => $offer_end
            ]);
            
            $new_id = $pdo->lastInsertId();
            logAction($pdo, $_SESSION['user_id'], 'Created Service', 'services', $new_id, "Service: $name");
            header("Location: index.php?msg=added");
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
    <h2><i class="fas fa-plus-circle me-2 text-primary"></i> Add New Service</h2>
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
                        <input type="text" name="name" class="form-control" required placeholder="e.g. Full Synthetic Oil Change">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Explain what is included in this service..."></textarea>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Original Price <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><?php echo formatCurrency($pdo, 0, ""); // Just get the symbol dynamically ?></span>
                            <input type="number" step="0.01" name="original_price" class="form-control" required min="0" value="0.00">
                        </div>
                    </div>

                    <!-- Special Offer Section -->
                    <div class="p-3 bg-light rounded border border-warning border-opacity-50">
                        <h5 class="text-warning mb-3 border-bottom border-warning border-opacity-50 pb-2"><i class="fas fa-star me-2"></i> Seasonal / Special Offer Settings</h5>
                        
                        <div class="mb-3">
                            <label class="form-label">Offer Name (e.g. "Winter Special")</label>
                            <input type="text" name="offer_name" class="form-control" placeholder="Leave blank if no active offer">
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Discount Type</label>
                                    <select name="offer_discount_type" class="form-select">
                                        <option value="">-- Select --</option>
                                        <option value="fixed">Fixed Amount</option>
                                        <option value="percentage">Percentage (%)</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Discount Value</label>
                                    <input type="number" step="0.01" name="offer_discount_value" class="form-control" min="0" placeholder="e.g. 15">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Expiry Date</label>
                                    <input type="date" name="offer_end_date" class="form-control">
                                </div>
                            </div>
                        </div>
                        <small class="text-muted d-block mt-2"><i class="fas fa-info-circle"></i> If set, the system will automatically calculate the final price when a technician selects this service until the Expiry Date is reached.</small>
                    </div>

                    <hr class="mt-4">
                    <div class="d-flex justify-content-end">
                        <a href="index.php" class="btn btn-light me-2">Cancel</a>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i> Save Service</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
