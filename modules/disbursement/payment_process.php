<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php';
check_login();

// This page executes approved payments and posts to GL
$voucher_id = $_GET['id'] ?? null;

if ($voucher_id) {
    try {
        $pdo->beginTransaction();

        // 1. Get Voucher Details
        $stmt = $pdo->prepare("SELECT v.*, r.payee, r.purpose FROM payment_vouchers v JOIN payment_requests r ON v.request_id = r.request_id WHERE v.voucher_id = ? AND v.status = 'Approved'");
        $stmt->execute([$voucher_id]);
        $v = $stmt->fetch();

        if (!$v) {
            header("Location: " . BASE_URL . "modules/disbursement/index.php?msg=" . urlencode("Voucher not eligible for payment.") . "&type=error");
            exit();
        }

        // 2. Mark as Paid
        $stmt = $pdo->prepare("UPDATE payment_vouchers SET status = 'Paid' WHERE voucher_id = ?");
        $stmt->execute([$voucher_id]);
        
        $stmt = $pdo->prepare("UPDATE payment_requests SET status = 'Paid' WHERE request_id = ?");
        $stmt->execute([$v['request_id']]);

        // 3. Post to General Ledger (Direct Integration)
        // Debit: Expense (Assume a default petty cash/expense account for now)
        // Credit: Cash/Bank
        
        // Find Cash Account (simplified search)
        $cash_acc = $pdo->query("SELECT account_id FROM chart_of_accounts WHERE account_name LIKE '%Cash%' LIMIT 1")->fetchColumn();
        $exp_acc = $pdo->query("SELECT account_id FROM chart_of_accounts WHERE account_type = 'Expense' LIMIT 1")->fetchColumn();

        if ($cash_acc && $exp_acc) {
            $ref = "DISB-" . str_pad($voucher_id, 4, '0', STR_PAD_LEFT);
            $date = date('Y-m-d');
            
            // Get Period
            $month = date('n');
            $year = date('Y');
            $period = $pdo->query("SELECT period_id FROM period_status WHERE month=$month AND fiscal_year=$year")->fetchColumn();

            // Insert Journal Header
            $stmt = $pdo->prepare("INSERT INTO journal_headers (description, status, prepared_by, period_id) VALUES (?, 'Posted', ?, ?)");
            $stmt->execute(["Disbursement to " . $v['payee'], $_SESSION['user_id'], $period]);
            $journal_id = $pdo->lastInsertId();

            // Entries
            $stmt = $pdo->prepare("INSERT INTO gl_entries (journal_ref, account_id, debit, credit, transaction_date) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$ref, $exp_acc, $v['amount'], 0, $date]); // Debit Expense
            $stmt->execute([$ref, $cash_acc, 0, $v['amount'], $date]); // Credit Cash

            // Log Disbursement
            $stmt = $pdo->prepare("INSERT INTO disbursement_log (voucher_id, payment_date, reference_no) VALUES (?, ?, ?)");
            $stmt->execute([$voucher_id, $date, $ref]);

            // NEW: Record to actual_expenditures for Dashboard & Budget Tracking
            require_once __DIR__ . '/../budget/budget_control.php';
            $budget_stmt = $pdo->prepare("SELECT budget_id FROM approved_budgets WHERE account_id = ? AND fiscal_year = ?");
            $budget_stmt->execute([$exp_acc, $year]);
            $budget_id = $budget_stmt->fetchColumn();

            if ($budget_id) {
                $budgetControl->recordExpenditure($budget_id, $v['amount'], 'Disbursement', $voucher_id);
            }
        }

        $pdo->commit();
        header("Location: " . BASE_URL . "modules/disbursement/index.php?msg=" . urlencode('Payment processed and posted to GL') . "&type=success");
        exit();

    } catch (Exception $e) {
        $pdo->rollBack();
        header("Location: " . BASE_URL . "modules/disbursement/index.php?msg=" . urlencode('Payment Processing Error: ' . $e->getMessage()) . "&type=error");
        exit();
    }
}
?>
