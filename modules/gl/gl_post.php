<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php';
check_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reference = $_POST['reference'] ?? '';
    $transaction_date = $_POST['transaction_date'] ?? date('Y-m-d');
    $description = $_POST['description'] ?? '';
    $accounts = $_POST['accounts'] ?? [];
    $debits = $_POST['debits'] ?? [];
    $credits = $_POST['credits'] ?? [];

    // Validation: Total Debits must equal Total Credits
    $total_debit = array_sum($debits);
    $total_credit = array_sum($credits);

    if (abs($total_debit - $total_credit) > 0.001) {
        header("Location: " . BASE_URL . "modules/gl/index.php?msg=" . urlencode("Error: Balanced entry required (Debits: $total_debit, Credits: $total_credit)") . "&type=error");
        exit();
    }

    try {
        $pdo->beginTransaction();

        // 1. Get or Create Period
        $month = date('n', strtotime($transaction_date));
        $year = date('Y', strtotime($transaction_date));
        
        $stmt = $pdo->prepare("SELECT period_id FROM period_status WHERE month = ? AND fiscal_year = ? AND status = 'open'");
        $stmt->execute([$month, $year]);
        $period = $stmt->fetch();

        if (!$period) {
            // Auto-open period for demo purposes if not exist
            $pdo->prepare("INSERT INTO period_status (month, fiscal_year, status) VALUES (?, ?, 'open')")
                ->execute([$month, $year]);
            $period_id = $pdo->lastInsertId();
        } else {
            $period_id = $period['period_id'];
        }

        // 2. Insert Journal Header
        // Maker-Checker: If user doesn't have approve permission, set to 'Pending'
        require_once '../../includes/auth.php';
        $status = has_permission('gl', 'approve') ? 'Posted' : 'Pending';

        $stmt = $pdo->prepare("INSERT INTO journal_headers (description, status, prepared_by, period_id) VALUES (?, ?, ?, ?)");
        $stmt->execute([$description, $status, $_SESSION['user_id'], $period_id]);
        $journal_id = $pdo->lastInsertId();

        // 3. Insert GL Entries
        $stmt = $pdo->prepare("INSERT INTO gl_entries (journal_ref, account_id, debit, credit, transaction_date) VALUES (?, ?, ?, ?, ?)");
        
        for ($i = 0; $i < count($accounts); $i++) {
            if ($debits[$i] > 0 || $credits[$i] > 0) {
                $stmt->execute([
                    $reference,
                    $accounts[$i],
                    $debits[$i],
                    $credits[$i],
                    $transaction_date
                ]);
            }
        }

        // 4. Audit Log
        $pdo->prepare("INSERT INTO audit_log (user_id, action, module, table_affected, record_id) VALUES (?, 'POST_JOURNAL', 'GL', 'journal_headers', ?)")
            ->execute([$_SESSION['user_id'], $journal_id]);

        $pdo->commit();
        header("Location: " . BASE_URL . "modules/gl/index.php?msg=" . urlencode('Journal entry posted successfully') . "&type=success");
        exit();

    } catch (Exception $e) {
        $pdo->rollBack();
        header("Location: " . BASE_URL . "modules/gl/index.php?msg=" . urlencode("Transaction failed: " . $e->getMessage()) . "&type=error");
        exit();
    }
}
?>
