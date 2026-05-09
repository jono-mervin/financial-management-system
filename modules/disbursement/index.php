<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php';
check_login();

$current_page = 'index.php';
$current_dir = 'disbursement';

// Get Summary Stats
$pending_requests = $pdo->query("SELECT COUNT(*) FROM payment_requests WHERE status = 'Pending'")->fetchColumn();
$total_disbursed = $pdo->query("SELECT SUM(amount) FROM payment_requests WHERE status = 'Paid'")->fetchColumn() ?: 0;
$pending_approvals = $pdo->query("SELECT COUNT(*) FROM payment_vouchers WHERE status = 'Pending'")->fetchColumn();

include __DIR__ . '/../../includes/header_dashboard.php';
?>

<div class="flex">
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <main class="ml-72 flex-grow p-10 min-h-screen">
        <header class="mb-10 animate-fade-in px-8 py-12 glass rounded-[2.5rem] border border-white/10 dark:border-white/5 relative overflow-hidden shadow-2xl">
            <div class="absolute inset-0 bg-gradient-to-r from-teal-500/10 via-transparent to-transparent pointer-events-none"></div>
            <div class="relative flex justify-between items-center">
                <h2 class="text-4xl font-black tracking-tight uppercase text-slate-900 dark:text-white">Disbursement <span class="gradient-text">Module</span></h2>
                <?php if (has_permission('disbursement', 'create')): ?>
                    <button onclick="toggleModal('payment-modal')" class="px-8 py-4 bg-teal-500 hover:bg-teal-600 text-slate-900 font-black rounded-2xl transition-all shadow-xl shadow-teal-500/20 flex items-center gap-2">
                        <i data-lucide="plus-circle" class="w-5 h-5"></i> New Request
                    </button>
                <?php endif; ?>
            </div>
        </header>

        <?php if (isset($_GET['request_success'])): ?>
            <div class="mb-8 p-4 bg-teal-500/10 border border-teal-500/20 text-teal-400 text-sm font-bold rounded-2xl flex items-center gap-3 animate-fade-in">
                <i data-lucide="check-circle" class="w-5 h-5"></i> Payment request submitted successfully.
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10 animate-fade-in" style="animation-delay: 0.1s;">
            <div class="glass p-6 rounded-3xl border-l-4 border-teal-500">
                <p class="text-slate-500 uppercase text-xs font-black tracking-widest mb-1">Total Disbursed</p>
                <h3 class="text-3xl font-black"><?= CURRENCY ?><?= number_format($total_disbursed, 2) ?></h3>
            </div>
            <div class="glass p-6 rounded-3xl border-l-4 border-indigo-500">
                <p class="text-slate-500 uppercase text-xs font-black tracking-widest mb-1">Pending Requests</p>
                <h3 class="text-3xl font-black"><?= $pending_requests ?></h3>
            </div>
            <div class="glass p-6 rounded-3xl border-l-4 border-amber-500">
                <p class="text-slate-500 uppercase text-xs font-black tracking-widest mb-1">Pending Approvals</p>
                <h3 class="text-3xl font-black"><?= $pending_approvals ?></h3>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 animate-fade-in" style="animation-delay: 0.2s;">
            <div class="glass p-8 rounded-[2.5rem]">
                <div class="flex justify-between items-center mb-6">
                    <h4 class="font-bold flex items-center gap-2"><i data-lucide="clock" class="w-4 h-4 text-indigo-400"></i> Recent Requests</h4>
                    <a href="#" class="text-xs text-slate-500 hover:text-white font-bold">View All</a>
                </div>
                <div class="space-y-4">
                    <?php
                    $recent = $pdo->query("SELECT r.*, u.name as requester FROM payment_requests r JOIN users u ON r.requested_by = u.user_id ORDER BY r.created_at DESC LIMIT 5")->fetchAll();
                    foreach ($recent as $item):
                    ?>
                        <div class="flex items-center justify-between p-4 bg-white/5 rounded-2xl border border-white/5">
                            <div>
                                <p class="text-sm font-bold"><?= $item['payee'] ?></p>
                                <p class="text-xs text-slate-500"><?= $item['purpose'] ?> • By <?= $item['requester'] ?></p>
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="text-right">
                                    <p class="text-sm font-black text-teal-400"><?= CURRENCY ?><?= number_format($item['amount'], 2) ?></p>
                                    <span class="px-2 py-0.5 bg-slate-800 text-[10px] font-black uppercase rounded text-slate-400">
                                        <?= $item['status'] ?>
                                    </span>
                                </div>
                                <?php if ($_SESSION['role'] === 'Admin'): ?>
                                <button onclick="adminDelete('payment_request', <?= $item['request_id'] ?>, 'Payment Request #<?= $item['request_id'] ?>')" class="p-2 text-rose-500 hover:text-rose-400 hover:bg-rose-500/10 rounded-lg transition-all" title="Delete">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($recent)): ?>
                        <p class="text-center py-10 text-slate-600 italic">No recent payment requests found.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="glass p-8 rounded-[2.5rem] flex flex-col">
                <h4 class="font-bold flex items-center gap-2 mb-6"><i data-lucide="shield-check" class="w-4 h-4 text-teal-400"></i> Approval Queue</h4>
                <div class="space-y-4 flex-grow">
                    <?php
                    $approvals = $pdo->query("SELECT v.*, r.payee, r.amount FROM payment_vouchers v JOIN payment_requests r ON v.request_id = r.request_id WHERE v.status = 'Pending' LIMIT 5")->fetchAll();
                    foreach ($approvals as $app):
                    ?>
                        <div class="flex items-center justify-between p-4 bg-white/5 rounded-2xl border border-white/5">
                            <div>
                                <p class="text-sm font-bold"><?= $app['payee'] ?></p>
                                <p class="text-xs text-slate-500"><?= $app['payment_mode'] ?> Payment Request</p>
                            </div>
                            <a href="<?= BASE_URL ?>modules/disbursement/approval_workflow.php?id=<?= $app['voucher_id'] ?>" class="px-4 py-2 bg-indigo-500/10 text-indigo-400 text-xs font-black uppercase rounded-lg hover:bg-indigo-500 hover:text-white transition-all">
                                Review
                            </a>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($approvals)): ?>
                        <p class="text-center py-10 text-slate-600 italic">Your approval queue is empty.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Modal: New Payment Request -->
<div id="payment-modal" class="fixed inset-0 z-[110] hidden bg-slate-950/60 backdrop-blur-md flex items-center justify-center p-4">
    <div class="glass w-full max-w-4xl max-h-[90vh] overflow-y-auto rounded-[3rem] border border-white/10 shadow-2xl animate-scale-in">
        <div class="p-10">
            <header class="flex justify-between items-center mb-8">
                <div>
                    <h2 class="text-3xl font-bold">New Payment <span class="gradient-text">Request</span></h2>
                    <p class="text-slate-500 text-sm mt-1">Submit an expenditure for review.</p>
                </div>
                <button onclick="toggleModal('payment-modal')" class="p-3 hover:bg-white/5 rounded-full transition-colors">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </header>

            <form action="<?= BASE_URL ?>modules/disbursement/payment_request.php" method="POST" class="space-y-8">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="space-y-3">
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest ml-1">Payee / Vendor</label>
                        <input type="text" name="payee" required class="w-full bg-slate-100 dark:bg-slate-900/50 border border-white/5 rounded-2xl px-5 py-4 focus:ring-2 focus:ring-teal-500/50 outline-none" placeholder="Who are we paying?">
                    </div>
                    <div class="space-y-3">
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest ml-1">Amount</label>
                        <div class="relative">
                            <span class="absolute left-5 top-1/2 -translate-y-1/2 text-slate-500 font-bold"><?= CURRENCY ?></span>
                            <input type="number" step="0.01" name="amount" required class="w-full bg-slate-100 dark:bg-slate-900/50 border border-white/5 rounded-2xl pl-10 pr-5 py-4 focus:ring-2 focus:ring-teal-500/50 outline-none font-black text-teal-400" placeholder="0.00">
                        </div>
                    </div>
                </div>

                <div class="space-y-3">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest ml-1">Payment Method</label>
                    <div class="grid grid-cols-3 gap-4">
                        <label class="cursor-pointer">
                            <input type="radio" name="payment_mode" value="Cash" class="hidden peer" checked>
                            <div class="p-4 bg-slate-100 dark:bg-slate-900/50 rounded-2xl border-2 border-transparent peer-checked:border-teal-500 peer-checked:bg-teal-500/10 transition-all text-center">
                                <i data-lucide="banknote" class="w-6 h-6 mx-auto mb-2 text-slate-400 peer-checked:text-teal-400"></i>
                                <span class="text-xs font-bold uppercase tracking-tighter">Cash</span>
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="payment_mode" value="Check" class="hidden peer">
                            <div class="p-4 bg-slate-100 dark:bg-slate-900/50 rounded-2xl border-2 border-transparent peer-checked:border-teal-500 peer-checked:bg-teal-500/10 transition-all text-center">
                                <i data-lucide="file-check" class="w-6 h-6 mx-auto mb-2 text-slate-400 peer-checked:text-teal-400"></i>
                                <span class="text-xs font-bold uppercase tracking-tighter">Check</span>
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="payment_mode" value="ACH" class="hidden peer">
                            <div class="p-4 bg-slate-100 dark:bg-slate-900/50 rounded-2xl border-2 border-transparent peer-checked:border-teal-500 peer-checked:bg-teal-500/10 transition-all text-center">
                                <i data-lucide="credit-card" class="w-6 h-6 mx-auto mb-2 text-slate-400 peer-checked:text-teal-400"></i>
                                <span class="text-xs font-bold uppercase tracking-tighter">ACH / EFT</span>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="space-y-3">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest ml-1">Purpose / Justification</label>
                    <textarea name="purpose" rows="3" required class="w-full bg-slate-100 dark:bg-slate-900/50 border border-white/5 rounded-2xl px-5 py-4 focus:ring-2 focus:ring-teal-500/50 outline-none" placeholder="Provide detail for the approver..."></textarea>
                </div>

                <div class="flex justify-end gap-4 pt-6">
                    <button type="button" onclick="toggleModal('payment-modal')" class="px-8 py-4 glass rounded-2xl font-bold hover:bg-white/10 transition-all">Cancel</button>
                    <button type="submit" class="px-12 py-4 bg-teal-500 text-slate-900 rounded-2xl font-black transition-all shadow-xl shadow-teal-500/20 flex items-center gap-3">
                        <i data-lucide="send" class="w-5 h-5"></i> Submit Request
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
