<?php
require_once '../../includes/auth_check.php';
require_once '../../config/db.php';
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';

checkRole(['admin']);

$error = '';
$success = '';

// Fetch existing profile
$stmt = $pdo->query("SELECT * FROM company_profile LIMIT 1");
$company = $stmt->fetch();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $company_name = trim($_POST['company_name']);
    $address = trim($_POST['address']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $tax_percentage = $_POST['tax_percentage'];
    $currency_symbol = trim($_POST['currency_symbol']);
    
    // Handle File Upload
    $logo_path = $company['logo'] ?? null;
    if(isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
        $target_dir = "../../assets/uploads/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $file_extension = pathinfo($_FILES["logo"]["name"], PATHINFO_EXTENSION);
        $new_filename = "company_logo_" . time() . "." . $file_extension;
        $target_file = $target_dir . $new_filename;
        
        if (move_uploaded_file($_FILES["logo"]["tmp_name"], $target_file)) {
            $logo_path = "assets/uploads/" . $new_filename;
        } else {
            $error = "Error uploading logo.";
        }
    }

    if(!$error) {
        if($company) {
            // Update
            $sql = "UPDATE company_profile SET company_name=:name, address=:address, phone=:phone, email=:email, logo=:logo, tax_percentage=:tax, currency_symbol=:currency WHERE id=:id";
            $stmt = $pdo->prepare($sql);
            $res = $stmt->execute(['name' => $company_name, 'address' => $address, 'phone' => $phone, 'email' => $email, 'logo' => $logo_path, 'tax' => $tax_percentage, 'currency' => $currency_symbol, 'id' => $company['id']]);
        } else {
            // Insert
            $sql = "INSERT INTO company_profile (company_name, address, phone, email, logo, tax_percentage, currency_symbol) VALUES (:name, :address, :phone, :email, :logo, :tax, :currency)";
            $stmt = $pdo->prepare($sql);
            $res = $stmt->execute(['name' => $company_name, 'address' => $address, 'phone' => $phone, 'email' => $email, 'logo' => $logo_path, 'tax' => $tax_percentage, 'currency' => $currency_symbol]);
        }
        
        if($res) {
            $_SESSION['currency_symbol'] = $currency_symbol;
            $success = "Company profile updated successfully.";
            // Refresh data
            $stmt = $pdo->query("SELECT * FROM company_profile LIMIT 1");
            $company = $stmt->fetch();
        } else {
            $error = "Database error.";
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-10">
        <div class="card">
            <div class="card-header">
                <h4>Company Profile Settings</h4>
            </div>
            <div class="card-body">
                <?php if($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                <?php if($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>

                <form action="" method="post" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label class="form-label">Company Name</label>
                                <input type="text" name="company_name" class="form-control" value="<?php echo htmlspecialchars($company['company_name'] ?? ''); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Address</label>
                                <textarea name="address" class="form-control" rows="3" required><?php echo htmlspecialchars($company['address'] ?? ''); ?></textarea>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Phone Number</label>
                                        <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($company['phone'] ?? ''); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Email Address</label>
                                        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($company['email'] ?? ''); ?>" required>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Currency Symbol / Code (e.g., $, LKR, €)</label>
                                        <input type="text" name="currency_symbol" class="form-control" value="<?php echo htmlspecialchars($company['currency_symbol'] ?? '$'); ?>" list="currency_options" required>
                                        <datalist id="currency_options">
                                            <option value="$">
                                            <option value="LKR">
                                            <option value="€">
                                            <option value="£">
                                            <option value="₹">
                                            <option value="¥">
                                            <option value="Rs.">
                                            <option value="AED">
                                            <option value="SAR">
                                            <option value="AUD">
                                            <option value="CAD">
                                        </datalist>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Default Tax Percentage (%)</label>
                                        <input type="number" step="0.01" name="tax_percentage" class="form-control" value="<?php echo htmlspecialchars($company['tax_percentage'] ?? '0.00'); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 text-center">
                            <div class="mb-3">
                                <label class="form-label d-block">Company Logo</label>
                                <?php 
                                $logo_filename = basename($company['logo'] ?? '');
                                $logo_rel_path = "../../assets/uploads/" . $logo_filename;
                                if(!empty($logo_filename) && file_exists($logo_rel_path)): ?>
                                    <img src="<?php echo $logo_rel_path; ?>" class="img-thumbnail mb-2" style="max-height: 150px;">
                                <?php else: ?>
                                    <img src="https://via.placeholder.com/150" class="img-thumbnail mb-2">
                                <?php endif; ?>
                                <input type="file" name="logo" class="form-control">
                                <small class="text-muted">Upload a new logo to replace the current one.</small>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i> Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
