<?php
require_once '../../includes/auth_check.php';
require_once '../../config/db.php';
require_once '../../includes/functions.php';


checkRole(['admin', 'technician']);

$id = $_GET['id'] ?? null;
if(!$id) die("Invalid ID");

// Fetch Invoice
$stmt = $pdo->prepare("SELECT i.*, c.name as customer_name, c.address as customer_address, c.phone as customer_phone, c.email as customer_email 
                       FROM invoices i 
                       JOIN customers c ON i.customer_id = c.id 
                       WHERE i.id = :id");
$stmt->execute(['id' => $id]);
$inv = $stmt->fetch();

if(!$inv) die("Invoice not found");

// Handle Payment Status Toggle
if(isset($_POST['toggle_status']) && $_SESSION['role'] == 'admin') {
    $new_status = ($inv['status'] == 'Paid') ? 'Unpaid' : 'Paid';
    $upd = $pdo->prepare("UPDATE invoices SET status = :st WHERE id = :id");
    if($upd->execute(['st' => $new_status, 'id' => $id])) {
        logAction($pdo, $_SESSION['user_id'], 'Updated Payment Status', 'invoices', $id, "Status: $new_status");
        header("Location: view.php?id=$id");
        exit;
    }

}

// Fetch Items
$items_stmt = $pdo->prepare("SELECT * FROM invoice_items WHERE invoice_id = :id");
$items_stmt->execute(['id' => $id]);
$items = $items_stmt->fetchAll();

// Fetch Company Info
$comp = $pdo->query("SELECT * FROM company_profile LIMIT 1")->fetch();

require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>


<div class="mb-3 print-hide d-flex justify-content-between">
    <a href="index.php" class="btn btn-secondary">Back to List</a>
    <div>
        <?php if($_SESSION['role'] == 'admin'): ?>
        <form action="" method="post" class="d-inline">
             <button type="submit" name="toggle_status" class="btn <?php echo $inv['status']=='Paid'?'btn-outline-danger':'btn-outline-success'; ?>">
                Mark as <?php echo $inv['status']=='Paid'?'Unpaid':'Paid'; ?>
            </button>
        </form>
        <?php endif; ?>
        <button onclick="window.print()" class="btn btn-primary"><i class="fas fa-print me-2"></i> Print Invoice</button>
    </div>
</div>

<div class="card p-4" id="print-area">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-8">
            <h4><?php echo htmlspecialchars($comp['company_name']); ?></h4>
            <p>
                <?php echo nl2br(htmlspecialchars($comp['address'])); ?><br>
                Phone: <?php echo htmlspecialchars($comp['phone']); ?><br>
                Email: <?php echo htmlspecialchars($comp['email']); ?>
            </p>
        </div>
        <div class="col-4 text-end">
            <?php if(!empty($comp['logo'])): ?>
                <img src="../../assets/uploads/<?php echo $comp['logo']; ?>" style="max-height: 80px;" class="mb-2">
            <?php endif; ?>
            <h2 class="text-primary">INVOICE</h2>
            <strong>#<?php echo $inv['invoice_number']; ?></strong><br>
            Date: <?php echo date('Y-m-d H:i', strtotime($inv['invoice_date'])); ?><br>
            Status: <span class="badge <?php echo $inv['status']=='Paid'?'bg-success':'bg-danger'; ?>"><?php echo $inv['status']; ?></span>
        </div>
    </div>
    
    <hr>
    
    <!-- Customer Info -->
    <div class="row mb-4">
        <div class="col-6">
            <h6 class="text-muted">Bill To:</h6>
            <h5><?php echo htmlspecialchars($inv['customer_name']); ?></h5>
            <p>
                <?php echo htmlspecialchars($inv['customer_address']); ?><br>
                <?php echo htmlspecialchars($inv['customer_phone']); ?><br>
                <?php echo htmlspecialchars($inv['customer_email']); ?>
            </p>
        </div>
    </div>
    
    <!-- Items -->
    <table class="table table-bordered">
        <thead class="table-light">
            <tr>
                <th>Description</th>
                <th class="text-end">Amount</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($items as $item): ?>
            <tr>
                <td><?php echo htmlspecialchars($item['description']); ?></td>
                <td class="text-end"><?php echo formatCurrency($pdo, $item['amount']); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <th class="text-end">Subtotal</th>
                <td class="text-end"><?php echo formatCurrency($pdo, $inv['subtotal']); ?></td>
            </tr>
            <tr>
                <th class="text-end">Tax</th>
                <td class="text-end"><?php echo formatCurrency($pdo, $inv['tax_amount']); ?></td>
            </tr>
            <?php if($inv['discount_amount'] > 0): ?>
             <tr>
                <th class="text-end">Discount</th>
                <td class="text-end text-danger">-<?php echo formatCurrency($pdo, $inv['discount_amount']); ?></td>
            </tr>
            <?php endif; ?>
            <tr class="table-active">
                <th class="text-end h5">Total</th>
                <td class="text-end h5"><?php echo formatCurrency($pdo, $inv['total_amount']); ?></td>
            </tr>
        </tfoot>
    </table>
    
    <div class="mt-5 text-center text-muted">
        <p>Thank you for your business!</p>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
