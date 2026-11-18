<template>
  <div>
    <div class="mb-2">
      <RouterLink to="/stats" class="text-sm text-teal-600 dark:text-teal-400">‹ 返回统计</RouterLink>
    </div>
    <h1 class="text-xl font-bold text-slate-900 dark:text-slate-100">年账单</h1>

    <div class="mt-4 flex items-center gap-2">
      <label class="text-sm text-slate-600 dark:text-slate-300">年份</label>
      <select v-model.number="year" class="app-field py-2 text-sm" @change="load">
        <option v-for="y in yearOptions" :key="y" :value="y">{{ y }}</option>
      </select>
    </div>

    <section v-if="overview" class="app-card mt-4 rounded-2xl border p-4 shadow-sm">
      <p class="text-sm text-slate-500 dark:text-slate-400">{{ year }} 年度总览</p>
      <div class="mt-2 grid grid-cols-3 gap-2 text-sm">
        <div>
          <p class="text-xs text-slate-500 dark:text-slate-400">总收入</p>
          <p class="font-semibold text-emerald-600 dark:text-emerald-400">{{ formatMoney(overview.income) }}</p>
        </div>
        <div>
          <p class="text-xs text-slate-500 dark:text-slate-400">总支出</p>
          <p class="font-semibold text-orange-600 dark:text-orange-400">{{ formatMoney(overview.expense) }}</p>
        </div>
        <div>
          <p class="text-xs text-slate-500 dark:text-slate-400">总结余</p>
          <p class="font-semibold text-teal-700 dark:text-teal-400">{{ formatMoney(overview.balance) }}</p>
        </div>
      </div>
      <p v-if="overview.savings_rate != null" class="mt-2 text-xs text-slate-500 dark:text-slate-400">
        储蓄率 {{ overview.savings_rate }}%
      </p>
    </section>

    <ul v-if="overview" class="mt-4 space-y-2">
      <li v-for="m in overview.months" :key="m.month">
        <RouterLink
          :to="{ path: '/transactions', query: { from: m.from, to: m.to } }"
          class="app-card block rounded-xl border p-4 shadow-sm"
        >
          <div class="flex items-center justify-between">
            <span class="font-medium text-slate-900 dark:text-slate-100">{{ m.label }}</span>
            <span class="text-sm text-teal-600 dark:text-teal-400">明细 ›</span>
          </div>
          <div class="mt-2 grid grid-cols-3 gap-2 text-xs text-slate-600 dark:text-slate-300">
            <span>收入 {{ formatMoney(m.income) }}</span>
            <span>支出 {{ formatMoney(m.expense) }}</span>
            <span>结余 {{ formatMoney(m.balance) }}</span>
          </div>
        </RouterLink>
      </li>
    </ul>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import { getBillsOverview } from '../api/http';
import { formatMoney } from '../utils/money';

const year = ref(new Date().getFullYear());
const overview = ref(null);

const yearOptions = computed(() => {
    const y = new Date().getFullYear();
    return [y, y - 1, y - 2];
});

async function load() {
    overview.value = await getBillsOverview(year.value);
}

onMounted(load);
</script>