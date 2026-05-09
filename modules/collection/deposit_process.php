<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php';
check_login();

// Create New Batch
if (isset($_POST['create_batch']) && !empty($_POST['collection_ids'])) {
    $total = 0;
    
    // Create batch record
    $stmt = $pdo->prepare("INSERT INTO deposit_batches (total_amount, deposit_date, prepared_by, status) VALUES (?, CURDATE(), ?, 'Pending')");
    $stmt->execute([0, $_SESSION['user_id']]);
    $batch_id = $pdo->lastInsertId();
    
    // Link collections and calculate total
    foreach ($_POST['collection_ids'] as $cid) {
        // Fetch amount
        $amt = $pdo->query("SELECT amount_paid FROM collections WHERE collection_id = " . intval($cid))->fetchColumn();
        $total += floatval($amt);
        // Link
        $pdo->query("UPDATE collections SET batch_id = $batch_id WHERE collection_id = " . intval($cid));
    }
    
    // Update batch total
    $pdo->query("UPDATE deposit_batches SET total_amount = $total WHERE batch_id = $batch_id");
    
    header("Location: deposit_process.php?msg=" . urlencode('Deposit batch created successfully') . "&type=success");
    exit();
}

// Confirm Deposit
if (isset($_POST['confirm_deposit']) && isset($_POST['batch_id'])) {
    $stmt = $pdo->prepare("UPDATE deposit_batches SET status = 'Deposited', bank_ref = ? WHERE batch_id = ?");
    $stmt->execute([$_POST['bank_ref'], $_POST['batch_id']]);
    header("Location: deposit_process.php?msg=" . urlencode('Deposit confirmed successfully') . "&type=success");
    exit();
}

// Summary of Undeposited Funds
$undeposited = $pdo->query("SELECT SUM(amount_paid) FROM collections c LEFT JOIN official_receipts o ON c.collection_id = o.collection_id WHERE c.payment_mode IN ('Cash', 'Check') AND c.batch_id IS NULL")->fetchColumn() ?: 0;

include __DIR__ . '/../../includes/header_dashboard.php';
?>

<div class="flex">
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <main class="ml-72 flex-grow p-10 min-h-screen">
        <header class="flex justify-between items-center mb-10">
            <div>
                <h2 class="text-3xl font-bold">Deposit <span class="gradient-text">Management</span></h2>
                <p class="text-slate-500 dark:text-slate-400 mt-1">Batch daily collections for bank transmittal.</p>
            </div>
        </header>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
            <div class="lg:col-span-2 space-y-6">
                <!-- Batch Preparation -->
                <div class="glass p-10 rounded-[2.5rem] border border-white/5">
                    <div class="flex justify-between items-start mb-10">
                        <div>
                            <p class="text-xs font-black uppercase tracking-widest text-slate-500 mb-1">Undeposited Funds</p>
                            <h3 class="text-4xl font-black text-teal-400"><?= CURRENCY ?><?= number_format($undeposited, 2) ?></h3>
                        </div>
                        <button onclick="toggleModal('batch-modal')" class="px-8 py-4 bg-teal-500 hover:bg-teal-600 text-slate-900 font-black rounded-2xl transition-all shadow-xl shadow-teal-500/20">
                            Create New Batch
                        </button>
                    </div>

                    <div class="space-y-4">
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-widest ml-1">Most Recent Collections</p>
                        <?php
                        $pending = $pdo->query("SELECT c.*, cust.name FROM collections c JOIN ar_invoices i ON c.invoice_id = i.ar_invoice_id JOIN customers cust ON i.customer_id = cust.customer_id WHERE c.payment_mode IN ('Cash', 'Check') AND c.batch_id IS NULL ORDER BY c.collected_at DESC LIMIT 5")->fetchAll();
                        if (empty($pending)): ?>
                            <p class="text-center py-6 text-slate-500 italic">No pending collections to batch.</p>
                        <?php else:
                            foreach ($pending as $p):
                        ?>
                            <div class="flex items-center justify-between p-4 bg-white/5 rounded-2xl border border-white/5 opacity-70">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 bg-teal-500/10 rounded-xl flex items-center justify-center">
                                        <i data-lucide="tag" class="w-4 h-4 text-teal-400"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold"><?= $p['name'] ?></p>
                                        <p class="text-xs text-slate-500"><?= $p['payment_mode'] ?> • <?= $p['or_number'] ?></p>
                                    </div>
                                </div>
                                <p class="text-sm font-black text-slate-400"><?= CURRENCY ?><?= number_format($p['amount_paid'], 2) ?></p>
                            </div>
                        <?php 
                            endforeach;
                        endif; 
                        ?>
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div class="glass p-8 rounded-3xl border border-white/5">
                    <h4 class="text-sm font-bold uppercase tracking-widest text-slate-400 mb-6">Recent Batches</h4>
                    <div class="space-y-4">
                        <?php
                        $batches = $pdo->query("SELECT * FROM deposit_batches ORDER BY batch_id DESC LIMIT 5")->fetchAll();
                        if (empty($batches)): ?>
                            <p class="text-sm text-slate-500">No batches created yet.</p>
                        <?php else:
                            foreach ($batches as $b):
                        ?>
                        <div class="p-4 bg-indigo-500/10 rounded-2xl border border-indigo-500/20">
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-xs font-black uppercase text-indigo-400">BATCH-<?= str_pad($b['batch_id'], 4, '0', STR_PAD_LEFT) ?></span>
                                <span class="px-2 py-0.5 bg-indigo-500 text-[10px] font-black text-white rounded uppercase"><?= $b['status'] ?></span>
                            </div>
                            <p class="text-xl font-black text-slate-900 dark:text-white"><?= CURRENCY ?><?= number_format($b['total_amount'], 2) ?></p>
                            <?php if ($b['status'] == 'Pending'): ?>
                                <button onclick="openConfirmModal(<?= $b['batch_id'] ?>)" class="text-xs text-indigo-300 hover:text-white mt-2 font-bold flex items-center gap-1 group">Confirm Deposit <i data-lucide="arrow-right" class="w-3 h-3 group-hover:translate-x-1 transition-transform"></i></button>
                            <?php else: ?>
                                <p class="text-[10px] text-slate-400 mt-1 uppercase">Bank Ref: <?= $b['bank_ref'] ?: 'N/A' ?></p>
                            <?php endif; ?>
                        </div>
                        <?php 
                            endforeach;
                        endif; 
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Modal: Create Batch -->
<div id="batch-modal" class="fixed inset-0 z-[110] hidden bg-slate-950/60 backdrop-blur-md flex items-center justify-center p-4">
    <div class="glass w-full max-w-2xl max-h-[90vh] flex flex-col rounded-[3rem] border border-white/10 shadow-2xl animate-scale-in">
        <div class="p-10 border-b border-white/10 shrink-0 flex justify-between items-center">
            <div>
                <h2 class="text-3xl font-bold">New <span class="gradient-text">Batch</span></h2>
                <p class="text-slate-500 text-sm mt-1">Select collections to include in deposit.</p>
            </div>
            <button onclick="toggleModal('batch-modal')" class="p-3 hover:bg-white/5 rounded-full transition-colors">
                <i data-lucide="x" class="w-6 h-6"></i>
            </button>
        </div>
        
        <form action="<?= BASE_URL ?>modules/collection/deposit_process.php" method="POST" class="flex flex-col overflow-hidden">
            <input type="hidden" name="create_batch" value="1">
            <div class="p-10 overflow-y-auto space-y-4 max-h-[50vh]">
                <?php
                if (empty($pending)): ?>
                    <p class="text-center py-10 text-slate-500 italic">No pending unbatched collections available.</p>
                <?php else:
                    foreach ($pending as $p):
                ?>
                    <label class="flex items-center justify-between p-4 bg-white/5 hover:bg-white/10 rounded-2xl border border-white/5 cursor-pointer transition-colors">
                        <div class="flex items-center gap-4">
                            <input type="checkbox" name="collection_ids[]" value="<?= $p['collection_id'] ?>" class="w-5 h-5 rounded-md border-white/20 bg-slate-800 text-teal-500 focus:ring-teal-500/50 focus:ring-offset-slate-900">
                            <div>
                                <p class="text-sm font-bold text-slate-900 dark:text-white"><?= $p['name'] ?></p>
                                <p class="text-[10px] text-slate-400 uppercase tracking-widest"><?= $p['payment_mode'] ?> • OR-<?= str_pad($p['or_number'], 6, '0', STR_PAD_LEFT) ?></p>
                            </div>
                        </div>
                        <p class="text-sm font-black text-teal-400"><?= CURRENCY ?><?= number_format($p['amount_paid'], 2) ?></p>
                    </label>
                <?php 
                    endforeach;
                endif; 
                ?>
            </div>
            
            <div class="p-10 border-t border-white/10 shrink-0 flex justify-end gap-4 bg-slate-900/50">
                <button type="button" onclick="toggleModal('batch-modal')" class="px-8 py-4 glass rounded-2xl font-bold hover:bg-white/10 transition-all">Cancel</button>
                <button type="submit" <?= empty($pending) ? 'disabled' : '' ?> class="px-12 py-4 bg-teal-500 hover:bg-teal-600 disabled:opacity-50 disabled:cursor-not-allowed text-slate-900 rounded-2xl font-black transition-all shadow-xl shadow-teal-500/20">
                    Group to Batch
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Confirm Deposit -->
<div id="confirm-modal" class="fixed inset-0 z-[110] hidden bg-slate-950/60 backdrop-blur-md flex items-center justify-center p-4">
    <div class="glass w-full max-w-sm overflow-hidden rounded-[3rem] border border-white/10 shadow-2xl animate-scale-in">
        <div class="p-8 text-center">
            <div class="w-16 h-16 bg-teal-500/10 rounded-full flex items-center justify-center mx-auto mb-6 shadow-xl shadow-teal-500/20">
                <i data-lucide="building-2" class="w-8 h-8 text-teal-400"></i>
            </div>
            <h2 class="text-xl font-black uppercase tracking-tight mb-2">Confirm <span class="gradient-text">Deposit</span></h2>
            <p class="text-slate-500 text-xs mb-8 font-medium">Enter the bank reference number to finalize this deposit transmittal.</p>
            
            <form action="<?= BASE_URL ?>modules/collection/deposit_process.php" method="POST" class="flex flex-col gap-4">
                <input type="hidden" name="confirm_deposit" value="1">
                <input type="hidden" name="batch_id" id="confirm-batch-id" value="">
                
                <input type="text" name="bank_ref" required class="w-full bg-slate-900/50 border border-white/10 rounded-xl px-5 py-4 focus:ring-2 focus:ring-teal-500/50 outline-none text-sm text-center font-mono" placeholder="Bank Ref No.">
                
                <button type="submit" class="w-full py-4 bg-teal-500 hover:bg-teal-600 text-slate-900 rounded-xl font-black transition-all shadow-xl shadow-teal-500/20 mt-2">
                    Submit Deposit
                </button>
                <button type="button" onclick="toggleModal('confirm-modal')" class="w-full py-4 text-slate-500 hover:text-white rounded-xl font-bold transition-all text-sm">
                    Cancel
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    function toggleModal(id) {
        document.getElementById(id).classList.toggle('hidden');
    }
    
    function openConfirmModal(id) {
        document.getElementById('confirm-batch-id').value = id;
        toggleModal('confirm-modal');
    }

    lucide.createIcons();
</script>
</body>
</html>
