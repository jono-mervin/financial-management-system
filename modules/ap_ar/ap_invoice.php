<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php';
check_login();

$current_page = 'ap_invoice.php';
$current_dir = 'ap_ar';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vendor_id = $_POST['vendor_id'];
    $amount = $_POST['amount'];
    $tax = $_POST['tax_withheld'] ?? 0; // optional field
    $due_date = $_POST['due_date'];
    $desc = $_POST['description'] ?? 'AP Invoice'; // optional field

    try {
        $pdo->beginTransaction();

        // 1. Record AP Invoice
        $stmt = $pdo->prepare("INSERT INTO ap_invoices (vendor_id, amount, tax_withheld, due_date, status) VALUES (?, ?, ?, ?, 'Pending')");
        $stmt->execute([$vendor_id, $amount, $tax, $due_date]);
        $invoice_id = $pdo->lastInsertId();

        // 2. Post to GL
        $exp_acc = $pdo->query("SELECT account_id FROM chart_of_accounts WHERE account_type = 'Expense' LIMIT 1")->fetchColumn();
        $ap_acc  = $pdo->query("SELECT account_id FROM chart_of_accounts WHERE account_name LIKE '%Payable%' LIMIT 1")->fetchColumn();

        if ($exp_acc && $ap_acc) {
            $ref    = "APINF-" . str_pad($invoice_id, 4, '0', STR_PAD_LEFT);
            $month  = date('n');
            $year   = date('Y');

            // Auto-create the fiscal period if it doesn't exist yet
            $period = $pdo->query("SELECT period_id FROM period_status WHERE month=$month AND fiscal_year=$year")->fetchColumn();
            if (!$period) {
                $ins = $pdo->prepare("INSERT INTO period_status (month, fiscal_year, status) VALUES (?, ?, 'Open')");
                $ins->execute([$month, $year]);
                $period = $pdo->lastInsertId();
            }

            $stmt = $pdo->prepare("INSERT INTO gl_entries (journal_ref, account_id, debit, credit, transaction_date, period_id, posted_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$ref, $exp_acc, $amount,        0,              date('Y-m-d'), $period, $_SESSION['user_id']]);
            $stmt->execute([$ref, $ap_acc,  0,              $amount - $tax, date('Y-m-d'), $period, $_SESSION['user_id']]);
        }

        $pdo->commit();
        header("Location: index.php?msg=" . urlencode("AP Invoice processed and posted successfully") . "&type=success");
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        header("Location: index.php?msg=" . urlencode("AP Processing Error: " . $e->getMessage()) . "&type=error");
        exit();
    }
}

$vendors = $pdo->query("SELECT * FROM vendors WHERE accreditation_status = 'Active' ORDER BY name")->fetchAll();

include __DIR__ . '/../../includes/header_dashboard.php';
?>

<div class="flex">
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <main class="ml-72 flex-grow p-10 min-h-screen">
        <header class="mb-10 animate-fade-in px-8 py-12 glass rounded-[2.5rem] border border-white/10 dark:border-white/5 relative overflow-hidden shadow-2xl">
            <div class="absolute inset-0 bg-gradient-to-r from-rose-500/10 via-transparent to-transparent pointer-events-none"></div>
            <div class="relative flex justify-between items-center">
                <h2 class="text-4xl font-black tracking-tight uppercase text-slate-900 dark:text-white">AP <span class="gradient-text">Invoice Processing</span></h2>
                <a href="index.php" class="px-6 py-4 glass rounded-2xl hover:bg-white/10 transition-all flex items-center gap-2 text-sm font-black text-slate-900 dark:text-white border border-white/5">
                    <i data-lucide="arrow-left" class="w-4 h-4 text-rose-500"></i> Back to AP
                </a>
            </div>
        </header>

        <div class="max-w-4xl mx-auto">
            <div class="glass p-12 rounded-[2.5rem] border border-white/5 space-y-10">
                <form method="POST" class="space-y-8">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="space-y-3">
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest ml-1">Vendor</label>
                            <select name="vendor_id" required class="w-full bg-slate-100 dark:bg-slate-900/50 border border-white/5 rounded-2xl px-5 py-4 outline-none">
                                <option value="">Select Accredited Vendor...</option>
                                <?php foreach ($vendors as $v): ?>
                                    <option value="<?= $v['vendor_id'] ?>"><?= $v['name'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="space-y-3">
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest ml-1">Due Date</label>
                            <input type="date" name="due_date" required class="w-full bg-slate-100 dark:bg-slate-900/50 border border-white/5 rounded-2xl px-5 py-4 outline-none">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="space-y-3">
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest ml-1">Gross Amount</label>
                            <div class="relative">
                                <span class="absolute left-5 top-1/2 -translate-y-1/2 text-slate-500 font-bold"><?= CURRENCY ?></span>
                                <input type="number" step="0.01" name="amount" required class="w-full bg-slate-100 dark:bg-slate-900/50 border border-white/5 rounded-2xl pl-10 pr-5 py-4 outline-none font-black text-rose-400" placeholder="0.00">
                            </div>
                        </div>
                        <div class="space-y-3">
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest ml-1">EWT Withheld (Creditable)</label>
                            <div class="relative">
                                <span class="absolute left-5 top-1/2 -translate-y-1/2 text-slate-500 font-bold"><?= CURRENCY ?></span>
                                <input type="number" step="0.01" name="tax_withheld" class="w-full bg-slate-100 dark:bg-slate-900/50 border border-white/5 rounded-2xl pl-10 pr-5 py-4 outline-none" placeholder="0.00">
                            </div>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest ml-1">Justification / Description</label>
                        <textarea name="description" required class="w-full bg-slate-100 dark:bg-slate-900/50 border border-white/5 rounded-2xl px-5 py-4 outline-none min-h-[100px]" placeholder="Explain the nature of this expense..."></textarea>
                    </div>

                    <div class="flex justify-end gap-4 pt-6">
                        <button type="submit" class="w-full py-4 bg-indigo-500 text-white rounded-2xl font-black transition-all shadow-xl shadow-indigo-500/20 flex items-center justify-center gap-3">
                            <i data-lucide="file-check" class="w-5 h-5"></i> Record Payable & Post GL
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
