<?php
require_once __DIR__ . '/../../includes/db.php';

/**
 * Core Budget Integrity Module
 * Ensures spending does not exceed available funds.
 */

class BudgetControl {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Check if a specific amount can be spent from an account's budget
     */
    public function checkAvailability($account_id, $amount, $fiscal_year = null) {
        if (!$fiscal_year) $fiscal_year = date('Y');

        // 1. Get Approved Budget
        $stmt = $this->pdo->prepare("SELECT approved_amount, budget_id FROM approved_budgets WHERE account_id = ? AND fiscal_year = ?");
        $stmt->execute([$account_id, $fiscal_year]);
        $budget = $stmt->fetch();

        if (!$budget) return ['allowed' => false, 'reason' => 'No approved budget for this account.', 'balance' => 0];

        // 2. Get Actual Spending
        $stmt = $this->pdo->prepare("SELECT SUM(amount) FROM actual_expenditures WHERE budget_id = ?");
        $stmt->execute([$budget['budget_id']]);
        $actual = $stmt->fetchColumn() ?: 0;

        // 3. Get Pending Obligations (Encumbrances)
        $stmt = $this->pdo->prepare("SELECT SUM(amount) FROM obligations WHERE budget_id = ? AND status = 'Encumbered'");
        $stmt->execute([$budget['budget_id']]);
        $obligations = $stmt->fetchColumn() ?: 0;

        $available = $budget['approved_amount'] - ($actual + $obligations);

        if ($amount > $available) {
            return [
                'allowed' => false, 
                'reason' => 'Insufficient budget. Request: ' . number_format($amount, 2) . ', Available: ' . number_format($available, 2),
                'balance' => $available
            ];
        }

        return ['allowed' => true, 'balance' => $available];
    }

    /**
     * Record an expenditure against the budget
     */
    public function recordExpenditure($budget_id, $amount, $module, $record_id) {
        $stmt = $this->pdo->prepare("INSERT INTO actual_expenditures (budget_id, amount, source_module, source_record_id) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$budget_id, $amount, $module, $record_id]);
    }
}

// Instantiate for global use
$budgetControl = new BudgetControl($pdo);
?>
