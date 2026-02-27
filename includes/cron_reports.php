<?php
// includes/cron_reports.php
// This script should be run periodically (e.g., every hour) by a Cron Job or Windows Scheduled Task.

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/notifications.php'; // Gives us access to _dispatchEmail()

$stmt = $pdo->query("SELECT * FROM company_profile LIMIT 1");
$company = $stmt->fetch();

if (!$company || empty($company['report_email'])) {
    die("No reporting email designated. Exiting.\n");
}

$report_email = $company['report_email'];
$currency = $company['currency_symbol'] ?? '$';
$company_name = $company['company_name'];

// Get current date and time
$current_date = date('Y-m-d');
$current_time = date('H:i');
$current_hour = (int)date('H');

// Get designated daily report hour (e.g., "14" from "14:30:00")
$target_time = $company['daily_report_time'] ?? '18:00:00';
$target_timestamp = strtotime($current_date . ' ' . $target_time);
$target_hour = (int)date('H', $target_timestamp);
$target_minute = (int)date('i', $target_timestamp);

// Determine if we should run:
// 1. If hour matches (good for "every hour" cron jobs).
// 2. Or if current time is within +/- 5 minutes of the target time (good for Windows specific task scheduling).
$current_timestamp = time();
$time_diff_minutes = abs($current_timestamp - $target_timestamp) / 60;

$should_run = false;
if ($current_hour === $target_hour || $time_diff_minutes <= 5) {
    $should_run = true;
}

// Logging
echo "[".date('Y-m-d H:i:s')."] Cron Triggered.\n";
echo "Target Scheduled Time: $target_time\n";
echo "Current Server Time: ".date('H:i:s')."\n";

// ==========================================
// 1. DAILY REPORT LOGIC
// ==========================================
if ($should_run) {
    echo "Time matched! Generating Daily Report...\n";
    
    // Total Sales Today
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount), 0) as total_sales FROM invoices WHERE DATE(invoice_date) = :today AND status = 'Paid'");
    $stmt->execute(['today' => $current_date]);
    $daily_sales = $stmt->fetchColumn();
    
    // Jobs Completed Today
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM job_cards WHERE status = 'Completed' AND DATE(completed_at) = :today");
    $stmt->execute(['today' => $current_date]);
    $jobs_completed = $stmt->fetchColumn();

    // Technician Allocations (Currently In Progress)
    $stmt = $pdo->query("SELECT j.job_number, j.description, u.name as tech_name, c.name as customer_name, vm.name as vehicle 
                         FROM job_cards j 
                         LEFT JOIN users u ON j.technician_id = u.id 
                         LEFT JOIN customers c ON j.customer_id = c.id
                         LEFT JOIN customer_vehicles cv ON j.vehicle_id = cv.id
                         LEFT JOIN vehicle_models vm ON cv.model_id = vm.id
                         WHERE j.status = 'In Progress'");
    $active_jobs = $stmt->fetchAll();

    // Build HTML Daily Report
    $daily_subject = "Daily Garage Summary - " . date('M d, Y');
    $daily_html = "<h2>Daily Report for $company_name</h2>";
    $daily_html .= "<h3 style='color: #28a745;'>Total Paid Sales Today: $currency" . number_format($daily_sales, 2) . "</h3>";
    $daily_html .= "<h3 style='color: #17a2b8;'>Jobs Completed Today: $jobs_completed</h3>";
    
    $daily_html .= "<hr>";
    $daily_html .= "<h3>Current Active Allocations (In Progress)</h3>";
    
    if (count($active_jobs) > 0) {
        $daily_html .= "<table style='width: 100%; border-collapse: collapse; text-align: left;'>
                        <tr style='background-color: #f8f9fa;'>
                            <th style='padding: 8px; border: 1px solid #ddd;'>Job No.</th>
                            <th style='padding: 8px; border: 1px solid #ddd;'>Technician</th>
                            <th style='padding: 8px; border: 1px solid #ddd;'>Vehicle / Customer</th>
                            <th style='padding: 8px; border: 1px solid #ddd;'>Description</th>
                        </tr>";
        foreach ($active_jobs as $aj) {
            $daily_html .= "<tr>
                                <td style='padding: 8px; border: 1px solid #ddd;'>{$aj['job_number']}</td>
                                <td style='padding: 8px; border: 1px solid #ddd;'><strong>".($aj['tech_name'] ?? 'Unassigned')."</strong></td>
                                <td style='padding: 8px; border: 1px solid #ddd;'>{$aj['vehicle']} ({$aj['customer_name']})</td>
                                <td style='padding: 8px; border: 1px solid #ddd;'>{$aj['description']}</td>
                            </tr>";
        }
        $daily_html .= "</table>";
    } else {
        $daily_html .= "<p>No active jobs currently in progress.</p>";
    }

    if(_dispatchEmail($report_email, $daily_subject, $daily_html, true)){
        echo "Daily Report sent to $report_email.\n";
    } else {
        echo "Failed to send Daily Report.\n";
    }
}

// ==========================================
// 2. MONTHLY REPORT LOGIC
// ==========================================
$last_day_of_month = date('Y-m-t'); // 't' gives the last day of the current month
if ($current_date === $last_day_of_month && $should_run) {
    echo "Last day of month! Generating Monthly Report...\n";
    
    $current_month = date('Y-m');
    $month_name = date('F Y');

    // Total Monthly Sales
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount), 0) as monthly_sales FROM invoices WHERE DATE_FORMAT(invoice_date, '%Y-%m') = :month AND status = 'Paid'");
    $stmt->execute(['month' => $current_month]);
    $monthly_sales = $stmt->fetchColumn();

    // Total Monthly Jobs
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM job_cards WHERE status = 'Completed' AND DATE_FORMAT(completed_at, '%Y-%m') = :month");
    $stmt->execute(['month' => $current_month]);
    $monthly_jobs = $stmt->fetchColumn();

    // Build HTML Monthly Report
    $monthly_subject = "Monthly Performance Report - " . $month_name;
    $monthly_html = "<h2>Monthly Summary for $company_name</h2>";
    $monthly_html .= "<p>Here is your overall performance summary for <strong>$month_name</strong>.</p>";
    
    $monthly_html .= "<div style='background-color: #f4f6f9; padding: 20px; border-radius: 8px; border-left: 5px solid #007bff;'>
                        <h2 style='color: #007bff; margin: 0;'>Total Revenue: $currency" . number_format($monthly_sales, 2) . "</h2>
                        <h3 style='color: #333; margin-top: 10px; margin-bottom: 0;'>Total Vehicles Serviced: $monthly_jobs</h3>
                      </div>";
    
    $monthly_html .= "<br><p>Log in to the Garage System dashboard for a complete breakdown of sales, inventory usage, and technician performance.</p>";

    if(_dispatchEmail($report_email, $monthly_subject, $monthly_html, true)){
        echo "Monthly Report sent to $report_email.\n";
    } else {
        echo "Failed to send Monthly Report.\n";
    }
}

echo "Cron cycle complete.\n";
?>
