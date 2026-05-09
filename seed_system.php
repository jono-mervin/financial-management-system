<?php
/**
 * System Initialization and User Seeding Script
 * Run this after creating the database using schema.sql
 */

require_once 'includes/db.php';

echo "<h2>SMS System Initialization</h2>";
echo "<pre>";

try {
    $pdo->beginTransaction();

    // 1. Initialize Financial Period
    echo "Initializing Financial Period... ";
    $month = date('n');
    $year = date('Y');
    $stmt = $pdo->prepare("INSERT IGNORE INTO period_status (period_id, month, fiscal_year, status) VALUES (1, ?, ?, 'open')");
    $stmt->execute([$month, $year]);
    echo "OK\n";

    // 2. Seed Default Roles and Permissions
    echo "Seeding Roles and Permissions... ";
    $roles = ['Admin', 'Finance', 'Manager', 'Staff'];
    $modules = ['gl', 'disbursement', 'collection', 'budget', 'ap_ar', 'dashboard', 'admin'];
    
    // Clear existing to avoid duplicates
    $pdo->exec("DELETE FROM roles_permissions");
    
    $stmt = $pdo->prepare("INSERT INTO roles_permissions (role, module, can_view, can_create, can_approve, can_void, can_admin) 
                          VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    foreach ($roles as $role) {
        foreach ($modules as $module) {
            $can_view = 1;
            $can_create = ($role !== 'Staff') ? 1 : 0;
            $can_approve = ($role === 'Admin' || $role === 'Manager') ? 1 : 0;
            $can_void = ($role === 'Admin') ? 1 : 0;
            $can_admin = ($role === 'Admin') ? 1 : 0;
            
            // Special rules for admin module
            if ($module === 'admin' && $role !== 'Admin') {
                $can_view = 0; $can_create = 0; $can_approve = 0; $can_void = 0; $can_admin = 0;
            }

            $stmt->execute([$role, $module, $can_view, $can_create, $can_approve, $can_void, $can_admin]);
        }
    }
    echo "OK\n";

    // 3. Create Default User Accounts
    echo "Creating Default User Accounts... ";
    $password_default = password_hash('Password123!', PASSWORD_DEFAULT);
    $users = [
        ['System Administrator', 'admin@sms.com', 'Admin'],
        ['Finance Officer', 'finance@sms.com', 'Finance'],
        ['Operations Manager', 'manager@sms.com', 'Manager'],
        ['General Staff', 'staff@sms.com', 'Staff']
    ];

    $stmt = $pdo->prepare("INSERT IGNORE INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)");
    foreach ($users as $user) {
        $stmt->execute([$user[0], $user[1], $password_default, $user[2]]);
    }
    echo "OK\n";

    // 4. Seed Basic Departments
    echo "Seeding Departments... ";
    $depts = [
        ['Finance & Accounting', 'FIN'],
        ['Human Resources', 'HR'],
        ['IT Operations', 'IT'],
        ['Marketing & Sales', 'MKT']
    ];
    $stmt = $pdo->prepare("INSERT IGNORE INTO departments (dept_name, dept_code) VALUES (?, ?)");
    foreach ($depts as $d) {
        $stmt->execute($d);
    }
    echo "OK\n";

    // 4.5 Safety: Fix collections table — add batch_id if missing
    echo "Fixing collections table (batch_id column)... ";
    $pdo->exec("ALTER TABLE collections ADD COLUMN IF NOT EXISTS batch_id INT(11) DEFAULT NULL AFTER invoice_id");
    // Add FK only if it doesn't exist yet
    $fk = $pdo->query("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE 
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'collections' AND COLUMN_NAME = 'batch_id' AND REFERENCED_TABLE_NAME = 'deposit_batches'")->fetchColumn();
    if (!$fk) {
        try {
            $pdo->exec("ALTER TABLE collections ADD CONSTRAINT fk_collections_batch FOREIGN KEY (batch_id) REFERENCES deposit_batches(batch_id)");
        } catch (Exception $ignore) {}
    }
    echo "OK\n";

    // 5. Seed Core Chart of Accounts
    echo "Seeding Chart of Accounts... ";
    
    // Safety: Add normal_balance column if it doesn't exist (fixes the error)
    $pdo->exec("ALTER TABLE chart_of_accounts ADD COLUMN IF NOT EXISTS normal_balance ENUM('Debit', 'Credit') NOT NULL AFTER account_type");

    $coa = [
        ['1000', 'Cash and Equivalents', 'Asset', 'Debit'],
        ['1200', 'Accounts Receivable', 'Asset', 'Debit'],
        ['2000', 'Accounts Payable', 'Liability', 'Credit'],
        ['4000', 'Service Revenue', 'Revenue', 'Credit'],
        ['5000', 'Operating Expenses', 'Expense', 'Debit']
    ];
    $stmt = $pdo->prepare("INSERT IGNORE INTO chart_of_accounts (account_code, account_name, account_type, normal_balance) VALUES (?, ?, ?, ?)");
    foreach ($coa as $c) {
        $stmt->execute($c);
    }
    echo "OK\n";

    $pdo->commit();
    echo "\n<b>System Initialization Successful!</b>\n";
    echo "You can now log in using:\n";
    echo "- Email: admin@sms.com\n";
    echo "- Password: Password123!\n";

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo "\n[ERROR] Initialization failed: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>
