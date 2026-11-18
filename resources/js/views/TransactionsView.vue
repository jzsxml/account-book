<template>
  <div>
    <div class="flex items-center justify-between gap-2">
      <h1 class="text-xl font-bold text-slate-900 dark:text-slate-100">明细</h1>
      <div class="flex shrink-0 items-center gap-2">
        <button
          type="button"
          class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 disabled:opacity-60 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200"
          :disabled="exporting"
          @click="exportExcel"
        >
          {{ exporting ? '导出中…' : '导出 Excel' }}
        </button>
        <button
          type="button"
          class="flex items-center gap-1 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-teal-700 dark:border-slate-600 dark:bg-slate-800 dark:text-teal-400"
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
    </div>
    <p v-if="filterError" class="mt-2 text-sm text-rose-600 dark:text-rose-400">{{ filterError }}</p>
    <p class="mt-1 text-[11px] text-slate-400 dark:text-slate-500">导出将包含符合当前筛选条件的全部流水（不限本页）。</p>

    <section v-show="filtersOpen" class="app-card mt-4 rounded-2xl border p-4">
      <p class="text-sm font-medium text-slate-600 dark:text-slate-300">检索</p>
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
        类型
        <select v-model="filters.type" class="app-field mt-1 w-full py-2 text-sm" @change="onTypeChange">
          <option value="">全部</option>
          <option value="expense">支出</option>
          <option value="income">收入</option>
        </select>
      </label>
      <label class="mt-3 block text-xs text-slate-500 dark:text-slate-400">
        分类
        <select v-model="filters.category_id" class="app-field mt-1 w-full py-2 text-sm">
          <option value="">全部分类</option>
          <optgroup v-if="expenseCategories.length" label="支出">
            <option
              v-for="cat in expenseCategories"
              :key="'e-' + cat.id"
              :value="String(cat.id)"
            >
              {{ cat.name }}
            </option>
          </optgroup>
          <optgroup v-if="incomeCategories.length" label="收入">
            <option
              v-for="cat in incomeCategories"
              :key="'i-' + cat.id"
              :value="String(cat.id)"
            >
              {{ cat.name }}
            </option>
          </optgroup>
        </select>
      </label>
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
          @click="search"
        >
          {{ loading ? '查询中…' : '查询' }}
        </button>
      </div>
    </section>

    <ul class="mt-4 space-y-2">
      <li
        v-for="item in items"
        :key="item.id"
        class="flex items-center justify-between rounded-xl border border-slate-200 bg-white px-4 py-3 dark:border-slate-600 dark:bg-slate-800"
      >
        <div>
          <p class="font-medium text-slate-900 dark:text-slate-100">{{ item.category?.name }}</p>
          <p class="text-xs text-slate-500 dark:text-slate-400">{{ formatDate(item.occurred_at) }}</p>
          <p v-if="item.note" class="text-xs text-slate-400 dark:text-slate-500">{{ item.note }}</p>
          <p v-if="attachmentCount(item)" class="text-xs text-teal-600 dark:text-teal-400">
            📎 {{ attachmentCount(item) }} 个附件
          </p>
        </div>
        <div class="text-right">
          <p :class="item.type === 'income' ? 'text-emerald-600 dark:text-emerald-400' : 'text-orange-600 dark:text-orange-400'">
            {{ item.type === 'income' ? '+' : '-' }}{{ formatMoney(item.amount) }}
          </p>
          <div class="mt-1 flex justify-end gap-3 text-xs">
            <router-link :to="`/transactions/${item.id}/edit`" class="text-teal-600 dark:text-teal-400">编辑</router-link>
            <button type="button" class="text-rose-500 dark:text-rose-400" @click="remove(item.id)">删除</button>
          </div>
        </div>
      </li>
    </ul>
    <p v-if="!loading && !items.length" class="py-10 text-center text-slate-400 dark:text-slate-500">暂无流水</p>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import { deleteTransaction, exportTransactionsExcel, getCategories, getTransactions } from '../api/http';
import { useConfirm } from '../composables/useConfirm';
import { formatMoney } from '../utils/money';

const { ask: askConfirm } = useConfirm();

const route = useRoute();
const items = ref([]);
const loading = ref(false);
const exporting = ref(false);
const filtersOpen = ref(false);
const filterError = ref('');
const allExpense = ref([]);
const allIncome = ref([]);

const filters = reactive({
    from: '',
    to: '',
    type: '',
    category_id: '',
});

const expenseCategories = computed(() => {
    if (filters.type === 'income') {
        return [];
    }
    return allExpense.value;
});

const incomeCategories = computed(() => {
    if (filters.type === 'expense') {
        return [];
    }
    return allIncome.value;
});

const hasActiveFilters = computed(
    () => Boolean(filters.from || filters.to || filters.type || filters.category_id),
);

function formatDate(value) {
    return new Date(value).toLocaleString('zh-CN');
}

function attachmentCount(item) {
    return item.metadata?.attachments?.length ?? 0;
}

function buildParams() {
    const params = {};
    if (filters.from) {
        params.from = filters.from;
    }
    if (filters.to) {
        params.to = filters.to;
    }
    if (filters.type) {
        params.type = filters.type;
    }
    if (filters.category_id) {
        params.category_id = filters.category_id;
    }
    return params;
}

function validateFilters() {
    if (filters.from && filters.to && filters.from > filters.to) {
        filterError.value = '开始日期不能晚于结束日期';
        return false;
    }
    filterError.value = '';
    return true;
}

async function loadCategories() {
    const [expense, income] = await Promise.all([
        getCategories('expense'),
        getCategories('income'),
    ]);
    allExpense.value = expense;
    allIncome.value = income;
}

function onTypeChange() {
    if (!filters.category_id) {
        return;
    }
    const id = Number(filters.category_id);
    const inExpense = allExpense.value.some((c) => c.id === id);
    const inIncome = allIncome.value.some((c) => c.id === id);
    if (filters.type === 'expense' && !inExpense) {
        filters.category_id = '';
    }
    if (filters.type === 'income' && !inIncome) {
        filters.category_id = '';
    }
}

async function load() {
    if (!validateFilters()) {
        return;
    }
    loading.value = true;
    try {
        const data = await getTransactions(buildParams());
        items.value = data.data || [];
    } catch (e) {
        filterError.value = e.response?.data?.message || '加载失败';
        items.value = [];
    } finally {
        loading.value = false;
    }
}

function search() {
    load();
}

async function exportExcel() {
    if (!validateFilters()) {
        return;
    }
    exporting.value = true;
    filterError.value = '';
    try {
        await exportTransactionsExcel(buildParams());
    } catch (e) {
        filterError.value = e.response?.data?.message || e.message || '导出失败';
    } finally {
        exporting.value = false;
    }
}

function resetFilters() {
    filters.from = '';
    filters.to = '';
    filters.type = '';
    filters.category_id = '';
    filterError.value = '';
    load();
}

async function remove(id) {
    const item = items.value.find((row) => row.id === id);
    const detail = item
        ? `「${item.category?.name ?? '未分类'}」${item.type === 'income' ? '+' : '-'}${formatMoney(item.amount)}`
        : '该条流水';
    const ok = await askConfirm(`确定删除 ${detail} 吗？删除后无法恢复。`);
    if (!ok) return;
    await deleteTransaction(id);
    await load();
}

function applyRouteQuery() {
    const { from, to } = route.query;
    if (from && to) {
        filters.from = String(from);
        filters.to = String(to);
        filtersOpen.value = true;
    }
}

onMounted(async () => {
    applyRouteQuery();
    await loadCategories();
    await load();
});

watch(
    () => route.query,
    () => {
        applyRouteQuery();
        load();
    },
);
</script>