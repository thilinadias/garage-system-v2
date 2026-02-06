<?php
require_once '../../includes/auth_check.php';
require_once '../../config/db.php';

checkRole(['admin']);

if(!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM customers WHERE id = :id");
$stmt->execute(['id' => $id]);
$customer = $stmt->fetch();

if(!$customer) {
    die("Customer not found.");
}

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $address = trim($_POST['address']);
    $nic = trim($_POST['nic']);

    if(empty($name) || empty($phone)) {
        $error = "Name and Phone are required.";
    } else {
        $sql = "UPDATE customers SET name = :name, phone = :phone, email = :email, address = :address, nic = :nic WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        if($stmt->execute(['name' => $name, 'phone' => $phone, 'email' => $email, 'address' => $address, 'nic' => $nic, 'id' => $id])) {
             logAction($pdo, $_SESSION['user_id'], 'Updated Customer Details', 'customers', $id, "Customer: $name");
             header("Location: index.php?msg=updated");
             exit;
        }
 else {
            $error = "Error updating customer.";
        }
    }
}

require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
require_once '../../includes/functions.php';

?>


<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h4>Edit Customer</h4>
            </div>
            <div class="card-body">
                <?php if($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <form action="" method="post">
                    <div class="mb-3">
                        <label class="form-label">Full Name *</label>
                        <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($customer['name']); ?>" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Phone Number *</label>
                                <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($customer['phone']); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">NIC</label>
                                <input type="text" name="nic" class="form-control" value="<?php echo htmlspecialchars($customer['nic']); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($customer['email']); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-control" rows="2"><?php echo htmlspecialchars($customer['address']); ?></textarea>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="index.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Update Customer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
