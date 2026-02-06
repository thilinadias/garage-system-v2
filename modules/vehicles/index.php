<?php
require_once '../../includes/auth_check.php';
require_once '../../config/db.php';
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';

checkRole(['admin']);

// Handle Brand Addition
if(isset($_POST['add_brand'])) {
    $brand_name = trim($_POST['brand_name']);
    if(!empty($brand_name)) {
        $stmt = $pdo->prepare("INSERT INTO vehicle_brands (name) VALUES (:name)");
        try {
            $stmt->execute(['name' => $brand_name]);
            echo "<script>window.location='index.php?msg=brand_added';</script>";
        } catch(PDOException $e) {
            $error = "Error adding brand.";
        }
    }
}

// Handle Model Addition
if(isset($_POST['add_model'])) {
    $brand_id = $_POST['brand_id'];
    $model_name = trim($_POST['model_name']);
    if(!empty($model_name) && !empty($brand_id)) {
        $stmt = $pdo->prepare("INSERT INTO vehicle_models (brand_id, name) VALUES (:brand_id, :name)");
        try {
            $stmt->execute(['brand_id' => $brand_id, 'name' => $model_name]);
            echo "<script>window.location='index.php?msg=model_added';</script>";
        } catch(PDOException $e) {
            $error = "Error adding model.";
        }
    }
}

// Handle Deletion (Simplified)
if(isset($_GET['delete_brand'])) {
    $stmt = $pdo->prepare("DELETE FROM vehicle_brands WHERE id = :id");
    $stmt->execute(['id' => $_GET['delete_brand']]);
    echo "<script>window.location='index.php';</script>";
}
if(isset($_GET['delete_model'])) {
    $stmt = $pdo->prepare("DELETE FROM vehicle_models WHERE id = :id");
    $stmt->execute(['id' => $_GET['delete_model']]);
    echo "<script>window.location='index.php';</script>";
}

// Fetch Brands
$brands = $pdo->query("SELECT * FROM vehicle_brands ORDER BY name")->fetchAll();

// Fetch Models
$models = $pdo->query("SELECT m.*, b.name as brand_name FROM vehicle_models m JOIN vehicle_brands b ON m.brand_id = b.id ORDER BY b.name, m.name")->fetchAll();
?>

<div class="row">
    <!-- Brand Management -->
    <div class="col-md-5">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Vehicle Brands</h5>
            </div>
            <div class="card-body">
                <form action="" method="post" class="d-flex gap-2 mb-3">
                    <input type="text" name="brand_name" class="form-control" placeholder="New Brand Name" required>
                    <button type="submit" name="add_brand" class="btn btn-success"><i class="fas fa-plus"></i></button>
                </form>
                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-sm table-bordered">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th style="width: 50px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($brands as $brand): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($brand['name']); ?></td>
                                <td class="text-center">
                                    <a href="?delete_brand=<?php echo $brand['id']; ?>" class="text-danger" onclick="return confirm('Delete this brand? All associated models will be deleted.')"><i class="fas fa-trash"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Model Management -->
    <div class="col-md-7">
        <div class="card">
             <div class="card-header bg-info text-white">
                <h5 class="mb-0">Vehicle Models</h5>
            </div>
            <div class="card-body">
                 <form action="" method="post" class="row g-2 mb-3">
                    <div class="col-md-5">
                         <select name="brand_id" class="form-select" required>
                            <option value="">Select Brand</option>
                            <?php foreach ($brands as $brand): ?>
                                <option value="<?php echo $brand['id']; ?>"><?php echo htmlspecialchars($brand['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <input type="text" name="model_name" class="form-control" placeholder="New Model Name" required>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" name="add_model" class="btn btn-success w-100"><i class="fas fa-plus"></i></button>
                    </div>
                </form>
                
                 <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-sm table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>Brand</th>
                                <th>Model</th>
                                <th style="width: 50px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($models as $model): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($model['brand_name']); ?></td>
                                <td><?php echo htmlspecialchars($model['name']); ?></td>
                                <td class="text-center">
                                    <a href="?delete_model=<?php echo $model['id']; ?>" class="text-danger" onclick="return confirm('Delete this model?')"><i class="fas fa-trash"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
