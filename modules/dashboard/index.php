<?php
require_once '../../includes/auth_check.php';
require_once '../../config/db.php';
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';

// Fetch quick stats
$total_jobs = $pdo->query("SELECT COUNT(*) FROM job_cards")->fetchColumn();
$pending_jobs = $pdo->query("SELECT COUNT(*) FROM job_cards WHERE status != 'Delivered'")->fetchColumn();
$total_customers = $pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn();
$low_stock = $pdo->query("SELECT COUNT(*) FROM inventory WHERE stock_quantity <= min_stock_alert")->fetchColumn();

// Fetch today's bookings
$today = date('Y-m-d');
$where = "WHERE b.booking_date = ? AND b.status IN ('Pending', 'Confirmed')";
$params = [$today];

if ($_SESSION['role'] === 'technician') {
    $where .= " AND b.technician_id = ?";
    $params[] = $_SESSION['user_id'];
}

$today_bookings = $pdo->prepare("SELECT b.*, c.name as customer_name, vm.name as model_name 
                                FROM bookings b 
                                JOIN customers c ON b.customer_id = c.id 
                                JOIN customer_vehicles cv ON b.vehicle_id = cv.id 
                                JOIN vehicle_models vm ON cv.model_id = vm.id 
                                $where
                                ORDER BY b.booking_time ASC");
$today_bookings->execute($params);
$todays = $today_bookings->fetchAll();
?>

<div class="row mb-4">
    <div class="col-md-12">
        <h2>Dashboard</h2>
        <p class="text-muted">Welcome back, <?php echo htmlspecialchars($_SESSION['name']); ?></p>
    </div>
</div>

<div class="row">
    <!-- Card 1: Active Jobs -->
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title mb-1">Active Jobs</h6>
                        <h2 class="mb-0"><?php echo $pending_jobs; ?></h2>
                    </div>
                    <i class="fas fa-tools fa-2x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Card 2: Total Jobs -->
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title mb-1">Total Jobs</h6>
                        <h2 class="mb-0"><?php echo $total_jobs; ?></h2>
                    </div>
                    <i class="fas fa-clipboard-check fa-2x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 3: Customers -->
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title mb-1">Customers</h6>
                        <h2 class="mb-0"><?php echo $total_customers; ?></h2>
                    </div>
                    <i class="fas fa-users fa-2x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 4: Low Stock -->
    <div class="col-md-3">
        <div class="card bg-danger text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title mb-1">Low Stock Alerts</h6>
                        <h2 class="mb-0"><?php echo $low_stock; ?></h2>
                    </div>
                    <i class="fas fa-exclamation-triangle fa-2x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <!-- Today's Agenda -->
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Today's Agenda</h5>
                <span class="badge bg-primary"><?php echo count($todays); ?> Bookings</span>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush" style="max-height: 400px; overflow-y: auto;">
                    <?php foreach($todays as $tb): ?>
                    <a href="../bookings/edit.php?id=<?php echo $tb['id']; ?>" class="list-group-item list-group-item-action">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1 text-primary"><?php echo date('h:i A', strtotime($tb['booking_time'])); ?></h6>
                            <small class="badge bg-<?php echo ($tb['status'] == 'Confirmed') ? 'info' : 'warning'; ?>"><?php echo $tb['status']; ?></small>
                        </div>
                        <p class="mb-1 fw-bold"><?php echo htmlspecialchars($tb['customer_name']); ?></p>
                        <small class="text-muted"><?php echo $tb['model_name']; ?></small>
                    </a>
                    <?php endforeach; ?>
                    <?php if(empty($todays)): ?>
                        <div class="p-4 text-center text-muted">No bookings for today.</div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-footer bg-white text-center">
                <a href="../bookings/index.php" class="small text-decoration-none">View All Bookings</a>
            </div>
        </div>
    </div>

    <!-- Calendar Widget -->
    <div class="col-md-8">
        <div class="card h-100">
            <div class="card-header bg-white">
                <h5 class="mb-0">Booking Calendar</h5>
            </div>
            <div class="card-body">
                <div id="calendar"></div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Jobs Table -->
<div class="row mt-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">Recent Job Cards</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Job #</th>
                                <th>Customer</th>
                                <th>Vehicle</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $stmt = $pdo->query("SELECT j.*, c.name as customer_name, vm.name as vehicle_model 
                                                 FROM job_cards j 
                                                 JOIN customers c ON j.customer_id = c.id 
                                                 JOIN customer_vehicles cv ON j.vehicle_id = cv.id
                                                 JOIN vehicle_models vm ON cv.model_id = vm.id
                                                 ORDER BY j.created_at DESC LIMIT 5");
                            while ($row = $stmt->fetch()) {
                                $status_badge = match($row['status']) {
                                    'Open' => 'secondary',
                                    'In Progress' => 'primary',
                                    'Completed' => 'success',
                                    'Delivered' => 'info',
                                    default => 'secondary'
                                };
                                echo "<tr>
                                    <td>{$row['job_number']}</td>
                                    <td>{$row['customer_name']}</td>
                                    <td>{$row['vehicle_model']}</td>
                                    <td><span class='badge bg-$status_badge'>{$row['status']}</span></td>
                                    <td>" . date('Y-m-d H:i', strtotime($row['created_at'])) . "</td>
                                </tr>";
                            }
                            if ($stmt->rowCount() == 0) {
                                echo "<tr><td colspan='5' class='text-center'>No recent jobs found</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>

<!-- FullCalendar -->
<link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek'
        },
        height: 500,
        events: '../bookings/get_events.php',
        eventClick: function(info) {
            if (info.event.url) {
                window.location.href = info.event.url;
                info.jsEvent.preventDefault();
            }
        },
        eventTimeFormat: {
            hour: 'numeric',
            minute: '2-digit',
            meridiem: 'short'
        }
    });
    calendar.render();
});
</script>
