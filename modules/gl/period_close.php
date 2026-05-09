<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php';
check_login();

if (isset($_POST['close_period'])) {
    $period_id = $_POST['period_id'];
    
    try {
        $stmt = $pdo->prepare("UPDATE period_status SET status = 'closed', closed_by = ?, closed_at = NOW() WHERE period_id = ?");
        $stmt->execute([$_SESSION['user_id'], $period_id]);
        
        header("Location: " . BASE_URL . "modules/gl/index.php?msg=" . urlencode('Fiscal period closed successfully') . "&type=success");
        exit();
    } catch (Exception $e) {
        header("Location: " . BASE_URL . "modules/gl/index.php?msg=" . urlencode("Closing failed: " . $e->getMessage()) . "&type=error");
        exit();
    }
}

$open_periods = $pdo->query("SELECT * FROM period_status WHERE status = 'open' ORDER BY fiscal_year DESC, month DESC")->fetchAll();

include __DIR__ . '/../../includes/header_dashboard.php';
?>

<div class="flex">
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <main class="ml-72 flex-grow p-10 min-h-screen">
        <header class="flex justify-between items-center mb-10">
            <div>
                <h2 class="text-3xl font-bold">Period-End <span class="gradient-text">Closing</span></h2>
                <p class="text-slate-500 dark:text-slate-400 mt-1">Lock financial periods to prevent unauthorized changes.</p>
            </div>
        </header>

        <div class="max-w-4xl">
            <?php if (empty($open_periods)): ?>
                <div class="glass p-12 rounded-[2.5rem] text-center">
                    <div class="w-16 h-16 bg-teal-500/10 rounded-full flex items-center justify-center mx-auto mb-6 text-teal-400">
                        <i data-lucide="check-circle-2" class="w-10 h-10"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-2">All Periods Secure</h3>
                    <p class="text-slate-500">There are no currently open segments that require closing.</p>
                </div>
            <?php else: ?>
                <div class="space-y-6">
                    <?php foreach ($open_periods as $p): ?>
                        <div class="glass p-8 rounded-[2rem] flex items-center justify-between border-l-4 border-amber-500">
                            <div>
                                <h4 class="text-lg font-bold">FY <?= $p['fiscal_year'] ?> - Period <?= str_pad($p['month'], 2, '0', STR_PAD_LEFT) ?></h4>
                                <p class="text-slate-500 text-sm">Status: <span class="text-teal-400 font-bold uppercase tracking-tighter">Open for Posting</span></p>
                            </div>
                            <form action="" method="POST">
                                <input type="hidden" name="period_id" value="<?= $p['period_id'] ?>">
                                <button type="submit" name="close_period" onclick="return confirm('Are you sure you want to close this period? This will block all future postings to this month.')" class="px-6 py-3 bg-rose-500 hover:bg-rose-600 text-slate-900 font-black rounded-xl transition-all shadow-lg shadow-rose-500/20 flex items-center gap-2">
                                    <i data-lucide="lock" class="w-4 h-4"></i> Close & Lock Period
                                </button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="mt-12 glass p-8 rounded-[2.5rem] border-dashed border-2 border-slate-300 dark:border-slate-800">
            <h4 class="text-sm font-bold uppercase tracking-widest text-slate-500 mb-4">Closing Checklist</h4>
            <ul class="space-y-3 text-sm text-slate-400">
                <li class="flex items-center gap-2"><i data-lucide="check" class="w-4 h-4 text-teal-500"></i> Ensure all submodules have posted their final batches.</li>
                <li class="flex items-center gap-2"><i data-lucide="check" class="w-4 h-4 text-teal-500"></i> Reconcile all bank statements.</li>
                <li class="flex items-center gap-2"><i data-lucide="check" class="w-4 h-4 text-teal-500"></i> Verify that total debits match total credits globally.</li>
                <li class="flex items-center gap-2"><i data-lucide="circle" class="w-4 h-4 text-slate-600"></i> Run preliminary Trial Balance.</li>
            </ul>
        </div>
    </main>
</div>

<script>
    lucide.createIcons();
</script>
</body>
</html>
