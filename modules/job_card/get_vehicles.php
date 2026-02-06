<?php
require_once '../../config/db.php';

header('Content-Type: application/json');

if(isset($_GET['customer_id'])) {
    $cid = $_GET['customer_id'];
    try {
        $stmt = $pdo->prepare("SELECT cv.id, vm.name as model_name, vb.name as brand_name, cv.license_plate 
                               FROM customer_vehicles cv 
                               JOIN vehicle_models vm ON cv.model_id = vm.id 
                               JOIN vehicle_brands vb ON vm.brand_id = vb.id
                               WHERE cv.customer_id = :id");
        $stmt->execute(['id' => $cid]);
        $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($vehicles);
    } catch (PDOException $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
}
?>
