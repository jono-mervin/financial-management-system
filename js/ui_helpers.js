/**
 * UI Helpers for SMS Financial Module
 * Provides global toast notifications and confirmation modals
 */

const UI = {
    toastContainer: null,
    modalContainer: null,

    init() {
        // Create containers if they don't exist
        if (!document.getElementById('toast-container')) {
            this.toastContainer = document.createElement('div');
            this.toastContainer.id = 'toast-container';
            this.toastContainer.className = 'fixed top-6 right-6 z-[100] flex flex-col gap-3 pointer-events-none';
            document.body.appendChild(this.toastContainer);
        } else {
            this.toastContainer = document.getElementById('toast-container');
        }

        if (!document.getElementById('modal-container')) {
            this.modalContainer = document.createElement('div');
            this.modalContainer.id = 'modal-container';
            this.modalContainer.className = 'fixed inset-0 z-[110] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm hidden opacity-0 transition-opacity duration-300';
            document.body.appendChild(this.modalContainer);
        } else {
            this.modalContainer = document.getElementById('modal-container');
        }
    },

    /**
     * Show a toast notification
     * @param {string} message 
     * @param {'success' | 'error' | 'info' | 'warning'} type 
     * @param {number} duration 
     */
    showToast(message, type = 'success', duration = 4000) {
        if (!this.toastContainer) this.init();

        const toast = document.createElement('div');
        const colors = {
            success: 'border-teal-500/50 text-teal-500 bg-teal-500/10',
            error: 'border-rose-500/50 text-rose-500 bg-rose-500/10',
            warning: 'border-amber-500/50 text-amber-500 bg-amber-500/10',
            info: 'border-indigo-500/50 text-indigo-500 bg-indigo-500/10'
        };

        const icons = {
            success: 'check-circle',
            error: 'alert-circle',
            warning: 'alert-triangle',
            info: 'info'
        };

        toast.className = `glass pointer-events-auto flex items-center gap-4 px-6 py-4 rounded-2xl border ${colors[type]} shadow-2xl min-w-[300px] animate-slide-in-right transition-all duration-300 transform translate-x-full`;
        
        toast.innerHTML = `
            <i data-lucide="${icons[type]}" class="w-6 h-6"></i>
            <p class="text-sm font-bold tracking-tight">${message}</p>
        `;

        this.toastContainer.appendChild(toast);
        lucide.createIcons({ props: { class: 'w-6 h-6' } });

        // Trigger animation
        requestAnimationFrame(() => {
            toast.classList.remove('translate-x-full');
        });

        // Remove toast
        setTimeout(() => {
            toast.classList.add('translate-x-full', 'opacity-0');
            setTimeout(() => toast.remove(), 300);
        }, duration);
    },

    /**
     * Show a confirmation modal
     * @param {Object} options { title, message, onConfirm, confirmText, cancelText, type }
     */
    showConfirm({ title, message, onConfirm, confirmText = 'Confirm', cancelText = 'Cancel', type = 'info' }) {
        if (!this.modalContainer) this.init();

        const colors = {
            success: 'bg-teal-500 shadow-teal-500/20',
            error: 'bg-rose-500 shadow-rose-500/20',
            info: 'bg-indigo-500 shadow-indigo-500/20',
            warning: 'bg-amber-500 shadow-amber-500/20'
        };

        this.modalContainer.innerHTML = `
            <div class="glass max-w-md w-full p-8 rounded-[2.5rem] border border-white/10 shadow-2xl transform scale-95 transition-transform duration-300">
                <h3 class="text-2xl font-black mb-4 dark:text-white text-slate-900">${title}</h3>
                <p class="text-slate-500 dark:text-slate-400 mb-8 leading-relaxed">${message}</p>
                <div class="flex gap-4">
                    <button id="modal-cancel" class="flex-1 py-4 px-6 rounded-2xl border border-slate-200 dark:border-white/5 hover:bg-slate-100 dark:hover:bg-white/5 text-sm font-bold transition-all uppercase tracking-widest">
                        ${cancelText}
                    </button>
                    <button id="modal-confirm" class="flex-1 py-4 px-6 rounded-2xl ${colors[type]} text-white dark:text-slate-900 text-sm font-black transition-all shadow-xl uppercase tracking-widest">
                        ${confirmText}
                    </button>
                </div>
            </div>
        `;

        const modalBox = this.modalContainer.querySelector('div');
        this.modalContainer.classList.remove('hidden');
        
        requestAnimationFrame(() => {
            this.modalContainer.classList.add('opacity-100');
            modalBox.classList.remove('scale-95');
            modalBox.classList.add('scale-100');
        });

        const closeModal = () => {
            this.modalContainer.classList.remove('opacity-100');
            modalBox.classList.remove('scale-100');
            modalBox.classList.add('scale-95');
            setTimeout(() => {
                this.modalContainer.classList.add('hidden');
                this.modalContainer.innerHTML = '';
            }, 300);
        };

        document.getElementById('modal-cancel').onclick = closeModal;
        document.getElementById('modal-confirm').onclick = () => {
            if (onConfirm) onConfirm();
            closeModal();
        };
    }
};

// Initialize on load
document.addEventListener('DOMContentLoaded', () => UI.init());

// Export globally
window.SMS_UI = UI;
