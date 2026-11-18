import { inject, provide, ref } from 'vue';

const CONFIRM_KEY = Symbol('confirm');

export function provideConfirm() {
    const open = ref(false);
    const payload = ref({
        title: '确认删除',
        message: '',
        confirmText: '删除',
        cancelText: '取消',
    });
    let resolver = null;

    function ask(message, opts = {}) {
        payload.value = {
            title: opts.title ?? '确认删除',
            message,
            confirmText: opts.confirmText ?? '删除',
            cancelText: opts.cancelText ?? '取消',
        };
        open.value = true;
        return new Promise((resolve) => {
            resolver = resolve;
        });
    }

    function settle(ok) {
        open.value = false;
        resolver?.(ok);
        resolver = null;
    }

    function confirm() {
        settle(true);
    }

    function cancel() {
        settle(false);
    }

    const api = { open, payload, ask, confirm, cancel };
    provide(CONFIRM_KEY, api);
    return api;
}

export function useConfirm() {
    const api = inject(CONFIRM_KEY, null);
    if (!api) {
        return {
            ask: (message) => Promise.resolve(window.confirm(message)),
        };
    }
    return api;
}