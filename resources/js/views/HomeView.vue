<template>
  <div class="space-y-4">
    <p
      v-if="loadError"
      class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-900 dark:bg-rose-950/50 dark:text-rose-200"
    >
      {{ loadError }}
      <button type="button" class="ml-2 font-medium underline" @click="load">重试</button>
    </p>

    <header>
      <p class="text-sm text-slate-500 dark:text-slate-400">今天</p>
      <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100">记账本</h1>
    </header>

    <section v-if="habits" class="app-card rounded-2xl border px-4 py-3 shadow-sm">
      <div class="flex flex-wrap items-center justify-between gap-2">
        <div class="text-sm text-slate-700 dark:text-slate-200">
          <span class="font-semibold text-teal-700 dark:text-teal-400">连续 {{ habits.streak_days }} 天</span>
          <span class="text-slate-500 dark:text-slate-400"> · 本周 {{ habits.week_transaction_count }} 笔</span>
        </div>
        <p class="text-xs text-slate-500 dark:text-slate-400">
          <template v-if="habits.logged_today">今日已记账</template>
          <template v-else>今日还未记账，记得点 +</template>
          <span v-if="habits.best_streak_days > habits.streak_days" class="ml-1">
            · 最佳 {{ habits.best_streak_days }} 天
          </span>
        </p>
      </div>
      <StreakMilestoneBadges :milestones="habits.milestones || []" />
    </section>

    <section v-if="scheduleHints.length" class="app-card rounded-2xl border p-4 shadow-sm">
      <div class="flex items-center justify-between">
        <p class="text-sm font-medium text-slate-700 dark:text-slate-200">固定账单日</p>
        <RouterLink to="/settings" class="text-xs text-teal-600 dark:text-teal-400">设置</RouterLink>
      </div>
      <ul class="mt-2 space-y-2 text-sm">
        <li
          v-for="h in scheduleHints"
          :key="h.label"
          class="flex flex-wrap items-center justify-between gap-1 rounded-lg bg-slate-50 px-3 py-2 dark:bg-slate-800/50"
        >
          <span class="text-slate-700 dark:text-slate-200">
            <span
              class="mr-1 rounded px-1 text-[10px]"
              :class="
                h.kind === 'income'
                  ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300'
                  : 'bg-orange-100 text-orange-800 dark:bg-orange-950/60 dark:text-orange-300'
              "
            >
              {{ h.kind === 'income' ? '收入' : '支出' }}
            </span>
            {{ h.label }} · 每月 {{ h.day }} 号
          </span>
          <span
            class="text-xs font-medium"
            :class="
              h.is_today
                ? 'text-teal-700 dark:text-teal-400'
                : h.days_until <= 3
                  ? 'text-orange-600 dark:text-orange-400'
                  : 'text-slate-500 dark:text-slate-400'
            "
          >
            <template v-if="h.is_today">就是今天，记得记账</template>
            <template v-else-if="h.days_until <= 3">还有 {{ h.days_until }} 天</template>
            <template v-else>下次 {{ h.next_date }}</template>
          </span>
        </li>
      </ul>
    </section>

    <section class="app-card grid grid-cols-3 gap-2 rounded-2xl border p-4 shadow-sm">
      <div>
        <p class="text-xs text-slate-500 dark:text-slate-400">今日收入</p>
        <p class="mt-1 text-lg font-semibold text-emerald-600 dark:text-emerald-400">
          {{ loading ? '…' : formatMoney(today.income) }}
        </p>
      </div>
      <div>
        <p class="text-xs text-slate-500 dark:text-slate-400">今日支出</p>
        <p class="mt-1 text-lg font-semibold text-orange-600 dark:text-orange-400">
          {{ loading ? '…' : formatMoney(today.expense) }}
        </p>
      </div>
      <div>
        <p class="text-xs text-slate-500 dark:text-slate-400">今日结余</p>
        <p class="mt-1 text-lg font-semibold text-teal-700 dark:text-teal-400">
          {{ loading ? '…' : formatMoney(today.balance) }}
        </p>
      </div>
    </section>

    <section v-if="monthOverview" class="app-card rounded-2xl border p-4 shadow-sm">
      <div class="flex items-center justify-between">
        <p class="text-sm font-medium text-slate-700 dark:text-slate-200">本月</p>
        <div class="flex gap-2 text-xs">
          <RouterLink to="/stats/report" class="text-teal-700 dark:text-teal-400">本月报告 ›</RouterLink>
          <RouterLink to="/stats" class="text-teal-600 dark:text-teal-400">统计 ›</RouterLink>
        </div>
      </div>
      <div class="mt-2 grid grid-cols-2 gap-3 text-sm">
        <div>
          <p class="text-xs text-slate-500 dark:text-slate-400">结余</p>
          <p class="font-semibold text-teal-700 dark:text-teal-400">{{ formatMoney(monthOverview.balance) }}</p>
        </div>
        <div v-if="budgetAmount > 0">
          <p class="text-xs text-slate-500 dark:text-slate-400">预算已用</p>
          <p
            class="font-semibold"
            :class="budgetUsedPercent > 100 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-800 dark:text-slate-100'"
          >
            {{ budgetUsedPercent }}%
          </p>
        </div>
      </div>
    </section>

    <section class="app-card rounded-2xl border shadow-sm">
      <button
        type="button"
        class="flex w-full items-center justify-between px-4 py-3 text-left"
        @click="todayOpen = !todayOpen"
      >
        <div>
          <h2 class="font-semibold text-slate-800 dark:text-slate-100">今日明细</h2>
          <p class="text-xs text-slate-500 dark:text-slate-400">{{ todayList.length }} 笔</p>
        </div>
        <span class="text-sm text-teal-600 dark:text-teal-400">{{ todayOpen ? '收起' : '展开' }}</span>
      </button>
      <ul v-show="todayOpen" class="divide-y divide-slate-100 border-t border-slate-100 dark:divide-slate-700 dark:border-slate-700">
        <li v-for="item in todayList" :key="item.id" class="flex items-center justify-between px-4 py-3">
          <div>
            <p class="font-medium text-slate-900 dark:text-slate-100">{{ item.category?.name }}</p>
            <p class="text-xs text-slate-500 dark:text-slate-400">{{ formatTime(item.occurred_at) }}</p>
          </div>
          <span
            :class="
              item.type === 'income'
                ? 'text-emerald-600 dark:text-emerald-400'
                : 'text-orange-600 dark:text-orange-400'
            "
          >
            {{ item.type === 'income' ? '+' : '-' }}{{ formatMoney(item.amount) }}
          </span>
        </li>
        <li v-if="!todayList.length && !loading" class="px-4 py-6 text-center text-sm text-slate-400 dark:text-slate-500">
          今天还没有记录，点底部「+」记一笔吧
        </li>
      </ul>
      <div v-if="todayOpen" class="border-t border-slate-100 px-4 py-2 dark:border-slate-700">
        <RouterLink
          :to="{ path: '/transactions', query: { from: todayStr, to: todayStr } }"
          class="block text-center text-sm text-teal-700 dark:text-teal-400"
        >
          查看当日全部明细
        </RouterLink>
      </div>
    </section>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { RouterLink } from 'vue-router';
import {
    getBookkeepingHabits,
    getBookSchedules,
    getBudget,
    getStatsOverview,
    getTransactions,
} from '../api/http';
import { useQuickAddOptional } from '../composables/useQuickAdd';
import { localDateString } from '../utils/dates';
import StreakMilestoneBadges from '../components/StreakMilestoneBadges.vue';
import { formatMoney } from '../utils/money';

const quickAdd = useQuickAddOptional();

const loading = ref(true);
const todayOpen = ref(true);
const todayStr = localDateString();
const today = reactive({ income: 0, expense: 0, balance: 0 });
const todayList = ref([]);
const monthOverview = ref(null);
const budgetAmount = ref(0);
const habits = ref(null);
const schedules = ref(null);
const loadError = ref('');

const scheduleHints = computed(() => schedules.value?.summaries ?? []);

const budgetUsedPercent = computed(() => {
    if (!budgetAmount.value || !monthOverview.value?.expense) {
        return 0;
    }
    return Math.round((monthOverview.value.expense / budgetAmount.value) * 1000) / 10;
});

function formatTime(value) {
    return new Date(value).toLocaleString('zh-CN', { hour: '2-digit', minute: '2-digit' });
}

async function load() {
    loading.value = true;
    loadError.value = '';
    const now = new Date();
    const tasks = await Promise.allSettled([
        getStatsOverview({ period: 'day' }),
        getTransactions({ from: todayStr, to: todayStr }),
        getStatsOverview({ period: 'month' }),
        getBudget(now.getFullYear(), now.getMonth() + 1),
        getBookkeepingHabits(),
        getBookSchedules(),
    ]);
    const failed = tasks.filter((t) => t.status === 'rejected');
    if (failed.length) {
        const first = failed[0];
        const msg = first.reason?.response?.data?.message || first.reason?.message || '部分数据加载失败';
        loadError.value =
            failed.length >= tasks.length
                ? `${msg}（请确认已登录且执行过 php artisan migrate）`
                : `${msg}（其余内容已尽量展示）`;
    }

    const pick = (index, fallback = null) =>
        tasks[index].status === 'fulfilled' ? tasks[index].value : fallback;

    const dayOverview = pick(0);
    const txRes = pick(1, { data: [] });
    const monthOv = pick(2);
    const budget = pick(3, {});
    const habitsData = pick(4);
    const sched = pick(5, { summaries: [], items: [] });

    if (dayOverview) {
        today.income = dayOverview.income;
        today.expense = dayOverview.expense;
        today.balance = dayOverview.balance;
    }
    todayList.value = txRes?.data || [];
    monthOverview.value = monthOv;
    budgetAmount.value = Number(budget?.amount) || 0;
    habits.value = habitsData;
    schedules.value = sched;

    loading.value = false;
}

onMounted(load);

if (quickAdd) {
    watch(
        () => quickAdd.refreshTick.value,
        () => {
            load();
        },
    );
}
</script>