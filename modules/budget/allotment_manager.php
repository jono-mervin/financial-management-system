<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php';
check_login();

// Allotment release logic
if (isset($_POST['release_allotment'])) {
    $budget_id = $_POST['budget_id'];
    $amount = $_POST['amount'];
    
    $stmt = $pdo->prepare("INSERT INTO allotment_ledger (budget_id, amount_released, released_by) VALUES (?, ?, ?)");
    $stmt->execute([$budget_id, $amount, $_SESSION['user_id']]);
    header("Location: " . BASE_URL . "modules/budget/allotment_manager.php?msg=" . urlencode('Allotment funds released successfully') . "&type=success");
    exit();
}

$budgets = $pdo->query("SELECT b.*, a.account_name, a.account_code FROM approved_budgets b JOIN chart_of_accounts a ON b.account_id = a.account_id")->fetchAll();

include __DIR__ . '/../../includes/header_dashboard.php';
?>

<div class="flex">
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <main class="ml-72 flex-grow p-10 min-h-screen">
        <header class="mb-10 animate-fade-in px-8 py-12 glass rounded-[2.5rem] border border-white/10 dark:border-white/5 relative overflow-hidden shadow-2xl">
            <div class="absolute inset-0 bg-gradient-to-r from-teal-500/10 via-transparent to-transparent pointer-events-none"></div>
            <div class="relative flex justify-between items-center">
                <h2 class="text-4xl font-black tracking-tight uppercase text-slate-900 dark:text-white">Allotment <span class="gradient-text">Manager</span></h2>
                <a href="<?= BASE_URL ?>modules/budget/index.php" class="px-6 py-4 glass rounded-2xl hover:bg-white/10 transition-all flex items-center gap-2 text-sm font-black text-slate-900 dark:text-white border border-white/5">
                    <i data-lucide="arrow-left" class="w-4 h-4 text-teal-400"></i> Back to Hub
                </a>
            </div>
        </header>

        <div class="glass p-8 rounded-[2.5rem] border border-white/5">
            <table class="w-full text-left">
                <thead class="bg-white/5">
                    <tr>
                        <th class="px-6 py-4 text-sm font-bold uppercase tracking-widest text-slate-500">Account</th>
                        <th class="px-6 py-4 text-sm font-bold uppercase tracking-widest text-slate-500">Approved</th>
                        <th class="px-6 py-4 text-sm font-bold uppercase tracking-widest text-slate-500">Released</th>
                        <th class="px-6 py-4 text-sm font-bold uppercase tracking-widest text-slate-500">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    <?php foreach ($budgets as $b): 
                        $released = $pdo->query("SELECT SUM(amount_released) FROM allotment_ledger WHERE budget_id = " . $b['budget_id'])->fetchColumn() ?: 0;
                    ?>
                        <tr class="hover:bg-white/5 transition-colors">
                             <td class="px-6 py-4 font-bold text-slate-900 dark:text-white"><?= $b['account_name'] ?> <span class="text-xs text-slate-500 block"><?= $b['account_code'] ?></span></td>
                            <td class="px-6 py-4 text-teal-400 font-black"><?= CURRENCY ?><?= number_format($b['approved_amount'], 2) ?></td>
                            <td class="px-6 py-4 text-indigo-400 font-bold"><?= CURRENCY ?><?= number_format($released, 2) ?></td>
                            <td class="px-6 py-4">
                                <button onclick="openReleaseModal(<?= $b['budget_id'] ?>, '<?= $b['account_name'] ?>', <?= ($b['approved_amount'] - $released) ?>)" class="text-xs font-black uppercase text-teal-400 border border-teal-500/20 px-3 py-1 rounded-lg hover:bg-teal-500 hover:text-white transition-all">
                                    Release Funds
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<!-- Simple Fund Release Modal -->
<div id="release-modal" class="fixed inset-0 z-[110] hidden bg-slate-950/60 backdrop-blur-md flex items-center justify-center p-4">
    <div class="glass w-full max-w-md p-10 rounded-[2.5rem] border border-white/10 shadow-2xl">
        <h3 class="text-xl font-bold mb-2">Release <span class="gradient-text">Allotment</span></h3>
        <p class="text-sm text-slate-500 mb-8" id="modal-dept-label"></p>
        
        <form method="POST" class="space-y-6">
            <input type="hidden" name="budget_id" id="modal-budget-id">
            <div class="space-y-2">
                <label class="text-xs font-bold text-slate-500 uppercase tracking-widest ml-1">Release Amount</label>
                <input type="number" step="0.01" name="amount" id="modal-max-amount" required class="w-full bg-slate-100 dark:bg-slate-900 overflow-hidden border border-white/5 rounded-2xl px-5 py-4 focus:ring-2 focus:ring-teal-500/50 outline-none font-black text-teal-400">
            </div>
            <div class="flex gap-4">
                <button type="button" onclick="closeReleaseModal()" class="flex-grow py-3 glass rounded-xl font-bold">Cancel</button>
                <button type="submit" name="release_allotment" class="flex-grow py-3 bg-teal-500 text-slate-900 rounded-xl font-black">Confirm Release</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openReleaseModal(id, name, max) {
        document.getElementById('modal-budget-id').value = id;
        document.getElementById('modal-dept-label').innerText = "Releasing funds for: " + name;
        document.getElementById('modal-max-amount').value = max;
        document.getElementById('release-modal').classList.remove('hidden');
    }
    function closeReleaseModal() {
        document.getElementById('release-modal').classList.add('hidden');
    }
    lucide.createIcons();
</script>
</body>
</html>
