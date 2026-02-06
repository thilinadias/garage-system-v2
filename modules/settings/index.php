<?php
require_once '../../includes/auth_check.php';
require_once '../../config/db.php';
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';

checkRole(['admin']);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>System Settings</h2>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">Database Management</h5>
    </div>
    <div class="card-body">
        <p class="text-muted">Create a backup of your entire database including users, inventory, customers, and job history.</p>
        <div class="d-flex gap-2">
            <a href="backup_db.php" class="btn btn-warning"><i class="fas fa-database me-2"></i> Download Backup (SQL)</a>
            <a href="audit_log.php" class="btn btn-info text-white"><i class="fas fa-history me-2"></i> View Audit Logs</a>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">Restore Database</h5>
    </div>
    <div class="card-body">
        <p class="text-muted">Restore the system database from a previously downloaded SQL file. <span class="text-danger fw-bold">Warning: This will overwrite existing data!</span></p>
        <form action="restore_db.php" method="post" enctype="multipart/form-data" class="row g-3">
            <div class="col-md-8">
                <input type="file" name="backup_file" class="form-control" accept=".sql" required>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-danger w-100" onclick="return confirm('Are you sure? This will replace all current data.')">Restore Now</button>
            </div>
        </form>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
