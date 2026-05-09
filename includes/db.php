<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'sms_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('BASE_URL', '/commission/sms/financial/');

// Philippine Standards
define('CURRENCY', '₱');
define('VAT_RATE', 0.12);
define('EWT_GOODS', 0.01);
define('EWT_SERVICES', 0.02);
define('EWT_RENT', 0.05);
define('EWT_PROFESSIONAL', 0.10);

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
