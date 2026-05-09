<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php';
check_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $invoice_id = $_POST['invoice_id'];
    $amount_paid = $_POST['amount_paid'];
    $payment_mode = $_POST['payment_mode'];
    $reference_no = $_POST['reference_no'] ?? '';
    $collected_at = $_POST['collected_at'];

    try {
        $pdo->beginTransaction();

        // 1. Get Invoice Details
        $stmt = $pdo->prepare("SELECT * FROM ar_invoices WHERE ar_invoice_id = ? FOR UPDATE");
        $stmt->execute([$invoice_id]);
        $inv = $stmt->fetch();

        if (!$inv) throw new Exception("Invoice not found.");

        // 2. Record Collection
        $or_number = "OR-" . date('Ymd') . "-" . rand(1000, 9999);
        $stmt = $pdo->prepare("INSERT INTO collections (invoice_id, amount_paid, payment_mode, collected_by, collected_at, or_number) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$invoice_id, $amount_paid, $payment_mode, $_SESSION['user_id'], $collected_at, $or_number]);
        $collection_id = $pdo->lastInsertId();

        // 3. Issue Official Receipt
        $stmt = $pdo->prepare("INSERT INTO official_receipts (or_number, collection_id, customer_id, amount) VALUES (?, ?, ?, ?)");
        $stmt->execute([$or_number, $collection_id, $inv['customer_id'], $amount_paid]);
        $or_id = $pdo->lastInsertId();

        // 4. Update Invoice Balance
        $new_balance = $inv['balance_due'] - $amount_paid;
        $status = ($new_balance <= 0) ? 'Paid' : 'Partially Paid';
        
        $stmt = $pdo->prepare("UPDATE ar_invoices SET balance_due = ?, status = ? WHERE ar_invoice_id = ?");
        $stmt->execute([$new_balance, $status, $invoice_id]);

        // 5. Post to General Ledger (Integration)
        // Debit: Cash/Bank
        // Credit: Accounts Receivable
        
        $cash_acc = $pdo->query("SELECT account_id FROM chart_of_accounts WHERE account_name LIKE '%Cash%' LIMIT 1")->fetchColumn();
        $ar_acc = $pdo->query("SELECT account_id FROM chart_of_accounts WHERE account_name LIKE '%Receivable%' LIMIT 1")->fetchColumn();

        if ($cash_acc && $ar_acc) {
            $ref   = "COLL-" . str_pad($collection_id, 4, '0', STR_PAD_LEFT);
            $month = date('n');
            $year  = date('Y');

            // Auto-create the fiscal period if it doesn't exist yet
            $period = $pdo->query("SELECT period_id FROM period_status WHERE month=$month AND fiscal_year=$year")->fetchColumn();
            if (!$period) {
                $ins = $pdo->prepare("INSERT INTO period_status (month, fiscal_year, status) VALUES (?, ?, 'Open')");
                $ins->execute([$month, $year]);
                $period = $pdo->lastInsertId();
            }

            // Entries
            $stmt = $pdo->prepare("INSERT INTO gl_entries (journal_ref, account_id, debit, credit, transaction_date, period_id, posted_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$ref, $cash_acc, $amount_paid, 0,            $collected_at, $period, $_SESSION['user_id']]); // Debit Cash
            $stmt->execute([$ref, $ar_acc,   0,            $amount_paid, $collected_at, $period, $_SESSION['user_id']]); // Credit AR
        }

        $pdo->commit();
        header("Location: " . BASE_URL . "modules/collection/or_issuance.php?id=" . $or_id . "&msg=" . urlencode('Collection recorded successfully') . "&type=success");
        exit();

    } catch (Exception $e) {
        $pdo->rollBack();
        header("Location: " . BASE_URL . "modules/collection/index.php?msg=" . urlencode('Collection Error: ' . $e->getMessage()) . "&type=error");
        exit();
    }
}
?>
