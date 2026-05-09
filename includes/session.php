<?php
// Session Management
require_once __DIR__ . '/db.php';
session_start();

// Session Timeout (15 minutes)
$timeout_duration = 900;

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout_duration)) {
    session_unset();
    session_destroy();
    header("Location: " . BASE_URL . "login.php?timeout=1");
    exit();
}

$_SESSION['last_activity'] = time();

function check_login() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: " . BASE_URL . "login.php");
        exit();
    }
}
?>
