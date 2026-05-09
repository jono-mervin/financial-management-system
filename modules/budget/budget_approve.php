<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php';
check_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $proposal_id = (int)$_POST['proposal_id'];
    $action = $_POST['action']; // 'approve' or 'reject'

    if (!in_array($action, ['approve', 'reject'])) {
        header("Location: index.php");
        exit();
    }

    $new_status = ($action === 'approve') ? 'Approved' : 'Rejected';

    try {
        $pdo->beginTransaction();

        // Update the proposal status
        $stmt = $pdo->prepare("UPDATE budget_proposals SET status = ? WHERE proposal_id = ?");
        $stmt->execute([$new_status, $proposal_id]);

        // If approved, insert into approved_budgets table
        if ($action === 'approve') {
            // Get the proposal details first
            $prop = $pdo->prepare("SELECT * FROM budget_proposals WHERE proposal_id = ?");
            $prop->execute([$proposal_id]);
            $p = $prop->fetch();

            if ($p) {
                $stmt2 = $pdo->prepare("INSERT INTO approved_budgets (proposal_id, fiscal_year, account_id, approved_amount, approved_by, approved_at)
                                        VALUES (?, ?, ?, ?, ?, NOW())
                                        ON DUPLICATE KEY UPDATE approved_amount = VALUES(approved_amount), approved_by = VALUES(approved_by), approved_at = NOW()");
                $stmt2->execute([$proposal_id, $p['fiscal_year'], $p['account_id'], $p['proposed_amount'], $_SESSION['user_id']]);
            }
        }

        $pdo->commit();
        header("Location: " . BASE_URL . "modules/budget/index.php?msg=" . urlencode("Budget proposal " . strtolower($new_status) . " successfully") . "&type=success");
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        header("Location: " . BASE_URL . "modules/budget/index.php?msg=" . urlencode('Budget Approval Error: ' . $e->getMessage()) . "&type=error");
        exit();
    }
}

header("Location: " . BASE_URL . "modules/budget/index.php");
exit();
?>
