<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php';
check_login();

// Handle Supplemental Request
if (isset($_POST['request_revision'])) {
    // Logic for realignment or supplemental budget
    // For now, redirect with success simulated
    header("Location: " . BASE_URL . "modules/budget/index.php?msg=" . urlencode('Budget revision request submitted') . "&type=success");
    exit();
}

include __DIR__ . '/../../includes/header_dashboard.php';
?>

<div class="flex">
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <main class="ml-72 flex-grow p-10 min-h-screen">
        <header class="flex justify-between items-center mb-10">
            <div>
                <h2 class="text-3xl font-bold">Budget <span class="gradient-text">Revision</span></h2>
                <p class="text-slate-500 dark:text-slate-400 mt-1">Request supplemental funds or realign existing budget items.</p>
            </div>
        </header>

        <div class="max-w-2xl">
            <div class="glass p-10 rounded-[2.5rem] border border-white/5 space-y-8">
                <div class="p-6 bg-indigo-500/10 rounded-3xl border border-indigo-500/20">
                    <p class="text-xs font-bold text-indigo-400 uppercase tracking-widest mb-2">Policy Note</p>
                    <p class="text-xs text-slate-400 leading-relaxed">All revisions require board-level approval and must include a detailed justification. Realignments between departments are restricted.</p>
                </div>

                <form method="POST" class="space-y-6">
                    <div class="space-y-2">
                        <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Type of Revision</label>
                        <select name="type" class="w-full bg-slate-900 border border-white/5 rounded-2xl px-5 py-4 outline-none">
                            <option>Supplemental Budget Request</option>
                            <option>Inter-Account Realignment</option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Justification</label>
                        <textarea class="w-full bg-slate-900 border border-white/5 rounded-2xl px-5 py-4 outline-none min-h-[150px]" placeholder="Explain why this revision is necessary..."></textarea>
                    </div>
                    <button type="submit" name="request_revision" class="w-full py-4 bg-indigo-500 text-white font-black rounded-2xl shadow-xl shadow-indigo-500/20">
                        Submit Revision Request
                    </button>
                </form>
            </div>
        </div>
    </main>
</div>

<script>
    lucide.createIcons();
</script>
</body>
</html>
