<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php';
check_login();

// BIR Compliant Tax Computation Logic
function calculate_tax($amount_vatin, $type = 'Services') {
    // 1. Get VAT-Exclusive Base
    $vat_ex = $amount_vatin / (1 + VAT_RATE);
    $vat_amount = $vat_ex * VAT_RATE;

    // 2. Apply EWT on VAT-Exclusive Base
    $rate = EWT_SERVICES;
    if ($type === 'Goods') $rate = EWT_GOODS;
    if ($type === 'Rent') $rate = EWT_RENT;
    if ($type === 'Professional') $rate = EWT_PROFESSIONAL;

    $ewt_amount = $vat_ex * $rate;
    
    return [
        'base_amount' => $vat_ex,
        'vat_amount' => $vat_amount,
        'tax_rate' => $rate * 100,
        'tax_amount' => $ewt_amount,
        'net_amount' => $amount_vatin - $ewt_amount
    ];
}

$voucher_id = $_GET['id'] ?? null;
if ($voucher_id) {
    $stmt = $pdo->prepare("SELECT v.*, r.payee FROM payment_vouchers v JOIN payment_requests r ON v.request_id = r.request_id WHERE v.voucher_id = ?");
    $stmt->execute([$voucher_id]);
    $v = $stmt->fetch();
} else {
    header("Location: " . BASE_URL . "modules/disbursement/index.php");
    exit();
}

include __DIR__ . '/../../includes/header_dashboard.php';
?>

<div class="flex">
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <main class="ml-72 flex-grow p-10 min-h-screen">
        <header class="mb-10 animate-fade-in px-8 py-12 glass rounded-[2.5rem] border border-white/10 dark:border-white/5 relative overflow-hidden shadow-2xl">
            <div class="absolute inset-0 bg-gradient-to-r from-rose-500/10 via-transparent to-transparent pointer-events-none"></div>
            <div class="relative flex justify-between items-center">
                <h2 class="text-4xl font-black tracking-tight uppercase text-slate-900 dark:text-white">Tax <span class="gradient-text">Computation</span></h2>
                <div class="flex gap-4 print:hidden">
                    <button onclick="window.print()" class="px-8 py-4 glass rounded-2xl hover:bg-white/10 transition-all flex items-center gap-2 text-sm font-black text-slate-900 dark:text-white border border-white/5">
                        <i data-lucide="printer" class="w-5 h-5 text-rose-500"></i> Print Form
                    </button>
                    <a href="index.php" class="px-6 py-4 glass rounded-2xl hover:bg-white/10 transition-all flex items-center gap-2 text-sm font-black text-slate-900 dark:text-white border border-white/5">
                        <i data-lucide="arrow-left" class="w-4 h-4 text-rose-500"></i> Back
                    </a>
                </div>
            </div>
        </header>

        <div class="max-w-4xl mx-auto">
            <div class="glass p-12 rounded-[2.5rem] border border-white/5 shadow-2xl space-y-10 relative overflow-hidden print:shadow-none print:border-slate-200 print:p-8">
                <div class="flex justify-between items-start border-b border-white/5 pb-8 print:border-slate-200">
                    <div>
                        <h3 class="text-lg font-black uppercase tracking-widest text-teal-400 mb-2">Internal Tax Advisory</h3>
                        <p class="text-sm text-slate-500">Certificate of Creditable Tax Withheld at Source</p>
                    </div>
                    <div class="text-right">
                        <p class="text-[10px] font-bold text-slate-500 uppercase">Voucher ID</p>
                        <p class="text-xl font-black">#<?= str_pad($v['voucher_id'], 6, '0', STR_PAD_LEFT) ?></p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-12">
                    <div class="space-y-6">
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-500 mb-1">Payee / Payor</p>
                            <p class="text-lg font-bold"><?= $v['payee'] ?></p>
                            <p class="text-xs text-slate-500 mt-1 italic">Type: Professional / Individual</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-500 mb-1">Gross Amount</p>
                            <p class="text-2xl font-black"><?= CURRENCY ?><?= number_format($v['amount'], 2) ?></p>
                        </div>
                    </div>

                    <div class="space-y-6 bg-white/5 p-8 rounded-3xl border border-white/5 print:bg-slate-50 print:border-slate-200">
                        <?php $tax_data = calculate_tax($v['amount'], 'Professional'); ?>
                        <div class="flex justify-between items-center">
                            <span class="text-xs font-bold text-slate-500 uppercase">Input VAT (12%)</span>
                            <span class="text-sm font-black"><?= CURRENCY ?><?= number_format($tax_data['vat_amount'], 2) ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-xs font-bold text-slate-500 uppercase">EWT Rate (<?= $tax_data['tax_rate'] ?>%)</span>
                            <span class="text-sm font-black text-rose-500">(<?= CURRENCY ?><?= number_format($tax_data['tax_amount'], 2) ?>)</span>
                        </div>
                        <div class="h-[1px] bg-white/10 my-4 print:bg-slate-200"></div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-black uppercase tracking-widest text-teal-400">Net Payable</span>
                            <span class="text-2xl font-black text-teal-400"><?= CURRENCY ?><?= number_format($tax_data['net_amount'], 2) ?></span>
                        </div>
                    </div>
                </div>

                <div class="pt-10 border-t border-white/5 print:border-slate-200 print:mt-10">
                    <p class="text-[10px] text-slate-500 text-center uppercase tracking-[0.2em]">Generated for compliance purposes by SMS Financial System</p>
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
