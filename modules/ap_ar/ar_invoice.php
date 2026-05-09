<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php';
check_login();

$current_page = 'ar_invoice.php';
$current_dir = 'ap_ar';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_id = $_POST['customer_id'];
    $amount = $_POST['amount'];
    $due_date = $_POST['due_date'];

    try {
        $pdo->beginTransaction();

        // 1. Record AR Invoice
        $stmt = $pdo->prepare("INSERT INTO ar_invoices (customer_id, amount, due_date, balance_due, status) VALUES (?, ?, ?, ?, 'Unpaid')");
        $stmt->execute([$customer_id, $amount, $due_date, $amount]);
        $invoice_id = $pdo->lastInsertId();

        // 2. Post to GL (Simulated)
        // Debit: Accounts Receivable
        // Credit: Revenue
        
        $ar_acc = $pdo->query("SELECT account_id FROM chart_of_accounts WHERE account_name LIKE '%Receivable%' LIMIT 1")->fetchColumn();
        $rev_acc = $pdo->query("SELECT account_id FROM chart_of_accounts WHERE account_type = 'Revenue' LIMIT 1")->fetchColumn();

        if ($ar_acc && $rev_acc) {
            $ref = "ARINV-" . str_pad($invoice_id, 4, '0', STR_PAD_LEFT);
            $month = date('n'); $year = date('Y');
            $period = $pdo->query("SELECT period_id FROM period_status WHERE month=$month AND fiscal_year=$year")->fetchColumn();

            $stmt = $pdo->prepare("INSERT INTO journal_headers (description, status, prepared_by, period_id) VALUES (?, 'Posted', ?, ?)");
            $stmt->execute(["AR Invoice: " . $invoice_id, $_SESSION['user_id'], $period]);
            $journal_id = $pdo->lastInsertId();

            $stmt = $pdo->prepare("INSERT INTO gl_entries (journal_ref, account_id, debit, credit, transaction_date) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$ref, $ar_acc, $amount, 0, date('Y-m-d')]);
            $stmt->execute([$ref, $rev_acc, 0, $amount, date('Y-m-d')]);
        }

        $pdo->commit();
        header("Location: index.php?msg=" . urlencode("AR Invoice generated and posted successfully") . "&type=success");
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        header("Location: index.php?msg=" . urlencode("AR Generation Error: " . $e->getMessage()) . "&type=error");
        exit();
    }
}

$customers = $pdo->query("SELECT * FROM customers WHERE is_on_hold = 0 ORDER BY name")->fetchAll();

include __DIR__ . '/../../includes/header_dashboard.php';
?>

<div class="flex">
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <main class="ml-72 flex-grow p-10 min-h-screen">
        <header class="mb-10 animate-fade-in px-8 py-12 glass rounded-[2.5rem] border border-white/10 dark:border-white/5 relative overflow-hidden shadow-2xl">
            <div class="absolute inset-0 bg-gradient-to-r from-teal-500/10 via-transparent to-transparent pointer-events-none"></div>
            <div class="relative flex justify-between items-center">
                <h2 class="text-4xl font-black tracking-tight uppercase text-slate-900 dark:text-white">AR <span class="gradient-text">Invoice Entry</span></h2>
                <a href="index.php" class="px-6 py-4 glass rounded-2xl hover:bg-white/10 transition-all flex items-center gap-2 text-sm font-black text-slate-900 dark:text-white border border-white/5">
                    <i data-lucide="arrow-left" class="w-4 h-4 text-teal-400"></i> Back to AR
                </a>
            </div>
        </header>

        <div class="max-w-4xl mx-auto">
            <div class="glass p-12 rounded-[2.5rem] border border-white/5 space-y-10">
                <form method="POST" class="space-y-8">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="space-y-3">
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest ml-1">Customer</label>
                            <select name="customer_id" required class="w-full bg-slate-100 dark:bg-slate-900/50 border border-white/5 rounded-2xl px-5 py-4 outline-none">
                                <option value="">Select Active Customer...</option>
                                <?php foreach ($customers as $c): ?>
                                    <option value="<?= $c['customer_id'] ?>"><?= $c['name'] ?> (Limit: <?= CURRENCY ?><?= number_format($c['credit_limit'], 0) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="space-y-3">
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest ml-1">Due Date</label>
                            <input type="date" name="due_date" required class="w-full bg-slate-100 dark:bg-slate-900/50 border border-white/5 rounded-2xl px-5 py-4 outline-none" value="<?= date('Y-m-d', strtotime('+30 days')) ?>">
                        </div>
                    </div>

                    <div class="space-y-3">
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest ml-1">Invoice Amount</label>
                        <div class="relative">
                            <span class="absolute left-5 top-1/2 -translate-y-1/2 text-slate-500 font-bold"><?= CURRENCY ?></span>
                            <input type="number" step="0.01" name="amount" required class="w-full bg-slate-100 dark:bg-slate-900/50 border border-white/5 rounded-2xl pl-10 pr-5 py-4 outline-none font-black text-teal-400" placeholder="0.00">
                        </div>
                    </div>

                    <div class="flex justify-end gap-4 pt-6">
                        <button type="submit" class="w-full py-4 bg-teal-500 text-slate-900 rounded-2xl font-black transition-all shadow-xl shadow-teal-500/20 flex items-center justify-center gap-3">
                            <i data-lucide="file-plus-2" class="w-5 h-5"></i> Generate Invoice & Post GL
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>

<script>
    lucide.createIcons();
</script>
</body>
</html>
