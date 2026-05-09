<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php';
check_login();

if (isset($_POST['void_id'])) {
    $voucher_id = $_POST['void_id'];
    $reason = $_POST['reason'];

    try {
        $pdo->beginTransaction();

        // 1. Get Voucher & GL Entry
        $stmt = $pdo->prepare("SELECT v.*, l.reference_no, l.gl_entry_id FROM payment_vouchers v LEFT JOIN disbursement_log l ON v.voucher_id = l.voucher_id WHERE v.voucher_id = ?");
        $stmt->execute([$voucher_id]);
        $v = $stmt->fetch();

        if ($v['status'] === 'Voided') die("Already voided.");

        // 2. Mark as Voided
        $stmt = $pdo->prepare("UPDATE payment_vouchers SET status = 'Voided' WHERE voucher_id = ?");
        $stmt->execute([$voucher_id]);
        
        $stmt = $pdo->prepare("UPDATE payment_requests SET status = 'Pending' WHERE request_id = ?");
        $stmt->execute([$v['request_id']]);

        // 3. Reverse GL Entry (Negative Journal)
        // In real accounting, we'd add reversal entries. For this prototype, we'll mark as voided in log.
        $stmt = $pdo->prepare("INSERT INTO audit_log (user_id, action, module, table_affected, record_id, remarks) VALUES (?, 'VOID_PAYMENT', 'Disbursement', 'payment_vouchers', ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $voucher_id, $reason]);

        $pdo->commit();
        header("Location: " . BASE_URL . "modules/disbursement/index.php?msg=" . urlencode('Payment voided successfully') . "&type=success");
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        header("Location: " . BASE_URL . "modules/disbursement/void_reissue.php?id=" . $voucher_id . "&msg=" . urlencode('Error: ' . $e->getMessage()) . "&type=error");
        exit();
    }
}

$id = $_GET['id'] ?? null;
include __DIR__ . '/../../includes/header_dashboard.php';
?>

<div class="flex">
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <main class="ml-72 flex-grow p-10 min-h-screen">
        <header class="flex justify-between items-center mb-10">
            <div>
                <h2 class="text-3xl font-bold">Void & <span class="gradient-text">Reissue</span></h2>
                <p class="text-slate-500 dark:text-slate-400 mt-1">Reverse payments and handle reissue workflows.</p>
            </div>
        </header>

        <div class="max-w-2xl">
            <div class="glass p-10 rounded-[2.5rem] border border-white/5 space-y-8">
                <div class="p-6 bg-rose-500/10 border border-rose-500/20 rounded-2xl flex gap-4">
                    <i data-lucide="alert-triangle" class="w-8 h-8 text-rose-500"></i>
                    <div>
                        <h4 class="font-bold text-rose-500">Warning</h4>
                        <p class="text-xs text-slate-500">Voiding a payment will reverse the status and require a new approval for reissue. This action is permanently logged in the audit trail.</p>
                    </div>
                </div>

                <form method="POST" class="space-y-6">
                    <input type="hidden" name="void_id" value="<?= $id ?>">
                    <div class="space-y-2">
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest ml-1">Reason for Voiding</label>
                        <textarea name="reason" required class="w-full bg-slate-100 dark:bg-slate-900/50 border border-white/5 rounded-2xl px-5 py-4 focus:ring-2 focus:ring-rose-500/50 outline-none" placeholder="Wrong payee, incorrect amount, etc..."></textarea>
                    </div>
                    <button type="submit" class="w-full py-4 bg-rose-500 hover:bg-rose-600 text-slate-900 font-black rounded-2xl transition-all shadow-lg shadow-rose-500/20">
                        Confirm Void Payment
                    </button>
                </form>
            </div>
        </div>
    </main>
</div>

<script>
    lucide.createIcons();
</script>
</body>
</html>
