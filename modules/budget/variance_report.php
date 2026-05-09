<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php';
check_login();

// Variance Data Calculation
$year = date('Y');
$variance_data = $pdo->query("
    SELECT 
        a.account_name, 
        a.account_code,
        b.approved_amount,
        IFNULL((SELECT SUM(amount) FROM actual_expenditures WHERE budget_id = b.budget_id), 0) as actual_amount
    FROM approved_budgets b
    JOIN chart_of_accounts a ON b.account_id = a.account_id
    WHERE b.fiscal_year = $year
")->fetchAll();

include __DIR__ . '/../../includes/header_dashboard.php';
?>

<div class="flex">
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <main class="ml-72 flex-grow p-10 min-h-screen">
        <header class="mb-10 animate-fade-in px-8 py-12 glass rounded-[2.5rem] border border-white/10 dark:border-white/5 relative overflow-hidden shadow-2xl">
            <div class="absolute inset-0 bg-gradient-to-r from-teal-500/10 via-transparent to-transparent pointer-events-none"></div>
            <div class="relative flex justify-between items-center">
                <h2 class="text-4xl font-black tracking-tight uppercase text-slate-900 dark:text-white">Variance <span class="gradient-text">Analysis</span></h2>
                <a href="<?= BASE_URL ?>modules/budget/index.php" class="px-6 py-4 glass rounded-2xl hover:bg-white/10 transition-all flex items-center gap-2 text-sm font-black text-slate-900 dark:text-white border border-white/5">
                    <i data-lucide="arrow-left" class="w-4 h-4 text-teal-400"></i> Back
                </a>
            </div>
        </header>

        <div class="glass p-10 rounded-[2.5rem] border border-white/5 space-y-10">
            <div class="grid grid-cols-1 gap-8">
                <?php foreach ($variance_data as $v): 
                    $variance = $v['approved_amount'] - $v['actual_amount'];
                    $v_perc = $v['approved_amount'] > 0 ? ($v['actual_amount'] / $v['approved_amount']) * 100 : 0;
                    $color = $v_perc > 90 ? 'rose' : ($v_perc > 70 ? 'amber' : 'teal');
                ?>
                    <div class="space-y-4">
                        <div class="flex justify-between items-end">
                            <div>
                                <h4 class="font-bold text-sm"><?= $v['account_name'] ?></h4>
                                <p class="text-[10px] text-slate-500 uppercase"><?= $v['account_code'] ?></p>
                            </div>
                            <div class="text-right">
                                <span class="text-xs font-black text-<?= $color ?>-400"><?= CURRENCY ?><?= number_format($v['actual_amount'], 2) ?></span>
                                <span class="text-[10px] text-slate-500 font-bold mx-2">/</span>
                                <span class="text-xs font-bold text-slate-400"><?= CURRENCY ?><?= number_format($v['approved_amount'], 2) ?></span>
                            </div>
                        </div>
                        <div class="w-full h-3 bg-slate-800 rounded-full overflow-hidden flex">
                            <div class="h-full bg-<?= $color ?>-500 transition-all duration-1000 shadow-lg shadow-<?= $color ?>-500/50" style="width: <?= min($v_perc, 100) ?>%"></div>
                        </div>
                        <div class="flex justify-between items-center text-[10px] font-black uppercase tracking-tighter">
                            <span class="text-slate-600">Utilization: <?= round($v_perc, 1) ?>%</span>
                            <span class="<?= $variance < 0 ? 'text-rose-500' : 'text-teal-500' ?>">
                                Variance: <?= ($variance < 0 ? '-' : '+') ?><?= CURRENCY ?><?= number_format(abs($variance), 2) ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <?php if (empty($variance_data)): ?>
                    <div class="text-center py-20">
                        <i data-lucide="info" class="w-12 h-12 text-slate-700 mx-auto mb-4"></i>
                        <p class="text-slate-500 italic">No approved budget data available for variance analysis.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<script>
    lucide.createIcons();
</script>
</body>
</html>
