<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php';
check_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dept_id = $_POST['department_id'];
    $year = $_POST['fiscal_year'];
    $accounts = $_POST['accounts'];
    $amounts = $_POST['amounts'];

    try {
        $pdo->beginTransaction();

        for ($i = 0; $i < count($accounts); $i++) {
            if (empty($accounts[$i]) || empty($amounts[$i])) continue;
            
            $stmt = $pdo->prepare("INSERT INTO budget_proposals (fiscal_year, department_id, account_id, proposed_amount, status) VALUES (?, ?, ?, ?, 'Submitted')");
            $stmt->execute([$year, $dept_id, $accounts[$i], $amounts[$i]]);
        }

        $pdo->commit();
        header("Location: " . BASE_URL . "modules/budget/index.php?msg=" . urlencode('Budget proposal submitted successfully') . "&type=success");
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        header("Location: " . BASE_URL . "modules/budget/index.php?msg=" . urlencode('Budget Preparation Error: ' . $e->getMessage()) . "&type=error");
        exit();
    }
}
?>
