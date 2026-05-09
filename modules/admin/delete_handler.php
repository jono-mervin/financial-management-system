<?php
/**
 * Centralized Admin Delete Handler
 * Admin-only. Handles deletions for all module entities with FK safety.
 */
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php';
check_login();

header('Content-Type: application/json');

// Admin-only gate
if ($_SESSION['role'] !== 'Admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Admin access required.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

$type = $_POST['type'] ?? '';
$id   = intval($_POST['id'] ?? 0);

if (!$type || !$id) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters.']);
    exit();
}

// Map of entity types to their table/PK and protected statuses
$config = [
    // GL
    'journal'        => ['table' => 'journal_headers', 'pk' => 'journal_id',     'status_col' => 'status',  'protected' => ['Posted']],
    'coa'            => ['table' => 'chart_of_accounts','pk' => 'account_id',    'status_col' => null,      'protected' => []],
    // Disbursement
    'payment_request'=> ['table' => 'payment_requests', 'pk' => 'request_id',   'status_col' => 'status',  'protected' => ['Paid']],
    'payment_voucher'=> ['table' => 'payment_vouchers',  'pk' => 'voucher_id',   'status_col' => 'status',  'protected' => ['Paid', 'Approved']],
    // Collection
    'collection'     => ['table' => 'collections',       'pk' => 'collection_id','status_col' => null,      'protected' => []],
    'ar_invoice'     => ['table' => 'ar_invoices',       'pk' => 'ar_invoice_id','status_col' => 'status',  'protected' => ['Paid']],
    'billing_invoice'=> ['table' => 'billing_invoices',  'pk' => 'invoice_id',   'status_col' => 'status',  'protected' => ['Paid']],
    // Budget
    'budget_proposal'=> ['table' => 'budget_proposals',  'pk' => 'proposal_id',  'status_col' => 'status',  'protected' => ['Approved']],
    // AP/AR
    'vendor'         => ['table' => 'vendors',           'pk' => 'vendor_id',    'status_col' => null,      'protected' => []],
    'customer'       => ['table' => 'customers',         'pk' => 'customer_id',  'status_col' => null,      'protected' => []],
    'ap_invoice'     => ['table' => 'ap_invoices',       'pk' => 'ap_invoice_id','status_col' => 'status',  'protected' => ['Paid']],
    // Admin
    'user'           => ['table' => 'users',             'pk' => 'user_id',      'status_col' => null,      'protected' => []],
    // Departments
    'department'     => ['table' => 'departments',       'pk' => 'department_id','status_col' => null,      'protected' => []],
];

if (!array_key_exists($type, $config)) {
    echo json_encode(['success' => false, 'message' => "Unknown entity type: '$type'."]);
    exit();
}

$c = $config[$type];

// Prevent self-deletion for user accounts
if ($type === 'user' && $id === $_SESSION['user_id']) {
    echo json_encode(['success' => false, 'message' => 'You cannot delete your own account.']);
    exit();
}

try {
    // Status protection check
    if ($c['status_col']) {
        $row = $pdo->query("SELECT {$c['status_col']} FROM `{$c['table']}` WHERE `{$c['pk']}` = $id")->fetch();
        if ($row && in_array($row[$c['status_col']], $c['protected'])) {
            echo json_encode(['success' => false, 'message' => "Cannot delete: record has status '{$row[$c['status_col']]}' and is protected."]);
            exit();
        }
    }

    // Disable FK checks for safe cascade-free delete, then re-enable
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $stmt = $pdo->prepare("DELETE FROM `{$c['table']}` WHERE `{$c['pk']}` = ?");
    $stmt->execute([$id]);
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    if ($stmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'Record not found or already deleted.']);
        exit();
    }

    // Audit log
    $pdo->prepare("INSERT INTO audit_log (user_id, action, module, table_affected, record_id) VALUES (?, ?, 'admin', ?, ?)")
        ->execute([$_SESSION['user_id'], 'DELETE_' . strtoupper($type), $c['table'], $id]);

    echo json_encode(['success' => true, 'message' => ucfirst(str_replace('_', ' ', $type)) . ' deleted successfully.']);

} catch (PDOException $e) {
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    echo json_encode(['success' => false, 'message' => 'Delete failed: ' . $e->getMessage()]);
}
?>
