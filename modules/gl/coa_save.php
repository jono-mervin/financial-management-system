<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php';
check_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'create';
    $code = trim($_POST['account_code']);
    $name = trim($_POST['account_name']);
    $type = $_POST['account_type'];
    $balance = $_POST['normal_balance'];

    try {
        if ($action === 'create') {
            $stmt = $pdo->prepare("INSERT INTO chart_of_accounts (account_code, account_name, account_type, normal_balance) VALUES (?, ?, ?, ?)");
            $stmt->execute([$code, $name, $type, $balance]);
            $msg = "Account created successfully!";
        } elseif ($action === 'update') {
            $id = $_POST['account_id'];
            $stmt = $pdo->prepare("UPDATE chart_of_accounts SET account_code = ?, account_name = ?, account_type = ?, normal_balance = ? WHERE account_id = ?");
            $stmt->execute([$code, $name, $type, $balance, $id]);
            $msg = "Account updated successfully!";
        }
        header("Location: coa_manager.php?msg=" . urlencode($msg) . "&type=success");
        exit();
    } catch (PDOException $e) {
        // Simple error handling, could be improved with standard toast
        header("Location: coa_manager.php?msg=" . urlencode("Error: " . $e->getMessage()) . "&type=error");
        exit();
    }
}
header("Location: coa_manager.php");
exit();
?>
