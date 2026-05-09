<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php';
check_login();

// Mock Billing Generation Logic
// In a real system, this would query Service Agreements and create invoices for the current period.

if (isset($_POST['run_billing'])) {
    try {
        $pdo->beginTransaction();

        // Simulate creating 5 invoices
        $customers = $pdo->query("SELECT customer_id FROM customers ORDER BY RAND() LIMIT 5")->fetchAll();
        $count = 0;
        foreach ($customers as $c) {
            $amount_ex = rand(500, 5000);
            $vat_amount = $amount_ex * VAT_RATE;
            $amount_in = $amount_ex + $vat_amount;
            $due = date('Y-m-d', strtotime('+15 days'));
            
            // Insert into billing_invoices (Stored VAT-inclusive)
            $stmt = $pdo->prepare("INSERT INTO billing_invoices (customer_id, amount, due_date, status, billing_period) VALUES (?, ?, ?, 'Unpaid', ?)");
            $stmt->execute([$c['customer_id'], $amount_in, $due, date('Y-m')]);
            $inv_id = $pdo->lastInsertId();

            // Insert into ar_invoices (Master AR table)
            $stmt = $pdo->prepare("INSERT INTO ar_invoices (customer_id, amount, due_date, balance_due, status) VALUES (?, ?, ?, ?, 'Unpaid')");
            $stmt->execute([$c['customer_id'], $amount_in, $due, $amount_in]);
            
            $count++;
        }

        $pdo->commit();
        $msg = "Successfully generated $count billing records.";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = $e->getMessage();
    }
}

include __DIR__ . '/../../includes/header_dashboard.php';
?>

<div class="flex">
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <main class="ml-72 flex-grow p-10 min-h-screen">
        <header class="mb-10 animate-fade-in px-8 py-12 glass rounded-[2.5rem] border border-white/10 dark:border-white/5 relative overflow-hidden shadow-2xl">
            <div class="absolute inset-0 bg-gradient-to-r from-indigo-500/10 via-transparent to-transparent pointer-events-none"></div>
            <div class="relative flex justify-between items-center">
                <h2 class="text-4xl font-black tracking-tight uppercase text-slate-900 dark:text-white">Billing <span class="gradient-text">Engine</span></h2>
                <a href="<?= BASE_URL ?>modules/collection/index.php" class="px-6 py-4 glass rounded-2xl hover:bg-white/10 transition-all flex items-center gap-2 text-sm font-black text-slate-900 dark:text-white border border-white/5">
                    <i data-lucide="arrow-left" class="w-4 h-4 text-indigo-400"></i> Back to Hub
                </a>
            </div>
        </header>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
            <div class="glass p-10 rounded-[2.5rem] border border-white/5 space-y-8">
                <div class="w-20 h-20 bg-teal-500/10 rounded-full flex items-center justify-center shadow-xl shadow-teal-500/20">
                    <i data-lucide="zap" class="w-10 h-10 text-teal-400"></i>
                </div>
                <div>
                    <h4 class="text-xl font-bold mb-2">Mass Billing Routine</h4>
                    <p class="text-slate-500 text-sm leading-relaxed">Run the monthly billing routine to generate invoices for all active service agreements. This will create both Billing Records and AR Ledger entries.</p>
                </div>
                
                <form method="POST">
                    <button type="submit" name="run_billing" class="w-full py-4 bg-teal-500 hover:bg-teal-600 text-slate-900 font-black rounded-2xl transition-all shadow-xl shadow-teal-500/20">
                        Run Batch Billing (<?= date('F Y') ?>)
                    </button>
                </form>

                <?php if (isset($msg)): ?>
                    <p class="p-4 bg-teal-500/10 text-teal-400 text-xs font-bold rounded-xl text-center"><?= $msg ?></p>
                <?php endif; ?>
            </div>

            <div class="glass p-8 rounded-[2.5rem] flex flex-col">
                <h4 class="font-bold flex items-center gap-2 mb-6"><i data-lucide="list" class="w-4 h-4 text-indigo-400"></i> Generation History</h4>
                <div class="space-y-4">
                    <div class="flex items-center justify-between p-4 bg-white/5 rounded-2xl border border-white/5 opacity-50">
                        <div>
                            <p class="text-sm font-bold">Batch #0081 - Jan 2026</p>
                            <p class="text-xs text-slate-500">142 Invoices Generated</p>
                        </div>
                        <span class="text-xs font-black uppercase text-slate-600">Completed</span>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
    lucide.createIcons();
</script>
</body>
</html>
