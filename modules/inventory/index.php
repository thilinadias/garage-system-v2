<?php
require_once '../../includes/auth_check.php';
require_once '../../config/db.php';
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';

require_once '../../includes/functions.php';

checkRole(['admin', 'technician']);

$search = $_GET['search'] ?? '';
$where = "WHERE 1=1";
$params = [];

if($search) {
    $where .= " AND (part_name LIKE :search OR part_number LIKE :search OR category LIKE :search)";
    $params['search'] = "%$search%";
}

// Pagination setup
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Count total records for pagination
$count_sql = "SELECT COUNT(*) FROM inventory $where";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_records = $count_stmt->fetchColumn();

$sql = "SELECT * FROM inventory $where ORDER BY part_name ASC LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$parts = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Inventory Management</h2>
    <?php if($_SESSION['role'] == 'admin'): ?>
    <a href="form.php" class="btn btn-primary"><i class="fas fa-plus me-2"></i> Add New Part</a>
    <?php endif; ?>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form action="" method="get" class="d-flex gap-2">
            <input type="text" name="search" class="form-control" placeholder="Search Part Name, Number or Category" value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="btn btn-secondary">Search</button>
             <?php if($search): ?>
                <a href="index.php" class="btn btn-outline-secondary">Reset</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
         <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th style="width: 50px;">Image</th>
                        <th>Part Name</th>
                        <th>Part No.</th>
                        <th>Category</th>
                        <th>Stock</th>
                        <th>Price (Unit)</th>
                         <?php if($_SESSION['role'] == 'admin'): ?>
                        <th>Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($parts as $p): ?>
                    <tr class="<?php echo ($p['stock_quantity'] <= $p['min_stock_alert']) ? 'table-warning' : ''; ?>">
                         <td>
                            <?php if($p['image']): ?>
                                <img src="../../assets/uploads/<?php echo $p['image']; ?>" width="40" height="40" class="rounded">
                            <?php else: ?>
                                <div class="bg-secondary rounded d-flex align-items-center justify-content-center text-white" style="width: 40px; height: 40px;"><i class="fas fa-box"></i></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?php echo htmlspecialchars($p['part_name']); ?></strong>
                            <?php if($p['stock_quantity'] <= $p['min_stock_alert']): ?>
                                <span class="badge bg-danger ms-2">Low Stock</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($p['part_number']); ?></td>
                        <td><?php echo htmlspecialchars($p['category']); ?></td>
                        <td><?php echo $p['stock_quantity']; ?></td>
                        <td><?php echo formatCurrency($pdo, $p['unit_price']); ?></td>
                         <?php if($_SESSION['role'] == 'admin'): ?>
                        <td>
                             <a href="adjust.php?id=<?php echo $p['id']; ?>" class="btn btn-sm btn-outline-info" title="Adjust Stock"><i class="fas fa-sync-alt"></i></a>
                            <a href="form.php?id=<?php echo $p['id']; ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="fas fa-edit"></i></a>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="mt-3">
            <?php 
            $base_url = "index.php?" . ($search ? "search=" . urlencode($search) . "&" : "");
            echo getPagination($total_records, $limit, $page, $base_url); 
            ?>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
