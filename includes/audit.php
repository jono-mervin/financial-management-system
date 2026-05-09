<?php
// Audit Logging Helper
require_once 'db.php';

function log_action($action, $module, $table = null, $record_id = null, $old_val = null, $new_val = null) {
    global $pdo;
    
    $user_id = $_SESSION['user_id'] ?? null;
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'CLI';
    
    $stmt = $pdo->prepare("INSERT INTO audit_log (user_id, action, module, table_affected, record_id, old_value, new_value, ip_address) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $user_id,
        $action,
        $module,
        $table,
        $record_id,
        $old_val ? json_encode($old_val) : null,
        $new_val ? json_encode($new_val) : null,
        $ip
    ]);
}
?>
