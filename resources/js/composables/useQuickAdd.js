import { inject, provide, ref } from 'vue';

const QUICK_ADD_KEY = Symbol('quickAdd');

export function provideQuickAdd() {
    const sheetOpen = ref(false);
    const toast = ref('');
    const toastCelebrate = ref(false);
    const refreshTick = ref(0);
    let toastTimer = null;

    function openQuickAdd() {
        sheetOpen.value = true;
    }

    function closeQuickAdd() {
        sheetOpen.value = false;
    }

    function showToast(message) {
        if (!message) {
            return;
        }
        toast.value = message;
        toastCelebrate.value = true;
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => {
            toast.value = '';
            toastCelebrate.value = false;
        }, 3200);
    }

    function notifyTransactionSaved(message) {
        refreshTick.value += 1;
        showToast(message);
    }

    const api = {
        sheetOpen,
        toast,
        toastCelebrate,
        refreshTick,
        openQuickAdd,
        closeQuickAdd,
        showToast,
        notifyTransactionSaved,
    };

    provide(QUICK_ADD_KEY, api);

    return api;
}

export function useQuickAdd() {
    const api = inject(QUICK_ADD_KEY, null);
    if (!api) {
        throw new Error('useQuickAdd must be used within MobileLayout');
    }
    return api;
}

export function useQuickAddOptional() {
    return inject(QUICK_ADD_KEY, null);
}