<?php
require_once '../../includes/db.php';
require_once '../../includes/session.php';
require_once '../dashboard/stats.php';
check_login();

$summary = get_module_summary()['gl'];
$coa = $pdo->query("SELECT account_id, account_code, account_name FROM chart_of_accounts ORDER BY account_code")->fetchAll();

include '../../includes/header_dashboard.php';
?>

<div class="flex">
    <?php include '../../includes/sidebar.php'; ?>

    <main class="ml-72 flex-grow p-10 min-h-screen">
        <header class="mb-10 animate-fade-in px-8 py-12 glass rounded-[2.5rem] border border-white/10 dark:border-white/5 relative overflow-hidden shadow-2xl">
            <div class="absolute inset-0 bg-gradient-to-r from-teal-500/10 via-transparent to-transparent pointer-events-none"></div>
            <div class="relative flex justify-between items-center">
                <h2 class="text-4xl font-black tracking-tight uppercase text-slate-900 dark:text-white">General <span class="gradient-text">Ledger</span></h2>
                <div class="flex gap-4">
                    <?php if (has_permission('gl', 'create')): ?>
                        <button onclick="toggleModal('je-modal')" class="px-8 py-4 bg-teal-500 hover:bg-teal-600 text-slate-900 font-black rounded-2xl transition-all shadow-xl shadow-teal-500/20 flex items-center gap-3">
                            <i data-lucide="plus-circle" class="w-5 h-5"></i> New Journal Entry
                        </button>
                    <?php endif; ?>
                    <a href="<?= BASE_URL ?>modules/gl/gl_reports.php" class="px-6 py-4 glass rounded-2xl hover:bg-white/10 transition-all flex items-center gap-2 text-sm font-black text-slate-900 dark:text-white border border-white/5">
                        <i data-lucide="file-bar-chart" class="w-4 h-4 text-indigo-400"></i> Financial Statements
                    </a>
                </div>
            </div>
        </header>

        <?php if (isset($_GET['success'])): ?>
            <div class="mb-8 p-4 bg-teal-500/10 border border-teal-500/20 text-teal-400 text-sm font-bold rounded-2xl flex items-center gap-3 animate-fade-in">
                <i data-lucide="check-circle" class="w-5 h-5"></i> Transaction posted successfully to General Ledger.
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-10 animate-fade-in" style="animation-delay: 0.1s;">
            <div class="glass p-6 rounded-3xl border-l-4 border-teal-500 hover:scale-105 transition-transform cursor-pointer">
                <p class="text-slate-500 uppercase text-[10px] font-black tracking-widest mb-1">Total Entries</p>
                <h3 class="text-3xl font-black"><?= $summary['total_entries'] ?></h3>
            </div>
            <div class="glass p-6 rounded-3xl border-l-4 border-indigo-500 hover:scale-105 transition-transform cursor-pointer">
                <p class="text-slate-500 uppercase text-[10px] font-black tracking-widest mb-1">Open Periods</p>
                <h3 class="text-3xl font-black"><?= $summary['open_periods'] ?></h3>
            </div>
            <div class="glass p-6 rounded-3xl border-l-4 border-amber-500 hover:scale-105 transition-transform cursor-pointer">
                <p class="text-slate-500 uppercase text-[10px] font-black tracking-widest mb-1">Pending Approvals</p>
                <h3 class="text-3xl font-black">0</h3>
            </div>
            <a href="<?= BASE_URL ?>modules/gl/coa_manager.php" class="glass p-6 rounded-3xl border-l-4 border-slate-500 hover:scale-105 transition-transform cursor-pointer group">
                <p class="text-slate-500 uppercase text-[10px] font-black tracking-widest mb-1 group-hover:text-teal-400">Chart of Accounts</p>
                <h3 class="text-3xl font-black">Manage <i data-lucide="chevron-right" class="inline w-6 h-6"></i></h3>
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 animate-fade-in" style="animation-delay: 0.2s;">
            <div class="glass p-8 rounded-[2.5rem]">
                <div class="flex justify-between items-center mb-6">
                    <h4 class="font-bold flex items-center gap-2"><i data-lucide="clock" class="w-4 h-4 text-indigo-400"></i> Recent Activities</h4>
                    <button class="text-xs text-slate-500 hover:text-white font-bold">View Audit Log</button>
                </div>
                <div class="space-y-4">
                    <?php
                    $recent = $pdo->query("SELECT * FROM journal_headers ORDER BY created_at DESC LIMIT 5")->fetchAll();
                    foreach ($recent as $item):
                    ?>
                        <div class="flex items-center justify-between p-4 bg-white/5 rounded-2xl hover:bg-white/10 transition-colors border border-white/5">
                            <div>
                                <p class="text-sm font-bold"><?= $item['description'] ?: 'Manual Posting' ?></p>
                                <p class="text-[10px] text-slate-500 font-mono"><?= $item['created_at'] ?></p>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="px-3 py-1 bg-teal-500/10 text-teal-400 text-[10px] font-black uppercase rounded-full">
                                    <?= $item['status'] ?>
                                </span>
                                <?php if ($_SESSION['role'] === 'Admin' && $item['status'] !== 'Posted'): ?>
                                <button onclick="adminDelete('journal', <?= $item['journal_id'] ?>, 'Journal JE-<?= str_pad($item['journal_id'], 4, '0', STR_PAD_LEFT) ?>')" class="p-2 text-rose-500 hover:text-rose-400 hover:bg-rose-500/10 rounded-lg transition-all" title="Delete Journal">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($recent)): ?>
                        <p class="text-center py-10 text-slate-600 italic">No recent journal postings found.</p>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (has_permission('gl', 'approve')): 
                $pending_entries = $pdo->query("SELECT jh.*, u.name as preparer 
                                              FROM journal_headers jh 
                                              JOIN users u ON jh.prepared_by = u.user_id 
                                              WHERE jh.status = 'Pending' 
                                              ORDER BY jh.journal_id DESC")->fetchAll();
                if (!empty($pending_entries)):
            ?>
            <div class="glass p-8 rounded-[2.5rem] mb-10 border border-indigo-500/20 shadow-xl shadow-indigo-500/10">
                <div class="flex items-center gap-3 mb-6">
                    <div class="p-2 bg-indigo-500/10 rounded-lg">
                        <i data-lucide="shield-check" class="w-5 h-5 text-indigo-400"></i>
                    </div>
                    <h3 class="text-xl font-black uppercase tracking-tight">Pending <span class="gradient-text">Authorizations</span></h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-indigo-500/5">
                            <tr>
                                <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-500">ID</th>
                                <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-500">Description</th>
                                <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-500">Prepared By</th>
                                <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-500 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <?php foreach ($pending_entries as $pe): ?>
                            <tr>
                                <td class="px-6 py-4 text-xs font-bold font-mono">JE-<?= str_pad($pe['journal_id'], 4, '0', STR_PAD_LEFT) ?></td>
                                <td class="px-6 py-4 text-xs"><?= $pe['description'] ?></td>
                                <td class="px-6 py-4 text-xs font-bold"><?= $pe['preparer'] ?></td>
                                <td class="px-6 py-4 text-center">
                                    <button type="button" onclick="openReviewjeModal(<?= $pe['journal_id'] ?>)" class="px-6 py-2 bg-indigo-500/10 hover:bg-indigo-500/20 text-indigo-400 hover:text-white text-[10px] font-black uppercase rounded-xl border border-indigo-500/20 transition-all flex items-center justify-center gap-2 mx-auto">
                                        <i data-lucide="eye" class="w-4 h-4"></i> Review
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; endif; ?>

            <div class="glass p-8 rounded-[2.5rem] flex flex-col justify-center items-center relative overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-br from-indigo-500/10 to-transparent pointer-events-none"></div>
                <div class="w-20 h-20 bg-indigo-500/10 rounded-full flex items-center justify-center mb-6 shadow-xl shadow-indigo-500/20">
                    <i data-lucide="lock" class="w-10 h-10 text-indigo-400"></i>
                </div>
                <h4 class="text-xl font-bold mb-2">Period-End Closing</h4>
                <p class="text-slate-500 text-sm text-center max-w-xs mb-8">Maintain financial integrity by locking previous months and generating trial balances.</p>
                <a href="<?= BASE_URL ?>modules/gl/period_close.php" class="px-8 py-3 bg-white/5 hover:bg-white/10 border border-white/10 rounded-xl text-sm font-bold transition-all">Execute Close Sequence</a>
            </div>
        </div>
    </main>
</div>

<!-- Modal: New Journal Entry -->
<div id="je-modal" class="fixed inset-0 z-[110] hidden bg-slate-950/60 backdrop-blur-md flex items-center justify-center p-4">
    <div class="glass w-full max-w-5xl max-h-[90vh] overflow-y-auto rounded-[3rem] border border-white/10 shadow-2xl animate-scale-in">
        <div class="p-10">
            <header class="flex justify-between items-center mb-8">
                <div>
                    <h2 class="text-3xl font-bold">Journal <span class="gradient-text">Entry</span></h2>
                    <p class="text-slate-500 text-sm mt-1">Create a new manual posting.</p>
                </div>
                <button onclick="toggleModal('je-modal')" class="p-3 hover:bg-white/5 rounded-full transition-colors">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </header>

            <form action="<?= BASE_URL ?>modules/gl/gl_post.php" method="POST" class="space-y-8">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div class="space-y-3">
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest ml-1">Reference</label>
                        <input type="text" name="reference" required class="w-full bg-slate-100 dark:bg-slate-900/50 border border-white/5 rounded-2xl px-5 py-4 focus:ring-2 focus:ring-teal-500/50 outline-none" placeholder="JE-2026-0001">
                    </div>
                    <div class="space-y-3">
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest ml-1">Date</label>
                        <input type="date" name="transaction_date" required class="w-full bg-slate-100 dark:bg-slate-900/50 border border-white/5 rounded-2xl px-5 py-4 focus:ring-2 focus:ring-teal-500/50 outline-none" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="space-y-3">
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest ml-1">Currency</label>
                        <select name="currency" class="w-full bg-slate-100 dark:bg-slate-900/50 border border-white/5 rounded-2xl px-5 py-4 focus:ring-2 focus:ring-teal-500/50 outline-none">
                            <option value="PHP">PHP</option>
                            <option value="USD">USD</option>
                        </select>
                    </div>
                </div>

                <div class="space-y-3">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest ml-1">Description</label>
                    <textarea name="description" rows="2" class="w-full bg-slate-100 dark:bg-slate-900/50 border border-white/5 rounded-2xl px-5 py-4 focus:ring-2 focus:ring-teal-500/50 outline-none" placeholder="Purpose of this entry..."></textarea>
                </div>

                <div class="space-y-4">
                    <div class="flex justify-between items-center px-1">
                        <h4 class="text-sm font-bold uppercase tracking-widest text-teal-400">Postings</h4>
                        <button type="button" onclick="addRow()" class="text-xs font-bold text-teal-400 hover:text-teal-300 flex items-center gap-1 group">
                            <i data-lucide="plus" class="w-4 h-4 transition-transform group-hover:rotate-90"></i> Add Row
                        </button>
                    </div>
                    <div class="glass rounded-3xl overflow-hidden border border-white/5">
                        <table class="w-full text-left" id="je-table">
                            <thead class="bg-slate-100 dark:bg-slate-800/50">
                                <tr>
                                    <th class="px-6 py-4 text-xs font-bold uppercase tracking-widest text-slate-500">Account</th>
                                    <th class="px-6 py-4 text-xs font-bold uppercase tracking-widest text-slate-500 w-40">Debit</th>
                                    <th class="px-6 py-4 text-xs font-bold uppercase tracking-widest text-slate-500 w-40">Credit</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                <!-- JS-generated rows -->
                            </tbody>
                            <tfoot class="bg-slate-50 dark:bg-white/5 font-black">
                                <tr>
                                    <td class="px-6 py-4 text-right text-xs uppercase tracking-widest text-slate-500">Totals</td>
                                    <td class="px-6 py-4 text-teal-400" id="total-debit">0.00</td>
                                    <td class="px-6 py-4 text-rose-400" id="total-credit">0.00</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <div class="flex justify-end gap-4 pt-6">
                    <button type="button" onclick="toggleModal('je-modal')" class="px-8 py-4 glass rounded-2xl font-bold hover:bg-white/10 transition-all">Cancel</button>
                    <button type="submit" id="submit-btn" disabled class="px-12 py-4 bg-slate-700 text-slate-400 rounded-2xl font-black transition-all cursor-not-allowed">
                        Post Entry
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Review Journal Entry -->
<div id="je-review-modal" class="fixed inset-0 z-[110] hidden bg-slate-950/60 backdrop-blur-md flex items-center justify-center p-4">
    <div class="glass w-full max-w-sm overflow-hidden rounded-[3rem] border border-white/10 shadow-2xl animate-scale-in">
        <div class="p-8 text-center">
            <div class="w-20 h-20 bg-indigo-500/10 rounded-full flex items-center justify-center mx-auto mb-6 shadow-xl shadow-indigo-500/20 relative overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-br from-indigo-500/20 to-transparent pointer-events-none"></div>
                <i data-lucide="clipboard-check" class="w-10 h-10 text-indigo-400 relative z-10"></i>
            </div>
            <h2 class="text-2xl font-black uppercase tracking-tight mb-2">Review <span class="gradient-text">Entry</span></h2>
            <p class="text-slate-500 text-sm mb-8 font-medium">Please choose an action for this journal posting.</p>
            
            <form action="<?= BASE_URL ?>modules/gl/gl_approve.php" method="POST" class="flex flex-col gap-3">
                <input type="hidden" name="journal_id" id="review-journal-id" value="">
                
                <button type="submit" name="action" value="Approve" class="w-full py-4 bg-teal-500 hover:bg-teal-600 text-slate-900 rounded-2xl font-black transition-all shadow-xl shadow-teal-500/20 flex items-center justify-center gap-2">
                    <i data-lucide="check-circle-2" class="w-5 h-5"></i> Authorize
                </button>
                <button type="submit" name="action" value="Reject" class="w-full py-4 bg-rose-500/10 hover:bg-rose-500/20 text-rose-500 hover:text-white rounded-2xl font-black transition-all border border-rose-500/20 hover:border-rose-500 flex items-center justify-center gap-2 group">
                    <i data-lucide="x-circle" class="w-5 h-5 group-hover:scale-110 transition-transform"></i> Reject
                </button>
                <button type="button" onclick="toggleModal('je-review-modal')" class="w-full py-4 text-slate-500 hover:text-white rounded-2xl font-bold transition-all mt-2">
                    Cancel
                </button>
            </form>
        </div>
    </div>
</div>

<template id="entry-row">
    <tr class="hover:bg-white/5 transition-colors">
        <td class="px-6 py-3">
            <select name="accounts[]" required class="w-full bg-transparent border-none focus:ring-0 text-sm font-semibold">
                <option value="">Select Account...</option>
                <?php foreach ($coa as $row): ?>
                    <option value="<?= $row['account_id'] ?>"><?= $row['account_code'] ?> - <?= $row['account_name'] ?></option>
                <?php endforeach; ?>
            </select>
        </td>
        <td class="px-6 py-3 border-l border-white/5">
            <input type="number" step="0.01" name="debits[]" onchange="calculateTotals()" class="w-full bg-transparent border-none focus:ring-0 text-sm font-black text-teal-400" placeholder="0.00">
        </td>
        <td class="px-6 py-3 border-l border-white/5">
            <input type="number" step="0.01" name="credits[]" onchange="calculateTotals()" class="w-full bg-transparent border-none focus:ring-0 text-sm font-black text-rose-400" placeholder="0.00">
        </td>
    </tr>
</template>

<script>
    function openReviewjeModal(id) {
        document.getElementById('review-journal-id').value = id;
        toggleModal('je-review-modal');
    }

    function toggleModal(id) {
        const modal = document.getElementById(id);
        modal.classList.toggle('hidden');
        if (!modal.classList.contains('hidden')) {
            // Initial rows if empty
            if (document.querySelectorAll('#je-table tbody tr').length === 0) {
                addRow();
                addRow();
            }
        }
    }

    function addRow() {
        const table = document.getElementById('je-table').querySelector('tbody');
        const template = document.getElementById('entry-row');
        table.appendChild(template.content.cloneNode(true));
        lucide.createIcons();
    }

    function calculateTotals() {
        let totalDebit = 0, totalCredit = 0;
        document.querySelectorAll('input[name="debits[]"]').forEach(i => totalDebit += parseFloat(i.value) || 0);
        document.querySelectorAll('input[name="credits[]"]').forEach(i => totalCredit += parseFloat(i.value) || 0);
        
        document.getElementById('total-debit').innerText = totalDebit.toFixed(2);
        document.getElementById('total-credit').innerText = totalCredit.toFixed(2);
        
        const btn = document.getElementById('submit-btn');
        if (totalDebit === totalCredit && totalDebit > 0) {
            btn.disabled = false;
            btn.classList.remove('bg-slate-700', 'text-slate-400', 'cursor-not-allowed');
            btn.classList.add('bg-teal-500', 'text-slate-900', 'shadow-xl', 'shadow-teal-500/20');
        } else {
            btn.disabled = true;
            btn.classList.add('bg-slate-700', 'text-slate-400', 'cursor-not-allowed');
            btn.classList.remove('bg-teal-500', 'text-slate-900', 'shadow-xl', 'shadow-teal-500/20');
        }
    }

    lucide.createIcons();
</script>
<?php if ($_SESSION['role'] === 'Admin'): ?>
<script src="<?= BASE_URL ?>assets/js/admin_delete.js"></script>
<?php endif; ?>
</body>
</html>
