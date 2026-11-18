<template>
  <div>
    <div class="mb-2">
      <RouterLink to="/stats" class="text-sm text-teal-600 dark:text-teal-400">‹ 返回统计</RouterLink>
    </div>
    <div class="flex items-center justify-between gap-2">
      <h1 class="text-xl font-bold text-slate-900 dark:text-slate-100">本月报告</h1>
      <div class="flex gap-1">
        <button
          type="button"
          class="rounded-lg border border-slate-200 px-2 py-1 text-sm dark:border-slate-600"
          :disabled="loading"
          @click="shiftMonth(-1)"
        >
          ‹
        </button>
        <button
          type="button"
          class="rounded-lg border border-slate-200 px-2 py-1 text-sm dark:border-slate-600"
          :disabled="loading"
          @click="shiftMonth(1)"
        >
          ›
        </button>
      </div>
    </div>
    <p v-if="report" class="mt-1 text-sm text-slate-500 dark:text-slate-400">
      {{ report.label }}
      <span v-if="report.range">（{{ report.range.start }} ~ {{ report.range.end }}）</span>
    </p>

    <p v-if="loading" class="mt-8 text-center text-slate-400">加载中…</p>
    <p v-else-if="error" class="mt-6 text-sm text-rose-600 dark:text-rose-400">{{ error }}</p>

    <template v-else-if="report">
      <section class="app-card mt-4 grid grid-cols-3 gap-2 rounded-2xl border p-4 shadow-sm">
        <div>
          <p class="text-xs text-slate-500 dark:text-slate-400">收入</p>
          <p class="font-semibold text-emerald-600 dark:text-emerald-400">{{ formatMoney(report.income) }}</p>
        </div>
        <div>
          <p class="text-xs text-slate-500 dark:text-slate-400">支出</p>
          <p class="font-semibold text-orange-600 dark:text-orange-400">{{ formatMoney(report.expense) }}</p>
        </div>
        <div>
          <p class="text-xs text-slate-500 dark:text-slate-400">结余</p>
          <p class="font-semibold text-teal-700 dark:text-teal-400">{{ formatMoney(report.balance) }}</p>
        </div>
      </section>

      <section class="app-card mt-3 rounded-2xl border p-4 text-sm shadow-sm">
        <div class="grid grid-cols-2 gap-3">
          <div>
            <p class="text-xs text-slate-500 dark:text-slate-400">日均支出</p>
            <p class="font-medium text-slate-800 dark:text-slate-100">{{ formatMoney(report.daily_expense_avg) }}</p>
          </div>
          <div v-if="report.savings_rate != null">
            <p class="text-xs text-slate-500 dark:text-slate-400">储蓄率</p>
            <p class="font-medium text-slate-800 dark:text-slate-100">{{ report.savings_rate }}%</p>
          </div>
          <div>
            <p class="text-xs text-slate-500 dark:text-slate-400">记账笔数</p>
            <p class="font-medium text-slate-800 dark:text-slate-100">
              共 {{ report.transaction_count.total }} 笔（支出 {{ report.transaction_count.expense }} / 收入
              {{ report.transaction_count.income }}）
            </p>
          </div>
        </div>

        <div v-if="report.budget?.amount" class="mt-4">
          <p class="text-xs text-slate-500 dark:text-slate-400">
            月预算 {{ formatMoney(report.budget.amount) }}
            <span v-if="report.budget.used_percent != null">（已用 {{ report.budget.used_percent }}%）</span>
          </p>
          <div class="mt-1 h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-700">
            <div
              class="h-full rounded-full"
              :class="(report.budget.used_percent || 0) > 100 ? 'bg-rose-500' : 'bg-teal-500'"
              :style="{ width: Math.min(report.budget.used_percent || 0, 100) + '%' }"
            />
          </div>
        </div>
      </section>

      <section class="mt-4">
        <h2 class="mb-2 text-sm font-medium text-slate-600 dark:text-slate-300">支出类目排行</h2>
        <ul v-if="report.expense_by_category.length" class="space-y-2">
          <li
            v-for="row in report.expense_by_category"
            :key="row.category_id"
            class="app-card rounded-xl border px-4 py-3 shadow-sm"
          >
            <div class="flex items-center justify-between text-sm">
              <span class="font-medium text-slate-900 dark:text-slate-100">{{ row.name }}</span>
              <span class="text-orange-600 dark:text-orange-400">{{ formatMoney(row.total) }}</span>
            </div>
            <div class="mt-2 flex items-center gap-2">
              <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-700">
                <div class="h-full rounded-full bg-orange-400" :style="{ width: row.percent + '%' }" />
              </div>
              <span class="text-xs text-slate-500 dark:text-slate-400">{{ row.percent }}%</span>
            </div>
          </li>
        </ul>
        <p v-else class="text-sm text-slate-400">本月暂无支出记录</p>
      </section>

      <section class="mt-6">
        <h2 class="mb-2 text-sm font-medium text-slate-600 dark:text-slate-300">单笔支出 TOP</h2>
        <ul v-if="report.top_expenses.length" class="space-y-2">
          <li
            v-for="row in report.top_expenses"
            :key="row.id"
            class="app-card rounded-xl border px-4 py-3 shadow-sm"
          >
            <RouterLink :to="`/transactions/${row.id}/edit`" class="flex items-center justify-between gap-2">
              <div class="min-w-0">
                <p class="truncate font-medium text-slate-900 dark:text-slate-100">
                  {{ row.category?.name || '未分类' }}
                </p>
                <p v-if="row.note" class="truncate text-xs text-slate-500 dark:text-slate-400">{{ row.note }}</p>
              </div>
              <span class="shrink-0 font-semibold text-orange-600 dark:text-orange-400">
                {{ formatMoney(row.amount) }}
              </span>
            </RouterLink>
          </li>
        </ul>
        <p v-else class="text-sm text-slate-400">暂无数据</p>
      </section>

      <RouterLink
        v-if="report.range"
        :to="{ path: '/transactions', query: { from: report.range.start, to: report.range.end } }"
        class="app-card mt-6 block rounded-2xl border py-3 text-center text-sm font-medium text-teal-700 shadow-sm dark:text-teal-400"
      >
        查看本月全部明细
      </RouterLink>
    </template>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import { getMonthReport } from '../api/http';
import { formatMoney } from '../utils/money';

const loading = ref(true);
const error = ref('');
const report = ref(null);
const anchor = ref(null);

function monthAnchorFromDate(date) {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    return `${y}-${m}-01`;
}

function shiftMonth(delta) {
    const base = anchor.value ? new Date(anchor.value) : new Date();
    base.setDate(1);
    base.setMonth(base.getMonth() + delta);
    anchor.value = monthAnchorFromDate(base);
    load();
}

async function load() {
    loading.value = true;
    error.value = '';
    try {
        report.value = await getMonthReport(anchor.value || undefined);
        if (!anchor.value && report.value?.range?.start) {
            anchor.value = monthAnchorFromDate(new Date(report.value.range.start));
        }
    } catch (e) {
        error.value = e.response?.data?.message || '加载失败';
        report.value = null;
    } finally {
        loading.value = false;
    }
}

onMounted(load);
</script>