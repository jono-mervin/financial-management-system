<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php';
check_login();

$current_page = 'index.php';
$current_dir = 'budget';

// Stats
$total_approved = $pdo->query("SELECT SUM(approved_amount) FROM approved_budgets WHERE fiscal_year = " . date('Y'))->fetchColumn() ?: 0;
$total_allotted = $pdo->query("SELECT SUM(amount_released) FROM allotment_ledger")->fetchColumn() ?: 0;
$total_actual = $pdo->query("SELECT SUM(amount) FROM actual_expenditures")->fetchColumn() ?: 0;

$available_balance = $total_approved - $total_actual;
$utilization_rate = $total_approved > 0 ? ($total_actual / $total_approved) * 100 : 0;

// Get Departments for Preparation Modal
$depts = $pdo->query("SELECT * FROM departments ORDER BY dept_name")->fetchAll();
$expense_accounts = $pdo->query("SELECT * FROM chart_of_accounts WHERE account_type = 'Expense' ORDER BY account_code")->fetchAll();

include __DIR__ . '/../../includes/header_dashboard.php';
?>

<div class="flex">
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <main class="ml-72 flex-grow p-10 min-h-screen">
        <header class="mb-10 animate-fade-in px-8 py-12 glass rounded-[2.5rem] border border-white/10 dark:border-white/5 relative overflow-hidden shadow-2xl">
            <div class="absolute inset-0 bg-gradient-to-r from-teal-500/10 via-transparent to-transparent pointer-events-none"></div>
            <div class="relative flex justify-between items-center">
                <h2 class="text-4xl font-black tracking-tight uppercase text-slate-900 dark:text-white">Budget <span class="gradient-text">Control</span></h2>
                <div class="flex gap-4">
                    <?php if (has_permission('budget', 'create')): ?>
                        <button onclick="toggleModal('prep-modal')" class="px-8 py-4 bg-teal-500 hover:bg-teal-600 text-slate-900 font-black rounded-2xl transition-all shadow-xl shadow-teal-500/20 flex items-center gap-2">
                            <i data-lucide="plus-circle" class="w-5 h-5"></i> New Proposal
                        </button>
                    <?php endif; ?>
                    <a href="<?= BASE_URL ?>modules/budget/variance_report.php" class="px-6 py-4 glass rounded-2xl hover:bg-white/10 transition-all flex items-center gap-2 text-sm font-black text-slate-900 dark:text-white">
                        <i data-lucide="bar-chart-3" class="w-4 h-4 text-teal-400"></i> Variance Report
                    </a>
                </div>
            </div>
        </header>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-10 animate-fade-in" style="animation-delay: 0.1s;">
            <div class="glass p-6 rounded-3xl border-l-4 border-teal-500">
                <p class="text-slate-500 uppercase text-xs font-black tracking-widest mb-1">Approved Budget (CY)</p>
                <h3 class="text-2xl font-black"><?= CURRENCY ?><?= number_format($total_approved, 2) ?></h3>
            </div>
            <div class="glass p-6 rounded-3xl border-l-4 border-indigo-500">
                <p class="text-slate-500 uppercase text-xs font-black tracking-widest mb-1">Released Allotments</p>
                <h3 class="text-2xl font-black"><?= CURRENCY ?><?= number_format($total_allotted, 2) ?></h3>
            </div>
            <div class="glass p-6 rounded-3xl border-l-4 border-amber-500">
                <p class="text-slate-500 uppercase text-xs font-black tracking-widest mb-1">Actual Expenditure</p>
                <h3 class="text-2xl font-black text-rose-400"><?= CURRENCY ?><?= number_format($total_actual, 2) ?></h3>
            </div>
            <div class="glass p-6 rounded-3xl border-l-4 border-slate-500">
                <p class="text-slate-500 uppercase text-xs font-black tracking-widest mb-1">Free Balance</p>
                <h3 class="text-2xl font-black text-teal-400"><?= CURRENCY ?><?= number_format($available_balance, 2) ?></h3>
            </div>
        </div>

        <!-- Budget Proposals Table -->
        <?php
        $proposals = $pdo->query("SELECT p.*, d.dept_name, d.dept_code, a.account_code, a.account_name
                                  FROM budget_proposals p
                                  JOIN departments d ON p.department_id = d.department_id
                                  JOIN chart_of_accounts a ON p.account_id = a.account_id
                                  ORDER BY p.proposal_id DESC")->fetchAll();
        ?>
        <div class="mt-10 glass rounded-[2rem] overflow-hidden border border-white/5 animate-fade-in" style="animation-delay: 0.1s;">
            <div class="p-6 border-b border-white/5 flex items-center justify-between">
                <h4 class="font-bold flex items-center gap-2">
                    <i data-lucide="clipboard-list" class="w-4 h-4 text-teal-400"></i>
                    Budget Proposals
                </h4>
                <span class="text-xs text-slate-500 font-bold uppercase tracking-widest"><?= count($proposals) ?> total</span>
            </div>
            <?php if (empty($proposals)): ?>
                <p class="text-center py-12 text-slate-500 italic">No proposals submitted yet. Click <strong>New Proposal</strong> to get started.</p>
            <?php else: ?>
            <table class="w-full text-left">
                <thead class="bg-slate-100 dark:bg-slate-800/50">
                    <tr>
                        <th class="px-6 py-4 text-xs font-bold uppercase tracking-widest text-slate-500">Department</th>
                        <th class="px-6 py-4 text-xs font-bold uppercase tracking-widest text-slate-500">Account</th>
                        <th class="px-6 py-4 text-xs font-bold uppercase tracking-widest text-slate-500">Year</th>
                        <th class="px-6 py-4 text-xs font-bold uppercase tracking-widest text-slate-500">Proposed</th>
                        <th class="px-6 py-4 text-xs font-bold uppercase tracking-widest text-slate-500">Status</th>
                        <?php if ($_SESSION['role'] === 'Admin' || has_permission('budget', 'update')): ?>
                        <th class="px-6 py-4 text-xs font-bold uppercase tracking-widest text-slate-500 text-right">Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    <?php foreach ($proposals as $p): ?>
                    <tr class="hover:bg-white/5 transition-colors">
                        <td class="px-6 py-4">
                            <p class="font-bold text-sm"><?= $p['dept_name'] ?></p>
                            <p class="text-xs text-slate-500"><?= $p['dept_code'] ?></p>
                        </td>
                        <td class="px-6 py-4">
                            <p class="font-mono text-teal-400 text-sm font-bold"><?= $p['account_code'] ?></p>
                            <p class="text-xs text-slate-500"><?= $p['account_name'] ?></p>
                        </td>
                        <td class="px-6 py-4 text-sm font-bold"><?= $p['fiscal_year'] ?></td>
                        <td class="px-6 py-4 font-black text-sm"><?= CURRENCY ?><?= number_format($p['proposed_amount'], 2) ?></td>
                        <td class="px-6 py-4">
                            <?php
                            $badge = match($p['status']) {
                                'Approved'  => 'bg-teal-500/10 text-teal-400',
                                'Rejected'  => 'bg-rose-500/10 text-rose-400',
                                default     => 'bg-amber-500/10 text-amber-400',
                            };
                            ?>
                            <span class="px-3 py-1 rounded-full text-xs font-black uppercase <?= $badge ?>">
                                <?= $p['status'] ?>
                            </span>
                        </td>
                        <?php if ($_SESSION['role'] === 'Admin' || has_permission('budget', 'update')): ?>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <?php if ($p['status'] === 'Submitted'): ?>
                                    <button onclick='openProcessModal(<?= json_encode([
                                        "id" => $p["proposal_id"],
                                        "dept" => $p["dept_name"],
                                        "account" => $p["account_name"],
                                        "amount" => number_format($p["proposed_amount"], 2),
                                        "year" => $p["fiscal_year"]
                                    ]) ?>)' class="px-4 py-2 bg-indigo-500/10 text-indigo-400 text-xs font-black rounded-xl hover:bg-indigo-500 hover:text-white transition-all flex items-center gap-2">
                                        <i data-lucide="settings-2" class="w-3.5 h-3.5"></i> Process
                                    </button>
                                <?php endif; ?>
                                <?php if ($_SESSION['role'] === 'Admin'): ?>
                                <button onclick="adminDelete('budget_proposal', <?= $p['proposal_id'] ?>, 'Budget Proposal #<?= $p['proposal_id'] ?>')" class="p-2 text-rose-500 hover:text-rose-400 hover:bg-rose-500/10 rounded-lg transition-all" title="Delete Proposal">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mt-10 animate-fade-in" style="animation-delay: 0.2s;">
            <!-- Utilization Gauge -->
            <div class="glass p-8 rounded-[2.5rem] flex flex-col items-center justify-center relative overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-br from-teal-500/5 to-transparent pointer-events-none"></div>
                <h4 class="text-sm font-bold uppercase tracking-widest text-slate-500 mb-8 self-start">Overall Utilization</h4>
                
                <div class="relative w-64 h-64 flex items-center justify-center">
                    <svg class="w-full h-full -rotate-90">
                        <circle cx="128" cy="128" r="110" stroke="currentColor" stroke-width="20" fill="transparent" class="text-slate-100 dark:text-slate-800" />
                        <circle cx="128" cy="128" r="110" stroke="currentColor" stroke-width="20" fill="transparent" class="text-teal-500" stroke-dasharray="<?= (110 * 2 * pi()) ?>" stroke-dashoffset="<?= (110 * 2 * pi()) * (1 - ($utilization_rate / 100)) ?>" stroke-linecap="round" />
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <span class="text-4xl font-black"><?= round($utilization_rate, 1) ?>%</span>
                        <span class="text-xs text-slate-500 font-black uppercase">Utilized</span>
                    </div>
                </div>
                
                <div class="mt-8 flex gap-8">
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 rounded-full bg-teal-500 shadow-lg shadow-teal-500/50"></div>
                        <span class="text-xs font-bold text-slate-500">Actual Spent</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 rounded-full bg-slate-800"></div>
                        <span class="text-xs font-bold text-slate-500">Unused Budget</span>
                    </div>
                </div>
            </div>

            <!-- Dept Status -->
            <div class="glass p-8 rounded-[2.5rem]">
                <h4 class="font-bold flex items-center gap-2 mb-6"><i data-lucide="layers" class="w-4 h-4 text-indigo-400"></i> Departmental Status</h4>
                <div class="space-y-6">
                    <?php
                    $dept_data = $pdo->query("SELECT d.dept_name, d.dept_code, SUM(b.approved_amount) as total 
                                            FROM departments d 
                                            LEFT JOIN budget_proposals p ON d.department_id = p.department_id 
                                            LEFT JOIN approved_budgets b ON p.proposal_id = b.proposal_id 
                                            GROUP BY d.department_id LIMIT 4")->fetchAll();
                    foreach ($dept_data as $dept):
                        $d_total = $dept['total'] ?: 0;
                        $width = ($total_approved > 0) ? ($d_total / $total_approved) * 100 : 0;
                    ?>
                        <div class="space-y-2">
                            <div class="flex justify-between text-xs font-bold">
                                <span class="text-slate-400"><?= $dept['dept_name'] ?> (<?= $dept['dept_code'] ?>)</span>
                                <span><?= CURRENCY ?><?= number_format($d_total, 2) ?></span>
                            </div>
                            <div class="w-full h-1.5 bg-slate-800 rounded-full overflow-hidden">
                                <div class="h-full bg-indigo-500 transition-all duration-1000" style="width: <?= $width ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <a href="<?= BASE_URL ?>modules/budget/allotment_manager.php" class="mt-8 block p-5 bg-white/5 rounded-2xl border border-white/5 hover:bg-white/10 transition-all text-center">
                    <p class="text-xs font-bold text-teal-400">View Detailed Allotment Ledger <i data-lucide="arrow-right" class="inline w-3 h-3 ml-1"></i></p>
                </a>
            </div>
        </div>
    </main>
</div>

<!-- Modal: Budget Prep -->
<div id="prep-modal" class="fixed inset-0 z-[110] hidden bg-slate-950/60 backdrop-blur-md flex items-center justify-center p-4">
    <div class="glass w-full max-w-4xl max-h-[90vh] overflow-y-auto rounded-[3rem] border border-white/10 shadow-2xl animate-scale-in">
        <div class="p-10">
            <header class="flex justify-between items-center mb-10">
                <div>
                    <h2 class="text-3xl font-bold">Budget <span class="gradient-text">Preparation</span></h2>
                    <p class="text-slate-500 text-sm mt-1">Submit a new budget proposal for the next period.</p>
                </div>
                <button onclick="toggleModal('prep-modal')" class="p-3 hover:bg-white/5 rounded-full transition-colors">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </header>

            <form action="<?= BASE_URL ?>modules/budget/budget_prep.php" method="POST" class="space-y-8">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="space-y-3">
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest ml-1">Target Department</label>
                        <select name="department_id" required class="w-full bg-slate-100 dark:bg-slate-900/50 border border-white/5 rounded-2xl px-5 py-4 focus:ring-2 focus:ring-teal-500/50 outline-none">
                            <option value="">Select Department...</option>
                            <?php foreach ($depts as $d): ?>
                                <option value="<?= $d['department_id'] ?>"><?= $d['dept_name'] ?> (<?= $d['dept_code'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="space-y-3">
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest ml-1">Fiscal Year</label>
                        <input type="number" name="fiscal_year" value="<?= date('Y') ?>" required class="w-full bg-slate-100 dark:bg-slate-900/50 border border-white/5 rounded-2xl px-5 py-4 focus:ring-2 focus:ring-teal-500/50 outline-none">
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="flex justify-between items-center px-1">
                        <h4 class="text-sm font-bold uppercase tracking-widest text-teal-400">Budget Items</h4>
                        <button type="button" onclick="addItemRow()" class="text-xs font-bold text-teal-400 hover:text-teal-300 flex items-center gap-1 group">
                            <i data-lucide="plus" class="w-4 h-4 transition-transform group-hover:rotate-90"></i> Add Item
                        </button>
                    </div>
                    
                    <div class="glass rounded-3xl overflow-hidden border border-white/5">
                        <table class="w-full text-left" id="budget-items-table">
                            <thead class="bg-slate-100 dark:bg-slate-800/50">
                                <tr>
                                    <th class="px-6 py-4 text-sm font-bold uppercase tracking-widest text-slate-500">Account Code</th>
                                    <th class="px-6 py-4 text-sm font-bold uppercase tracking-widest text-slate-500">Proposed Amount</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                <!-- JS rows -->
                            </tbody>
                            <tfoot class="bg-slate-50 dark:bg-white/5">
                                <tr class="font-black">
                                    <td class="px-6 py-4 text-right text-xs uppercase tracking-widest text-slate-500">Total Proposal</td>
                                    <td class="px-6 py-4 text-teal-400" id="total-proposed"><?= CURRENCY ?>0.00</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <div class="flex justify-end gap-4 pt-6">
                    <button type="button" onclick="toggleModal('prep-modal')" class="px-8 py-4 glass rounded-2xl font-bold hover:bg-white/10 transition-all">Cancel</button>
                    <button type="submit" class="px-12 py-4 bg-teal-500 text-slate-900 rounded-2xl font-black transition-all shadow-xl shadow-teal-500/20 flex items-center gap-3">
                        <i data-lucide="save" class="w-5 h-5"></i> Submit Proposal
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Process Proposal -->
<div id="process-modal" class="fixed inset-0 z-[110] hidden bg-slate-950/60 backdrop-blur-md flex items-center justify-center p-4">
    <div class="glass w-full max-w-lg rounded-[2.5rem] border border-white/10 shadow-2xl animate-scale-in overflow-hidden">
        <div class="p-8">
            <header class="flex justify-between items-center mb-6">
                <h3 class="text-2xl font-black tracking-tight">Process <span class="gradient-text">Proposal</span></h3>
                <button onclick="toggleModal('process-modal')" class="p-2 hover:bg-white/5 rounded-full transition-colors">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </header>

            <div class="mb-8 p-6 bg-white/5 rounded-2xl border border-white/5 space-y-4">
                <div class="flex justify-between items-center">
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-widest">Department</span>
                    <span id="process-dept" class="text-sm font-black dark:text-white"></span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-widest">Account</span>
                    <span id="process-account" class="text-sm font-bold text-teal-400"></span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-widest">Year</span>
                    <span id="process-year" class="text-sm font-bold dark:text-white"></span>
                </div>
                <div class="pt-4 border-t border-white/5 flex justify-between items-center">
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-widest">Proposed Amount</span>
                    <span class="text-xl font-black text-slate-900 dark:text-white"><?= CURRENCY ?><span id="process-amount"></span></span>
                </div>
            </div>

            <form action="<?= BASE_URL ?>modules/budget/budget_approve.php" method="POST" class="grid grid-cols-2 gap-4">
                <input type="hidden" name="proposal_id" id="process-id">
                <button type="submit" name="action" value="reject" class="py-4 bg-rose-500/10 text-rose-500 rounded-2xl font-black text-sm hover:bg-rose-500 hover:text-white transition-all flex items-center justify-center gap-2">
                    <i data-lucide="x-circle" class="w-4 h-4"></i> Reject
                </button>
                <button type="submit" name="action" value="approve" class="py-4 bg-teal-500 text-slate-900 rounded-2xl font-black text-sm hover:bg-teal-600 transition-all shadow-xl shadow-teal-500/20 flex items-center justify-center gap-2">
                    <i data-lucide="check-circle" class="w-4 h-4"></i> Approve
                </button>
            </form>
        </div>
    </div>
</div>

<template id="item-row">
    <tr class="hover:bg-white/5 transition-colors">
        <td class="px-6 py-3">
            <select name="accounts[]" required class="w-full bg-transparent border-none focus:ring-0 text-sm font-semibold">
                <option value="">Select Expense Account...</option>
                <?php foreach ($expense_accounts as $row): ?>
                    <option value="<?= $row['account_id'] ?>"><?= $row['account_code'] ?> - <?= $row['account_name'] ?></option>
                <?php endforeach; ?>
            </select>
        </td>
        <td class="px-6 py-3 border-l border-white/5">
            <input type="number" step="0.01" name="amounts[]" onchange="calcTotal()" class="w-full bg-transparent border-none focus:ring-0 text-sm font-black text-teal-400" placeholder="0.00">
        </td>
    </tr>
</template>

<script>
    function toggleModal(id) {
        const m = document.getElementById(id);
        m.classList.toggle('hidden');
        if (id === 'prep-modal' && !m.classList.contains('hidden') && document.querySelectorAll('#budget-items-table tbody tr').length === 0) {
            addItemRow();
        }
    }

    function openProcessModal(data) {
        document.getElementById('process-id').value = data.id;
        document.getElementById('process-dept').innerText = data.dept;
        document.getElementById('process-account').innerText = data.account;
        document.getElementById('process-year').innerText = data.year;
        document.getElementById('process-amount').innerText = data.amount;
        toggleModal('process-modal');
    }

    function addItemRow() {
        const table = document.getElementById('budget-items-table').querySelector('tbody');
        const template = document.getElementById('item-row');
        table.appendChild(template.content.cloneNode(true));
        lucide.createIcons();
    }

    function calcTotal() {
        let total = 0;
        document.querySelectorAll('input[name="amounts[]"]').forEach(i => total += parseFloat(i.value) || 0);
        document.getElementById('total-proposed').innerText = '₱' + total.toLocaleString(undefined, {minimumFractionDigits: 2});
    }

    lucide.createIcons();
</script>
<?php if ($_SESSION['role'] === 'Admin'): ?>
<script src="<?= BASE_URL ?>assets/js/admin_delete.js"></script>
<?php endif; ?>
</body>
</html>

