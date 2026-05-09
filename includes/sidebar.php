<?php
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
require_once __DIR__ . '/auth.php';

function is_active($target_page, $target_dir = null)
{
    global $current_page, $current_dir;
    $request_uri = $_SERVER['PHP_SELF'];
    $is_in_module = strpos($request_uri, '/modules/') !== false;

    $is_match = false;

    if ($target_dir) {
        // For admin module, match the exact page since there are multiple sidebar items
        if ($target_dir === 'admin') {
            $is_match = ($current_dir === $target_dir && $current_page === $target_page);
        } else {
            // For other modules, highlight if we are anywhere in that module's directory
            $is_match = ($current_dir === $target_dir);
        }
    } else {
        // Dashboard only active if it's the root index and NOT inside a modules folder
        $is_match = ($current_page === $target_page && !$is_in_module);
    }

    return $is_match ? 'bg-teal-500/10 text-teal-400 font-bold shadow-sm' : 'text-slate-500 hover:bg-slate-100 dark:hover:bg-white/5 transition-all duration-300';
}
?>
<aside
    class="w-64 h-screen glass border-r border-slate-200 dark:border-slate-800 fixed left-0 top-0 p-6 flex flex-col z-50">
    <div class="flex flex-col items-center gap-4 mb-10 group cursor-pointer w-full text-center">
        <div class="w-20 h-20 transition-transform group-hover:scale-110">
            <img src="<?= BASE_URL ?>images/logo.png" alt="AURA Logo" class="w-full h-full object-contain">
        </div>
    </div>

    <nav class="space-y-2 flex-grow">
        <?php if (has_permission('dashboard', 'view')): ?>
            <a href="<?= BASE_URL ?>index.php"
                class="flex items-center gap-3 px-4 py-3 rounded-xl <?= is_active('index.php') ?>">
                <i data-lucide="layout-dashboard" class="w-5 h-5"></i> Dashboard
            </a>
        <?php endif; ?>

        <?php if (has_permission('budget', 'view')): ?>
            <a href="<?= BASE_URL ?>modules/budget/index.php"
                class="flex items-center gap-3 px-4 py-3 rounded-xl <?= is_active('index.php', 'budget') ?>">
                <i data-lucide="pie-chart" class="w-5 h-5"></i> Budget Management
            </a>
        <?php endif; ?>

        <?php if (has_permission('ap_ar', 'view')): ?>
            <a href="<?= BASE_URL ?>modules/ap_ar/index.php"
                class="flex items-center gap-3 px-4 py-3 rounded-xl <?= is_active('index.php', 'ap_ar') ?>">
                <i data-lucide="users" class="w-5 h-5"></i> AP / AR
            </a>
        <?php endif; ?>

        <?php if (has_permission('disbursement', 'view')): ?>
            <a href="<?= BASE_URL ?>modules/disbursement/index.php"
                class="flex items-center gap-3 px-4 py-3 rounded-xl <?= is_active('index.php', 'disbursement') ?>">
                <i data-lucide="send" class="w-5 h-5"></i> Disbursement
            </a>
        <?php endif; ?>

        <?php if (has_permission('collection', 'view')): ?>
            <a href="<?= BASE_URL ?>modules/collection/index.php"
                class="flex items-center gap-3 px-4 py-3 rounded-xl <?= is_active('index.php', 'collection') ?>">
                <i data-lucide="arrow-down-to-line" class="w-5 h-5"></i> Collection
            </a>
        <?php endif; ?>

        <?php if (has_permission('gl', 'view')): ?>
            <a href="<?= BASE_URL ?>modules/gl/index.php"
                class="flex items-center gap-3 px-4 py-3 rounded-xl <?= is_active('index.php', 'gl') ?>">
                <i data-lucide="book-open" class="w-5 h-5"></i> General Ledger
            </a>
        <?php endif; ?>

        <?php if (has_permission('admin', 'view')): ?>
            <div class="pt-4 pb-2">
                <p class="text-[10px] font-black uppercase tracking-widest text-slate-500 ml-4 mb-2">Administration</p>
                <a href="<?= BASE_URL ?>modules/admin/audit_logs.php"
                    class="flex items-center gap-3 px-4 py-3 rounded-xl <?= is_active('audit_logs.php', 'admin') ?>">
                    <i data-lucide="shield-check" class="w-5 h-5 text-indigo-400"></i> Audit Logs
                </a>
                <a href="<?= BASE_URL ?>modules/admin/users.php"
                    class="flex items-center gap-3 px-4 py-3 rounded-xl <?= is_active('users.php', 'admin') ?>">
                    <i data-lucide="users" class="w-5 h-5 text-indigo-400"></i> User Management
                </a>
            </div>
        <?php endif; ?>
    </nav>

    <div class="mt-auto pt-6 border-t border-slate-200 dark:border-slate-800 space-y-4">
        <!-- Theme Toggle -->
        <button onclick="toggleTheme()"
            class="w-full flex items-center justify-center gap-2 py-2 glass rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-all text-sm">
            <i data-lucide="sun" class="w-4 h-4 hidden dark:block"></i>
            <i data-lucide="moon" class="w-4 h-4 block dark:hidden"></i>
            <span class="dark:inline hidden">Light Mode</span>
            <span class="inline dark:hidden">Dark Mode</span>
        </button>

        <!-- User Info -->
        <div class="flex items-center gap-3 p-2 rounded-2xl bg-white/5 border border-white/5">
            <div
                class="w-10 h-10 rounded-xl bg-gradient-to-br from-slate-700 to-slate-900 flex items-center justify-center text-xs font-black text-white shadow-lg">
                <?= strtoupper(substr($_SESSION['name'] ?? 'U', 0, 1)) ?>
            </div>
            <div class="overflow-hidden">
                <p class="text-sm font-black truncate text-slate-900 dark:text-white"><?= $_SESSION['name'] ?? 'User' ?>
                </p>
                <p class="text-xs text-slate-500 uppercase font-bold tracking-wider"><?= $_SESSION['role'] ?? 'Staff' ?>
                </p>
            </div>
        </div>

        <a href="#" onclick="handleSignOut(event)"
            class="flex items-center justify-center gap-2 px-4 py-4 bg-rose-500/10 hover:bg-rose-500 text-rose-500 hover:text-white rounded-2xl text-sm font-black transition-all shadow-lg shadow-rose-500/5 group">
            <i data-lucide="log-out" class="w-4 h-4 transition-transform group-hover:-translate-x-1"></i> Sign Out
        </a>
    </div>
</aside>

<script>
    function handleSignOut(e) {
        e.preventDefault();
        if (typeof SMS_UI !== 'undefined') {
            SMS_UI.showConfirm({
                title: 'Sign Out',
                message: 'Are you sure you want to end your session? Any unsaved changes may be lost.',
                confirmText: 'Sign Out',
                cancelText: 'Stay',
                type: 'error',
                onConfirm: () => {
                    window.location.href = '<?= BASE_URL ?>logout.php';
                }
            });
        } else {
            // Fallback if UI helpers aren't loaded
            if (confirm('Are you sure you want to sign out?')) {
                window.location.href = '<?= BASE_URL ?>logout.php';
            }
        }
    }

    function toggleTheme() {
        const html = document.documentElement;
        if (html.classList.contains('light')) {
            html.classList.remove('light');
            html.classList.add('dark');
            localStorage.setItem('theme', 'dark');
        } else {
            html.classList.remove('dark');
            html.classList.add('light');
            localStorage.setItem('theme', 'light');
        }
        lucide.createIcons();
    }
    // Re-initialize icons for the sidebar specifically
    lucide.createIcons();
</script>