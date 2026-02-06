<?php
require_once '../../includes/auth_check.php';
require_once '../../config/db.php';
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
require_once '../../includes/functions.php';

checkRole(['admin']);

$search = $_GET['search'] ?? '';
$where = "";
$params = [];

if($search) {
    $where = "WHERE name LIKE :search OR phone LIKE :search OR nic LIKE :search";
    $params['search'] = "%$search%";
}

// Pagination setup
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$count_sql = "SELECT COUNT(*) FROM customers $where";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_records = $count_stmt->fetchColumn();

$sql = "SELECT * FROM customers $where ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
foreach($params as $key => $val) {
    $stmt->bindValue(':'.$key, $val);
}
$stmt->execute();
$customers = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Customer Management</h2>
    <a href="add.php" class="btn btn-primary"><i class="fas fa-plus me-2"></i> Add Customer</a>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form action="" method="get" class="d-flex gap-2">
            <input type="text" name="search" class="form-control" placeholder="Search by Name, Phone, or NIC" value="<?php echo htmlspecialchars($search); ?>">
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
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>NIC</th>
                        <th>Address</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($customers as $c): ?>
                    <tr>
                        <td><?php echo $c['id']; ?></td>
                        <td><?php echo htmlspecialchars($c['name']); ?></td>
                        <td><?php echo htmlspecialchars($c['phone']); ?></td>
                        <td><?php echo htmlspecialchars($c['nic'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($c['address'] ?? '-'); ?></td>
                        <td>
                            <a href="view.php?id=<?php echo $c['id']; ?>" class="btn btn-sm btn-info text-white" title="View Profile"><i class="fas fa-eye"></i></a>
                            <a href="edit.php?id=<?php echo $c['id']; ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="fas fa-edit"></i></a>
                        </td>
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
