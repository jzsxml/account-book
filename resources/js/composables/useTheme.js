import { ref } from 'vue';

const STORAGE_KEY = 'account-book-theme';

export const isDarkMode = ref(false);

export const THEME_OPTIONS = [
    { value: 'light', label: '白天' },
    { value: 'dark', label: '黑夜' },
    { value: 'system', label: '跟随系统' },
];

export function getStoredTheme() {
    return localStorage.getItem(STORAGE_KEY) || 'dark';
}

export function resolveIsDark(mode) {
    if (mode === 'dark') {
        return true;
    }
    if (mode === 'light') {
        return false;
    }
    return window.matchMedia('(prefers-color-scheme: dark)').matches;
}

export function applyTheme(mode) {
    const dark = resolveIsDark(mode);
    isDarkMode.value = dark;
    document.documentElement.classList.toggle('dark', dark);
    document.querySelector('meta[name="theme-color"]')?.setAttribute(
        'content',
        dark ? '#0f172a' : '#0f766e',
    );
    localStorage.setItem(STORAGE_KEY, mode);
}

let systemListenerAttached = false;

export function initTheme() {
    const mode = getStoredTheme();
    applyTheme(mode);

    if (!systemListenerAttached) {
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
            if (getStoredTheme() === 'system') {
                applyTheme('system');
            }
        });
        systemListenerAttached = true;
    }
}

export function useTheme() {
    function setTheme(mode) {
        applyTheme(mode);
    }

    return {
        getStoredTheme,
        setTheme,
        THEME_OPTIONS,
    };
}