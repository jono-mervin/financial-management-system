-- SMS (Service Management System) Full Database Schema
-- Version 1.2 | March 2026

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- --------------------------------------------------------
-- CORE SHARED TABLES
-- --------------------------------------------------------

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('Admin','Finance','Manager','Staff') NOT NULL,
  `is_locked` tinyint(1) DEFAULT 0,
  `failed_attempts` int(11) DEFAULT 0,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `login_attempts` (
  `attempt_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) NOT NULL,
  `attempted_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `success` tinyint(1) NOT NULL,
  PRIMARY KEY (`attempt_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `otp_tokens` (
  `token_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `otp_hash` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) DEFAULT 0,
  `action_context` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`token_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `sessions` (
  `session_id` varchar(255) NOT NULL,
  `user_id` int(11) NOT NULL,
  `last_activity` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  PRIMARY KEY (`session_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `audit_log` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `module` varchar(50) NOT NULL,
  `table_affected` varchar(50) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `roles_permissions` (
  `perm_id` int(11) NOT NULL AUTO_INCREMENT,
  `role` enum('Admin','Finance','Manager','Staff') NOT NULL,
  `module` varchar(50) NOT NULL,
  `can_view` tinyint(1) DEFAULT 0,
  `can_create` tinyint(1) DEFAULT 0,
  `can_approve` tinyint(1) DEFAULT 0,
  `can_void` tinyint(1) DEFAULT 0,
  `can_admin` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`perm_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `period_status` (
  `period_id` int(11) NOT NULL AUTO_INCREMENT,
  `fiscal_year` int(4) NOT NULL,
  `month` int(2) NOT NULL,
  `status` enum('open','closed') DEFAULT 'open',
  `closed_by` int(11) DEFAULT NULL,
  `closed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`period_id`),
  FOREIGN KEY (`closed_by`) REFERENCES `users`(`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `exchange_rates` (
  `rate_id` int(11) NOT NULL AUTO_INCREMENT,
  `currency_code` varchar(3) NOT NULL,
  `rate_to_base` decimal(15,6) NOT NULL,
  `effective_date` date NOT NULL,
  PRIMARY KEY (`rate_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `notifications` (
  `notif_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `module` varchar(50) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`notif_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- GENERAL LEDGER (GL) TABLES
-- --------------------------------------------------------

CREATE TABLE `chart_of_accounts` (
  `account_id` int(11) NOT NULL AUTO_INCREMENT,
  `account_code` varchar(20) NOT NULL,
  `account_name` varchar(100) NOT NULL,
  `account_type` enum('Asset','Liability','Equity','Revenue','Expense') NOT NULL,
  `normal_balance` enum('Debit','Credit') NOT NULL,
  `parent_account_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`account_id`),
  UNIQUE KEY `account_code` (`account_code`),
  FOREIGN KEY (`parent_account_id`) REFERENCES `chart_of_accounts`(`account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `journal_headers` (
  `journal_id` int(11) NOT NULL AUTO_INCREMENT,
  `description` text,
  `status` enum('Draft','Pending','Approved','Posted','Rejected') DEFAULT 'Draft',
  `prepared_by` int(11) NOT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `period_id` int(11) NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`journal_id`),
  FOREIGN KEY (`prepared_by`) REFERENCES `users`(`user_id`),
  FOREIGN KEY (`approved_by`) REFERENCES `users`(`user_id`),
  FOREIGN KEY (`period_id`) REFERENCES `period_status`(`period_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `gl_entries` (
  `entry_id` int(11) NOT NULL AUTO_INCREMENT,
  `journal_ref` varchar(50) NOT NULL,
  `account_id` int(11) NOT NULL,
  `debit` decimal(15,2) DEFAULT 0.00,
  `credit` decimal(15,2) DEFAULT 0.00,
  `transaction_date` date DEFAULT NULL,
  `cost_center` varchar(50) DEFAULT NULL,
  `period_id` int(11) NOT NULL,
  `posted_by` int(11) NOT NULL,
  `posted_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `source_module` varchar(50) DEFAULT NULL,
  `source_record_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`entry_id`),
  FOREIGN KEY (`account_id`) REFERENCES `chart_of_accounts`(`account_id`),
  FOREIGN KEY (`period_id`) REFERENCES `period_status`(`period_id`),
  FOREIGN KEY (`posted_by`) REFERENCES `users`(`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- DISBURSEMENT TABLES
-- --------------------------------------------------------

CREATE TABLE `payment_requests` (
  `request_id` int(11) NOT NULL AUTO_INCREMENT,
  `payee` varchar(255) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `purpose` text DEFAULT NULL,
  `status` enum('Pending','Approved','Paid','Rejected') DEFAULT 'Pending',
  `requested_by` int(11) NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`request_id`),
  FOREIGN KEY (`requested_by`) REFERENCES `users`(`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `payment_vouchers` (
  `voucher_id` int(11) NOT NULL AUTO_INCREMENT,
  `request_id` int(11) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `tax_withheld` decimal(15,2) DEFAULT 0.00,
  `net_amount` decimal(15,2) NOT NULL,
  `payment_mode` enum('Cash','Check','ACH') NOT NULL,
  `status` enum('Draft','Pending','Approved','Paid','Voided') DEFAULT 'Draft',
  PRIMARY KEY (`voucher_id`),
  FOREIGN KEY (`request_id`) REFERENCES `payment_requests`(`request_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `payment_approvals` (
  `approval_id` int(11) NOT NULL AUTO_INCREMENT,
  `voucher_id` int(11) NOT NULL,
  `approver_id` int(11) NOT NULL,
  `level` int(11) NOT NULL,
  `action` enum('Approve','Reject','Hold') NOT NULL,
  `remarks` text,
  `acted_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`approval_id`),
  FOREIGN KEY (`voucher_id`) REFERENCES `payment_vouchers`(`voucher_id`),
  FOREIGN KEY (`approver_id`) REFERENCES `users`(`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `disbursement_log` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `voucher_id` int(11) NOT NULL,
  `payment_date` date NOT NULL,
  `reference_no` varchar(50) DEFAULT NULL,
  `bank_ref` varchar(50) DEFAULT NULL,
  `gl_entry_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`log_id`),
  FOREIGN KEY (`voucher_id`) REFERENCES `payment_vouchers`(`voucher_id`),
  FOREIGN KEY (`gl_entry_id`) REFERENCES `gl_entries`(`entry_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- COLLECTION TABLES
-- --------------------------------------------------------

CREATE TABLE `billing_invoices` (
  `invoice_id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `due_date` date NOT NULL,
  `status` enum('Unpaid','Partially Paid','Paid','Overdue','Voided') DEFAULT 'Unpaid',
  `billing_period` varchar(20) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`invoice_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `deposit_batches` (
  `batch_id` int(11) NOT NULL AUTO_INCREMENT,
  `total_amount` decimal(15,2) NOT NULL,
  `deposit_date` date NOT NULL,
  `bank_ref` varchar(50) DEFAULT NULL,
  `prepared_by` int(11) NOT NULL,
  `status` enum('Pending','Deposited','Cleared') DEFAULT 'Pending',
  PRIMARY KEY (`batch_id`),
  FOREIGN KEY (`prepared_by`) REFERENCES `users`(`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `collections` (
  `collection_id` int(11) NOT NULL AUTO_INCREMENT,
  `invoice_id` int(11) DEFAULT NULL,
  `batch_id` int(11) DEFAULT NULL,
  `amount_paid` decimal(15,2) NOT NULL,
  `payment_mode` enum('Cash','Check','Bank Transfer','E-Wallet') NOT NULL,
  `collected_by` int(11) NOT NULL,
  `collected_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `or_number` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`collection_id`),
  FOREIGN KEY (`invoice_id`) REFERENCES `billing_invoices`(`invoice_id`),
  FOREIGN KEY (`batch_id`) REFERENCES `deposit_batches`(`batch_id`),
  FOREIGN KEY (`collected_by`) REFERENCES `users`(`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `official_receipts` (
  `or_id` int(11) NOT NULL AUTO_INCREMENT,
  `or_number` varchar(50) NOT NULL,
  `collection_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `is_void` tinyint(1) DEFAULT 0,
  `void_reason` text,
  PRIMARY KEY (`or_id`),
  UNIQUE KEY `or_number` (`or_number`),
  FOREIGN KEY (`collection_id`) REFERENCES `collections`(`collection_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `departments` (
  `department_id` int(11) NOT NULL AUTO_INCREMENT,
  `dept_name` varchar(100) NOT NULL,
  `dept_code` varchar(10) NOT NULL,
  `managed_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`department_id`),
  UNIQUE KEY `dept_code` (`dept_code`),
  FOREIGN KEY (`managed_by`) REFERENCES `users`(`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- BUDGET TABLES
-- --------------------------------------------------------

CREATE TABLE `budget_proposals` (
  `proposal_id` int(11) NOT NULL AUTO_INCREMENT,
  `fiscal_year` int(4) NOT NULL,
  `department_id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `proposed_amount` decimal(15,2) NOT NULL,
  `status` enum('Draft','Submitted','Reviewed','Approved','Rejected') DEFAULT 'Draft',
  PRIMARY KEY (`proposal_id`),
  FOREIGN KEY (`account_id`) REFERENCES `chart_of_accounts`(`account_id`),
  FOREIGN KEY (`department_id`) REFERENCES `departments`(`department_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `approved_budgets` (
  `budget_id` int(11) NOT NULL AUTO_INCREMENT,
  `proposal_id` int(11) DEFAULT NULL,
  `fiscal_year` int(4) NOT NULL,
  `account_id` int(11) NOT NULL,
  `approved_amount` decimal(15,2) NOT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  PRIMARY KEY (`budget_id`),
  FOREIGN KEY (`proposal_id`) REFERENCES `budget_proposals`(`proposal_id`),
  FOREIGN KEY (`account_id`) REFERENCES `chart_of_accounts`(`account_id`),
  FOREIGN KEY (`approved_by`) REFERENCES `users`(`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `allotment_ledger` (
  `allotment_id` int(11) NOT NULL AUTO_INCREMENT,
  `budget_id` int(11) NOT NULL,
  `amount_released` decimal(15,2) NOT NULL,
  `released_by` int(11) NOT NULL,
  `released_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`allotment_id`),
  FOREIGN KEY (`budget_id`) REFERENCES `approved_budgets`(`budget_id`),
  FOREIGN KEY (`released_by`) REFERENCES `users`(`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `obligations` (
  `obligation_id` int(11) NOT NULL AUTO_INCREMENT,
  `budget_id` int(11) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `source_type` varchar(50) DEFAULT NULL,
  `source_id` int(11) DEFAULT NULL,
  `status` enum('Encumbered','Liquidated','Cancelled') DEFAULT 'Encumbered',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`obligation_id`),
  FOREIGN KEY (`budget_id`) REFERENCES `approved_budgets`(`budget_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `actual_expenditures` (
  `actual_id` int(11) NOT NULL AUTO_INCREMENT,
  `budget_id` int(11) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `source_module` varchar(50) NOT NULL,
  `source_record_id` int(11) NOT NULL,
  `recorded_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`actual_id`),
  FOREIGN KEY (`budget_id`) REFERENCES `approved_budgets`(`budget_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- AP/AR TABLES
-- --------------------------------------------------------

CREATE TABLE `vendors` (
  `vendor_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `tin` varchar(20) DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `bank_account` varchar(50) DEFAULT NULL,
  `credit_terms` int(11) DEFAULT 0,
  `accreditation_status` enum('Active','Pending','Blacklisted') DEFAULT 'Pending',
  PRIMARY KEY (`vendor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `ap_invoices` (
  `ap_invoice_id` int(11) NOT NULL AUTO_INCREMENT,
  `vendor_id` int(11) NOT NULL,
  `po_id` int(11) DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `tax_withheld` decimal(15,2) DEFAULT 0.00,
  `status` enum('Draft','Pending','Approved','Paid','Voided') DEFAULT 'Draft',
  `due_date` date NOT NULL,
  PRIMARY KEY (`ap_invoice_id`),
  FOREIGN KEY (`vendor_id`) REFERENCES `vendors`(`vendor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `ap_vouchers` (
  `ap_voucher_id` int(11) NOT NULL AUTO_INCREMENT,
  `ap_invoice_id` int(11) NOT NULL,
  `gl_entry_id` int(11) DEFAULT NULL,
  `status` enum('Draft','Approved','Posted') DEFAULT 'Draft',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ap_voucher_id`),
  FOREIGN KEY (`ap_invoice_id`) REFERENCES `ap_invoices`(`ap_invoice_id`),
  FOREIGN KEY (`gl_entry_id`) REFERENCES `gl_entries`(`entry_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `customers` (
  `customer_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `tin` varchar(20) DEFAULT NULL,
  `credit_limit` decimal(15,2) DEFAULT 0.00,
  `is_on_hold` tinyint(1) DEFAULT 0,
  `billing_terms` int(11) DEFAULT 0,
  PRIMARY KEY (`customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `ar_invoices` (
  `ar_invoice_id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `due_date` date NOT NULL,
  `balance_due` decimal(15,2) NOT NULL,
  `status` enum('Unpaid','Partially Paid','Paid','Voided') DEFAULT 'Unpaid',
  PRIMARY KEY (`ar_invoice_id`),
  FOREIGN KEY (`customer_id`) REFERENCES `customers`(`customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `ar_ledger` (
  `ledger_id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `ar_invoice_id` int(11) DEFAULT NULL,
  `transaction_type` enum('Invoice','Payment','Adjustment') NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `transaction_date` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ledger_id`),
  FOREIGN KEY (`customer_id`) REFERENCES `customers`(`customer_id`),
  FOREIGN KEY (`ar_invoice_id`) REFERENCES `ar_invoices`(`ar_invoice_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

COMMIT;
