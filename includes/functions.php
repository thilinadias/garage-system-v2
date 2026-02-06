<?php
/**
 * Core Helper Functions
 */

/**
 * Log a system action to the audit_logs table
 */
function logAction($pdo, $user_id, $action, $table_name = null, $record_id = null, $details = null) {
    try {
        $sql = "INSERT INTO audit_logs (user_id, action, table_name, record_id, details) VALUES (:uid, :act, :tbl, :rid, :det)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'uid' => $user_id,
            'act' => $action,
            'tbl' => $table_name,
            'rid' => $record_id,
            'det' => $details
        ]);
        return true;
    } catch (PDOException $e) {
        // Silently fail or log to a file
        return false;
    }
}

/**
 * Sanitize output
 */
function h($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Format currency
 */
function formatCurrency($pdo, $amount, $symbol = null) {
    if ($symbol === null) {
        // Try to get from session first for performance
        if (isset($_SESSION['currency_symbol'])) {
            $symbol = $_SESSION['currency_symbol'];
        } else {
            // Fallback to DB
            $stmt = $pdo->query("SELECT currency_symbol FROM company_profile LIMIT 1");
            $res = $stmt->fetch();
            $symbol = $res['currency_symbol'] ?? '$';
            $_SESSION['currency_symbol'] = $symbol; // Cache it
        }
    }
    return $symbol . number_format((float)$amount, 2);
}

/**
 * Generate pagination links
 */
function getPagination($total_records, $records_per_page, $current_page, $url) {
    $total_pages = ceil($total_records / $records_per_page);
    if ($total_pages <= 1) return '';

    $html = '<nav aria-label="Page navigation"><ul class="pagination pagination-sm justify-content-end mb-0">';
    
    // Previous
    $disabled = ($current_page <= 1) ? 'disabled' : '';
    $prev_page = $current_page - 1;
    $html .= "<li class='page-item $disabled'><a class='page-link' href='{$url}page={$prev_page}'>Previous</a></li>";

    // Pages
    for ($i = 1; $i <= $total_pages; $i++) {
        $active = ($i == $current_page) ? 'active' : '';
        $html .= "<li class='page-item $active'><a class='page-link' href='{$url}page={$i}'>$i</a></li>";
    }

    // Next
    $disabled = ($current_page >= $total_pages) ? 'disabled' : '';
    $next_page = $current_page + 1;
    $html .= "<li class='page-item $disabled'><a class='page-link' href='{$url}page={$next_page}'>Next</a></li>";

    $html .= '</ul></nav>';
    return $html;
}
?>
