<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php';
check_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payee = $_POST['payee'];
    $amount = $_POST['amount'];
    $purpose = $_POST['purpose'];
    $payment_mode = $_POST['payment_mode'];

    try {
        $pdo->beginTransaction();

        // 1. Create Payment Request
        $stmt = $pdo->prepare("INSERT INTO payment_requests (payee, amount, purpose, status, requested_by) VALUES (?, ?, ?, 'Pending', ?)");
        $stmt->execute([$payee, $amount, $purpose, $_SESSION['user_id']]);
        $request_id = $pdo->lastInsertId();

        // 2. Create Voucher (Draft/Pending Approval)
        $stmt = $pdo->prepare("INSERT INTO payment_vouchers (request_id, amount, net_amount, payment_mode, status) VALUES (?, ?, ?, ?, 'Pending')");
        $stmt->execute([$request_id, $amount, $amount, $payment_mode]);
        $voucher_id = $pdo->lastInsertId();

        $pdo->commit();
        header("Location: " . BASE_URL . "modules/disbursement/index.php?msg=" . urlencode('Payment request submitted successfully') . "&type=success");
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        header("Location: " . BASE_URL . "modules/disbursement/payment_request.php?msg=" . urlencode('Error: ' . $e->getMessage()) . "&type=error");
        exit();
    }
}

include __DIR__ . '/../../includes/header_dashboard.php';
?>

<div class="flex">
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <main class="ml-72 flex-grow p-10 min-h-screen">
        <header class="flex justify-between items-center mb-10 animate-fade-in">
            <div>
                <h2 class="text-3xl font-bold">New Payment <span class="gradient-text">Request</span></h2>
                <p class="text-slate-500 dark:text-slate-400 mt-1">Submit a new expenditure request for approval.</p>
            </div>
            <div class="flex gap-4">
                <a href="<?= BASE_URL ?>modules/disbursement/index.php" class="px-5 py-2 glass rounded-xl hover:bg-white/10 transition-all flex items-center gap-2 text-sm font-bold">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i> Back
                </a>
            </div>
        </header>

        <form method="POST" class="glass p-10 rounded-[2.5rem] border border-white/5 space-y-8 shadow-2xl animate-fade-in max-w-4xl">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="space-y-4">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest ml-1">Payee / Vendor Name</label>
                    <input type="text" name="payee" required class="w-full bg-slate-100 dark:bg-slate-900/50 border border-white/5 rounded-2xl px-5 py-4 focus:ring-2 focus:ring-teal-500/50 outline-none" placeholder="e.g. Meralco, Supplier X">
                </div>
                <div class="space-y-4">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest ml-1">Total Amount</label>
                    <div class="relative">
                        <span class="absolute left-5 top-1/2 -translate-y-1/2 text-slate-500 font-bold"><?= CURRENCY ?></span>
                        <input type="number" step="0.01" name="amount" required class="w-full bg-slate-100 dark:bg-slate-900/50 border border-white/5 rounded-2xl pl-10 pr-5 py-4 focus:ring-2 focus:ring-teal-500/50 outline-none font-black text-teal-400" placeholder="0.00">
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest ml-1">Payment Mode</label>
                <div class="grid grid-cols-3 gap-4">
                    <label class="cursor-pointer">
                        <input type="radio" name="payment_mode" value="Cash" class="hidden peer" checked>
                        <div class="p-4 bg-slate-100 dark:bg-slate-900/50 rounded-2xl border-2 border-transparent peer-checked:border-teal-500 peer-checked:bg-teal-500/10 transition-all text-center">
                            <i data-lucide="banknote" class="w-6 h-6 mx-auto mb-2 text-slate-400 peer-checked:text-teal-400"></i>
                            <span class="text-xs font-bold">Cash</span>
                        </div>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="payment_mode" value="Check" class="hidden peer">
                        <div class="p-4 bg-slate-100 dark:bg-slate-900/50 rounded-2xl border-2 border-transparent peer-checked:border-teal-500 peer-checked:bg-teal-500/10 transition-all text-center">
                            <i data-lucide="file-text" class="w-6 h-6 mx-auto mb-2 text-slate-400 peer-checked:text-teal-400"></i>
                            <span class="text-xs font-bold">Check</span>
                        </div>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="payment_mode" value="ACH" class="hidden peer">
                        <div class="p-4 bg-slate-100 dark:bg-slate-900/50 rounded-2xl border-2 border-transparent peer-checked:border-teal-500 peer-checked:bg-teal-500/10 transition-all text-center">
                            <i data-lucide="zap" class="w-6 h-6 mx-auto mb-2 text-slate-400 peer-checked:text-teal-400"></i>
                            <span class="text-xs font-bold">ACH / EFT</span>
                        </div>
                    </label>
                </div>
            </div>

            <div class="space-y-4">
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest ml-1">Purpose / Justification</label>
                <textarea name="purpose" rows="3" required class="w-full bg-slate-100 dark:bg-slate-900/50 border border-white/5 rounded-2xl px-5 py-4 focus:ring-2 focus:ring-teal-500/50 outline-none" placeholder="Explain why this payment is required..."></textarea>
            </div>

            <div class="flex justify-end mt-10">
                <button type="submit" class="px-12 py-4 bg-teal-500 hover:bg-teal-600 text-slate-900 text-lg font-black rounded-2xl transition-all shadow-xl shadow-teal-500/20 flex items-center gap-3">
                    <i data-lucide="send" class="w-5 h-5"></i> Submit Request
                </button>
            </div>
        </form>
    </main>
</div>

<script>
    lucide.createIcons();
</script>
</body>
</html>
