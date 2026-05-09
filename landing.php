<?php
require_once 'includes/db.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AURA - Service Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            overflow-x: hidden;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        .glass {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .light .glass {
            background: rgba(255, 255, 255, 0.95);
            border: 1px solid rgba(15, 23, 42, 0.08);
            backdrop-filter: blur(10px);
            box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.05);
        }

        .gradient-bg {
            background: radial-gradient(circle at top right, rgba(45, 212, 191, 0.15), transparent), radial-gradient(circle at bottom left, rgba(129, 140, 248, 0.15), transparent);
        }

        .light .gradient-bg {
            background: radial-gradient(circle at top right, rgba(45, 212, 191, 0.08), transparent), radial-gradient(circle at bottom left, rgba(129, 140, 248, 0.08), transparent);
        }

        .hero-glow {
            filter: blur(100px);
            background: linear-gradient(135deg, #2dd4bf, #3b82f6, #818cf8);
            opacity: 0.2;
        }

        .light .hero-glow {
            opacity: 0.1;
            filter: blur(120px);
        }

        .gradient-text {
            background: linear-gradient(135deg, #2dd4bf, #3b82f6, #818cf8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .animate-float {
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-20px);
            }
        }

        .animate-fade-in {
            animation: fadeIn 1s ease-out forwards;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: { extend: { colors: { slate: { 950: '#0b0f1a' } } } }
        }
        // Theme initialization
        if (localStorage.getItem('theme') === 'light' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: light)').matches)) {
            document.documentElement.classList.add('light');
        } else {
            document.documentElement.classList.add('dark');
        }
    </script>
</head>

<body
    class="bg-white dark:bg-[#0b0f1a] text-slate-800 dark:text-slate-100 min-h-screen gradient-bg transition-colors duration-300">
    <!-- Hero Glow -->
    <div class="fixed top-[-10%] right-[-10%] w-[500px] h-[500px] hero-glow rounded-full"></div>
    <div class="fixed bottom-[-10%] left-[-10%] w-[500px] h-[500px] hero-glow rounded-full"></div>

    <!-- Navigation -->
    <nav class="relative z-50 flex justify-between items-center px-8 py-6 max-w-7xl mx-auto animate-fade-in">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10">
                <img src="<?= BASE_URL ?>images/logo.png" alt="AURA" class="w-full h-full object-contain">
            </div>
        </div>
        <div class="flex items-center gap-8">
            <a href="#features"
                class="text-[10px] font-black uppercase tracking-widest text-slate-700 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition-colors">Features</a>
            <a href="#about"
                class="text-[10px] font-black uppercase tracking-widest text-slate-700 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition-colors">About</a>
            <button onclick="toggleTheme()"
                class="p-2.5 bg-slate-100 dark:bg-white/5 hover:bg-slate-200 dark:hover:bg-white/10 rounded-xl transition-all border border-slate-200 dark:border-white/10 shadow-sm"
                id="theme-toggle">
                <i data-lucide="sun" class="w-5 h-5 dark:hidden text-slate-600"></i>
                <i data-lucide="moon" class="w-5 h-5 hidden dark:block"></i>
            </button>
            <a href="<?= BASE_URL ?>login.php"
                class="px-6 py-2.5 bg-white dark:bg-white/5 hover:bg-slate-50 dark:hover:bg-white/10 border border-slate-200 dark:border-white/10 rounded-xl text-xs font-black uppercase tracking-widest transition-all text-slate-900 dark:text-white shadow-sm">Sign
                In</a>
            <a href="<?= BASE_URL ?>login.php?signup=true"
                class="px-6 py-2.5 bg-teal-500 hover:bg-teal-600 text-slate-900 rounded-xl text-xs font-black uppercase tracking-widest transition-all shadow-lg shadow-teal-500/20">Get
                Started</a>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="relative z-10 max-w-7xl mx-auto px-8 pt-12 pb-32 text-center animate-fade-in"
        style="animation-delay: 0.1s;">

        <h2
            class="text-6xl md:text-9xl font-black tracking-tighter mb-6 leading-[0.9] uppercase text-slate-900 dark:text-white">
            Financial <br>
            <span class="gradient-text">Intelligence</span>
        </h2>
        <p
            class="text-slate-600 dark:text-slate-400 text-lg md:text-xl max-w-2xl mx-auto mb-12 font-medium leading-relaxed">
            The next generation Service Management System for modern financial ecosystems. Built for scale, security,
            and absolute precision.
        </p>
        <div class="flex flex-col md:flex-row items-center justify-center gap-4">
            <a href="<?= BASE_URL ?>login.php?signup=true"
                class="w-full md:w-auto px-10 py-5 bg-teal-500 hover:bg-teal-600 text-slate-900 rounded-2xl text-lg font-black uppercase tracking-widest transition-all shadow-2xl shadow-teal-500/25 flex items-center justify-center gap-3">
                Get Started <i data-lucide="chevron-right" class="w-6 h-6"></i>
            </a>
            <a href="<?= BASE_URL ?>login.php"
                class="w-full md:w-auto px-10 py-5 bg-white dark:bg-white/5 hover:bg-slate-50 dark:hover:bg-white/10 border border-slate-200 dark:border-white/10 rounded-2xl text-lg font-black uppercase tracking-widest transition-all flex items-center justify-center gap-3 text-slate-900 dark:text-white shadow-xl">
                Portal Access <i data-lucide="arrow-right-circle" class="w-6 h-6"></i>
            </a>
        </div>

        <!-- Dashboard Mockup -->
        <div class="mt-24 relative group animate-float">
            <div
                class="absolute inset-0 bg-gradient-to-tr from-teal-500 to-indigo-600 opacity-20 blur-3xl rounded-[3rem] group-hover:opacity-30 transition-opacity">
            </div>
            <div class="relative glass rounded-[2.5rem] p-4 shadow-2xl overflow-hidden aspect-video max-w-5xl mx-auto">
                <div
                    class="w-full h-full bg-slate-50 dark:bg-slate-900/50 rounded-2xl border border-slate-200 dark:border-white/5 flex flex-col p-8 text-left">
                    <div class="flex items-center justify-between mb-8">
                        <div class="flex gap-2">
                            <div class="w-3 h-3 rounded-full bg-rose-500/20"></div>
                            <div class="w-3 h-3 rounded-full bg-amber-500/20"></div>
                            <div class="w-3 h-3 rounded-full bg-teal-500/20"></div>
                        </div>
                        <div class="w-32 h-2 bg-slate-200 dark:bg-white/5 rounded-full"></div>
                    </div>
                    <div class="grid grid-cols-4 gap-4 mb-8">
                        <div
                            class="h-24 bg-white dark:bg-transparent glass rounded-xl border border-slate-200 dark:border-white/5 p-4 shadow-sm dark:shadow-none">
                            <div class="w-8 h-8 rounded-lg bg-teal-500/10 mb-2"></div>
                            <div class="w-full h-2 bg-slate-200 dark:bg-white/10 rounded-full mb-1"></div>
                            <div class="w-2/3 h-2 bg-slate-100 dark:bg-white/5 rounded-full"></div>
                        </div>
                        <div
                            class="h-24 bg-white dark:bg-transparent glass rounded-xl border border-slate-200 dark:border-white/5 p-4 shadow-sm dark:shadow-none">
                            <div class="w-8 h-8 rounded-lg bg-indigo-500/10 mb-2"></div>
                            <div class="w-full h-2 bg-slate-200 dark:bg-white/10 rounded-full mb-1"></div>
                            <div class="w-2/3 h-2 bg-slate-100 dark:bg-white/5 rounded-full"></div>
                        </div>
                        <div
                            class="h-24 bg-white dark:bg-transparent glass rounded-xl border border-slate-200 dark:border-white/5 p-4 shadow-sm dark:shadow-none">
                            <div class="w-8 h-8 rounded-lg bg-rose-500/10 mb-2"></div>
                            <div class="w-full h-2 bg-slate-200 dark:bg-white/10 rounded-full mb-1"></div>
                            <div class="w-2/3 h-2 bg-slate-100 dark:bg-white/5 rounded-full"></div>
                        </div>
                        <div
                            class="h-24 bg-white dark:bg-transparent glass rounded-xl border border-slate-200 dark:border-white/5 p-4 shadow-sm dark:shadow-none">
                            <div class="w-8 h-8 rounded-lg bg-amber-500/10 mb-2"></div>
                            <div class="w-full h-2 bg-slate-200 dark:bg-white/10 rounded-full mb-1"></div>
                            <div class="w-2/3 h-2 bg-slate-100 dark:bg-white/5 rounded-full"></div>
                        </div>
                    </div>
                    <div
                        class="flex-grow bg-white dark:bg-transparent glass rounded-2xl border border-slate-200 dark:border-white/5 p-8 flex items-end gap-2 shadow-sm dark:shadow-none">
                        <div class="w-full bg-indigo-500/20 rounded-t-lg h-1/2"></div>
                        <div class="w-full bg-teal-500/50 rounded-t-lg h-3/4"></div>
                        <div class="w-full bg-indigo-500/20 rounded-t-lg h-1/3"></div>
                        <div class="w-full bg-teal-500/20 rounded-t-lg h-1/4"></div>
                        <div class="w-full bg-indigo-500/50 rounded-t-lg h-2/3"></div>
                        <div class="w-full bg-teal-500/20 rounded-t-lg h-5/6"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="max-w-7xl mx-auto px-8 py-32 animate-fade-in" style="animation-delay: 0.4s;">
        <div class="text-center mb-20">
            <h3 class="text-4xl font-black mb-4 text-slate-900 dark:text-white uppercase tracking-tighter">Powerful Core
                Modules</h3>
            <p class="text-slate-500 dark:text-slate-400 font-bold uppercase tracking-widest text-xs">Everything you
                need to manage your financial ecosystem.</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <!-- General Ledger -->
            <div
                class="glass p-10 rounded-[2.5rem] hover:border-teal-500/50 transition-all group shadow-xl border-slate-200 dark:border-white/5">
                <div
                    class="w-16 h-16 bg-teal-500/10 text-teal-500 rounded-2xl flex items-center justify-center mb-8 group-hover:scale-110 transition-transform">
                    <i data-lucide="book-open" class="w-8 h-8"></i>
                </div>
                <h4 class="text-xl font-bold mb-4 text-slate-900 dark:text-white">General Ledger</h4>
                <p class="text-slate-600 dark:text-slate-400 text-sm font-medium leading-relaxed">The financial
                    heartbeat. Precision COA, multi-currency support, and real-time ledger posting.</p>
            </div>
            <!-- AP & AR -->
            <div
                class="glass p-10 rounded-[2.5rem] hover:border-blue-500/50 transition-all group shadow-xl border-slate-200 dark:border-white/5">
                <div
                    class="w-16 h-16 bg-blue-500/10 text-blue-500 rounded-2xl flex items-center justify-center mb-8 group-hover:scale-110 transition-transform">
                    <i data-lucide="users" class="w-8 h-8"></i>
                </div>
                <h4 class="text-xl font-bold mb-4 text-slate-900 dark:text-white">AP & AR</h4>
                <p class="text-slate-600 dark:text-slate-400 text-sm font-medium leading-relaxed">Streamlined
                    obligations. Automate vendor payables and optimize customer receivable cycles.</p>
            </div>
            <!-- Budget Management -->
            <div
                class="glass p-10 rounded-[2.5rem] hover:border-indigo-500/50 transition-all group shadow-xl border-slate-200 dark:border-white/5">
                <div
                    class="w-16 h-16 bg-indigo-500/10 text-indigo-500 rounded-2xl flex items-center justify-center mb-8 group-hover:scale-110 transition-transform">
                    <i data-lucide="pie-chart" class="w-8 h-8"></i>
                </div>
                <h4 class="text-xl font-bold mb-4 text-slate-900 dark:text-white">Budgeting</h4>
                <p class="text-slate-600 dark:text-slate-400 text-sm font-medium leading-relaxed">Strategic allocation.
                    Control spending with real-time budget tracking and variance analytics.</p>
            </div>
            <!-- Collection & Billing -->
            <div
                class="glass p-10 rounded-[2.5rem] hover:border-amber-500/50 transition-all group shadow-xl border-slate-200 dark:border-white/5">
                <div
                    class="w-16 h-16 bg-amber-500/10 text-amber-500 rounded-2xl flex items-center justify-center mb-8 group-hover:scale-110 transition-transform">
                    <i data-lucide="wallet" class="w-8 h-8"></i>
                </div>
                <h4 class="text-xl font-bold mb-4 text-slate-900 dark:text-white">Collections</h4>
                <p class="text-slate-600 dark:text-slate-400 text-sm font-medium leading-relaxed">Revenue optimization.
                    Automated billing workflows, fast deposits, and collection matching.</p>
            </div>
            <!-- Disbursement & Payments -->
            <div
                class="glass p-10 rounded-[2.5rem] hover:border-emerald-500/50 transition-all group shadow-xl border-slate-200 dark:border-white/5">
                <div
                    class="w-16 h-16 bg-emerald-500/10 text-emerald-500 rounded-2xl flex items-center justify-center mb-8 group-hover:scale-110 transition-transform">
                    <i data-lucide="credit-card" class="w-8 h-8"></i>
                </div>
                <h4 class="text-xl font-bold mb-4 text-slate-900 dark:text-white">Disbursements</h4>
                <p class="text-slate-600 dark:text-slate-400 text-sm font-medium leading-relaxed">Managed payouts.
                    Secure voucher processing, tax computations, and electronic fund transfers.</p>
            </div>
            <!-- Financial Analytics -->
            <div
                class="glass p-10 rounded-[2.5rem] hover:border-rose-500/50 transition-all group shadow-xl border-slate-200 dark:border-white/5">
                <div
                    class="w-16 h-16 bg-rose-500/10 text-rose-500 rounded-2xl flex items-center justify-center mb-8 group-hover:scale-110 transition-transform">
                    <i data-lucide="bar-chart-3" class="w-8 h-8"></i>
                </div>
                <h4 class="text-xl font-bold mb-4 text-slate-900 dark:text-white">Analytics</h4>
                <p class="text-slate-600 dark:text-slate-400 text-sm font-medium leading-relaxed">Vertical intelligence.
                    Real-time dashboards, automated reporting, and deep financial insights.</p>
            </div>
        </div>
    </section>

    <footer
        class="max-w-7xl mx-auto px-8 py-20 border-t border-slate-200 dark:border-white/5 flex flex-col md:flex-row justify-between items-center gap-8">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8">
                <img src="<?= BASE_URL ?>images/logo.png" alt="AURA" class="w-full h-full object-contain">
            </div>
            <p class="text-slate-500 dark:text-slate-400 text-sm font-medium">&copy; 2026 AURA - Service Management
                System.</p>
        </div>
        <div class="flex gap-8 text-sm font-bold uppercase tracking-widest text-[10px] text-slate-500">
            <a href="#" class="hover:text-slate-900 dark:hover:text-white transition-colors">Privacy Policy</a>
            <a href="#" class="hover:text-slate-900 dark:hover:text-white transition-colors">Terms of Service</a>
            <a href="#" class="hover:text-slate-900 dark:hover:text-white transition-colors">Contact Support</a>
        </div>
    </footer>

    <script>
        lucide.createIcons();

        function toggleTheme() {
            const html = document.documentElement;
            if (html.classList.contains('dark')) {
                html.classList.remove('dark');
                html.classList.add('light');
                localStorage.setItem('theme', 'light');
            } else {
                html.classList.remove('light');
                html.classList.add('dark');
                localStorage.setItem('theme', 'dark');
            }
            lucide.createIcons();
        }
    </script>
</body>

</html>