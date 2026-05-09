/**
 * Admin Delete Helper - AURA SMS
 * Provides adminDelete() function for all modules.
 * Requires SMS_UI.showConfirm and SMS_UI.showToast from the global UI library.
 */

const ADMIN_DELETE_URL = (function() {
    // Build path relative to any page depth
    const scripts = document.querySelectorAll('script[src]');
    for (const s of scripts) {
        if (s.src.includes('admin_delete.js')) {
            return s.src.replace('assets/js/admin_delete.js', 'modules/admin/delete_handler.php');
        }
    }
    return '/commission/sms/financial/modules/admin/delete_handler.php';
})();

function adminDelete(type, id, label) {
    SMS_UI.showConfirm({
        title: 'Delete Record',
        message: `Are you sure you want to permanently delete <b>${label}</b>? This action cannot be undone.`,
        confirmText: 'Delete',
        type: 'error',
        onConfirm: () => {
            const fd = new FormData();
            fd.append('type', type);
            fd.append('id', id);

            fetch(ADMIN_DELETE_URL, { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        SMS_UI.showToast(data.message || 'Deleted successfully.', 'success');
                        // Remove row from DOM gracefully
                        const btn = document.activeElement;
                        const row = btn?.closest('tr') || btn?.closest('div[class*="flex items-center justify-between"]');
                        if (row) {
                            row.style.transition = 'opacity 0.4s';
                            row.style.opacity = '0';
                            setTimeout(() => row.remove(), 400);
                        } else {
                            setTimeout(() => window.location.reload(), 800);
                        }
                    } else {
                        SMS_UI.showToast(data.message || 'Delete failed.', 'error');
                    }
                })
                .catch(() => SMS_UI.showToast('A network error occurred.', 'error'));
        }
    });
}
