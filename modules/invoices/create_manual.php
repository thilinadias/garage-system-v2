<?php
require_once '../../includes/auth_check.php';
require_once '../../config/db.php';
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';

checkRole(['admin']);

// Fetch Customers
$customers = $pdo->query("SELECT id, name FROM customers ORDER BY name")->fetchAll();

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $cid = $_POST['customer_id'];
    $desc_arr = $_POST['description'];
    $amt_arr = $_POST['amount'];
    
    $subtotal = 0;
    $items = [];
    
    for($i=0; $i<count($desc_arr); $i++) {
        if(!empty($desc_arr[$i]) && $amt_arr[$i] > 0) {
            $items[] = ['desc' => $desc_arr[$i], 'amt' => $amt_arr[$i]];
            $subtotal += $amt_arr[$i];
        }
    }
    
    if($subtotal > 0 && $cid) {
        $tax_p = $_POST['tax_percentage']; // Assume user sets it or default
        $tax_amt = $subtotal * ($tax_p / 100);
        $total = $subtotal + $tax_amt;
        
        $inv_num = "INV-M-" . date('Ym') . "-" . rand(1000, 9999);
        
        $sql = "INSERT INTO invoices (invoice_number, customer_id, subtotal, tax_amount, total_amount, status) VALUES (:num, :cid, :sub, :tax, :tot, 'Unpaid')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['num' => $inv_num, 'cid' => $cid, 'sub' => $subtotal, 'tax' => $tax_amt, 'tot' => $total]);
        $inv_id = $pdo->lastInsertId();
        
        $item_stmt = $pdo->prepare("INSERT INTO invoice_items (invoice_id, description, amount) VALUES (:iid, :desc, :amt)");
        foreach($items as $item) {
             $item_stmt->execute(['iid' => $inv_id, 'desc' => $item['desc'], 'amt' => $item['amt']]);
        }
        
        echo "<script>window.location='view.php?id=$inv_id';</script>";
        exit;
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h4>Create Manual Invoice</h4>
            </div>
            <div class="card-body">
                <form action="" method="post">
                    <div class="mb-3">
                        <label>Customer</label>
                        <select name="customer_id" class="form-select" required>
                            <option value="">Select Customer</option>
                            <?php foreach($customers as $c): ?>
                                <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <h6>Line Items</h6>
                    <div id="lines">
                        <div class="row mb-2">
                            <div class="col-8">
                                <input type="text" name="description[]" class="form-control" placeholder="Description" required>
                            </div>
                            <div class="col-3">
                                <input type="number" step="0.01" name="amount[]" class="form-control" placeholder="Amount" required>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-8">
                                <input type="text" name="description[]" class="form-control" placeholder="Description">
                            </div>
                            <div class="col-3">
                                <input type="number" step="0.01" name="amount[]" class="form-control" placeholder="Amount">
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-8">
                                <input type="text" name="description[]" class="form-control" placeholder="Description">
                            </div>
                            <div class="col-3">
                                <input type="number" step="0.01" name="amount[]" class="form-control" placeholder="Amount">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mb-3 mt-3">
                         <div class="col-md-4 offset-md-8">
                            <label>Tax Percentage</label>
                             <input type="number" step="0.01" name="tax_percentage" class="form-control" value="0.00">
                         </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100">Create Invoice</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
