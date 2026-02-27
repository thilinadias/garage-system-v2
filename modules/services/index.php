<?php
// c:\xampp\htdocs\garage-system-v2\modules\services\index.php
require_once '../../includes/auth_check.php';
require_once '../../config/db.php';
require_once '../../includes/functions.php';

checkRole(['admin', 'technician']);

// Pagination
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? $_GET['page'] : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

$search = $_GET['search'] ?? '';
$where = "1=1";
$params = [];

if($search) {
    $where .= " AND (name LIKE :search OR description LIKE :search)";
    $params['search'] = "%$search%";
}

// Get total for pagination
$total_stmt = $pdo->prepare("SELECT COUNT(*) FROM services WHERE $where");
$total_stmt->execute($params);
$total_records = $total_stmt->fetchColumn();

// Fetch records
$sql = "SELECT * FROM services WHERE $where ORDER BY name LIMIT $offset, $records_per_page";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$services = $stmt->fetchAll();

require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-hand-holding-medical me-2 text-primary"></i> Services Catalog</h2>
    <?php if($_SESSION['role'] == 'admin'): ?>
    <a href="add.php" class="btn btn-primary shadow-sm"><i class="fas fa-plus"></i> Add New Service</a>
    <?php endif; ?>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <form action="" method="get" class="mb-4">
            <div class="input-group">
                <input type="text" name="search" class="form-control" placeholder="Search services by name or description..." value="<?php echo htmlspecialchars($search); ?>">
                <button class="btn btn-outline-secondary" type="submit"><i class="fas fa-search"></i> Search</button>
                <?php if($search): ?>
                    <a href="index.php" class="btn btn-outline-danger">Clear</a>
                <?php endif; ?>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Service Name</th>
                        <th>Description</th>
                        <th>Price & Offers</th>
                        <?php if($_SESSION['role'] == 'admin'): ?>
                        <th class="text-end">Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($services as $s): 
                        $price_calc = calculateServicePrice($s);
                    ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($s['name']); ?></strong></td>
                        <td><small class="text-muted"><?php echo htmlspecialchars(substr($s['description'] ?? '', 0, 80)); ?>...</small></td>
                        <td>
                            <?php if ($price_calc['is_discounted']): ?>
                                <span class="text-decoration-line-through text-muted me-2"><?php echo formatCurrency($pdo, $price_calc['original_price']); ?></span>
                                <strong class="text-success"><?php echo formatCurrency($pdo, $price_calc['final_price']); ?></strong>
                                <br><span class="badge bg-warning text-dark mt-1"><i class="fas fa-tag me-1"></i> <?php echo htmlspecialchars($price_calc['offer_text']); ?></span>
                                <?php if($s['offer_end_date']): ?>
                                    <div style="font-size: 11px;" class="text-danger mt-1">Ends: <?php echo date('M d, Y', strtotime($s['offer_end_date'])); ?></div>
                                <?php endif; ?>
                            <?php else: ?>
                                <strong><?php echo formatCurrency($pdo, $price_calc['original_price']); ?></strong>
                            <?php endif; ?>
                        </td>
                        <?php if($_SESSION['role'] == 'admin'): ?>
                        <td class="text-end">
                            <a href="edit.php?id=<?php echo $s['id']; ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
                            <a href="delete.php?id=<?php echo $s['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this service?');"><i class="fas fa-trash"></i></a>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(empty($services)): ?>
                    <tr>
                        <td colspan="<?php echo ($_SESSION['role'] == 'admin') ? '4' : '3'; ?>" class="text-center py-4 text-muted">No services found in the catalog.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <div class="mt-3">
            <?php echo getPagination($total_records, $records_per_page, $page, "?search=" . urlencode($search) . "&"); ?>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
