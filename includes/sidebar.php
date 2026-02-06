<?php
// Determine active page for highlighting
$current_page = basename($_SERVER['PHP_SELF']);
$user_role = $_SESSION['role'] ?? 'technician'; // Default to technician if not set
?>
<div class="sidebar d-flex flex-column flex-shrink-0 p-3 text-white bg-dark" style="width: 280px;">
    <a href="../../modules/dashboard/index.php" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-white text-decoration-none brand">
        <i class="fas fa-wrench me-2"></i>
        <span>Garage Sys</span>
    </a>
    <hr>
    <ul class="nav nav-pills flex-column mb-auto">
        <li class="nav-item">
            <a href="../../modules/dashboard/index.php" class="nav-link <?php echo ($current_page == 'index.php' && strpos($_SERVER['REQUEST_URI'], 'dashboard') !== false) ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt me-2"></i> Dashboard
            </a>
        </li>
        <li>
            <a href="../../modules/job_card/index.php" class="nav-link <?php echo (strpos($_SERVER['REQUEST_URI'], 'job_card') !== false) ? 'active' : ''; ?>">
                <i class="fas fa-clipboard-list me-2"></i> Job Cards
            </a>
        </li>
        <li>
            <a href="../../modules/bookings/index.php" class="nav-link <?php echo (strpos($_SERVER['REQUEST_URI'], 'bookings') !== false) ? 'active' : ''; ?>">
                <i class="fas fa-calendar-alt me-2"></i> Bookings
            </a>
        </li>
        
        <?php if ($user_role === 'admin'): ?>
        <li>
            <a href="../../modules/customers/index.php" class="nav-link <?php echo (strpos($_SERVER['REQUEST_URI'], 'customers') !== false) ? 'active' : ''; ?>">
                <i class="fas fa-users me-2"></i> Customers
            </a>
        </li>
        <li>
            <a href="../../modules/vehicles/index.php" class="nav-link <?php echo (strpos($_SERVER['REQUEST_URI'], 'vehicles') !== false) ? 'active' : ''; ?>">
                <i class="fas fa-car me-2"></i> Vehicles
            </a>
        </li>
        <li>
            <a href="../../modules/inventory/index.php" class="nav-link <?php echo (strpos($_SERVER['REQUEST_URI'], 'inventory') !== false) ? 'active' : ''; ?>">
                <i class="fas fa-boxes me-2"></i> Inventory
            </a>
        </li>
        <li>
            <a href="../../modules/invoices/index.php" class="nav-link <?php echo (strpos($_SERVER['REQUEST_URI'], 'invoices') !== false) ? 'active' : ''; ?>">
                <i class="fas fa-file-invoice-dollar me-2"></i> Invoices
            </a>
        </li>
        <li>
            <a href="../../modules/users/index.php" class="nav-link <?php echo (strpos($_SERVER['REQUEST_URI'], 'users') !== false) ? 'active' : ''; ?>">
                <i class="fas fa-user-shield me-2"></i> Users
            </a>
        </li>
         <li>
            <a href="../../modules/company/index.php" class="nav-link <?php echo (strpos($_SERVER['REQUEST_URI'], 'company') !== false) ? 'active' : ''; ?>">
                <i class="fas fa-building me-2"></i> Company
            </a>
        </li>
        <li>
            <a href="../../modules/reports/index.php" class="nav-link <?php echo (strpos($_SERVER['REQUEST_URI'], 'reports') !== false) ? 'active' : ''; ?>">
                <i class="fas fa-chart-line me-2"></i> Reports
            </a>
        </li>
        <li>
            <a href="../../modules/settings/index.php" class="nav-link <?php echo (strpos($_SERVER['REQUEST_URI'], 'settings') !== false) ? 'active' : ''; ?>">
                <i class="fas fa-cogs me-2"></i> Settings
            </a>
        </li>
        <?php endif; ?>
    </ul>
    <hr>
    <div class="dropdown">
        <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false">
            <?php if(!empty($_SESSION['avatar'])): ?>
                <img src="../../assets/uploads/profiles/<?php echo $_SESSION['avatar']; ?>" alt="" width="32" height="32" class="rounded-circle me-2" style="object-fit: cover;">
            <?php else: ?>
                <div class="rounded-circle bg-secondary me-2 d-flex align-items-center justify-content-center text-white" style="width: 32px; height: 32px;">
                    <?php echo strtoupper(substr($_SESSION['name'] ?? 'U', 0, 1)); ?>
                </div>
            <?php endif; ?>
            <strong><?php echo htmlspecialchars($_SESSION['name'] ?? 'User'); ?></strong>
        </a>
        <ul class="dropdown-menu dropdown-menu-dark text-small shadow" aria-labelledby="dropdownUser1">
            <li><a class="dropdown-item" href="../../modules/auth/logout.php">Sign out</a></li>
        </ul>
    </div>
</div>
<!-- Main Content Wrapper -->
<div class="flex-grow-1">
    <!-- Top Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light navbar-custom px-4 print-hide">
        <div class="container-fluid">
            <form action="../../modules/search/index.php" method="get" class="d-flex me-auto">
                <div class="input-group">
                    <input class="form-control form-control-sm" type="search" name="q" placeholder="Global Search..." aria-label="Search" style="width: 200px;">
                    <button class="btn btn-outline-primary btn-sm" type="submit"><i class="fas fa-search"></i></button>
                </div>
            </form>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center">
                    <li class="nav-item">
                        <button class="btn btn-link nav-link" id="darkModeToggle"><i class="fas fa-moon"></i></button>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <div class="main-content">
