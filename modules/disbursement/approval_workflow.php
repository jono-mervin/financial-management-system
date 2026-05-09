<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/auth.php';
check_login();

if (!has_permission('disbursement', 'approve')) {
    header("Location: " . BASE_URL . "index.php?error=unauthorized");
    exit();
}

// Handle Approval/Rejection
if (isset($_POST['action'])) {
    $voucher_id = $_POST['voucher_id'];
    $action = $_POST['action']; // Approved or Rejected
    $remarks = $_POST['remarks'];

    try {
        $pdo->beginTransaction();

        // 1. Update Voucher Status
        $stmt = $pdo->prepare("UPDATE payment_vouchers SET status = ? WHERE voucher_id = ?");
        $stmt->execute([$action, $voucher_id]);

        // 2. Log Approval
        $stmt = $pdo->prepare("INSERT INTO payment_approvals (voucher_id, approver_id, level, action, remarks) VALUES (?, ?, 1, ?, ?)");
        $stmt->execute([$voucher_id, $_SESSION['user_id'], $action === 'Approved' ? 'Approve' : 'Reject', $remarks]);

        // 3. Update Request Status if Rejected or Approved (simplified to 1 level for now)
        $req_status = ($action === 'Approved') ? 'Approved' : 'Rejected';
        $stmt = $pdo->prepare("UPDATE payment_requests SET status = ? WHERE request_id = (SELECT request_id FROM payment_vouchers WHERE voucher_id = ?)");
        $stmt->execute([$req_status, $voucher_id]);

        $pdo->commit();
        header("Location: " . BASE_URL . "modules/disbursement/index.php?msg=" . urlencode("Voucher " . strtolower($action) . " successfully") . "&type=success");
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        header("Location: " . BASE_URL . "modules/disbursement/approval_workflow.php?id=" . $voucher_id . "&msg=" . urlencode('Error: ' . $e->getMessage()) . "&type=error");
        exit();
    }
}

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: " . BASE_URL . "modules/disbursement/index.php");
    exit();
}

$voucher = $pdo->prepare("SELECT v.*, r.payee, r.amount as req_amount, r.purpose, u.name as requester 
                          FROM payment_vouchers v 
                          JOIN payment_requests r ON v.request_id = r.request_id 
                          JOIN users u ON r.requested_by = u.user_id 
                          WHERE v.voucher_id = ?");
$voucher->execute([$id]);
$data = $voucher->fetch();

if (!$data) {
    header("Location: " . BASE_URL . "modules/disbursement/index.php?msg=" . urlencode("Voucher not found.") . "&type=error");
    exit();
}

include __DIR__ . '/../../includes/header_dashboard.php';
?>

<div class="flex">
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <main class="ml-72 flex-grow p-10 min-h-screen">
        <header class="flex justify-between items-center mb-10">
            <div>
                <h2 class="text-3xl font-bold">Review <span class="gradient-text">Voucher</span></h2>
                <p class="text-slate-500 dark:text-slate-400 mt-1">Maker-checker approval for outgoing funds.</p>
            </div>
        </header>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
            <div class="lg:col-span-2 space-y-8">
                <div class="glass p-10 rounded-[2.5rem] border border-white/5 shadow-2xl">
                    <div class="flex justify-between items-start mb-10">
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-500 mb-1">Payee</p>
                            <h3 class="text-2xl font-bold"><?= $data['payee'] ?></h3>
                        </div>
                        <div class="text-right">
                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-500 mb-1">Total Amount</p>
                            <h3 class="text-3xl font-black text-teal-400"><?= CURRENCY ?><?= number_format($data['amount'], 2) ?></h3>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-8 mb-10">
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-500 mb-1">Payment Mode</p>
                            <p class="font-bold flex items-center gap-2">
                                <i data-lucide="credit-card" class="w-4 h-4 text-indigo-400"></i> <?= $data['payment_mode'] ?>
                            </p>
                        </div>
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-500 mb-1">Requested By</p>
                            <p class="font-bold"><?= $data['requester'] ?></p>
                        </div>
                    </div>

                    <div>
                        <p class="text-[10px] font-black uppercase tracking-widest text-slate-500 mb-1">Justification</p>
                        <p class="text-slate-400 leading-relaxed italic">"<?= $data['purpose'] ?>"</p>
                    </div>
                </div>

                <!-- Approval Form -->
                <form method="POST" class="glass p-10 rounded-[2.5rem] border border-white/5">
                    <input type="hidden" name="voucher_id" value="<?= $id ?>">
                    <div class="space-y-6">
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest ml-1">Approver Remarks</label>
                        <textarea name="remarks" rows="2" class="w-full bg-slate-100 dark:bg-slate-900/50 border border-white/5 rounded-2xl px-5 py-4 focus:ring-2 focus:ring-teal-500/50 outline-none" placeholder="Optional notes..."></textarea>
                        
                        <div class="flex gap-4">
                            <button type="submit" name="action" value="Approved" class="flex-grow py-4 bg-teal-500 hover:bg-teal-600 text-slate-900 font-black rounded-2xl transition-all shadow-lg shadow-teal-500/20">
                                Approve Payment
                            </button>
                            <button type="submit" name="action" value="Rejected" class="flex-grow py-4 bg-rose-500/10 hover:bg-rose-500 text-rose-500 hover:text-white font-black rounded-2xl transition-all border border-rose-500/20">
                                Reject Request
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="space-y-6">
                <div class="glass p-6 rounded-3xl">
                    <h4 class="text-sm font-bold uppercase tracking-widest text-slate-500 mb-4">Audit Trail</h4>
                    <div class="space-y-4">
                        <div class="flex gap-3">
                            <div class="w-6 h-6 rounded-full bg-teal-500/10 flex items-center justify-center mt-1">
                                <i data-lucide="check" class="w-3 h-3 text-teal-400"></i>
                            </div>
                            <div>
                                <p class="text-xs font-bold">Request Created</p>
                                <p class="text-[10px] text-slate-500">By Admin • 2m ago</p>
                            </div>
                        </div>
                        <div class="flex gap-3">
                            <div class="w-6 h-6 rounded-full bg-slate-500/10 flex items-center justify-center mt-1">
                                <i data-lucide="clock-3" class="w-3 h-3 text-slate-500"></i>
                            </div>
                            <div>
                                <p class="text-xs font-bold">Pending Approval</p>
                                <p class="text-[10px] text-slate-500">Level 1: Finance Manager</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
    lucide.createIcons();
</script>
</body>
</html>
