<?php
require_once '../../includes/auth_check.php';
require_once '../../config/db.php';
require_once '../../includes/functions.php';


checkRole(['admin']);

if(!isset($_GET['job_id'])) {
    header("Location: ../job_card/index.php");
    exit;
}

$job_id = $_GET['job_id'];

// Check if invoice exists for this job
$check = $pdo->prepare("SELECT id FROM invoices WHERE job_id = :jid");
$check->execute(['jid' => $job_id]);
if($check->rowCount() > 0) {
    $inv = $check->fetch();
    header("Location: view.php?id=" . $inv['id']);
    exit;
}

// Fetch Job Info
$stmt = $pdo->prepare("SELECT j.*, c.id as cid, b.booking_number 
                       FROM job_cards j 
                       JOIN customers c ON j.customer_id = c.id 
                       LEFT JOIN bookings b ON j.booking_id = b.id
                       WHERE j.id = :id");
$stmt->execute(['id' => $job_id]);
$job = $stmt->fetch();

if(!$job) {
    die("Job not found.");
}

// Fetch Parts
$parts = $pdo->prepare("SELECT jcp.*, i.part_name FROM job_card_parts jcp JOIN inventory i ON jcp.part_id = i.id WHERE jcp.job_id = :id");
$parts->execute(['id' => $job_id]);
$job_parts = $parts->fetchAll();

// Fetch Company Tax %
$comp = $pdo->query("SELECT tax_percentage FROM company_profile LIMIT 1")->fetch();
$tax_percent = $comp['tax_percentage'] ?? 0;

// Calculate Totals
$parts_total = 0;
foreach($job_parts as $jp) { $parts_total += ($jp['quantity'] * $jp['unit_price']); }
$labor_total = $job['labor_cost'];
$subtotal = $parts_total + $labor_total;
$tax_amount = $subtotal * ($tax_percent / 100);
$total_amount = $subtotal + $tax_amount;

// Generate Logic on POST (Confirmation)
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $discount = $_POST['discount'] ?? 0;
    
    // Recalculate with discount
    $final_total = $total_amount - $discount;
    
    if (!empty($job['booking_number'])) {
        $inv_num = "INV-" . $job['booking_number'];
    } else {
        $inv_num = "INV-" . date('Ym') . "-" . rand(1000, 9999);
    }
    
    try {
        $pdo->beginTransaction();
        
        // Create Invoice
        $sql = "INSERT INTO invoices (invoice_number, job_id, customer_id, subtotal, tax_amount, discount_amount, total_amount, status) VALUES (:num, :jid, :cid, :sub, :tax, :disc, :tot, 'Unpaid')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['num' => $inv_num, 'jid' => $job_id, 'cid' => $job['cid'], 'sub' => $subtotal, 'tax' => $tax_amount, 'disc' => $discount, 'tot' => $final_total]);
        $inv_id = $pdo->lastInsertId();
        
        // Add Items (Freezing history)
        // 1. Labor
        $item_sql = "INSERT INTO invoice_items (invoice_id, description, amount) VALUES (:iid, :desc, :amt)";
        $item_stmt = $pdo->prepare($item_sql);
        $item_stmt->execute(['iid' => $inv_id, 'desc' => "Labor Charges", 'amt' => $labor_total]);
        
        // 2. Parts
        foreach($job_parts as $jp) {
             $desc = $jp['part_name'] . " x" . $jp['quantity'];
             $amt = $jp['quantity'] * $jp['unit_price'];
             $item_stmt->execute(['iid' => $inv_id, 'desc' => $desc, 'amt' => $amt]);
        }
        
        $pdo->commit();
        logAction($pdo, $_SESSION['user_id'], 'Generated Invoice', 'invoices', $inv_id, "Invoice: $inv_num, Amount: $final_total");
        header("Location: view.php?id=" . $inv_id);

        exit;
        
    } catch(Exception $e) {
        $pdo->rollBack();
        $error = "Failed to generate invoice: " . $e->getMessage();
    }
}

// Review View with Form
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h4>Generate Invoice for Job #<?php echo $job['job_number']; ?></h4>
            </div>
            <div class="card-body">
                <?php if(isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
                
                <table class="table table-bordered">
                    <thead>
                        <th>Description</th>
                        <th class="text-end">Amount</th>
                    </thead>
                    <tbody>
                        <!-- Labor -->
                        <tr>
                            <td>Labor Charges</td>
                            <td class="text-end"><?php echo formatCurrency($pdo, $labor_total); ?></td>
                        </tr>
                        <!-- Parts -->
                        <?php foreach($job_parts as $jp): ?>
                        <tr>
                            <td><?php echo $jp['part_name']; ?> (x<?php echo $jp['quantity']; ?>)</td>
                            <td class="text-end"><?php echo formatCurrency($pdo, $jp['quantity'] * $jp['unit_price']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <tr class="table-light fw-bold">
                            <td>Subtotal</td>
                            <td class="text-end"><?php echo formatCurrency($pdo, $subtotal); ?></td>
                        </tr>
                        <tr>
                            <td>Tax (<?php echo $tax_percent; ?>%)</td>
                            <td class="text-end"><?php echo formatCurrency($pdo, $tax_amount); ?></td>
                        </tr>
                    </tbody>
                </table>
                
                <form action="" method="post">
                    <div class="row mb-3 justify-content-end">
                        <label class="col-sm-3 col-form-label text-end fw-bold">Discount Amount</label>
                        <div class="col-sm-3">
                            <input type="number" step="0.01" name="discount" class="form-control text-end" value="0.00">
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                         <button type="submit" class="btn btn-success btn-lg">Confirm & Generate Invoice</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
