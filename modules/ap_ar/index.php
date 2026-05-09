<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php';
check_login();

$current_page = 'index.php';
$current_dir = 'ap_ar';

// AP Stats
$total_ap = $pdo->query("SELECT SUM(amount - tax_withheld) FROM ap_invoices WHERE status != 'Paid'")->fetchColumn() ?: 0;
$overdue_ap = $pdo->query("SELECT COUNT(*) FROM ap_invoices WHERE status != 'Paid' AND due_date < CURDATE()")->fetchColumn();

// AR Stats
$total_ar = $pdo->query("SELECT SUM(balance_due) FROM ar_invoices WHERE status != 'Paid'")->fetchColumn() ?: 0;
$overdue_ar = $pdo->query("SELECT COUNT(*) FROM ar_invoices WHERE status != 'Paid' AND due_date < CURDATE()")->fetchColumn();

// Master Data
$vendors = $pdo->query("SELECT * FROM vendors ORDER BY name")->fetchAll();
$customers = $pdo->query("SELECT * FROM customers ORDER BY name")->fetchAll();

include __DIR__ . '/../../includes/header_dashboard.php';
?>

<div class="flex">
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <main class="ml-72 flex-grow p-10 min-h-screen">
        <header class="mb-10 animate-fade-in px-8 py-12 glass rounded-[2.5rem] border border-white/10 dark:border-white/5 relative overflow-hidden shadow-2xl">
            <div class="absolute inset-0 bg-gradient-to-r from-indigo-500/10 via-transparent to-transparent pointer-events-none"></div>
            <div class="relative flex justify-between items-center">
                <h2 class="text-4xl font-black tracking-tight uppercase text-slate-900 dark:text-white">AP / AR <span class="gradient-text">Ledger</span></h2>
                <div class="flex gap-3">
                    <?php if (has_permission('ap_ar', 'create')): ?>
                        <button onclick="toggleModal('vendor-modal')" class="px-6 py-4 glass rounded-2xl hover:bg-white/10 transition-all flex items-center gap-2 text-xs font-black uppercase tracking-widest text-slate-900 dark:text-white border border-white/5">
                            <i data-lucide="truck" class="w-4 h-4 text-indigo-400"></i> Vendors
                        </button>
                    <?php endif; ?>
                    <?php if (has_permission('ap_ar', 'create')): ?>
                        <button onclick="toggleModal('customer-modal')" class="px-6 py-4 glass rounded-2xl hover:bg-white/10 transition-all flex items-center gap-2 text-xs font-black uppercase tracking-widest text-slate-900 dark:text-white border border-white/5">
                            <i data-lucide="users" class="w-4 h-4 text-teal-400"></i> Customers
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </header>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-10 animate-fade-in" style="animation-delay: 0.1s;">
            <!-- Accounts Payable Section -->
            <div class="glass p-8 rounded-[2.5rem] border-t-4 border-rose-500 shadow-xl shadow-rose-500/5">
                <div class="flex justify-between items-start mb-6">
                    <div>
                        <p class="text-xs font-black uppercase tracking-widest text-slate-500 mb-1">Accounts Payable (AP)</p>
                        <h3 class="text-3xl font-black text-rose-400"><?= CURRENCY ?><?= number_format($total_ap, 2) ?></h3>
                    </div>
                    <div class="p-4 bg-rose-500/10 rounded-2xl">
                        <i data-lucide="arrow-up-right" class="w-6 h-6 text-rose-500"></i>
                    </div>
                </div>
                <div class="flex gap-4 text-sm font-bold text-slate-500">
                    <span class="flex items-center gap-1"><i data-lucide="alert-circle" class="w-3 h-3 text-rose-500"></i> <?= $overdue_ap ?> Overdue</span>
                    <span class="text-slate-700">|</span>
                    <?php if (has_permission('ap_ar', 'create')): ?>
                        <button onclick="toggleModal('ap-invoice-modal')" class="text-indigo-400 hover:text-indigo-300">Process Invoices →</button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Accounts Receivable Section -->
            <div class="glass p-8 rounded-[2.5rem] border-t-4 border-teal-500 shadow-xl shadow-teal-500/5">
                <div class="flex justify-between items-start mb-6">
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-widest text-slate-500 mb-1">Accounts Receivable (AR)</p>
                        <h3 class="text-3xl font-black text-teal-400"><?= CURRENCY ?><?= number_format($total_ar, 2) ?></h3>
                    </div>
                    <div class="p-4 bg-teal-500/10 rounded-2xl">
                        <i data-lucide="arrow-down-left" class="w-6 h-6 text-teal-500"></i>
                    </div>
                </div>
                <div class="flex gap-4 text-sm font-bold text-slate-500">
                    <span class="flex items-center gap-1"><i data-lucide="clock" class="w-3 h-3 text-teal-500"></i> <?= $overdue_ar ?> Overdue</span>
                    <span class="text-slate-700">|</span>
                    <?php if (has_permission('ap_ar', 'create')): ?>
                        <button onclick="toggleModal('ar-statement-modal')" class="text-indigo-400 hover:text-indigo-300">Generate Statements →</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 animate-fade-in" style="animation-delay: 0.2s;">
            <!-- Top Propayables -->
            <div class="glass p-8 rounded-[2.5rem]">
                <h4 class="font-bold flex items-center gap-2 mb-6"><i data-lucide="list-ordered" class="w-4 h-4 text-indigo-400"></i> Pending Payables</h4>
                <div class="space-y-4">
                    <?php
                    $pending_ap = $pdo->query("SELECT i.*, v.name as vendor_name FROM ap_invoices i JOIN vendors v ON i.vendor_id = v.vendor_id WHERE i.status != 'Paid' ORDER BY i.due_date LIMIT 5")->fetchAll();
                    foreach ($pending_ap as $ap):
                    ?>
                        <div class="flex items-center justify-between p-4 bg-white/5 rounded-2xl border border-white/5">
                            <div>
                                <p class="text-sm font-bold text-slate-900 dark:text-white"><?= $ap['vendor_name'] ?></p>
                                <p class="text-xs text-slate-500">Due: <?= date('M d, Y', strtotime($ap['due_date'])) ?></p>
                            </div>
                            <div class="flex items-center gap-3">
                                <p class="text-sm font-black text-rose-400"><?= CURRENCY ?><?= number_format($ap['amount'], 2) ?></p>
                                <?php if ($_SESSION['role'] === 'Admin'): ?>
                                    <button onclick="adminDelete('ap_invoice', <?= $ap['ap_invoice_id'] ?>, 'Invoice from <?= addslashes($ap['vendor_name']) ?>')" class="p-2 text-rose-500 hover:text-rose-400 hover:bg-rose-500/10 rounded-lg transition-all" title="Delete">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Top Receivables -->
            <div class="glass p-8 rounded-[2.5rem]">
                <h4 class="font-bold flex items-center gap-2 mb-6"><i data-lucide="trending-up" class="w-4 h-4 text-teal-400"></i> Outstanding Collections</h4>
                <div class="space-y-4">
                    <?php
                    $pending_ar = $pdo->query("SELECT i.*, c.name as customer_name FROM ar_invoices i JOIN customers c ON i.customer_id = c.customer_id WHERE i.status != 'Paid' ORDER BY i.due_date LIMIT 5")->fetchAll();
                    foreach ($pending_ar as $ar):
                    ?>
                        <div class="flex items-center justify-between p-4 bg-white/5 rounded-2xl border border-white/5">
                            <div>
                                <p class="text-sm font-bold text-slate-900 dark:text-white"><?= $ar['customer_name'] ?></p>
                                <p class="text-xs text-slate-500">Due: <?= date('M d, Y', strtotime($ar['due_date'])) ?></p>
                            </div>
                            <div class="flex items-center gap-3">
                                <p class="text-sm font-black text-teal-400"><?= CURRENCY ?><?= number_format($ar['balance_due'], 2) ?></p>
                                <?php if ($_SESSION['role'] === 'Admin'): ?>
                                    <button onclick="adminDelete('ar_invoice', <?= $ar['ar_invoice_id'] ?>, 'Statement for <?= addslashes($ar['customer_name']) ?>')" class="p-2 text-rose-500 hover:text-rose-400 hover:bg-rose-500/10 rounded-lg transition-all" title="Delete">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Modal: Vendor Management -->
<div id="vendor-modal" class="fixed inset-0 z-[110] hidden bg-slate-950/60 backdrop-blur-md flex items-center justify-center p-4">
    <div class="glass w-full max-w-5xl max-h-[90vh] overflow-hidden rounded-[3rem] border border-white/10 shadow-2xl animate-scale-in flex flex-col">
        <div class="p-10 border-b border-white/5 flex justify-between items-center">
            <div>
                <h2 class="text-3xl font-bold uppercase tracking-tight text-slate-900 dark:text-white">Vendor <span class="gradient-text">Master</span></h2>
                <p class="text-slate-600 dark:text-slate-400 text-sm mt-1 font-medium">Manage suppliers and payment terms.</p>
            </div>
            <button onclick="toggleModal('vendor-modal')" class="p-3 hover:bg-black/5 dark:hover:bg-white/5 rounded-full transition-colors text-slate-900 dark:text-white">
                <i data-lucide="x" class="w-6 h-6"></i>
            </button>
        </div>
        
        <div class="flex-grow flex overflow-hidden p-8 gap-8">
            <!-- Vendor List -->
            <div class="w-2/3 overflow-y-auto bg-white/30 dark:bg-black/20 rounded-[2.5rem] border border-white/5 p-8">
                <table class="w-full text-left border-separate border-spacing-y-3 px-2">
                    <thead class="sticky top-0 z-20">
                        <tr class="group">
                            <th class="pb-5 pt-5 text-[10px] font-black uppercase tracking-[0.2em] text-indigo-900/40 dark:text-indigo-300/30 pl-5 rounded-l-2xl border-y border-l border-indigo-100/30 dark:border-white/5 bg-indigo-50/80 dark:bg-slate-900/80 backdrop-blur-md">Vendor Info</th>
                            <th class="pb-5 pt-5 text-[10px] font-black uppercase tracking-[0.2em] text-indigo-900/40 dark:text-indigo-300/30 px-4 border-y border-indigo-100/30 dark:border-white/5 bg-indigo-50/80 dark:bg-slate-900/80 backdrop-blur-md">Banking</th>
                            <th class="pb-5 pt-5 text-[10px] font-black uppercase tracking-[0.2em] text-indigo-900/40 dark:text-indigo-300/30 text-right pr-5 rounded-r-2xl border-y border-r border-indigo-100/30 dark:border-white/5 bg-indigo-50/80 dark:bg-slate-900/80 backdrop-blur-md">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y-0">
                        <?php foreach ($vendors as $v): ?>
                        <tr class="group hover:translate-x-1 transition-all">
                            <td class="py-5 bg-white/40 dark:bg-white/5 rounded-l-2xl px-4 border-y border-l border-slate-100 dark:border-white/5">
                                <p class="font-black text-slate-900 dark:text-white"><?= $v['name'] ?></p>
                                <p class="text-[11px] font-bold text-slate-500 uppercase tracking-tight">TIN: <?= $v['tin'] ?> • <?= $v['credit_terms'] ?> Days</p>
                            </td>
                            <td class="py-5 px-4 bg-white/40 dark:bg-white/5 border-y border-slate-100 dark:border-white/5">
                                <p class="text-xs font-black text-indigo-500 uppercase tracking-tighter"><?= $v['bank_name'] ?></p>
                                <p class="text-[10px] font-mono font-bold text-slate-400"><?= $v['bank_account'] ?></p>
                            </td>
                            <td class="py-5 text-right bg-white/40 dark:bg-white/5 rounded-r-2xl px-4 border-y border-r border-slate-100 dark:border-white/5">
                                <?php if ($_SESSION['role'] === 'Admin'): ?>
                                <button onclick="adminDelete('vendor', <?= $v['vendor_id'] ?>, '<?= addslashes($v['name']) ?>')" class="p-2.5 bg-rose-500/10 text-rose-500 rounded-xl opacity-0 group-hover:opacity-100 transition-all hover:bg-rose-500 hover:text-white shadow-lg shadow-rose-500/10">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Add Vendor Form -->
            <div class="w-1/3 bg-white/30 dark:bg-black/20 rounded-[2.5rem] border border-white/5 p-8 overflow-y-auto">
                <div class="mb-8">
                    <h3 class="text-lg font-black tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
                        <i data-lucide="plus-circle" class="w-5 h-5 text-indigo-500"></i> Add New <span class="gradient-text">Vendor</span>
                    </h3>
                    <p class="text-xs font-bold text-slate-400 mt-1 uppercase tracking-widest">Master Data Entry</p>
                </div>

                <form action="<?= BASE_URL ?>modules/ap_ar/vendor_master.php" method="POST" class="space-y-6">
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-500 ml-1">Vendor Name</label>
                        <input type="text" name="name" required class="w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-white/10 rounded-2xl px-5 py-4 text-sm text-slate-900 dark:text-white focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500/50 outline-none transition-all placeholder:text-slate-300" placeholder="e.g. Acme Corp">
                    </div>
                    
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-500 ml-1">Tax Identification (TIN)</label>
                        <input type="text" name="tin" class="w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-white/10 rounded-2xl px-5 py-4 text-sm text-slate-900 dark:text-white focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500/50 outline-none transition-all placeholder:text-slate-300" placeholder="000-000-000-000">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-500 ml-1">Bank Name</label>
                            <input type="text" name="bank_name" class="w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-white/10 rounded-2xl px-5 py-4 text-sm text-slate-900 dark:text-white focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500/50 outline-none transition-all" placeholder="BDO / BPI">
                        </div>
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-500 ml-1">Credit Terms</label>
                            <div class="relative">
                                <input type="number" name="credit_terms" value="30" class="w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-white/10 rounded-2xl px-5 py-4 text-sm text-slate-900 dark:text-white focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500/50 outline-none transition-all">
                                <span class="absolute right-5 top-1/2 -translate-y-1/2 text-[10px] font-black text-slate-400">DAYS</span>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-500 ml-1">Account Number</label>
                        <input type="text" name="bank_account" class="w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-white/10 rounded-2xl px-5 py-4 text-sm text-slate-900 dark:text-white focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500/50 outline-none transition-all font-mono" placeholder="000000000000">
                    </div>

                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-500 ml-1">Accreditation Status</label>
                        <select name="accreditation_status" class="w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-white/10 rounded-2xl px-5 py-4 text-sm text-slate-900 dark:text-white focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500/50 outline-none transition-all appearance-none">
                            <option value="Active">Active Status</option>
                            <option value="Pending">Pending Review</option>
                            <option value="Blacklisted">Blacklisted</option>
                        </select>
                    </div>

                    <button type="submit" class="w-full py-5 bg-indigo-500 text-white rounded-2xl font-black text-xs uppercase tracking-widest shadow-xl shadow-indigo-500/30 hover:bg-indigo-600 hover:-translate-y-1 transition-all flex items-center justify-center gap-2">
                        <i data-lucide="save" class="w-4 h-4"></i> Save Vendor Record
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Customer Management -->
<div id="customer-modal" class="fixed inset-0 z-[110] hidden bg-slate-950/60 backdrop-blur-md flex items-center justify-center p-4">
    <div class="glass w-full max-w-5xl max-h-[90vh] overflow-hidden rounded-[3rem] border border-white/10 shadow-2xl animate-scale-in flex flex-col">
        <div class="p-10 border-b border-white/5 flex justify-between items-center">
            <div>
                <h2 class="text-3xl font-bold uppercase tracking-tight text-slate-900 dark:text-white">Customer <span class="gradient-text">Master</span></h2>
                <p class="text-slate-600 dark:text-slate-400 text-sm mt-1 font-medium">Manage clients and credit limits.</p>
            </div>
            <button onclick="toggleModal('customer-modal')" class="p-3 hover:bg-black/5 dark:hover:bg-white/5 rounded-full transition-colors text-slate-900 dark:text-white">
                <i data-lucide="x" class="w-6 h-6"></i>
            </button>
        </div>
        <div class="flex-grow flex overflow-hidden p-8 gap-8">
            <!-- Customer List -->
            <div class="w-2/3 overflow-y-auto bg-white/30 dark:bg-black/20 rounded-[2.5rem] border border-white/5 p-8">
                <table class="w-full text-left border-separate border-spacing-y-3 px-2">
                    <thead class="sticky top-0 z-20">
                        <tr class="group">
                            <th class="pb-5 pt-5 text-[10px] font-black uppercase tracking-[0.2em] text-teal-900/40 dark:text-teal-300/30 pl-5 rounded-l-2xl border-y border-l border-teal-100/30 dark:border-white/5 bg-teal-50/80 dark:bg-slate-900/80 backdrop-blur-md">Customer Info</th>
                            <th class="pb-5 pt-5 text-[10px] font-black uppercase tracking-[0.2em] text-teal-900/40 dark:text-teal-300/30 px-4 border-y border-teal-100/30 dark:border-white/5 bg-teal-50/80 dark:bg-slate-900/80 backdrop-blur-md">Credit Status</th>
                            <th class="pb-5 pt-5 text-[10px] font-black uppercase tracking-[0.2em] text-teal-900/40 dark:text-teal-300/30 text-right pr-5 rounded-r-2xl border-y border-r border-teal-100/30 dark:border-white/5 bg-teal-50/80 dark:bg-slate-900/80 backdrop-blur-md">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y-0">
                        <?php foreach ($customers as $c): ?>
                        <tr class="group hover:translate-x-1 transition-all">
                            <td class="py-5 bg-white/40 dark:bg-white/5 rounded-l-2xl px-4 border-y border-l border-slate-100 dark:border-white/5">
                                <p class="font-black text-slate-900 dark:text-white"><?= $c['name'] ?></p>
                                <p class="text-[11px] font-bold text-slate-500 uppercase tracking-tight">TIN: <?= $c['tin'] ?></p>
                            </td>
                            <td class="py-5 px-4 bg-white/40 dark:bg-white/5 border-y border-slate-100 dark:border-white/5">
                                <p class="text-[10px] font-black uppercase text-slate-400 mb-1">Limit: <?= CURRENCY ?><?= number_format($c['credit_limit'], 0) ?></p>
                                <?php if ($c['is_on_hold']): ?>
                                    <span class="px-2.5 py-1 bg-rose-500/10 text-rose-600 text-[9px] font-black uppercase rounded-lg border border-rose-500/20 flex items-center gap-1 w-max"><i data-lucide="clock" class="w-3 h-3"></i> ON HOLD</span>
                                <?php else: ?>
                                    <span class="px-2.5 py-1 bg-teal-500/10 text-teal-600 text-[9px] font-black uppercase rounded-lg border border-teal-500/20 flex items-center gap-1 w-max"><i data-lucide="check" class="w-3 h-3"></i> ACTIVE</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-5 text-right bg-white/40 dark:bg-white/5 rounded-r-2xl px-4 border-y border-r border-slate-100 dark:border-white/5">
                                <?php if ($_SESSION['role'] === 'Admin'): ?>
                                <button onclick="adminDelete('customer', <?= $c['customer_id'] ?>, '<?= addslashes($c['name']) ?>')" class="p-2.5 bg-rose-500/10 text-rose-500 rounded-xl opacity-0 group-hover:opacity-100 transition-all hover:bg-rose-500 hover:text-white shadow-lg shadow-rose-500/10">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Add Customer Form -->
            <div class="w-1/3 bg-white/30 dark:bg-black/20 rounded-[2.5rem] border border-white/5 p-8 overflow-y-auto">
                <div class="mb-8">
                    <h3 class="text-lg font-black tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
                        <i data-lucide="user-plus" class="w-5 h-5 text-teal-500"></i> Add New <span class="gradient-text">Client</span>
                    </h3>
                    <p class="text-xs font-bold text-slate-400 mt-1 uppercase tracking-widest">Master Data Entry</p>
                </div>

                <form action="<?= BASE_URL ?>modules/ap_ar/customer_master.php" method="POST" class="space-y-6">
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-600 dark:text-slate-500 ml-1">Full Name / Entity</label>
                        <input type="text" name="name" required class="w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-white/10 rounded-2xl px-5 py-4 text-sm text-slate-900 dark:text-white focus:ring-4 focus:ring-teal-500/10 focus:border-teal-500/50 outline-none transition-all" placeholder="Client Name">
                    </div>
                    
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-600 dark:text-slate-500 ml-1">Tax Identification</label>
                        <input type="text" name="tin" class="w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-white/10 rounded-2xl px-5 py-4 text-sm text-slate-900 dark:text-white focus:ring-4 focus:ring-teal-500/10 focus:border-teal-500/50 outline-none transition-all" placeholder="000-000-000-000">
                    </div>

                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-600 dark:text-slate-500 ml-1">Credit Limit</label>
                        <div class="relative">
                            <span class="absolute left-5 top-1/2 -translate-y-1/2 text-slate-400 text-xs font-black"><?= CURRENCY ?></span>
                            <input type="number" name="credit_limit" value="50000" class="w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-white/10 rounded-2xl pl-10 pr-5 py-4 text-sm text-slate-900 dark:text-white focus:ring-4 focus:ring-teal-500/10 focus:border-teal-500/50 outline-none transition-all font-black">
                        </div>
                    </div>

                    <div class="flex items-center gap-4 p-5 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-white/10 shadow-sm">
                        <div class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="is_on_hold" id="hold" class="sr-only peer">
                            <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-rose-500"></div>
                        </div>
                        <label for="hold" class="text-xs font-black text-slate-600 dark:text-slate-400 cursor-pointer uppercase tracking-tight">Place Account on Hold</label>
                    </div>

                    <button type="submit" class="w-full py-5 bg-teal-500 text-slate-900 rounded-2xl font-black text-xs uppercase tracking-widest shadow-xl shadow-teal-500/30 hover:bg-teal-600 hover:-translate-y-1 transition-all flex items-center justify-center gap-2">
                        <i data-lucide="user-plus" class="w-4 h-4"></i> Establish Client
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal: AP Invoice Processing -->
<div id="ap-invoice-modal" class="fixed inset-0 z-[110] hidden bg-slate-950/60 backdrop-blur-md flex items-center justify-center p-4">
    <div class="glass w-full max-w-4xl max-h-[90vh] overflow-y-auto rounded-[3rem] border border-white/10 shadow-2xl animate-scale-in">
        <div class="p-10">
            <header class="flex justify-between items-center mb-8">
                <div>
                    <h2 class="text-3xl font-bold uppercase tracking-tight text-slate-900 dark:text-white">Process <span class="gradient-text">AP Invoice</span></h2>
                    <p class="text-slate-600 dark:text-slate-400 text-sm mt-1 font-medium">Record supplier bill and calculate taxes.</p>
                </div>
                <button onclick="toggleModal('ap-invoice-modal')" class="p-3 hover:bg-black/5 dark:hover:bg-white/5 rounded-full transition-colors text-slate-900 dark:text-white">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </header>

            <form action="<?= BASE_URL ?>modules/ap_ar/ap_invoice.php" method="POST" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-slate-600 dark:text-slate-500 uppercase tracking-widest ml-1">Supplier / Vendor</label>
                        <select name="vendor_id" required class="w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-white/5 rounded-2xl px-5 py-4 text-slate-900 dark:text-white focus:ring-2 focus:ring-rose-500/50 outline-none">
                            <option value="">Select Vendor...</option>
                            <?php foreach ($vendors as $v): ?>
                                <option value="<?= $v['vendor_id'] ?>"><?= $v['name'] ?> (TIN: <?= $v['tin'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-slate-600 dark:text-slate-500 uppercase tracking-widest ml-1">Invoice Number</label>
                        <input type="text" name="invoice_no" required class="w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-white/5 rounded-2xl px-5 py-4 text-slate-900 dark:text-white focus:ring-2 focus:ring-rose-500/50 outline-none placeholder:text-slate-400" placeholder="e.g. INV-12345">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-slate-500 uppercase tracking-widest ml-1">Base Amount (Ex-VAT)</label>
                        <div class="relative">
                            <span class="absolute left-5 top-1/2 -translate-y-1/2 text-slate-500 font-bold"><?= CURRENCY ?></span>
                            <input type="number" step="0.01" name="amount" required class="w-full bg-slate-100 dark:bg-slate-900/50 border border-white/5 rounded-2xl pl-10 pr-5 py-4 focus:ring-2 focus:ring-rose-500/50 outline-none font-black text-rose-400" placeholder="0.00">
                        </div>
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-slate-500 uppercase tracking-widest ml-1">EWT Rate</label>
                        <select name="ewt_rate" class="w-full bg-slate-100 dark:bg-slate-900/50 border border-white/5 rounded-2xl px-5 py-4 focus:ring-2 focus:ring-rose-500/50 outline-none">
                            <option value="0">No EWT (0%)</option>
                            <option value="0.01">EWT 1% (Goods)</option>
                            <option value="0.02">EWT 2% (Services)</option>
                            <option value="0.05">EWT 5% (Rent/Professional)</option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-slate-500 uppercase tracking-widest ml-1">Due Date</label>
                        <input type="date" name="due_date" required class="w-full bg-slate-100 dark:bg-slate-900/50 border border-white/5 rounded-2xl px-5 py-4 focus:ring-2 focus:ring-rose-500/50 outline-none" value="<?= date('Y-m-d', strtotime('+30 days')) ?>">
                    </div>
                </div>

                <div class="flex justify-end gap-4 pt-4">
                    <button type="button" onclick="toggleModal('ap-invoice-modal')" class="px-8 py-4 glass rounded-2xl font-bold hover:bg-white/10 transition-all">Cancel</button>
                    <button type="submit" name="save_ap" class="px-12 py-4 bg-rose-500 text-white rounded-2xl font-black shadow-xl shadow-rose-500/20 flex items-center gap-2">
                        <i data-lucide="save" class="w-5 h-5"></i> Post Payable
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: AR Statement / Invoice Entry -->
<div id="ar-statement-modal" class="fixed inset-0 z-[110] hidden bg-slate-950/60 backdrop-blur-md flex items-center justify-center p-4">
    <div class="glass w-full max-w-4xl max-h-[90vh] overflow-y-auto rounded-[3rem] border border-white/10 shadow-2xl animate-scale-in">
        <div class="p-10">
            <header class="flex justify-between items-center mb-8">
                <div>
                    <h2 class="text-3xl font-bold uppercase tracking-tight text-slate-900 dark:text-white">Generate <span class="gradient-text">AR Statement</span></h2>
                    <p class="text-slate-600 dark:text-slate-400 text-sm mt-1 font-medium">Create new receivable or billing statement.</p>
                </div>
                <button onclick="toggleModal('ar-statement-modal')" class="p-3 hover:bg-black/5 dark:hover:bg-white/5 rounded-full transition-colors text-slate-900 dark:text-white">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </header>

            <form action="<?= BASE_URL ?>modules/ap_ar/ar_invoice.php" method="POST" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-slate-600 dark:text-slate-500 uppercase tracking-widest ml-1">Customer / Client</label>
                        <select name="customer_id" required class="w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-white/5 rounded-2xl px-5 py-4 text-slate-900 dark:text-white focus:ring-2 focus:ring-teal-500/50 outline-none">
                            <option value="">Select Customer...</option>
                            <?php foreach ($customers as $c): ?>
                                <option value="<?= $c['customer_id'] ?>"><?= $c['name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-slate-600 dark:text-slate-500 uppercase tracking-widest ml-1">Reference No.</label>
                        <input type="text" name="ref_no" required class="w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-white/5 rounded-2xl px-5 py-4 text-slate-900 dark:text-white focus:ring-2 focus:ring-teal-500/50 outline-none placeholder:text-slate-400" placeholder="e.g. OR-9981">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-slate-500 uppercase tracking-widest ml-1">Total Amount (VAT Inc)</label>
                        <div class="relative">
                            <span class="absolute left-5 top-1/2 -translate-y-1/2 text-slate-500 font-bold"><?= CURRENCY ?></span>
                            <input type="number" step="0.01" name="amount" required class="w-full bg-slate-100 dark:bg-slate-900/50 border border-white/5 rounded-2xl pl-10 pr-5 py-4 focus:ring-2 focus:ring-teal-500/50 outline-none font-black text-teal-400" placeholder="0.00">
                        </div>
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-slate-500 uppercase tracking-widest ml-1">Due Date</label>
                        <input type="date" name="due_date" required class="w-full bg-slate-100 dark:bg-slate-900/50 border border-white/5 rounded-2xl px-5 py-4 focus:ring-2 focus:ring-teal-500/50 outline-none" value="<?= date('Y-m-d', strtotime('+15 days')) ?>">
                    </div>
                </div>

                <div class="flex justify-end gap-4 pt-4">
                    <button type="button" onclick="toggleModal('ar-statement-modal')" class="px-8 py-4 glass rounded-2xl font-bold hover:bg-white/10 transition-all">Cancel</button>
                    <button type="submit" name="save_ar" class="px-12 py-4 bg-teal-500 text-slate-900 rounded-2xl font-black shadow-xl shadow-teal-500/20 flex items-center gap-2">
                        <i data-lucide="check-square" class="w-5 h-5"></i> Post Receivable
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
    lucide.createIcons();
</script>
<?php if ($_SESSION['role'] === 'Admin'): ?>
<script src="<?= BASE_URL ?>assets/js/admin_delete.js"></script>
<?php endif; ?>
</body>
</html>
