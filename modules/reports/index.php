<?php
require_once '../../includes/auth_check.php';
require_once '../../config/db.php';
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
require_once '../../includes/functions.php';


checkRole(['admin']);

$start_date = $_GET['start'] ?? date('Y-m-01'); // First day of current month
$end_date = $_GET['end'] ?? date('Y-m-d');

// Revenue Data
$rev_sql = "SELECT SUM(total_amount) as total, COUNT(*) as count FROM invoices WHERE invoice_date BETWEEN :start AND :end";
$rev_stmt = $pdo->prepare($rev_sql);
$rev_stmt->execute(['start' => $start_date . " 00:00:00", 'end' => $end_date . " 23:59:59"]);
$revenue = $rev_stmt->fetch();

// Technician Performance
$tech_sql = "SELECT u.name, COUNT(j.id) as job_count, SUM(j.labor_cost) as labor_rev 
             FROM users u 
             LEFT JOIN job_cards j ON u.id = j.technician_id 
             WHERE u.role = 'technician' AND j.created_at BETWEEN :start AND :end 
             GROUP BY u.id";
$tech_stmt = $pdo->prepare($tech_sql);
$tech_stmt->execute(['start' => $start_date . " 00:00:00", 'end' => $end_date . " 23:59:59"]);
$tech_perf = $tech_stmt->fetchAll();

// Job Status Summary
$status_sql = "SELECT status, COUNT(*) as count FROM job_cards WHERE created_at BETWEEN :start AND :end GROUP BY status";
$status_stmt = $pdo->prepare($status_sql);
$status_stmt->execute(['start' => $start_date . " 00:00:00", 'end' => $end_date . " 23:59:59"]);
$job_stats = $status_stmt->fetchAll();

// NEW: Booking Reports Data
// 1. Booking Details
$bk_details_sql = "SELECT b.*, c.name as customer_name, cv.license_plate, u.name as tech_name 
                   FROM bookings b 
                   JOIN customers c ON b.customer_id = c.id 
                   JOIN customer_vehicles cv ON b.vehicle_id = cv.id 
                   LEFT JOIN users u ON b.technician_id = u.id 
                   WHERE b.booking_date BETWEEN :start AND :end 
                   ORDER BY b.booking_date ASC";
$bk_details_stmt = $pdo->prepare($bk_details_sql);
$bk_details_stmt->execute(['start' => $start_date, 'end' => $end_date]);
$bk_details = $bk_details_stmt->fetchAll();

// 2. Booking vs Jobs (Conversion)
$total_bk = count($bk_details);
$converted_jobs_sql = "SELECT COUNT(*) FROM job_cards WHERE booking_id IS NOT NULL AND created_at BETWEEN :start AND :end";
$converted_jobs_stmt = $pdo->prepare($converted_jobs_sql);
$converted_jobs_stmt->execute(['start' => $start_date . " 00:00:00", 'end' => $end_date . " 23:59:59"]);
$total_converted = $converted_jobs_stmt->fetchColumn();
$conversion_rate = ($total_bk > 0) ? round(($total_converted / $total_bk) * 100, 2) : 0;

// 3. Incoming Jobs Detailed
$inc_jobs_sql = "SELECT j.*, c.name as customer_name, b.booking_number 
                 FROM job_cards j 
                 JOIN customers c ON j.customer_id = c.id 
                 JOIN bookings b ON j.booking_id = b.id 
                 WHERE j.created_at BETWEEN :start AND :end";
$inc_jobs_stmt = $pdo->prepare($inc_jobs_sql);
$inc_jobs_stmt->execute(['start' => $start_date . " 00:00:00", 'end' => $end_date . " 23:59:59"]);
$inc_jobs = $inc_jobs_stmt->fetchAll();
?>

<div class="row mb-4">
    <div class="col-md-12 d-flex justify-content-between align-items-center">
        <h2>Reports</h2>
        <a href="export.php?type=revenue&start=<?php echo $start_date; ?>&end=<?php echo $end_date; ?>" class="btn btn-success"><i class="fas fa-file-csv me-2"></i> Export Revenue CSV</a>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
         <form action="" method="get" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label>Start Date</label>
                <input type="date" name="start" class="form-control" value="<?php echo $start_date; ?>">
            </div>
            <div class="col-md-4">
                <label>End Date</label>
                <input type="date" name="end" class="form-control" value="<?php echo $end_date; ?>">
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
            </div>
        </form>
    </div>
</div>

<ul class="nav nav-tabs mb-4" id="reportTabs" role="tablist">
  <li class="nav-item">
    <button class="nav-link active" id="revenue-tab" data-bs-toggle="tab" data-bs-target="#revenue" type="button">Revenue & Performance</button>
  </li>
  <li class="nav-item">
    <button class="nav-link" id="bookings-tab" data-bs-toggle="tab" data-bs-target="#bookings" type="button">Booking Reports</button>
  </li>
</ul>

<div class="tab-content" id="reportTabsContent">
  <div class="tab-pane fade show active" id="revenue" role="tabpanel">

<div class="row">
    <!-- Revenue Card -->
    <div class="col-md-4">
        <div class="card bg-success text-white mb-4">
            <div class="card-body">
                <h6>Total Revenue</h6>
                <h3><?php echo formatCurrency($pdo, $revenue['total']); ?></h3>
                <small class="opacity-75"><?php echo $revenue['count']; ?> Invoices Generated</small>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Technician Performance -->
    <div class="col-md-6">
        <div class="card mb-4 h-100">
            <div class="card-header">
                <h5 class="mb-0">Technician Performance</h5>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Technician</th>
                            <th>Jobs Handled</th>
                            <th>Labor Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($tech_perf as $t): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($t['name']); ?></td>
                            <td><?php echo $t['job_count']; ?></td>
                            <td><?php echo formatCurrency($pdo, $t['labor_rev']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Job Stats -->
    <div class="col-md-6">
        <div class="card mb-4 h-100">
            <div class="card-header">
                <h5 class="mb-0">Job Status Summary</h5>
            </div>
            <div class="card-body">
                <canvas id="jobChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    var ctx = document.getElementById('jobChart').getContext('2d');
    var jobChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: [<?php foreach($job_stats as $s) echo "'" . $s['status'] . "',"; ?>],
            datasets: [{
                data: [<?php foreach($job_stats as $s) echo $s['count'] . ","; ?>],
                backgroundColor: [
                    '#6c757d', // Open - secondary
                    '#0d6efd', // In Progress - primary
                    '#198754', // Completed - success
                    '#0dcaf0'  // Delivered - info
                ]
            }]
        }
    });
</script>

  </div> <!-- End Revenue Tab -->

  <div class="tab-pane fade" id="bookings" role="tabpanel">
    <div class="row">
        <!-- Conversion Stats -->
        <div class="col-md-12 mb-4">
            <div class="card border-primary">
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-4">
                            <h6 class="text-muted">Total Bookings</h6>
                            <h2 class="text-primary"><?php echo $total_bk; ?></h2>
                        </div>
                        <div class="col-md-4 border-start border-end">
                            <h6 class="text-muted">Converted to Jobs</h6>
                            <h2 class="text-success"><?php echo $total_converted; ?></h2>
                        </div>
                        <div class="col-md-4">
                            <h6 class="text-muted">Conversion Rate</h6>
                            <h2 class="text-info"><?php echo $conversion_rate; ?>%</h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Booking Details Report -->
        <div class="col-md-12 mb-4">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Booking Details Report</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Ref #</th>
                                    <th>Customer</th>
                                    <th>Vehicle</th>
                                    <th>Date & Time</th>
                                    <th>Technician</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($bk_details as $bk): ?>
                                <tr>
                                    <td><?php echo $bk['booking_number']; ?></td>
                                    <td><?php echo htmlspecialchars($bk['customer_name']); ?></td>
                                    <td><?php echo $bk['license_plate']; ?></td>
                                    <td><?php echo $bk['booking_date']; ?> <?php echo date('H:i', strtotime($bk['booking_time'])); ?></td>
                                    <td><?php echo $bk['tech_name'] ?? 'Unassigned'; ?></td>
                                    <td><span class="badge bg-<?php echo ($bk['status']=='Completed'?'success':($bk['status']=='Cancelled'?'danger':'warning')); ?>"><?php echo $bk['status']; ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if(empty($bk_details)): ?>
                                    <tr><td colspan="6" class="text-center p-4">No bookings found for this period.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Incoming Job Detailed Report -->
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Incoming Job Detailed Report (From Bookings)</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Job #</th>
                                    <th>Booking Ref</th>
                                    <th>Customer</th>
                                    <th>Status</th>
                                    <th>Created At</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($inc_jobs as $ij): ?>
                                <tr>
                                    <td><strong><?php echo $ij['job_number']; ?></strong></td>
                                    <td><?php echo $ij['booking_number']; ?></td>
                                    <td><?php echo htmlspecialchars($ij['customer_name']); ?></td>
                                    <td><span class="badge bg-secondary"><?php echo $ij['status']; ?></span></td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($ij['created_at'])); ?></td>
                                    <td><a href="../job_card/view.php?id=<?php echo $ij['id']; ?>" class="btn btn-sm btn-outline-info">View Job</a></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if(empty($inc_jobs)): ?>
                                    <tr><td colspan="6" class="text-center p-4">No incoming jobs found from bookings in this period.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
  </div> <!-- End Bookings Tab -->
</div> <!-- End Tab Content -->

<?php require_once '../../includes/footer.php'; ?>
