<?php
require_once 'includes/db.php';
require_once 'includes/session.php';
require_once 'includes/auth.php';

$error = '';
$signup_success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'login';

    if ($action === 'login') {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        if (login($email, $password)) {
            header("Location: " . BASE_URL . "index.php");
            exit();
        } else {
            $error = "Invalid credentials or account locked.";
        }
    } elseif ($action === 'signup') {
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $role = $_POST['role'] ?? 'Staff';

        if ($password !== $confirm_password) {
            $error = "Passwords do not match.";
        } else {
            try {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)");
                if ($stmt->execute([$name, $email, $password_hash, $role])) {
                    $signup_success = "Account created successfully! You can now sign in.";
                }
            } catch (PDOException $e) {
                $error = ($e->getCode() == 23000) ? "Email already registered." : "Registration failed.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AURA - Access Portal</title>
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>images/logo.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap"
        rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        .glass {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .light .glass {
            background: rgba(255, 255, 255, 0.98);
            border: 1px solid rgba(15, 23, 42, 0.08);
            box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.05);
        }

        .gradient-text {
            background: linear-gradient(135deg, #2dd4bf, #3b82f6, #818cf8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .brand-gradient {
            background: radial-gradient(circle at top right, rgba(45, 212, 191, 0.15), transparent), radial-gradient(circle at bottom left, rgba(129, 140, 248, 0.1), transparent);
        }

        .light .brand-gradient {
            background: radial-gradient(circle at top right, rgba(45, 212, 191, 0.04), transparent), radial-gradient(circle at bottom left, rgba(129, 140, 248, 0.03), transparent);
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0) scale(1);
            }

            50% {
                transform: translateY(-20px) scale(1.05);
            }
        }

        .animate-float {
            animation: float 6s ease-in-out infinite;
        }

        .blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.3;
            z-index: 0;
        }

        .blob-1 {
            width: 400px;
            height: 400px;
            background: #2dd4bf;
            top: -150px;
            right: -100px;
        }

        .blob-2 {
            width: 500px;
            height: 500px;
            background: #818cf8;
            bottom: -200px;
            left: -150px;
        }

        .light .blob {
            opacity: 0.15;
        }

        .modal-overlay {
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(8px);
        }

        .light .modal-overlay {
            background: rgba(255, 255, 255, 0.4);
        }

        .light .brand-showcase {
            background: linear-gradient(135deg, #f0f9ff 0%, #ecfeff 50%, #f0fdfa 100%);
            border-right: 1px solid rgba(8, 145, 178, 0.1);
        }
        
        .dark .brand-showcase {
            background: #070a13;
            border-right: none;
        }
    </style>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: { extend: { colors: { slate: { 950: '#0b0f1a' } } } }
        }
        if (localStorage.getItem('theme') === 'light' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: light)').matches)) {
            document.documentElement.classList.add('light');
            document.documentElement.classList.remove('dark');
        } else {
            document.documentElement.classList.add('dark');
            document.documentElement.classList.remove('light');
        }

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
</head>

<body
    class="bg-white dark:bg-[#0b0f1a] text-slate-800 dark:text-slate-200 min-h-screen transition-colors duration-300 overflow-hidden">
    <!-- Theme Toggle -->
    <button onclick="toggleTheme()"
        class="fixed top-6 right-6 z-[100] p-3 bg-white dark:bg-white/5 hover:bg-slate-100 dark:hover:bg-white/10 rounded-2xl border border-slate-200 dark:border-white/10 shadow-xl transition-all"
        id="theme-toggle">
        <i data-lucide="sun" class="w-6 h-6 dark:hidden text-slate-600"></i>
        <i data-lucide="moon" class="w-6 h-6 hidden dark:block text-slate-200"></i>
    </button>

    <div class="flex min-h-screen">
        <!-- Left Side: Brand Showcase -->
        <div
            class="hidden lg:flex lg:w-1/2 relative flex-col items-center justify-center p-12 brand-showcase overflow-hidden">
            <div class="blob blob-1"></div>
            <div class="blob blob-2"></div>
            <div class="relative z-10 text-center space-y-8 max-w-lg">
                <div class="animate-float">
                    <div class="w-32 h-32 mx-auto">
                        <img src="<?= BASE_URL ?>images/logo.png" alt="AURA" class="w-full h-full object-contain">
                    </div>
                </div>
                <div class="space-y-4">
                    <h1
                        class="text-5xl font-black tracking-tighter text-slate-900 dark:text-white uppercase leading-none">
                        Service <br><span class="gradient-text">Management</span></h1>
                    <p class="text-slate-600 dark:text-slate-400 text-lg font-medium leading-relaxed">Experience a
                        redefined financial ecosystem with precision tools and vertical intelligence.</p>
                </div>
            </div>
            <div class="absolute bottom-0 left-0 p-12 opacity-5 dark:opacity-5 select-none">
                <p
                    class="text-8xl font-black text-slate-900 dark:text-white tracking-tighter uppercase whitespace-nowrap">
                    AUTHENTICATION</p>
            </div>
        </div>

        <!-- Right Side: Login Form -->
        <div class="w-full lg:w-1/2 flex items-center justify-center p-8 lg:p-12 relative brand-gradient">
            <div class="w-full max-w-md relative z-10">
                <div class="lg:hidden text-center mb-10">
                    <div class="w-16 h-16 mx-auto mb-6">
                        <img src="<?= BASE_URL ?>images/logo.png" alt="AURA" class="w-full h-full object-contain">
                    </div>
                    <h2
                        class="text-3xl font-black tracking-tight text-slate-900 dark:text-white uppercase leading-none">
                        Sign In</h2>
                </div>
                <div class="hidden lg:block mb-10">
                    <h2
                        class="text-4xl font-black tracking-tight text-slate-900 dark:text-white uppercase leading-none">
                        Portal Access</h2>
                    <p
                        class="text-slate-600 dark:text-slate-500 mt-2 font-black tracking-[0.3em] uppercase text-[10px] opacity-70">
                        Secure Financial Gateway</p>
                </div>

                <form action="login.php" method="POST"
                    class="glass p-8 lg:p-10 rounded-[3rem] space-y-6 shadow-2xl relative overflow-hidden">
                    <input type="hidden" name="action" value="login">
                    <?php if ($error): ?>
                        <div
                            class="p-4 bg-rose-500/10 border border-rose-500/20 text-rose-600 dark:text-rose-400 text-xs font-bold rounded-2xl flex items-center gap-3">
                            <i data-lucide="alert-circle" class="w-5 h-5"></i> <?= $error ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($signup_success): ?>
                        <div
                            class="p-4 bg-teal-500/10 border border-teal-500/20 text-teal-600 dark:text-teal-400 text-xs font-bold rounded-2xl flex items-center gap-3">
                            <i data-lucide="check-circle" class="w-5 h-5"></i> <?= $signup_success ?>
                        </div>
                    <?php endif; ?>

                    <div>
                        <label
                            class="block text-[10px] font-black text-slate-600 dark:text-slate-400 uppercase tracking-widest mb-3 ml-1">Email</label>
                        <div class="relative group">
                            <i data-lucide="mail"
                                class="absolute left-5 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400 dark:text-slate-600 group-focus-within:text-blue-600 transition-colors"></i>
                            <input type="email" name="email" required
                                class="w-full bg-slate-50/50 dark:bg-slate-900/40 border-2 border-slate-200 dark:border-white/5 rounded-2xl pl-14 pr-5 py-4 focus:ring-4 focus:ring-blue-500/10 focus:border-blue-600 outline-none transition-all placeholder:text-slate-400 dark:placeholder:text-slate-700 text-slate-900 dark:text-white font-bold"
                                placeholder="name@company.com">
                        </div>
                    </div>

                    <div>
                        <div class="flex justify-between items-center mb-3 ml-1">
                            <label
                                class="block text-[10px] font-black text-slate-600 dark:text-slate-400 uppercase tracking-widest">Password</label>
                            <a href="#"
                                class="text-[10px] font-black text-blue-600 hover:text-blue-700 uppercase tracking-wider transition-colors">Reset?</a>
                        </div>
                        <div class="relative group">
                            <i data-lucide="lock"
                                class="absolute left-5 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400 dark:text-slate-600 group-focus-within:text-blue-600 transition-colors"></i>
                            <input type="password" id="password" name="password" required
                                class="w-full bg-slate-50/50 dark:bg-slate-900/40 border-2 border-slate-200 dark:border-white/5 rounded-2xl pl-14 pr-14 py-4 focus:ring-4 focus:ring-blue-500/10 focus:border-blue-600 outline-none transition-all placeholder:text-slate-400 dark:placeholder:text-slate-700 text-slate-900 dark:text-white font-bold"
                                placeholder="••••••••">
                            <button type="button" onclick="togglePassword('password', 'eye-icon')"
                                class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-600 hover:text-blue-600 transition-colors p-2"><i
                                    data-lucide="eye" id="eye-icon" class="w-5 h-5"></i></button>
                        </div>
                    </div>

                    <button type="submit"
                        class="w-full bg-teal-500 hover:bg-teal-600 text-slate-900 font-black py-4 rounded-2xl transition-all shadow-xl shadow-teal-500/25 text-lg uppercase tracking-widest transform hover:scale-[1.02]">Login</button>

                    <div class="text-center pt-6 border-t border-slate-200 dark:border-white/5 space-y-4">
                        <p
                            class="text-[10px] font-black text-slate-600 dark:text-slate-500 uppercase tracking-widest opacity-60">
                            No account yet?</p>
                        <button type="button" onclick="toggleSignupModal(true)"
                            class="w-full py-4 text-sm font-black text-slate-900 dark:text-white bg-slate-100/80 dark:bg-white/5 hover:bg-slate-200 dark:hover:bg-white/10 rounded-2xl border border-slate-200 dark:border-white/10 transition-all shadow-sm">Create
                            New Account</button>
                        <a href="<?= BASE_URL ?>landing.php"
                            class="inline-flex items-center gap-2 text-[10px] font-black uppercase tracking-widest text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-200 transition-colors"><i
                                data-lucide="arrow-left" class="w-4 h-4"></i> Return to Portal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Signup Modal -->
    <div id="signup-modal"
        class="fixed inset-0 z-[100] flex items-center justify-center p-4 modal-overlay opacity-0 pointer-events-none transition-all duration-300">
        <div
            class="glass w-full max-w-lg p-10 lg:p-12 rounded-[3.5rem] relative transform scale-95 transition-all duration-300">
            <button onclick="toggleSignupModal(false)"
                class="absolute right-8 top-8 p-3 text-slate-400 hover:text-slate-900 dark:hover:text-white bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded-2xl transition-all shadow-sm"><i
                    data-lucide="x" class="w-6 h-6"></i></button>
            <div class="mb-10 text-center">
                <h2 class="text-4xl font-black text-slate-900 dark:text-white uppercase tracking-tighter">Join <span
                        class="gradient-text">Ecosystem</span></h2>
                <p class="text-slate-600 dark:text-slate-400 mt-2 font-black tracking-widest uppercase text-[10px]">
                    Generate your vertical credentials</p>
            </div>
            <form action="login.php" method="POST" class="space-y-5">
                <input type="hidden" name="action" value="signup">
                <div>
                    <label
                        class="block text-[10px] font-black text-slate-600 dark:text-slate-400 uppercase tracking-widest mb-2 ml-1">Full
                        Identity</label>
                    <input type="text" name="name" required
                        class="w-full bg-slate-50/50 dark:bg-slate-900/40 border-2 border-slate-200 dark:border-white/5 rounded-2xl px-5 py-4 focus:ring-4 focus:ring-teal-500/10 focus:border-teal-500 outline-none text-slate-900 dark:text-white font-bold placeholder:text-slate-400"
                        placeholder="Full Name">
                </div>
                <div>
                    <label
                        class="block text-[10px] font-black text-slate-600 dark:text-slate-400 uppercase tracking-widest mb-2 ml-1">

                        Email</label>
                    <input type="email" name="email" required
                        class="w-full bg-slate-50/50 dark:bg-slate-900/40 border-2 border-slate-200 dark:border-white/5 rounded-2xl px-5 py-4 focus:ring-4 focus:ring-teal-500/10 focus:border-teal-500 outline-none text-slate-900 dark:text-white font-bold placeholder:text-slate-400"
                        placeholder="name@company.com">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label
                            class="block text-[10px] font-black text-slate-600 dark:text-slate-400 uppercase tracking-widest mb-2 ml-1">Password</label>
                        <div class="relative group">
                            <input type="password" id="signup_password" name="password" required
                                class="w-full bg-slate-50/50 dark:bg-slate-900/40 border-2 border-slate-200 dark:border-white/5 rounded-2xl px-5 pr-12 py-4 focus:ring-4 focus:ring-teal-500/10 focus:border-teal-500 outline-none text-slate-900 dark:text-white font-bold placeholder:text-slate-400"
                                placeholder="••••">
                            <button type="button" onclick="togglePassword('signup_password', 'signup-eye-icon')"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-600 hover:text-teal-500 transition-colors p-2">
                                <i data-lucide="eye" id="signup-eye-icon" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>
                    <div>
                        <label
                            class="block text-[10px] font-black text-slate-600 dark:text-slate-400 uppercase tracking-widest mb-2 ml-1">Confirm</label>
                        <div class="relative group">
                            <input type="password" id="signup_confirm" name="confirm_password" required
                                class="w-full bg-slate-50/50 dark:bg-slate-900/40 border-2 border-slate-200 dark:border-white/5 rounded-2xl px-5 pr-12 py-4 focus:ring-4 focus:ring-teal-500/10 focus:border-teal-500 outline-none text-slate-900 dark:text-white font-bold placeholder:text-slate-400"
                                placeholder="••••">
                            <button type="button" onclick="togglePassword('signup_confirm', 'confirm-eye-icon')"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-600 hover:text-teal-500 transition-colors p-2">
                                <i data-lucide="eye" id="confirm-eye-icon" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div>
                    <label
                        class="block text-[10px] font-black text-slate-600 dark:text-slate-400 uppercase tracking-widest mb-2 ml-1">Organizational
                        Role</label>
                    <select name="role"
                        class="w-full bg-slate-50/50 dark:bg-slate-900/40 border-2 border-slate-200 dark:border-white/5 rounded-2xl px-5 py-4 focus:ring-4 focus:ring-teal-500/10 focus:border-teal-500 outline-none text-slate-900 dark:text-white font-black appearance-none">
                        <option value="Admin">System Administrator</option>
                        <option value="Finance">Finance Officer</option>
                        <option value="Manager">Operations Manager</option>
                        <option value="Staff" selected>General Staff</option>
                    </select>
                </div>
                <button type="submit"
                    class="w-full bg-teal-500 hover:bg-teal-600 text-slate-900 font-black py-5 rounded-2xl transition-all shadow-xl shadow-teal-500/20 text-lg uppercase tracking-widest">Create
                    Account</button>
            </form>
        </div>
    </div>

    <script>
        lucide.createIcons();
        function togglePassword(inputId, iconId) {
            const el = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            el.type = el.type === 'password' ? 'text' : 'password';
            icon.setAttribute('data-lucide', el.type === 'password' ? 'eye' : 'eye-off');
            lucide.createIcons();
        }
        function toggleSignupModal(show) {
            const modal = document.getElementById('signup-modal');
            const box = modal.querySelector('.glass');
            if (show) {
                modal.classList.remove('pointer-events-none', 'opacity-0');
                box.classList.remove('scale-95');
                box.classList.add('scale-100');
                document.body.style.overflow = 'hidden';
            } else {
                modal.classList.add('opacity-0', 'pointer-events-none');
                box.classList.remove('scale-100');
                box.classList.add('scale-95');
                document.body.style.overflow = '';
            }
        }

        // Check for signup parameter on load
        window.addEventListener('load', () => {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('signup') === 'true') {
                toggleSignupModal(true);
            }
        });
    </script>
</body>

</html>
