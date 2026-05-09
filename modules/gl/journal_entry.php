<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php';
check_login();

$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = 'gl';

include __DIR__ . '/../../includes/header_dashboard.php';

$coa = $pdo->query("SELECT account_id, account_code, account_name FROM chart_of_accounts ORDER BY account_code")->fetchAll();
?>

<div class="flex">
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <main class="ml-72 flex-grow p-10 min-h-screen">
        <header class="flex justify-between items-center mb-10 animate-fade-in">
            <div>
                <h2 class="text-3xl font-bold">Journal <span class="gradient-text">Entry</span></h2>
                <p class="text-slate-500 dark:text-slate-400 mt-1">Manual financial postings with double-entry validation.</p>
            </div>
            <div class="flex gap-4">
                <a href="<?= BASE_URL ?>modules/gl/coa_manager.php" class="px-5 py-2 glass rounded-xl hover:bg-white/10 transition-all flex items-center gap-2 text-sm font-bold">
                    <i data-lucide="list" class="w-4 h-4"></i> View COA
                </a>
            </div>
        </header>

        <form action="<?= BASE_URL ?>modules/gl/gl_post.php" method="POST" class="glass p-10 rounded-[2.5rem] border border-white/5 space-y-8 shadow-2xl animate-fade-in" style="animation-delay: 0.1s;">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="space-y-4">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest ml-1">Reference Number</label>
                    <input type="text" name="reference" required class="w-full bg-slate-100 dark:bg-slate-900/50 border border-white/5 rounded-2xl px-5 py-4 focus:ring-2 focus:ring-teal-500/50 outline-none" placeholder="JE-2026-0001">
                </div>
                <div class="space-y-4">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest ml-1">Transaction Date</label>
                    <input type="date" name="transaction_date" required class="w-full bg-slate-100 dark:bg-slate-900/50 border border-white/5 rounded-2xl px-5 py-4 focus:ring-2 focus:ring-teal-500/50 outline-none" value="<?= date('Y-m-d') ?>">
                </div>
                <div class="space-y-4">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest ml-1">Currency</label>
                    <select name="currency" class="w-full bg-slate-100 dark:bg-slate-900/50 border border-white/5 rounded-2xl px-5 py-4 focus:ring-2 focus:ring-teal-500/50 outline-none">
                        <option value="PHP">PHP - Philippine Peso</option>
                        <option value="USD">USD - US Dollar</option>
                    </select>
                </div>
            </div>

            <div class="space-y-4">
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest ml-1">Description / Memo</label>
                <textarea name="description" rows="2" class="w-full bg-slate-100 dark:bg-slate-900/50 border border-white/5 rounded-2xl px-5 py-4 focus:ring-2 focus:ring-teal-500/50 outline-none" placeholder="Purpose of this entry..."></textarea>
            </div>

            <!-- Entries Table -->
            <div class="space-y-4">
                <div class="flex justify-between items-center mb-2">
                    <h4 class="text-sm font-bold uppercase tracking-widest text-teal-500">Postings</h4>
                    <button type="button" onclick="addRow()" class="text-xs font-bold text-teal-400 hover:text-teal-300 flex items-center gap-1">
                        <i data-lucide="plus" class="w-4 h-4"></i> Add Row
                    </button>
                </div>
                <div class="glass rounded-3xl overflow-hidden border border-white/5">
                    <table class="w-full text-left" id="je-table">
                        <thead class="bg-slate-100 dark:bg-slate-800/50">
                            <tr>
                                <th class="px-6 py-4 text-xs font-bold uppercase tracking-widest text-slate-500">Account</th>
                                <th class="px-6 py-4 text-xs font-bold uppercase tracking-widest text-slate-500 w-48">Debit</th>
                                <th class="px-6 py-4 text-xs font-bold uppercase tracking-widest text-slate-500 w-48">Credit</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <!-- Rows added via JS -->
                        </tbody>
                        <tfoot class="bg-slate-50 dark:bg-white/5">
                            <tr class="font-bold">
                                <td class="px-6 py-4 text-right text-xs uppercase tracking-widest text-slate-500">Total</td>
                                <td class="px-6 py-4 text-teal-400" id="total-debit">0.00</td>
                                <td class="px-6 py-4 text-rose-400" id="total-credit">0.00</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <div class="flex justify-end pt-6">
                <button type="submit" id="submit-btn" disabled class="px-10 py-4 bg-slate-500 text-white rounded-2xl text-lg font-black transition-all cursor-not-allowed">
                    Validate Entries
                </button>
            </div>
        </form>
    </main>
</div>

<template id="entry-row">
    <tr class="hover:bg-white/5 transition-colors">
        <td class="px-6 py-4">
            <select name="accounts[]" required class="w-full bg-transparent border-none focus:ring-0 text-sm font-semibold">
                <option value="">Select Account...</option>
                <?php foreach ($coa as $row): ?>
                    <option value="<?= $row['account_id'] ?>"><?= $row['account_code'] ?> - <?= $row['account_name'] ?></option>
                <?php endforeach; ?>
            </select>
        </td>
        <td class="px-6 py-4 border-l border-white/5">
            <input type="number" step="0.01" name="debits[]" onchange="calculateTotals()" class="w-full bg-transparent border-none focus:ring-0 text-sm font-black text-teal-400 placeholder:text-slate-700" placeholder="0.00">
        </td>
        <td class="px-6 py-4 border-l border-white/5">
            <input type="number" step="0.01" name="credits[]" onchange="calculateTotals()" class="w-full bg-transparent border-none focus:ring-0 text-sm font-black text-rose-400 placeholder:text-slate-700" placeholder="0.00">
        </td>
    </tr>
</template>

<script>
    function addRow() {
        const table = document.getElementById('je-table').getElementsByTagName('tbody')[0];
        const template = document.getElementById('entry-row');
        const clone = template.content.cloneNode(true);
        table.appendChild(clone);
    }

    function calculateTotals() {
        let totalDebit = 0;
        let totalCredit = 0;
        
        document.querySelectorAll('input[name="debits[]"]').forEach(input => {
            totalDebit += parseFloat(input.value) || 0;
        });
        
        document.querySelectorAll('input[name="credits[]"]').forEach(input => {
            totalCredit += parseFloat(input.value) || 0;
        });
        
        document.getElementById('total-debit').innerText = totalDebit.toFixed(2);
        document.getElementById('total-credit').innerText = totalCredit.toFixed(2);
        
        const btn = document.getElementById('submit-btn');
        if (totalDebit === totalCredit && totalDebit > 0) {
            btn.disabled = false;
            btn.classList.remove('bg-slate-500', 'cursor-not-allowed');
            btn.classList.add('bg-teal-500', 'hover:bg-teal-600', 'shadow-xl', 'shadow-teal-500/20');
            btn.innerText = 'Post Transaction';
        } else {
            btn.disabled = true;
            btn.classList.add('bg-slate-500', 'cursor-not-allowed');
            btn.classList.remove('bg-teal-500', 'hover:bg-teal-600', 'shadow-xl', 'shadow-teal-500/20');
            btn.innerText = 'Validate Entries';
        }
    }

    // Initialize with 2 rows
    addRow();
    addRow();
    lucide.createIcons();
</script>
</body>
</html>
