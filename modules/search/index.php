<?php
require_once '../../includes/auth_check.php';
require_once '../../config/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';

$query = $_GET['q'] ?? '';
$results = [];

if (!empty($query)) {
    $search = "%$query%";
    
    // Search Customers
    $stmt = $pdo->prepare("SELECT id, name, phone as detail, 'Customer' as type, '../../modules/customers/view.php?id=' as link FROM customers WHERE name LIKE :q OR phone LIKE :q OR email LIKE :q LIMIT 5");
    $stmt->execute(['q' => $search]);
    $results = array_merge($results, $stmt->fetchAll(PDO::FETCH_ASSOC));

    // Search Job Cards
    $stmt = $pdo->prepare("SELECT id, job_number as name, status as detail, 'Job Card' as type, '../../modules/job_card/view.php?id=' as link FROM job_cards WHERE job_number LIKE :q OR description LIKE :q LIMIT 5");
    $stmt->execute(['q' => $search]);
    $results = array_merge($results, $stmt->fetchAll(PDO::FETCH_ASSOC));

    // Search Invoices
    $stmt = $pdo->prepare("SELECT id, invoice_number as name, status as detail, 'Invoice' as type, '../../modules/invoices/view.php?id=' as link FROM invoices WHERE invoice_number LIKE :q LIMIT 5");
    $stmt->execute(['q' => $search]);
    $results = array_merge($results, $stmt->fetchAll(PDO::FETCH_ASSOC));

    // Search Inventory
    $stmt = $pdo->prepare("SELECT id, part_name as name, part_number as detail, 'Inventory' as type, '../../modules/inventory/index.php?search=' as link FROM inventory WHERE part_name LIKE :q OR part_number LIKE :q LIMIT 5");
    $stmt->execute(['q' => $search]);
    $results = array_merge($results, $stmt->fetchAll(PDO::FETCH_ASSOC));
}
?>

<div class="mb-4">
    <h3>Search Results for: "<?php echo h($query); ?>"</h3>
    <hr>
</div>

<?php if (empty($results)): ?>
    <div class="alert alert-info">No results found for your search query.</div>
<?php else: ?>
    <div class="row">
        <?php foreach ($results as $res): ?>
            <div class="col-md-6 mb-3">
                <div class="card h-100">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <span class="badge bg-secondary mb-2"><?php echo $res['type']; ?></span>
                            <h5 class="card-title mb-1"><?php echo h($res['name']); ?></h5>
                            <p class="text-muted small mb-0"><?php echo h($res['detail']); ?></p>
                        </div>
                        <a href="<?php echo $res['link'] . $res['id']; ?>" class="btn btn-primary btn-sm">View</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once '../../includes/footer.php'; ?>
