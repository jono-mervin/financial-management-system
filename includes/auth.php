<?php
// Authentication Helpers
require_once 'db.php';

function login($email, $password) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && !$user['is_locked'] && password_verify($password, $user['password_hash'])) {
        // Successful login
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['last_activity'] = time();
        
        // Reset failed attempts
        $reset = $pdo->prepare("UPDATE users SET failed_attempts = 0, last_login = NOW() WHERE user_id = ?");
        $reset->execute([$user['user_id']]);
        
        session_regenerate_id(true);
        return true;
    } else {
        // Failed login
        if ($user) {
            $stmt = $pdo->prepare("UPDATE users SET failed_attempts = failed_attempts + 1 WHERE user_id = ?");
            $stmt->execute([$user['user_id']]);
            
            // Lock if threshold reached
            if ($user['failed_attempts'] >= 5) {
                $lock = $pdo->prepare("UPDATE users SET is_locked = 1 WHERE user_id = ?");
                $lock->execute([$user['user_id']]);
            }
        }
        return false;
    }
}

function has_permission($module, $action) {
    global $pdo;
    if (!isset($_SESSION['role'])) return false;
    
    $stmt = $pdo->prepare("SELECT can_{$action} FROM roles_permissions WHERE role = ? AND module = ?");
    $stmt->execute([$_SESSION['role'], $module]);
    $perm = $stmt->fetch();
    
    return $perm && $perm["can_{$action}"] == 1;
}

function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
?>
