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

/**
 * Calculate final service price based on any active offers
 * Takes an array representing a database row from the `services` table.
 */
function calculateServicePrice($service) {
    $original = (float)$service['original_price'];
    
    // Default return payload
    $result = [
        'is_discounted' => false,
        'original_price' => $original,
        'final_price' => $original,
        'offer_text' => null
    ];

    // Check if there is an active offer conceptually
    if (!empty($service['offer_name']) && !empty($service['offer_discount_type']) && !empty($service['offer_discount_value'])) {
        $today = date('Y-m-d');
        // Validate date if set
        if (empty($service['offer_end_date']) || $service['offer_end_date'] >= $today) {
            
            $discount_val = (float)$service['offer_discount_value'];
            $final = $original;
            
            if ($service['offer_discount_type'] == 'fixed') {
                $final = $original - $discount_val;
            } elseif ($service['offer_discount_type'] == 'percentage') {
                $final = $original - ($original * ($discount_val / 100));
            }
            
            // Cannot be negative
            if ($final < 0) $final = 0;
            
            $result['is_discounted'] = true;
            $result['final_price'] = $final;
            
            $type_str = ($service['offer_discount_type'] == 'percentage') ? $discount_val . '%' : '$' . $discount_val; // Raw formatting, UI will handle currency
            $result['offer_text'] = $service['offer_name'] . " (-" . $type_str . ")";
        }
    }
    
    return $result;
}
?>
