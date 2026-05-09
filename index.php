<?php
require_once 'includes/db.php';
require_once 'modules/dashboard/stats.php';
require_once 'includes/header_dashboard.php';

$stats = get_dashboard_stats();
$module_summary = get_module_summary();
$cashflow = get_cashflow_data();
$performance = get_revenue_performance();
?>

<div class="flex">
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <main class="ml-72 flex-grow p-10 min-h-screen">
        <header class="mb-10 animate-fade-in px-8 py-12 glass rounded-[2.5rem] border border-white/10 dark:border-white/5 relative overflow-hidden shadow-2xl">
            <div class="absolute inset-0 bg-gradient-to-r from-indigo-500/10 via-transparent to-transparent pointer-events-none"></div>
            <div class="relative flex justify-between items-center">
                <h2 class="text-4xl font-black tracking-tight uppercase text-slate-900 dark:text-white">System <span class="gradient-text">Overview</span></h2>
                <div class="flex gap-3">
                    <span class="px-4 py-2 bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 text-xs font-black uppercase rounded-xl border border-indigo-500/20 tracking-widest">Financial Hub</span>
                </div>
            </div>
        </header>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
            <div class="glass p-6 rounded-3xl border-l-4 border-teal-500 hover:scale-[1.02] transition-transform cursor-pointer shadow-sm">
                <p class="text-slate-500 dark:text-slate-400 text-xs mb-1 uppercase tracking-widest font-bold">Total Revenue</p>
                <h3 class="text-3xl font-black"><?= CURRENCY ?><?= number_format($stats['total_revenue'], 2) ?></h3>
                <p class="text-teal-500 text-xs mt-2 font-bold bg-teal-500/10 inline-block px-3 py-1 rounded-full">Historical Collections</p>
            </div>
            <div class="glass p-6 rounded-3xl border-l-4 border-rose-500 hover:scale-[1.02] transition-transform cursor-pointer shadow-sm">
                <p class="text-slate-500 dark:text-slate-400 text-xs mb-1 uppercase tracking-widest font-bold">Expenditures</p>
                <h3 class="text-3xl font-black"><?= CURRENCY ?><?= number_format($stats['expenditures'], 2) ?></h3>
                <p class="text-rose-500 text-xs mt-2 font-bold bg-rose-500/10 inline-block px-3 py-1 rounded-full">Total Outflow</p>
            </div>
            <div class="glass p-6 rounded-3xl border-l-4 border-indigo-500 hover:scale-[1.02] transition-transform cursor-pointer shadow-sm">
                <p class="text-slate-500 dark:text-slate-400 text-xs mb-1 uppercase tracking-widest font-bold">Active Budget</p>
                <h3 class="text-3xl font-black"><?= CURRENCY ?><?= number_format($stats['active_budget'], 2) ?></h3>
                <p class="text-indigo-500 text-xs mt-2 font-bold bg-indigo-500/10 inline-block px-3 py-1 rounded-full"><?= $stats['budget_utilization'] ?>% Utilization</p>
            </div>
            <div class="glass p-6 rounded-3xl border-l-4 border-amber-500 hover:scale-[1.02] transition-transform cursor-pointer shadow-sm">
                <p class="text-slate-500 dark:text-slate-400 text-xs mb-1 uppercase tracking-widest font-bold">Pending Approvals</p>
                <h3 class="text-3xl font-black"><?= $stats['pending_approvals'] ?></h3>
                <p class="text-amber-500 text-xs mt-2 font-bold bg-amber-500/10 inline-block px-3 py-1 rounded-full">Action Required</p>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Cash Flow Projection -->
            <div class="glass p-8 rounded-[2rem] min-h-[400px] flex flex-col border border-white/10 shadow-xl">
                 <p class="text-slate-500 font-black mb-6 flex items-center gap-2 uppercase tracking-widest text-xs">
                    <i data-lucide="bar-chart-3" class="w-5 h-5 text-indigo-400"></i> Cash Flow Trend (Last 12 Months)
                 </p>
                 <div class="flex-grow">
                    <canvas id="cashflowChart"></canvas>
                 </div>
            </div>

            <!-- Revenue Target -->
            <div class="glass p-8 rounded-[2rem] min-h-[400px] flex flex-col border border-white/10 shadow-xl relative overflow-hidden">
                <p class="text-slate-500 font-black mb-6 flex items-center gap-2 uppercase tracking-widest text-xs">
                    <i data-lucide="target" class="w-5 h-5 text-teal-400"></i> Monthly Revenue Performance
                </p>
                <div class="flex-grow flex items-center justify-center relative">
                    <div style="width: 250px; height: 250px;">
                        <canvas id="revenuePerformanceChart"></canvas>
                    </div>
                    <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                        <span class="text-4xl font-black dark:text-white"><?= $performance['percentage'] ?>%</span>
                        <span class="text-[10px] text-slate-500 uppercase tracking-widest font-bold">Of Target</span>
                    </div>
                </div>
                <div class="mt-4 flex justify-between items-center text-xs font-bold px-4 py-3 bg-white/5 rounded-2xl border border-white/5">
                    <div class="flex flex-col">
                        <span class="text-slate-500 uppercase tracking-tighter">Current</span>
                        <span class="text-teal-400"><?= CURRENCY ?><?= number_format($performance['current'], 0) ?></span>
                    </div>
                    <div class="h-8 w-px bg-white/10"></div>
                    <div class="flex flex-col text-right">
                        <span class="text-slate-500 uppercase tracking-tighter">Target</span>
                        <span class="text-indigo-400"><?= CURRENCY ?><?= number_format($performance['target'], 0) ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Module Quick Links -->
        <div class="mt-10 glass p-8 rounded-[2.5rem] shadow-xl">
            <h4 class="text-xl font-bold mb-8 flex items-center gap-2">
                <i data-lucide="layers" class="w-6 h-6 text-teal-400"></i> Module Summaries
            </h4>
            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-6">
                <!-- GL -->
                <a href="modules/gl/index.php" class="p-6 rounded-[2rem] glass border border-transparent hover:border-teal-500 transition-all group scale-100 hover:scale-[1.03]">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-12 h-12 bg-teal-500/10 text-teal-400 rounded-2xl flex items-center justify-center group-hover:bg-teal-500 group-hover:text-slate-900 transition-colors shadow-sm">
                            <i data-lucide="book-open" class="w-6 h-6"></i>
                        </div>
                        <span class="font-bold">Gen Ledger</span>
                    </div>
                    <div class="space-y-2">
                        <div class="flex justify-between items-center">
                            <p class="text-xs text-slate-500 uppercase font-black tracking-tighter">Entries</p>
                            <span class="text-sm font-black"><?= $module_summary['gl']['total_entries'] ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <p class="text-xs text-slate-500 uppercase font-black tracking-tighter">Periods</p>
                            <span class="text-sm font-black text-teal-400"><?= $module_summary['gl']['open_periods'] ?> Open</span>
                        </div>
                    </div>
                </a>
                
                <!-- Disbursement -->
                <a href="modules/disbursement/index.php" class="p-6 rounded-[2rem] glass border border-transparent hover:border-indigo-500 transition-all group scale-100 hover:scale-[1.03]">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-12 h-12 bg-indigo-500/10 text-indigo-400 rounded-2xl flex items-center justify-center group-hover:bg-indigo-500 group-hover:text-slate-900 transition-colors shadow-sm">
                            <i data-lucide="send" class="w-6 h-6"></i>
                        </div>
                        <span class="font-bold">Disbursement</span>
                    </div>
                    <div class="space-y-2">
                        <div class="flex justify-between items-center">
                            <p class="text-xs text-slate-500 uppercase font-black tracking-tighter">Requests</p>
                            <span class="text-sm font-black"><?= $module_summary['disbursement']['total_requests'] ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <p class="text-xs text-slate-500 uppercase font-black tracking-tighter">Paid</p>
                            <span class="text-sm font-black text-indigo-400"><?= CURRENCY ?><?= number_format($module_summary['disbursement']['paid_amount'] / 1000, 1) ?>K</span>
                        </div>
                    </div>
                </a>

                <!-- Collection -->
                <a href="modules/collection/index.php" class="p-6 rounded-[2rem] glass border border-transparent hover:border-rose-500 transition-all group scale-100 hover:scale-[1.03]">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-12 h-12 bg-rose-500/10 text-rose-400 rounded-2xl flex items-center justify-center group-hover:bg-rose-500 group-hover:text-slate-900 transition-colors shadow-sm">
                            <i data-lucide="arrow-down-to-line" class="w-6 h-6"></i>
                        </div>
                        <span class="font-bold">Collection</span>
                    </div>
                    <div class="space-y-2">
                        <div class="flex justify-between items-center">
                            <p class="text-xs text-slate-500 uppercase font-black tracking-tighter">Collections</p>
                            <span class="text-sm font-black"><?= $module_summary['collection']['total_collections'] ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <p class="text-xs text-slate-500 uppercase font-black tracking-tighter">Unpaid</p>
                            <span class="text-sm font-black text-rose-400"><?= $module_summary['collection']['unpaid_invoices'] ?> Inv</span>
                        </div>
                    </div>
                </a>

                <!-- Budget -->
                <a href="modules/budget/index.php" class="p-6 rounded-[2rem] glass border border-transparent hover:border-amber-500 transition-all group scale-100 hover:scale-[1.03]">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-12 h-12 bg-amber-500/10 text-amber-400 rounded-2xl flex items-center justify-center group-hover:bg-amber-500 group-hover:text-slate-900 transition-colors shadow-sm">
                            <i data-lucide="pie-chart" class="w-6 h-6"></i>
                        </div>
                        <span class="font-bold">Budget Mgmt</span>
                    </div>
                    <div class="space-y-2">
                        <div class="flex justify-between items-center">
                            <p class="text-xs text-slate-500 uppercase font-black tracking-tighter">Approved</p>
                            <span class="text-sm font-black"><?= $module_summary['budget']['approved_items'] ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <p class="text-xs text-slate-500 uppercase font-black tracking-tighter">Allotment</p>
                            <span class="text-sm font-black text-amber-400"><?= CURRENCY ?><?= number_format($module_summary['budget']['total_allotments'] / 1000, 1) ?>K</span>
                        </div>
                    </div>
                </a>

                <!-- AP/AR -->
                <a href="modules/ap_ar/index.php" class="p-6 rounded-[2rem] glass border border-transparent hover:border-slate-500 transition-all group scale-100 hover:scale-[1.03]">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-12 h-12 bg-slate-400/10 text-slate-400 rounded-2xl flex items-center justify-center group-hover:bg-slate-400 group-hover:text-slate-900 transition-colors shadow-sm">
                            <i data-lucide="users" class="w-6 h-6"></i>
                        </div>
                        <span class="font-bold">AP / AR</span>
                    </div>
                    <div class="space-y-2">
                        <div class="flex justify-between items-center">
                            <p class="text-xs text-slate-500 uppercase font-black tracking-tighter">Vendors</p>
                            <span class="text-sm font-black"><?= $module_summary['ap_ar']['vendor_count'] ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <p class="text-xs text-slate-500 uppercase font-black tracking-tighter">Customers</p>
                            <span class="text-sm font-black"><?= $module_summary['ap_ar']['customer_count'] ?></span>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </main>
</div>

<script>
    // Initialize Lucide Icons
    lucide.createIcons();

    // Chart Configuration Helper
    const chartSettings = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: { color: 'rgba(255, 255, 255, 0.05)' },
                ticks: { color: '#94a3b8', font: { size: 10, weight: 'bold' } }
            },
            x: {
                grid: { display: false },
                ticks: { color: '#94a3b8', font: { size: 10, weight: 'bold' } }
            }
        }
    };

    // 1. Cash Flow Trend Chart
    new Chart(document.getElementById('cashflowChart').getContext('2d'), {
        type: 'line',
        data: {
            labels: <?= json_encode($cashflow['labels']) ?>,
            datasets: [
                {
                    label: 'Revenue',
                    data: <?= json_encode($cashflow['revenue']) ?>,
                    borderColor: '#2dd4bf',
                    backgroundColor: 'rgba(45, 212, 191, 0.1)',
                    borderWidth: 4,
                    tension: 0.4,
                    fill: true,
                    pointRadius: 4,
                    pointBackgroundColor: '#2dd4bf'
                },
                {
                    label: 'Expenditure',
                    data: <?= json_encode($cashflow['expenditure']) ?>,
                    borderColor: '#818cf8',
                    backgroundColor: 'rgba(129, 140, 248, 0.1)',
                    borderWidth: 4,
                    tension: 0.4,
                    fill: true,
                    pointRadius: 4,
                    pointBackgroundColor: '#818cf8'
                }
            ]
        },
        options: chartSettings
    });

    // 2. Revenue Performance Chart (Donut)
    new Chart(document.getElementById('revenuePerformanceChart').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: ['Collected', 'Remaining'],
            datasets: [{
                data: [<?= $performance['percentage'] ?>, <?= 100 - $performance['percentage'] ?>],
                backgroundColor: ['#2dd4bf', 'rgba(255,255,255,0.05)'],
                borderWidth: 0,
                hoverOffset: 4,
                borderRadius: 20
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '80%',
            plugins: { legend: { display: false } }
        }
    });
</script>
</body>
</html>
