<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/auth.php';
check_login();

if (!has_permission('gl', 'approve')) {
    header("Location: " . BASE_URL . "index.php?error=unauthorized");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $journal_id = $_POST['journal_id'];
    $action = $_POST['action']; // Approve or Reject

    try {
        $pdo->beginTransaction();

        $status = ($action === 'Approve') ? 'Posted' : 'Rejected';
        
        $stmt = $pdo->prepare("UPDATE journal_headers SET status = ? WHERE journal_id = ?");
        $stmt->execute([$status, $journal_id]);

        // Audit Log
        $pdo->prepare("INSERT INTO audit_log (user_id, action, module, table_affected, record_id) VALUES (?, ?, 'GL', 'journal_headers', ?)")
            ->execute([$_SESSION['user_id'], strtoupper($action) . '_JOURNAL', $journal_id]);

        $pdo->commit();
        header("Location: " . BASE_URL . "modules/gl/index.php?msg=" . urlencode("Journal entry " . strtolower($status) . " successfully") . "&type=success");
        exit();

    } catch (Exception $e) {
        $pdo->rollBack();
        header("Location: " . BASE_URL . "modules/gl/index.php?msg=" . urlencode("Approval Error: " . $e->getMessage()) . "&type=error");
        exit();
    }
}
?>
