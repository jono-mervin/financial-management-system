<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php';
check_login();

// Current directory for sidebar highlight
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = 'gl';

// COA Logic
$accounts = $pdo->query("SELECT * FROM chart_of_accounts ORDER BY account_code ASC")->fetchAll();

include __DIR__ . '/../../includes/header_dashboard.php';
?>

<div class="flex">
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <main class="ml-72 flex-grow p-10 min-h-screen">
        <header class="mb-10 animate-fade-in px-8 py-12 glass rounded-[2.5rem] border border-white/10 dark:border-white/5 relative overflow-hidden shadow-2xl">
            <div class="absolute inset-0 bg-gradient-to-r from-teal-500/10 via-transparent to-transparent pointer-events-none"></div>
            <div class="relative flex justify-between items-center">
                <h2 class="text-4xl font-black tracking-tight uppercase text-slate-900 dark:text-white">Chart of <span class="gradient-text">Accounts</span></h2>
                <div class="flex gap-4">
                    <button onclick="toggleModal('coa-modal')" class="px-8 py-4 bg-teal-500 hover:bg-teal-600 text-slate-900 font-black rounded-2xl transition-all shadow-xl shadow-teal-500/20 flex items-center gap-2">
                        <i data-lucide="plus" class="w-5 h-5"></i> New Account
                    </button>
                </div>
            </div>
        </header>

        <div class="glass rounded-[2rem] overflow-hidden border border-white/5">
            <table class="w-full text-left">
                <thead class="bg-slate-100 dark:bg-slate-800/50">
                    <tr>
                        <th class="px-6 py-4 text-sm font-bold uppercase tracking-widest text-slate-500">Code</th>
                        <th class="px-6 py-4 text-sm font-bold uppercase tracking-widest text-slate-500">Account Name</th>
                        <th class="px-6 py-4 text-sm font-bold uppercase tracking-widest text-slate-500">Type</th>
                        <th class="px-6 py-4 text-sm font-bold uppercase tracking-widest text-slate-500">Normal Balance</th>
                        <th class="px-6 py-4 text-sm font-bold uppercase tracking-widest text-slate-500 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    <?php if (empty($accounts)): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-slate-500 italic">No accounts found. Use the setup script or add one manually.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($accounts as $account): ?>
                            <tr class="hover:bg-white/5 transition-colors">
                                <td class="px-6 py-4 font-mono text-teal-400 font-bold"><?= $account['account_code'] ?></td>
                                <td class="px-6 py-4 font-semibold"><?= $account['account_name'] ?></td>
                                <td class="px-6 py-4">
                                    <span class="px-3 py-1 bg-slate-100 dark:bg-slate-800 rounded-full text-xs font-bold uppercase tracking-tighter">
                                        <?= $account['account_type'] ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm"><?= $account['normal_balance'] ?></td>
                                <td class="px-6 py-4 text-right">
                                    <button onclick='editAccount(<?= json_encode(["id" => $account['account_id'], "code" => $account['account_code'], "name" => $account['account_name'], "type" => $account['account_type'], "balance" => $account['normal_balance']]) ?>)' class="text-slate-400 hover:text-white transition-colors p-2">
                                        <i data-lucide="edit-3" class="w-4 h-4"></i>
                                    </button>
                                    <button onclick="adminDelete('coa', <?= $account['account_id'] ?>, '<?= addslashes($account['account_name']) ?>')" class="text-rose-500 hover:text-rose-400 transition-colors p-2">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<!-- Modal: Account Management -->
<div id="coa-modal" class="fixed inset-0 z-[110] hidden bg-slate-950/60 backdrop-blur-md flex items-center justify-center p-4">
    <div class="glass w-full max-w-2xl rounded-[3rem] border border-white/10 shadow-2xl overflow-hidden animate-scale-in">
        <div class="p-10 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-64 h-64 bg-teal-500/10 blur-[80px] -translate-y-1/2 translate-x-1/2"></div>
            
            <header class="flex justify-between items-center mb-10 relative z-10">
                <div>
                    <h2 class="text-3xl font-black uppercase tracking-tight text-white" id="modal-title">Manage <span class="gradient-text">Account</span></h2>
                    <p class="text-slate-400 text-sm mt-1">Configure Chart of Accounts entry</p>
                </div>
                <button onclick="toggleModal('coa-modal')" class="p-3 hover:bg-white/5 rounded-full transition-colors">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </header>

            <form id="coa-form" method="POST" action="coa_save.php" class="space-y-6 relative z-10">
                <input type="hidden" name="action" id="form-action" value="create">
                <input type="hidden" name="account_id" id="form-account-id">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-3">
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest ml-1">Account Code</label>
                        <input type="text" name="account_code" id="form-code" required class="w-full bg-slate-100 dark:bg-slate-900/50 border border-white/5 rounded-2xl px-5 py-4 focus:ring-2 focus:ring-teal-500/50 outline-none font-mono text-teal-400" placeholder="e.g. 1010">
                    </div>
                    <div class="space-y-3">
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest ml-1">Normal Balance</label>
                        <select name="normal_balance" id="form-balance" required class="w-full bg-slate-100 dark:bg-slate-900/50 border border-white/5 rounded-2xl px-5 py-4 focus:ring-2 focus:ring-teal-500/50 outline-none">
                            <option value="Debit">Debit</option>
                            <option value="Credit">Credit</option>
                        </select>
                    </div>
                </div>

                <div class="space-y-3">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest ml-1">Account Name</label>
                    <input type="text" name="account_name" id="form-name" required class="w-full bg-slate-100 dark:bg-slate-900/50 border border-white/5 rounded-2xl px-5 py-4 focus:ring-2 focus:ring-teal-500/50 outline-none" placeholder="e.g. Cash in Bank">
                </div>

                <div class="space-y-3">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest ml-1">Account Type</label>
                    <select name="account_type" id="form-type" required class="w-full bg-slate-100 dark:bg-slate-900/50 border border-white/5 rounded-2xl px-5 py-4 focus:ring-2 focus:ring-teal-500/50 outline-none">
                        <option value="Asset">Asset</option>
                        <option value="Liability">Liability</option>
                        <option value="Equity">Equity</option>
                        <option value="Revenue">Revenue</option>
                        <option value="Expense">Expense</option>
                    </select>
                </div>

                <div class="flex justify-end gap-4 pt-6">
                    <button type="button" onclick="toggleModal('coa-modal')" class="px-8 py-4 glass rounded-2xl font-bold hover:bg-white/10 transition-all">Cancel</button>
                    <button type="submit" class="px-12 py-4 bg-teal-500 text-slate-900 rounded-2xl font-black transition-all shadow-xl shadow-teal-500/20">
                        Save Account
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function toggleModal(id) {
        document.getElementById(id).classList.toggle('hidden');
    }

    function editAccount(data) {
        document.getElementById('modal-title').innerHTML = 'Edit <span class="gradient-text">Account</span>';
        document.getElementById('form-action').value = 'update';
        document.getElementById('form-account-id').value = data.id;
        document.getElementById('form-code').value = data.code;
        document.getElementById('form-name').value = data.name;
        document.getElementById('form-type').value = data.type;
        document.getElementById('form-balance').value = data.balance;
        toggleModal('coa-modal');
    }

    lucide.createIcons();

    // Check for success message
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('msg') && typeof SMS_UI !== 'undefined') {
        const msg = urlParams.get('msg');
        setTimeout(() => {
            SMS_UI.showToast(msg, 'success', 'check-circle');
            window.history.replaceState({}, document.title, window.location.pathname);
        }, 100);
    }
</script>
<?php if ($_SESSION['role'] === 'Admin'): ?>
<script src="<?= BASE_URL ?>assets/js/admin_delete.js"></script>
<?php endif; ?>
</body>
</html>
