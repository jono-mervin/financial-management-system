<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/auth.php';
check_login();

if ($_SESSION['role'] !== 'Admin') {
    header("Location: " . BASE_URL . "index.php");
    exit();
}

$user_filter = $_GET['user_id'] ?? '';
$module_filter = $_GET['module'] ?? '';

$query = "SELECT a.*, u.name as user_name, u.role as user_role 
          FROM audit_log a 
          LEFT JOIN users u ON a.user_id = u.user_id 
          WHERE 1=1";
$params = [];

if ($user_filter) {
    $query .= " AND a.user_id = ?";
    $params[] = $user_filter;
}
if ($module_filter) {
    $query .= " AND a.module = ?";
    $params[] = $module_filter;
}

$query .= " ORDER BY a.created_at DESC LIMIT 100";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$logs = $stmt->fetchAll();

$users = $pdo->query("SELECT user_id, name FROM users ORDER BY name")->fetchAll();
$modules = $pdo->query("SELECT DISTINCT module FROM audit_log ORDER BY module")->fetchAll(PDO::FETCH_COLUMN);

include __DIR__ . '/../../includes/header_dashboard.php';
?>

<div class="flex">
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <main class="ml-72 flex-grow p-10 min-h-screen">
        <header class="mb-10 animate-fade-in px-8 py-12 glass rounded-[2.5rem] border border-white/10 dark:border-white/5 relative overflow-hidden shadow-2xl">
            <div class="absolute inset-0 bg-gradient-to-r from-indigo-500/10 via-transparent to-transparent pointer-events-none"></div>
            <div class="relative flex justify-between items-center">
                <div>
                    <h2 class="text-4xl font-black tracking-tight uppercase text-slate-900 dark:text-white">System <span class="gradient-text">Audit Logs</span></h2>
                    <p class="text-slate-500 text-sm font-bold mt-2 uppercase tracking-widest">Security & Activity Monitoring</p>
                </div>
                <div class="flex gap-4">
                    <button onclick="window.print()" class="px-6 py-4 glass rounded-2xl hover:bg-white/10 transition-all flex items-center gap-2 text-sm font-black text-slate-900 dark:text-white border border-white/5">
                        <i data-lucide="printer" class="w-4 h-4 text-indigo-400"></i> Print Logs
                    </button>
                </div>
            </div>
        </header>

        <!-- Filters -->
        <div class="glass p-8 rounded-[2rem] mb-10 border border-white/5">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-6 items-end">
                <div class="space-y-2">
                    <label class="text-xs font-black uppercase tracking-widest text-slate-500 ml-1">Filter by User</label>
                    <select name="user_id" class="w-full bg-slate-100 dark:bg-slate-900/50 border border-white/5 rounded-2xl px-5 py-4 focus:ring-2 focus:ring-indigo-500/50 outline-none">
                        <option value="">All Users</option>
                        <?php foreach ($users as $u): ?>
                            <option value="<?= $u['user_id'] ?>" <?= $user_filter == $u['user_id'] ? 'selected' : '' ?>><?= $u['name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="space-y-2">
                    <label class="text-xs font-black uppercase tracking-widest text-slate-500 ml-1">Filter by Module</label>
                    <select name="module" class="w-full bg-slate-100 dark:bg-slate-900/50 border border-white/5 rounded-2xl px-5 py-4 focus:ring-2 focus:ring-indigo-500/50 outline-none">
                        <option value="">All Modules</option>
                        <?php foreach ($modules as $m): ?>
                            <option value="<?= $m ?>" <?= $module_filter == $m ? 'selected' : '' ?>><?= strtoupper($m) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="flex gap-4">
                    <button type="submit" class="flex-grow py-4 bg-indigo-500 hover:bg-indigo-600 text-white font-black rounded-2xl transition-all shadow-xl shadow-indigo-500/20">
                        Apply Filters
                    </button>
                    <a href="audit_logs.php" class="px-6 py-4 glass rounded-2xl border border-white/5 hover:bg-white/5 transition-all text-sm font-bold flex items-center">Reset</a>
                </div>
            </form>
        </div>

        <!-- Logs Table -->
        <div class="glass rounded-[2.5rem] overflow-hidden border border-white/5 shadow-2xl">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-slate-100 dark:bg-white/5 border-b border-white/5">
                        <tr>
                            <th class="px-8 py-6 text-xs font-black uppercase tracking-widest text-slate-500">Timestamp</th>
                            <th class="px-8 py-6 text-xs font-black uppercase tracking-widest text-slate-500">User</th>
                            <th class="px-8 py-6 text-xs font-black uppercase tracking-widest text-slate-500">Action</th>
                            <th class="px-8 py-6 text-xs font-black uppercase tracking-widest text-slate-500">Module</th>
                            <th class="px-8 py-6 text-xs font-black uppercase tracking-widest text-slate-500">Details</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        <?php foreach ($logs as $log): ?>
                            <tr class="hover:bg-white/5 transition-colors group">
                                <td class="px-8 py-6">
                                    <p class="text-xs font-bold text-slate-900 dark:text-white"><?= date('M d, Y', strtotime($log['created_at'])) ?></p>
                                    <p class="text-[10px] text-slate-500 font-mono"><?= date('H:i:s', strtotime($log['created_at'])) ?></p>
                                </td>
                                <td class="px-8 py-6">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-indigo-500/10 flex items-center justify-center text-[10px] font-black text-indigo-400 border border-indigo-500/20">
                                            <?= strtoupper(substr($log['user_name'] ?? 'S', 0, 1)) ?>
                                        </div>
                                        <div>
                                            <p class="text-xs font-black text-slate-900 dark:text-white"><?= $log['user_name'] ?? 'System' ?></p>
                                            <p class="text-[10px] text-slate-500 uppercase"><?= $log['user_role'] ?? 'Bot' ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-8 py-6">
                                    <span class="px-3 py-1 bg-white/5 border border-white/10 rounded-full text-[10px] font-black uppercase tracking-tighter text-indigo-400 group-hover:bg-indigo-500/20 transition-all">
                                        <?= str_replace('_', ' ', $log['action']) ?>
                                    </span>
                                </td>
                                <td class="px-8 py-6">
                                    <p class="text-xs font-bold uppercase text-slate-500"><?= $log['module'] ?></p>
                                </td>
                                <td class="px-8 py-6 max-w-xs">
                                    <p class="text-xs text-slate-600 dark:text-slate-400 truncate hover:text-clip hover:whitespace-normal cursor-help" title="Table: <?= $log['table_affected'] ?> | Record ID: <?= $log['record_id'] ?>">
                                        <?= $log['table_affected'] ?> (ID: <?= $log['record_id'] ?>)
                                    </p>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="5" class="px-8 py-20 text-center text-slate-500 italic">No activity logs found for the selected filters.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<script>
    lucide.createIcons();
</script>
</body>
</html>
