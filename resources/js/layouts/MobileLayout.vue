<template>
  <div class="mx-auto flex min-h-dvh max-w-lg flex-col pb-[calc(4.5rem+env(safe-area-inset-bottom))]">
    <main class="flex-1 px-4 pt-4">
      <router-view />
    </main>

    <AppToast />
    <QuickAddSheet />
    <ConfirmDialog />

    <nav
      class="fixed bottom-0 left-0 right-0 z-50 border-t border-slate-200 bg-white/95 backdrop-blur dark:border-slate-700 dark:bg-slate-900/95"
      style="padding-bottom: env(safe-area-inset-bottom)"
    >
      <div class="mx-auto grid max-w-lg grid-cols-5 items-end gap-0 px-1 py-1.5 text-[10px] sm:text-xs">
        <RouterLink to="/" :class="navItemClass(isHomeActive)">
          <NavIcon name="home" />
          <span class="mt-0.5 leading-tight">首页</span>
        </RouterLink>
        <RouterLink to="/stats" :class="navItemClass(isStatsActive)">
          <NavIcon name="stats" />
          <span class="mt-0.5 leading-tight">统计</span>
        </RouterLink>
        <div class="flex flex-col items-center justify-end pb-0.5">
          <button
            type="button"
            class="flex h-12 w-12 -translate-y-3 items-center justify-center rounded-full text-white shadow-lg ring-4 ring-white transition dark:ring-slate-900"
            :class="
              sheetOpen
                ? 'scale-105 bg-teal-700 shadow-teal-700/40 ring-teal-100 dark:ring-teal-900/80'
                : 'bg-teal-600 shadow-teal-600/35'
            "
            aria-label="记一笔"
            @click="openQuickAdd"
          >
            <NavIcon name="add" />
          </button>
          <span
            class="mt-0.5 leading-tight transition"
            :class="sheetOpen ? 'font-semibold text-teal-700 dark:text-teal-400' : 'text-slate-500 dark:text-slate-400'"
          >
            记账
          </span>
        </div>
        <RouterLink to="/transactions" :class="navItemClass(isLedgerActive)">
          <NavIcon name="ledger" />
          <span class="mt-0.5 leading-tight">明细</span>
        </RouterLink>
        <RouterLink to="/settings" :class="navItemClass(isSettingsActive)">
          <NavIcon name="user" />
          <span class="mt-0.5 leading-tight">我的</span>
        </RouterLink>
      </div>
    </nav>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import AppToast from '../components/AppToast.vue';
import ConfirmDialog from '../components/ConfirmDialog.vue';
import QuickAddSheet from '../components/QuickAddSheet.vue';
import NavIcon from '../components/NavIcon.vue';
import { provideConfirm } from '../composables/useConfirm';
import { provideQuickAdd } from '../composables/useQuickAdd';

const route = useRoute();
provideConfirm();
const { openQuickAdd, sheetOpen } = provideQuickAdd();

const isHomeActive = computed(() => route.name === 'home');

const isStatsActive = computed(() =>
    ['stats', 'stats-report', 'bills'].includes(String(route.name ?? '')),
);

const isLedgerActive = computed(() =>
    ['transactions', 'transaction-edit'].includes(String(route.name ?? '')),
);

const isSettingsActive = computed(() =>
    ['settings', 'tools'].includes(String(route.name ?? '')),
);

function navItemClass(active) {
    return [
        'flex flex-col items-center rounded-lg py-1.5 transition',
        active
            ? 'bg-teal-50 font-semibold text-teal-700 dark:bg-slate-800 dark:text-teal-400'
            : 'text-slate-500 dark:text-slate-400',
    ];
}
</script>