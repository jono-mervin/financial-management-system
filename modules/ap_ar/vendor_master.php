<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php';
check_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $tin = $_POST['tin'];
    $bank_name = $_POST['bank_name'];
    $bank_account = $_POST['bank_account'];
    $credit_terms = $_POST['credit_terms'];
    $status = $_POST['accreditation_status'];

    try {
        $stmt = $pdo->prepare("INSERT INTO vendors (name, tin, bank_name, bank_account, credit_terms, accreditation_status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $tin, $bank_name, $bank_account, $credit_terms, $status]);
        
        header("Location: " . BASE_URL . "modules/ap_ar/index.php?msg=" . urlencode("Vendor '$name' added successfully") . "&type=success");
        exit();
    } catch (Exception $e) {
        header("Location: " . BASE_URL . "modules/ap_ar/index.php?msg=" . urlencode("Vendor Master Error: " . $e->getMessage()) . "&type=error");
        exit();
    }
}
?>
