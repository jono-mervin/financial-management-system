<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php';
check_login();

$or_id = $_GET['id'] ?? null;
if (!$or_id) {
    header("Location: " . BASE_URL . "modules/collection/index.php");
    exit();
}

$stmt = $pdo->prepare("SELECT o.*, c.name as customer_name, col.payment_mode, col.collected_at 
                      FROM official_receipts o 
                      JOIN customers c ON o.customer_id = c.customer_id 
                      JOIN collections col ON o.collection_id = col.collection_id 
                      WHERE o.or_id = ?");
$stmt->execute([$or_id]);
$or = $stmt->fetch();

if (!$or) die("Receipt not found.");

include __DIR__ . '/../../includes/header_dashboard.php';
?>

<div class="flex">
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <main class="ml-72 flex-grow p-10 min-h-screen">
        <header class="mb-10 animate-fade-in px-8 py-12 glass rounded-[2.5rem] border border-white/10 dark:border-white/5 relative overflow-hidden shadow-2xl">
            <div class="absolute inset-0 bg-gradient-to-r from-teal-500/10 via-transparent to-transparent pointer-events-none"></div>
            <div class="relative flex justify-between items-center">
                <h2 class="text-4xl font-black tracking-tight uppercase text-slate-900 dark:text-white">Official <span class="gradient-text">Receipt</span></h2>
                <div class="flex gap-4 print:hidden">
                    <button onclick="window.print()" class="px-8 py-4 bg-teal-500 hover:bg-teal-600 text-slate-900 font-black rounded-2xl transition-all shadow-xl shadow-teal-500/20 flex items-center gap-2">
                        <i data-lucide="printer" class="w-5 h-5"></i> Print Receipt
                    </button>
                    <a href="<?= BASE_URL ?>modules/collection/index.php" class="px-6 py-4 glass rounded-2xl hover:bg-white/10 transition-all flex items-center gap-2 text-sm font-black text-slate-900 dark:text-white border border-white/5">
                        <i data-lucide="arrow-left" class="w-4 h-4 text-teal-400"></i> Back
                    </a>
                </div>
            </div>
        </header>

        <div class="max-w-3xl mx-auto">
            <div class="glass p-12 rounded-[2.5rem] border border-white/5 shadow-2xl space-y-10 relative overflow-hidden print:shadow-none print:border-slate-200 print:p-8">
                <!-- Watermark -->
                <div class="absolute -right-20 -top-20 opacity-[0.03] rotate-12 pointer-events-none select-none">
                    <i data-lucide="shield-check" class="w-96 h-96"></i>
                </div>

                <div class="flex justify-between items-start border-b border-white/5 pb-8 print:border-slate-200">
                    <div>
                        <h3 class="text-2xl font-black gradient-text mb-2">SMS FINANCIAL</h3>
                        <p class="text-xs text-slate-500 uppercase tracking-widest leading-relaxed">Service Management System<br>Official Billing & Collections</p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs font-black text-rose-500 uppercase tracking-widest mb-1">Receipt Number</p>
                        <p class="text-2xl font-black text-slate-900 dark:text-white print:text-slate-900"><?= $or['or_number'] ?></p>
                        <p class="text-xs text-slate-500 font-bold mt-2"><?= date('F d, Y', strtotime($or['collected_at'])) ?></p>
                    </div>
                </div>

                <div class="space-y-8">
                    <div>
                        <p class="text-xs font-black uppercase tracking-widest text-slate-500 mb-1">Received From</p>
                        <p class="text-xl font-bold border-b border-white/5 pb-2 print:border-slate-200"><?= $or['customer_name'] ?></p>
                    </div>

                    <div class="grid grid-cols-2 gap-12">
                        <div>
                            <p class="text-xs font-black uppercase tracking-widest text-slate-500 mb-1">The Sum Of</p>
                            <p class="text-lg font-bold">Amount in Pesos Only</p>
                            <p class="text-[10px] text-slate-600 mt-1 italic">(Mock Words Calculation)</p>
                        </div>
                        <div class="space-y-4">
                            <p class="text-xs font-black uppercase tracking-widest text-slate-500 mb-1">Total Amount Received</p>
                            <p class="text-4xl font-black text-teal-400"><?= CURRENCY ?><?= number_format($or['amount'], 2) ?></p>
                            
                            <div class="mt-8 pt-6 border-t border-white/5 space-y-2">
                                <?php 
                                $vat_ex = $or['amount'] / (1 + VAT_RATE);
                                $vat_amount = $vat_ex * VAT_RATE;
                                ?>
                                <div class="flex justify-between text-xs font-bold uppercase text-slate-500">
                                    <span>VAT-Exclusive Amount</span>
                                    <span><?= CURRENCY ?><?= number_format($vat_ex, 2) ?></span>
                                </div>
                                <div class="flex justify-between text-xs font-bold uppercase text-slate-500">
                                    <span>VAT Amount (12%)</span>
                                    <span><?= CURRENCY ?><?= number_format($vat_amount, 2) ?></span>
                                </div>
                                <div class="flex justify-between text-xs font-black text-teal-400 mt-2">
                                    <span>TOTAL</span>
                                    <span><?= CURRENCY ?><?= number_format($or['amount'], 2) ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="p-6 bg-white/5 rounded-3xl border border-white/5 flex justify-between items-center print:bg-slate-50 print:border-slate-200">
                        <div>
                            <p class="text-xs font-black uppercase tracking-widest text-slate-500 mb-1">Payment Mode</p>
                            <p class="font-bold text-sm uppercase"><?= $or['payment_mode'] ?></p>
                        </div>
                        <div class="text-right">
                            <i data-lucide="check-circle-2" class="w-8 h-8 text-teal-500"></i>
                        </div>
                    </div>
                </div>

                <div class="pt-20 flex justify-between items-end">
                    <div class="w-48 border-t border-white/20 pt-4 text-center print:border-slate-400">
                        <p class="text-xs font-bold text-slate-500 uppercase">Authorized Signature</p>
                    </div>
                    <div class="text-right opacity-50">
                        <p class="text-[10px] uppercase font-black text-slate-600">Generated via SMS Tech Platform</p>
                        <p class="text-[10px] uppercase font-black text-slate-600">E-Receipt #<?= $or['or_id'] ?></p>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<style>
    @media print {
        /* Hide sidebar, page header, background, and print button */
        aside,
        header,
        .print\:hidden,
        body > div > main > header {
            display: none !important;
        }

        /* Remove sidebar margin from main */
        main {
            margin-left: 0 !important;
            padding: 0 !important;
        }

        /* Reset page background */
        body, html {
            background: white !important;
            font-size: 100% !important;
        }

        /* Receipt card resets */
        .max-w-3xl {
            max-width: 100% !important;
            margin: 0 !important;
        }

        .glass {
            background: white !important;
            backdrop-filter: none !important;
            border: 1px solid #e2e8f0 !important;
            box-shadow: none !important;
        }

        /* Ensure text is dark for printing */
        * {
            color: #0f172a !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* Keep accent colors */
        .text-teal-400, .gradient-text {
            color: #2dd4bf !important;
        }

        .text-rose-500 {
            color: #f43f5e !important;
        }
    }
</style>

<script>
    lucide.createIcons();
</script>
</body>
</html>
