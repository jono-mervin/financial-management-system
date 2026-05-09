<?php
// Dashboard Statistics Module
require_once __DIR__ . '/../../includes/db.php';

function get_dashboard_stats() {
    global $pdo;
    
    $stats = [
        'total_revenue' => 0,
        'expenditures' => 0,
        'active_budget' => 0,
        'pending_approvals' => 0,
        'budget_utilization' => 0
    ];
    
    // Revenue (Sum of all collections)
    $stmt = $pdo->query("SELECT SUM(amount_paid) as total FROM collections");
    $stats['total_revenue'] = $stmt->fetch()['total'] ?? 0;
    
    // Expenditures (Sum of all actual expenditures)
    $stmt = $pdo->query("SELECT SUM(amount) as total FROM actual_expenditures");
    $stats['expenditures'] = $stmt->fetch()['total'] ?? 0;
    
    // Active Budget (Total approved budget for current fiscal year)
    $current_year = date('Y');
    $stmt = $pdo->prepare("SELECT SUM(approved_amount) as total FROM approved_budgets WHERE fiscal_year = ?");
    $stmt->execute([$current_year]);
    $stats['active_budget'] = $stmt->fetch()['total'] ?? 0;
    
    // Pending Approvals (Sum of pending payment requests)
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM payment_requests WHERE status = 'Pending'");
    $stats['pending_approvals'] = $stmt->fetch()['count'] ?? 0;
    
    // Budget Utilization Percentage
    if ($stats['active_budget'] > 0) {
        $stats['budget_utilization'] = round(($stats['expenditures'] / $stats['active_budget']) * 100, 2);
    }
    
    return $stats;
}

function get_module_summary() {
    global $pdo;
    
    return [
        'gl' => [
            'total_entries' => $pdo->query("SELECT COUNT(*) FROM gl_entries")->fetchColumn(),
            'open_periods' => $pdo->query("SELECT COUNT(*) FROM period_status WHERE status = 'open'")->fetchColumn()
        ],
        'disbursement' => [
            'total_requests' => $pdo->query("SELECT COUNT(*) FROM payment_requests")->fetchColumn(),
            'paid_amount' => $pdo->query("SELECT SUM(amount) FROM payment_requests WHERE status = 'Paid'")->fetchColumn() ?? 0
        ],
        'collection' => [
            'total_collections' => $pdo->query("SELECT COUNT(*) FROM collections")->fetchColumn(),
            'unpaid_invoices' => $pdo->query("SELECT COUNT(*) FROM ar_invoices WHERE status != 'Paid'")->fetchColumn()
        ],
        'budget' => [
            'approved_items' => $pdo->query("SELECT COUNT(*) FROM approved_budgets")->fetchColumn(),
            'total_allotments' => $pdo->query("SELECT SUM(amount_released) FROM allotment_ledger")->fetchColumn() ?? 0
        ],
        'ap_ar' => [
            'vendor_count' => $pdo->query("SELECT COUNT(*) FROM vendors")->fetchColumn(),
            'customer_count' => $pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn()
        ]
    ];
}

/**
 * Get monthly revenue and expenditure data for the last 6 months
 */
function get_cashflow_data() {
    global $pdo;
    $data = ['labels' => [], 'revenue' => [], 'expenditure' => []];
    
    for ($i = 11; $i >= 0; $i--) {
        $month = date('Y-m', strtotime("-$i months"));
        $label = date('M', strtotime("-$i months"));
        $data['labels'][] = $label;
        
        // Revenue per month
        $stmt = $pdo->prepare("SELECT SUM(amount_paid) FROM collections WHERE DATE_FORMAT(collected_at, '%Y-%m') = ?");
        $stmt->execute([$month]);
        $data['revenue'][] = (float)($stmt->fetchColumn() ?: 0);
        
        // Expenditure per month
        $stmt = $pdo->prepare("SELECT SUM(amount) FROM actual_expenditures WHERE DATE_FORMAT(recorded_at, '%Y-%m') = ?");
        $stmt->execute([$month]);
        $data['expenditure'][] = (float)($stmt->fetchColumn() ?: 0);
    }
    
    return $data;
}

/**
 * Get revenue performance for the current month vs target
 */
function get_revenue_performance() {
    global $pdo;
    
    // Current month total revenue
    $current_month = date('Y-m');
    $stmt = $pdo->prepare("SELECT SUM(amount_paid) FROM collections WHERE DATE_FORMAT(collected_at, '%Y-%m') = ?");
    $stmt->execute([$current_month]);
    $current_revenue = (float)($stmt->fetchColumn() ?: 0);
    
    // Target calculation: 1/12th of total approved annual budget OR a default threshold
    $current_year = date('Y');
    $stmt = $pdo->prepare("SELECT SUM(approved_amount) FROM approved_budgets WHERE fiscal_year = ?");
    $stmt->execute([$current_year]);
    $annual_budget = (float)($stmt->fetchColumn() ?: 0);
    
    $target = ($annual_budget > 0) ? ($annual_budget / 12) : 100000; // Default fallback target
    $percentage = ($target > 0) ? min(round(($current_revenue / $target) * 100), 100) : 0;
    
    return [
        'current' => $current_revenue,
        'target' => $target,
        'percentage' => $percentage
    ];
}
?>
