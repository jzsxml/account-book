<template>
  <div>
    <div class="flex items-center justify-between gap-2">
      <h1 class="text-xl font-bold text-slate-900 dark:text-slate-100">统计</h1>
      <button
        type="button"
        class="flex shrink-0 items-center gap-1 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-teal-700 dark:border-slate-600 dark:bg-slate-800 dark:text-teal-400"
        @click="filtersOpen = !filtersOpen"
      >
        {{ filtersOpen ? '收起筛选' : '筛选' }}
        <span
          v-if="!filtersOpen && hasActiveFilters"
          class="rounded-full bg-teal-100 px-1.5 text-xs text-teal-800 dark:bg-teal-950/60 dark:text-teal-200"
        >
          已选
        </span>
      </button>
    </div>

    <section v-show="filtersOpen" class="app-card mt-4 rounded-2xl border p-4">
      <p class="text-sm font-medium text-slate-600 dark:text-slate-300">筛选</p>
      <p class="mt-1 text-xs text-slate-400 dark:text-slate-500">填写日期区间后按区间统计；留空则按下方周/月/年。</p>
      <div class="mt-3 grid grid-cols-2 gap-2">
        <label class="text-xs text-slate-500 dark:text-slate-400">
          开始日期
          <input v-model="filters.from" type="date" class="app-field mt-1 w-full py-2 text-sm" />
        </label>
        <label class="text-xs text-slate-500 dark:text-slate-400">
          结束日期
          <input v-model="filters.to" type="date" class="app-field mt-1 w-full py-2 text-sm" />
        </label>
      </div>
      <label class="mt-3 block text-xs text-slate-500 dark:text-slate-400">
        分类
        <select v-model="filters.category_id" class="app-field mt-1 w-full py-2 text-sm">
          <option value="">全部分类</option>
          <option v-for="cat in metricCategories" :key="cat.id" :value="String(cat.id)">
            {{ cat.name }}
          </option>
        </select>
      </label>
      <p v-if="filterError" class="mt-2 text-sm text-rose-600 dark:text-rose-400">{{ filterError }}</p>
      <div class="mt-3 grid grid-cols-2 gap-2">
        <button
          type="button"
          class="rounded-xl border border-slate-200 py-2.5 text-sm font-medium text-slate-600 dark:border-slate-600 dark:text-slate-200"
          @click="resetFilters"
        >
          重置
        </button>
        <button
          type="button"
          class="rounded-xl bg-teal-600 py-2.5 text-sm font-medium text-white disabled:opacity-60"
          :disabled="loading"
          @click="load"
        >
          {{ loading ? '查询中…' : '应用' }}
        </button>
      </div>
    </section>

    <div class="mt-4 flex rounded-xl bg-slate-100 p-1 dark:bg-slate-800">
      <button
        v-for="p in periods"
        :key="p.value"
        type="button"
        class="flex-1 rounded-lg py-2 text-sm font-medium"
        :class="
          period === p.value
            ? 'bg-white text-teal-700 shadow dark:bg-slate-700 dark:text-teal-300'
            : 'text-slate-600 dark:text-slate-400'
        "
        @click="changePeriod(p.value)"
      >
        {{ p.label }}
      </button>
    </div>

    <div class="mt-3 flex rounded-xl bg-slate-100 p-1 dark:bg-slate-800">
      <button
        v-for="m in metrics"
        :key="m.value"
        type="button"
        class="flex-1 rounded-lg py-2 text-sm font-medium"
        :class="
          metric === m.value
            ? 'bg-white shadow dark:bg-slate-700 ' +
              (m.value === 'income'
                ? 'text-emerald-700 dark:text-emerald-400'
                : 'text-orange-700 dark:text-orange-400')
            : 'text-slate-600 dark:text-slate-400'
        "
        @click="changeMetric(m.value)"
      >
        {{ m.label }}
      </button>
    </div>

    <section v-if="overview && !hasActiveFilters" class="app-card mt-4 rounded-2xl border p-4 shadow-sm">
      <div class="flex flex-wrap items-center justify-between gap-2">
        <p class="text-sm font-medium text-slate-600 dark:text-slate-300">收支总览</p>
        <div class="flex gap-3 text-xs">
          <RouterLink
            v-if="period === 'month'"
            to="/stats/report"
            class="font-medium text-teal-700 dark:text-teal-400"
          >
            本月报告 ›
          </RouterLink>
          <RouterLink to="/bills" class="text-teal-600 dark:text-teal-400">年账单 ›</RouterLink>
        </div>
      </div>
      <div class="mt-3 grid grid-cols-3 gap-2 text-sm">
        <div>
          <p class="text-xs text-slate-500 dark:text-slate-400">收入</p>
          <p class="font-semibold text-emerald-600 dark:text-emerald-400">{{ formatMoney(overview.income) }}</p>
        </div>
        <div>
          <p class="text-xs text-slate-500 dark:text-slate-400">支出</p>
          <p class="font-semibold text-orange-600 dark:text-orange-400">{{ formatMoney(overview.expense) }}</p>
        </div>
        <div>
          <p class="text-xs text-slate-500 dark:text-slate-400">结余</p>
          <p class="font-semibold text-teal-700 dark:text-teal-400">{{ formatMoney(overview.balance) }}</p>
        </div>
      </div>
      <p
        v-if="period === 'week' || period === 'month'"
        class="mt-2 text-xs text-slate-500 dark:text-slate-400"
      >
        日均支出 {{ formatMoney(overview.daily_expense_avg) }}
      </p>
      <div v-if="period === 'month' && budgetAmount != null && budgetAmount > 0" class="mt-3">
        <p class="text-xs text-slate-500 dark:text-slate-400">
          月预算 {{ formatMoney(budgetAmount) }}（已用 {{ budgetUsedPercent }}%）
        </p>
        <div class="mt-1 h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-700">
          <div
            class="h-full rounded-full"
            :class="budgetUsedPercent > 100 ? 'bg-rose-500' : 'bg-teal-500'"
            :style="{ width: Math.min(budgetUsedPercent, 100) + '%' }"
          />
        </div>
      </div>
    </section>

    <section v-if="summary" class="app-card mt-6 rounded-2xl border p-5 shadow-sm">
      <p class="text-sm text-slate-500 dark:text-slate-400">{{ periodLabel }}{{ metricLabel }}</p>
      <p
        class="mt-1 text-3xl font-bold"
        :class="metric === 'income' ? 'text-emerald-600 dark:text-emerald-400' : 'text-orange-600 dark:text-orange-400'"
      >
        {{ formatMoney(summary.total) }}
      </p>
      <p v-if="summary.change_percent != null" class="mt-2 text-sm text-slate-500 dark:text-slate-400">
        较上期 {{ summary.change_percent > 0 ? '+' : '' }}{{ summary.change_percent }}%
      </p>
    </section>

    <section v-if="summary" class="app-card mt-4 rounded-2xl border p-4 shadow-sm">
      <h2 class="text-sm font-medium text-slate-600 dark:text-slate-300">{{ periodLabel }}{{ metricLabel }}趋势</h2>
      <StatsLineChart
        class="mt-3"
        :series="summary.series"
        :variant="metric"
        :label="`${periodLabel}${metricLabel}`"
      />
    </section>

    <section v-if="summary" class="mt-6">
      <h2 class="mb-3 text-sm font-medium text-slate-600 dark:text-slate-300">分类占比</h2>
      <div v-if="categoryRows.length" class="app-card mb-4 rounded-2xl border p-4 shadow-sm">
        <StatsPieChart :rows="categoryRows" :variant="metric" />
      </div>
      <ul v-if="categoryRows.length" class="space-y-3">
        <li
          v-for="row in categoryRows"
          :key="row.category_id"
          class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm dark:border-slate-600 dark:bg-slate-800"
        >
          <div class="flex items-center justify-between gap-2 text-sm">
            <span class="font-medium text-slate-800 dark:text-slate-100">{{ row.name }}</span>
            <span class="shrink-0 text-slate-600 dark:text-slate-300">
              <span
                class="font-semibold"
                :class="metric === 'income' ? 'text-emerald-600 dark:text-emerald-400' : 'text-orange-600 dark:text-orange-400'"
              >
                {{ row.percentText }}
              </span>
              <span class="ml-2 text-slate-500 dark:text-slate-400">{{ formatMoney(row.total) }}</span>
            </span>
          </div>
          <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-700">
            <div
              class="h-full rounded-full transition-all"
              :class="metric === 'income' ? 'bg-emerald-500' : 'bg-orange-500'"
              :style="{ width: row.percent + '%' }"
            />
          </div>
        </li>
      </ul>
      <p
        v-else
        class="app-card rounded-xl border py-8 text-center text-sm text-slate-400 shadow-sm dark:text-slate-500"
      >
        该周期暂无{{ metricLabel }}分类数据
      </p>
    </section>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { RouterLink } from 'vue-router';
import { getBudget, getCategories, getStats, getStatsOverview } from '../api/http';
import { formatMoney } from '../utils/money';
import StatsLineChart from '../components/StatsLineChart.vue';
import StatsPieChart from '../components/StatsPieChart.vue';

const periods = [
    { value: 'week', label: '周' },
    { value: 'month', label: '月' },
    { value: 'year', label: '年' },
];

const metrics = [
    { value: 'income', label: '收入' },
    { value: 'expense', label: '支出' },
];

const period = ref('month');
const metric = ref('expense');
const summary = ref(null);
const overview = ref(null);
const budgetAmount = ref(null);
const loading = ref(false);
const filtersOpen = ref(false);
const filterError = ref('');
const categories = ref([]);

const filters = reactive({
    from: '',
    to: '',
    category_id: '',
});

const periodLabel = computed(() => {
    if (summary.value?.period === 'custom' && summary.value?.range) {
        return `${summary.value.range.start} ~ ${summary.value.range.end} `;
    }
    return ({ week: '本周', month: '本月', year: '本年' })[period.value] || '';
});

const metricLabel = computed(() => (metric.value === 'income' ? '收入' : '支出'));

const metricCategories = computed(() => categories.value);

const hasActiveFilters = computed(
    () => Boolean(filters.from || filters.to || filters.category_id),
);

const budgetUsedPercent = computed(() => {
    if (!budgetAmount.value || !overview.value?.expense) {
        return 0;
    }
    return Math.round((overview.value.expense / budgetAmount.value) * 1000) / 10;
});

const categoryRows = computed(() => {
    const total = Number(summary.value?.total ?? 0);
    const rows = summary.value?.by_category ?? [];

    return rows.map((row) => {
        const percent = total > 0 ? (Number(row.total) / total) * 100 : 0;

        return {
            ...row,
            percent: Math.min(percent, 100),
            percentText: `${percent.toFixed(1)}%`,
        };
    });
});

function buildParams() {
    const params = {
        period: period.value,
        metric: metric.value,
    };
    if (filters.from && filters.to) {
        params.from = filters.from;
        params.to = filters.to;
    }
    if (filters.category_id) {
        params.category_id = filters.category_id;
    }
    return params;
}

function validateFilters() {
    if ((filters.from && !filters.to) || (!filters.from && filters.to)) {
        filterError.value = '请同时填写开始与结束日期';
        return false;
    }
    if (filters.from && filters.to && filters.from > filters.to) {
        filterError.value = '开始日期不能晚于结束日期';
        return false;
    }
    filterError.value = '';
    return true;
}

async function loadCategories() {
    categories.value = await getCategories(metric.value);
    if (filters.category_id) {
        const id = Number(filters.category_id);
        if (!categories.value.some((c) => c.id === id)) {
            filters.category_id = '';
        }
    }
}

async function load() {
    if (!validateFilters()) {
        return;
    }
    loading.value = true;
    try {
        const params = buildParams();
        summary.value = await getStats(params);
        if (filters.from && filters.to) {
            overview.value = null;
            budgetAmount.value = null;
        } else {
            overview.value = await getStatsOverview({ period: period.value });
            if (period.value === 'month') {
                const now = new Date();
                const bud = await getBudget(now.getFullYear(), now.getMonth() + 1);
                budgetAmount.value = bud.amount;
            } else {
                budgetAmount.value = null;
            }
        }
    } catch (e) {
        filterError.value = e.response?.data?.message || '加载失败';
        summary.value = null;
    } finally {
        loading.value = false;
    }
}

function changePeriod(value) {
    period.value = value;
    load();
}

function changeMetric(value) {
    metric.value = value;
    loadCategories().then(load);
}

function resetFilters() {
    filters.from = '';
    filters.to = '';
    filters.category_id = '';
    filterError.value = '';
    load();
}

watch(metric, () => {
    loadCategories();
});

onMounted(async () => {
    await loadCategories();
    await load();
});
</script>