<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php';
check_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $tin = $_POST['tin'];
    $credit_limit = $_POST['credit_limit'];
    $is_on_hold = isset($_POST['is_on_hold']) ? 1 : 0;

    try {
        $stmt = $pdo->prepare("INSERT INTO customers (name, tin, credit_limit, is_on_hold) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $tin, $credit_limit, $is_on_hold]);
        
        header("Location: " . BASE_URL . "modules/ap_ar/index.php?msg=" . urlencode("Customer '$name' added successfully") . "&type=success");
        exit();
    } catch (Exception $e) {
        header("Location: " . BASE_URL . "modules/ap_ar/index.php?msg=" . urlencode("Customer Master Error: " . $e->getMessage()) . "&type=error");
        exit();
    }
}
?>
