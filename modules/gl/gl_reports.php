<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../dashboard/stats.php';
check_login();

include __DIR__ . '/../../includes/header_dashboard.php';

// Use Centralized Logic
$stats = get_dashboard_stats();
$cashflow = get_cashflow_data(); // Returns last 12 months

$revenue = $stats['total_revenue'];
$expense = $stats['expenditures'];
$net_income = $revenue - $expense;
?>

<style>
    @media print {
        .glass { background: white !important; color: black !important; border: 1px solid #e2e8f0 !important; box-shadow: none !important; backdrop-filter: none !important; border-radius: 0 !important; margin: 0 !important; padding: 20px !important; }
        aside, .print\:hidden, nav, header button, .flex-grow > header { display: none !important; }
        main { margin-left: 0 !important; padding: 0 !important; width: 100% !important; }
        .grid { grid-template-columns: repeat(3, 1fr) !important; gap: 10px !important; }
        h2, h3, h4, p { color: black !important; }
        .gradient-text { background: none !important; -webkit-text-fill-color: black !important; color: black !important; }
        .animate-fade-in, .animate-scale-in { animation: none !important; opacity: 1 !important; transform: none !important; }
    }
</style>

<div class="flex">
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <main class="ml-72 flex-grow p-10 min-h-screen">
        <header class="mb-10 animate-fade-in px-8 py-12 glass rounded-[2.5rem] border border-white/10 dark:border-white/5 relative overflow-hidden shadow-2xl">
            <div class="absolute inset-0 bg-gradient-to-r from-teal-500/10 via-transparent to-transparent pointer-events-none"></div>
            <div class="relative flex justify-between items-center text-slate-900 dark:text-white">
                <h2 class="text-4xl font-black tracking-tight uppercase">Financial <span class="gradient-text">Reports</span></h2>
                <div class="flex gap-4">
                    <button onclick="window.print()" class="px-6 py-4 glass rounded-2xl hover:bg-white/10 transition-all flex items-center gap-2 text-sm font-black border border-white/5">
                        <i data-lucide="printer" class="w-4 h-4 text-teal-400"></i> Print Report
                    </button>
                    <a href="index.php" class="px-6 py-4 glass rounded-2xl hover:bg-white/10 transition-all flex items-center gap-2 text-sm font-black border border-white/5">
                        <i data-lucide="arrow-left" class="w-4 h-4 text-indigo-400"></i> Back
                    </a>
                </div>
            </div>
        </header>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-10">
            <div class="glass p-8 rounded-[2rem] border-l-4 border-teal-500">
                <p class="text-slate-500 uppercase text-xs font-black tracking-widest mb-2">Total Revenue</p>
                <h3 class="text-3xl font-black text-teal-400 dark:text-teal-400"><?= CURRENCY ?><?= number_format($revenue, 2) ?></h3>
            </div>
            <div class="glass p-8 rounded-[2rem] border-l-4 border-rose-500">
                <p class="text-slate-500 uppercase text-xs font-black tracking-widest mb-2">Total Expenses</p>
                <h3 class="text-3xl font-black text-rose-500 dark:text-rose-400"><?= CURRENCY ?><?= number_format($expense, 2) ?></h3>
            </div>
            <div class="glass p-8 rounded-[2rem] border-l-4 border-indigo-500 bg-gradient-to-br from-indigo-500/5 to-transparent">
                <p class="text-slate-500 uppercase text-xs font-black tracking-widest mb-2">Net Income</p>
                <h3 class="text-3xl font-black <?= $net_income >= 0 ? 'text-teal-400' : 'text-rose-400' ?>"><?= CURRENCY ?><?= number_format($net_income, 2) ?></h3>
            </div>
        </div>

        <div class="glass p-10 rounded-[2.5rem] mb-10">
            <h4 class="text-lg font-bold mb-8 flex items-center gap-2 text-slate-900 dark:text-white uppercase tracking-widest text-xs">
                <i data-lucide="trending-up" class="w-5 h-5 text-teal-400"></i> 12-Month Performance Analysis
            </h4>
            <div class="h-[400px] w-full">
                <canvas id="reportChart"></canvas>
            </div>
        </div>
    </main>
</div>

<script>
    lucide.createIcons();

    const ctx = document.getElementById('reportChart').getContext('2d');
    new Chart(ctx, {
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
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        color: '#94a3b8',
                        font: { weight: 'bold', size: 12 },
                        padding: 20
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(255, 255, 255, 0.05)' },
                    ticks: { 
                        color: '#94a3b8', 
                        font: { size: 11, weight: 'bold' },
                        callback: function(value) { return '₱' + value.toLocaleString(); }
                    }
                },
                x: {
                    grid: { display: false },
                    ticks: { color: '#94a3b8', font: { size: 11, weight: 'bold' } }
                }
            }
        }
    });
</script>
</body>
</html>
