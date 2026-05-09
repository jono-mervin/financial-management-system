<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/auth.php';

check_login();

if ($_SESSION['role'] !== 'Admin') {
    header("Location: " . BASE_URL . "index.php");
    exit();
}

// Handle AJAX User Locking/Unlocking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_lock') {
    header('Content-Type: application/json');
    $user_id = $_POST['user_id'] ?? null;
    $new_status = $_POST['status'] ?? null;

    if (!$user_id || ($new_status !== '0' && $new_status !== '1')) {
        echo json_encode(['success' => false, 'message' => 'Invalid parameters.']);
        exit();
    }

    try {
        $stmt = $pdo->prepare("UPDATE users SET is_locked = ? WHERE user_id = ?");
        $stmt->execute([$new_status, $user_id]);
        
        // Log the action
        $action = ($new_status === '1') ? 'LOCK_USER' : 'UNLOCK_USER';
        $stmt_log = $pdo->prepare("INSERT INTO audit_log (user_id, action, module, record_id) VALUES (?, ?, 'admin', ?)");
        $stmt_log->execute([$_SESSION['user_id'], $action, $user_id]);

        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}

// Search and Filter
$search = $_GET['search'] ?? '';
$role_filter = $_GET['role'] ?? '';

$query = "SELECT * FROM users WHERE 1=1";
$params = [];

if ($search) {
    $query .= " AND (name LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($role_filter) {
    $query .= " AND role = ?";
    $params[] = $role_filter;
}

$query .= " ORDER BY created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll();

$roles = ['Admin', 'Finance', 'Manager', 'Staff', 'Accountant', 'Cashier', 'DeptHead'];

include __DIR__ . '/../../includes/header_dashboard.php';
?>

<div class="flex">
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <main class="ml-72 flex-grow p-10 min-h-screen">
        <header class="mb-10 animate-fade-in px-8 py-12 glass rounded-[2.5rem] border border-white/10 dark:border-white/5 relative overflow-hidden shadow-2xl">
            <div class="absolute inset-0 bg-gradient-to-r from-teal-500/10 via-transparent to-transparent pointer-events-none"></div>
            <div class="relative flex justify-between items-center">
                <div>
                    <h2 class="text-4xl font-black tracking-tight uppercase text-slate-900 dark:text-white">User <span class="gradient-text">Management</span></h2>
                    <p class="text-slate-500 text-sm font-bold mt-2 uppercase tracking-widest">System Access & Roles</p>
                </div>
                <div class="flex gap-4">
                    <div class="px-6 py-4 glass rounded-2xl border border-white/5 flex items-center gap-3">
                        <div class="w-2 h-2 rounded-full bg-teal-500 animate-pulse"></div>
                        <span class="text-xs font-black uppercase tracking-widest text-slate-500"><?= count($users) ?> Total Accounts</span>
                    </div>
                </div>
            </div>
        </header>

        <!-- Filters -->
        <div class="glass p-8 rounded-[2rem] mb-10 border border-white/5">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-6 items-end">
                <div class="space-y-2">
                    <label class="text-xs font-black uppercase tracking-widest text-slate-500 ml-1">Search Account</label>
                    <div class="relative">
                        <i data-lucide="search" class="absolute left-5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"></i>
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Name or email..." 
                               class="w-full bg-slate-100 dark:bg-slate-900/50 border border-white/5 rounded-2xl pl-12 pr-5 py-4 focus:ring-2 focus:ring-teal-500/50 outline-none text-sm font-bold">
                    </div>
                </div>
                <div class="space-y-2">
                    <label class="text-xs font-black uppercase tracking-widest text-slate-500 ml-1">Filter by Role</label>
                    <select name="role" class="w-full bg-slate-100 dark:bg-slate-900/50 border border-white/5 rounded-2xl px-5 py-4 focus:ring-2 focus:ring-teal-500/50 outline-none text-sm font-bold">
                        <option value="">All Roles</option>
                        <?php foreach ($roles as $r): ?>
                            <option value="<?= $r ?>" <?= $role_filter === $r ? 'selected' : '' ?>><?= $r ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="flex gap-4">
                    <button type="submit" class="flex-grow py-4 bg-teal-500 hover:bg-teal-600 text-white dark:text-slate-900 font-black rounded-2xl transition-all shadow-xl shadow-teal-500/20 uppercase tracking-widest text-xs">
                        Refine Search
                    </button>
                    <a href="users.php" class="px-6 py-4 glass rounded-2xl border border-white/5 hover:bg-white/5 transition-all text-xs font-black flex items-center uppercase tracking-widest">Reset</a>
                </div>
            </form>
        </div>

        <!-- Users Table -->
        <div class="glass rounded-[2.5rem] overflow-hidden border border-white/5 shadow-2xl">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-slate-100 dark:bg-white/5 border-b border-white/5">
                        <tr>
                            <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-500">Account</th>
                            <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-500">Role</th>
                            <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-500">Joined Date</th>
                            <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-500">Status</th>
                            <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-500 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        <?php foreach ($users as $user): ?>
                            <tr class="hover:bg-white/5 transition-colors group">
                                <td class="px-8 py-6">
                                    <div class="flex items-center gap-4">
                                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-slate-100 to-slate-200 dark:from-white/5 dark:to-white/10 flex items-center justify-center text-lg font-black text-slate-400 border border-white/5 shadow-sm">
                                            <?= strtoupper(substr($user['name'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <p class="text-sm font-black text-slate-900 dark:text-white"><?= htmlspecialchars($user['name']) ?></p>
                                            <p class="text-xs text-slate-500 font-medium"><?= htmlspecialchars($user['email']) ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-8 py-6">
                                    <span class="px-3 py-1 bg-teal-500/10 border border-teal-500/20 rounded-full text-[10px] font-black uppercase tracking-widest text-teal-500">
                                        <?= $user['role'] ?>
                                    </span>
                                </td>
                                <td class="px-8 py-6">
                                    <p class="text-xs font-bold text-slate-700 dark:text-slate-300"><?= date('M d, Y', strtotime($user['created_at'])) ?></p>
                                    <p class="text-[10px] text-slate-500 mt-0.5"><?= date('h:i A', strtotime($user['created_at'])) ?></p>
                                </td>
                                <td class="px-8 py-6">
                                    <?php if ($user['is_locked']): ?>
                                        <span class="flex items-center gap-2 text-rose-500">
                                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                            <span class="text-[10px] font-black uppercase tracking-widest">Locked</span>
                                        </span>
                                    <?php else: ?>
                                        <span class="flex items-center gap-2 text-teal-500">
                                            <span class="w-1.5 h-1.5 rounded-full bg-teal-500"></span>
                                            <span class="text-[10px] font-black uppercase tracking-widest">Active</span>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-8 py-6 text-right">
                                    <?php if ($user['user_id'] != $_SESSION['user_id']): ?>
                                        <div class="flex items-center justify-end gap-2">
                                            <button onclick="toggleUserLock(<?= $user['user_id'] ?>, <?= $user['is_locked'] ?>, '<?= htmlspecialchars($user['name']) ?>')" 
                                                    class="p-3 glass rounded-xl border border-white/5 hover:bg-rose-500/10 hover:text-rose-500 transition-all group/btn" title="<?= $user['is_locked'] ? 'Unlock' : 'Lock' ?>">
                                                <i data-lucide="<?= $user['is_locked'] ? 'unlock' : 'lock' ?>" class="w-5 h-5"></i>
                                            </button>
                                            <button onclick="adminDelete('user', <?= $user['user_id'] ?>, '<?= htmlspecialchars($user['name']) ?>')"
                                                    class="p-3 glass rounded-xl border border-white/5 hover:bg-rose-500/20 text-rose-500 hover:text-rose-400 transition-all" title="Delete User">
                                                <i data-lucide="trash-2" class="w-5 h-5"></i>
                                            </button>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-[10px] font-black uppercase tracking-widest text-slate-500 opacity-50 px-3">You</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="5" class="px-8 py-20 text-center">
                                    <div class="flex flex-col items-center gap-3">
                                        <i data-lucide="users" class="w-12 h-12 text-slate-300 dark:text-slate-700"></i>
                                        <p class="text-slate-500 font-bold">No matching accounts found.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<script>
    function toggleUserLock(userId, isLocked, userName) {
        const action = isLocked ? 'unlock' : 'lock';
        const title = isLocked ? 'Unlock Account' : 'Lock Account';
        const message = isLocked 
            ? `Are you sure you want to unlock <b>${userName}</b>'s account? They will regain access to the portal immediately.`
            : `Are you sure you want to lock <b>${userName}</b>'s account? They will be unable to sign in until manually unlocked.`;
        
        SMS_UI.showConfirm({
            title: title,
            message: message,
            confirmText: isLocked ? 'Unlock Access' : 'Lock Account',
            type: isLocked ? 'success' : 'error',
            onConfirm: () => {
                const formData = new FormData();
                formData.append('action', 'toggle_lock');
                formData.append('user_id', userId);
                formData.append('status', isLocked ? '0' : '1');

                fetch('users.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        SMS_UI.showToast(`Account successfully ${isLocked ? 'unlocked' : 'locked'}.`, 'success');
                        setTimeout(() => window.location.reload(), 1000);
                    } else {
                        SMS_UI.showToast(data.message || 'Action failed.', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    SMS_UI.showToast('A system error occurred.', 'error');
                });
            }
        });
    }

    lucide.createIcons();
</script>
<script src="<?= BASE_URL ?>assets/js/admin_delete.js"></script>
</body>
</html>
