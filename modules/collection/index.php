<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php';
check_login();

$current_page = 'index.php';
$current_dir = 'collection';

// Summary Stats
$total_collected = $pdo->query("SELECT SUM(amount_paid) FROM collections")->fetchColumn() ?: 0;
$pending_invoices = $pdo->query("SELECT COUNT(*) FROM ar_invoices WHERE status != 'Paid'")->fetchColumn();
$overdue_count = $pdo->query("SELECT COUNT(*) FROM ar_invoices WHERE status = 'Unpaid' AND due_date < CURDATE()")->fetchColumn();

// Get Customers for Modal
$customers = $pdo->query("SELECT * FROM customers ORDER BY name")->fetchAll();
// Get Unpaid Invoices for Modal
$unpaid_invoices = $pdo->query("SELECT i.*, c.name as customer_name FROM ar_invoices i JOIN customers c ON i.customer_id = c.customer_id WHERE i.status != 'Paid' ORDER BY i.due_date")->fetchAll();

include __DIR__ . '/../../includes/header_dashboard.php';
?>

<div class="flex">
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <main class="ml-72 flex-grow p-10 min-h-screen">
        <header class="mb-10 animate-fade-in px-8 py-12 glass rounded-[2.5rem] border border-white/10 dark:border-white/5 relative overflow-hidden shadow-2xl">
            <div class="absolute inset-0 bg-gradient-to-r from-indigo-500/10 via-transparent to-transparent pointer-events-none"></div>
            <div class="relative flex justify-between items-center">
                <h2 class="text-4xl font-black tracking-tight uppercase text-slate-900 dark:text-white">Collections <span class="gradient-text">Hub</span></h2>
                <div class="flex gap-4">
                    <?php if (has_permission('collection', 'create')): ?>
                        <button onclick="toggleModal('payment-intake-modal')" class="px-8 py-4 bg-indigo-500 hover:bg-indigo-600 text-white font-black rounded-2xl transition-all shadow-xl shadow-indigo-500/20 flex items-center gap-2">
                            <i data-lucide="plus-circle" class="w-5 h-5"></i> Post Payment
                        </button>
                    <?php endif; ?>
                    <?php if (has_permission('collection', 'create')): // Mass billing is a creation task ?>
                        <button onclick="toggleModal('billing-engine-modal')" class="px-6 py-4 glass rounded-2xl hover:bg-white/10 transition-all flex items-center gap-2 text-sm font-black text-slate-900 dark:text-white border border-white/5">
                            <i data-lucide="zap" class="w-4 h-4 text-teal-400"></i> Billing Engine
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </header>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10 animate-fade-in" style="animation-delay: 0.1s;">
            <div class="glass p-6 rounded-3xl border-l-4 border-teal-500">
                <p class="text-slate-500 uppercase text-xs font-black tracking-widest mb-1">Total Collections</p>
                <h3 class="text-3xl font-black"><?= CURRENCY ?><?= number_format($total_collected, 2) ?></h3>
            </div>
            <div class="glass p-6 rounded-3xl border-l-4 border-indigo-500">
                <p class="text-slate-500 uppercase text-xs font-black tracking-widest mb-1">Active Invoices</p>
                <h3 class="text-3xl font-black"><?= $pending_invoices ?></h3>
            </div>
            <div class="glass p-6 rounded-3xl border-l-4 border-rose-500">
                <p class="text-slate-500 uppercase text-xs font-black tracking-widest mb-1">Overdue Accounts</p>
                <h3 class="text-3xl font-black"><?= $overdue_count ?></h3>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 animate-fade-in" style="animation-delay: 0.2s;">
            <!-- Recent Collections -->
            <div class="glass p-8 rounded-[2.5rem]">
                <div class="flex justify-between items-center mb-6">
                    <h4 class="font-bold flex items-center gap-2"><i data-lucide="history" class="w-4 h-4 text-indigo-400"></i> Recent Payments</h4>
                    <a href="#" class="text-xs text-slate-500 hover:text-white font-bold">View Ledger</a>
                </div>
                <div class="space-y-4">
                    <?php
                    $recent = $pdo->query("SELECT c.*, cust.name as customer_name FROM collections c LEFT JOIN ar_invoices i ON c.invoice_id = i.ar_invoice_id LEFT JOIN customers cust ON i.customer_id = cust.customer_id ORDER BY c.collected_at DESC LIMIT 5")->fetchAll();
                    foreach ($recent as $item):
                    ?>
                        <div class="flex items-center justify-between p-4 bg-white/5 rounded-2xl border border-white/5">
                            <div>
                                <p class="text-sm font-bold"><?= $item['customer_name'] ?: 'General Collection' ?></p>
                                <p class="text-xs text-slate-500">OR# <?= $item['or_number'] ?> • <?= $item['payment_mode'] ?></p>
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="text-right">
                                    <p class="text-sm font-black text-teal-400"><?= CURRENCY ?><?= number_format($item['amount_paid'], 2) ?></p>
                                    <p class="text-xs text-slate-600 uppercase font-black"><?= date('M d, H:i', strtotime($item['collected_at'])) ?></p>
                                </div>
                                <?php if ($_SESSION['role'] === 'Admin'): ?>
                                <button onclick="adminDelete('collection', <?= $item['collection_id'] ?>, 'Collection #<?= $item['collection_id'] ?>')" class="p-2 text-rose-500 hover:text-rose-400 hover:bg-rose-500/10 rounded-lg transition-all" title="Delete">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($recent)): ?>
                        <p class="text-center py-10 text-slate-600 italic">No payments recorded today.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Aging Analysis -->
            <div class="glass p-8 rounded-[2.5rem] flex flex-col">
                <h4 class="font-bold flex items-center gap-2 mb-6"><i data-lucide="pie-chart" class="w-4 h-4 text-teal-400"></i> Accounts Receivable</h4>
                <div class="space-y-4 flex-grow">
                    <div class="p-6 bg-white/5 rounded-3xl border border-white/5">
                        <div class="flex justify-between items-end mb-4">
                            <div>
                                <p class="text-xs font-black uppercase text-slate-500">Total Outstanding</p>
                                <h5 class="text-2xl font-black">
                                    <?php
                                    $outstanding = $pdo->query("SELECT SUM(balance_due) FROM ar_invoices WHERE status != 'Paid'")->fetchColumn() ?: 0;
                                    echo CURRENCY . number_format($outstanding, 2);
                                    ?>
                                </h5>
                            </div>
                            <div class="text-right">
                                <span class="px-3 py-1 bg-rose-500/10 text-rose-500 text-xs font-black rounded-full uppercase">Check Liquidity</span>
                            </div>
                        </div>
                        <!-- Progress bar simulation for aging -->
                        <div class="w-full h-2 bg-slate-800 rounded-full flex overflow-hidden">
                            <div class="h-full bg-teal-500" style="width: 60%"></div>
                            <div class="h-full bg-indigo-500" style="width: 25%"></div>
                            <div class="h-full bg-rose-500" style="width: 15%"></div>
                        </div>
                        <div class="flex justify-between mt-3 text-xs font-black uppercase tracking-tighter text-slate-600">
                            <span>Current</span>
                            <span>30-60 Days</span>
                            <span>90+ Days</span>
                        </div>
                    </div>
                    
                    <a href="<?= BASE_URL ?>modules/collection/deposit_process.php" class="mt-auto group p-6 bg-gradient-to-br from-teal-500/10 to-indigo-500/10 rounded-3xl border border-white/5 flex items-center justify-between hover:from-teal-500/20 transition-all">
                        <div>
                            <p class="font-bold text-sm">Prepare Bank Deposit</p>
                            <p class="text-xs text-slate-500">Batch collected cash & checks for today.</p>
                        </div>
                        <i data-lucide="arrow-right" class="w-5 h-5 text-teal-400 group-hover:translate-x-1 transition-transform"></i>
                    </a>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Modal: Billing Engine -->
<div id="billing-engine-modal" class="fixed inset-0 z-[110] hidden bg-slate-950/60 backdrop-blur-md flex items-center justify-center p-4">
    <div class="glass w-full max-w-4xl rounded-[3rem] border border-white/10 shadow-2xl overflow-hidden animate-scale-in">
        <div class="p-12 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-64 h-64 bg-teal-500/10 blur-[80px] -translate-y-1/2 translate-x-1/2"></div>
            
            <header class="flex justify-between items-center mb-10">
                <div>
                    <h2 class="text-3xl font-bold dark:text-white text-slate-900">Billing <span class="gradient-text">Engine</span></h2>
                    <p class="text-slate-500 text-sm mt-1">Generate new accounts receivable invoices.</p>
                </div>
                <button onclick="toggleModal('billing-engine-modal')" class="p-3 hover:bg-white/5 rounded-full transition-colors">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </header>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-10 relative z-10">
                <div class="space-y-6">
                    <div class="p-8 bg-white/5 rounded-[2rem] border border-white/5 space-y-4">
                        <div class="w-16 h-16 bg-teal-500/10 rounded-2xl flex items-center justify-center">
                            <i data-lucide="zap" class="w-8 h-8 text-teal-400"></i>
                        </div>
                        <h4 class="text-lg font-bold">Standard Billing Cycle</h4>
                        <p class="text-slate-500 text-xs leading-relaxed">This routine queries all active service agreements and generates tax-compliant invoices for the <strong><?= date('F Y') ?></strong> period automatically.</p>
                        
                        <form action="<?= BASE_URL ?>modules/collection/billing_gen.php" method="POST">
                            <button type="submit" name="run_billing" class="w-full py-4 bg-teal-500 hover:bg-teal-600 text-slate-900 font-black rounded-2xl transition-all shadow-xl shadow-teal-500/20 flex items-center justify-center gap-2">
                                <i data-lucide="play" class="w-4 h-4"></i> Execute Batch
                            </button>
                        </form>
                    </div>
                </div>

                <div class="space-y-6">
                    <h5 class="text-xs font-black uppercase tracking-widest text-slate-500 ml-1">Generation Log</h5>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between p-4 bg-white/5 rounded-2xl border border-white/5 opacity-50">
                            <div>
                                <p class="text-sm font-bold uppercase tracking-tighter">Batch #0081</p>
                                <p class="text-xs text-slate-500"><?= date('M Y', strtotime('-1 month')) ?> • 142 Invoices</p>
                            </div>
                            <span class="text-xs font-black uppercase text-teal-400 bg-teal-500/10 px-2 py-1 rounded-lg">Posted</span>
                        </div>
                        <div class="p-6 border border-dashed border-white/10 rounded-2xl text-center">
                            <p class="text-xs text-slate-600 italic">No recent batches for current period.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Payment Intake -->
<div id="payment-intake-modal" class="fixed inset-0 z-[110] hidden bg-slate-950/60 backdrop-blur-md flex items-center justify-center p-4">
    <div class="glass w-full max-w-2xl rounded-[3rem] border border-white/10 shadow-2xl overflow-hidden animate-scale-in">
        <div class="p-10 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-64 h-64 bg-indigo-500/10 blur-[80px] -translate-y-1/2 translate-x-1/2"></div>
            
            <header class="flex justify-between items-center mb-10 relative z-10">
                <div>
                    <h2 class="text-3xl font-black uppercase tracking-tight text-slate-900 dark:text-white">Post <span class="gradient-text">Payment</span></h2>
                    <p class="text-slate-400 text-sm mt-1">Record collection and issue OR</p>
                </div>
                <button onclick="toggleModal('payment-intake-modal')" class="p-3 hover:bg-white/5 rounded-full transition-colors">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </header>

            <form action="<?= BASE_URL ?>modules/collection/payment_intake.php" method="POST" class="space-y-6 relative z-10">
                <div class="space-y-3">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest ml-1">Select Invoice</label>
                    <select name="invoice_id" required class="w-full bg-slate-100 dark:bg-slate-900/50 border border-white/5 rounded-2xl px-5 py-4 focus:ring-2 focus:ring-indigo-500/50 outline-none text-slate-900 dark:text-white">
                        <option value="">Choose Pending Invoice...</option>
                        <?php foreach ($unpaid_invoices as $inv): ?>
                            <option value="<?= $inv['ar_invoice_id'] ?>" data-amount="<?= $inv['balance_due'] ?>">
                                <?= $inv['customer_name'] ?> - INV#<?= $inv['ar_invoice_id'] ?> (<?= CURRENCY ?><?= number_format($inv['balance_due'], 2) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-3">
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest ml-1">Amount Paid</label>
                        <div class="relative">
                            <span class="absolute left-5 top-1/2 -translate-y-1/2 text-slate-500 font-bold"><?= CURRENCY ?></span>
                            <input type="number" step="0.01" name="amount_paid" id="amount-input" required class="w-full bg-slate-100 dark:bg-slate-900/50 border border-white/5 rounded-2xl pl-10 pr-5 py-4 focus:ring-2 focus:ring-teal-500/50 outline-none font-black text-teal-400" placeholder="0.00">
                        </div>
                    </div>
                    <div class="space-y-3">
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest ml-1">Payment Mode</label>
                        <select name="payment_mode" required class="w-full bg-slate-100 dark:bg-slate-900/50 border border-white/5 rounded-2xl px-5 py-4 focus:ring-2 focus:ring-indigo-500/50 outline-none text-slate-900 dark:text-white">
                            <option value="Cash">Cash</option>
                            <option value="Check">Check</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                        </select>
                    </div>
                </div>

                <div class="space-y-3">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest ml-1">Collection Date</label>
                    <input type="datetime-local" name="collected_at" required class="w-full bg-slate-100 dark:bg-slate-900/50 border border-white/5 rounded-2xl px-5 py-4 focus:ring-2 focus:ring-indigo-500/50 outline-none text-slate-900 dark:text-white" value="<?= date('Y-m-d\TH:i') ?>">
                </div>

                <div class="flex justify-end gap-4 pt-6">
                    <button type="button" onclick="toggleModal('payment-intake-modal')" class="px-8 py-4 glass rounded-2xl font-bold hover:bg-white/10 transition-all">Cancel</button>
                    <button type="submit" class="px-12 py-4 bg-indigo-500 text-white rounded-2xl font-black transition-all shadow-xl shadow-indigo-500/20 flex items-center gap-2">
                        <i data-lucide="check-circle" class="w-5 h-5"></i> Confirm Payment
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

    const invSelect = document.querySelector('select[name="invoice_id"]');
    if (invSelect) {
        invSelect.addEventListener('change', function() {
            const option = this.options[this.selectedIndex];
            const amount = option.getAttribute('data-amount');
            if (amount) {
                document.getElementById('amount-input').value = amount;
            }
        });
    }

    lucide.createIcons();
</script>
<?php if ($_SESSION['role'] === 'Admin'): ?>
<script src="<?= BASE_URL ?>assets/js/admin_delete.js"></script>
<?php endif; ?>
</body>
</html>
